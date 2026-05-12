#!/usr/bin/env bash
set -Eeuo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BUILD_ANDROID=0
RUN_MIGRATIONS=0
SKIP_BACKEND=0
SKIP_ANDROID=0
SKIP_ANALYTICS=0

usage() {
  cat <<'USAGE'
Plantaria installer

Usage:
  ./scripts/install_project.sh [options]

Options:
  --build-android   Build the prod debug APK after resolving Gradle dependencies.
  --migrate         Run Laravel migrations and seeders. Requires DB and demo passwords in backend/.env.
  --skip-backend    Skip Composer, npm and Laravel .env setup.
  --skip-android    Skip Gradle/Android setup.
  --skip-analytics  Skip Python virtualenv setup.
  -h, --help        Show this help.

This script installs project dependencies. It does not install Android Studio, the
Android SDK, Docker, PHP, Composer, Node.js or Python itself.
USAGE
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --build-android) BUILD_ANDROID=1 ;;
    --migrate) RUN_MIGRATIONS=1 ;;
    --skip-backend) SKIP_BACKEND=1 ;;
    --skip-android) SKIP_ANDROID=1 ;;
    --skip-analytics) SKIP_ANALYTICS=1 ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      echo "Unknown option: $1" >&2
      usage
      exit 2
      ;;
  esac
  shift
done

section() {
  printf '\n==> %s\n' "$1"
}

require_command() {
  if ! command -v "$1" >/dev/null 2>&1; then
    echo "Missing required command: $1" >&2
    return 1
  fi
}

copy_env_if_missing() {
  if [[ ! -f "$ROOT_DIR/backend/.env" ]]; then
    cp "$ROOT_DIR/backend/.env.example" "$ROOT_DIR/backend/.env"
    echo "Created backend/.env from backend/.env.example."
  else
    echo "backend/.env already exists; leaving it untouched."
  fi
}

install_backend() {
  section "Backend dependencies"
  require_command php
  require_command composer
  require_command npm

  cd "$ROOT_DIR/backend"
  composer install --no-interaction --prefer-dist
  npm ci
  copy_env_if_missing

  if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    php artisan key:generate --ansi
  fi

  php artisan storage:link || true

  if [[ "$RUN_MIGRATIONS" -eq 1 ]]; then
    php artisan migrate --seed
  else
    echo "Skipping migrations. Use --migrate when PostgreSQL is ready and demo passwords are set."
  fi
}

install_android() {
  section "Android dependencies"
  require_command java

  cd "$ROOT_DIR/android"
  chmod +x ./gradlew
  ./gradlew --version

  if [[ "$BUILD_ANDROID" -eq 1 ]]; then
    ./gradlew :app:assembleProdDebug
  else
    echo "Skipping APK build. Use --build-android to compile app-prod-debug.apk."
  fi
}

install_analytics() {
  section "Analytics dependencies"
  require_command python3

  cd "$ROOT_DIR"
  python3 -m venv analytics/.venv
  # shellcheck disable=SC1091
  source analytics/.venv/bin/activate
  python -m pip install --upgrade pip
  pip install -r analytics/requirements.txt
}

section "Plantaria final code setup"
echo "Project root: $ROOT_DIR"

if [[ "$SKIP_BACKEND" -eq 0 ]]; then
  install_backend
fi

if [[ "$SKIP_ANDROID" -eq 0 ]]; then
  install_android
fi

if [[ "$SKIP_ANALYTICS" -eq 0 ]]; then
  install_analytics
fi

section "Done"
echo "Android prod API: https://api.dlimachii.com/api/"
echo "Laravel env file: backend/.env"
