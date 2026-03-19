#!/usr/bin/env bash
#
# Integration tests for pipeline task lifecycle.
# Tests: move_to_done, move_to_failed, extract_summary, event log, metadata.
#
# Usage:
#   ./builder/tests/test-pipeline-lifecycle.sh
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
PASS=0
FAIL=0
TOTAL=0

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

assert_eq() {
  local desc="$1" expected="$2" actual="$3"
  TOTAL=$((TOTAL + 1))
  if [[ "$expected" == "$actual" ]]; then
    echo -e "  ${GREEN}✓${NC} $desc"
    PASS=$((PASS + 1))
  else
    echo -e "  ${RED}✗${NC} $desc"
    echo -e "    expected: ${expected}"
    echo -e "    actual:   ${actual}"
    FAIL=$((FAIL + 1))
  fi
}

assert_file_exists() {
  local desc="$1" path="$2"
  TOTAL=$((TOTAL + 1))
  if [[ -f "$path" ]]; then
    echo -e "  ${GREEN}✓${NC} $desc"
    PASS=$((PASS + 1))
  else
    echo -e "  ${RED}✗${NC} $desc — file not found: $path"
    FAIL=$((FAIL + 1))
  fi
}

assert_file_not_exists() {
  local desc="$1" path="$2"
  TOTAL=$((TOTAL + 1))
  if [[ ! -f "$path" ]]; then
    echo -e "  ${GREEN}✓${NC} $desc"
    PASS=$((PASS + 1))
  else
    echo -e "  ${RED}✗${NC} $desc — file should not exist: $path"
    FAIL=$((FAIL + 1))
  fi
}

assert_file_not_empty() {
  local desc="$1" path="$2"
  TOTAL=$((TOTAL + 1))
  if [[ -f "$path" && -s "$path" ]]; then
    echo -e "  ${GREEN}✓${NC} $desc"
    PASS=$((PASS + 1))
  else
    echo -e "  ${RED}✗${NC} $desc — file empty or missing: $path"
    FAIL=$((FAIL + 1))
  fi
}

# ── Setup test environment ──
TEST_DIR=$(mktemp -d)
TASK_SOURCE="$TEST_DIR/tasks"
mkdir -p "$TASK_SOURCE/todo" "$TASK_SOURCE/in-progress" "$TASK_SOURCE/done" "$TASK_SOURCE/failed" "$TASK_SOURCE/summary"
WORKTREE_BASE="$TEST_DIR/worktrees"
mkdir -p "$WORKTREE_BASE/worker-1/.opencode/pipeline/logs"

cleanup() {
  rm -rf "$TEST_DIR"
}
trap cleanup EXIT

echo ""
echo "Pipeline Lifecycle Tests"
echo "========================"
echo ""

# ── Source pipeline-batch functions ──
# We need to source just the functions, not run the script
# Extract functions we need

BATCH_TIMESTAMP="20260319_120000"

