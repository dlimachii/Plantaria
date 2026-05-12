# Registro de sesiones

## 2026-05-10 20:47 CEST

### Corrección de servidor Android `prod` + revisión VPS

- Android: se confirmó que la variante `prod` compila contra `https://api.dlimachii.com/api/`.
- Android: se desactivó `PLANTARIA_BOOTSTRAP_CONFIG_URL` porque `https://dlimachii.com/plantaria.json` no devuelve JSON válido del proyecto.
- Android: si el móvil conserva una URL antigua de desarrollo (`127.0.0.1`, `10.0.2.2`, `localhost` o `0.0.0.0`) en `DataStore`, la app la elimina al arrancar y vuelve al servidor público por defecto.
- Scripts/docs: `install_debug_apk.sh`, `install_debug_apk.ps1` y `android/README.md` quedan alineados con el comportamiento real; `adb reverse` ya no se presenta como cambio automático de servidor.
- VPS: `https://api.dlimachii.com/api/records?limit=1` responde `200 OK` con JSON real; en el servidor el repo está en `main` sobre el commit `e65c985`.
- Verificación: `./gradlew :app:assembleProdDebug` termina en `BUILD SUCCESSFUL`.

## 2026-05-10 21:20 CEST

### Rescate controlado del árbol sucio del VPS

- Se creó backup del árbol de trabajo del VPS en `/root/plantaria-backups/20260510-190443/` con `git-status.txt`, `working-tree.patch` y `backend-working-tree.tgz`.
- Se verificó que el VPS está en `main` sobre `e65c985`, pero con cambios manuales sin commit en controladores y vistas del panel admin/moderación.
- Se comparó el diff del VPS con el workspace local: la mayoría de cambios ya estaban también en local; los principales desajustes eran variantes menores de textos y la gestión visible de `OLLAMA_ENABLED`.
- Se recuperó exactamente `backend/package-lock.json` desde el VPS para que el repo refleje el estado real del despliegue y no un lock regenerado con otra toolchain.
- `backend/resources/views/admin/show.blade.php` quedó fuera de integración porque es un duplicado no referenciado por rutas; sigue preservado dentro del backup del VPS por si más adelante hace falta revisarlo.
- Se revalidó el backend en local con `php artisan test --filter=AdminPanelTest` y `php artisan test`: ambos pasan (`38 tests`, `176 assertions`).

## 2026-05-08 23:48 CEST

### Notificación de verificación (actividad de usuario)

- Backend: cuando un moderador/admin verifica o rechaza un reporte, además del evento del moderador se registra un evento `record_verified` también para el autor del reporte (visible en `/me/activity`).
- Backend: cuando un admin edita un reporte desde el panel, se registra también un evento `record_updated` para el autor (con metadata de estado anterior → nuevo).
- Android: la pantalla `Usuario` muestra mejor los eventos de `record_verified` rechazados (icono/color) y `record_updated`.
- Despliegue: se copiaron los controladores actualizados al VPS (`161.35.204.224`) y se limpió caché con `php artisan optimize:clear`.
- Verificación: `php artisan test` pasa y `./gradlew :app:assembleProdDebug` compila.

## 2026-05-08 22:56 CEST

### Correcciones de textos + demo multi-APK + ubicación

- Se corrigieron faltas ortográficas visibles en el panel admin web (Blade) y en mensajes del backend (acentos y tildes: moderación, contraseña, sesión, ubicación, analítica, etc.).
- Se aplicaron las mismas correcciones en el VPS (`161.35.204.224`) por SSH en `/var/www/Plantaria/backend` y se limpiaron cachés con `php artisan optimize:clear`.
- Android: se endureció la captura de ubicación para evitar ubicaciones antiguas/erróneas usando un límite de antigüedad (si la última ubicación es vieja, se considera no disponible).
- Android: se añadieron flavors `prod`, `demoA`, `demoB`, `demoC` para poder instalar varias APKs en paralelo en el mismo móvil (evita desloguear/reloguear) y se actualizaron scripts/docs para compilar e instalar por flavor (`PLANTARIA_ANDROID_FLAVOR`).
- Se verificó compilación con `./gradlew :app:assembleProdDebug`.

## 2026-05-08 12:34 CEST

### Chuleta tecnica del proyecto

- Se inspecciono el stack real del repo (Laravel 13 + Sanctum, PostgreSQL/PostGIS en Docker, Android Compose + MapLibre, analitica Python+pandas).
- Se creo `docs/BRIEF_TECNICO.md` como resumen operativo y justificable para defensa: arquitectura, librerias usadas y puntos de explicacion rapida.

## 2026-05-07 14:18 CEST

### Despliegue real en VPS + dominio para demo (API publica)

- Objetivo: que el APK funcione sin depender de `adb reverse` ni backend local, con API accesible desde cualquier red.
- VPS (DigitalOcean): IP `161.35.204.224`, Ubuntu `24.04.4 LTS`.
- Dominio: se creo el registro DNS `A` para `api.dlimachii.com` apuntando a `161.35.204.224` (gestionado en Porkbun).
- Backend desplegado desde GitHub (repo publico) en `/var/www/Plantaria` (Laravel en `/var/www/Plantaria/backend`).
- Stack instalado en VPS: Nginx + PHP 8.3 FPM + PostgreSQL 16 + PostGIS.
- PostgreSQL:
  - rol/usuario `plantaria` con password `plantaria`;
  - base de datos `plantaria` con owner `plantaria`;
  - extension `postgis` habilitada (ademas del guard en migracion).
- Laravel (produccion):
  - `.env` ajustado a `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://api.dlimachii.com`;
  - `php artisan key:generate --force`;
  - `php artisan migrate --force --no-interaction` y `php artisan db:seed --force --no-interaction`;
  - `php artisan storage:link` (uploads/fotos via `public/storage`).
- Frontend del panel admin:
  - se instalo Node 20 (NodeSource) porque Vite requiere Node >= 20;
  - `npm install` y `npm run build` para generar `public/build/*`.
- Nginx:
  - vhost `api.dlimachii.com` apuntando a `/var/www/Plantaria/backend/public`;
  - `client_max_body_size 25m` para subir fotos.
- SSL:
  - certbot + plugin nginx;
  - HTTPS activo en `https://api.dlimachii.com` con renovacion automatica.
- Smoke test:
  - `https://api.dlimachii.com` devuelve `200 OK`;
  - `https://api.dlimachii.com/api/records?limit=1` devuelve JSON con `data`.

### Android (demo contra VPS)

- La app puede guardar el servidor desde login (campo `Servidor (API)` + `Guardar servidor`) para evitar builds distintas.
- La build debug se dejo apuntando por defecto a `https://api.dlimachii.com/api/` (para que funcione sin PC).
- En dispositivos que tuvieran guardada la URL vieja `http://127.0.0.1:8000`, fue necesario actualizar el servidor desde login o borrar datos de la app.

### Credenciales demo

- App (demo): `plantaria_demo` (password configurado en `.env`, no publicado en repo).
- Panel admin: `https://api.dlimachii.com/admin/login`:
  - `plantaria_admin`
  - `plantaria_mod`
  - `plantaria_user`

### Asistente IA (Ollama) visible pero no operativo en demo

- Se incorporo `OLLAMA_ENABLED` para poder dejar el asistente visible en `/admin/assistant` sin ejecutar IA en la demo.
- En el VPS se dejo `OLLAMA_ENABLED=false`.

### Instalacion APK en movil (Windows + WSL)

- Como `adb` no estaba en PATH, se uso `adb.exe` de Android SDK en Windows:
  - ruta tipica: `$env:LOCALAPPDATA\\Android\\Sdk\\platform-tools\\adb.exe`.
- La distro WSL no era `Ubuntu` sino `UbuntuBackup`; para instalar el APK se uso `\\wsl$\\UbuntuBackup\\...\\app-debug.apk` o `wslpath -w`.
- Si ADB mostraba `unauthorized`, se acepto el popup de depuracion USB en el movil (o se revocaron autorizaciones y se reconecto el cable).

## 2026-05-04 20:29 CEST

### Limpieza de repositorio e indexación

- Se auditó Git y se confirmó que el commit actual no tenía dependencias ni builds versionados; `git ls-files -ci --exclude-standard` no devolvió archivos.
- Se identificó basura local ignorada: `backend/vendor/`, `analytics/.venv/`, `android/app/build/`, cachés Gradle/Kotlin/Python, `backend/.env`, sqlite local, logs, ficheros de storage y temporales de Office.
- Tras la corrección del alcance, `DocumentoTFG/Plantaria_TFG_DAM.docx` queda rastreado en Git como material de referencia para el agente.
- Se restauró el archivo Laravel `backend/resources/views/welcome.blade.php` y la ruta `/` para no tocar scaffolding que no sea artefacto generado.
- La limpieza queda centrada en artefactos generados/locales y en reglas auxiliares de ignore/atributos para facilitar indexación.

