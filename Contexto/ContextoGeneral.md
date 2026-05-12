# Contexto general

## Qué es este proyecto

`Plantaria` es un TFC de DAM orientado a una plataforma de registro colaborativo de plantas geolocalizadas.

Idea base:

- un usuario encuentra una planta o flor en el mundo real;
- crea un registro con foto y ubicación;
- ese registro aparece sobre un mapa;
- otros usuarios pueden volver al mismo punto y añadir nuevas observaciones temporales;
- moderadores o administradores validan el nombre común y el científico para convertir un reporte provisional en una ficha fiable.

La metáfora que usa el usuario es la de un "GitHub de plantas", pero funcionalmente el núcleo del sistema es el mapa, la trazabilidad temporal y la moderación.

## Estado actual

- revisión de coherencia realizada el 2026-04-22 17:10 CEST: el árbol real confirma que ya hay código fuente y que el contexto técnico reciente es el correcto;
- ya existe código fuente inicial en este workspace;
- el backend Laravel del proyecto ha sido generado y adaptado al dominio de `Plantaria`;
- el backend ya está conectado a una base PostgreSQL/PostGIS real levantada con Docker Compose;
- existe una cuenta admin seedable desde entorno;
- existe una cuenta demo y registros demo seedables alrededor de Barcelona;
- existe también un directorio `analytics/` preparado para explotación de datos con `Python`;
- la toolchain Android ya está instalada en el entorno local y existe un AVD `plantaria-api36`;
- existe un primer cliente Android en `android/` con Kotlin, Jetpack Compose, navegación base, mapa real MapLibre/OSM y conexión con la API;
- repositorio Git inicializado el 2026-04-22 17:12 CEST en la raíz del workspace, rama `main`, con `.gitignore` raíz;
- revisión integral del 2026-04-24 16:53 CEST: el proyecto ya no está en fase de scaffold, sino en cierre de MVP Android + backend + panel, con pruebas automatizadas pasando y pendiente principal en validación física final;
- cierre documental y geoespacial del 2026-04-24 17:07 CEST: se añadió README raíz, se sustituyó el README genérico del backend y `/api/records` acepta filtro por radio con PostGIS en PostgreSQL;
- preparación de entrega del 2026-04-24 17:26 CEST: se añadieron guía de demo, checklist de validación móvil y memoria técnica base; se validó manualmente el filtro por radio contra PostgreSQL/PostGIS local;
- validación operativa del 2026-04-24 17:29 CEST: se añadió `scripts/validate_project.sh` para ejecutar tests backend, build Android, sintaxis de scripts y smoke PostGIS;
- cierre demo del 2026-04-24 17:34 CEST: el seeder genera imágenes demo PNG y se añadió `docs/API.md`; Composer metadata/lock quedan sincronizados con Plantaria;
- cierre de seguridad API del 2026-04-24 17:41 CEST: middleware de cuenta activa y tests de autorización de API admin;
- backup OneDrive del 2026-04-24 17:44 CEST: se añadió `scripts/package_for_onedrive.sh`, documentación de backup y se creó un paquete real en OneDrive CEAC con fuente, bundle Git, APK, manifest y checksums;
- revalidación local del 2026-04-27 16:23 CEST: `./scripts/validate_project.sh` terminó correctamente con tests backend, build Android y smoke real contra PostgreSQL/PostGIS; no había teléfono conectado por ADB;
- corrección móvil del 2026-04-27 17:31 CEST: se corrigió un crash real de Android en MapLibre causado por pasar drawables XML a `IconFactory.fromResource`; el APK corregido fue instalado y relanzado en el móvil sin `FATAL EXCEPTION`;
- ajustes móviles del 2026-04-27 18:19 CEST: login, pines, ubicación y búsqueda `Lavanda` validados por usuario; se corrigió creación de reporte sin `plant_condition`; Android recibió panel de mapa más compacto, lista de resultados con distancia y ficha completa tipo perfil;
- ajuste de UX del 2026-04-27 18:25 CEST: el mapa vuelve a tener un único buscador, solo por nombres de planta; se retiró el campo visible de zona/coordenadas y el backend dejó de buscar por `public_id` en `q`;
- pulido de UX del 2026-04-27 18:43 CEST: se quitó `Resumen del mapa`, los resultados se integraron dentro del bloque del buscador con scroll interno y se instaló el APK en móvil físico;
- ajuste móvil del 2026-04-27 19:18 CEST: al entrar al mapa se usa ubicación cacheada en vez de pedir ubicación actual lenta; la foto original queda como portada fija y el historial excluye la observación inicial automática;
- pulido visual del 2026-04-27 20:03 CEST: fotos grandes en ficha/commits pasan a marco 4:3 completo y los commits muestran fecha, foto, nota y metadatos sin exponer `source_type=update`;
- pulido visual del 2026-04-27 20:14 CEST: metadatos de cada commit pasan a columna etiqueta/valor para evitar que se estiren en horizontal;
- pulido visual del 2026-04-27 20:23 CEST: el bloque de datos del reporte en la ficha completa usa filas etiqueta/valor y descripción separada;
- avance panel admin del 2026-04-28 16:10 CEST: los flags web muestran contexto del objetivo denunciado, filtros por tipo/búsqueda y la ficha de moderación enseña flags relacionados del registro y sus observaciones;
- avance demo/login del 2026-04-28 16:48 CEST: el seeder crea cuentas por rol (`plantaria_user`, `plantaria_mod`, `plantaria_admin`), Android oculta la URL técnica del login, añade splash/logo animado y mueve cierre de sesión a menú de perfil;
- avance actividad de usuario del 2026-04-28 17:24 CEST: se añade `/api/me/activity` y la pestaña `Usuario` de Android deja de listar registros globales para mostrar solo acciones propias recientes de la cuenta;
- avance panel/analítica del 2026-04-28 17:37 CEST: el panel web integra snapshot Python+pandas desde CSV exportados por Laravel y prepara asistente local con Ollama para consultas administrativas;
- ajuste asistente admin del 2026-04-30 16:20 CEST: `/admin/assistant` resuelve primero consultas directas seguras de BBDD para preguntas conocidas, como top de autores de observaciones y plantas verificadas sin nombre científico; pandas queda como contexto extra para preguntas abiertas.
- analítica local del 2026-04-30 16:20 CEST: se preparó `analytics/.venv`, `.env` local apunta a ese Python y `admin_dashboard.json` quedó generado contra PostgreSQL/PostGIS local.
- perfilado de rendimiento del 2026-04-30 16:45 CEST: se añadió `scripts/profile_app_performance.sh` para medir endpoints críticos de la app, tamaño del APK y snapshot básico ADB si hay móvil conectado.
- documentación TFG del 2026-05-04 16:40 CEST: se generó `DocumentoTFG/Plantaria_TFG_DAM.docx` a partir de la plantilla `DocumentoTFG/TFG DAM_DAW.docx`, con índice automático, estructura académica completa y pendientes explícitos para información externa al código.
- ajuste documental del 2026-05-04 16:49 CEST: el DOCX incorpora comparativa de plataformas reales del sector, enfoque TFG/MVP no económico, código público/libre y aclaración de valoración económica como coste de ejecución.
- ajuste documental del 2026-05-04 16:54 CEST: se añadió tabla de costes de ejecución para entrega local, despliegue público pequeño, MVP comunitario con mapas estables y escala mayor.
- sincronización GitHub del 2026-05-04 20:08 CEST: se consolidó el trabajo acumulado en Git, se actualizó `main` por avance rápido y se subieron `main` y `mobile-beta-20260427` a `origin`; se excluyó de Git el temporal de Word `~$*.docx`.
- limpieza de repositorio del 2026-05-04 20:29 CEST: se confirma que `DocumentoTFG/Plantaria_TFG_DAM.docx` debe seguir versionado como referencia de estructura y contexto del TFG; la limpieza se limita a artefactos generados/locales como dependencias instaladas, builds, cachés, logs, sqlite local, temporales e IDE.
- el trabajo actual debe centrarse en estabilización, validación física y documentación de entrega.

