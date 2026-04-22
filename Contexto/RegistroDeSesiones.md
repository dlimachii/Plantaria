# Registro de sesiones

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
- Queda pendiente añadir la clave pública en GitHub, crear el repositorio remoto y hacer `git push -u origin main`.

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