## 2026-05-04 20:08 CEST

### Sincronización con GitHub

- Se identificó que el último trabajo documentado previo era el ajuste del documento TFG del 2026-05-04 16:54 CEST.
- Se revisó el estado Git: la rama `mobile-beta-20260427` contenía el trabajo acumulado posterior a `main`, y `main` era ancestro directo.
- Se añadió a `.gitignore` la exclusión de temporales de Office (`~$*.docx`, `~$*.xlsx`, `~$*.pptx`) para no subir el archivo temporal `DocumentoTFG/~$antaria_TFG_DAM.docx`.
- Se consolidaron los cambios en el commit `a377e9b` (`Add analytics assistant and TFG documentation`).
- Se avanzó `main` al mismo commit por fast-forward local y se subieron a GitHub `main` y `mobile-beta-20260427` en `git@github.com:dlimachii/Plantaria.git`.

## 2026-05-04 16:40 CEST

### Documento Word profesional para TFG

- Se analizó la plantilla `DocumentoTFG/TFG DAM_DAW.docx` y se extrajo su estructura: portada, tabla de contenidos, capítulos de introducción, análisis, diseño, organización, actividades profesionales, líneas de actuación y bibliografía.
- Se inventarió el repositorio real para redactar la memoria a partir de código y configuración existentes: backend Laravel, Android Kotlin/Jetpack Compose, PostgreSQL/PostGIS, analítica Python/pandas, panel admin, rutas, modelos, migraciones y scripts.
- Se creó `scripts/generate_tfg_docx.py` como generador reproducible de Word basado en la plantilla original.
- Se generó `DocumentoTFG/Plantaria_TFG_DAM.docx` con formato académico, tablas, encabezados jerárquicos, campo de índice automático y marcadores `[PENDIENTE: ...]` donde la información depende del alumno o de investigación externa.
- El documento incluye una sección final con preguntas pendientes para completar portada, mercado, presupuesto, modalidad de trabajo, publicación, pliego y autoevaluación personal.

### Validaciones ejecutadas

- `python3 scripts/generate_tfg_docx.py`: correcto.
- `unzip -t DocumentoTFG/Plantaria_TFG_DAM.docx`: correcto, sin errores en el paquete DOCX.
- Parseo XML de `word/document.xml`: correcto; 65 encabezados con nivel de esquema.
- Verificación de índice automático: campo `TOC` presente y `w:updateFields` activado.
- `python3 -m py_compile scripts/generate_tfg_docx.py`: correcto.

### Ajuste posterior 2026-05-04 16:49 CEST

- Se completó la comparativa de plataformas reales del sector con iNaturalist, Pl@ntNet, GBIF, Observation.org/ObsIdentify, Flora Incognita, PictureThis, PlantSnap y OpenStreetMap.
- Se ajustó el enfoque del documento: Plantaria queda presentado como TFG/MVP con posible escalado comunitario, no como proyecto económico.
- Se añadió que el código se plantea como público y libre para uso, lectura, escritura y ejecución.
- Se sustituyeron pendientes de empresas/modelo de negocio por texto redactado y fuentes en bibliografía.
- Se aclaró que la valoración económica se refiere a recursos/costes de ejecución si el proyecto se despliega, no a beneficios ni ventas.
- Se dejó indicado que el trabajo es individual.
- Nueva validación: `python3 scripts/generate_tfg_docx.py`, `python3 -m py_compile scripts/generate_tfg_docx.py` y `unzip -t DocumentoTFG/Plantaria_TFG_DAM.docx` correctos; el DOCX queda con 65 encabezados, 16 tablas y 18 marcadores `[PENDIENTE: ...]`.

### Ajuste posterior 2026-05-04 16:54 CEST

- Se añadió una valoración económica concreta de costes de ejecución para Plantaria:
  - entrega TFG local: 0 € adicionales si ya se dispone de equipo y móvil;
  - despliegue público pequeño: 10-25 €/mes más dominio anual;
  - MVP comunitario con mapas estables: 35-60 €/mes más dominio anual;
  - escala mayor: desde 60 €/mes en adelante según almacenamiento, mapas, backups, CDN y mantenimiento.
- Se añadieron fuentes de coste en bibliografía: Google Play Console, Hetzner, Cloudflare R2, MapTiler y Dominios.es.
- Nueva validación: `python3 scripts/generate_tfg_docx.py`, `python3 -m py_compile scripts/generate_tfg_docx.py` y `unzip -t DocumentoTFG/Plantaria_TFG_DAM.docx` correctos; el DOCX queda con 65 encabezados, 18 tablas y 17 marcadores `[PENDIENTE: ...]`.

## 2026-04-30 17:15 CEST

### Operativa PowerShell para móvil y pandas cacheado

- Se añadió `scripts/install_debug_apk.ps1` para instalar el APK desde Windows PowerShell.
- Queda registrado que en este equipo el teléfono físico se detecta de forma fiable desde PowerShell, no desde WSL.
- Flujo correcto para móvil físico:
  - compilar el APK desde WSL;
  - ejecutar `adb devices`, `adb reverse tcp:8000 tcp:8000` y `adb install -r` desde Windows PowerShell;
  - usar `scripts/install_debug_apk.ps1` para automatizar esos pasos.
- Se investigó el error `ModuleNotFoundError: No module named 'pandas'` al ejecutar `php artisan plantaria:analytics:build`.
- Causa: Laravel estaba usando configuración cacheada con `python3` en vez de `../analytics/.venv/bin/python`.
- Solución validada: `php artisan config:clear` y repetir `php artisan plantaria:analytics:build`.

### Validaciones ejecutadas

- `analytics/.venv/bin/python -c 'import pandas as pd; print(pd.__version__)'`: devuelve `2.3.3`.
- `php artisan config:clear`: correcto.
- `php artisan plantaria:analytics:build`: correcto contra PostgreSQL local; generó `admin_dashboard.json`.

## 2026-04-30 17:08 CEST

### Cierre de números y criterio de mapa

- Se actualizaron números finales visibles en documentación principal:
  - `php artisan test`: 38 tests, 176 assertions;
  - build Android debug: `BUILD SUCCESSFUL`;
  - analítica pandas: `php artisan plantaria:analytics:build` correcto;
  - perfilado rápido: `scripts/profile_app_performance.sh` correcto.
- Se ajustó la redacción de mapa en README, README Android y memoria:
  - para el TFC se mantiene MapLibre con `https://demotiles.maplibre.org/style.json` como estilo de demo/desarrollo documentado;
  - no queda como decisión bloqueante de entrega;
  - si Plantaria se publica fuera del TFC, se sustituirá por un proveedor final de tiles compatible con MapLibre o por hosting propio.

## 2026-04-30 16:45 CEST

### Perfilado rápido para optimización

- Se añadió `scripts/profile_app_performance.sh`.
- El script mide tiempos medios/min/max de endpoints críticos para Android:
  - listado de mapa;
  - búsqueda por radio PostGIS;
  - búsqueda de planta;
  - ficha completa;
  - login demo;
  - actividad de usuario autenticada.
- También informa del tamaño del APK debug y, si hay dispositivo ADB con la app abierta, muestra memoria/render básicos.
- `scripts/validate_project.sh` ahora valida la sintaxis del nuevo script.
- `README.md` documenta el uso y variables principales.

### Resultados base

- `Mapa registros`: 16.7 ms de media.
- `Mapa radio PostGIS`: 66.8 ms de media, con máximo de 130.3 ms.
- `Búsqueda Lavanda`: 21.2 ms de media.
- `Ficha completa`: 24.2 ms de media.
- `Login demo`: 212.8 ms.
- `Actividad usuario`: 22.6 ms de media.
- `app-debug.apk`: 77.9 MiB.
- No había dispositivo ADB listo para medir memoria/render de la app.

### Validaciones ejecutadas

- `PLANTARIA_PROFILE_RUNS=3 PLANTARIA_PROFILE_PORT=8021 ./scripts/profile_app_performance.sh`: correcto.
- `bash -n scripts/validate_project.sh && bash -n scripts/profile_app_performance.sh && git diff --check`: correcto.

## 2026-04-30 16:20 CEST

### Asistente admin con consultas directas seguras

