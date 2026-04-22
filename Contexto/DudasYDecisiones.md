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
