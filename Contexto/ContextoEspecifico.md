# Contexto específico

## Estado actual

Ya hay una definición funcional inicial del TFC, una primera implementación real del backend y un primer cliente Android compilable.

Este archivo recoge el detalle técnico y funcional suficiente para continuar la implementación sin tener que reconstruir la idea desde cero.

## Estructura real creada en el repo

### Backend

Se ha creado `backend/` con Laravel 13.

Piezas ya presentes:

- autenticación por tokens con Sanctum;
- rutas API iniciales;
- modelos principales del dominio;
- migraciones iniciales;
- validaciones con `FormRequest`;
- tests feature básicos.

Capacidades ya implementadas:

- registro e inicio de sesión;
- perfil público y actualización de perfil;
- creación de registros;
- creación de observaciones;
- creación de denuncias;
- verificación de registros por moderación;
- panel API de analítica;
- gestión administrativa básica de usuarios.
- panel web de moderación/admin con login de `MOD`/`ADMIN`, dashboard, cola de pendientes, detalle de registro y acciones de verificar/rechazar.
- actualización 2026-04-22 19:07 CEST: el panel web también incluye listado/filtro de flags y cambio de estado por moderadores/admins.
- actualización 2026-04-22 19:07 CEST: el panel web también incluye listado/filtro de usuarios y edición básica de rol, estado y datos de ubicación por admins.
- actualización 2026-04-23 16:33 CEST: el backend añade `/api/geocoding/search` como proxy cacheado a Nominatim para búsquedas de lugar desde Android.
- actualización 2026-04-23 16:44 CEST: el dashboard web muestra analítica visual de actividad diaria, horas pico, top búsquedas y creadores destacados.
- actualización 2026-04-23 16:53 CEST: la URL del estilo de mapa Android pasa a configuración de build para poder cambiar de proveedor sin tocar Kotlin.
- actualización 2026-04-23 16:55 CEST: se añaden scripts operativos para levantar el stack móvil e instalar el APK debug.
- actualización 2026-04-23 19:10 CEST: el panel web añade búsqueda/filtro de registros y edición avanzada de registros, limitada a `ADMIN`, desde la ficha web.
- actualización 2026-04-23 19:50 CEST: el flujo Android prepara fotos para móvil real antes de subirlas y el mapa separa búsqueda de registros del foco por zona/coordenadas.
- actualización 2026-04-24 17:07 CEST: `/api/records` valida filtros de listado y acepta búsqueda por radio con `latitude`, `longitude` y `radius_km`; en PostgreSQL usa PostGIS (`ST_DWithin` y `ST_Distance`) y devuelve `distance_km`.
- actualización 2026-04-24 17:07 CEST: la documentación visible queda reforzada con `README.md` raíz y `backend/README.md` específico de Plantaria.
- actualización 2026-04-24 17:26 CEST: se añaden documentos de entrega en `docs/`: guía de demo, checklist de validación móvil y memoria técnica base.
- actualización 2026-04-24 17:26 CEST: el filtro por radio de `/api/records` queda probado manualmente contra PostgreSQL/PostGIS local levantado con Docker.
- actualización 2026-04-24 17:29 CEST: se añade `scripts/validate_project.sh` para validar backend, Android, scripts y smoke PostGIS con una sola orden.
- actualización 2026-04-24 17:34 CEST: `DatabaseSeeder` genera imágenes demo PNG para evitar fotos rotas en una instalación limpia.
- actualización 2026-04-24 17:34 CEST: se añade `docs/API.md` como referencia práctica de endpoints y se sincroniza la metadata Composer del backend con Plantaria.
- actualización 2026-04-24 17:41 CEST: se añade middleware `active.user` para bloquear rutas autenticadas a cuentas no activas y tests de autorización API admin.
- actualización 2026-04-24 17:44 CEST: se añade script de backup `scripts/package_for_onedrive.sh`, guía `docs/BACKUP_ONEDRIVE.md` y se crea un paquete real en OneDrive CEAC.
- actualización 2026-04-28 16:10 CEST: el panel web de flags muestra contexto del objetivo denunciado, permite filtrar por tipo y búsqueda, enlaza a registros/usuarios cuando procede y la ficha de moderación enseña flags relacionados con el registro y sus observaciones.
- actualización 2026-04-28 16:48 CEST: `DatabaseSeeder` crea cuentas de prueba por rol (`plantaria_user`, `plantaria_mod`, `plantaria_admin`) y mantiene `plantaria_demo` para datos demo.
- actualización 2026-04-28 16:48 CEST: Android añade splash/logo animado inspirado en el logo SVG, oculta el campo técnico de URL en login, decide URL local por emulador/teléfono y mueve cierre de sesión al menú de perfil.
- actualización 2026-04-28 17:24 CEST: el backend añade `/api/me/activity` y Android cambia `Usuario` para mostrar actividad propia reciente, no registros globales cargados en el mapa.
- actualización 2026-04-28 17:37 CEST: el panel web integra una capa Python+pandas real: Laravel exporta CSV, `analytics/build_admin_analytics.py` calcula un JSON y el dashboard lo muestra; `/admin/assistant` queda preparado para consultas con Ollama local.

### Dominio implementado

Ya existen en código:

- `User`;
- `PlantRecord`;
- `Observation`;
- `ModerationFlag`;
- `AppEvent`.

También existen enums para:

- roles;
- estado de usuario;
- estado de verificación;
- estado de planta;
- tipos de evento;
- tipos de flag;
- tipos de observación.

### Analítica

Se ha creado `analytics/` en la raíz del proyecto con:

- `requirements.txt`;
- `usage_report.py`;
- `README.md`.