- Se añadió `AdminAssistantDirectQuery` para resolver preguntas administrativas conocidas sin depender de pandas ni de SQL generado por IA.
- `/admin/assistant` consulta primero esa capa directa y solo usa Ollama+pandas para preguntas abiertas no reconocidas.
- Se creó `analytics/.venv`, se instalaron las dependencias de `analytics/requirements.txt` y se configuró `.env` local con `PLANTARIA_ANALYTICS_PYTHON=../analytics/.venv/bin/python`.
- Se generó correctamente `backend/storage/app/analytics/output/admin_dashboard.json` contra PostgreSQL/PostGIS local.
- Se cubren las preguntas:
  - usuarios con más observaciones, contando `observations` por `author_user_id` y mostrando también observaciones de seguimiento;
  - plantas verificadas sin `verified_scientific_name`, filtrando `plant_records.verification_status = verified`.
- El texto del panel aclara que la consulta directa a BBDD funciona aunque todavía no exista snapshot pandas.

### Validaciones ejecutadas

- `php artisan test --filter=AdminPanelTest`: 15 tests, 71 assertions, todo pasando.
- `php artisan test`: 38 tests, 176 assertions, todo pasando.
- `php artisan plantaria:analytics:build`: correcto; exportó 4 usuarios, 6 registros, 10 observaciones, 0 flags y 54 eventos.
- `git diff --check`: correcto.

## 2026-04-28 17:37 CEST

### Panel admin con pandas y Ollama

- Se añadió `analytics/build_admin_analytics.py`, que lee CSV con `pandas` y genera `admin_dashboard.json`.
- Se añadió el comando `php artisan plantaria:analytics:build`:
  - exporta `users`, `plant_records`, `observations`, `moderation_flags` y `app_events` a CSV;
  - ejecuta el script pandas salvo que se use `--skip-python`;
  - deja el JSON en `storage/app/analytics/output/admin_dashboard.json`.
- El dashboard `/admin` muestra un bloque `Analitica Python + pandas` cuando existe el snapshot.
- Un `ADMIN` puede regenerar la analítica desde el panel.
- Se añadió `/admin/assistant` para consultas con Ollama local usando el snapshot pandas como contexto.
- Se añadieron variables `.env`: `PLANTARIA_ANALYTICS_PYTHON`, `OLLAMA_BASE_URL` y `OLLAMA_MODEL`.
- Se actualizaron README y guía de demo.

### Validaciones ejecutadas

- `php artisan test --filter=AdminPanelTest`: 14 tests, 63 assertions, todo pasando.
- `php artisan test`: 37 tests, 168 assertions, todo pasando.
- `python3 -m py_compile analytics/build_admin_analytics.py`: correcto.
- El comando `plantaria:analytics:build --skip-python` queda cubierto por test con sqlite; fuera de tests requiere base PostgreSQL activa.

## 2026-04-28 17:24 CEST

### Actividad propia en perfil

- Se añadió `GET /api/me/activity` para devolver actividad reciente de la cuenta autenticada.
- La actividad propia incluye reportes creados, observaciones/commits reales, denuncias enviadas, cambios de perfil y acciones de moderación/admin.
- Se excluye la observación inicial automática del reporte para que no aparezca como commit duplicado.
- Android cambia la pestaña `Usuario`: ya no recibe los registros globales del mapa y ahora muestra actividad propia con estado vacío para cuentas sin acciones.
- Se documentó el comportamiento en la referencia API, README Android, README backend, guía de demo y contexto.

### Validaciones ejecutadas

- `php artisan test --filter=UserActivityTest`: 4 tests, 19 assertions, todo pasando.
- `php artisan test`: 33 tests, 157 assertions, todo pasando.
- `./gradlew :app:assembleDebug`: `BUILD SUCCESSFUL`.
- `git diff --check`: correcto.

## 2026-04-28 16:48 CEST

### Roles, login y portada Android

- Roles existentes confirmados: `user`, `mod` y `admin`.
- `DatabaseSeeder` crea cuentas de prueba por rol:
  - `plantaria_user`;
  - `plantaria_mod`;
  - `plantaria_admin`.
- Se mantiene `plantaria_demo` como usuario con datos demo (password fuera de Git).
- Android añade splash/logo animado inspirado en el SVG de Plantaria.
- La pantalla de login ya no muestra el campo técnico de URL de API.
- Android decide URL local automáticamente:
  - emulador con `10.0.2.2`;
  - teléfono físico con `127.0.0.1`, pensado para `adb reverse`.
- `scripts/install_debug_apk.sh` ejecuta `adb reverse tcp:8000 tcp:8000` automáticamente si hay ADB.
- En perfil, el cierre de sesión queda en el menú de tres puntos.
- Se actualizaron README, guía de demo, API docs y contexto.

### Validaciones ejecutadas

- `php artisan test --filter=DatabaseSeederTest`: 2 tests, 9 assertions, todo pasando.
- `php artisan test`: 29 tests, 138 assertions, todo pasando.
- `./gradlew :app:assembleDebug`: `BUILD SUCCESSFUL`.
- `bash -n scripts/install_debug_apk.sh`: correcto.
- `bash -n scripts/start_mobile_stack.sh`: correcto.
- `git diff --check`: correcto.
- `docker compose up -d postgis`: `plantaria-postgis` en ejecución.
- `php artisan db:seed --class=DatabaseSeeder --no-interaction`: ejecutado correctamente contra PostgreSQL local.
- Verificación en PostgreSQL local: `plantaria_admin`, `plantaria_mod`, `plantaria_user` y `plantaria_demo` existen y están activos.
- `adb devices`: ADB arranca, pero no hay dispositivos conectados.

## 2026-04-28 16:10 CEST

### Avance del panel admin web

- Se reforzó el listado web de flags para mostrar el objetivo denunciado con contexto real:
  - registros con `public_id`, nombre y enlace a la ficha de moderación;
  - observaciones con enlace al registro relacionado y ancla de observación;
  - usuarios con enlace a edición solo para administradores.
- Se añadieron filtros en `/admin/flags` por texto y tipo de objetivo (`record`, `observation`, `user`), manteniendo el filtro por estado.
- La ficha web de moderación de un registro ahora muestra flags relacionados con el propio registro y con sus observaciones.
- Se añadieron tests feature para cubrir el contexto/filtro de flags y los flags relacionados en la ficha de registro.

### Validaciones ejecutadas

- `php artisan test --filter=AdminPanelTest`: 10 tests, 52 assertions, todo pasando.
- `php artisan test`: 28 tests, 132 assertions, todo pasando.
- `git diff --check`: correcto.

## 2026-04-27 20:23 CEST

### Metadatos del reporte en ficha

- Se aplicó al bloque de datos del reporte el mismo criterio visual usado en los commits.
- La ficha completa muestra los datos del reporte en filas etiqueta/valor para:
  - nombre común;
  - nombre científico;
  - nombre provisional;
  - autor;
  - fecha de creación;
  - coordenadas.
- La descripción queda en una fila propia para permitir varias líneas sin romper el diseño.
- Se recompiló e instaló el APK actualizado en móvil físico.

### Validaciones ejecutadas

- `./gradlew :app:assembleDebug`: `BUILD SUCCESSFUL`.
- `git diff --check`: correcto.
- `adb install -r`: `Success`.

## 2026-04-27 20:14 CEST

### Metadatos de commit en vertical

- Se ajustaron los metadatos de cada observación/commit en la ficha completa.
- Antes se mostraban en horizontal como chips (`Commit`, usuario y estado), lo que podía deformarse visualmente.
- Ahora se muestran en una columna de tres filas etiqueta/valor:
  - `Commit`;
  - `Usuario`;
  - `Estado`.
- Se recompiló e instaló el APK actualizado en móvil físico.

### Validaciones ejecutadas

- `./gradlew :app:assembleDebug`: `BUILD SUCCESSFUL`.
- `git diff --check`: correcto.
- `adb install -r`: `Success`.

## 2026-04-27 20:03 CEST

### Reordenación visual de commits

- Se ajustó la ficha completa para que la foto principal del reporte use formato 4:3 y `ContentScale.Fit`, evitando recortes en la vista grande.
- Se ajustaron las tarjetas de observación/commit:
  - fecha y hora arriba;
  - foto en formato 4:3 completa;
  - nota/descripción debajo;
  - metadatos al final como chips (`Commit #n`, usuario y estado).
- Se dejó de mostrar el `source_type` crudo (`update`) en la UI de commits porque era información interna poco clara para usuario final.
- El cambio se limitó a la ficha completa y commits; previews y resultados de búsqueda no se tocaron.
- Se recompiló e instaló el APK actualizado en móvil físico.

