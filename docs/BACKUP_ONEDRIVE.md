# Backup en OneDrive

El proyecto puede empaquetarse para OneDrive con:

```bash
./scripts/package_for_onedrive.sh
```

Destino por defecto en este entorno:

```text
/mnt/c/Users/DavidAdrianLimachiPe/OneDrive - INSTITUTO SUPERIOR DE FORMACION PROFESIONAL CEAC FP/PlantariaBackups
```

Si esa carpeta no existe, el script prueba el OneDrive personal y finalmente `backups/` dentro del repo.

## Que incluye

- `plantaria-source-*.tar.gz`: codigo fuente, documentacion, scripts y lockfiles.
- `plantaria-git-*.bundle`: historial Git comprometido.
- `app-debug-*.apk`: APK debug si ya existe.
- `MANIFEST.txt`: contenido y pasos de restauracion.
- `SHA256SUMS`: hashes para comprobar integridad.

## Que excluye

- `.env` y `.env.*`;
- `.git/` dentro del tar de fuente;
- `vendor/`;
- `node_modules/`;
- builds Android y Gradle;
- caches temporales;
- logs;
- storage generado.

La idea es guardar un paquete reproducible y limpio, no copiar gigas de dependencias reconstruibles.

## Incluir base de datos

Por defecto no se incluye dump de PostgreSQL para evitar subir datos locales o hashes de usuarios sin querer.

Para incluirlo:

```bash
INCLUDE_DB_DUMP=1 ./scripts/package_for_onedrive.sh
```

Variables opcionales:

```bash
PLANTARIA_DB_HOST=127.0.0.1
PLANTARIA_DB_PORT=5432
PLANTARIA_DB_NAME=plantaria
PLANTARIA_DB_USER=plantaria
PLANTARIA_DB_PASSWORD=plantaria
```

## Elegir otra carpeta

```bash
./scripts/package_for_onedrive.sh "/mnt/c/Users/DavidAdrianLimachiPe/OneDrive/PlantariaBackups"
```

O con variable:

```bash
PLANTARIA_BACKUP_DIR="/ruta/de/destino" ./scripts/package_for_onedrive.sh
```

## Restauracion rapida

1. Descomprimir `plantaria-source-*.tar.gz`.
2. En `backend/`: `composer install`.
3. Copiar `.env.example` a `.env`.
4. Ejecutar `php artisan key:generate`.
5. Levantar base: `docker compose up -d postgis`.
6. Ejecutar `php artisan migrate --seed`.
7. En `android/`: `./gradlew :app:assembleDebug`.
8. Validar: `./scripts/validate_project.sh`.