## Objetivo actual

Cerrar y estabilizar un MVP realista y defendible para el TFC:

- cliente principal Android en Kotlin;
- backend API en PHP con Laravel;
- base de datos PostgreSQL con PostGIS;
- panel web para moderación y administración;
- módulo analítico auxiliar con Python;
- dejar iOS y una web pública completa como fases posteriores, no como requisito del primer MVP;
- priorizar prueba física, documentación y coherencia de entrega antes que abrir más alcance.

## Decisiones globales ya tomadas

- El proyecto se llama `Plantaria`.
- El MVP inicial debe priorizar una sola app cliente real, no Android + iOS + web completa a la vez.
- La base de datos elegida para el arranque es PostgreSQL + PostGIS por la parte geoespacial.
- Se usarán dos identificadores de usuario:
  - `uid` interno e inmutable;
  - `handle` público, único y editable.
- La app debe diferenciar tres roles: `USER`, `MOD`, `ADMIN`.

## Siguiente objetivo importante

Completar la integración funcional Android:

- validar en móvil físico el flujo completo de mapa, ficha, fotos, cámara, GPS y API local;
- pulir estados visuales y rendimiento real del mapa en hardware físico;
- validar en móvil físico el buscador de mapa con geocodificación, coordenadas y recentrado;
- si hace falta, aprovechar el panel web ya ampliado para preparar datos demo y moderación antes de la prueba física;
- mantener documentado el estilo demo de MapLibre para el TFC local; si Plantaria se publica como producto real, sustituirlo por proveedor final de tiles o hosting propio.