Su función es servir de base para el módulo analítico en `Python + pandas`.

### Infraestructura local

Ya existe `compose.yaml` en la raíz del proyecto con un servicio:

- `postgis`, basado en `postgis/postgis:16-3.5`.

Su función es levantar la base PostgreSQL/PostGIS real del proyecto en desarrollo local.

### Estado actual del cliente Android

Ya existe un proyecto Android generado en `android/`.

Actualización: 2026-04-28 17:24 CEST.

Estado actual:

- módulo `app` con Kotlin y Jetpack Compose;
- Gradle wrapper propio con Gradle 9.3.1;
- Android Gradle Plugin 9.1.1;
- Kotlin/Compose compiler 2.3.10;
- Compose BOM 2026.03.00;
- `compileSdk` y `targetSdk` 36;
- paquete `com.plantaria.app`;
- navegación inferior base `Mapa / Acciones / Usuario`;
- pantalla de mapa con MapLibre Native Android y estilo OSM demo;
- pantalla de acciones para crear reporte o actualizar registro;
- pantalla de usuario con perfil, rol, cierre de sesión y actividad reciente propia;
- capa de API con `HttpURLConnection`;
- login y registro contra Laravel;
- persistencia de token con DataStore;
- carga de registros desde `/api/records`;
- selección de imagen con Photo Picker;
- captura directa con cámara usando `TakePicture` y `FileProvider`;
- subida real de fotos contra `/api/uploads/photos`;
- creación de reportes contra `/api/records` usando la ruta devuelta por el backend;
- creación de observaciones contra `/api/records/{publicId}/observations`.
- solicitud de permisos de ubicación y botón para rellenar coordenadas con ubicación actual o última conocida;
- registros demo seedables alrededor de Barcelona para validar el mapa sin depender del móvil físico.
- ficha completa de registro desde la preview del mapa, consultando `/api/records/{publicId}`;
- carga visual de fotos reales en preview, ficha y observaciones;
- normalización Android de URLs de fotos para sustituir `localhost` por la raíz de la API configurada cuando haga falta;
- validaciones por campo en login/registro, nuevo reporte y nueva observación.
- centrado del mapa en ubicación real del usuario si el permiso ya existe o al pulsar el botón `Mi ubicación`;
- marcador de ubicación del usuario en el mapa con iconografía distinta a la de los registros.
- botón `Añadir observación` en la ficha de registro para abrir `Acciones` con el ID del registro ya prellenado.
- sugerencias de lugar y recentrado del mapa desde búsquedas geocodificadas;
- recentrado directo del mapa al introducir coordenadas `lat, lon`;
- separación explícita entre búsqueda de registros por planta/ID y foco del mapa por zona o coordenadas.
- panel web con dashboard visual de analítica sin depender de JavaScript ni librerías de gráficos externas.
- bloque de dashboard calculado con Python+pandas desde snapshots CSV exportados por Laravel.
- asistente admin opcional con Ollama local alimentado por el snapshot pandas.
- ajuste 2026-04-30 16:20 CEST: el asistente admin intenta antes consultas directas seguras con Query Builder sobre tablas del dominio Plantaria; para preguntas conocidas no depende del snapshot pandas.
- entorno local 2026-04-30 16:20 CEST: `analytics/.venv` instalado, `.env` apunta a ese Python y `admin_dashboard.json` generado correctamente.
- pantallas Android de acceso, acciones y usuario con estados de ayuda, carga y vacío más claros para demo.
- estilo de mapa Android configurable por `BuildConfig` en lugar de constante fija.
- scripts `scripts/start_mobile_stack.sh` y `scripts/install_debug_apk.sh` para reducir el coste operativo de prueba.
- script `scripts/profile_app_performance.sh` añadido el 2026-04-30 16:45 CEST para medir tiempos de endpoints usados por Android, tamaño del APK debug y métricas básicas ADB si hay dispositivo.
- preparación/compresión de fotos en Android antes de subirlas para tolerar mejor imágenes reales de móvil.
- preview de pin más compacto, cerrable y sin bloquear los controles principales del mapa.
- actividad propia en perfil desde `/api/me/activity`: reportes creados por la cuenta, commits/observaciones de actualización, flags enviados y acciones registradas de moderación/admin.

Validación realizada:

- `./gradlew :app:assembleDebug` ejecutado correctamente;
- `php artisan test` ejecutado correctamente con 38 tests y 176 assertions;
- `php artisan plantaria:analytics:build` ejecutado correctamente contra PostgreSQL/PostGIS local usando `analytics/.venv`;
- `scripts/profile_app_performance.sh` ejecutado correctamente con línea base API/APK;
- APK debug generado en `android/app/build/outputs/apk/debug/app-debug.apk`.

Pendiente:

- revalidar en móvil físico el APK actual con flujo completo y repetir perfilado con ADB para memoria/render real.

## Visión funcional

`Plantaria` es una plataforma para registrar y seguir plantas geolocalizadas sobre mapa.

Escenario base:

- una persona encuentra una planta;
- hace una foto;
- la app toma o solicita su ubicación;
- añade un nombre provisional y una breve descripción;
- el contenido se publica sobre el mapa;
- más adelante otra persona, o la misma, puede volver al mismo punto y añadir una nueva observación para seguir la evolución temporal de esa planta o de ese lugar.

El sistema debe conservar trazabilidad:

- nombre provisional aportado por el usuario;
- nombre validado por moderador o administrador;
- historial de observaciones posteriores;
- relación entre autor, ubicación, foto y momento temporal.

## Texto funcional breve

