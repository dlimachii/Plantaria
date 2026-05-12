#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BACKEND_DIR="$ROOT_DIR/backend"
ANDROID_DIR="$ROOT_DIR/android"
DEFAULT_BASE_URL="http://127.0.0.1:8000"
BASE_URL="${PLANTARIA_PROFILE_BASE_URL:-$DEFAULT_BASE_URL}"
PROFILE_PORT="${PLANTARIA_PROFILE_PORT:-8020}"
RUNS="${PLANTARIA_PROFILE_RUNS:-5}"
SERVER_PID=""
SERVER_LOG="$ROOT_DIR/.plantaria-profile-server.log"
TMP_DIR="$(mktemp -d)"
LOGIN_TOKEN=""

cleanup() {
  if [[ -n "$SERVER_PID" ]] && kill -0 "$SERVER_PID" >/dev/null 2>&1; then
    kill "$SERVER_PID" >/dev/null 2>&1 || true
    wait "$SERVER_PID" >/dev/null 2>&1 || true
  fi
  rm -rf "$TMP_DIR"
  rm -f "$SERVER_LOG"
}

trap cleanup EXIT

section() {
  echo
  echo "==> $1"
}

require_command() {
  if ! command -v "$1" >/dev/null 2>&1; then
    echo "$1 no esta disponible en PATH" >&2
    exit 1
  fi
}

probe_api() {
  curl -fsS -H "Accept: application/json" "$BASE_URL/api/records?limit=1" >/dev/null 2>&1
}

start_temporary_server() {
  BASE_URL="http://127.0.0.1:$PROFILE_PORT"

  section "Iniciando servidor temporal en $BASE_URL"
  cd "$BACKEND_DIR"
  php \
    -d upload_max_filesize=20M \
    -d post_max_size=24M \
    -d memory_limit=512M \
    artisan serve --host=127.0.0.1 --port="$PROFILE_PORT" > "$SERVER_LOG" 2>&1 &
  SERVER_PID=$!

  for _ in {1..40}; do
    if probe_api; then
      return
    fi
    sleep 0.5
  done

  echo "No se pudo arrancar el servidor temporal." >&2
  echo "Ultimas lineas del log:" >&2
  tail -n 40 "$SERVER_LOG" >&2 || true
  exit 1
}

curl_request() {
  local method="$1"
  local url="$2"
  local body="$3"
  local token="$4"
  local output="$5"

  local args=(
    -sS
    -o "$output"
    -w "%{http_code} %{time_total} %{size_download}"
    -H "Accept: application/json"
    -X "$method"
  )

  if [[ -n "$token" ]]; then
    args+=(-H "Authorization: Bearer $token")
  fi

  if [[ "$method" == "POST" || "$method" == "PATCH" ]]; then
    args+=(-H "Content-Type: application/json" --data "$body")
  fi

  curl "${args[@]}" "$url"
}

measure_endpoint() {
  local label="$1"
  local method="$2"
  local path="$3"
  local body="${4:-}"
  local token="${5:-}"
  local metrics_file="$TMP_DIR/${label//[^a-zA-Z0-9]/_}.txt"
  local response_file="$TMP_DIR/response.json"

  : > "$metrics_file"

  for ((i = 1; i <= RUNS; i++)); do
    local metrics
    local status
    local time_total
    local size_download

    metrics="$(curl_request "$method" "$BASE_URL$path" "$body" "$token" "$response_file")"
    read -r status time_total size_download <<< "$metrics"

    if [[ "$status" -lt 200 || "$status" -ge 300 ]]; then
      echo "Fallo en '$label' con HTTP $status" >&2
      cat "$response_file" >&2 || true
      exit 1
    fi

    printf "%s %s\n" "$time_total" "$size_download" >> "$metrics_file"
  done

  awk -v label="$label" '
    {
      n += 1
      sum += $1
      bytes += $2
      if (n == 1 || $1 < min) min = $1
      if (n == 1 || $1 > max) max = $1
    }
    END {
      printf "%-36s avg %7.1f ms  min %7.1f ms  max %7.1f ms  resp %6.1f KB\n",
        label, (sum / n) * 1000, min * 1000, max * 1000, (bytes / n) / 1024
    }
  ' "$metrics_file"
}