### Validaciones ejecutadas

- `./gradlew :app:assembleDebug`: `BUILD SUCCESSFUL`.
- `git diff --check`: correcto.
- `adb install -r`: `Success`.

## 2026-04-27 19:18 CEST

### Ajuste de ubicación e historial

- Se ajustó el mapa para no pedir ubicación actual cada vez que se entra.
- Al abrir el mapa, Android usa solo la última ubicación conocida si existe y no muestra el proceso lento de búsqueda.
- La búsqueda de ubicación fresca queda reservada al botón `Mi ubicación`.
- Se ajustó la ficha completa para que la foto original del reporte quede como portada fija.
- El historial de observaciones deja de mostrar la observación inicial generada automáticamente con el reporte.
- Los commits/observaciones posteriores se mantienen en orden de más nuevo a más antiguo.
- Se recompiló e instaló el APK actualizado en móvil físico.

### Validaciones ejecutadas

- `./gradlew :app:assembleDebug`: `BUILD SUCCESSFUL`.
- `git diff --check`: correcto.
- `adb install -r`: `Success`.

## 2026-04-27 18:43 CEST

### Pulido de mapa y guardado de corte beta

- Se quitó el bloque `Resumen del mapa` porque ocupaba espacio sin aportar valor al usuario final.
- Los resultados de búsqueda pasan a vivir dentro del mismo bloque del buscador.
- La zona de resultados tiene altura limitada y scroll interno para mostrar de primeras unas 3-4 coincidencias y permitir más resultados si existen.
- Se mantuvo el buscador único de plantas y los botones de recargar/ubicación.
- Se recompiló e instaló el APK actualizado en el móvil físico.

### Validaciones ejecutadas

- `php artisan test --filter=PlantRecordTest`: 5 tests, 32 assertions, todo pasando.
- `php artisan test`: 26 tests, 122 assertions, todo pasando.
- `./gradlew :app:assembleDebug`: `BUILD SUCCESSFUL`.
- `git diff --check`: correcto.
- `adb install -r`: `Success`.

## 2026-04-27 18:25 CEST

### Buscador único de plantas

- Se atendió feedback móvil: no debe haber dos buscadores en el mapa.
- Android queda con un único campo `Buscar plantas`, orientado a nombre común o científico.
- Se retiró de la UI el campo `Mover mapa`; la geocodificación/zona deja de ser flujo visible en el mapa para este corte.
- El backend ajustó `GET /api/records?q=` para buscar solo por nombres de planta:
  - `provisional_common_name`;
  - `verified_common_name`;
  - `verified_scientific_name`.
- Se quitó la búsqueda por `public_id` del filtro textual para respetar que el buscador sea por nombres.
- Se añadió test feature que comprueba que buscar por nombre devuelve resultados y buscar por ID público no.
- Se recompiló e instaló el APK en móvil físico.

### Validaciones ejecutadas

- `php artisan test --filter=PlantRecordTest`: 5 tests, 32 assertions, todo pasando.
- `./gradlew :app:assembleDebug`: `BUILD SUCCESSFUL`.
- `adb install -r`: `Success`.

## 2026-04-27 18:19 CEST

### Ajustes tras prueba móvil real

- El usuario validó en móvil físico que:
  - el login ya funciona;
  - cargan los pines del mapa;
  - el marcador de ubicación se distingue visualmente;
  - la búsqueda por `Lavanda` funciona y se puede limpiar;
  - la ubicación ya estaba autorizada y el marcador aparece correctamente.
- Se aclaró que las imágenes demo actuales no son fotos reales de plantas, sino imágenes PNG generadas por el seeder con colores distintos para evitar rutas rotas.
- Se corrigió el fallo al crear reporte: Android no manda `plant_condition` y el backend estaba enviando `null` explícito a columnas con default `unknown`.
- Se actualizó backend para usar `PlantCondition::UNKNOWN` por defecto al crear registros y observaciones cuando el cliente no envía condición.
- Se añadió test feature para crear registro y observación sin `plant_condition`.
- Se ajustó Android:
  - panel superior de mapa más compacto;
  - lista de resultados al buscar registros, con miniatura y distancia cuando hay ubicación de usuario;
  - ficha de registro en pantalla completa con flecha de vuelta, foto principal, nombres, metadatos e historial de observaciones tipo perfil;
  - conversión previa de drawables XML a bitmap para iconos de MapLibre mantenida.
- Se recompiló e instaló el APK actualizado en el teléfono físico con `adb install -r`: `Success`.
- Se abrió la app desde ADB; no apareció `FATAL EXCEPTION` y el proceso `com.plantaria.app` quedó vivo.

### Validaciones ejecutadas

- `php artisan test --filter=PlantRecordTest`: 4 tests, 28 assertions, todo pasando.
- `php artisan test`: 25 tests, 118 assertions, todo pasando.
- `./gradlew :app:assembleDebug`: `BUILD SUCCESSFUL`.
- `adb logcat -d AndroidRuntime:E '*:S'`: sin crashes tras abrir el APK actualizado.

## 2026-04-27 17:31 CEST

### Corrección de crash Android en móvil físico

- Se reprodujo el cierre de la app en el teléfono físico usando `adb.exe` desde WSL contra el móvil conectado por Windows.
- `logcat` mostró `FATAL EXCEPTION` en `MapScreen.kt`: `IconFactory.fromResource` no podía decodificar los marcadores `marker_user_location` y `marker_search_focus` porque MapLibre esperaba un `Bitmap`.
- Se cambió `MapScreen.kt` para convertir los drawables XML de marcador a `Bitmap` mediante `Canvas` antes de crear los iconos de MapLibre.
- Se recompiló Android con `./gradlew :app:assembleDebug`: `BUILD SUCCESSFUL`.
- Se instaló el APK corregido en el móvil con `adb install -r`: `Success`.
- Se relanzó la app desde ADB y no apareció ningún `FATAL EXCEPTION`; `pidof com.plantaria.app` confirmó el proceso vivo.
- La API local en `127.0.0.1:8000` no estaba arrancada durante esta comprobación, así que login/mapa con datos siguen dependiendo de ejecutar `./scripts/start_mobile_stack.sh` en WSL.

## 2026-04-27 16:23 CEST

### Revalidación local y estado móvil

- Se revisó el contexto obligatorio antes de responder sobre el estado actual del proyecto.
- Se ejecutó `./scripts/validate_project.sh` con acceso a Docker/Gradle/PHP fuera del sandbox.
- La validación integral terminó correctamente:
  - sintaxis de scripts correcta;
  - `php artisan test`: 24 tests, 113 assertions, todo pasando;
  - `./gradlew :app:assembleDebug`: `BUILD SUCCESSFUL`;
  - smoke real contra PostgreSQL/PostGIS correcto, incluyendo migración/seed y filtro por radio con `distance_km`.
- El APK debug actual existe en `android/app/build/outputs/apk/debug/app-debug.apk` y pesa aproximadamente 78 MB.
- `adb devices` se pudo ejecutar fuera del sandbox, pero no mostró ningún teléfono conectado.

### Lectura de estado

- El proyecto está listo para prueba física en móvil.
- Todavía no debe considerarse validado definitivamente en móvil porque falta ejecutar `docs/CHECKLIST_VALIDACION_MOVIL.md` en un dispositivo real conectado.
- El siguiente paso útil con móvil presente es arrancar el stack, instalar el APK, configurar la URL de API y probar login, mapa, búsqueda, GPS, cámara, galería, creación de reporte y observación.

## 2026-04-24 17:44 CEST

### Backup empaquetado en OneDrive

- Se añadió `scripts/package_for_onedrive.sh` para crear paquetes limpios del proyecto.
- Se añadió `docs/BACKUP_ONEDRIVE.md` con uso, destino, contenido, exclusiones y restauración.
- Se enlazó la guía desde `README.md`.
- El script empaqueta fuente sin dependencias/builds/secretos, crea `git bundle`, copia APK debug si existe y genera `MANIFEST.txt` + `SHA256SUMS`.
- El dump de PostgreSQL queda desactivado por defecto y se puede incluir con `INCLUDE_DB_DUMP=1`.
- Se añadió el script de backup a la comprobación de sintaxis de `scripts/validate_project.sh`.
- Se creó un paquete real en OneDrive CEAC:
  - `/ruta/a/PlantariaBackups/plantaria-backup-20260424-174446`