`Plantaria` será una aplicación centrada en el descubrimiento, registro y seguimiento de plantas en ubicaciones reales. Su funcionamiento parte de una idea sencilla: cuando una persona encuentra una planta, flor o árbol de interés, puede hacer una foto, asociarla a una ubicación concreta y publicarla en el mapa para que otros usuarios puedan verla, consultarla y volver a ese mismo punto más adelante. De esta forma, la aplicación no solo sirve para identificar lugares donde hay vegetación interesante, sino también para construir una memoria colectiva del entorno natural.

Cada publicación nacerá como un reporte creado por un usuario y podrá incluir nombre provisional, imagen, descripción, estado visible de la planta y localización. Con el paso del tiempo, ese mismo punto podrá recibir nuevas actualizaciones, permitiendo reflejar cambios como floración, maduración, deterioro o evolución estacional. Además, el sistema incorporará moderación para validar el nombre común y el nombre científico de cada registro, diferenciando entre contenido pendiente y contenido verificado. En conjunto, `Plantaria` funcionará como un mapa colaborativo de observaciones botánicas, donde la comunidad aporta datos reales sobre el terreno y la plataforma los organiza, valida y presenta de forma útil.

## Funcionalidad detallada recogida del usuario

Esta sección intenta traducir la cadena larga de ideas del usuario a una especificación funcional más ordenada. Aquí se deja claro qué se ha recogido como comportamiento deseado del producto.

### 1. Arranque y acceso

#### Splash

- pantalla inicial limpia;
- animación de carga con logo de `Plantaria`;
- comprobación automática de sesión guardada;
- si existe token válido, entrada directa a la app;
- si no existe sesión, paso a autenticación.

#### Autenticación

- dos opciones iniciales:
  - `iniciar sesión`;
  - `registrarse`.

#### Inicio de sesión

- acceso con `handle` de usuario y contraseña;
- por ahora no se implementa recuperación completa de contraseña;
- si más adelante se quiere, se podrá dejar un contacto o flujo manual sencillo.

#### Registro

Campos recogidos de la idea del usuario:

- `handle` único visible;
- nombre y apellidos;
- correo electrónico;
- contraseña repetida dos veces;
- país;
- provincia;
- ciudad;
- fecha de nacimiento.

Restricciones previstas:

- `handle` con validación de longitud y unicidad;
- contraseña con requisitos básicos de longitud y mezcla de caracteres;
- contraseña siempre almacenada como hash.

Resultado del registro:

- el usuario se crea con rol `USER`;
- tras registrarse debe iniciar sesión de forma normal;
- se guardará una ubicación por defecto para el caso en que no conceda permisos de geolocalización.

### 2. Estructura general de navegación

La app móvil tendrá navegación inferior fija con tres apartados:

- `mapas`;
- `acciones`;
- `usuario`.

`mapas` será la pestaña de apertura por defecto.

### 3. Pantalla de mapas

Esta es la parte más importante del producto y la que más ideas del usuario concentra.

#### Centrado inicial del mapa

- si el usuario concede permiso de ubicación, el mapa se abre centrado en su posición actual;
- si no concede permiso, el mapa se abre según la ubicación por defecto guardada en su perfil;
- el mapa debe permitir zoom y desplazamiento normal;
- habrá un punto o marcador visual de la ubicación relevante del usuario cuando proceda.

#### Chinchetas y preview

- cada registro aparecerá como una chincheta sobre el mapa;
- al pulsar una chincheta se abrirá una preview rápida en la misma escena del mapa;
- esa preview mostrará al menos:
  - foto principal;
  - nombre común si existe;
  - nombre científico si ya está verificado;
  - estado de verificación.

Comportamiento deseado tomado de tu idea:

- primer toque en chincheta: preview;
- segundo toque sobre la preview: apertura de la ficha completa;
- más adelante se podrá estudiar si mantener pulsación larga o aparición por zoom como refinamiento visual.

#### Búsqueda en mapa

Ideas recogidas como funcionalidad objetivo:

- buscador en la parte superior del mapa;
- búsqueda por zonas: `Barcelona`, `Madrid`, `El Retiro`;
- búsqueda por planta:
  - nombre común;
  - nombre científico;
- búsqueda combinada, por ejemplo: `orquídeas en Barcelona`;
- búsqueda por coordenadas: `39.4699, -0.3763`;
- búsqueda por ID de reporte.

Comportamiento esperado:

- si se busca una ubicación, el mapa se recentra allí;
- si la búsqueda es por planta o combinación planta + zona, se listan hasta cinco resultados previstos;
- cada resultado previsto mostrará:
  - imagen;
  - nombre común;
  - nombre científico;
  - distancia respecto al usuario, solo si hay permiso de ubicación.

#### Contenido pendiente y verificado

- los usuarios normales podrán ver contenido pendiente y verificado;
- en búsquedas y prioridades se dará preferencia a contenido verificado;
- moderadores y administradores sí verán con mayor claridad la distinción entre ambos estados.

### 4. Ficha y reporte

Has planteado dos ideas relacionadas:

- `reporte` como contenido inicialmente no verificado;
- `ficha` como contenido ya validado.

La decisión tomada para implementarlo sin duplicar lógica es:

- internamente será un mismo registro con distinto estado;
- externamente en la interfaz se podrá seguir hablando de reporte y ficha.

#### Datos mínimos del reporte inicial

Elementos recogidos como obligatorios:

- foto;
- ubicación;
- nombre provisional.

Elementos recogidos como opcionales o ampliables:

- descripción;
- estado visible de la planta;
- hora de creación;
- otros datos biológicos futuros.

#### Trazabilidad

Esta parte sí la he recogido como central, no secundaria:

- se guardará el nombre provisional original del usuario;
- se guardará el nombre común validado;
- se guardará el nombre científico validado;
- se conservará el historial temporal de actualizaciones posteriores;
- cada elemento tendrá autor, fecha y referencia espacial.

### 5. Acciones

La pestaña `acciones` se ha recogido con dos flujos distintos.

#### Crear reporte

Opciones de entrada:

- sacar foto con cámara;
- elegir imagen desde fototeca si finalmente se permite en el MVP.

Campos previstos:

- foto obligatoria;
- ubicación obligatoria;
- nombre provisional obligatorio, con posibilidad de usar algo tipo `desconocido`;
- descripción opcional;
- estado visible de la planta;
- fecha y hora automáticas.

Resultado:

- se crea un reporte pendiente;
- aparece en mapa;
- aparece en el perfil del autor;
- queda marcado como pendiente de validación.

#### Actualizar un registro existente

Esta idea la he recogido como la metáfora del `commit` o seguimiento temporal.

Flujo previsto:

- el usuario elige `actualizar`;
- introduce el ID del registro;
- si el ID no existe, la acción no continúa;
- si existe, se crea una nueva observación asociada al registro;
- esa observación añade nueva foto, nueva fecha y nueva ubicación.

Restricción adoptada:

- para actualizar un registro sí se exigirá ubicación activa en el momento de la actualización, porque refuerza la verificación de que la observación está ligada al lugar.

### 6. Perfil de usuario

#### Perfil público

Ideas recogidas:

- foto de perfil;
- handle;
- ID visible y copiable;
- lista de reportes públicos del usuario;
- acceso a los reportes desde el perfil.

También se recoge este flujo:

- si desde una ficha se pulsa el autor del reporte, se accede a su perfil público.

#### Edición del propio perfil

El usuario podrá modificar:

- nombre visible;
- handle público;
- foto de perfil.

Nota técnica importante ya adoptada:

- el handle podrá cambiar sin romper relaciones porque el sistema usará un `uid` interno inmutable.

### 7. Roles y moderación

#### Usuario base

- crea reportes;
- crea actualizaciones;
- consulta mapa, fichas y perfiles.

#### Moderador

Funciones recogidas de tu idea:

- ver una lista de pendientes por verificar;
- validar nombre común y científico;
- distinguir mejor contenido no verificado;
- denunciar reportes o usuarios al administrador;
- disponer de filtros como cercanía o fecha para revisar pendientes.

#### Administrador

Funciones recogidas:

- todo lo del moderador;
- verificar también;
- editar cualquier dato de un registro;
- borrar reportes;
- borrar usuarios;
- banear usuarios;
- cambiar roles;
- ver más información interna del usuario.

#### Panel del administrador

Tu idea se ha recogido así:

- dentro del espacio de usuario del admin habrá apartados adicionales;
- habrá al menos una zona de pendientes y otra de denuncias;
- dentro de denuncias podrá filtrarse por:
  - reportes;
  - usuarios.

### 8. Datos internos y visibilidad

#### Datos visibles públicamente

Se recoge como visible para cualquiera:

- handle;
- foto pública;
- lista pública de reportes;
- ficha o reporte consultable;
- autor del contenido.

#### Datos solo para administración

Se recoge como información reservada para `ADMIN`:

- correo electrónico;
- última conexión;
- posible última ubicación conocida si finalmente se almacena;
- estado de cuenta;
- datos internos de control.

### 9. Identificadores

Has insistido bastante en copiar IDs, buscarlos y mantener estabilidad. Esto se ha recogido así:

- cada usuario tendrá:
  - `uid` interno inmutable;
  - `handle` público editable.
- cada registro tendrá un ID público generado por servidor;
- el ID del registro será copiable;
- el ID del usuario también podrá copiarse si se quiere mostrar como dato público secundario.

### 10. Ideas visuales recogidas

Sin cerrar todavía un diseño final, sí se han recogido estas líneas:

- footer con tres pestañas en móvil;
- splash con logo animado;
- mapa como foco visual principal;
- uso de verdes, blancos y tonos tierra claros;
- diferenciación visual entre pendiente y verificado;
- grid o tarjetas simples en perfil de usuario.

### 11. Ideas recogidas pero simplificadas para el MVP

Estas ideas tuyas sí se han tenido en cuenta, pero con versión simplificada:

- estado biológico complejo:
  - se arranca con estado visible simple de aspecto;
  - ciclos más botánicos se dejan para más adelante.
- comportamiento avanzado del mapa:
  - se implementa primero tap para preview y apertura de ficha;
  - interacciones más finas como zoom contextual o long-press quedan como mejora.
- búsqueda:
  - se deja prevista la combinación por planta y lugar;
  - la versión exacta del motor de ranking se resolverá en implementación.

### 12. Ideas que no se descartan, pero no entran aún

- cliente iOS nativo;
- web pública completa con el mapa para usuarios generales;
- reconocimiento automático de especies con IA;
- taxonomías avanzadas;
- sistema fuerte de recuperación de contraseña;
- notificaciones push;
- lógica muy profunda sobre estados botánicos especializados.

## Analítica y estadísticas de uso

No se había definido como bloque propio al inicio, pero encaja bien con el panel web de administración y puede aportar mucho valor al TFC.

### Enfoque recomendado

La propuesta recomendada es separar dos niveles:

#### Nivel 1: analítica integrada en el stack principal

- registrar eventos de uso en el backend;
- guardar esos eventos en PostgreSQL;
- generar consultas, agregados y gráficas desde Laravel en el panel de administración.

Ventaja:

- es la opción más simple y más razonable para el MVP;
- no obliga a introducir otro lenguaje o servicio desde el principio.

#### Nivel 2: analítica avanzada opcional con Python

