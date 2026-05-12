#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BACKEND_DIR="$ROOT_DIR/backend"
ANDROID_DIR="$ROOT_DIR/android"
VALIDATION_PORT="${PLANTARIA_VALIDATE_PORT:-8010}"
SERVER_PID=""
SERVER_LOG="$ROOT_DIR/.plantaria-validate-server.log"

cleanup() {
  if [[ -n "$SERVER_PID" ]] && kill -0 "$SERVER_PID" >/dev/null 2>&1; then
    kill "$SERVER_PID" >/dev/null 2>&1 || true
    wait "$SERVER_PID" >/dev/null 2>&1 || true
  fi
  rm -f "$SERVER_LOG"
}

trap cleanup EXIT

section() {
  echo
  echo "==> $1"
}

section "Comprobando sintaxis de scripts"
bash -n "$ROOT_DIR/scripts/start_mobile_stack.sh"
bash -n "$ROOT_DIR/scripts/install_debug_apk.sh"
bash -n "$ROOT_DIR/scripts/validate_project.sh"
bash -n "$ROOT_DIR/scripts/profile_app_performance.sh"
bash -n "$ROOT_DIR/scripts/build_demo_apks.sh"
bash -n "$ROOT_DIR/scripts/start_demo_tunnel.sh"

if command -v pwsh >/dev/null 2>&1; then
  pwsh -NoProfile -Command "\$ErrorActionPreference='Stop'; \$null = [System.Management.Automation.Language.Parser]::ParseFile('$ROOT_DIR/scripts/install_debug_apk.ps1', [ref]\$null, [ref]\$null)"
  pwsh -NoProfile -Command "\$ErrorActionPreference='Stop'; \$null = [System.Management.Automation.Language.Parser]::ParseFile('$ROOT_DIR/scripts/install_demo_apks.ps1', [ref]\$null, [ref]\$null)"
fi

section "Ejecutando tests backend"
cd "$BACKEND_DIR"
php artisan test

if [[ "${SKIP_ANDROID_BUILD:-0}" != "1" ]]; then
  section "Compilando APK debug Android"
  cd "$ANDROID_DIR"
  ./gradlew :app:assembleProdDebug
else
  section "Saltando build Android por SKIP_ANDROID_BUILD=1"
fi

if [[ "${SKIP_POSTGIS_SMOKE:-0}" == "1" ]]; then
  section "Saltando smoke PostGIS por SKIP_POSTGIS_SMOKE=1"
  exit 0
fi

if ! command -v docker >/dev/null 2>&1; then
  section "Saltando smoke PostGIS: docker no esta disponible"
  exit 0
fi

if ! command -v curl >/dev/null 2>&1; then
  section "Saltando smoke PostGIS: curl no esta disponible"
  exit 0
fi

cd "$ROOT_DIR"
if ! docker compose ps --services --filter status=running | grep -qx "postgis"; then
  section "Saltando smoke PostGIS: el servicio postgis no esta en ejecucion"
  echo "Ejecuta 'docker compose up -d postgis' para incluir esta prueba."
  exit 0
fi

section "Validando PostgreSQL/PostGIS real"
cd "$BACKEND_DIR"
php artisan migrate --seed --no-interaction

php artisan serve --host=127.0.0.1 --port="$VALIDATION_PORT" > "$SERVER_LOG" 2>&1 &
SERVER_PID=$!

for _ in {1..30}; do
  if curl -fsS "http://127.0.0.1:$VALIDATION_PORT/api/records?limit=1" >/dev/null 2>&1; then
    break
  fi
  sleep 0.5
done

nearby_response="$(curl -fsS -H "Accept: application/json" "http://127.0.0.1:$VALIDATION_PORT/api/records?latitude=41.3851&longitude=2.1734&radius_km=8&limit=10")"
if ! grep -q '"distance_km"' <<< "$nearby_response"; then
  echo "El endpoint por radio no devolvio distance_km." >&2
  exit 1
fi

validation_status="$(curl -sS -o /tmp/plantaria-validation-error.json -w "%{http_code}" -H "Accept: application/json" "http://127.0.0.1:$VALIDATION_PORT/api/records?latitude=41.3851")"
if [[ "$validation_status" != "422" ]]; then
  echo "La validacion de filtros esperaba HTTP 422 y recibio $validation_status." >&2
  cat /tmp/plantaria-validation-error.json >&2
  exit 1
fi

section "Validacion completa terminada"
echo "Backend, Android, scripts y smoke PostGIS correctos."
