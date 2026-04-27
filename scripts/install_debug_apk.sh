#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ANDROID_DIR="$ROOT_DIR/android"
APK_PATH="$ANDROID_DIR/app/build/outputs/apk/debug/app-debug.apk"

if ! command -v adb >/dev/null 2>&1; then
  echo "adb no esta disponible en PATH" >&2
  exit 1
fi

cd "$ANDROID_DIR"
./gradlew :app:assembleDebug

if ! adb get-state >/dev/null 2>&1; then
  echo "No hay ningun dispositivo adb listo. Ejecuta 'adb devices' y acepta la depuracion USB en el movil." >&2
  exit 1
fi

adb install -r "$APK_PATH"
echo "APK instalado desde: $APK_PATH"
