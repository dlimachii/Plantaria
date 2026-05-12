# 100 preguntas posibles de defensa sobre Plantaria

Este documento recopila preguntas que podrían aparecer en la defensa del proyecto y respuestas orientativas. La idea no es memorizar palabra por palabra, sino tener claro el criterio técnico detrás de cada decisión.

## Arquitectura y visión general

1. **¿Cómo resumirías Plantaria en pocas frases?**

   Plantaria es una plataforma para registrar, consultar y moderar plantas geolocalizadas desde una app Android. El sistema combina una aplicación móvil, una API Laravel, una base de datos PostgreSQL/PostGIS, un panel web de administración y scripts de analítica. No es solo una app local: funciona como un servicio conectado donde el móvil consume datos del backend y el administrador controla calidad, usuarios, moderación y métricas.

2. **¿Por qué separaste el proyecto en Android, backend, analítica y despliegue?**

   Lo separé porque cada parte tiene una responsabilidad clara. Android se centra en la experiencia del usuario, Laravel en la lógica de negocio y la API, PostgreSQL/PostGIS en persistencia y consultas geográficas, y los scripts de analítica en procesar datos para el panel. Esta separación hace que el proyecto sea más mantenible y evita mezclar interfaz, servidor, datos e infraestructura en un único bloque difícil de controlar.

3. **¿Cuál es la decisión arquitectónica más importante del proyecto?**

   La decisión más importante fue usar una arquitectura cliente-servidor con API REST. Esto permite que la app Android no guarde toda la información de forma aislada, sino que consulte y actualice un backend centralizado. Gracias a eso hay usuarios, moderación, registros compartidos, fotos, actividad y un panel administrativo, algo que no sería viable con una aplicación puramente local.

4. **¿Por qué no hiciste todo dentro de la app móvil?**

   Porque Plantaria necesita datos compartidos entre usuarios y control administrativo. Si todo estuviera solo en el móvil, cada usuario tendría su propia base aislada, no habría moderación centralizada ni sincronización real. El backend permite validar datos, gestionar autenticación, almacenar fotos, controlar permisos y mantener una fuente común de información.

5. **¿Qué ventajas te aporta tener un backend propio?**

   Un backend propio me da control sobre reglas de negocio, seguridad, estructura de datos y evolución del proyecto. Puedo decidir cómo se crean registros, cómo se validan fotos, qué usuarios pueden moderar y qué datos se devuelven a Android. También facilita añadir un panel web, analítica y futuras integraciones sin depender de un servicio cerrado externo.

6. **¿Por qué usaste una API REST y no otra alternativa como GraphQL?**

   REST encaja bien porque el proyecto tiene recursos claros: usuarios, registros, observaciones, fotos, flags y actividad. Para un TFG es una opción más directa, estándar y fácil de probar con herramientas comunes. GraphQL podría ser útil en un sistema con consultas mucho más dinámicas, pero aquí REST permite un contrato simple entre Android y Laravel sin añadir complejidad innecesaria.

7. **¿Qué papel cumple cada carpeta principal del código?**

   `backend/` contiene Laravel, la API, el panel admin, modelos, controladores, validaciones y tests. `android/` contiene la app nativa en Kotlin y Jetpack Compose. `analytics/` agrupa scripts Python/pandas para generar métricas del panel. `deploy/vps/`, `docs/` y `scripts/` completan el proyecto con despliegue de referencia, documentación y automatización de instalación o validación.

8. **¿Cómo justificas que el proyecto sea suficientemente completo para un TFG?**

   Es completo porque cubre varias capas reales de desarrollo: app móvil, backend, base de datos geoespacial, autenticación, fotos, mapas, moderación, panel administrativo, analítica, despliegue y documentación. No se queda en una maqueta visual, sino que implementa flujos funcionales de registro, login, consulta, creación de reportes, subida de imágenes y gestión administrativa.

9. **¿Cuál sería el flujo principal de un usuario en Plantaria?**

   El usuario se registra o inicia sesión, consulta el mapa o listado de plantas, crea un reporte con foto y ubicación, y puede añadir observaciones posteriores. La app se comunica con la API para subir fotos, guardar coordenadas y recuperar registros. Después, desde el panel, un moderador o administrador puede revisar la calidad del contenido.

10. **¿Cuál sería el flujo principal de un administrador?**

   El administrador accede al panel web, revisa métricas, usuarios, registros pendientes, flags e información operativa. Puede validar o rechazar registros, gestionar usuarios y consultar datos administrativos de forma controlada. También puede ver información de recursos del servidor y estimación de huella de carbono, que aporta una capa de seguimiento operativo.

