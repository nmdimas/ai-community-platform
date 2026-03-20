#!/usr/bin/env bash
# Ultraworks (Sisyphus) Pipeline Monitor
# Shows current state and allows launching OpenCode in tmux

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
PIPELINE_DIR="$PROJECT_ROOT/.opencode/pipeline"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'
ULTRAWORKS_MAX_RUNTIME="${ULTRAWORKS_MAX_RUNTIME:-7200}"
ULTRAWORKS_STALL_TIMEOUT="${ULTRAWORKS_STALL_TIMEOUT:-900}"
ULTRAWORKS_WATCHDOG_INTERVAL="${ULTRAWORKS_WATCHDOG_INTERVAL:-30}"

# Helper functions
print_header() {
    echo -e "${CYAN}╔══════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║       Ultraworks (Sisyphus) Pipeline Monitor                 ║${NC}"
    echo -e "${CYAN}╚══════════════════════════════════════════════════════════════╝${NC}"
}

print_status() {
    local label="$1"
    local value="$2"
    printf "${BLUE}%-20s${NC} %s\n" "$label:" "$value"
}

get_current_phase() {
    if [[ ! -f "$PIPELINE_DIR/handoff.md" ]]; then
        echo "idle"
        return
    fi
    
    local last_section=$(grep -E "^## " "$PIPELINE_DIR/handoff.md" | tail -1 | sed 's/^## //')
    if [[ -z "$last_section" ]]; then
        echo "idle"
    else
        echo "$last_section"
    fi
}

get_plan_info() {
    if [[ ! -f "$PIPELINE_DIR/plan.json" ]]; then
        echo "{}"
        return
    fi
    cat "$PIPELINE_DIR/plan.json"
}