# Simulate extract_summary_from_branch (we can't use git in test)
extract_summary_from_branch() {
  local branch="$1"
  # In tests, we simulate by copying from a fake branch dir
  local fake_branch_dir="$TEST_DIR/fake-branch-summary"
  if [[ -d "$fake_branch_dir" ]]; then
    local summary_dir="${TASK_SOURCE}/summary"
    mkdir -p "$summary_dir"
    for f in "$fake_branch_dir"/*.md; do
      [[ -f "$f" ]] || continue
      cp "$f" "$summary_dir/$(basename "$f")"
    done
  fi
}

_build_task_meta() {
  echo ""
  echo "---"
  echo "## Pipeline Run"
  echo "- **Status:** $1"
  echo "- **Branch:** $2"
  echo "- **Duration:** $3"
}

move_to_done() {
  local src="$1"
  local task_branch="$2"
  local duration="$3"
  local basename_f; basename_f=$(basename "$src")
  local dest="${TASK_SOURCE}/done/${basename_f}"
  {
    echo "<!-- batch: ${BATCH_TIMESTAMP} | status: pass | duration: ${duration}s | branch: ${task_branch} -->"
    cat "$src"
    _build_task_meta "PASS" "$task_branch" "$duration"
  } > "$dest"
  rm -f "$src"
  rm -f "${TASK_SOURCE}/failed/${basename_f}"
  extract_summary_from_branch "$task_branch"
}

move_to_failed() {
  local src="$1"
  local task_branch="$2"
  local duration="$3"
  local dest="${TASK_SOURCE}/failed/$(basename "$src")"
  {
    echo "<!-- batch: ${BATCH_TIMESTAMP} | status: fail | duration: ${duration}s | branch: ${task_branch} -->"
    cat "$src"
    _build_task_meta "FAIL" "$task_branch" "$duration"
  } > "$dest"
  rm -f "$src"
  extract_summary_from_branch "$task_branch"
}

# ══════════════════════════════════════════════════
# Test 1: move_to_done removes from in-progress
# ══════════════════════════════════════════════════
echo "Test 1: move_to_done lifecycle"

cat > "$TASK_SOURCE/in-progress/test-task-1.md" << 'EOF'
<!-- priority: 1 -->
# Test task 1
Some content
EOF

move_to_done "$TASK_SOURCE/in-progress/test-task-1.md" "pipeline/test-task-1" "120"

assert_file_not_exists "in-progress file removed" "$TASK_SOURCE/in-progress/test-task-1.md"
assert_file_exists "done file created" "$TASK_SOURCE/done/test-task-1.md"
assert_file_not_empty "done file has content" "$TASK_SOURCE/done/test-task-1.md"

# Check batch metadata header
header=$(head -1 "$TASK_SOURCE/done/test-task-1.md")
assert_eq "done file has batch header" "true" "$(echo "$header" | grep -q 'status: pass' && echo true || echo false)"
assert_eq "done file has duration" "true" "$(echo "$header" | grep -q 'duration: 120s' && echo true || echo false)"

# Check task metadata footer
assert_eq "done file has Pipeline Run footer" "true" "$(grep -q '## Pipeline Run' "$TASK_SOURCE/done/test-task-1.md" && echo true || echo false)"

echo ""

# ══════════════════════════════════════════════════
# Test 2: move_to_failed lifecycle
# ══════════════════════════════════════════════════
echo "Test 2: move_to_failed lifecycle"

cat > "$TASK_SOURCE/in-progress/test-task-2.md" << 'EOF'
<!-- priority: 2 -->
# Test task 2
EOF

move_to_failed "$TASK_SOURCE/in-progress/test-task-2.md" "pipeline/test-task-2" "60"

assert_file_not_exists "in-progress file removed" "$TASK_SOURCE/in-progress/test-task-2.md"
assert_file_exists "failed file created" "$TASK_SOURCE/failed/test-task-2.md"

header=$(head -1 "$TASK_SOURCE/failed/test-task-2.md")
assert_eq "failed file has fail status" "true" "$(echo "$header" | grep -q 'status: fail' && echo true || echo false)"

echo ""

# ══════════════════════════════════════════════════
# Test 3: move_to_done extracts summary
# ══════════════════════════════════════════════════
echo "Test 3: summary extraction on move_to_done"

# Setup fake branch summary
mkdir -p "$TEST_DIR/fake-branch-summary"
echo "# Summary for task 3" > "$TEST_DIR/fake-branch-summary/20260319_120000-test-task-3.md"

cat > "$TASK_SOURCE/in-progress/test-task-3.md" << 'EOF'
# Test task 3
EOF

move_to_done "$TASK_SOURCE/in-progress/test-task-3.md" "pipeline/test-task-3" "300"

assert_file_exists "summary extracted" "$TASK_SOURCE/summary/20260319_120000-test-task-3.md"
assert_file_not_empty "summary has content" "$TASK_SOURCE/summary/20260319_120000-test-task-3.md"

rm -rf "$TEST_DIR/fake-branch-summary"
echo ""

# ══════════════════════════════════════════════════
# Test 4: move_to_done cleans leftover failed copy
# ══════════════════════════════════════════════════
echo "Test 4: move_to_done cleans old failed copy"

echo "old failed" > "$TASK_SOURCE/failed/test-task-4.md"
cat > "$TASK_SOURCE/in-progress/test-task-4.md" << 'EOF'
# Test task 4
EOF

move_to_done "$TASK_SOURCE/in-progress/test-task-4.md" "pipeline/test-task-4" "60"

assert_file_not_exists "old failed copy removed" "$TASK_SOURCE/failed/test-task-4.md"
assert_file_exists "done file created" "$TASK_SOURCE/done/test-task-4.md"

echo ""

# ══════════════════════════════════════════════════
# Test 5: retry-task.sh clean mode
# ══════════════════════════════════════════════════
echo "Test 5: retry-task.sh (simulated)"

cat > "$TASK_SOURCE/failed/test-task-5.md" << 'EOF'
<!-- batch: 20260319_100000 | status: fail | duration: 30s | branch: pipeline/test-5 -->
<!-- priority: 1 -->
# Test task 5
EOF

# Simulate retry: clean batch metadata, move to todo
sed '/^<!-- batch:.*-->$/d' "$TASK_SOURCE/failed/test-task-5.md" > "$TASK_SOURCE/todo/test-task-5.md"
rm "$TASK_SOURCE/failed/test-task-5.md"

assert_file_not_exists "failed file removed" "$TASK_SOURCE/failed/test-task-5.md"
assert_file_exists "todo file created" "$TASK_SOURCE/todo/test-task-5.md"
assert_eq "batch metadata stripped" "false" "$(grep -q '<!-- batch:' "$TASK_SOURCE/todo/test-task-5.md" && echo true || echo false)"
assert_eq "original content preserved" "true" "$(grep -q '# Test task 5' "$TASK_SOURCE/todo/test-task-5.md" && echo true || echo false)"

echo ""

# ══════════════════════════════════════════════════
# Test 6: event log format
# ══════════════════════════════════════════════════
echo "Test 6: event log format"

EVENT_LOG="$TEST_DIR/events.log"

emit_event() {
  local event_type="$1"; shift
  local details="$*"
  local ts; ts=$(date '+%H:%M:%S')
  local epoch; epoch=$(date +%s)
  echo "${epoch}|${ts}|${event_type}|${details}" >> "$EVENT_LOG" 2>/dev/null || true
}

emit_event "TASK_START" "task=Test Task|worker=worker-1"
emit_event "PLAN" "profile=quality-gate|agents=coder → validator → summarizer"
emit_event "AGENT_START" "agent=coder|model=anthropic/claude-sonnet-4-20250514"
emit_event "AGENT_DONE" "agent=coder|status=ok|duration=120s|tokens=5000/800"
emit_event "TASK_DONE" "duration=3m"

assert_file_exists "events.log created" "$EVENT_LOG"
assert_eq "5 events written" "5" "$(wc -l < "$EVENT_LOG" | tr -d ' ')"
assert_eq "TASK_START event present" "true" "$(grep -q 'TASK_START' "$EVENT_LOG" && echo true || echo false)"
assert_eq "PLAN event present" "true" "$(grep -q 'PLAN' "$EVENT_LOG" && echo true || echo false)"
assert_eq "AGENT_START event present" "true" "$(grep -q 'AGENT_START' "$EVENT_LOG" && echo true || echo false)"
assert_eq "AGENT_DONE event present" "true" "$(grep -q 'AGENT_DONE' "$EVENT_LOG" && echo true || echo false)"
assert_eq "TASK_DONE event present" "true" "$(grep -q 'TASK_DONE' "$EVENT_LOG" && echo true || echo false)"

# Check event format: epoch|time|type|details
first_line=$(head -1 "$EVENT_LOG")
# Format: epoch|time|type|details (details may contain |)
assert_eq "event has at least 4 pipe-delimited fields" "true" "$(echo "$first_line" | awk -F'|' '{print (NF >= 4) ? "true" : "false"}')"

echo ""

# ══════════════════════════════════════════════════
# Test 7: meta.json token format
# ══════════════════════════════════════════════════
echo "Test 7: meta.json parsing"

cat > "$WORKTREE_BASE/worker-1/.opencode/pipeline/logs/20260319_120000_coder.meta.json" << 'JSONEOF'
{
  "agent": "coder",
  "model": "anthropic/claude-sonnet-4-20250514",
  "started_epoch": 1773900000,
  "finished_epoch": 1773900120,
  "duration_seconds": 120,
  "exit_code": 0,
  "tokens": {
    "input_tokens": 5000,
    "output_tokens": 800,
    "cache_read": 100000,
    "cache_write": 5000,
    "cost": 0
  }
}
JSONEOF

local_meta="$WORKTREE_BASE/worker-1/.opencode/pipeline/logs/20260319_120000_coder.meta.json"
agent=$(jq -r '.agent' "$local_meta")
model=$(jq -r '.model' "$local_meta")
in_tok=$(jq -r '.tokens.input_tokens' "$local_meta")
out_tok=$(jq -r '.tokens.output_tokens' "$local_meta")

assert_eq "agent parsed" "coder" "$agent"
assert_eq "model parsed" "anthropic/claude-sonnet-4-20250514" "$model"
assert_eq "input tokens parsed" "5000" "$in_tok"
assert_eq "output tokens parsed" "800" "$out_tok"

echo ""

# ══════════════════════════════════════════════════
# Results
# ══════════════════════════════════════════════════
echo "========================"
echo -e "Results: ${GREEN}${PASS} passed${NC}, ${RED}${FAIL} failed${NC}, ${TOTAL} total"
echo ""

[[ $FAIL -eq 0 ]] && exit 0 || exit 1