11. **¿Qué querías demostrar técnicamente con Plantaria?**

   Quería demostrar que era capaz de construir una solución conectada completa, no solo una pantalla o una API aislada. El proyecto demuestra integración entre Android, Laravel, base de datos, mapas, fotos, autenticación, despliegue y documentación. También muestra criterio al separar responsabilidades, validar entradas y pensar en mantenimiento.

12. **¿Qué diferencia hay entre un registro y una observación?**

   Un registro representa una planta identificada o reportada inicialmente, con datos principales como nombre provisional, ubicación, estado y foto. Una observación es una actualización o aporte posterior asociado a ese registro, por ejemplo otra foto, una nota o el estado de la planta en otro momento. Esta separación permite seguir la evolución de una planta sin duplicar todo como si fueran registros independientes.

13. **¿Por qué tiene sentido que Plantaria tenga moderación?**

   Porque una plataforma colaborativa puede recibir datos incorrectos, duplicados o malintencionados. La moderación permite revisar registros, flags y usuarios para mantener una calidad mínima de información. En un proyecto relacionado con biodiversidad, la fiabilidad del dato es importante, aunque no se pretenda sustituir herramientas científicas profesionales.

14. **¿Qué parte del proyecto consideras más crítica?**

   La integración entre Android, API y base de datos es la parte más crítica, porque si falla no existe el flujo real de uso. El usuario puede tener una interfaz correcta, pero si no sube fotos, no autentica, no guarda coordenadas o no recupera registros, el sistema pierde su utilidad. Por eso se probaron especialmente login, endpoints, mapa, fotos y creación de reportes.

15. **¿Qué parte del proyecto escalaría peor si creciera mucho?**

   Probablemente la gestión de imágenes, las consultas geográficas y el volumen de actividad serían los puntos a vigilar. Con muchos usuarios habría que optimizar almacenamiento, paginación, índices geoespaciales, cache y quizá almacenamiento externo para fotos. La arquitectura permite crecer, pero una versión de producción real con miles de usuarios requeriría más monitorización y decisiones de infraestructura.

## Backend Laravel y API

16. **¿Por qué elegiste Laravel para el backend?**

   Laravel ofrece una base muy completa para construir APIs, autenticación, validación, rutas, migraciones, seeders, tests y panel web. Para este proyecto era importante avanzar con una tecnología productiva y estructurada. Además, su ecosistema permite trabajar de forma ordenada con controladores, modelos, FormRequest, middleware y comandos Artisan.

17. **¿Qué responsabilidades tiene Laravel en Plantaria?**

   Laravel gestiona la API REST consumida por Android, la autenticación con tokens, la persistencia de usuarios, registros, observaciones, fotos, flags y eventos. También contiene el panel administrativo, la moderación, validaciones, rate limiting, generación de analítica y servicios internos como el snapshot de huella de carbono. Es el núcleo de la lógica de negocio.

18. **¿Por qué usaste Laravel Sanctum?**

   Sanctum encaja bien para una app móvil que necesita autenticarse mediante tokens Bearer. Permite emitir tokens al hacer login y proteger rutas autenticadas sin depender de sesiones web tradicionales. Además, es una solución integrada en Laravel, sencilla de mantener y suficiente para el nivel de seguridad requerido por el proyecto.

19. **¿Cómo funciona la autenticación entre Android y backend?**

   Android envía credenciales al endpoint de login y Laravel devuelve un token. Ese token se guarda en la app y se envía en las peticiones autenticadas usando la cabecera `Authorization: Bearer`. El backend valida el token, comprueba que el usuario siga activo y decide si puede acceder a la ruta solicitada.

20. **¿Qué pasa si se bloquea un usuario que ya tenía token?**

   El backend incluye control para que las cuentas no activas no puedan seguir usando la API aunque tuvieran un token emitido previamente. Esto es importante porque en una plataforma con moderación no basta con impedir nuevos logins: también hay que cortar el acceso a tokens existentes si el usuario queda bloqueado.

21. **¿Por qué usaste FormRequest en Laravel?**

   FormRequest permite centralizar la validación de entradas en clases específicas en lugar de dispersarla por los controladores. Esto mejora la limpieza del código y hace más fácil revisar qué datos acepta cada endpoint. También ayuda a aplicar reglas coherentes para registro, login, creación de reportes, observaciones, perfil o moderación.

22. **¿Qué ventaja tienen las migraciones en este proyecto?**

   Las migraciones dejan documentada y versionada la estructura de la base de datos. Si otra persona instala el proyecto, puede reconstruir tablas, relaciones y extensiones necesarias ejecutando comandos de Laravel. Esto es especialmente útil en un TFG porque el código entregado debe poder entenderse y reproducirse sin depender de una base de datos manual creada a ojo.