- Contenido del paquete:
  - `plantaria-source-20260424-174446.tar.gz`;
  - `plantaria-git-20260424-174446.bundle`;
  - `app-debug-20260424-174446.apk`;
  - `MANIFEST.txt`;
  - `SHA256SUMS`.

### Validaciones ejecutadas

- `bash -n scripts/package_for_onedrive.sh`: correcto.
- `git diff --check`: correcto.
- `sha256sum -c SHA256SUMS` dentro del paquete OneDrive: correcto.

## 2026-04-24 17:26 CEST

### Preparación de demo y validación real PostGIS

- Se añadió `docs/GUIA_DEMO.md` con guion de demo, preparación del entorno, usuarios demo, recorrido por Android, panel web y resolución de fallos frecuentes.
- Se añadió `docs/CHECKLIST_VALIDACION_MOVIL.md` para probar el APK en teléfono real con sesión, mapa, búsqueda, ubicación, fotos, creación de reportes, observaciones y panel web.
- Se añadió `docs/MEMORIA_TFC.md` como base técnica para la memoria/defensa del TFC.
- Se enlazó la nueva documentación desde `README.md`.
- Se validó que `plantaria-postgis` está healthy con `docker compose ps`.
- Se ejecutó `php artisan migrate --seed --no-interaction` contra PostgreSQL local.
- Se arrancó Laravel temporalmente en `127.0.0.1:8001` y se probó `/api/records?latitude=41.3851&longitude=2.1734&radius_km=8&limit=10`.
- El endpoint respondió con registros demo y campo `distance_km`, confirmando el camino real de PostGIS.
- Se probó también una petición inválida con `Accept: application/json` y devolvió errores JSON de validación para `longitude` y `radius_km`.
- Se cerró el servidor temporal de Laravel tras la prueba.
- Se añadió `scripts/validate_project.sh` para repetir en una sola orden la validación de scripts, backend, Android y smoke PostGIS.
- Se actualizó `.gitignore` para ignorar `.plantaria-validate-server.log`, generado temporalmente por el script.
- Se actualizó `backend/composer.json` para que el nombre, descripción y keywords de Composer reflejen Plantaria y no el skeleton genérico de Laravel.
- Se actualizó `DatabaseSeeder` para generar imágenes demo PNG en `storage/app/public/demo` y cambiar los registros demo a rutas `.png`.
- Se añadió `DatabaseSeederTest` para comprobar que el seeder crea registros demo con imagen PNG.
- Se añadió `docs/API.md` con referencia práctica de endpoints, payloads, filtros y autenticación.
- Se sincronizó `composer.lock` con `composer update --lock --no-interaction` tras cambiar metadata de `composer.json`.
- Se añadió middleware `active.user` para bloquear rutas API autenticadas a cuentas no activas.
- Se añadieron tests para login de usuarios baneados, tokens ya existentes de cuentas baneadas y permisos de API admin por rol.

### Validaciones ejecutadas

- `git diff --check`: correcto.
- `./scripts/validate_project.sh`: correcto; ejecutó `php artisan test`, `./gradlew :app:assembleDebug` y smoke PostGIS.
- `php artisan test --filter=DatabaseSeederTest`: correcto.
- `php artisan test --filter=AuthTest`: correcto.
- `php artisan test --filter=ApiAuthorizationTest`: correcto.
- `php artisan migrate --seed --no-interaction`: correcto y generó cuatro PNG demo.
- `composer validate --no-check-publish`: correcto tras sincronizar el lock.
- `pgrep -af 'artisan serve'`: sin servidor Laravel temporal activo tras cerrar la prueba.

### Pendiente inmediato

- Repetir la validación física en teléfono real usando `docs/CHECKLIST_VALIDACION_MOVIL.md`.

## 2026-04-24 17:07 CEST

### Cierre documental y mejora geoespacial mínima

- Se añadió `README.md` en la raíz del repositorio con visión del MVP, estructura, arranque rápido, Android, backend, datos demo, validación y pendientes reales.
- Se sustituyó `backend/README.md`, que seguía siendo el genérico de Laravel, por documentación específica de Plantaria.
- Se añadieron `NOMINATIM_BASE_URL` y `NOMINATIM_USER_AGENT` a `backend/.env.example`.
- Se amplió `GET /api/records` con validación de filtros y búsqueda por radio mediante `latitude`, `longitude` y `radius_km`.
- En PostgreSQL, el filtro por radio usa PostGIS con `ST_DWithin` y `ST_Distance`, y la respuesta incluye `distance_km`.
- Para tests sqlite se dejó fallback matemático en memoria, evitando convertir sqlite en base objetivo del proyecto.
- Se añadieron tests feature para el filtro por radio y para validación de filtros del listado.
- Se pulieron mensajes Android de error de subida de foto para eliminar redacción interna en primera persona.

### Validaciones ejecutadas

- `php artisan test` en `backend/`: 24 tests, 113 assertions, todo pasando.
- `./gradlew :app:assembleDebug` en `android/`: `BUILD SUCCESSFUL`.
- `bash -n scripts/start_mobile_stack.sh`: correcto.
- `bash -n scripts/install_debug_apk.sh`: correcto.

### Pendiente inmediato

- Sigue pendiente la revalidación física del APK actual en teléfono real: login, mapa, búsqueda, GPS, cámara, galería, subida de foto, creación de reporte y observación.
- Si no hay móvil, el siguiente trabajo útil es preparar memoria/capturas y revisar consistencia de entrega, no abrir funcionalidades grandes.

## 2026-04-24 16:53 CEST

### Revisión integral del estado del proyecto

- Se revisó el contexto obligatorio y el contexto específico del proyecto.
- Se contrastó la documentación con el árbol real de `backend/`, `android/`, `analytics/`, `compose.yaml` y `scripts/`.
- Se revisaron rutas API/web, modelos, migraciones, controladores, requests, tests backend, pantallas Android, cliente API Android, ViewModel, README Android y scripts operativos.
- Se confirmó que `Plantaria` ya tiene un MVP móvil-backend-panel avanzado, no solo una estructura inicial.
- Se dejó documentado que el cuello de botella actual es la revalidación física Android con foto/cámara/GPS/subida tras los últimos arreglos.

### Juicio técnico registrado

- Acierto principal: el alcance está bien enfocado para DAM porque prioriza Android nativo, Laravel, PostGIS, mapa y moderación, sin intentar cerrar iOS y web pública completa a la vez.
- Acierto de dominio: `PlantRecord` y `Observation` representan bien la metáfora de ficha + historial temporal.
- Acierto operativo: los scripts de arranque móvil y la URL API editable reducen fricción real para emulador, USB y móvil físico.
- Riesgo principal: la prueba física final aún no está cerrada para creación de reporte, galería, cámara y observación con foto.
- Riesgo técnico: PostGIS está activado, pero todavía no se explota con columnas espaciales ni consultas por radio/distancia.
- Riesgo documental: `backend/README.md` sigue siendo el genérico de Laravel y conviene sustituirlo antes de entrega.

### Validaciones ejecutadas

- `php artisan test` en `backend/`: 16 tests, 88 assertions, todo pasando.
- `./gradlew :app:assembleDebug` en `android/`: `BUILD SUCCESSFUL`.
- `bash -n scripts/start_mobile_stack.sh`: correcto.
- `bash -n scripts/install_debug_apk.sh`: correcto.

### Documentación actualizada

- `ContextoGeneral.md` actualizado con corte vigente, juicio de estado, validaciones y riesgos.
- `ContextoEspecifico.md` actualizado con revisión por módulos, aciertos, deuda y juicio técnico.
- `DudasYDecisiones.md` actualizado con prioridad de estabilización, estado real de PostGIS y nuevas dudas abiertas.
- `EntornoYVersiones.md` actualizado con revalidación local y limitaciones técnicas observadas.

### Recomendación para la siguiente sesión

- Si hay móvil físico: arrancar stack, instalar APK, configurar URL API y validar login, mapa, búsqueda, GPS, cámara, galería, creación de reporte y observación.
- Si no hay móvil físico: limpiar README del backend, preparar material de memoria/capturas y evitar abrir funcionalidades grandes.

## 2026-04-23 19:50 CEST

### Prueba física parcial y correcciones Android

- Se hizo una primera prueba física parcial en Android real por USB con `adb reverse`.
- El login con `plantaria_demo` quedó validado en dispositivo real.
- La navegación básica de mapa y ficha quedó visible en móvil real.
- La prueba detectó que la creación de reportes con foto fallaba antes de llegar a crear el registro.
- Se identificó como causa principal un límite de subida demasiado bajo en PHP (`upload_max_filesize` de `2M`) para fotos reales de móvil.
- Se actualizó `scripts/start_mobile_stack.sh` para arrancar Laravel con límites más altos de subida y memoria en la prueba móvil.
- Se amplió la validación backend de subida de fotos a `20 MB`.
- Android ahora prepara/comprime imágenes antes de subirlas para tolerar mejor fotos reales de cámara o galería.