login_demo_user() {
  local response_file="$TMP_DIR/login.json"
  local handle="${PLANTARIA_PROFILE_HANDLE:-plantaria_demo}"
  local password="${PLANTARIA_PROFILE_PASSWORD:-}"
  local payload
  local metrics
  local status
  local time_total
  local size_download
  local token

  if [[ -z "$password" ]]; then
    echo "PLANTARIA_PROFILE_PASSWORD no esta configurado; se salta login y endpoints autenticados." >&2
    return
  fi

  payload="{\"handle\":\"$handle\",\"password\":\"$password\",\"device_name\":\"profile-script\"}"
  metrics="$(curl_request "POST" "$BASE_URL/api/auth/login" "$payload" "" "$response_file")"
  read -r status time_total size_download <<< "$metrics"

  if [[ "$status" -lt 200 || "$status" -ge 300 ]]; then
    echo "Login demo no disponible para medir endpoints autenticados (HTTP $status)." >&2
    return
  fi

  token="$(sed -n 's/.*"token":"\([^"]*\)".*/\1/p' "$response_file")"
  if [[ -z "$token" ]]; then
    echo "Login demo respondio sin token; se saltan endpoints autenticados." >&2
    return
  fi

  printf "%-36s avg %7.1f ms  min %7.1f ms  max %7.1f ms  resp %6.1f KB\n" \
    "Login demo" \
    "$(awk -v t="$time_total" 'BEGIN { print t * 1000 }')" \
    "$(awk -v t="$time_total" 'BEGIN { print t * 1000 }')" \
    "$(awk -v t="$time_total" 'BEGIN { print t * 1000 }')" \
    "$(awk -v b="$size_download" 'BEGIN { print b / 1024 }')"

  LOGIN_TOKEN="$token"
}

first_record_public_id() {
  local response_file="$TMP_DIR/records.json"
  local metrics
  local status

  metrics="$(curl_request "GET" "$BASE_URL/api/records?limit=1" "" "" "$response_file")"
  read -r status _ <<< "$metrics"

  if [[ "$status" -lt 200 || "$status" -ge 300 ]]; then
    return
  fi

  sed -n 's/.*"public_id":"\([^"]*\)".*/\1/p' "$response_file" | head -n 1
}

report_apk_size() {
  local flavor="${PLANTARIA_ANDROID_FLAVOR:-prod}"
  local apk_path
  apk_path="$(find "$ANDROID_DIR/app/build/outputs/apk" -type f -path "*/${flavor}/debug/*.apk" -name "app-${flavor}-debug.apk" | head -n 1)"
  if [[ -z "$apk_path" ]]; then
    apk_path="$(find "$ANDROID_DIR/app/build/outputs/apk" -type f -path "*/${flavor}/debug/*.apk" | head -n 1)"
  fi

  section "Tamaño APK"
  if [[ ! -f "$apk_path" ]]; then
    echo "No existe el APK debug para flavor '$flavor'. Ejecuta primero: cd android && ./gradlew :app:assemble${flavor^}Debug"
    return
  fi

  awk -v bytes="$(wc -c < "$apk_path")" '
    BEGIN {
      mib = bytes / 1024 / 1024
      printf "APK: %.1f MiB\n", mib
      if (mib > 100) {
        print "Aviso: APK debug por encima de 100 MiB; revisar dependencias, recursos y build release."
      }
    }
  '
}

report_adb_snapshot() {
  local package_name="${PLANTARIA_PROFILE_PACKAGE:-com.plantaria.app}"

  section "Snapshot ADB"
  if ! command -v adb >/dev/null 2>&1; then
    echo "adb no esta disponible; se salta la parte de dispositivo."
    return
  fi

  if ! adb get-state >/dev/null 2>&1; then
    echo "No hay dispositivo ADB listo; se salta la parte de dispositivo."
    return
  fi

  echo "Dispositivo: $(adb shell getprop ro.product.model | tr -d '\r')"

  if ! adb shell pidof "$package_name" >/dev/null 2>&1; then
    echo "$package_name no esta en ejecucion. Abre la app para medir memoria/render."
    return
  fi

  echo "Memoria:"
  adb shell dumpsys meminfo "$package_name" | sed -n '/TOTAL PSS/Ip;/TOTAL:/Ip' | head -n 5 || true

  echo "Render:"
  adb shell dumpsys gfxinfo "$package_name" | sed -n '/Total frames rendered/Ip;/Janky frames/Ip;/50th percentile/Ip;/90th percentile/Ip;/95th percentile/Ip;/99th percentile/Ip' || true
}

main() {
  require_command curl
  require_command php
  require_command awk

  if ! [[ "$RUNS" =~ ^[0-9]+$ ]] || [[ "$RUNS" -lt 1 ]]; then
    echo "PLANTARIA_PROFILE_RUNS debe ser un entero mayor que 0." >&2
    exit 1
  fi

  section "Preparando API"
  if probe_api; then
    echo "Usando API existente en $BASE_URL"
  elif [[ -z "${PLANTARIA_PROFILE_BASE_URL:-}" ]]; then
    start_temporary_server
  else
    echo "No responde $BASE_URL. Arranca la API o cambia PLANTARIA_PROFILE_BASE_URL." >&2
    exit 1
  fi

  section "Perfilado API ($RUNS iteraciones)"
  measure_endpoint "Mapa registros" "GET" "/api/records?limit=20"
  measure_endpoint "Mapa radio PostGIS" "GET" "/api/records?latitude=41.3851&longitude=2.1734&radius_km=8&limit=20"
  measure_endpoint "Busqueda Lavanda" "GET" "/api/records?q=Lavanda&limit=20"

  public_id="$(first_record_public_id || true)"
  if [[ -n "${public_id:-}" ]]; then
    measure_endpoint "Ficha completa" "GET" "/api/records/$public_id"
  else
    echo "No hay registros para medir ficha completa."
  fi

  login_demo_user || true
  if [[ -n "$LOGIN_TOKEN" ]]; then
    measure_endpoint "Actividad usuario" "GET" "/api/me/activity" "" "$LOGIN_TOKEN"
  fi

  report_apk_size
  report_adb_snapshot

  section "Lectura rapida"
  echo "- Si Mapa registros o Mapa radio pasan de 500 ms en local, revisar indices, eager loading y payload de fotos/observaciones."
  echo "- Si Ficha completa pesa mucho, revisar numero de observaciones y campos enviados al movil."
  echo "- Si el APK debug crece mucho, comprobar dependencias y comparar con una build release."
}

main "$@"