23. **¿Qué papel tienen los seeders?**

   Los seeders permiten cargar datos iniciales y cuentas de prueba por rol. En Plantaria sirven para crear usuarios demo, moderador y administrador, además de registros de ejemplo alrededor de Barcelona. Esto facilita validar la app y enseñar el proyecto sin tener que introducir todos los datos manualmente cada vez.

24. **¿Por qué separaste rutas API y rutas web?**

   Las rutas API están pensadas para Android y devuelven JSON, mientras que las rutas web sirven para el panel administrativo en navegador. Separarlas evita confundir contratos y responsabilidades. También facilita aplicar middleware, autenticación y respuestas distintas según si la petición viene de la app o del panel.

25. **¿Qué endpoints son fundamentales en la API?**

   Los endpoints fundamentales son registro, login, usuario actual, listado de registros, detalle de registro, subida de fotos, creación de reportes, observaciones, perfil, actividad y flags. Con ellos se cubre el ciclo principal de usuario: autenticarse, consultar plantas, aportar información y reportar contenido problemático.

26. **¿Por qué la subida de fotos se hizo como endpoint separado?**

   Separar la subida de fotos simplifica el flujo y evita mezclar multipart con toda la creación del reporte. Primero se sube la imagen, el backend devuelve una ruta, y luego esa ruta se usa al crear el registro u observación. Esto hace más clara la responsabilidad de cada endpoint y facilita gestionar errores de subida sin perder todo el formulario.

27. **¿Cómo controlas que la API no acepte cualquier dato?**

   La API aplica validaciones mediante FormRequest, enums y reglas de negocio. Por ejemplo, se controlan campos obligatorios, formatos, coordenadas, estados permitidos y tipos de flags. Además, las rutas autenticadas requieren token y algunas acciones administrativas requieren rol adecuado.

28. **¿Qué utilidad tienen los enums del backend?**

   Los enums evitan trabajar con cadenas arbitrarias repartidas por el código. Estados como rol de usuario, estado de verificación, condición de planta o tipo de flag quedan acotados a valores válidos. Esto reduce errores, mejora legibilidad y hace que el dominio del proyecto sea más explícito.

29. **¿Por qué registras actividad de usuario?**

   La actividad permite mostrar al usuario sus acciones recientes y también aporta trazabilidad. En una plataforma colaborativa es útil saber qué reportes, observaciones, flags o acciones administrativas se han realizado. Además, ayuda a construir un panel más informativo y a entender el uso real del sistema.

30. **¿Qué aporta el panel administrativo frente a gestionar todo por base de datos?**

   El panel permite que un moderador o administrador trabaje sin acceder directamente a la base de datos. Esto es más seguro y más usable, porque las acciones se limitan a interfaces preparadas: revisar registros, validar contenido, gestionar usuarios o ver métricas. Acceder directamente a la base quedaría reservado a mantenimiento técnico, no a operación diaria.

31. **¿Qué diferencia hay entre moderador y administrador?**

   El moderador está orientado a revisar contenido y gestionar cola de moderación o flags. El administrador tiene más capacidad, como gestión avanzada de usuarios, registros y herramientas internas. Esta jerarquía evita dar permisos excesivos a todos los perfiles y refleja un modelo más realista de control de acceso.

32. **¿Por qué incluiste un asistente administrativo de consultas?**

   El asistente sirve como apoyo para consultar información administrativa de forma más cómoda. Aun así, se diseñó con límites: las consultas directas son de solo lectura y no deben permitir modificar la base de datos. La idea es demostrar una utilidad operativa sin comprometer la seguridad del sistema.

33. **¿Por qué el SQL administrativo es de solo lectura?**

   Porque permitir escritura libre desde un asistente o panel sería demasiado arriesgado. Una consulta mal formulada podría borrar datos, modificar usuarios o romper consistencia. Al limitarlo a lectura se mantiene una herramienta útil para análisis sin abrir una vía peligrosa de administración.

34. **¿Qué hace el servicio `ServerFootprintSnapshot`?**

   Ese servicio obtiene una instantánea de recursos del servidor y estima consumo energético y CO2 de forma orientativa. No pretende ser una medición certificada, sino una métrica de seguimiento para enseñar impacto operativo. Encaja en el dashboard porque complementa usuarios, registros y actividad con una dimensión de sostenibilidad.

35. **¿Por qué incluiste huella de carbono en el panel?**

   Porque el proyecto trata tecnología aplicada al medio ambiente, y tenía sentido que la propia plataforma también mostrara conciencia sobre recursos digitales. La huella de carbono aporta una reflexión sobre CPU, memoria, disco, energía estimada y CO2. Es una forma de conectar el tema ambiental con la infraestructura real que sostiene el servicio.

