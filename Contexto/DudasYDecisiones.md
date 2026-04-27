# Dudas y decisiones

## Decisiones cerradas

### Estructura documental del contexto

Estado: resuelta

Decisión:

- usar archivos `.md` directos en la raíz del workspace para que sean fáciles de localizar;
- separar contexto general y contexto específico;
- leer siempre primero el general;
- abrir el específico solo cuando el general se quede corto;
- registrar dudas y sesiones para no repetir trabajo.

Motivo:

Así se reduce el coste de contexto sin obligar a releer todo el código ni toda la conversación histórica.

### Registro de lo hablado y lo hecho

Estado: resuelta

Decisión:

- lo importante de las conversaciones y del trabajo técnico debe quedar reflejado en archivos `.md` del workspace.

Motivo:

La memoria útil no debe depender solo del chat de una sesión concreta.

### El contexto actual es un procedimiento, no documentación técnica

Estado: resuelta

Decisión:

- mientras no exista código ni materiales reales del TFC, estos archivos deben describir cómo mantener el contexto, no inventar detalles del proyecto.

Motivo:

Primero hay que fijar el procedimiento. El contenido técnico llegará después.

### Definición inicial del proyecto Plantaria

Estado: resuelta

Decisión:

- el TFC se llama `Plantaria`;
- el producto es una plataforma colaborativa de registros vegetales geolocalizados;
- la idea central es combinar mapa, foto, trazabilidad temporal y validación comunitaria.

Motivo:

El usuario ya ha descrito suficiente material funcional como para dejar de tratar el proyecto como una idea vacía.

### Alcance inicial del MVP

Estado: resuelta

Decisión:

- priorizar una app Android nativa como cliente principal;
- construir backend y panel web de moderación/administración;
- dejar fuera del primer MVP una app iOS nativa y una web pública completa con paridad total.

Motivo:

Intentar Android + iOS + web pública + backend completo en un TFC de DAM multiplica demasiado el riesgo y el tiempo.

### Stack base recomendado

Estado: resuelta

Decisión:

- backend en PHP con Laravel;
- base de datos en PostgreSQL con PostGIS;
- ecosistema de mapas basado en OpenStreetMap;
- geocodificación con Nominatim;
- panel web simple sobre el propio backend.

Motivo:

Es una combinación realista para un TFC, rápida de desarrollar y especialmente adecuada para consultas geoespaciales.

### Modelo de identidad de usuario

Estado: resuelta

Decisión:

- usar un `uid` interno, inmutable y no visible como clave real;
- usar un `handle` público, único y editable como identificador visible en la app.

Motivo:

Permite que el usuario cambie su identificador visible sin romper relaciones ni referencias entre tablas.

### Modelo funcional de dominio

Estado: resuelta

Decisión:

- tratar la chincheta del mapa como un `registro` principal;
- guardar el historial temporal en `observaciones`;
- distinguir estado pendiente o verificado mediante moderación, no creando dos sistemas inconexos.

Motivo:

El usuario necesita a la vez mapa, seguimiento temporal y trazabilidad entre nombres provisionales y nombres validados.

### Estrategia de IA para identificación vegetal

Estado: resuelta

Decisión:

- si se incorpora reconocimiento automático de plantas, se integrará una API externa ya existente;
- la IA será una ayuda de sugerencia, no un sustituto del sistema de moderación;
- la opción preferida investigada es `Pl@ntNet`;
- la integración debe hacerse desde backend para no exponer claves ni acoplar el cliente a un proveedor concreto.

Motivo:

Entrenar un modelo propio no es razonable para el alcance del TFC. Integrar una IA externa sí es viable y mantiene el proyecto simple.

### Estrategia de analítica y estadísticas de uso

Estado: resuelta

Decisión:

- el panel web de administración podrá incluir estadísticas de uso de la app;
- las métricas básicas deben salir del propio stack principal con `Laravel + PostgreSQL`;
- `Python` se usará como módulo complementario de analítica para demostrar tratamiento de datos, informes y gráficas;
- `pandas` queda expresamente contemplado como herramienta válida para esa parte analítica;
- `Python` no será la base del backend principal ni del flujo normal de moderación.