get_latest_report() {
    local latest=$(ls -t "$PIPELINE_DIR/reports"/*.md 2>/dev/null | head -1)
    if [[ -n "$latest" ]]; then
        echo "$latest"
    fi
}

get_latest_summary() {
    local latest=$(ls -t "$PROJECT_ROOT/builder/tasks/summary"/*.md 2>/dev/null | head -1)
    if [[ -n "$latest" ]]; then
        echo "$latest"
    fi
}

list_pending_tasks() {
    # Check for pending tasks in builder/tasks/todo
    if [[ -d "$PROJECT_ROOT/builder/tasks/todo" ]]; then
        ls -1 "$PROJECT_ROOT/builder/tasks/todo"/*.md 2>/dev/null | head -10 || true
    fi
}

show_state() {
    print_header
    echo ""
    
    print_status "Project root" "$PROJECT_ROOT"
    
    # Current phase
    local phase=$(get_current_phase)
    print_status "Current phase" "$phase"
    
    # Plan info
    if [[ -f "$PIPELINE_DIR/plan.json" ]]; then
        local profile=$(jq -r '.profile // "unknown"' "$PIPELINE_DIR/plan.json" 2>/dev/null || echo "unknown")
        local agents=$(jq -r '.agents | join(", ") // "none"' "$PIPELINE_DIR/plan.json" 2>/dev/null || echo "none")
        print_status "Profile" "$profile"
        print_status "Agents" "$agents"
    fi
    
    # Latest report
    local latest_report=$(get_latest_report)
    if [[ -n "$latest_report" ]]; then
        local report_time=$(stat -c %y "$latest_report" 2>/dev/null | cut -d. -f1)
        print_status "Latest report" "$(basename $latest_report) ($report_time)"
    fi

    local latest_summary=$(get_latest_summary)
    if [[ -n "$latest_summary" ]]; then
        local summary_time=$(stat -c %y "$latest_summary" 2>/dev/null | cut -d. -f1)
        print_status "Latest summary" "$(basename $latest_summary) ($summary_time)"
    fi
    
    echo ""
    echo -e "${YELLOW}─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─${NC}"
    echo ""
    
    # Show handoff state
    if [[ -f "$PIPELINE_DIR/handoff.md" ]]; then
        echo -e "${GREEN}Handoff state:${NC}"
        echo -e "${BLUE}─────────────────${NC}"
        head -40 "$PIPELINE_DIR/handoff.md"
        echo ""
    fi
    
    # Show pending tasks
    local pending=$(list_pending_tasks)
    if [[ -n "$pending" ]]; then
        echo -e "${YELLOW}Pending tasks in builder/tasks/todo:${NC}"
        echo "$pending" | while read task; do
            local name=$(basename "$task" .md)
            local priority=$(grep -m1 "<!-- priority:" "$task" 2>/dev/null | sed 's/.*priority: *\([0-9]*\).*/\1/' || echo "1")
            echo "  [$priority] $name"
        done
        echo ""
    fi
    
    # Recent reports
    echo -e "${YELLOW}Recent reports:${NC}"
    ls -lt "$PIPELINE_DIR/reports"/*.md 2>/dev/null | head -5 | while read _ _ _ _ _ date time _ file; do
        echo "  $date $time $(basename $file)"
    done || echo "  (no reports)"
}

launch_opencode_tmux() {
    local session_name="ultraworks"
    local task_description="${1:-}"

    # Check if tmux is available
    if ! command -v tmux &> /dev/null; then
        echo -e "${RED}Error: tmux is not installed${NC}"
        echo "Install: sudo apt install tmux"
        return 1
    fi

    # Check if opencode is available
    if ! command -v opencode &> /dev/null; then
        echo -e "${RED}Error: opencode is not installed${NC}"
        return 1
    fi

    # Check if session exists
    if tmux has-session -t "$session_name" 2>/dev/null; then
        echo -e "${YELLOW}Session '$session_name' already exists${NC}"
        echo -e "Attach: ${CYAN}tmux attach -t $session_name${NC}"

        # Offer to send task
        if [[ -n "$task_description" ]]; then
            read -p "Send task to existing session? [y/N] " -n1 -r
            echo
            if [[ $REPLY =~ ^[Yy]$ ]]; then
                # Kill existing and relaunch with new task
                tmux kill-session -t "$session_name"
                _launch_opencode_session "$session_name" "$task_description"
            fi
        fi
        return 0
    fi

    _launch_opencode_session "$session_name" "$task_description"
}

_detect_model() {
    # Model routing rules for Sisyphus orchestrator:
    # Both GLM-5 and GPT-5.4 work after builder-agent Sisyphus exception fix.
    # GLM-5 first as primary (free), GPT-5.4 as strong fallback.
    # See: docs/guides/pipeline-models/ for full policy
    local models=(
        "opencode-go/glm-5"
        "openai/gpt-5.4"
        "minimax/MiniMax-M2.7"
        "opencode/big-pickle"
        "google/gemini-3.1-pro-preview"
        "opencode/minimax-m2.5-free"
        "openrouter/free"
        "openrouter/deepseek/deepseek-r1-0528:free"
    )
    local available
    available=$(opencode models 2>/dev/null)

    for model in "${models[@]}"; do
        if echo "$available" | grep -qF "$model"; then
            echo "$model"
            return 0
        fi
    done

    # Fallback to default
    echo ""
    return 1
}

_task_log_path() {
    local timestamp
    timestamp=$(date +%Y%m%d_%H%M%S)
    local task_text="${1:-unknown}"
    local slug
    slug=$(python3 - "$task_text" <<'PYEOF'
import re
import sys

text = sys.argv[1]
title = ""
for line in text.splitlines():
    stripped = line.strip()
    if stripped.startswith("# "):
        title = stripped[2:].strip()
        break
    if stripped and not stripped.startswith("<!--"):
        title = stripped
        break

if not title:
    title = "unknown"

slug = re.sub(r"[^a-z0-9]+", "-", title.lower()).strip("-")
print((slug or "unknown")[:60])
PYEOF
)
    local log_dir="$PIPELINE_DIR/logs"
    mkdir -p "$log_dir"
    echo "$log_dir/task-${timestamp}-${slug}.log"
}

_postprocess_summary_cmd() {
    local start_epoch="$1"
    printf '%q' "./builder/normalize-summary.py"
    printf ' --workflow ultraworks --since-epoch %q || true' "$start_epoch"
}

_timeout_prefix() {
    if command -v timeout &>/dev/null && [[ "$ULTRAWORKS_MAX_RUNTIME" =~ ^[0-9]+$ ]] && (( ULTRAWORKS_MAX_RUNTIME > 0 )); then
        printf 'timeout %q ' "$ULTRAWORKS_MAX_RUNTIME"
    fi
}

_watchdog_marker_path() {
    local log_file="$1"
    echo "${log_file}.watchdog"
}

_start_watchdog() {
    local pipeline_pid="$1"
    local log_file="$2"
    local marker_file
    marker_file=$(_watchdog_marker_path "$log_file")

    rm -f "$marker_file"

    if ! [[ "$ULTRAWORKS_STALL_TIMEOUT" =~ ^[0-9]+$ ]] || (( ULTRAWORKS_STALL_TIMEOUT <= 0 )); then
        echo ""
        return 0
    fi

    (
        local last_log_size=0
        local last_log_progress
        local last_handoff_progress
        last_log_progress=$(date +%s)
        last_handoff_progress=$(date +%s)
        local last_handoff_mtime=0

        while kill -0 "$pipeline_pid" 2>/dev/null; do
            sleep "$ULTRAWORKS_WATCHDOG_INTERVAL"

            local now
            now=$(date +%s)
            local log_size=0
            if [[ -f "$log_file" ]]; then
                log_size=$(wc -c < "$log_file" 2>/dev/null || echo 0)
            fi
            if (( log_size > last_log_size )); then
                last_log_size="$log_size"
                last_log_progress="$now"
            fi

            local handoff_mtime=0
            if [[ -f "$PIPELINE_DIR/handoff.md" ]]; then
                handoff_mtime=$(stat -c %Y "$PIPELINE_DIR/handoff.md" 2>/dev/null || echo 0)
            fi
            if (( handoff_mtime > last_handoff_mtime )); then
                last_handoff_mtime="$handoff_mtime"
                last_handoff_progress="$now"
            fi

            local log_idle=$(( now - last_log_progress ))
            local handoff_idle=$(( now - last_handoff_progress ))
            if (( log_idle >= ULTRAWORKS_STALL_TIMEOUT && handoff_idle >= ULTRAWORKS_STALL_TIMEOUT )); then
                printf 'stall:%ss\n' "$ULTRAWORKS_STALL_TIMEOUT" > "$marker_file"
                echo "Ultraworks watchdog: no log or handoff progress for ${ULTRAWORKS_STALL_TIMEOUT}s, terminating pipeline." | tee -a "$log_file"
                kill -TERM "$pipeline_pid" 2>/dev/null || true
                sleep 10
                kill -KILL "$pipeline_pid" 2>/dev/null || true
                exit 0
            fi
        done
    ) &

    echo "$!"
}

_stop_watchdog() {
    local watchdog_pid="${1:-}"
    [[ -z "$watchdog_pid" ]] && return 0
    kill "$watchdog_pid" 2>/dev/null || true
    wait "$watchdog_pid" 2>/dev/null || true
}

_run_headless_pipeline() {
    local task="$1"
    local model="$2"
    local log_file="$3"
    local start_epoch="$4"

    local -a run_cmd=(opencode run --command auto "$task")
    if [[ -n "$model" ]]; then
        run_cmd=(opencode run --model "$model" --command auto "$task")
    fi

    local pipeline_pid=""
    if command -v timeout &>/dev/null && [[ "$ULTRAWORKS_MAX_RUNTIME" =~ ^[0-9]+$ ]] && (( ULTRAWORKS_MAX_RUNTIME > 0 )); then
        timeout "$ULTRAWORKS_MAX_RUNTIME" "${run_cmd[@]}" > >(tee "$log_file") 2>&1 &
    else
        "${run_cmd[@]}" > >(tee "$log_file") 2>&1 &
    fi
    pipeline_pid=$!

    local watchdog_pid=""
    watchdog_pid=$(_start_watchdog "$pipeline_pid" "$log_file")

    local pipeline_status=0
    set +e
    wait "$pipeline_pid"
    pipeline_status=$?
    set -e

    _stop_watchdog "$watchdog_pid"

    local marker_file
    marker_file=$(_watchdog_marker_path "$log_file")
    if [[ -f "$marker_file" ]]; then
        echo "Ultraworks pipeline stopped by watchdog ($(cat "$marker_file"))." | tee -a "$log_file"
        rm -f "$marker_file"
    elif [[ "$pipeline_status" -eq 124 || "$pipeline_status" -eq 137 ]]; then
        echo "Ultraworks wrapper timeout after ${ULTRAWORKS_MAX_RUNTIME}s" | tee -a "$log_file"
    fi

    ./builder/postmortem-summary.sh 2>&1 | tee -a "$log_file" || true
    ./builder/normalize-summary.py --workflow ultraworks --since-epoch "$start_epoch" 2>&1 | tee -a "$log_file" || true

    return "$pipeline_status"
}

_launch_opencode_session() {
    local session_name="$1"
    local task_description="${2:-}"
    local start_epoch
    start_epoch=$(date +%s)

    # Detect best available model for Sisyphus orchestration
    local model
    model=$(_detect_model)
    local model_flag=""
    if [[ -n "$model" ]]; then
        model_flag="--model $model"
        echo -e "${BLUE}Model:${NC} $model"
    fi

    echo -e "${GREEN}Starting Sisyphus pipeline in tmux session '$session_name'${NC}"

    if [[ -n "$task_description" ]]; then
        # Generate log file path
        local log_file
        log_file=$(_task_log_path "$task_description")
        echo -e "${BLUE}Log:${NC} $log_file"

        local runner
        printf -v runner '%q %q %q' "$SCRIPT_DIR/ultraworks-monitor.sh" headless "$task_description"
        tmux new-session -d -s "$session_name" -c "$PROJECT_ROOT" \
            "bash -lc '$runner; status=\$?; echo; echo \"Pipeline finished with status \$status. Press Enter to close.\"; read; exit \$status'"
        echo -e "${CYAN}Pipeline running. Attach: tmux attach -t $session_name${NC}"
    else
        # Interactive mode: just start opencode TUI (no logging)
        tmux new-session -d -s "$session_name" -c "$PROJECT_ROOT" \
            "opencode $model_flag"
        echo -e "${CYAN}OpenCode TUI started. Attach: tmux attach -t $session_name${NC}"
    fi
}

interactive_menu() {
    while true; do
        echo ""
        echo -e "${CYAN}Actions:${NC}"
        echo "  1) Show current state"
        echo "  2) Launch OpenCode (tmux)"
        echo "  3) View latest report"
        echo "  4) View latest summary"
        echo "  5) View handoff"
        echo "  6) Tail logs"
        echo "  q) Quit"
        echo ""
        read -p "Choose [1-6/q]: " -n1 -r
        echo ""
        
        case $REPLY in
            1) show_state ;;
            2) launch_opencode_tmux ;;
            3) 
                local report=$(get_latest_report)
                if [[ -n "$report" ]]; then
                    less "$report"
                else
                    echo -e "${YELLOW}No reports available${NC}"
                fi
                ;;
            4)
                local summary=$(get_latest_summary)
                if [[ -n "$summary" ]]; then
                    less "$summary"
                else
                    echo -e "${YELLOW}No summary available${NC}"
                fi
                ;;
            5)
                if [[ -f "$PIPELINE_DIR/handoff.md" ]]; then
                    less "$PIPELINE_DIR/handoff.md"
                else
                    echo -e "${YELLOW}No handoff available${NC}"
                fi
                ;;
            6)
                local log_dir="$PIPELINE_DIR/logs"
                if [[ -d "$log_dir" ]]; then
                    ls -lt "$log_dir"/*.log 2>/dev/null | head -1 | awk '{print $NF}' | xargs tail -f || echo "No logs"
                else
                    echo -e "${YELLOW}No logs available${NC}"
                fi
                ;;
            q|Q) exit 0 ;;
            *) echo -e "${RED}Invalid option${NC}" ;;
        esac
    done
}

# Main
main() {
    local action="${1:-show}"
    local task="${2:-}"
    
    case "$action" in
        show|state)
            show_state
            ;;
        launch|run)
            launch_opencode_tmux "$task"
            ;;
        headless)
            # Direct execution without tmux — outputs to stdout + log file
            # Useful when called from Claude Code or CI
            if [[ -z "$task" ]]; then
                echo -e "${RED}Error: task description required${NC}"
                echo "Usage: $0 headless \"task description\""
                exit 1
            fi
            local model
            model=$(_detect_model)
            if [[ -n "$model" ]]; then
                echo -e "${BLUE}Model:${NC} $model"
            fi
            local log_file
            log_file=$(_task_log_path "$task")
            local start_epoch
            start_epoch=$(date +%s)
            echo -e "${GREEN}Running Sisyphus pipeline (headless)...${NC}"
            echo -e "${BLUE}Task:${NC} $task"
            echo -e "${BLUE}Log:${NC} $log_file"
            if command -v timeout &>/dev/null && [[ "$ULTRAWORKS_MAX_RUNTIME" =~ ^[0-9]+$ ]] && (( ULTRAWORKS_MAX_RUNTIME > 0 )); then
                echo -e "${BLUE}Max runtime:${NC} ${ULTRAWORKS_MAX_RUNTIME}s"
            fi
            if [[ "$ULTRAWORKS_STALL_TIMEOUT" =~ ^[0-9]+$ ]] && (( ULTRAWORKS_STALL_TIMEOUT > 0 )); then
                echo -e "${BLUE}Stall watchdog:${NC} ${ULTRAWORKS_STALL_TIMEOUT}s"
            fi
            echo ""
            _run_headless_pipeline "$task" "$model" "$log_file" "$start_epoch"
            exit $?
            ;;
        logs)
            # Show recent task logs
            local log_dir="$PIPELINE_DIR/logs"
            if [[ -n "$task" ]]; then
                # View specific log
                if [[ -f "$task" ]]; then
                    less "$task"
                elif [[ -f "$log_dir/$task" ]]; then
                    less "$log_dir/$task"
                else
                    # Search by pattern
                    local found
                    found=$(ls -t "$log_dir"/task-*"$task"* 2>/dev/null | head -1)
                    if [[ -n "$found" ]]; then
                        less "$found"
                    else
                        echo -e "${RED}No log matching '$task'${NC}"
                        echo "Available logs:"
                        ls -lt "$log_dir"/task-*.log 2>/dev/null | head -10 | awk '{print "  " $NF}'
                    fi
                fi
            else
                # List recent logs
                echo -e "${CYAN}Recent task logs:${NC}"
                ls -lt "$log_dir"/task-*.log 2>/dev/null | head -15 | while read -r line; do
                    local f=$(echo "$line" | awk '{print $NF}')
                    local sz=$(echo "$line" | awk '{print $5}')
                    local dt=$(echo "$line" | awk '{print $6, $7, $8}')
                    local name=$(basename "$f")
                    # Check if log ends with "Pipeline finished" (success) or has error
                    local status="?"
                    if tail -5 "$f" 2>/dev/null | grep -q "Pipeline finished"; then
                        status="done"
                    elif tail -20 "$f" 2>/dev/null | grep -qi "error\|failed\|exception"; then
                        status="FAIL"
                    elif [[ $sz -lt 100 ]]; then
                        status="empty"
                    fi
                    printf "  %-8s %6s  %s  %s\n" "[$status]" "$(numfmt --to=iec $sz 2>/dev/null || echo ${sz}B)" "$dt" "$name"
                done || echo "  (no task logs)"
                echo ""
                echo -e "View a log: ${CYAN}$0 logs <filename-or-pattern>${NC}"
            fi
            ;;
        attach)
            tmux attach -t ultraworks 2>/dev/null || echo -e "${YELLOW}No ultraworks session. Run: $0 launch \"task\"${NC}"
            ;;
        menu|interactive)
            interactive_menu
            ;;
        *)
            show_state
            echo ""
            echo -e "${CYAN}Usage: $0 [show|launch|headless|logs|attach|menu] [task description]${NC}"
            echo ""
            echo "Commands:"
            echo "  show      Show current pipeline state (default)"
            echo "  launch    Start Sisyphus pipeline in tmux session (logs to file)"
            echo "  headless  Run pipeline directly (stdout + log file)"
            echo "  logs      List recent task logs, or view one: logs <pattern>"
            echo "  attach    Attach to existing tmux session"
            echo "  menu      Interactive menu"
            echo ""
            echo "Examples:"
            echo "  $0 launch \"Implement user authentication\""
            echo "  $0 headless \"Add metrics dashboard\""
            echo "  $0 logs                    # list recent logs"
            echo "  $0 logs e2e                # view latest log matching 'e2e'"
            echo "  $0 attach"
            ;;
    esac
}

main "$@"