## Estado para próxima sesión

Fecha de corte vigente: 2026-04-30 17:08 CEST.

Estado aproximado del MVP Android + backend + panel: 94-96%.

Lectura honesta del estado:

- el núcleo del producto ya existe de punta a punta: usuario, sesión, mapa, registros, fotos, observaciones, moderación, flags, usuarios, analítica y datos demo;
- el móvil es ahora el cuello de botella real, no el backend: falta repetir en dispositivo físico el flujo completo tras los arreglos de compresión/subida de fotos;
- el panel web está suficientemente fuerte para defender moderación y administración en un TFC;
- el backend está razonablemente cubierto por tests feature para el alcance actual, pero esos tests corren en sqlite y no sustituyen una pasada real completa contra PostgreSQL/PostGIS;
- `analytics/` existe como módulo Python defendible, con snapshot pandas generado y asistente admin preparado, pero sigue siendo auxiliar y no parte del flujo principal de producto;
- el proyecto está en un punto bueno para estabilizar y documentar, no para abrir grandes funcionalidades nuevas.

Ya está hecho y validado:

- backend Laravel con Sanctum, PostgreSQL/PostGIS, usuarios, registros, observaciones, flags, moderación básica, analítica API y subida de fotos;
- tests backend pasando con `php artisan test`: 38 tests, 176 assertions;
- tokens de cuentas no activas bloqueados por middleware en rutas autenticadas;
- tests de autorización para impedir acceso de `USER`/`MOD` a rutas API reservadas a `ADMIN`;
- `/api/records` acepta filtro por radio con `latitude`, `longitude` y `radius_km`, usando PostGIS (`ST_DWithin`/`ST_Distance`) en PostgreSQL y devolviendo `distance_km`;
- proyecto Android compilable con Kotlin + Jetpack Compose;
- login, registro, token persistido con DataStore y URL de API editable para emulador/móvil físico;
- mapa real MapLibre Native Android con estilo demo documentado `https://demotiles.maplibre.org/style.json`;
- registros reales de `/api/records` pintados como marcadores en el mapa;
- selector de imagen con Photo Picker;
- captura directa con cámara usando `TakePicture` y `FileProvider`;
- subida real de fotos a `/api/uploads/photos`;
- creación de reportes y observaciones desde Android;
- botón para rellenar coordenadas con ubicación actual o última conocida;
- mapa centrable en la ubicación real del usuario si ya hay permiso o al pulsar el botón `Mi ubicación`;
- marcador de ubicación del usuario en el mapa;
- ficha completa de registro al tocar la preview del mapa, cargando `/api/records/{publicId}`;
- desde la ficha se puede saltar a `Acciones` con el ID del registro prellenado para añadir observación;
- fotos reales visibles en preview, ficha y observaciones mediante URLs públicas del backend;
- validaciones por campo en autenticación, creación de reporte y creación de observación;
- buscador de mapa ampliado el 2026-04-23 16:33 CEST con geocodificación de lugares vía Nominatim, sugerencias y recentrado por coordenadas o zonas;
- panel web mínimo de administración/moderación en Laravel;
- login web para `MOD`/`ADMIN`, dashboard, cola de pendientes, detalle y verificación/rechazo de registros;
- panel web ampliado el 2026-04-22 19:07 CEST con gestión de flags para `MOD`/`ADMIN`;
- panel web ampliado el 2026-04-22 19:07 CEST con listado, filtros y edición básica de usuarios para `ADMIN`;
- dashboard web ampliado el 2026-04-23 16:44 CEST con analítica visual de actividad, horas pico, top búsquedas y creadores destacados;
- panel web ampliado el 2026-04-23 19:10 CEST con búsqueda/filtro de registros y edición avanzada de registros para `ADMIN` desde la ficha web;
- panel web ampliado el 2026-04-28 16:10 CEST con filtros de flags por tipo/búsqueda, enlaces al objetivo denunciado y bloque de flags relacionados dentro de la ficha de moderación;
- Android pulido el 2026-04-23 16:48 CEST con ayudas rápidas de conexión y estados de carga/error más visibles para demo;
- Android pulido el 2026-04-28 16:48 CEST con splash/logo animado, login sin campo visible de URL y cierre de sesión desde menú de perfil;
- Android/backend pulidos el 2026-04-28 17:24 CEST con actividad propia de usuario en perfil: reportes creados, commits/observaciones propios, flags enviados y acciones de moderación/admin registradas;
- panel web ampliado el 2026-04-28 17:37 CEST con bloque `Analitica Python + pandas`, comando `plantaria:analytics:build`, export CSV a `storage/app/analytics/input`, JSON calculado en `storage/app/analytics/output/admin_dashboard.json` y página `/admin/assistant` para Ollama;
- asistente admin ajustado el 2026-04-30 16:20 CEST para contestar consultas directas de BBDD sin requerir snapshot pandas cuando reconoce preguntas acotadas del dominio Plantaria;
- estrategia de tiles cerrada el 2026-04-23 16:53 CEST: estilo configurable en build, sin depender de `demotiles` ni de `tile.openstreetmap.org` para producción, y con salida futura compatible con hosting vectorial propio;
- scripts de apoyo añadidos el 2026-04-23 16:55 CEST para levantar stack móvil e instalar el APK debug con menos pasos manuales;
- script `scripts/validate_project.sh` añadido el 2026-04-24 17:29 CEST para validación integral repetible;
- script `scripts/profile_app_performance.sh` añadido el 2026-04-30 16:45 CEST para obtener una línea base rápida de rendimiento API/APK/ADB antes de optimizar;
- Android reajustado el 2026-04-23 19:50 CEST tras prueba física parcial: búsqueda de registros separada del foco por zona, preview compacto y cerrable, marcador de ubicación claramente distinto y flujo de fotos endurecido para móvil real;
- documentación visible actualizada el 2026-04-24 17:07 CEST con `README.md` raíz y `backend/README.md` específico de Plantaria;
- documentación de entrega añadida el 2026-04-24 17:26 CEST en `docs/GUIA_DEMO.md`, `docs/CHECKLIST_VALIDACION_MOVIL.md` y `docs/MEMORIA_TFC.md`;
- referencia API añadida el 2026-04-24 17:34 CEST en `docs/API.md`;
- cuentas seedables por rol añadidas el 2026-04-28 16:48 CEST: `plantaria_user`, `plantaria_mod` y `plantaria_admin`, además de `plantaria_demo`;
- documentación de backup OneDrive añadida el 2026-04-24 17:44 CEST en `docs/BACKUP_ONEDRIVE.md`;
- imágenes demo PNG generadas automáticamente por `DatabaseSeeder`;
- datos demo seedables y cargados en PostgreSQL local alrededor de Barcelona;
- APK debug generado en `android/app/build/outputs/apk/debug/app-debug.apk`.