Motivo:

Las estadísticas de uso aportan valor al TFC y el uso de `Python` puede reforzar la parte de análisis de datos, siempre que se mantenga separado del núcleo transaccional.

### Posible asistente IA para consultas administrativas

Estado: resuelta

Decisión:

- se deja prevista una posible implementación futura de asistente interno en el panel admin;
- la opción preferida sería un modelo local servido con `Ollama`;
- su uso sería consultar métricas y actividad del sistema en lenguaje natural;
- si se implementa, debe trabajar sobre vistas o métricas agregadas y no sobre acceso libre de escritura a la base.

Motivo:

Encaja bien como mejora avanzada del panel admin y como posible demostración de IA aplicada a analítica interna.

### Ubicación definitiva del código fuente

Estado: resuelta

Decisión:

- el código real del proyecto queda ubicado en este mismo workspace;
- el backend se ha creado en `backend/`;
- la parte analítica en Python se ha creado en `analytics/`.

Motivo:

La duda inicial ya no aplica: ahora sí existe implementación real en este repositorio.

### Política de base de datos para desarrollo y proyecto real

Estado: resuelta

Decisión:

- la base de datos real objetivo del proyecto es `PostgreSQL + PostGIS`;
- `sqlite` no se considera una base equivalente para desarrollo completo;
- `sqlite` queda solo como apoyo para tests automáticos o arranque mínimo cuando no exista todavía servidor PostgreSQL disponible.

Motivo:

El dominio de `Plantaria` depende de geolocalización y consultas espaciales. Intentar mantener ambos motores como opciones de primera clase complicaría el proyecto y diluiría el uso real de PostGIS.

### Estrategia de infraestructura local

Estado: resuelta

Decisión:

- el desarrollo local del backend debe apoyarse en `Docker Compose` para levantar PostgreSQL/PostGIS;
- el archivo `compose.yaml` del repo es ahora parte real del proyecto;
- las migraciones del backend deben probarse contra PostgreSQL real, no solo contra sqlite.

Motivo:

Ya existe Docker operativo en el entorno y el proyecto necesita validar de verdad su capa geoespacial.

### Estado del cliente Android

Estado: resuelta

Decisión:

- ya se puede avanzar con el cliente Android en este entorno;
- la toolchain Android está instalada y validada;
- el repo ya contiene un proyecto Android inicial en `android/`;
- el cliente usa Gradle wrapper propio y no depende del Gradle global antiguo instalado por `apt`.

Motivo:

El scaffold Android ya se ha creado con SDK real y se ha validado con `./gradlew :app:assembleDebug`.

### Stack inicial del cliente Android

Estado: resuelta

Decisión:

- usar Kotlin con Jetpack Compose;
- usar Android Gradle Plugin 9.1.1;
- usar Gradle wrapper 9.3.1;
- usar Kotlin/Compose compiler 2.3.10;
- usar Compose BOM 2026.03.00;
- arrancar con navegación inferior `Mapa / Acciones / Usuario`;
- usar DataStore para persistir el token;
- usar un cliente HTTP propio con `HttpURLConnection` para la primera integración;
- mantener el mapa visual simulado hasta integrar un motor real OSM/MapLibre.

Motivo:

El objetivo inmediato era tener un cliente Android real, compilable y alineado con el MVP, conectando auth/registros sin introducir todavía una capa de red pesada.

### Motor de mapa Android

Estado: resuelta

Decisión:

- usar MapLibre Native Android como motor de mapa en la app;
- usar `org.maplibre.gl:android-sdk:13.0.2`;
- arrancar con el estilo público `https://demotiles.maplibre.org/style.json` para desarrollo;
- mantener OpenStreetMap como base cartográfica del MVP.

Motivo:

MapLibre permite mostrar un mapa móvil real sin depender de Google Maps ni de claves propietarias en esta fase. El estilo demo sirve para probar interacción y pines, aunque para producción habrá que elegir un proveedor de tiles o infraestructura propia.

### Corrección del estado documental del workspace

Estado: resuelta

Fecha: 2026-04-22 17:10 CEST

Decisión:

- el estado vigente es que sí existe código fuente real en este workspace;
- se corrigió `AGENTS.md` para no seguir diciendo que la carpeta está vacía;
- se actualizó `Contexto/Contexto.md` para dejar la nota de carpeta vacía como histórico;
- `ContextoGeneral.md` queda como resumen de entrada del estado actual.

Motivo:

El árbol real contiene `backend/`, `android/`, `analytics/` y `compose.yaml`. La nota antigua de arranque ya no describe el proyecto actual y podía inducir a decisiones incorrectas en sesiones futuras.

### Política de marcas temporales en contexto

Estado: resuelta

Fecha: 2026-04-22 17:10 CEST

Decisión:

- toda entrada nueva de contexto, decisión o registro de sesión debe incluir fecha y hora local;
- el formato preferido es `YYYY-MM-DD HH:MM CEST/CET`;
- si hay discrepancias futuras, se prioriza la entrada verificada con marca temporal más reciente, salvo que el árbol real contradiga esa entrada.

Motivo:

Evita confundir notas históricas con estado vigente y facilita saber qué documentación está más actualizada entre sesiones.

### Control de versiones del workspace

Estado: resuelta

Fecha: 2026-04-22 17:12 CEST; actualizado 2026-04-22 17:30 CEST

Decisión:

- inicializar Git en `/home/aviddrianimachie/CEAC/Proyecto`;
- usar rama principal `main`;
- añadir `.gitignore` raíz para excluir dependencias, builds, caches, entornos locales y secretos;
- configurar identidad Git local del proyecto como `dlimachii <dlimachi@icloud.com>`;
- crear commit inicial `6b07fce` con mensaje `Initial Plantaria baseline`;
- usar GitHub por SSH con remoto `git@github.com:dlimachii/Plantaria.git`;
- dejar `main` siguiendo a `origin/main`.

Motivo:

El proyecto ya tiene código real y necesita control de cambios. Inicializar Git permite usar `git status` y preparar una línea base sin arriesgarse a versionar `vendor/`, builds Android, `.env`, caches o ficheros generados.

### Exposición de fotos a cliente Android

Estado: resuelta

Fecha: 2026-04-22 18:16 CEST

Decisión:

- el backend incluye URLs públicas de fotos en los payloads de registros y observaciones;
- el cliente Android consume esas URLs para preview, ficha y timeline de observaciones;
- el cliente normaliza URLs con host `localhost`, `127.0.0.1` o `0.0.0.0` sustituyéndolas por la raíz de la API configurada.

Motivo:

En emulador y móvil físico, una URL generada como `localhost` apuntaría al propio dispositivo, no al backend Laravel. Normalizar desde la URL de API evita romper la demo local sin acoplar la UI a un host fijo.

### Ubicación real en mapa Android

Estado: resuelta

Fecha: 2026-04-22 18:30 CEST

Decisión:

- el mapa no solicita permisos automáticamente si todavía no existen;
- si el permiso ya está concedido, intenta centrar al entrar en la pantalla;
- si no está concedido, el usuario puede pulsar `Mi ubicación` para solicitar permiso y centrar;
- se muestra un marcador `Tu ubicación` además de los marcadores de registros.

Motivo:

Evita una solicitud invasiva de permisos al abrir la app, pero permite demostrar claramente que el mapa puede usar ubicación real cuando el usuario lo autoriza.

### Flujo de observaciones desde ficha Android

Estado: resuelta

Fecha: 2026-04-22 18:35 CEST

Decisión:

- la ficha de registro incluye una acción `Añadir observación`;
- al pulsarla se navega a `Acciones`;
- el formulario de observación recibe el `public_id` del registro y lo prellena;
- se mantiene el campo editable para permitir corrección manual o uso avanzado.