`Python` sí tendrá sentido como módulo complementario del TFC si se quiere demostrar tratamiento de datos de forma explícita.

Usos previstos:

- generar informes periódicos complejos;
- hacer análisis más pesados sobre históricos;
- crear gráficas avanzadas o exportaciones automáticas;
- detectar patrones, anomalías o tendencias;
- preparar cuadros de mando más analíticos que operativos.

Decisión operativa:

- no usar `Python` en el núcleo transaccional del backend inicial;
- sí plantearlo como módulo auxiliar de analítica para el panel admin;
- usarlo especialmente para demostrar en el TFC tratamiento de datos, generación de gráficas y explotación de históricos.

### Datos de uso que se quieren explotar

Se recoge como deseable para el panel de administración:

- usuarios activos por día;
- usuarios nuevos por día;
- horas pico de uso;
- número de reportes creados por día;
- número de actualizaciones o seguimientos por día;
- plantas más buscadas;
- zonas más buscadas;
- fichas o reportes más vistos;
- reportes pendientes por verificar;
- tiempo medio de validación por parte de moderadores;
- actividad por rol;
- usuarios más activos en creación de contenido.

### Eventos que conviene registrar

Para poder sacar esas estadísticas, el backend debería registrar eventos de uso como:

- inicio de sesión correcto;
- registro de nuevo usuario;
- apertura de la app con sesión válida;
- búsqueda en mapa;
- apertura de preview de registro;
- apertura de ficha completa;
- creación de reporte;
- creación de actualización;
- validación de un reporte;
- denuncia de reporte o usuario;
- visita a perfil de usuario.

### Modelo técnico recomendado para el MVP

#### Tabla de eventos

Conviene una tabla tipo `app_events` o similar con campos como:

- `id`;
- `event_type`;
- `user_uid` nullable;
- `role_snapshot`;
- `record_uid` nullable;
- `search_query` nullable;
- `search_type` nullable;
- `metadata` en JSON;
- `created_at`.

#### Normalización útil

Además del evento bruto, puede ser útil guardar datos ya preparados para consultas:

- fecha;
- hora;
- país o provincia si procede;
- nombre de planta buscado normalizado;
- zona buscada normalizada.

### Métricas iniciales del panel admin

Para un primer dashboard administrativo, se recomienda incluir:

- tarjetas resumen:
  - usuarios activos hoy;
  - usuarios nuevos hoy;
  - reportes pendientes;
  - reportes creados hoy;
- gráfica de actividad por día;
- gráfica de actividad por horas;
- tabla de plantas más buscadas;
- tabla de zonas más buscadas;
- tabla de usuarios con más reportes;
- tabla de registros más actualizados;
- bloque de moderación con pendientes y tiempo medio de revisión.

### Qué papel tendría Python si se añade más adelante

Si se añade `Python`, el uso recomendado será secundario y analítico:

- scripts programados que lean de PostgreSQL;
- generación de CSV, JSON o informes PDF;
- procesamiento con `pandas` o similares;
- creación de gráficas más avanzadas;
- cálculo de tendencias semanales o mensuales;
- detección de crecimiento, caídas de uso o búsquedas anómalas.

No se recomienda:

- usar `Python` para el flujo normal de moderación web;
- convertir el panel admin en una aplicación separada solo para estadísticas;
- depender de `Python` para métricas básicas que SQL ya resuelve bien.

### Propuesta concreta para demostrar Python en el TFC

Para que el uso de `Python` sea defendible y no decorativo, se propone un pequeño módulo analítico con:

- lectura de eventos y agregados desde PostgreSQL;
- procesamiento con `pandas`;
- generación de gráficas de:
  - usuarios activos por día;
  - picos horarios;
  - búsquedas más frecuentes;
  - reportes creados por periodo;
  - validaciones por periodo;
- exportación de resultados para el panel admin o para informes.

Estado actual de implementación:

- ya existe el directorio `analytics/`;
- ya existe un script inicial `usage_report.py`;
- el script está pensado para leer PostgreSQL y exportar CSV y gráficas.

## Posible IA de consultas en el panel admin

Además de la IA de reconocimiento de plantas, se deja anotada otra posible línea futura: un asistente interno para administración que permita consultar estadísticas y actividad del sistema usando lenguaje natural.

### Idea funcional

Ejemplos de consulta:

- `cuántos usuarios se conectaron el día 12 de mayo`;
- `muéstrame los picos de uso de la última semana`;
- `qué plantas se buscaron más este mes`;
- `cuántos reportes siguen pendientes de validar`.

### Enfoque técnico recomendado

- usar un modelo local mediante `Ollama`;
- no darle acceso libre a toda la base de datos;
- hacerlo trabajar sobre:
  - vistas controladas;
  - tablas agregadas;
  - métricas ya preparadas;
  - consultas parametrizadas seguras.

### Papel dentro del TFC

Esto no debe ser parte del núcleo del MVP, pero sí puede quedar planteado como:

- mejora avanzada del panel admin;
- demostración de IA aplicada a consulta interna de negocio;
- extensión futura coherente con el uso de datos y analítica.

### Regla de seguridad recomendada

Si se implementa, el asistente no debería:

- escribir en base de datos;
- modificar usuarios o reportes;
- ejecutar SQL libre generado sin validación.

Su función sería solo:

- traducir lenguaje natural a consultas seguras ya acotadas;
- resumir resultados de uso, moderación y actividad.

## Integración futura de IA para reconocimiento de plantas

Se deja constancia de una decisión importante para futuras sesiones: si más adelante se implementa ayuda automática de reconocimiento de plantas, la estrategia recomendada no es entrenar un modelo propio desde cero, sino integrar una API externa ya existente.