### Replanteamiento de UX del mapa

- La pantalla `Mapa` se reestructuró para separar búsqueda de registros por planta/ID de la acción de mover el mapa por zona o coordenadas.
- Se eliminó el preview automático del primer registro.
- El preview de pin ahora es más compacto, cerrable y no se superpone con botones flotantes.
- La ubicación del usuario y el foco de búsqueda pasan a tener iconografía distinta de la de los registros.

### Validaciones ejecutadas

- `php artisan test` en `backend/`: 16 tests, 88 assertions, todo pasando.
- `./gradlew :app:assembleDebug` en `android/`: `BUILD SUCCESSFUL`.

### Pendiente inmediato

- reinstalar la APK reconstruida en el teléfono;
- repetir creación de reporte con galería y con cámara;
- repetir creación de observación;
- cerrar la validación física de la nueva UX del mapa.

## 2026-04-23 19:10 CEST

### Edición avanzada de registros en panel web

- Se amplió la lista web de moderación para permitir filtro por estado y búsqueda por ID público o nombre.
- Se añadió edición avanzada del registro desde la ficha web con campos de nombre, estado, condición, foto principal, descripción y coordenadas.
- La edición avanzada quedó restringida a `ADMIN`; `MOD` mantiene el flujo de verificar o rechazar sin permisos de edición total.
- Se añadió el evento `record_updated` para dejar rastro de ediciones administrativas sobre registros.

### Validaciones ejecutadas

- `php artisan test` en `backend/`: 16 tests, 88 assertions, todo pasando.

## 2026-04-23 16:55 CEST

### Scripts de apoyo para prueba móvil

- Se añadió `scripts/start_mobile_stack.sh` para arrancar PostgreSQL/PostGIS, ejecutar migraciones + seed, asegurar `storage:link` y servir Laravel para el móvil.
- Se añadió `scripts/install_debug_apk.sh` para compilar e instalar el APK debug por `adb` con una sola orden.
- Se actualizó `android/README.md` para reflejar la configuración de estilo del mapa y estos nuevos scripts.

### Validaciones ejecutadas

- `bash -n scripts/start_mobile_stack.sh`: correcto.
- `bash -n scripts/install_debug_apk.sh`: correcto.

## 2026-04-23 16:53 CEST

### Estrategia de tiles y configuración del estilo

- Se movió la URL del estilo del mapa Android a `PLANTARIA_MAP_STYLE_URL` en `app/build.gradle.kts`.
- El valor por defecto sigue siendo `https://demotiles.maplibre.org/style.json` para desarrollo.
- Se dejó cerrada en el contexto la estrategia de no depender ni del estilo demo ni de `tile.openstreetmap.org` para producción.
- Se documentó una salida compatible tanto con proveedor vectorial hospedado como con hosting propio futuro.

### Validaciones ejecutadas

- `./gradlew :app:assembleDebug` en `android/`: `BUILD SUCCESSFUL`.

## 2026-04-23 16:48 CEST

### Pulido de estados Android para demo

- Se mejoró la pantalla de acceso con una ayuda rápida para configurar la URL API según emulador, USB o Wi-Fi.
- Los mensajes de éxito y error pasan a mostrarse como tarjetas más visibles en Android.
- La pantalla `Acciones` ahora da feedback más claro sobre foto pendiente/lista y sobre operaciones en curso.
- La pantalla `Usuario` muestra un estado vacío más útil cuando todavía no hay registros visibles desde la API.

### Validaciones ejecutadas

- `./gradlew :app:assembleDebug` en `android/`: `BUILD SUCCESSFUL`.
- La primera ejecución de Gradle dentro del sandbox volvió a fallar por el lock en `~/.gradle`; se reejecutó fuera del sandbox con permiso.

## 2026-04-23 16:44 CEST

### Analítica visual en el panel web

- Se amplió el dashboard web `/admin` para mostrar analítica visual directamente en Blade.
- El panel ahora enseña actividad diaria de 14 días, actividad por hora, top búsquedas y creadores destacados.
- Se mantuvo la implementación sin librerías JS de gráficas para simplificar la demo y la estabilidad del panel.
- Se reforzó el test de panel admin para comprobar que el dashboard renderiza la nueva capa analítica.

### Validaciones ejecutadas

- `php artisan test` en `backend/`: 13 tests, 72 assertions, todo pasando.

## 2026-04-23 16:33 CEST

### Buscador de mapa con geocodificación

- Se añadió en Laravel el endpoint `/api/geocoding/search` como proxy a Nominatim con caché para búsquedas de lugar.
- Se normalizó la respuesta de geocodificación a `display_name`, `latitude`, `longitude`, `type` y `category`.
- Se añadió test feature para validar geocodificación, caché y validación de parámetros.
- Android ahora muestra sugerencias de lugar en la pantalla `Mapa`.
- El mapa puede recentrarse sobre una sugerencia elegida o sobre coordenadas escritas manualmente.
- Se añadió soporte básico para consultas combinadas del tipo `planta en lugar`, separando filtro textual de registros y foco de mapa.
- Se mantuvo pendiente la validación fuerte en móvil físico; esta sesión deja el código listo para hacer esa prueba.

### Validaciones ejecutadas

- `php artisan test` en `backend/`: 13 tests, 70 assertions, todo pasando.
- `./gradlew :app:assembleDebug` en `android/`: `BUILD SUCCESSFUL`.
- La primera ejecución de Gradle dentro del sandbox falló por no poder escribir locks en `~/.gradle`; se repitió fuera del sandbox con permiso para completar la validación real.

## 2026-04-22 17:10 CEST

### Revisión de coherencia documental

- Se contrastó `AGENTS.md` y los archivos de `Contexto/` contra el árbol real del workspace.
- Se confirmó que el estado correcto es el descrito por `ContextoGeneral.md`: ya existen `backend/`, `android/`, `analytics/` y `compose.yaml`.
- Se corrigió `AGENTS.md`, que todavía arrastraba la nota histórica de que no había código fuente.
- Se actualizó `Contexto/Contexto.md` para aclarar que la carpeta vacía fue solo el estado inicial.
- Se actualizó `ContextoGeneral.md` con fecha de corte 2026-04-22 y la revalidación técnica.
- Se añadió en `DudasYDecisiones.md` la decisión cerrada sobre la corrección del estado documental.

### Validaciones ejecutadas

- `php artisan test` en `backend/`: 6 tests, 25 assertions, todo pasando.
- `./gradlew :app:assembleDebug` en `android/`: `BUILD SUCCESSFUL`.
- La primera ejecución de Gradle dentro del sandbox falló por no poder escribir en `~/.gradle`; se reejecutó fuera del sandbox con permiso porque el wrapper necesita crear locks en la caché global de Gradle.
- Antes de inicializar Git, `git status` no se pudo usar porque `/home/aviddrianimachie/CEAC/Proyecto` todavía no tenía repositorio.

### Regla nueva de contexto

- Se añadió al procedimiento que las nuevas entradas de contexto, decisiones y sesiones lleven fecha y hora local explícita.
- Formato operativo acordado: `YYYY-MM-DD HH:MM CEST/CET`.

### Inicialización Git

- Fecha: 2026-04-22 17:12 CEST.
- Se añadió `.gitignore` raíz con exclusiones para `.codex`, `.env`, `backend/vendor/`, `backend/node_modules/`, artefactos Android/Gradle, caches Python y salidas generadas.
- Se ejecutó `git init` en la raíz del workspace.
- Se renombró la rama inicial a `main`; el primer intento dentro del sandbox falló al crear `.git/HEAD.lock`, y se repitió con permiso fuera del sandbox.
- Se verificó `git branch --show-current`: `main`.
- A las 2026-04-22 17:16 CEST se configuró la identidad Git local como `dlimachii <dlimachi@icloud.com>`.
- Antes de preparar el commit inicial se revisaron candidatos: 142 archivos versionables, sin `.env`, `vendor/`, APK, sqlite local ni caches según `git check-ignore`.
- A las 2026-04-22 17:18 CEST se creó el commit inicial `6b07fce` con mensaje `Initial Plantaria baseline`.

### Preparación de GitHub por SSH

