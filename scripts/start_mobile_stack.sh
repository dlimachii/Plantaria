#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BACKEND_DIR="$ROOT_DIR/backend"

if ! command -v docker >/dev/null 2>&1; then
  echo "docker no esta disponible en PATH" >&2
  exit 1
fi

if ! command -v php >/dev/null 2>&1; then
  echo "php no esta disponible en PATH" >&2
  exit 1
fi

echo "==> Levantando PostgreSQL/PostGIS"
docker compose -f "$ROOT_DIR/compose.yaml" up -d postgis

echo "==> Preparando backend Laravel"
cd "$BACKEND_DIR"
php artisan migrate --seed
php artisan storage:link || true

echo

echo "Backend listo. URLs recomendadas para la app:"
echo "- Emulador Android: http://10.0.2.2:8000/api/"
echo "- Movil por USB con adb reverse: http://127.0.0.1:8000/api/"
echo "- Movil por Wi-Fi: http://IP_DE_TU_PC:8000/api/"
echo

echo "==> Iniciando Laravel en http://0.0.0.0:8000"
exec php \
  -d upload_max_filesize=20M \
  -d post_max_size=24M \
  -d memory_limit=512M \
  artisan serve --host=0.0.0.0 --port=8000
