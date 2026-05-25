#!/usr/bin/env bash
set -e
trap 'kill 0' SIGINT

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "Iniciando servicios de desarrollo..."

# API Laravel
( cd "$ROOT/backend" && php artisan serve ) &

# Worker de colas
( cd "$ROOT/backend" && php artisan queue:work ) &

# Scheduler (mensajes programados)
( cd "$ROOT/backend" && php artisan schedule:work ) &

# Frontend Next.js (solo si existe la carpeta)
if [ -d "$ROOT/frontend" ]; then
  ( cd "$ROOT/frontend" && npm run dev ) &
fi

echo "Servicios arriba. Ctrl+C para detener todos."
echo "  API:      http://localhost:8000"
echo "  Frontend: http://localhost:3000 (si está)"
echo "  Recuerda: ngrok http 8000  (en otra terminal, para WhatsApp)"

wait