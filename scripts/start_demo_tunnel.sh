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

if ! docker info >/dev/null 2>&1; then
  if command -v sudo >/dev/null 2>&1 && [[ -t 0 ]] && [[ "${PLANTARIA_SKIP_SUDO:-}" != "1" ]]; then
    echo "Docker requiere permisos elevados. Reintentando con sudo..."
    exec sudo PLANTARIA_SKIP_SUDO=1 bash "$0"
  fi

  echo "No se puede conectar al daemon de Docker (permiso denegado en /var/run/docker.sock)." >&2
  echo "Opciones:" >&2
  echo "- Ejecutar este script con sudo: sudo bash ./scripts/start_demo_tunnel.sh" >&2
  echo "- O añadir tu usuario al grupo docker y reentrar en sesion:" >&2
  echo "    sudo usermod -aG docker \"$USER\"" >&2
  echo "    newgrp docker" >&2
  exit 1
fi

echo "==> Levantando PostgreSQL/PostGIS"
docker compose -f "$ROOT_DIR/compose.yaml" up -d postgis

echo "==> Preparando backend Laravel"
cd "$BACKEND_DIR"
php artisan migrate --seed --no-interaction
php artisan storage:link >/dev/null 2>&1 || true

echo "==> Iniciando Laravel en http://0.0.0.0:8000"
php \
  -d upload_max_filesize=20M \
  -d post_max_size=24M \
  -d memory_limit=512M \
  artisan serve --host=0.0.0.0 --port=8000 >/tmp/plantaria-laravel-demo.log 2>&1 &
LARAVEL_PID="$!"

cleanup() {
  if kill -0 "$LARAVEL_PID" >/dev/null 2>&1; then
    kill "$LARAVEL_PID" >/dev/null 2>&1 || true
  fi
}
trap cleanup EXIT

echo "==> Esperando a que el backend responda en localhost:8000"
for _ in {1..30}; do
  if command -v curl >/dev/null 2>&1; then
    if curl -fsS "http://127.0.0.1:8000/" >/dev/null 2>&1; then
      break
    fi
  else
    # Fallback sin curl: asume que el proceso arranco tras unos segundos.
    sleep 2
    break
  fi
  sleep 1
done

echo
echo "==> Creando tunel publico (Quick Tunnel)"
echo "Cuando aparezca una URL https://....trycloudflare.com, copiala en Android:"
echo "Login -> Servidor (API) -> Guardar servidor"
echo

if command -v cloudflared >/dev/null 2>&1; then
  exec cloudflared tunnel --url http://localhost:8000
fi

echo "cloudflared no esta instalado. Usando Docker para ejecutar cloudflared."
exec docker run --rm --network host cloudflare/cloudflared:latest tunnel --no-autoupdate --url http://localhost:8000