Revalidación del 2026-04-24 17:26 CEST:

- `php artisan test` en `backend/`: 24 tests, 113 assertions, todo pasando;
- `./gradlew :app:assembleDebug` en `android/`: `BUILD SUCCESSFUL`;
- `bash -n scripts/start_mobile_stack.sh`: correcto;
- `bash -n scripts/install_debug_apk.sh`: correcto.
- PostgreSQL/PostGIS local: `docker compose ps` muestra `plantaria-postgis` healthy; `php artisan migrate --seed --no-interaction` correcto; endpoint `/api/records?latitude=41.3851&longitude=2.1734&radius_km=8&limit=10` probado contra Laravel local y devuelve registros con `distance_km`.
- `./scripts/validate_project.sh`: correcto, incluyendo backend, Android, sintaxis de scripts y smoke PostGIS.
- `composer validate --no-check-publish`: correcto tras sincronizar `composer.lock`.
- `./scripts/package_for_onedrive.sh`: paquete creado en `/ruta/a/PlantariaBackups/plantaria-backup-20260424-174446`; `sha256sum -c SHA256SUMS` correcto.

Revalidación del 2026-04-27 16:23 CEST:

- `./scripts/validate_project.sh`: correcto, incluyendo sintaxis de scripts, `php artisan test`, `./gradlew :app:assembleDebug` y smoke PostGIS real;
- `php artisan test`: 24 tests, 113 assertions, todo pasando;
- `./gradlew :app:assembleDebug`: `BUILD SUCCESSFUL`;
- APK debug disponible en `android/app/build/outputs/apk/debug/app-debug.apk`, aproximadamente 78 MB;
- `adb devices`: sin teléfono conectado en ese momento.

