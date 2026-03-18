#!/usr/bin/env bash
# Builder Agent — post-clone setup
# Run once after git clone to create local directories.
#
# Usage: ./builder/setup.sh
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"

# ── 1. Task queue directories (gitignored) ──────────────────────────
echo "Creating builder/tasks/ directories..."
mkdir -p "$REPO_ROOT/builder/tasks"/{todo,in-progress,done,failed,summary,artifacts,archive}

# ── 2. Ink monitor dependencies (optional) ───────────────────────────
if command -v npm &>/dev/null && [[ -f "$REPO_ROOT/builder/monitor/ink/package.json" ]]; then
  echo "Installing ink monitor dependencies..."
  (cd "$REPO_ROOT/builder/monitor/ink" && npm install --silent 2>/dev/null) || echo "  SKIP npm install failed (optional)"
else
  echo "Skipping ink monitor (npm not found or no package.json)"
fi

echo ""
echo "Builder agent setup complete."
echo "  Queue tasks:  use Claude skill 'builder' or create files in builder/tasks/todo/"
echo "  Monitor:      ./builder/monitor/pipeline-monitor.sh"
echo "  Pipeline:     ./builder/pipeline.sh \"your task\""