### Enfoque recomendado

- usar la IA como asistente de identificación;
- no usarla como sistema definitivo de validación;
- mantener la moderación humana como criterio oficial de verificación;
- integrar la IA desde backend, no directamente desde la app móvil.

### Flujo funcional recomendado

- el usuario sube una foto al crear un reporte;
- el backend consulta un servicio externo de identificación;
- se guardan:
  - mejor coincidencia;
  - top 3 o top 5 sugerencias;
  - score de confianza;
  - respuesta cruda del proveedor;
- la app muestra la sugerencia como ayuda;
- el usuario puede aceptar una sugerencia o dejar nombre provisional manual;
- el moderador o admin sigue validando el nombre final.

### Proveedores ya investigados

#### Pl@ntNet

Estado:

- opción preferida para una futura integración en `Plantaria`.

Motivos:

- API específica para identificación de plantas;
- enfoque alineado con ciencia, educación y biodiversidad;
- plan gratuito útil para bajo volumen;
- posibilidad de plan `non-profit`;
- devuelve varias especies probables con score y nombres científicos;
- permite enviar de 1 a 5 imágenes del mismo individuo;
- puede trabajar con flora o proyecto concreto para mejorar resultados.

Notas técnicas recordadas:

- el endpoint principal identificado en documentación es `/v2/identify/{project}`;
- admite imágenes JPG o PNG;
- puede inferir automáticamente el órgano o recibirlo como parámetro;
- devuelve `bestMatch`, lista de `results`, score y cuota restante;
- el plan gratuito publicado indica 500 identificaciones al día.

#### plant.id / Kindwise

Estado:

- opción secundaria viable, más comercial.

Motivos:

- producto maduro y orientado a integración empresarial;
- ofrece detalles adicionales útiles;
- también dispone de documentación y API pública.

Reservas:

- modelo de precios más comercial;
- encaja menos con un MVP sencillo y académico del TFC.

### Decisión operativa si se implementa

Si en una futura sesión el usuario pide "meter IA para reconocer plantas", la recomendación por defecto debe ser:

- empezar con `Pl@ntNet`;
- encapsularlo en backend como proveedor intercambiable;
- guardar sugerencias IA sin sustituir la validación humana.

## Alcance MVP recomendado

### Cliente principal

- app Android nativa en Kotlin;
- navegación inferior con tres secciones:
  - `mapas`;
  - `acciones`;
  - `usuario`.

### Funcionalidad de usuario

- splash / carga inicial;
- inicio de sesión;
- registro;
- mapa con ubicación actual si hay permiso;
- fallback a ubicación por defecto del usuario si no hay permiso;
- vista de chinchetas con preview rápida;
- apertura de ficha completa al pulsar la preview;
- creación de reporte nuevo;
- creación de actualización de un registro ya existente;
- perfil público básico con foto, handle y lista de registros;
- edición de perfil propio.

### Funcionalidad de moderación

- cola de pendientes por verificar;
- validación de nombre común y nombre científico;
- distinción visual entre contenido verificado y pendiente;
- denuncias de reportes o usuarios;
- filtro de pendientes por cercanía o fecha.

### Funcionalidad de administración

- edición total de registros y usuarios;
- promoción de usuarios a moderador;
- borrado de registros;
- borrado o baneo de usuarios;
- acceso a metadatos administrativos del usuario.

### Soporte web del MVP

Recomendación:

- no hacer una web pública completa con paridad total respecto a móvil en el primer corte;
- sí hacer un panel web administrativo y de moderación, porque tiene sentido de uso real y mejora la defensa del TFC.

Estado actualizado 2026-04-22 19:07 CEST:

- el panel web Laravel ya cubre login, dashboard, revisión de registros pendientes, detalle, verificación/rechazo, gestión de flags y gestión básica de usuarios;
- la gestión de flags está disponible para `MOD` y `ADMIN`;
- la gestión de usuarios está limitada a `ADMIN`.

Estado actualizado 2026-04-23 16:33 CEST:

- Android ya permite buscar lugares contra Nominatim mediante el backend y centrar el mapa sobre la coincidencia elegida;
- el buscador acepta coordenadas para recentrado rápido sin depender del proveedor externo;
- la validación funcional fuerte pendiente sigue siendo la prueba en móvil físico.

Estado actualizado 2026-04-23 16:44 CEST:

- el panel web Laravel ya incluye una capa visual de analítica para demo y seguimiento operativo;
- el foco inmediato del proyecto vuelve a ser la prueba en móvil físico y el cierre de detalles UX.

Estado actualizado 2026-04-23 16:48 CEST:

- el cliente Android ya muestra ayudas rápidas para configurar la URL API en acceso;
- las pantallas clave enseñan mejor los estados de error, éxito y vacío de cara a la demo en móvil físico.

Estado actualizado 2026-04-23 16:53 CEST:

- la app Android ya puede cambiar de estilo/proveedor de mapa sin modificar el código Kotlin del mapa;
- la estrategia técnica queda preparada para pasar de un estilo demo a un proveedor vectorial real o a hosting propio.

Estado actualizado 2026-04-23 16:55 CEST:

- el repo ya incluye comandos scriptados para preparar backend+BD y para compilar/instalar el APK por `adb`;
- la siguiente interacción útil pendiente sigue siendo la prueba real en el teléfono.

Estado actualizado 2026-04-23 19:10 CEST:

- el panel web Laravel ya permite filtrar registros por estado y buscarlos por ID o nombre;
- la ficha web del registro ya permite edición avanzada de datos para `ADMIN` sin quitar a `MOD` el flujo separado de verificar/rechazar;
- la validación funcional fuerte pendiente sigue siendo la prueba en móvil físico.