## Android, interfaz y experiencia de usuario

36. **¿Por qué elegiste Kotlin para Android?**

   Kotlin es el lenguaje moderno recomendado para Android y ofrece una sintaxis más segura y expresiva que Java en muchos casos. Permite trabajar bien con null-safety, data classes y corrutinas. Para una app conectada como Plantaria, ayuda a organizar modelos, estado y llamadas a API de forma clara.

37. **¿Por qué usaste Jetpack Compose?**

   Jetpack Compose permite construir interfaces declarativas y reactivas. En lugar de mantener XML y lógica separada de forma más rígida, la UI se actualiza según el estado de la aplicación. Para Plantaria era útil porque hay pantallas con login, mapa, formularios, actividad y perfil que dependen de datos cargados desde el backend.

38. **¿Qué estructura tiene la app Android?**

   La app se organiza en capas de API, sesión, estado y pantallas. El cliente HTTP se comunica con Laravel, `SessionStore` mantiene la sesión, el ViewModel coordina estado y acciones, y las pantallas Compose muestran mapa, acciones y usuario. Esta estructura evita que la interfaz tenga que conocer detalles internos de red o persistencia.

39. **¿Qué papel tiene el ViewModel?**

   El ViewModel actúa como punto intermedio entre la UI y los datos. Centraliza llamadas a la API, estado de carga, errores, sesión, registros y acciones del usuario. Esto hace que las pantallas Compose sean más limpias y que el comportamiento de la app sea más fácil de probar y mantener.

40. **¿Por qué guardas el token en DataStore?**

   DataStore es una solución moderna para persistir datos simples en Android de forma segura y asíncrona frente a alternativas antiguas como SharedPreferences. En Plantaria se usa para mantener sesión y configuración del servidor. Así el usuario no tiene que iniciar sesión cada vez que abre la app.

41. **¿Por qué permites cambiar manualmente la URL del backend en la pantalla de acceso?**

   Aunque la build `prod` apunta a `https://api.dlimachii.com/api/`, durante pruebas puede ser útil usar backend local, emulador, túnel o móvil físico. Permitir cambiar la URL facilita validación sin recompilar. Aun así, la variante final prioriza la URL de producción para que la entrega sea coherente con el entorno real usado.

42. **¿Por qué la app descarta URLs antiguas como `127.0.0.1` o `10.0.2.2`?**

   Porque esas URLs son útiles en desarrollo, pero en una build final podrían dejar la app apuntando a un entorno que ya no existe. Si un móvil tenía guardada una dirección local, la app puede volver a la URL base configurada. Esto reduce errores al probar la versión final en dispositivos reales.

43. **¿Por qué usaste navegación inferior?**

   La navegación inferior encaja con una app móvil que tiene pocas áreas principales y se usa de forma repetida. Plantaria se organiza en mapa, acciones y usuario, que son tres flujos claros. Esta jerarquía permite que el usuario cambie rápido entre consultar información, crear contenido y revisar su perfil.

44. **¿Por qué la pestaña principal es el mapa?**

   El mapa representa la parte diferencial del proyecto: plantas vinculadas a ubicaciones reales. Si la app empezara por un listado genérico, perdería fuerza la idea de biodiversidad cercana y observaciones geolocalizadas. El mapa ayuda a entender visualmente dónde están los registros y cómo se relacionan con el entorno del usuario.

45. **¿Por qué añadiste una alternativa de visión de mapa?**

   Porque distintos usuarios pueden preferir estilos de mapa diferentes o necesitar una base más clara según el contexto. El selector permite alternar entre el estilo configurado y una alternativa OSM estándar sin cambiar el código. Es una mejora pequeña, pero demuestra que se pensó en usabilidad y flexibilidad.

46. **¿Por qué usaste MapLibre?**

   MapLibre es una alternativa open source para mapas interactivos y encaja con la filosofía del proyecto de usar tecnologías abiertas. Permite mostrar mapas, marcadores y estilos configurables en Android. Además, evita depender totalmente de soluciones cerradas o con condiciones comerciales más restrictivas.

47. **¿Qué aporta OpenStreetMap al proyecto?**

   OpenStreetMap aporta una base cartográfica abierta y ampliamente utilizada. Para Plantaria tiene sentido porque el proyecto trabaja con entorno natural, ubicaciones y participación ciudadana. Usar mapas abiertos refuerza la idea de colaboración y reduce dependencia de proveedores comerciales.