Motivo:

Evita que el usuario tenga que copiar IDs manualmente desde la ficha, reduce errores y conecta mejor el flujo principal de mapa con el seguimiento temporal.

### Panel web mínimo de moderación

Estado: resuelta

Fecha: 2026-04-22 18:45 CEST

Decisión:

- implementar un panel web Laravel bajo `/admin`;
- usar login de sesión web con handle o email;
- restringir acceso a roles `MOD` y `ADMIN`;
- priorizar dashboard, cola de registros pendientes, detalle de registro y verificación/rechazo;
- dejar flags, usuarios y analítica visual como ampliaciones posteriores.

Motivo:

El sistema necesita una forma defendible de validar reportes sin depender solo de endpoints API. Un panel mínimo cubre el flujo central de moderación del MVP y encaja con el alcance del TFC.

### Ampliación del panel web con flags y usuarios

Estado: resuelta

Fecha: 2026-04-22 19:07 CEST

Decisión:

- añadir una pantalla web `/admin/flags` para que `MOD` y `ADMIN` revisen denuncias y cambien su estado;
- añadir una pantalla web `/admin/users` para que solo `ADMIN` filtre usuarios y edite rol, estado y datos básicos;
- mantener la analítica visual como siguiente ampliación del panel, separada de la moderación operativa.

Motivo:

Flags y usuarios son funciones administrativas necesarias para que el panel no se limite a verificar registros. Separarlas por permisos mantiene claro el reparto entre moderación y administración.

### Buscador de mapa con geocodificación

Estado: resuelta

Fecha: 2026-04-23 16:33 CEST

Decisión:

- añadir un endpoint backend `/api/geocoding/search` que actúe como proxy cacheado a Nominatim;
- mantener la geocodificación fuera del cliente Android directo para no acoplar la app a un tercero ni exponer detalles de integración;
- ampliar el mapa Android con sugerencias de lugar, foco sobre coincidencias elegidas y recentrado directo por coordenadas;
- aceptar de forma básica consultas del tipo `planta en lugar`, usando filtro textual de registros y foco del mapa sobre la zona buscada;
- no introducir todavía filtro espacial real por bounding box o radio hasta modelarlo con más rigor.

Motivo:

La app necesitaba una búsqueda de lugares usable antes de la prueba en móvil físico. El proxy backend permite controlar caché, evolución futura y trazabilidad sin meter una integración frágil o duplicada en Android.

### Analítica visual del panel web

Estado: resuelta

Fecha: 2026-04-23 16:44 CEST

Decisión:

- ampliar el dashboard web existente en `/admin` con analítica visual server-rendered;
- mostrar actividad diaria, actividad por hora, top búsquedas y creadores destacados dentro del propio dashboard;
- evitar por ahora dependencias de gráficos en JavaScript y resolverlo con Blade + CSS;
- mantener la API analítica existente como base reutilizable, pero no obligar al panel a depender de peticiones AJAX para la demo.

Motivo:

La parte analítica ya estaba prevista para el TFC y era el siguiente bloque natural tras cerrar flags, usuarios y geocodificación. Resolverlo en render del servidor simplifica la demo, reduce superficie de fallo y deja un panel más defendible para presentación.

### Estrategia de tiles para producción

Estado: resuelta

Fecha: 2026-04-23 16:53 CEST

Decisión:

- no depender en producción ni del estilo demo de MapLibre ni de `tile.openstreetmap.org`;
- dejar la URL del estilo del mapa Android en configuración de build para poder cambiar de proveedor sin tocar el código Kotlin;
- mantener para desarrollo el estilo demo actual como valor por defecto de arranque;
- orientar el cierre técnico hacia vector tiles compatibles con MapLibre, con dos salidas válidas:
  - proveedor hospedado compatible con URL de estilo;
  - hosting propio futuro usando piezas del ecosistema MapLibre como `Martin` y/o `PMTiles`.