Estado actualizado 2026-04-23 19:50 CEST:

- la primera prueba física parcial confirmó login y navegación básica en Android real;
- la creación de reportes con foto detectó un cuello de botella real en el límite de subida del servidor y en el tratamiento Android de imágenes grandes;
- el mapa Android se reestructuró para que buscar registros y mover el foco del mapa sean acciones distintas;
- la revalidación final pendiente consiste en reinstalar la APK nueva y repetir creación de reporte y observación.

## Fuera de alcance inicial

Conviene dejar fuera del primer MVP:

- cliente iOS nativo en Swift;
- web pública completa con todas las funciones del cliente móvil;
- identificación automática de especies con IA;
- taxonomía botánica avanzada y demasiado detallada;
- modo offline completo;
- sistema de recuperación de contraseña elaborado;
- notificaciones push.

Todo eso puede ir a líneas futuras.

## Roles del sistema

### USER

- puede registrarse e iniciar sesión;
- puede consultar mapa, fichas y perfiles públicos;
- puede crear registros y actualizaciones;
- puede editar su perfil.

### MOD

- hereda todo lo de `USER`;
- puede validar nombre común y nombre científico;
- puede revisar pendientes;
- puede denunciar reportes o usuarios al panel administrativo.

### ADMIN

- hereda todo lo de `MOD`;
- puede editar cualquier campo;
- puede verificar también;
- puede borrar reportes;
- puede borrar o banear usuarios;
- puede cambiar roles;
- puede ver metadatos internos del usuario.

## Modelo de dominio recomendado

### Nota de terminología

En la interfaz el usuario habla de:

- `reporte` cuando algo está pendiente;
- `ficha` cuando ya está validado.

En backend conviene simplificarlo:

- un `record` o registro geolocalizado como entidad principal del mapa;
- una o varias `observations` como historial temporal de ese registro;
- un campo de verificación para distinguir pendiente y verificado.

### Entidades principales

#### Users

Campos base recomendados:

- `uid` interno inmutable;
- `handle` público, único y editable;
- `email` único;
- `password_hash`;
- `display_name`;
- `photo_url`;
- `country`;
- `province`;
- `city`;
- `default_lat`;
- `default_lng`;
- `birthdate`;
- `role`;
- `status`;
- `created_at`;
- `last_login_at`;
- `last_known_lat` y `last_known_lng` solo visibles para admin si finalmente se guardan.

#### Records

Representan la chincheta principal del mapa.

Campos recomendados:

- `uid` interno;
- `public_id` legible o copiable;
- `created_by_uid`;
- `point` geoespacial;
- `provisional_common_name`;
- `verified_common_name`;
- `verified_scientific_name`;
- `description`;
- `plant_condition`;
- `verification_status` (`PENDING`, `VERIFIED`, `REJECTED`);
- `verified_by_uid`;
- `verified_at`;
- `created_at`;
- `deleted_at`.

#### Observations

Representan cada actualización temporal asociada a un registro.

Campos recomendados:

- `uid` interno;
- `public_id`;
- `record_uid`;
- `author_uid`;
- `photo_url`;
- `point`;
- `note`;
- `plant_condition`;
- `observed_at`;
- `created_at`;
- `source_type` (`INITIAL`, `UPDATE`);
- `moderation_status` si se decide moderar también las actualizaciones.

#### Flags

Para incidencias y denuncias.

Campos recomendados:

- `uid`;
- `target_type` (`RECORD`, `OBSERVATION`, `USER`);
- `target_id` interno o `target_reference` en la API pública;
- `created_by_uid`;
- `reason`;
- `status`;
- `created_at`;
- `resolved_by_uid`;
- `resolved_at`.

## Reglas funcionales importantes

- Todo registro debe tener usuario, foto y ubicación.
- El nombre provisional debe guardarse aunque luego el moderador lo corrija.
- El `handle` del usuario puede cambiar, pero el `uid` no.
- Los IDs públicos copiados por la interfaz no deben ser la PK real.
- Si no hay permiso de ubicación, el mapa se centra en la ubicación por defecto del usuario.
- Para crear una actualización de un registro existente sí conviene exigir ubicación activa, porque el objetivo es reforzar trazabilidad.
- En búsquedas públicas se prioriza contenido verificado, pero el pendiente puede seguir visible si así se mantiene en el producto.

## Flujos clave

### 1. Acceso

- splash;
- comprobación de token;
- si hay token válido, entrar;
- si no, mostrar `iniciar sesión / registrarse`.

### 2. Mapa

- centrar en ubicación actual si hay permiso;
- si no, centrar en coordenadas por defecto del usuario;
- mostrar chinchetas;
- al pulsar chincheta, mostrar preview;
- al pulsar la preview, abrir ficha completa.

### 3. Búsqueda

Tipos de búsqueda previstos:

- por zona o texto geográfico;
- por nombre común;
- por nombre científico;
- por coordenadas;
- por `record_id`;
- por `handle`.

Orden recomendado:

- verificados primero;
- si hay ubicación del usuario, después por distancia;
- después por recencia.

### 4. Crear registro

Campos mínimos:

- foto obligatoria;
- ubicación obligatoria;
- nombre provisional obligatorio;
- descripción opcional;
- estado visible de la planta;
- fecha y hora automáticas.

### 5. Actualizar registro

- el usuario introduce o escanea el ID del registro;
- si no existe, no se permite continuar;
- si existe, se añade una nueva observación con foto, ubicación y fecha.

### 6. Moderación

- moderador o administrador abre pendientes;
- valida nombre común y científico;
- el contenido pasa a verificado;
- una denuncia genera entrada en el panel del administrador.

