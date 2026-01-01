#!/usr/bin/env bash
set -euo pipefail

# Starts Laravel Reverb websockets and a queue worker side by side.
# Uses your existing .env values (APP_URL, REVERB_* and QUEUE_CONNECTION).
# Suitable for local/dev use; in production run via supervisor/systemd.

APP_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$APP_ROOT"

if [ ! -f ".env" ]; then
  echo "Missing .env in $APP_ROOT" >&2
  exit 1
fi

cpu_count() {
  if command -v getconf >/dev/null 2>&1; then
    getconf _NPROCESSORS_ONLN 2>/dev/null || true
  fi
  if [ -z "${CPU_CORES:-}" ] && command -v sysctl >/dev/null 2>&1; then
    CPU_CORES=$(sysctl -n hw.ncpu 2>/dev/null || true)
  fi
  echo "${CPU_CORES:-1}"
}

load_avg_1m() {
  if [ -f /proc/loadavg ]; then
    awk '{print $1}' /proc/loadavg
  elif command -v sysctl >/dev/null 2>&1; then
    # macOS: "{ 1.63 1.71 1.55 }"
    sysctl -n vm.loadavg 2>/dev/null | awk '{print $2}'
  else
    echo "0"
  fi
}

auto_workers() {
  local cores load available
  cores=$(cpu_count)
  load=$(load_avg_1m)
  # Simple heuristic: cores minus rounded load, at least 1.
  available=$(awk -v c="$cores" -v l="$load" 'BEGIN {w=c-int(l+0.5); if (w<1) w=1; if (w>c) w=c; print w}')
  echo "$available"
}

start_reverb() {
  php artisan reverb:start
}

start_queue() {
  # Adjust --queue ordering to your needs
  php artisan queue:work --queue=high,default,low --tries=3
}

main() {
  echo "Starting Reverb..."
  start_reverb &
  REVERB_PID=$!

  # priority: CLI arg > QUEUE_WORKERS env > auto
  if [ $# -gt 0 ]; then
    WORKERS="$1"
  elif [ -n "${QUEUE_WORKERS:-}" ]; then
    WORKERS="${QUEUE_WORKERS}"
  else
    WORKERS=$(auto_workers)
  fi

  echo "CPU cores: $(cpu_count), 1m load: $(load_avg_1m), queue workers: $WORKERS"
  echo "Starting $WORKERS queue worker(s)..."
  QUEUE_PIDS=()
  for _ in $(seq 1 "$WORKERS"); do
    start_queue &
    QUEUE_PIDS+=($!)
  done

  trap 'echo "Stopping..."; kill $REVERB_PID "${QUEUE_PIDS[@]}" 2>/dev/null || true' INT TERM
  wait $REVERB_PID "${QUEUE_PIDS[@]}"
}

main "$@"