48. **¿Cómo gestionas permisos de ubicación en Android?**

   La app solicita permisos cuando necesita usar la ubicación actual, por ejemplo al crear reportes u observaciones. No se debe asumir que el usuario siempre concede el permiso, así que la interfaz tiene que permitir introducir o conservar coordenadas de otra forma. Esto evita que la app quede inutilizable si el permiso se deniega.

49. **¿Por qué incluiste cámara y selector de imagen?**

   Porque una planta reportada solo con texto y coordenadas tendría menos valor. La foto ayuda a identificar la especie, validar el registro y aportar evidencia visual. Permitir cámara directa y Photo Picker cubre dos usos reales: fotografiar en el momento o elegir una imagen ya tomada.

50. **¿Qué papel tiene FileProvider en la cámara?**

   FileProvider permite compartir de forma controlada un URI temporal con la cámara sin exponer rutas internas del sistema de archivos. Es la forma correcta de manejar captura de fotos en Android moderno. En Plantaria se usa para guardar primero la imagen en caché y después subirla al backend.

51. **¿Por qué separaste la pantalla de Acciones del mapa?**

   Crear reportes u observaciones implica formularios, fotos, estado de la planta y ubicación. Separarlo del mapa evita sobrecargar la pantalla principal y permite un flujo más claro. El mapa queda para exploración y la pestaña de acciones para aportar información.

52. **¿Qué muestra la pestaña Usuario?**

   Muestra información de sesión, perfil, rol, cierre de sesión y actividad reciente propia. Esto ayuda al usuario a comprobar con qué cuenta está trabajando y qué acciones ha realizado. También refuerza la idea de plataforma con identidad, no solo de mapa anónimo.

53. **¿Por qué la actividad propia no muestra todos los registros cargados?**

   Porque actividad propia y listado de registros son conceptos distintos. La actividad muestra lo que ha hecho esa cuenta: reportes, observaciones, flags o acciones relevantes. El listado de registros muestra datos disponibles en la plataforma, aunque los haya creado otro usuario.

54. **¿Cómo pensaste la usabilidad para usuarios no técnicos?**

   Intenté que las acciones principales fueran directas: iniciar sesión, ver mapa, crear reporte, subir foto y consultar perfil. El usuario no necesita entender Laravel, PostGIS o tokens; solo interactúa con pantallas y formularios. La complejidad técnica queda detrás de la API y del panel administrativo.

55. **¿Qué mejorarías de la interfaz si tuvieras más tiempo?**

   Mejoraría el diseño visual, mensajes de error, estados vacíos y accesibilidad. También añadiría filtros más cómodos, clustering más avanzado, modo offline parcial y una ficha de planta más rica. La base funcional ya existe, pero una app pública necesitaría más pulido de experiencia de usuario.

## Base de datos, datos geográficos y dominio

56. **¿Por qué elegiste PostgreSQL?**

   PostgreSQL es una base de datos robusta, libre y muy usada en entornos profesionales. Permite trabajar bien con relaciones, consultas complejas y consistencia de datos. Además, su integración con PostGIS la hacía especialmente adecuada para un proyecto basado en coordenadas y búsquedas geográficas.

57. **¿Qué aporta PostGIS?**

   PostGIS añade capacidades geoespaciales a PostgreSQL. En Plantaria permite filtrar registros por radio y calcular distancias usando funciones como `ST_DWithin` y `ST_Distance`. Esto es más correcto y escalable que tratar latitud y longitud como simples números sin soporte espacial.

58. **¿Por qué no guardaste solo latitud y longitud sin PostGIS?**

   Se podrían guardar como números, pero perdería capacidad geográfica avanzada y eficiencia para búsquedas por zona. PostGIS permite consultar registros cercanos de forma más adecuada y preparada para crecer. Para una app donde la ubicación es central, usar una extensión geoespacial tiene sentido técnico.

59. **¿Qué entidades principales tiene la base de datos?**

   Las entidades principales son usuarios, registros de plantas, observaciones, flags de moderación, tokens y eventos de actividad. Cada una responde a un bloque funcional del sistema: identidad, contenido, evolución temporal, control de calidad y trazabilidad. Esta estructura refleja el dominio real de la aplicación.

60. **¿Por qué usaste IDs públicos para registros?**

   Los IDs públicos evitan exponer directamente identificadores internos de base de datos. También permiten tener referencias más estables y presentables en la API o en flags. Es una decisión útil para separar la implementación interna de lo que ve el cliente Android o el panel.

61. **¿Cómo evitas inconsistencias entre registros y observaciones?**

   Las observaciones se asocian a un registro existente y se validan antes de guardarse. La base de datos mantiene relaciones y Laravel controla que los datos tengan formato correcto. Además, la moderación puede revisar contenido si aparecen datos incorrectos o conflictivos.