## Arquitectura propuesta

### Capa cliente

- Android en Kotlin como cliente principal del MVP.

### Capa servidor

- API REST en Laravel;
- autenticación con tokens para móvil;
- panel web administrativo servido por el mismo backend.

### Persistencia

- PostgreSQL como base de datos principal;
- PostGIS para coordenadas, distancias, radios y filtros geográficos;
- almacenamiento de imágenes en disco local de desarrollo o en almacenamiento tipo S3 si más adelante se dockeriza con algo como MinIO.

### Ecosistema de mapas

- OpenStreetMap como fuente cartográfica base;
- Nominatim para geocodificación y búsqueda de lugares;
- motor de mapa web tipo Leaflet para panel web;
- capa móvil basada en el ecosistema OSM, manteniendo la puerta abierta a MapLibre en Android e iOS si se quiere unificar más adelante.

## Motivos técnicos clave

- PostgreSQL + PostGIS encaja mejor que MariaDB cuando el mapa y la geolocalización son parte central del dominio.
- Laravel permite construir rápido API, autenticación, panel web y modelo relacional sin meter demasiada complejidad.
- Un solo cliente nativo real en el MVP reduce mucho riesgo para un TFC y sigue siendo perfectamente defendible.
- Separar `uid` interno y `handle` público evita romper relaciones cuando el usuario cambia su identificador visible.

## Revisión técnica integral

Estado actualizado: 2026-04-24 16:53 CEST.

### Lectura por módulos

Backend:

- Laravel 13 con Sanctum ya cubre autenticación móvil, perfil, registros, observaciones, flags, subida de fotos, moderación, usuarios admin y analítica.
- El modelo de dominio está bien alineado con la idea original: `PlantRecord` como chincheta/ficha, `Observation` como historial temporal, `ModerationFlag` como denuncia y `AppEvent` como base de analítica.
- Hay tests feature para registro/login, creación de registros y observaciones, subida de fotos, flags, moderación, panel admin y geocoding.
- El backend está preparado para PostgreSQL/PostGIS local vía `compose.yaml`, pero los tests automáticos usan sqlite en memoria.

Android:

- El cliente móvil ya no es maqueta: inicia sesión, guarda token y URL API, pinta mapa MapLibre, carga registros reales, muestra preview/ficha, sube fotos, crea reportes y crea observaciones.
- La navegación real está concentrada en `Mapa`, `Acciones` y `Usuario`, que encaja con la idea móvil del proyecto.
- El flujo de fotos se endureció para móvil real: Photo Picker, cámara con `TakePicture`/`FileProvider` y compresión antes de subir.
- La parte más sensible sigue siendo la prueba física final, porque cámara, permisos, GPS, red local y subida de imágenes son justo lo que más cambia entre emulador y teléfono.

Panel web:

- El panel `/admin` ya permite login de `MOD`/`ADMIN`, dashboard, cola de moderación, verificación/rechazo, flags, usuarios y edición avanzada de registros para `ADMIN`.
- La analítica visual se renderiza en Blade sin depender de JavaScript externo, lo que simplifica la demo.
- Para el TFC es suficiente como panel administrativo, aunque no sustituye una web pública completa.

Analítica:

- `analytics/usage_report.py` demuestra una vía clara con `Python + pandas + matplotlib + SQLAlchemy`.
- Actualmente es módulo complementario: lee `app_events` y exporta CSV/gráficas, pero no alimenta todavía el panel en tiempo real.

Infraestructura:

- `compose.yaml` levanta PostgreSQL/PostGIS.
- `scripts/start_mobile_stack.sh` reduce pasos para móvil: arranca base, migra/seed, asegura `storage:link` y sirve Laravel con límites de subida adecuados.
- `scripts/install_debug_apk.sh` reduce pasos para compilar e instalar APK debug.

### Aciertos técnicos

- La arquitectura está bien dimensionada para DAM: Android nativo + Laravel + PostGIS + panel admin, sin intentar resolver iOS y web pública a la vez.
- La separación `uid` interno y `handle` público es correcta y evita deuda futura en relaciones.
- La decisión de tratar reporte y ficha como el mismo registro con distinto estado de verificación evita duplicar modelos.
- La moderación existe de verdad en backend y panel, no solo como idea escrita.
- La app usa datos reales de API y fotos reales; eso sube mucho la calidad defendible del proyecto.
- La configuración editable de URL API en Android resuelve el problema práctico emulador/móvil físico/WSL.

### Deuda y riesgos

- PostGIS está activado, pero todavía no hay columnas espaciales ni consultas reales por radio/distancia/bounding box; de momento se guardan `latitude` y `longitude` como decimales.
- El ranking de búsqueda todavía es básico: texto/ID y geocoding separada, sin filtro espacial fuerte.
- El backend README sigue siendo el genérico de Laravel y debe reemplazarse antes de una entrega seria.
- No hay CI configurada ni suite Android automatizada; la confianza Android depende de compilación y prueba manual.
- Falta validar en teléfono físico el flujo reconstruido: galería, cámara, GPS, subida de foto, creación de reporte y observación.
- El estilo de mapa sigue apuntando por defecto a `demotiles.maplibre.org`, correcto para desarrollo pero no para producción.

### Juicio de estado

El proyecto está fuerte para un TFC si se presenta como MVP centrado en Android. No está terminado como producto público completo, pero sí tiene una base funcional real y coherente. La prioridad técnica no debería ser añadir más pantallas, sino cerrar la prueba física, limpiar documentación visible y endurecer dos o tres puntos de calidad que puedan salir en una defensa: README real, explicación de PostGIS aunque aún no haya consultas espaciales avanzadas y evidencia de validación móvil.