Revalidación móvil del 2026-04-27 17:31 CEST:

- `adb devices` desde PowerShell mostró el teléfono físico autorizado;
- `adb reverse tcp:8000 tcp:8000` correcto;
- `adb install -r` correcto tras recompilar el APK;
- crash inicial al abrir corregido en `MapScreen.kt` convirtiendo marcadores XML a bitmap para MapLibre;
- relanzamiento desde ADB sin `FATAL EXCEPTION` y con proceso `com.plantaria.app` vivo;
- pendiente arrancar `./scripts/start_mobile_stack.sh` para probar login/mapa con API local, porque `127.0.0.1:8000` no respondía durante la comprobación.

Revalidación móvil del 2026-04-27 18:19 CEST:

- usuario confirma login real en móvil, carga de pines, marcador de ubicación y búsqueda por `Lavanda`;
- backend corregido para que registros y observaciones usen `plant_condition=unknown` si Android no envía condición;
- `php artisan test`: 25 tests, 118 assertions, todo pasando;
- `./gradlew :app:assembleDebug`: `BUILD SUCCESSFUL`;
- APK actualizado instalado en el teléfono y abierto sin `FATAL EXCEPTION`;
- pendiente que el usuario repita creación de reporte y observación con el APK/backend corregidos.

Revalidación del 2026-04-27 18:25 CEST:

- `GET /api/records?q=` busca solo nombres de planta, no `public_id`;
- `php artisan test --filter=PlantRecordTest`: 5 tests, 32 assertions, todo pasando;
- `./gradlew :app:assembleDebug`: `BUILD SUCCESSFUL`;
- APK instalado en móvil físico con `adb install -r`: `Success`.

