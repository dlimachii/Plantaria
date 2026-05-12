#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ANDROID_DIR="$ROOT_DIR/android"
FLAVOR="${PLANTARIA_ANDROID_FLAVOR:-prod}"
VARIANT_TASK="assemble${FLAVOR^}Debug"

if ! command -v adb >/dev/null 2>&1; then
  echo "adb no esta disponible en PATH" >&2
  exit 1
fi

cd "$ANDROID_DIR"
./gradlew ":app:${VARIANT_TASK}"

APK_PATH="$(find "$ANDROID_DIR/app/build/outputs/apk" -type f -path "*/${FLAVOR}/debug/*.apk" -name "app-${FLAVOR}-debug.apk" | head -n 1)"
if [[ -z "$APK_PATH" ]]; then
  APK_PATH="$(find "$ANDROID_DIR/app/build/outputs/apk" -type f -path "*/${FLAVOR}/debug/*.apk" | head -n 1)"
fi
if [[ -z "$APK_PATH" ]] || [[ ! -f "$APK_PATH" ]]; then
  echo "No se encontro el APK para flavor '$FLAVOR'." >&2
  echo "Busca en: $ANDROID_DIR/app/build/outputs/apk/$FLAVOR/debug" >&2
  exit 1
fi

if ! adb get-state >/dev/null 2>&1; then
  echo "No hay ningun dispositivo adb listo. Ejecuta 'adb devices' y acepta la depuracion USB en el movil." >&2
  exit 1
fi

adb reverse tcp:8000 tcp:8000 >/dev/null 2>&1 || true
adb install -r "$APK_PATH"
echo "APK instalado desde: $APK_PATH"
echo "Si es un movil fisico por USB, queda preparado adb reverse tcp:8000 tcp:8000."
echo "La app solo usara el backend local si guardas http://127.0.0.1:8000/api/ como servidor."