- Fecha: 2026-04-22 17:23 CEST.
- Se comprobó que no había `gh`, remoto Git ni clave SSH visible configurada.
- Se generó una clave SSH ED25519 en `~/.ssh/id_ed25519` con correo `dlimachi@icloud.com`.
- Fingerprint de la clave pública: `SHA256:2yuV33Jk6tvBm9eKjUJJ+zacTNU16XsvM/NKv6eZrNM`.
- A las 2026-04-22 17:30 CEST se verificó autenticación SSH con GitHub: `Hi dlimachii! You've successfully authenticated`.
- Se configuró `origin` como `git@github.com:dlimachii/Plantaria.git`.
- Se ejecutó `git push -u origin main`; la rama local `main` queda siguiendo a `origin/main`.

### Ficha de registro, fotos y validaciones Android

- Fecha: 2026-04-22 17:43 CEST; validado 2026-04-22 18:16 CEST.
- Se amplió el backend para incluir `primary_photo_url` y `photo_url` en registros y observaciones.
- Se añadió test backend para verificar URLs públicas de fotos en detalle de registro.
- Android añade modelo de observaciones en detalle y endpoint cliente `GET /api/records/{publicId}`.
- La preview del mapa abre una ficha completa con foto principal, nombres, estado, autor, coordenadas, fechas y observaciones.
- Se añadió componente `RemotePlantariaImage` para cargar imágenes remotas sin introducir dependencias externas.
- Se muestran fotos reales en preview, ficha y observaciones.
- Se añadieron validaciones por campo en login/registro, creación de reporte y creación de observación.
- Los formularios de reporte/observación limpian campos principales tras éxito.
- Validaciones ejecutadas: `php artisan test` pasa con 6 tests y 32 assertions; `./gradlew :app:assembleDebug` pasa con `BUILD SUCCESSFUL`.

### Ubicación real en pantalla de mapa

- Fecha: 2026-04-22 18:30 CEST.
- La pantalla de mapa intenta usar ubicación real del usuario si el permiso ya está concedido.
- Se añadió botón `Mi ubicación` para solicitar permisos de ubicación y centrar el mapa manualmente.
- Se añade un marcador `Tu ubicación` junto a los marcadores de registros.
- Si no hay proveedor o última ubicación disponible, la UI muestra un estado de error controlado y mantiene el mapa funcional.
- Validaciones ejecutadas: `./gradlew :app:assembleDebug` pasa con `BUILD SUCCESSFUL`; `php artisan test` pasa con 6 tests y 32 assertions.

### Flujo ficha a observación

- Fecha: 2026-04-22 18:35 CEST.
- Se añadió botón `Añadir observación` en la ficha completa de registro del mapa.
- Al pulsarlo, la app navega a `Acciones` y prellena el campo `ID del registro`.
- Se añadió estado en `PlantariaViewModel` para transportar el ID y versionar el prellenado incluso si se repite el mismo registro.
- El formulario mantiene el ID editable y muestra estado local indicando el registro seleccionado.
- Validaciones ejecutadas: `./gradlew :app:assembleDebug` pasa con `BUILD SUCCESSFUL`; `php artisan test` pasa con 6 tests y 32 assertions.

### Panel web mínimo de moderación/admin

- Fecha: 2026-04-22 18:45 CEST.
- Se añadieron rutas web `/admin`, `/admin/login`, `/admin/moderation/pending` y `/admin/moderation/records/{publicId}`.
- Se implementó login web con handle o email, restringido a roles `MOD` y `ADMIN`.
- Se creó dashboard con métricas básicas de registros, usuarios, flags y eventos recientes.
- Se creó cola de registros pendientes con foto, autor, ubicación y acceso a revisión.
- Se creó detalle de registro con foto, datos, observaciones y formularios de verificar o rechazar.
- Se añadieron tests de panel admin: login, bloqueo de usuario normal y verificación web de registro.
- Validaciones ejecutadas: `php artisan test` pasa con 9 tests y 45 assertions; `./gradlew :app:assembleDebug` pasa con `BUILD SUCCESSFUL`.

### Panel web de flags y usuarios

- Fecha: 2026-04-22 19:07 CEST.
- Se añadieron rutas web `/admin/flags` y `/admin/flags/{uid}` para listar denuncias, filtrar por estado y actualizar estado desde el panel.
- Se añadieron rutas web `/admin/users`, `/admin/users/{handle}` y actualización de usuario para listar, filtrar y editar usuarios desde administración.
- La navegación del panel muestra `Flags` a `MOD`/`ADMIN` y `Usuarios` solo a `ADMIN`.
- Se añadieron tests de panel admin para actualizar flags desde moderación y editar usuarios desde administración.
- Validaciones ejecutadas: `php artisan test` pasa con 11 tests y 56 assertions; `./gradlew :app:assembleDebug` pasa con `BUILD SUCCESSFUL`.

## 2026-04-21

### Recuperación de instalación Android

- Se corrigieron las líneas Android rotas en `~/.bashrc` causadas por un salto de línea dentro del `PATH`.
- Se dejaron configuradas correctamente `ANDROID_HOME`, `ANDROID_SDK_ROOT` y el `PATH` hacia `cmdline-tools`, `platform-tools` y `emulator`.
- Se aceptaron las licencias del Android SDK.
- Se instalaron `platform-tools`, `cmdline-tools;latest`, `platforms;android-36`, `build-tools;36.0.0`, `emulator` y `system-images;android-36;google_apis;x86_64`.
- Se creó el AVD `plantaria-api36` con dispositivo `pixel_7`.
- Se validaron `sdkmanager`, `adb`, `gradle`, `avdmanager` y `emulator -list-avds`.
- Se eliminó la copia duplicada `cmdline-tools/latest-2` y se protegieron las líneas de `fzf` en `~/.bashrc` para evitar errores al cargar la shell.

### Estado resultante

- La toolchain Android básica ya está disponible en el entorno local.
- El proyecto Android de `Plantaria` ya puede crearse y compilarse con herramientas reales.

### Scaffold Android inicial

- Se creó `android/` como proyecto Android con módulo `app`.
- Se configuró Gradle wrapper 9.3.1 para no depender del Gradle global antiguo instalado por `apt`.
- Se configuró Android Gradle Plugin 9.1.1 y Kotlin/Compose compiler 2.3.10.
- Se añadió Jetpack Compose con Compose BOM 2026.03.00.
- Se creó la navegación inferior base `Mapa / Acciones / Usuario`.
- Se añadió una pantalla de mapa inicial con chinchetas y preview usando datos simulados.
- Se añadió una pantalla de acciones para crear reporte o actualizar registro.
- Se añadió una pantalla de usuario con perfil y reportes simulados.
- Se generaron recursos mínimos, manifest, permisos previstos e icono adaptativo simple.
- Se validó el cliente con `./gradlew :app:assembleDebug`.
- Se generó `android/app/build/outputs/apk/debug/app-debug.apk`.

### Estado resultante Android

- El cliente Android ya existe, compila y tiene conexión inicial con la API Laravel.
- Todavía usa un mapa visual simulado y no sube fotos reales.
- El siguiente paso técnico es integrar mapa real OSM/MapLibre y captura/subida de imágenes.

### Integración Android con API Laravel

- Se añadió capa de modelos Android para `ApiUser`, `AuthResult`, `PlantRecord` y `RecordAuthor`.
- Se añadió `PlantariaApiClient` con `HttpURLConnection` para consumir la API Laravel.
- Se añadió `SessionStore` con DataStore Preferences para persistir token y datos mínimos de usuario.
- Se añadió `PlantariaViewModel` para login, registro, logout, carga de registros y creación básica de reportes.
- Se añadió pantalla de autenticación con modo entrar/registro.
- La pantalla de mapa ahora carga registros reales desde `/api/records` y permite búsqueda textual.
- La pantalla de acciones puede crear un reporte básico contra `/api/records` usando ruta de foto temporal y coordenadas manuales.
- La pantalla de usuario muestra la sesión real y estadísticas de registros cargados.
- Se añadió `android/README.md` con comandos de uso y URL local esperada.
- Se validó de nuevo con `./gradlew :app:assembleDebug`.

### Ajuste para móvil físico

- Se detectó que `10.0.2.2` solo sirve para emulador Android y no para instalación directa en teléfono físico.
- Se hizo editable la URL de API desde la pantalla de acceso y se guarda en DataStore.
- Se mantiene `http://10.0.2.2:8000/api/` como valor por defecto para emulador.
- Se documentó uso en móvil físico por Wi-Fi con IP LAN del PC y posible `netsh interface portproxy` para WSL2.
- Se documentó alternativa por USB con `adb reverse tcp:8000 tcp:8000` y URL `http://127.0.0.1:8000/api/`.

