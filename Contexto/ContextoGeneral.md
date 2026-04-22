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
- el trabajo actual deja de ser solo de definición y pasa a construcción incremental del sistema.

## Objetivo actual

Arrancar el TFC con una base realista y defendible:

- cliente principal Android en Kotlin;
- backend API en PHP con Laravel;
- base de datos PostgreSQL con PostGIS;
- panel web para moderación y administración;
- dejar iOS y una web pública completa como fases posteriores, no como requisito del primer MVP.

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

- centrar el mapa en la ubicación real del usuario cuando haya permiso;
- mejorar validaciones y estados de error por campo.
- preparar una ficha completa de registro al tocar una preview.

## Estado para próxima sesión

Fecha de corte: 2026-04-22 17:10 CEST.

Estado aproximado del MVP Android + backend: 60-65%.

Ya está hecho y validado:

- backend Laravel con Sanctum, PostgreSQL/PostGIS, usuarios, registros, observaciones, flags, moderación básica, analítica API y subida de fotos;
- tests backend pasando con `php artisan test`: 6 tests, 25 assertions;
- proyecto Android compilable con Kotlin + Jetpack Compose;
- login, registro, token persistido con DataStore y URL de API editable para emulador/móvil físico;
- mapa real MapLibre Native Android con estilo `https://demotiles.maplibre.org/style.json`;
- registros reales de `/api/records` pintados como marcadores en el mapa;
- selector de imagen con Photo Picker;
- captura directa con cámara usando `TakePicture` y `FileProvider`;
- subida real de fotos a `/api/uploads/photos`;
- creación de reportes y observaciones desde Android;
- botón para rellenar coordenadas con ubicación actual o última conocida;
- datos demo seedables y cargados en PostgreSQL local alrededor de Barcelona;
- APK debug generado en `android/app/build/outputs/apk/debug/app-debug.apk`.

Revalidación del 2026-04-22 17:10 CEST:

- `php artisan test` en `backend/`: 6 tests, 25 assertions, todo pasando;
- `./gradlew :app:assembleDebug` en `android/`: `BUILD SUCCESSFUL`.

No se ha podido validar todavía porque falta el móvil físico:

- instalación real del APK en el teléfono;
- permisos reales de cámara/GPS en dispositivo;
- funcionamiento real de cámara y ubicación;
- conexión del móvil con Laravel en WSL por Wi-Fi o por USB;
- rendimiento real del mapa en hardware físico.

Siguiente paso recomendado si no está el móvil:

- implementar ficha completa de registro al tocar la preview del mapa;
- cargar y mostrar fotos reales en tarjetas/ficha;
- mejorar validaciones por campo y limpieza de formularios.

Siguiente paso recomendado cuando esté el móvil:

- arrancar PostGIS y Laravel;
- instalar APK;
- configurar URL de API según Wi-Fi o `adb reverse`;
- probar login/registro, mapa, cámara, GPS, subida de foto, creación de reporte y observación.

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
- Git ya está inicializado en la raíz del workspace; identidad local configurada el 2026-04-22 17:16 CEST como `dlimachii <dlimachi@icloud.com>`.
- El alcance funcional es amplio; si no se protege el MVP, el TFC se puede ir de tamaño.
