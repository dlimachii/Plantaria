#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DOCS_DIR="$ROOT_DIR/docs"
PORT="${PLANTARIA_DOCS_PORT:-4173}"

if command -v python3 >/dev/null 2>&1; then
  echo "Sirviendo Docsify en http://127.0.0.1:$PORT"
  exec python3 -m http.server "$PORT" --directory "$DOCS_DIR"
fi

if command -v php >/dev/null 2>&1; then
  echo "Sirviendo Docsify en http://127.0.0.1:$PORT"
  cd "$DOCS_DIR"
  exec php -S "127.0.0.1:$PORT"
fi

echo "Necesitas python3 o php para servir la documentación." >&2
exit 1