62. **¿Por qué hay estados como `pending`, `verified` o `rejected`?**

   Esos estados permiten gestionar la calidad del contenido. Un registro creado por un usuario puede quedar pendiente hasta que un moderador lo revise. Después puede verificarse si es válido o rechazarse si no cumple criterios mínimos, evitando mezclar contenido fiable y no revisado.

63. **¿Qué importancia tiene la condición de la planta?**

   La condición aporta información adicional sobre el estado observado: buena, regular, mala, seca o desconocida. No sustituye la identificación botánica, pero ayuda a describir la situación real de la planta en ese momento. En observaciones posteriores permite seguir cierta evolución temporal.

64. **¿Por qué hay fallback para SQLite en tests?**

   Algunas pruebas pueden ejecutarse más rápido usando SQLite, pero SQLite no soporta PostGIS. Por eso se implementa una alternativa matemática para mantener la suite de tests funcional sin depender siempre de PostgreSQL. En producción o desarrollo real con PostgreSQL se usan las funciones geoespaciales correctas.

65. **¿Qué índices o mejoras de base de datos añadirías si creciera el proyecto?**

   Añadiría índices geoespaciales adecuados, índices por estado, usuario, fecha y campos de búsqueda frecuentes. También revisaría paginación, consultas del dashboard y almacenamiento de eventos. Si hubiera muchas fotos, separaría almacenamiento de archivos en un servicio especializado y dejaría la base solo para metadatos.

## Seguridad, permisos y moderación

66. **¿Qué medidas de seguridad aplicaste?**

   Apliqué autenticación con tokens Sanctum, validación de entradas, rutas protegidas, roles, control de usuarios activos, CORS configurable, rate limiting y consultas administrativas de solo lectura. No es una auditoría de seguridad completa, pero sí cubre medidas razonables para un proyecto académico con backend real.

67. **¿Por qué es importante el rate limiting?**

   El rate limiting reduce abuso en rutas sensibles como login, geocodificación, uploads o panel administrativo. Sin límites, alguien podría intentar fuerza bruta, saturar subida de fotos o lanzar muchas consultas. Es una medida sencilla que mejora la resistencia del sistema.

68. **¿Qué es CORS y por qué lo configuraste?**

   CORS controla qué orígenes web pueden hacer peticiones al backend desde navegador. Aunque Android no depende igual de CORS, el panel y posibles frontends web sí pueden verse afectados. Configurarlo por entorno permite desarrollo local y producción sin abrir el backend innecesariamente a cualquier dominio.

69. **¿Por qué no se sube el archivo `.env` al repositorio?**

   El `.env` contiene credenciales, claves, contraseñas y configuración sensible. Subirlo a Git sería un riesgo porque cualquiera con acceso al repositorio podría ver datos privados. Por eso se incluye `.env.example` como plantilla y las credenciales reales se configuran fuera del código.

70. **¿Qué otros archivos no deberían subirse a Git?**

   No deberían subirse dependencias generadas, builds, logs, storage público, bases locales, `.gradle`, `vendor`, `node_modules`, `local.properties` o archivos con secretos. El repositorio debe contener código fuente, configuración reproducible y documentación, no artefactos pesados ni datos privados.

71. **¿Cómo proteges las rutas administrativas?**

   Las rutas administrativas requieren autenticación y roles adecuados. No todos los usuarios pueden entrar al panel ni realizar acciones de moderación o administración. Esta separación evita que una cuenta normal pueda validar registros, gestionar usuarios o acceder a información sensible.

72. **¿Qué sentido tienen los flags o denuncias?**

   Los flags permiten que usuarios reporten contenido problemático. En una plataforma colaborativa, los administradores no pueden revisar todo de forma inmediata, así que las denuncias ayudan a detectar registros, observaciones o usuarios que requieren atención. Es una herramienta básica de control comunitario.

73. **¿Cómo responderías si te preguntan si Plantaria cumple RGPD?**

   Diría que el proyecto aplica criterios básicos de protección, como no publicar secretos, controlar acceso, validar datos y no exponer información innecesaria. Pero una puesta en producción real requeriría textos legales, consentimiento, política de privacidad, gestión formal de derechos y revisión jurídica. Para el TFG se trabaja la base técnica, no una certificación legal completa.

74. **¿Por qué no guardaste contraseñas de demo en el repositorio?**

   Porque aunque sean cuentas de prueba, las contraseñas siguen siendo credenciales. Dejarlas en Git crea malos hábitos y puede comprometer un despliegue si alguien las reutiliza. Es mejor definirlas mediante variables de entorno y documentar qué nombres deben configurarse.

