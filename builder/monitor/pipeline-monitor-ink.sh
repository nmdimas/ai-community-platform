#!/usr/bin/env bash
# Pipeline monitor — Ink (React for CLI) version
# Usage: ./builder/monitor/pipeline-monitor-ink.sh [tasks-dir]
SCRIPT_DIR="$(cd "$(dirname "$(readlink -f "$0" 2>/dev/null || realpath "$0" 2>/dev/null || echo "$0")")" && pwd)"
cd "$SCRIPT_DIR/ink" && exec node index.js "$@"