Revalidación del 2026-04-27 18:43 CEST:

- `php artisan test`: 26 tests, 122 assertions, todo pasando;
- `./gradlew :app:assembleDebug`: `BUILD SUCCESSFUL`;
- `git diff --check`: correcto;
- APK instalado en móvil físico con `adb install -r`: `Success`.

Revalidación del 2026-04-27 19:18 CEST:

- `./gradlew :app:assembleDebug`: `BUILD SUCCESSFUL`;
- `git diff --check`: correcto;
- APK instalado en móvil físico con `adb install -r`: `Success`.

Revalidación del 2026-04-27 20:03 CEST:

- `./gradlew :app:assembleDebug`: `BUILD SUCCESSFUL`;
- `git diff --check`: correcto;
- APK instalado en móvil físico con `adb install -r`: `Success`.

Revalidación del 2026-04-27 20:14 CEST:

- `./gradlew :app:assembleDebug`: `BUILD SUCCESSFUL`;
- `git diff --check`: correcto;
- APK instalado en móvil físico con `adb install -r`: `Success`.

Revalidación del 2026-04-28 16:10 CEST:

- `php artisan test --filter=AdminPanelTest`: 10 tests, 52 assertions, todo pasando;
- `php artisan test`: 28 tests, 132 assertions, todo pasando;
- `git diff --check`: correcto.

Revalidación del 2026-04-28 16:48 CEST:

- `php artisan test --filter=DatabaseSeederTest`: 2 tests, 9 assertions, todo pasando;
- `php artisan test`: 29 tests, 138 assertions, todo pasando;
- `./gradlew :app:assembleDebug`: `BUILD SUCCESSFUL`;
- `bash -n scripts/install_debug_apk.sh`: correcto;
- `bash -n scripts/start_mobile_stack.sh`: correcto;
- `git diff --check`: correcto;
- `php artisan db:seed --class=DatabaseSeeder --no-interaction`: correcto contra PostgreSQL local tras levantar/verificar `plantaria-postgis`;
- verificación PostgreSQL local: existen `plantaria_admin` (`admin`), `plantaria_mod` (`mod`), `plantaria_user` (`user`) y `plantaria_demo` (`user`), todos activos;
- `adb devices`: sin dispositivos conectados tras arrancar ADB.

Revalidación del 2026-04-28 17:24 CEST:

- `php artisan test --filter=UserActivityTest`: 4 tests, 19 assertions, todo pasando;
- `php artisan test`: 33 tests, 157 assertions, todo pasando;
- `./gradlew :app:assembleDebug`: `BUILD SUCCESSFUL`;
- `git diff --check`: correcto.

Revalidación del 2026-04-28 17:37 CEST:

- `php artisan test --filter=AdminPanelTest`: 14 tests, 63 assertions, todo pasando;
- `php artisan test`: 37 tests, 168 assertions, todo pasando;
- `python3 -m py_compile analytics/build_admin_analytics.py`: correcto;
- `php artisan plantaria:analytics:build --skip-python`: validado dentro de test sqlite; en entorno local directo requiere PostgreSQL activo.

Revalidación del 2026-04-30 16:20 CEST:

- `php artisan test --filter=AdminPanelTest`: 15 tests, 71 assertions, todo pasando;
- `php artisan test`: 38 tests, 176 assertions, todo pasando;
- `php artisan plantaria:analytics:build`: correcto contra PostgreSQL/PostGIS local usando `analytics/.venv`;
- `git diff --check`: correcto.

Revalidación del 2026-04-30 16:45 CEST:

- `PLANTARIA_PROFILE_RUNS=3 PLANTARIA_PROFILE_PORT=8021 ./scripts/profile_app_performance.sh`: correcto contra Laravel temporal y PostgreSQL/PostGIS local;
- resultados base: mapa 16.7 ms, radio PostGIS 66.8 ms, búsqueda `Lavanda` 21.2 ms, ficha completa 24.2 ms, login demo 212.8 ms, actividad usuario 22.6 ms, APK debug 77.9 MiB;
- `bash -n scripts/validate_project.sh && bash -n scripts/profile_app_performance.sh && git diff --check`: correcto.

