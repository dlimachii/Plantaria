# Plantaria

`Plantaria` es un TFC de DAM orientado a una plataforma colaborativa de plantas geolocalizadas: un usuario registra una planta con foto y ubicacion, otros usuarios pueden añadir observaciones temporales, y moderadores o administradores validan la informacion botanica.

## Estado del MVP

El proyecto ya contiene un flujo real de punta a punta:

- backend Laravel con Sanctum, API, panel web de moderacion/admin y subida de fotos;
- PostgreSQL/PostGIS local con Docker Compose;
- cliente Android Kotlin/Jetpack Compose con MapLibre, login, mapa, reportes, observaciones, camara, galeria y GPS;
- modulo auxiliar `analytics/` en Python;
- scripts de apoyo para prueba movil.

La prioridad actual es estabilizar, validar en telefono fisico y documentar la entrega. iOS, web publica completa e IA de identificacion vegetal quedan como fases posteriores.

## Estructura

```text
backend/    API Laravel, panel web y tests feature
android/    app Android Kotlin + Jetpack Compose
analytics/  scripts Python para analitica auxiliar
scripts/    utilidades de arranque e instalacion de APK
Contexto/   memoria tecnica viva entre sesiones
compose.yaml PostgreSQL/PostGIS local
```

## Arranque rapido

Desde la raiz del repo:

```bash
./scripts/start_mobile_stack.sh
```

Ese comando levanta PostGIS, migra y rellena datos demo, prepara storage e inicia Laravel en:

```text
http://0.0.0.0:8000
```

URLs de API para Android:

- emulador: `http://10.0.2.2:8000/api/`
- movil por USB con `adb reverse`: `http://127.0.0.1:8000/api/`
- movil por Wi-Fi: `http://IP_DEL_PC:8000/api/`

## Backend

Arranque manual:

```bash
docker compose up -d postgis
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve --host=0.0.0.0 --port=8000
```

Panel admin:

```text
http://127.0.0.1:8000/admin
```

Mas detalle en `backend/README.md`.

## Android

Compilar APK debug:

```bash
cd android
./gradlew :app:assembleDebug
```

Instalar en movil fisico:

```bash
../scripts/install_debug_apk.sh
```

APK generado:

```text
android/app/build/outputs/apk/debug/app-debug.apk
```

Mas detalle en `android/README.md`.

## Datos demo

Usuario demo para la app Android:

```text
handle: plantaria_demo
password: PlantariaDemo1
```

La cuenta admin se configura con:

```text
PLANTARIA_ADMIN_HANDLE
PLANTARIA_ADMIN_EMAIL
PLANTARIA_ADMIN_PASSWORD
```

## Documentacion de entrega

- `docs/GUIA_DEMO.md`: guion para ensenar el MVP.
- `docs/CHECKLIST_VALIDACION_MOVIL.md`: lista de prueba fisica del APK.
- `docs/MEMORIA_TFC.md`: base tecnica para memoria o defensa.
- `docs/API.md`: referencia practica de endpoints.
- `docs/BACKUP_ONEDRIVE.md`: empaquetado limpio del proyecto para OneDrive.

## Validacion

Backend:

```bash
cd backend
php artisan test
```

Android:

```bash
cd android
./gradlew :app:assembleDebug
```

Scripts:

```bash
bash -n scripts/start_mobile_stack.sh
bash -n scripts/install_debug_apk.sh
```

Validacion integral:

```bash
./scripts/validate_project.sh
```

Variables utiles:

- `SKIP_ANDROID_BUILD=1` salta la compilacion Android.
- `SKIP_POSTGIS_SMOKE=1` salta la prueba HTTP contra PostgreSQL/PostGIS.
- `PLANTARIA_VALIDATE_PORT=8010` cambia el puerto temporal de validacion.

Estado reciente:

```text
php artisan test: 24 tests, 113 assertions
./gradlew :app:assembleDebug: BUILD SUCCESSFUL
```

La validacion integral tambien comprueba que los tokens de usuarios no activos quedan bloqueados y que las rutas admin API respetan roles.

## Pendiente real antes de entrega

- Revalidar en telefono fisico el APK actual.
- Probar login, mapa, busqueda, GPS, camara, galeria, subida de foto, creacion de reporte y observacion.
- Elegir proveedor final de tiles o dejar claramente documentado que el estilo actual es de demo/desarrollo.