### Fotos reales y observaciones desde Android

- Se añadió endpoint backend `POST /api/uploads/photos` protegido por Sanctum.
- El backend guarda imágenes en el disco público de Laravel y devuelve `path` y `url`.
- Se añadió test `PhotoUploadTest`; la suite backend completa pasa.
- Android usa Photo Picker para seleccionar imágenes desde el móvil.
- Android sube la imagen como multipart antes de crear reportes u observaciones.
- La creación de reporte ya usa la ruta real devuelta por `/api/uploads/photos`.
- La actualización de un registro existente ya crea observaciones reales con foto, nota y coordenadas.
- La build Android volvió a pasar con `./gradlew :app:assembleDebug`.

### Mapa real y datos demo

- Se integró MapLibre Native Android `13.0.2` en el cliente Android.
- La pestaña `Mapa` dejó de usar `Canvas` simulado y ahora renderiza un `MapView` real con estilo `https://demotiles.maplibre.org/style.json`.
- Los registros de `/api/records` aparecen como marcadores geolocalizados en el mapa y al pulsarlos se actualiza la preview inferior.
- Se añadieron registros demo seedables alrededor de Barcelona: Platanero, Lavanda, Romero y Bugambilia.
- Se ejecutó `php artisan db:seed --class=DatabaseSeeder --no-interaction` contra PostgreSQL local y quedaron insertados 4 registros demo.
- Se validó backend con `php artisan test`: 6 tests, 25 assertions.
- Se validó Android con `./gradlew :app:assembleDebug`; el APK debug queda en `android/app/build/outputs/apk/debug/app-debug.apk`.

### Ubicación real en acciones Android

- Se añadieron botones `Usar ubicación actual` en creación de reporte y creación de observación.
- La app solicita permisos `ACCESS_FINE_LOCATION` y `ACCESS_COARSE_LOCATION` en runtime desde Compose.
- Si el dispositivo devuelve ubicación actual se rellenan latitud/longitud automáticamente; si no, intenta usar la última ubicación conocida.
- Las coordenadas manuales se mantienen como fallback.
- Se validó de nuevo con `./gradlew :app:assembleDebug`.

### Captura directa con cámara

- Se añadió `FileProvider` con rutas de cache para fotos tomadas desde la app.
- Se añadieron botones `Hacer foto` en creación de reporte y creación de observación.
- La app solicita permiso `CAMERA` en runtime y usa `ActivityResultContracts.TakePicture`.
- Las fotos capturadas reutilizan el flujo existente de subida a `/api/uploads/photos`.
- Se validó de nuevo con `./gradlew :app:assembleDebug`.

### Cierre de estado para próxima sesión

- Se añadió en `ContextoGeneral.md` una sección `Estado para próxima sesión`.
- Queda indicado que el MVP Android + backend está aproximadamente al 60-65%.
- Queda indicado qué está validado, qué depende del móvil físico y qué conviene hacer después.
- La próxima sesión debe empezar leyendo `Contexto/Contexto.md` y `Contexto/ContextoGeneral.md`; si se sigue trabajando en Android, abrir también `ContextoEspecifico.md` y `EntornoYVersiones.md`.

## 2026-04-20

### Definición inicial de Plantaria

- Se convirtió la idea verbal del usuario en una primera definición funcional del TFC.
- Se fijó el nombre del proyecto: `Plantaria`.
- Se describió el producto como plataforma colaborativa de registros vegetales geolocalizados con trazabilidad temporal y validación comunitaria.

### Decisiones de alcance

- Se recomendó limitar el MVP a Android + backend + base de datos + panel web de moderación.
- Se dejó iOS y la web pública completa como fases posteriores para no sobredimensionar el TFC.

### Decisiones técnicas

- Se propuso `PHP + Laravel` para backend.
- Se propuso `PostgreSQL + PostGIS` para persistencia y consultas geográficas.
- Se propuso `OpenStreetMap + Nominatim` para la parte cartográfica y de geocodificación.
- Se definió el patrón `uid` interno + `handle` público editable para usuarios.
- Se definió un modelo de dominio base con registros geolocalizados, observaciones y denuncias.

### Documentación actualizada

- Se actualizó `ContextoGeneral.md` con resumen real del proyecto.
- Se rellenó `ContextoEspecifico.md` con alcance, roles, flujos y arquitectura.
- Se actualizó `EntornoYVersiones.md` con stack objetivo de arranque.
- Se añadieron decisiones y dudas nuevas en `DudasYDecisiones.md`.

### Redacción funcional

- Se añadió un texto funcional breve de `Plantaria` en `ContextoEspecifico.md` para reutilizarlo como base de memoria, presentación o arranque del desarrollo.

### Desglose de ideas del usuario

- Se desarrolló una especificación funcional más detallada a partir de la cadena larga de ideas del usuario.
- Se dejó indicado qué partes se recogen tal cual, cuáles se simplifican para el MVP y cuáles se mandan a fases futuras.

### Nota de IA futura

- Se documentó en el contexto la estrategia para una futura integración de reconocimiento de plantas mediante API externa.
- Se dejó `Pl@ntNet` como opción preferida y `plant.id / Kindwise` como alternativa secundaria.

### Analítica de uso

- Se añadió una propuesta de analítica y estadísticas de uso para el panel web de administración.
- Se dejó planteado que las métricas básicas saldrán del stack principal y que `Python` solo tendría sentido como apoyo analítico avanzado.

### Python analítico y asistente admin

- Se actualizó la estrategia de analítica para contemplar `Python + pandas` como módulo complementario real del TFC.
- Se dejó anotada una posible implementación futura de asistente de consultas administrativas con `Ollama`.
- Se registró el estado observado del entorno y después quedó disponible también `pip3`.

### Arranque real del código

- Se instaló Composer y se completó la instalación del backend Laravel en `backend/`.
- Se añadió Sanctum y se publicó `routes/api.php`.
- Se implementó un primer modelo de dominio con usuarios, registros, observaciones, flags y eventos.
- Se añadieron enums de dominio para roles, estados y tipos de evento.
- Se creó un conjunto inicial de endpoints de auth, perfil, registros, observaciones y analítica.
- Se añadió un directorio `analytics/` con base para `Python + pandas`.
- Se actualizaron los tests de ejemplo y el backend pasa pruebas básicas.

### Política de base de datos

- Se cerró la decisión de no tratar `sqlite` y `PostgreSQL` como motores equivalentes.
- PostgreSQL/PostGIS queda como base real del proyecto y `sqlite` solo como apoyo para tests o arranque mínimo.

### PostgreSQL real y moderación

- Se añadió `compose.yaml` para levantar PostgreSQL/PostGIS con Docker.
- Se conectó el backend a PostgreSQL real y se ejecutaron las migraciones sobre esa base.
- Se corrigió el orden de migraciones para respetar dependencias entre tablas.
- Se añadieron endpoints y lógica de moderación, flags y administración básica de usuarios.
- Se añadió un seeder de administrador inicial configurable por entorno.
- El backend quedó validado con tests y con esquema real en PostgreSQL.

### Estado del entorno Android

- Se comprobó que en esta máquina todavía no están disponibles `gradle`, `kotlinc`, `sdkmanager` ni `adb`.
- Se dejó constancia de que el siguiente paso grande del cliente móvil exige instalar esa toolchain.

### Instalación Android iniciada y pausada

- Se empezó la instalación manual de toolchain Android en Ubuntu/WSL.
- El proceso quedó interrumpido al añadir mal la variable `PATH` en `~/.bashrc` por un salto de línea dentro del comando `echo`.
- Se dejó documentado en `EntornoYVersiones.md` el punto exacto de parada y el bloque de comandos de recuperación.

## 2026-04-15

### Arranque del sistema de contexto

- Se revisó la carpeta `/home/aviddrianimachie/CEAC/Proyecto`.
- La carpeta estaba vacía salvo el archivo `.codex`.
- Se creó un sistema de archivos `.md` para conservar contexto entre sesiones.
- Se corrigió una inferencia errónea que asociaba este workspace con otro proyecto distinto.
- Se dejó la estructura documental preparada, pero sin contenido técnico inventado.

### Decisión tomada

- El archivo de entrada para futuras sesiones debe ser `ContextoGeneral.md`.
- Los documentos más detallados solo deben abrirse si el general no basta.

### Tarea pendiente implícita

- Cuando haya materiales reales del TFC, habrá que rellenar estos documentos con información verificada.
