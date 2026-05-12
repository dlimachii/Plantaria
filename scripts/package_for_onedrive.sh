#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TIMESTAMP="$(date '+%Y%m%d-%H%M%S')"
PACKAGE_NAME="plantaria-backup-$TIMESTAMP"

DEFAULT_DEST=""
DEFAULT_DEST="$ROOT_DIR/backups"

DEST_DIR="${1:-${PLANTARIA_BACKUP_DIR:-$DEFAULT_DEST}}"
PACKAGE_DIR="$DEST_DIR/$PACKAGE_NAME"
SOURCE_ARCHIVE="$PACKAGE_DIR/plantaria-source-$TIMESTAMP.tar.gz"
GIT_BUNDLE="$PACKAGE_DIR/plantaria-git-$TIMESTAMP.bundle"
APK_SOURCE="$ROOT_DIR/android/app/build/outputs/apk/debug/app-debug.apk"
APK_TARGET="$PACKAGE_DIR/app-debug-$TIMESTAMP.apk"
DB_DUMP="$PACKAGE_DIR/plantaria-db-$TIMESTAMP.sql"
MANIFEST="$PACKAGE_DIR/MANIFEST.txt"

mkdir -p "$PACKAGE_DIR"

echo "==> Creando paquete en: $PACKAGE_DIR"

cat > "$MANIFEST" <<EOF
Plantaria backup
Fecha: $(date '+%Y-%m-%d %H:%M %Z')
Origen: $ROOT_DIR

Contenido:
- plantaria-source-$TIMESTAMP.tar.gz: codigo fuente y documentacion, sin dependencias, builds ni secretos locales.
- plantaria-git-$TIMESTAMP.bundle: historial Git comprometido hasta este momento.
- app-debug-$TIMESTAMP.apk: APK debug si existia al empaquetar.
- plantaria-db-$TIMESTAMP.sql: dump PostgreSQL opcional si se ejecuta con INCLUDE_DB_DUMP=1.
- SHA256SUMS: hashes de verificacion.

Exclusiones principales:
- .git/
- .codex
- .env y .env.*
- vendor/
- node_modules/
- builds Android/Gradle
- caches temporales
- storage publico/logs generados

Restauracion orientativa:
1. Descomprimir plantaria-source-$TIMESTAMP.tar.gz.
2. En backend: composer install, cp .env.example .env, php artisan key:generate.
3. Levantar PostGIS con docker compose up -d postgis.
4. Ejecutar php artisan migrate --seed.
5. En android: ./gradlew :app:assembleDebug.
6. Opcional: clonar historial con git clone plantaria-git-$TIMESTAMP.bundle PlantariaGit.
EOF

echo "==> Empaquetando fuente"
tar -C "$ROOT_DIR" \
  --exclude='./.git' \
  --exclude='./.codex' \
  --exclude='./.env' \
  --exclude='./.env.*' \
  --exclude='./backups' \
  --exclude='./backend/.env' \
  --exclude='./backend/.env.*' \
  --exclude='./backend/vendor' \
  --exclude='./backend/node_modules' \
  --exclude='./backend/.phpunit.cache' \
  --exclude='./backend/.phpunit.result.cache' \
  --exclude='./backend/storage/logs/*.log' \
  --exclude='./backend/storage/app/public' \
  --exclude='./backend/public/build' \
  --exclude='./backend/public/hot' \
  --exclude='./backend/public/storage' \
  --exclude='./android/.gradle' \
  --exclude='./android/.gradle-home' \
  --exclude='./android/.kotlin' \
  --exclude='./android/local.properties' \
  --exclude='./android/build' \
  --exclude='./android/app/build' \
  --exclude='./analytics/.venv' \
  --exclude='./analytics/venv' \
  --exclude='./analytics/__pycache__' \
  --exclude='./analytics/output' \
  --exclude='./.plantaria-validate-server.log' \
  -czf "$SOURCE_ARCHIVE" .

if git -C "$ROOT_DIR" rev-parse --git-dir >/dev/null 2>&1; then
  echo "==> Creando bundle Git"
  git -C "$ROOT_DIR" bundle create "$GIT_BUNDLE" --all
else
  echo "No hay repositorio Git; se omite bundle." >> "$MANIFEST"
fi

if [[ -f "$APK_SOURCE" && "${INCLUDE_APK:-1}" != "0" ]]; then
  echo "==> Copiando APK debug"
  cp "$APK_SOURCE" "$APK_TARGET"
else
  echo "APK no incluido. Ejecuta con INCLUDE_APK=1 y compila Android antes si lo necesitas." >> "$MANIFEST"
fi

if [[ "${INCLUDE_DB_DUMP:-0}" == "1" ]]; then
  if command -v pg_dump >/dev/null 2>&1; then
    echo "==> Exportando dump PostgreSQL"
    PGPASSWORD="${PLANTARIA_DB_PASSWORD:-}" pg_dump \
      -h "${PLANTARIA_DB_HOST:-127.0.0.1}" \
      -p "${PLANTARIA_DB_PORT:-5432}" \
      -U "${PLANTARIA_DB_USER:-plantaria}" \
      -d "${PLANTARIA_DB_NAME:-plantaria}" \
      --no-owner \
      --no-privileges \
      > "$DB_DUMP"
  else
    echo "pg_dump no disponible; dump de base de datos omitido." >> "$MANIFEST"
  fi
else
  echo "Dump de base de datos omitido por defecto. Ejecuta INCLUDE_DB_DUMP=1 $0 para incluirlo." >> "$MANIFEST"
fi

echo "==> Calculando checksums"
(
  cd "$PACKAGE_DIR"
  find . -maxdepth 1 -type f ! -name SHA256SUMS -print0 \
    | sort -z \
    | xargs -0 sha256sum > SHA256SUMS
)

echo
echo "Paquete listo:"
echo "$PACKAGE_DIR"