Motivo:

Los servidores comunitarios de OSM no son una base fiable ni pensada para este caso de uso de producción, y el estilo demo tampoco debe asumirse como solución final. La configuración por build reduce acoplamiento y deja una transición limpia hacia un proveedor real o hacia infraestructura propia.

### Edición avanzada de registros en el panel web

Estado: resuelta

Fecha: 2026-04-23 19:10 CEST

Decisión:

- ampliar `/admin/moderation/pending` para permitir filtro por estado y búsqueda por ID o nombre;
- permitir edición avanzada del registro desde su detalle web;
- limitar esa edición avanzada a `ADMIN`;
- mantener a `MOD` con flujo de verificación/rechazo, sin permisos de edición total;
- registrar la edición administrativa del registro como evento `record_updated`.

Motivo:

El siguiente bloque útil sin móvil físico era reforzar la operativa del panel web. Separar edición total y moderación mantiene coherencia con los roles definidos del TFC y deja una vía defendible para corregir datos demo o reales antes de la validación en dispositivo.

### Ajuste del mapa Android y de la subida de fotos para móvil real

Estado: resuelta

Fecha: 2026-04-23 19:50 CEST

Decisión:

- separar en Android la búsqueda de registros por planta/ID de la búsqueda de zona/coordenadas para mover el mapa;
- eliminar el preview automático del primer registro y dejar previews compactos, cerrables y sin pisar controles principales;
- diferenciar visualmente la ubicación del usuario frente a los registros y frente al foco de búsqueda;
- preparar/comprimir fotos en Android antes de subirlas;
- arrancar Laravel para prueba móvil con límites más altos de `upload_max_filesize` y `post_max_size`;
- ampliar la validación de subida backend para aceptar fotos de hasta `20 MB` en la ruta de pruebas móvil.

Motivo:

La primera prueba física parcial mostró dos problemas reales: la UX del mapa estaba mezclando intenciones distintas y el flujo de creación fallaba con imágenes de móvil por un límite de subida demasiado bajo. Era mejor corregir ambos puntos antes de seguir iterando sobre funcionalidades nuevas.

### Prioridad tras revisión integral del proyecto

Estado: resuelta

Fecha: 2026-04-24 16:53 CEST

Decisión:

- tratar el proyecto como MVP avanzado en fase de estabilización, no como fase temprana de scaffold;
- priorizar la revalidación física Android antes de añadir grandes funcionalidades;
- no abrir iOS, web pública completa, IA de identificación o geoespacial avanzado hasta cerrar el flujo móvil básico;
- usar el panel web actual como herramienta suficiente de moderación/admin para la primera defensa del TFC;
- dedicar el siguiente esfuerzo sin móvil a documentación visible, limpieza de README y preparación de memoria/capturas.

Motivo:

El árbol real ya contiene backend, Android, panel, analítica, datos demo y scripts. La deuda que queda no se resuelve añadiendo más alcance, sino demostrando que el flujo principal funciona en teléfono real y que el relato técnico coincide con lo implementado.

### Estado real de PostGIS en el MVP

Estado: resuelta

Fecha: 2026-04-24 16:53 CEST

Decisión:

- mantener PostgreSQL/PostGIS como decisión correcta de base de datos del proyecto;
- reconocer que el MVP activa PostGIS pero sigue guardando coordenadas como decimales normales;
- desde el 2026-04-24 17:07 CEST, sí existe una consulta geoespacial real mínima: filtro por radio en `/api/records` con `ST_DWithin` y `ST_Distance` cuando el driver es PostgreSQL;
- no afirmar en memoria o defensa que ya existen columnas espaciales persistentes o un motor geoespacial avanzado completo;
- presentar PostGIS como una base real ya usada en un caso concreto y preparada para evolucionar hacia índices/columnas espaciales si hiciera falta.

Motivo:

La elección de PostGIS es coherente con el producto y ahora tiene una prueba funcional acotada. Conviene evitar sobreprometer: el avance es defendible como filtro por radio, no como rediseño geoespacial completo.