75. **¿Qué harías si descubrieras una vulnerabilidad grave en producción?**

   Primero limitaría el impacto: desactivar ruta afectada, bloquear acceso o revocar tokens si fuera necesario. Después reproduciría el fallo, aplicaría parche, revisaría logs y validaría que no se repite. También documentaría la incidencia y cambiaría credenciales si hubiese riesgo de exposición.

## Analítica, datos y huella de carbono

76. **¿Por qué añadiste analítica con Python y pandas si ya tenías Laravel?**

   Laravel gestiona bien la aplicación, pero pandas es muy práctico para procesar datasets y generar resúmenes analíticos. Separar esta parte permite tratar la analítica como un proceso auxiliar que genera un JSON consumido por el panel. Es una solución razonable para KPIs sin cargar toda la lógica estadística dentro de controladores.

77. **¿Cómo funciona el flujo de analítica?**

   Laravel exporta datos a CSV, ejecuta el script Python con pandas y genera un archivo `admin_dashboard.json`. El panel administrativo consume ese snapshot para mostrar métricas. Esto evita recalcular todo en cada carga del dashboard y separa extracción, procesamiento y presentación.

78. **¿Por qué usas un snapshot JSON para el dashboard?**

   Porque el dashboard no necesita recalcular métricas pesadas en cada petición. Un snapshot JSON permite generar datos cuando se quiera actualizar y luego leerlos de forma rápida. Es una estrategia simple de cache operacional, adecuada para un proyecto de este tamaño.

79. **¿La huella de carbono que muestras es exacta?**

   No la presentaría como exacta ni certificada. Es una estimación orientativa basada en recursos del servidor y supuestos de consumo. Su valor principal es educativo y de seguimiento: mostrar que un servicio digital también consume recursos y que se puede incorporar esa dimensión al panel.

80. **¿Por qué hablar de sostenibilidad en una app tecnológica?**

   Porque Plantaria trata medio ambiente y biodiversidad, así que era coherente reflexionar también sobre el impacto de la infraestructura. La tecnología no es neutra: servidores, CPU, memoria, disco y red consumen energía. Incluir esa métrica demuestra una visión más completa del proyecto.

81. **¿Qué métricas mostrarías en una evolución futura del panel?**

   Añadiría evolución temporal de usuarios activos, registros por zona, especies más reportadas, tiempos de respuesta, errores de API, uso de almacenamiento y consumo real del servidor si se integra monitorización. También sería útil separar métricas ambientales de métricas puramente técnicas.

82. **¿Por qué no calculas analítica en tiempo real para todo?**

   Porque no siempre compensa. Para métricas administrativas, una actualización periódica puede ser suficiente y reduce carga sobre la base de datos. El tiempo real sería útil para alertas críticas, pero para resúmenes de uso o tendencias un snapshot es más simple y estable.

83. **¿Qué limitaciones tiene la analítica actual?**

   Depende de los datos disponibles y del momento en que se genera el snapshot. No sustituye a una plataforma completa de observabilidad ni a métricas de negocio avanzadas. Además, si el volumen creciera mucho habría que optimizar exportaciones, procesamiento y almacenamiento histórico.

84. **¿Cómo validarías que las métricas del dashboard son correctas?**

   Compararía los resultados del JSON con consultas directas a la base de datos y con casos de prueba conocidos. También usaría datos demo controlados para saber cuántos usuarios, registros o flags deberían aparecer. En producción, añadiría tests o comprobaciones automáticas para evitar regresiones.

85. **¿Qué sentido tiene que el panel mezcle moderación, usuarios, analítica y recursos?**

   El panel representa la operación del sistema. Un administrador no solo necesita revisar contenido, también necesita entender actividad, usuarios, estado general y recursos. Agrupar estas vistas en un panel único facilita supervisión y demuestra que el proyecto está pensado como servicio, no solo como app móvil.

## Despliegue, instalación y producción

86. **¿Por qué la build final apunta a `https://api.dlimachii.com/api/`?**

   Porque esa es la API de producción usada por la versión final del móvil. El objetivo de la entrega era reflejar el código real utilizado, no una demo desconectada. Aun así, el proyecto documenta cómo cambiar configuración para desarrollo local o despliegues nuevos.

87. **¿Por qué dejaste un script de instalación?**

   Porque un proyecto final debe ser más fácil de preparar por otra persona. El script instala dependencias de backend, Android y analítica, crea `.env` si falta, genera clave Laravel y prepara pasos comunes. No elimina la necesidad de configurar credenciales, pero reduce errores repetitivos.

