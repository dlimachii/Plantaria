#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ANDROID_DIR="$ROOT_DIR/android"

cd "$ANDROID_DIR"
./gradlew --no-parallel :app:assembleDemoADebug :app:assembleDemoBDebug :app:assembleDemoCDebug
echo "OK: APKs demoA/demoB/demoC generadas en android/app/build/outputs/apk/"