### Filtro geoespacial mínimo antes de entrega

Estado: resuelta

Fecha: 2026-04-24 17:07 CEST

Decisión:

- implementar en `GET /api/records` los parámetros `latitude`, `longitude` y `radius_km`;
- usar PostGIS en PostgreSQL mediante `ST_DWithin` para filtrar y `ST_Distance` para devolver `distance_km`;
- mantener un fallback en memoria para sqlite, solo para que los tests automáticos sigan siendo rápidos y estables;
- limitar el alcance a una mejora API, sin cambiar todavía la UX móvil ni crear columnas espaciales persistentes.

Motivo:

Refuerza la defensa técnica de PostgreSQL/PostGIS con una mejora pequeña, verificable y sin abrir una migración amplia justo antes de la validación física Android.

### Documentación visible del repositorio

Estado: resuelta

Fecha: 2026-04-24 17:07 CEST

Decisión:

- añadir `README.md` en la raíz del repo con visión, estructura, arranque rápido, validación y pendientes reales;
- sustituir `backend/README.md`, que todavía era el genérico de Laravel, por instrucciones reales del backend de Plantaria;
- documentar filtros de `/api/records`, panel web, fotos, geocodificación, datos demo y comandos de validación;
- añadir `NOMINATIM_BASE_URL` y `NOMINATIM_USER_AGENT` a `.env.example`.

Motivo:

La documentación visible del repositorio ya forma parte de la calidad de entrega. Un README genérico de framework desentonaba con el estado real del MVP.

### Documentación de entrega para demo y memoria

Estado: resuelta

Fecha: 2026-04-24 17:26 CEST

Decisión:

- añadir `docs/GUIA_DEMO.md` para guiar la demostración del MVP;
- añadir `docs/CHECKLIST_VALIDACION_MOVIL.md` para cerrar la prueba física del APK con criterios claros;
- añadir `docs/MEMORIA_TFC.md` como base de redacción técnica para memoria o defensa;
- enlazar estos documentos desde el README raíz.

Motivo:

El proyecto ya está suficientemente avanzado como para necesitar material de presentación y validación, no solo código. Estos documentos ayudan a defender el alcance real y a no olvidar pruebas críticas en móvil.

### Validación integral repetible

Estado: resuelta

Fecha: 2026-04-24 17:29 CEST

Decisión:

- añadir `scripts/validate_project.sh`;
- ejecutar sintaxis de scripts, tests backend, build Android y smoke HTTP contra PostGIS cuando el servicio `postgis` esté activo;
- permitir saltar Android o PostGIS con variables de entorno para casos rápidos o máquinas sin Docker/SDK.

Motivo:

El proyecto ya tiene varias piezas y la validación manual se estaba repitiendo. Un script único reduce errores antes de demo o entrega y deja claro qué se considera validación local completa.

### Imágenes demo reproducibles

Estado: resuelta

Fecha: 2026-04-24 17:34 CEST

Decisión:

- hacer que `DatabaseSeeder` genere imágenes demo PNG bajo `storage/app/public/demo`;
- cambiar los registros demo de `.jpg` a `.png`;
- añadir un test feature que confirma que el seeder crea registros demo con imagen PNG;
- no versionar los binarios generados en `storage`, porque ya están cubiertos por el seeder.

Motivo:

Las rutas demo existían en base de datos, pero los ficheros no estaban en `storage/app/public`. Generarlos desde el seeder evita fotos rotas en Android y en el panel web después de una instalación limpia.

### Referencia API práctica

Estado: resuelta

Fecha: 2026-04-24 17:34 CEST

Decisión:

- añadir `docs/API.md` con endpoints, filtros, ejemplos de request y notas de autenticación;
- enlazarlo desde README raíz y README del backend.

Motivo:

La API ya tiene suficiente superficie como para necesitar una referencia propia. Ayuda a probar, explicar y defender el backend sin leer controladores.

