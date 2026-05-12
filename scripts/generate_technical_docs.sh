#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ANDROID_DIR="$ROOT_DIR/android"

cd "$ANDROID_DIR"
./gradlew :app:dokkaGeneratePublicationHtml

echo "OK: documentación Android generada en:"
echo "  $ANDROID_DIR/app/build/documentation/html/index.html"