88. **¿Qué dependencias necesita el proyecto?**

   Necesita PHP y Composer para Laravel, Node/npm para assets backend, PostgreSQL/PostGIS o Docker, Python con venv para analítica, JDK 17 y Android SDK para compilar la app. También puede usar ADB si se instala en móvil físico. El README documenta estos requisitos para reproducibilidad.

89. **¿Por qué usaste Docker Compose para PostGIS local?**

   Docker Compose facilita levantar PostgreSQL/PostGIS sin instalarlo manualmente en el sistema. Esto reduce diferencias entre entornos y hace más fácil preparar desarrollo o pruebas. Para un proyecto con base geoespacial, tener un servicio local reproducible es muy útil.

90. **¿Qué diferencia hay entre entorno local y producción?**

   En local se busca facilidad de desarrollo: `php artisan serve`, Docker para PostGIS y URLs como `127.0.0.1` o `10.0.2.2`. En producción importan dominio real, HTTPS, variables de entorno seguras, servidor persistente, Nginx/Caddy y configuración estable. La lógica de código es la misma, pero cambia la infraestructura.

91. **¿Por qué documentaste el despliegue si no tienes previsto volver a desplegarlo?**

   Porque el TFG debe dejar claro cómo funcionó la versión final y cómo podría reproducirse. Aunque no se despliegue de nuevo, documentar VPS, variables, comandos y estructura ayuda a evaluar el criterio técnico. También separa lo que se entrega como código de lo que queda fuera por seguridad, como credenciales y bases reales.

92. **¿Qué partes no se incluyen en la entrega final por seguridad o tamaño?**

   No se incluyen `.env`, credenciales, base de datos real, fotos reales de storage público, logs, dependencias generadas, builds Android ni carpetas como `vendor`, `node_modules` o `.gradle`. Eso no significa que falte código: significa que se entrega lo reproducible y se excluye lo sensible o regenerable.

93. **¿Cómo probarías rápidamente que el backend funciona?**

   Levantaría PostGIS, instalaría dependencias, configuraría `.env`, ejecutaría migraciones y seeders, y lanzaría `php artisan test`. Después probaría login, listado de registros y subida de fotos con la app o herramientas HTTP. El README y los scripts dejan estos pasos documentados.

94. **¿Cómo probarías rápidamente que Android funciona?**

   Compilaría la variante `prod` con Gradle y la instalaría en un móvil o emulador. Después comprobaría login, mapa, listado de registros, creación de reporte, foto y perfil. Si uso backend local, ajustaría la URL o configuraría `adb reverse` según el caso.

95. **¿Qué harías si la app móvil no conecta con el backend?**

   Revisaría primero la URL base, conectividad, HTTPS y si el backend está activo. Después comprobaría logs de Android, respuesta HTTP, CORS si aplica, token y formato JSON. También verificaría si el móvil apunta a producción o a una URL local antigua.

## Pruebas, calidad y mantenimiento

96. **¿Qué pruebas tiene el proyecto?**

   El backend incluye tests de autenticación, autorización, registros, fotos, moderación, panel admin, seguridad, actividad de usuario, geocoding y seeders. Estas pruebas validan flujos importantes y reducen riesgo de romper comportamiento al modificar código. Android se valida principalmente por compilación y pruebas manuales en dispositivo.

97. **¿Por qué hay más tests en backend que en Android?**

   El backend concentra reglas de negocio, permisos, validación y persistencia, que son más fáciles de automatizar y muy críticos para la integridad del sistema. Android tiene mucha interacción visual, permisos y hardware, por lo que se probó más de forma manual. En una evolución futura añadiría tests instrumentados y de UI.

98. **¿Qué significa para ti que el código sea mantenible?**

   Que otra persona pueda entender estructura, responsabilidades y puntos de cambio sin rehacer el proyecto. En Plantaria se busca mantenibilidad mediante carpetas claras, controladores, modelos, requests, servicios, documentación, scripts y README. También ayuda evitar secretos en Git y dejar comandos de instalación y validación.

99. **¿Qué refactorización harías si tuvieras más tiempo?**

   Revisaría componentes Android para separar todavía más UI, estado y servicios, y en backend estudiaría extraer algunos casos de uso a servicios más específicos. También mejoraría paginación, cache, manejo de errores y observabilidad. La idea sería preparar el proyecto para más usuarios sin cambiar su arquitectura base.

100. **¿Cuál sería la evolución profesional más razonable de Plantaria?**

   La evolución razonable sería mejorar identificación de especies, validación comunitaria, perfiles públicos, filtros geográficos avanzados, notificaciones, modo offline parcial y almacenamiento externo de imágenes. También incorporaría monitorización real, backups, políticas legales y un proceso formal de moderación. La base actual permite avanzar hacia eso porque ya existe app, API, datos geográficos, roles y panel administrativo.
