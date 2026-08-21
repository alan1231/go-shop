#!/usr/bin/env bash
set -u

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOG="$ROOT/.dev.log"
PIDS="$ROOT/.dev.pids"

info() { printf '%s\n' "$*"; }

port_free() { ! lsof -iTCP:"$1" -sTCP:LISTEN >/dev/null 2>&1; }

deps_check() {
  [[ -d "$ROOT/backend/vendor" ]] || info "  [php] vendor 不存在，先執行：cd backend && composer install"
  [[ -d "$ROOT/frontend/node_modules" ]] || info "  [frontend] node_modules 不存在，先執行：cd frontend && npm install"
  [[ -d "$ROOT/frontend-admin/node_modules" ]] || info "  [frontend-admin] node_modules 不存在，先執行：cd frontend-admin && npm install"
}

start_svc() {
  local name="$1" port="$2"
  shift 2
  if ! port_free "$port"; then
    info "  [$name] 已在執行 (port $port)"
    return
  fi
  (
    cd "$ROOT/$name" || exit 1
    nohup "$@" >>"$LOG" 2>&1 &
    echo "$!" >>"$PIDS"
  )
  info "  [$name] 已啟動 (port $port)"
}

stop_all() {
  if [[ -f "$PIDS" ]]; then
    while IFS= read -r pid; do
      [[ -n "$pid" ]] && kill "$pid" 2>/dev/null
    done <"$PIDS"
    rm -f "$PIDS"
  fi
  for p in 8080 8081 5173 5174; do
    lsof -tiTCP:"$p" -sTCP:LISTEN 2>/dev/null | xargs kill 2>/dev/null
  done
  info "已停止 backend / frontend / frontend-admin"
}

status() {
  for p in 8080 8081 5173 5174; do
    if port_free "$p"; then
      info "  port $p: 未執行"
    else
      info "  port $p: 執行中 (PID $(lsof -tiTCP:$p -sTCP:LISTEN | tr '\n' ' '))"
    fi
  done
}

case "${1:-start}" in
  start)
    info "啟動中..."
    deps_check
    start_svc backend 8080 php -S localhost:8080 index.php
    if port_free 8081; then
      ( cd "$ROOT/backend" && nohup php -S localhost:8081 index.php >>"$LOG" 2>&1 & echo "$!" >>"$PIDS" )
      info "  [backend-sse] 已啟動 (port 8081, KDS 推播專用)"
    fi
    start_svc frontend 5173 npm run dev
    start_svc frontend-admin 5174 npm run dev
    info ""
    info "前台:   http://localhost:5173/"
    info "後台:   http://localhost:5174/admin/"
    info "API:    http://localhost:8080/api/"
    info "SSE:    http://localhost:8081 (KDS 推播專用)"
    info "紀錄:   $LOG"
    ;;
  stop)
    stop_all
    ;;
  status)
    status
    ;;
  logs)
    tail -f "$LOG"
    ;;
  *)
    info "用法: $0 [start|stop|status|logs]"
    ;;
esac