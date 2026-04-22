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

Actualización: 2026-04-22 18:30 CEST.

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
- pantalla de usuario con perfil y registros cargados;
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
- marcador de ubicación del usuario en el mapa.

Validación realizada:

- `./gradlew :app:assembleDebug` ejecutado correctamente;
- APK debug generado en `android/app/build/outputs/apk/debug/app-debug.apk`.

Pendiente:

- validar el flujo completo en móvil físico.

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
- `target_uid`;
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