Primera prueba física parcial del 2026-04-23 19:50 CEST:

- login en Android real validado con `plantaria_demo`;
- mapa, ficha y carga básica de datos visibles en dispositivo;
- la creación de reportes falló durante la prueba inicial por límites de subida demasiado bajos en el servidor y por un flujo Android poco robusto con fotos reales;
- esas dos causas se corrigieron en esta misma sesión, pero la revalidación final en móvil quedó pendiente tras reconstruir la APK.

No se ha podido validar todavía porque falta el móvil físico:

- revalidación final del APK reconstruido y vuelto a compilar el 2026-04-24 16:53 CEST en el teléfono;
- creación de reporte con galería tras el nuevo flujo de compresión/subida;
- creación de reporte con cámara tras el nuevo flujo de compresión/subida;
- creación de observación con foto tras los mismos cambios;
- verificación final de la nueva UX de mapa en hardware físico.

Siguiente paso recomendado si no está el móvil:

- no abrir funcionalidades grandes;
- preparar memoria/capturas y revisar que el relato del TFC coincide con lo implementado;
- si se toca código, limitarlo a correcciones pequeñas y tests.

Siguiente paso recomendado cuando esté el móvil:

- arrancar PostGIS y Laravel;
- reinstalar APK debug reconstruido;
- configurar URL de API según Wi-Fi o `adb reverse`;
- probar login, mapa, búsqueda de registros, foco de mapa por zona/coordenadas, cámara, GPS, subida de foto, creación de reporte y observación.

## Qué no hay que volver a inferir

- El proyecto no es una app genérica de jardinería; es una plataforma de reportes y seguimiento geolocalizado.
- El mapa y la ubicación son parte central del dominio, no una función secundaria.
- La validación comunitaria forma parte del producto desde el principio.
- No conviene meter iOS nativo en el MVP inicial salvo que más adelante el usuario lo priorice de forma explícita.

## Qué leer primero en futuras sesiones

Si una futura sesión solo necesita visión rápida, este archivo debería bastar.

Abrir además:

- `ContextoEspecifico.md` para dominio, arquitectura y flujos;
- `EntornoYVersiones.md` para stack y versiones objetivo;
- `DudasYDecisiones.md` para decisiones cerradas y dudas abiertas;
- `RegistroDeSesiones.md` para saber qué se hizo recientemente.

## Riesgos o puntos a tener en cuenta

- El cliente Android compila, consume la API para auth/registros, captura o selecciona fotos reales, permite crear observaciones, rellena coordenadas desde ubicación del dispositivo y muestra registros en mapa real MapLibre.
- El mapa usa por ahora el estilo público demo de MapLibre, útil para desarrollo; para producción habría que cerrar proveedor/tiles con límites claros.
- PostGIS ya se usa en una consulta real por radio desde `/api/records`, aunque el modelo todavía guarda coordenadas como decimales y no incorpora columnas espaciales persistentes `geometry`/`geography`.
- La documentación visible ya incluye README raíz y README específico del backend; mantenerla sincronizada si cambian rutas, comandos o alcance.
- Android no tiene todavía tests instrumentados ni pruebas UI automáticas; la validación móvil real sigue siendo manual.
- Git ya está inicializado en la raíz del workspace; identidad local configurada el 2026-04-22 17:16 CEST como `dlimachii <dlimachi@icloud.com>`.
- Commit inicial creado el 2026-04-22 17:18 CEST: `6b07fce` (`Initial Plantaria baseline`).
- Remoto GitHub configurado y rama `main` subida el 2026-04-22 17:30 CEST: `git@github.com:dlimachii/Plantaria.git`.
- El alcance funcional es amplio; si no se protege el MVP, el TFC se puede ir de tamaño.