### Bloqueo de tokens de cuentas no activas

Estado: resuelta

Fecha: 2026-04-24 17:41 CEST

Decisión:

- añadir middleware `active.user` a las rutas API autenticadas;
- mantener el bloqueo de login para usuarios baneados;
- impedir que tokens ya emitidos de cuentas baneadas o no activas sigan usando la API;
- cubrir la autorización API admin con tests específicos.

Motivo:

Banear un usuario no debe afectar solo a nuevos inicios de sesión. Si el usuario ya tenía token móvil, el backend debe cortar también el uso posterior de rutas autenticadas.

### Empaquetado para OneDrive

Estado: resuelta

Fecha: 2026-04-24 17:44 CEST

Decisión:

- añadir `scripts/package_for_onedrive.sh`;
- empaquetar fuente/documentación/scripts/lockfiles sin dependencias, builds ni secretos;
- crear aparte un `git bundle` para preservar historial comprometido;
- incluir APK debug si existe;
- omitir dump de base de datos por defecto, activable con `INCLUDE_DB_DUMP=1`;
- guardar por defecto en OneDrive CEAC si existe;
- generar `MANIFEST.txt` y `SHA256SUMS`.

Motivo:

OneDrive no debe sincronizar directamente `vendor`, `node_modules`, builds Android o `.git/` con miles de ficheros. Un paquete comprimido con manifest y checksums es más estable, más limpio y más fácil de restaurar.

## Dudas abiertas

### Cliente web público en el TFC

Estado: abierta

Pregunta práctica:

- si la entrega del TFC incluirá solo panel web administrativo o también una versión web pública del mapa para usuarios generales.

Impacto:

- cambia bastante el volumen de frontend;
- afecta al tiempo disponible para cerrar bien Android.

### Política exacta de moderación sobre actualizaciones

Estado: abierta

Pregunta práctica:

- si cada actualización temporal de una ficha debe pasar otra vez por verificación formal o si solo la creación inicial necesita validación fuerte.

Impacto:

- afecta al modelo de datos;
- afecta a la cola de pendientes y a la UX de usuario.

### Visibilidad pública del contenido pendiente

Estado: abierta

Pregunta práctica:

- si los reportes pendientes deben verse siempre en el mapa público o si debe haber un filtro o marca más restrictiva.

Impacto:

- cambia búsquedas, prioridades y riesgo de ruido o errores.

### Validación final del flujo móvil real

Estado: abierta

Fecha: 2026-04-24 16:53 CEST

Actualización: 2026-04-27 16:23 CEST

Nota:

- la validación local integral volvió a pasar con `./scripts/validate_project.sh`;
- `adb devices` no mostró ningún teléfono conectado, así que la validación física sigue pendiente;
- el proyecto está listo para instalar y probar el APK en móvil, pero no cerrado como validado en hardware real.

Pregunta práctica:

- si el APK reconstruido tras los cambios del 2026-04-23 19:50 CEST crea correctamente reportes y observaciones con fotos reales en el teléfono físico.

Impacto:

- es el bloqueo principal para considerar cerrado el MVP Android;
- afecta directamente a la demo porque combina red local, permisos, cámara, GPS, compresión y subida.

### Nivel de detalle botánico del primer corte

Estado: abierta

Pregunta práctica:

- cuánto se modelará del estado biológico de la planta en el MVP inicial.

Propuesta operativa:

- arrancar solo con estado visible simple de aspecto;
- dejar ciclos o taxonomía compleja para más adelante.

### Política de actualización del contexto

Estado: abierta

Pregunta práctica:

- cuánto detalle registrar tras cada cambio futuro.

Propuesta operativa:

- actualizar `RegistroDeSesiones.md` en cada cambio relevante;
- tocar `ContextoGeneral.md`, `ContextoEspecifico.md` o `EntornoYVersiones.md` solo cuando cambie de verdad el alcance, arquitectura o entorno;
- mover aquí cualquier duda cerrada para que no vuelva a abrirse innecesariamente.
