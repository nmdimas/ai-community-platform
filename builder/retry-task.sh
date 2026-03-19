#!/usr/bin/env bash
#
# Retry failed pipeline tasks.
#
# Usage:
#   ./builder/retry-task.sh                     # retry all failed (clean)
#   ./builder/retry-task.sh --resume            # retry all failed (resume from last agent)
#   ./builder/retry-task.sh <slug>              # retry one task (clean)
#   ./builder/retry-task.sh --resume <slug>     # retry one task (resume)
#   ./builder/retry-task.sh --list              # list failed tasks
#
# Modes:
#   clean  (default)  — delete checkpoints, branches, worktree. Fresh start.
#   --resume          — keep checkpoints. Builder continues from the agent that crashed.
#
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
TASK_DIR="${REPO_ROOT}/builder/tasks"
ARTIFACT_DIR="${TASK_DIR}/artifacts"
WORKTREE_BASE="${REPO_ROOT}/.pipeline-worktrees"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
DIM='\033[2m'
BOLD='\033[1m'
NC='\033[0m'

MODE="clean"
TARGET=""

# Parse args
while [[ $# -gt 0 ]]; do
  case "$1" in
    --resume) MODE="resume"; shift ;;
    --list|-l) MODE="list"; shift ;;
    --clean) MODE="clean"; shift ;;
    --help|-h)
      sed -n '3,15p' "$0" | sed 's/^# \?//'
      exit 0
      ;;
    *) TARGET="$1"; shift ;;
  esac
done

# ── List mode ──
if [[ "$MODE" == "list" ]]; then
  echo -e "${BOLD}Failed tasks:${NC}"
  for f in "$TASK_DIR/failed"/*.md; do
    [[ -f "$f" ]] || { echo -e "  ${DIM}(none)${NC}"; exit 0; }
    local_name=$(basename "$f" .md)
    dur=$(head -1 "$f" | grep -oP 'duration: \K[0-9]+' || echo "?")
    status=$(head -1 "$f" | grep -oP 'status: \K\w+' || echo "?")
    branch=$(head -1 "$f" | grep -oP 'branch: \K[^ ]+' | sed 's/ -->.*//' || echo "?")
    slug=$(echo "$local_name" | sed 's/^implement-change-//;s/^finish-change-/finish-/;s/^implement-//')
    has_checkpoint="no"
    [[ -f "$ARTIFACT_DIR/$local_name/checkpoint.json" ]] && has_checkpoint="yes"
    echo -e "  ${RED}✗${NC} ${local_name}  ${DIM}(${dur}s, checkpoint: ${has_checkpoint})${NC}"
  done
  exit 0
fi

# ── Collect tasks to retry ──
tasks=()
if [[ -n "$TARGET" ]]; then
  # Find matching task
  match=$(ls "$TASK_DIR/failed"/*"${TARGET}"*.md 2>/dev/null | head -1 || true)
  if [[ -z "$match" ]]; then
    echo -e "${RED}No failed task matching '${TARGET}'${NC}"
    echo -e "${DIM}Available:${NC}"
    ls "$TASK_DIR/failed"/*.md 2>/dev/null | xargs -I{} basename {} .md | sed 's/^/  /'
    exit 1
  fi
  tasks+=("$match")
else
  for f in "$TASK_DIR/failed"/*.md; do
    [[ -f "$f" ]] && tasks+=("$f")
  done
fi

if [[ ${#tasks[@]} -eq 0 ]]; then
  echo -e "${DIM}No failed tasks to retry${NC}"
  exit 0
fi

echo -e "${BOLD}Retrying ${#tasks[@]} task(s) in ${CYAN}${MODE}${NC} mode${NC}"
echo ""

for f in "${tasks[@]}"; do
  name=$(basename "$f" .md)
  slug=$(echo "$name" | sed 's/^implement-change-//;s/^finish-change-/finish-/;s/^implement-//')

  echo -e "  ${YELLOW}↺${NC} ${name}"

  # Clean batch metadata from task file, move to todo
  sed '/^<!-- batch:.*-->$/d' "$f" > "$TASK_DIR/todo/$name.md"
  rm "$f"

  if [[ "$MODE" == "clean" ]]; then
    # Delete checkpoint
    if [[ -d "$ARTIFACT_DIR/$name" ]]; then
      rm -rf "$ARTIFACT_DIR/$name"
      echo -e "    ${DIM}cleaned checkpoint${NC}"
    fi

    # Delete pipeline branch
    branch_name="pipeline/$name"
    if git -C "$REPO_ROOT" rev-parse --verify "$branch_name" &>/dev/null; then
      git -C "$REPO_ROOT" branch -D "$branch_name" 2>/dev/null && \
        echo -e "    ${DIM}deleted branch ${branch_name}${NC}"
    fi
    # Also try slug-based branch name
    for branch in $(git -C "$REPO_ROOT" branch | grep "pipeline/" | grep "$slug" | tr -d ' *'); do
      git -C "$REPO_ROOT" branch -D "$branch" 2>/dev/null && \
        echo -e "    ${DIM}deleted branch ${branch}${NC}"
    done
  else
    # Resume mode — keep artifacts
    if [[ -f "$ARTIFACT_DIR/$name/checkpoint.json" ]]; then
      local_agent=$(jq -r 'to_entries | map(select(.value.status == "failed" or .value.status == null)) | .[0].key // "unknown"' "$ARTIFACT_DIR/$name/checkpoint.json" 2>/dev/null || echo "unknown")
      echo -e "    ${GREEN}keeping checkpoint → will resume from: ${CYAN}${local_agent}${NC}"
    else
      echo -e "    ${DIM}no checkpoint found, will start fresh${NC}"
    fi
  fi
done

# Clear batch lock
rm -f "$REPO_ROOT/.opencode/pipeline/.batch.lock"

# Clean worktree if in clean mode
if [[ "$MODE" == "clean" ]]; then
  for wt in "$WORKTREE_BASE"/worker-*; do
    [[ -d "$wt" ]] || continue
    git -C "$REPO_ROOT" worktree remove --force "$wt" 2>/dev/null || rm -rf "$wt"
  done
  git -C "$REPO_ROOT" worktree prune 2>/dev/null || true
  echo ""
  echo -e "  ${DIM}cleaned worktrees${NC}"
fi

echo ""
echo -e "${GREEN}✓ ${#tasks[@]} task(s) moved to todo/${NC}"
echo -e "${DIM}Start monitor to begin: ./builder/monitor/pipeline-monitor.sh${NC}"
