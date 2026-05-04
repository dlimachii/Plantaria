from __future__ import annotations

import re
from pathlib import Path
from zipfile import ZIP_DEFLATED, ZipFile
from xml.sax.saxutils import escape


ROOT = Path(__file__).resolve().parents[1]
TEMPLATE = ROOT / "DocumentoTFG" / "TFG DAM_DAW.docx"
OUTPUT = ROOT / "DocumentoTFG" / "Plantaria_TFG_DAM.docx"

W_NS = "http://schemas.openxmlformats.org/wordprocessingml/2006/main"
XML_NS = "http://www.w3.org/XML/1998/namespace"

SPANISH_REPLACEMENTS = [
    ("Introduccion", "Introducción"),
    ("Analisis", "Análisis"),
    ("Recopilacion", "Recopilación"),
    ("informacion", "información"),
    ("Informacion", "Información"),
    ("caracteristicas", "características"),
    ("Caracteristicas", "Características"),
    ("especificas", "específicas"),
    ("especificos", "específicos"),
    ("especifico", "específico"),
    ("segun", "según"),
    ("organizacion", "organización"),
    ("Organizacion", "Organización"),
    ("Identificacion", "Identificación"),
    ("priorizacion", "priorización"),
    ("Priorizacion", "Priorización"),
    ("mas", "más"),
    ("tecnologias", "tecnologías"),
    ("produccion", "producción"),
    ("aplicacion", "aplicación"),
    ("aplicaciones", "aplicaciones"),
    ("movil", "móvil"),
    ("moviles", "móviles"),
    ("camara", "cámara"),
    ("Autenticacion", "Autenticación"),
    ("autenticacion", "autenticación"),
    ("geografica", "geográfica"),
    ("geograficos", "geográficos"),
    ("geoespacial", "geoespacial"),
    ("analitica", "analítica"),
    ("Analitica", "Analítica"),
    ("automatico", "automático"),
    ("indice", "índice"),
    ("tecnica", "técnica"),
    ("Tecnica", "Técnica"),
    ("tecnico", "técnico"),
    ("tecnicos", "técnicos"),
    ("Diseno", "Diseño"),
    ("diseno", "diseño"),
    ("Definicion", "Definición"),
    ("intervencion", "intervención"),
    ("viabilidad tecnica", "viabilidad técnica"),
    ("planificacion", "planificación"),
    ("Planificacion", "Planificación"),
    ("ejecucion", "ejecución"),
    ("instalacion", "instalación"),
    ("actualizacion", "actualización"),
    ("construccion", "construcción"),
    ("Determinacion", "Determinación"),
    ("financiacion", "financiación"),
    ("evaluacion", "evaluación"),
    ("Evaluacion", "Evaluación"),
    ("atencion", "atención"),
    ("Deteccion", "Detección"),
    ("Programacion", "Programación"),
    ("Gestion", "Gestión"),
    ("prevencion", "prevención"),
    ("Coordinacion", "Coordinación"),
    ("supervision", "supervisión"),
    ("Asignacion", "Asignación"),
    ("Valoracion", "Valoración"),
    ("economica", "económica"),
    ("Elaboracion", "Elaboración"),
    ("Seguimiento", "Seguimiento"),
    ("Procedimiento", "Procedimiento"),
    ("solucion", "solución"),
    ("participacion", "participación"),
    ("Actividades", "Actividades"),
    ("Lineas", "Líneas"),
    ("actuacion", "actuación"),
    ("ejecucion", "ejecución"),
    ("autoevaluacion", "autoevaluación"),
    ("autonomia", "autonomía"),
    ("version", "versión"),
    ("credito", "crédito"),
    ("Sesion", "Sesión"),
    ("sesion", "sesión"),
    ("operacion", "operación"),
    ("operativo", "operativo"),
    ("publico", "público"),
    ("publica", "pública"),
    ("codigo", "código"),
    ("Codigo", "Código"),
    ("configuracion", "configuración"),
    ("repositorio", "repositorio"),
    ("modulo", "módulo"),
    ("Modulo", "Módulo"),
    ("migracion", "migración"),
    ("extension", "extensión"),
    ("conexion", "conexión"),
    ("validacion", "validación"),
    ("Validacion", "Validación"),
    ("funcion", "función"),
    ("funcional", "funcional"),
    ("ubicacion", "ubicación"),
    ("fotografia", "fotografía"),
    ("botanica", "botánica"),
    ("basica", "básica"),
    ("basico", "básico"),
    ("cronograma academico", "cronograma académico"),
    ("academico", "académico"),
    ("academica", "académica"),
    ("fisica", "física"),
    ("fisico", "físico"),
    ("telefono", "teléfono"),
    ("contrasena", "contraseña"),
    ("contrasena", "contraseña"),
    ("botanicas", "botánicas"),
    ("cientifico", "científico"),
    ("cientifica", "científica"),
    ("comun", "común"),
    ("accion", "acción"),
    ("Accion", "Acción"),
    ("descripcion", "descripción"),
    ("Descripcion", "Descripción"),
    ("imagenes", "imágenes"),
    ("imagenes", "imágenes"),
    ("permisos", "permisos"),
    ("proteccion", "protección"),
    ("Politicas", "Políticas"),
    ("politicas", "políticas"),
    ("revision", "revisión"),
    ("Revision", "Revisión"),
    ("produccion", "producción"),
    ("educacion", "educación"),
    ("organica", "orgánica"),
    ("tecnologica", "tecnológica"),
    ("tecnologicos", "tecnológicos"),
    ("comercio electronico", "comercio electrónico"),
    ("electronico", "electrónico"),
    ("transaccion", "transacción"),
    ("transacciones", "transacciones"),
    ("limitacion", "limitación"),
    ("limitaciones", "limitaciones"),
    ("publicacion", "publicación"),
    ("operacion", "operación"),
    ("Opcion", "Opción"),
    ("opcion", "opción"),
    ("Gestion", "Gestión"),
    ("guion", "guion"),
    ("Guion", "Guion"),
]


def attrs(values: dict[str, str | int | None]) -> str:
    rendered = []
    for key, value in values.items():
        if value is not None:
            rendered.append(f'{key}="{escape(str(value))}"')
    return (" " + " ".join(rendered)) if rendered else ""


def run(text: str, *, bold: bool = False, italic: bool = False, size: int | None = None) -> str:
    props = []
    if bold:
        props.append("<w:b/>")
    if italic:
        props.append("<w:i/>")
    if size:
        props.append(f'<w:sz w:val="{size}"/>')
        props.append(f'<w:szCs w:val="{size}"/>')
    rpr = f"<w:rPr>{''.join(props)}</w:rPr>" if props else ""
    space = ' xml:space="preserve"' if text[:1].isspace() or text[-1:].isspace() else ""
    return f"<w:r>{rpr}<w:t{space}>{escape(text)}</w:t></w:r>"


def paragraph(
    text: str = "",
    *,
    style: str | None = None,
    align: str | None = None,
    outline: int | None = None,
    bold: bool = False,
    italic: bool = False,
    size: int | None = None,
    before: int = 0,
    after: int = 160,
    keep_next: bool = False,
    indent_left: int | None = None,
) -> str:
    ppr_parts = []
    if style:
        ppr_parts.append(f'<w:pStyle w:val="{style}"/>')
    if keep_next:
        ppr_parts.append("<w:keepNext/>")
    if align:
        ppr_parts.append(f'<w:jc w:val="{align}"/>')
    if indent_left is not None:
        ppr_parts.append(f'<w:ind w:left="{indent_left}"/>')
    if before or after:
        ppr_parts.append(f'<w:spacing w:before="{before}" w:after="{after}" w:line="276" w:lineRule="auto"/>')
    if outline is not None:
        ppr_parts.append(f'<w:outlineLvl w:val="{outline}"/>')
    ppr = f"<w:pPr>{''.join(ppr_parts)}</w:pPr>" if ppr_parts else ""
    return f"<w:p>{ppr}{run(text, bold=bold, italic=italic, size=size) if text else ''}</w:p>"


def heading(level: int, text: str) -> str:
    if level == 1:
        return paragraph(text, style="Ttulo1", outline=0, bold=True, size=32, before=260, after=220, keep_next=True)
    if level == 2:
        return paragraph(text, outline=1, bold=True, size=28, before=220, after=160, keep_next=True)
    return paragraph(text, outline=2, bold=True, size=24, before=160, after=120, keep_next=True)


def bullet(text: str) -> str:
    return paragraph(f"- {text}", indent_left=360, after=80)


def page_break() -> str:
    return '<w:p><w:r><w:br w:type="page"/></w:r></w:p>'


def toc_field() -> str:
    return (
        "<w:p>"
        "<w:pPr><w:spacing w:before=\"0\" w:after=\"160\"/></w:pPr>"
        "<w:r><w:fldChar w:fldCharType=\"begin\" w:dirty=\"true\"/></w:r>"
        "<w:r><w:instrText xml:space=\"preserve\">TOC \\o \"1-3\" \\h \\z \\u</w:instrText></w:r>"
        "<w:r><w:fldChar w:fldCharType=\"separate\"/></w:r>"
        f"{run('Actualiza los campos en Word para generar el indice automatico.', italic=True)}"
        "<w:r><w:fldChar w:fldCharType=\"end\"/></w:r>"
        "</w:p>"
    )


def table(rows: list[list[str]], widths: list[int] | None = None) -> str:
    if not rows:
        return ""
    widths = widths or [int(9000 / len(rows[0]))] * len(rows[0])
    grid = "".join(f'<w:gridCol w:w="{width}"/>' for width in widths)
    body = []
    for row_index, row in enumerate(rows):
        cells = []
        for cell_index, cell in enumerate(row):
            width = widths[min(cell_index, len(widths) - 1)]
            cell_paragraph = paragraph(cell, bold=row_index == 0, after=60)
            cells.append(
                "<w:tc>"
                f"<w:tcPr><w:tcW w:w=\"{width}\" w:type=\"dxa\"/>"
                "<w:tcMar><w:top w:w=\"80\" w:type=\"dxa\"/><w:left w:w=\"80\" w:type=\"dxa\"/>"
                "<w:bottom w:w=\"80\" w:type=\"dxa\"/><w:right w:w=\"80\" w:type=\"dxa\"/></w:tcMar>"
                "</w:tcPr>"
                f"{cell_paragraph}"
                "</w:tc>"
            )
        body.append(f"<w:tr>{''.join(cells)}</w:tr>")
    return (
        "<w:tbl>"
        "<w:tblPr>"
        '<w:tblW w:w="0" w:type="auto"/>'
        "<w:tblBorders>"
        '<w:top w:val="single" w:sz="4" w:space="0" w:color="B7C2B7"/>'
        '<w:left w:val="single" w:sz="4" w:space="0" w:color="B7C2B7"/>'
        '<w:bottom w:val="single" w:sz="4" w:space="0" w:color="B7C2B7"/>'
        '<w:right w:val="single" w:sz="4" w:space="0" w:color="B7C2B7"/>'
        '<w:insideH w:val="single" w:sz="4" w:space="0" w:color="D9E2D9"/>'
        '<w:insideV w:val="single" w:sz="4" w:space="0" w:color="D9E2D9"/>'
        "</w:tblBorders>"
        "</w:tblPr>"
        f"<w:tblGrid>{grid}</w:tblGrid>"
        f"{''.join(body)}"
        "</w:tbl>"
    )


def code_block(lines: list[str]) -> list[str]:
    return [paragraph(line, size=20, after=40, indent_left=360) for line in lines]


def add_paragraphs(parts: list[str], paragraphs: list[str]) -> None:
    for item in paragraphs:
        parts.append(paragraph(item))


def build_document_xml() -> str:
    parts: list[str] = []

    parts.append(paragraph("Plantaria", align="center", bold=True, size=48, before=900, after=260))
    parts.append(paragraph("Trabajo Final de Grado - Desarrollo de Aplicaciones Multiplataforma", align="center", size=28, after=300))
    parts.append(paragraph("Memoria tecnica y organizativa del proyecto", align="center", italic=True, size=24, after=700))
    parts.append(table([
        ["Dato", "Valor"],
        ["Alumno/a", "[PENDIENTE: indicar nombre completo del alumno/a]"],
        ["Centro", "[PENDIENTE: indicar centro educativo]"],
        ["Tutor/a", "[PENDIENTE: indicar tutor/a del TFG]"],
        ["Convocatoria", "[PENDIENTE: indicar convocatoria y curso academico]"],
        ["Proyecto", "Plantaria"],
        ["Repositorio analizado", str(ROOT)],
        ["Fecha de generacion", "2026-05-04"],
    ], [2600, 6400]))
    parts.append(paragraph("Nota metodologica: este documento se ha redactado a partir del codigo, configuracion y scripts presentes en el repositorio. Cuando una cuestion depende de informacion externa no incluida en el codigo, se marca como pendiente.", italic=True, after=160))
    parts.append(page_break())

    parts.append(heading(1, "Tabla de contenidos"))
    parts.append(toc_field())
    parts.append(page_break())

    parts.append(heading(1, "1. Introduccion"))
    add_paragraphs(parts, [
        "Plantaria es una aplicacion colaborativa para registrar plantas geolocalizadas. El repositorio contiene un backend Laravel, una aplicacion Android nativa, una base PostgreSQL/PostGIS levantada con Docker Compose y un modulo auxiliar de analitica en Python.",
        "La finalidad tecnica del proyecto es permitir que un usuario cree reportes de plantas con fotografia y coordenadas, consulte los registros en un mapa, añada observaciones posteriores y permita que perfiles de moderacion o administracion validen la informacion botanica. Esta descripcion se apoya en los controladores, modelos, migraciones y pantallas existentes en el codigo.",
        "El proyecto se presenta como TFG/MVP. La intencion de crecimiento se entiende como posible aumento de usuarios, registros y utilidad comunitaria, no como explotacion economica. El codigo se plantea como publico y libre para uso, lectura, escritura y ejecucion.",
        "El MVP actual no intenta cubrir una aplicacion iOS, una web publica completa ni identificacion automatica de especies. Esas lineas no aparecen implementadas como producto final dentro del repositorio y, por tanto, se tratan como posibles ampliaciones futuras.",
    ])
    parts.append(table([
        ["Bloque", "Evidencia en el repositorio"],
        ["Backend", "backend/: Laravel 13, rutas API, panel web Blade, modelos Eloquent, tests feature."],
        ["Android", "android/: Kotlin, Jetpack Compose, MapLibre, DataStore, camara, galeria y localizacion."],
        ["Base de datos", "compose.yaml con postgis/postgis:16-3.5 y migraciones Laravel."],
        ["Analitica", "analytics/ y comando php artisan plantaria:analytics:build con pandas."],
        ["Automatizacion", "scripts/ con arranque local, instalacion de APK, validacion, perfilado y backup."],
    ], [2600, 6400]))

    parts.append(heading(1, "2. Analisis"))
    parts.append(heading(2, "2.1. Recopilacion de informacion"))
    add_paragraphs(parts, [
        "El analisis de este documento se limita a informacion verificable dentro del repositorio. La idea funcional queda representada por el dominio implementado: usuarios, registros de plantas, observaciones, denuncias de moderacion y eventos de uso.",
        "La carpeta Contexto/ describe el estado operativo del proyecto entre sesiones, pero las afirmaciones funcionales de esta memoria se contrastan con archivos ejecutables del repositorio: routes/api.php, routes/web.php, migraciones, controladores, modelos, Gradle, scripts y modulo de analitica.",
        "Para contextualizar el sector se han revisado plataformas reales de ciencia ciudadana, biodiversidad, identificacion vegetal, datos abiertos y cartografia colaborativa. Esta comparativa se usa como marco, no como afirmacion de que Plantaria ya tenga su escala o sus capacidades.",
    ])

    parts.append(heading(3, "2.1.1. Empresas del sector por sus caracteristicas organizativas y el tipo de producto o servicio que ofrecen"))
    add_paragraphs(parts, [
        "Plantaria se situa cerca de tres familias de plataformas: ciencia ciudadana de biodiversidad, identificacion vegetal mediante imagen y cartografia/datos abiertos. La diferencia del MVP es que se centra en una aplicacion Android propia con backend Laravel, seguimiento temporal de registros y panel de moderacion local.",
        "Las referencias mas cercanas son iNaturalist, Pl@ntNet, GBIF, Observation.org/ObsIdentify, Flora Incognita, PictureThis, PlantSnap y OpenStreetMap. Algunas son organizaciones sin animo de lucro o infraestructuras abiertas; otras son productos comerciales de identificacion y cuidado de plantas.",
    ])
    parts.append(table([
        ["Plataforma", "Tipo", "Relacion con Plantaria"],
        ["iNaturalist", "Organizacion sin animo de lucro y red social de biodiversidad", "Referencia directa por observaciones con foto, ubicacion, comunidad y datos utiles para ciencia."],
        ["Pl@ntNet", "Plataforma de ciencia ciudadana e identificacion vegetal con IA", "Referencia por foco botanico, app movil/web, observaciones revisables y base publica."],
        ["GBIF", "Infraestructura internacional de datos abiertos de biodiversidad", "Referencia por estandarizacion, acceso abierto y registros de presencia de especies."],
        ["Observation.org / ObsIdentify", "Plataforma y app de observaciones naturales", "Referencia por captura, validacion comunitaria y contribucion a investigacion biologica."],
        ["Flora Incognita", "App gratuita de identificacion vegetal y ciencia ciudadana", "Referencia por identificacion de plantas, fichas y guardado de observaciones."],
        ["PictureThis", "App comercial de identificacion y cuidado de plantas", "Referencia de mercado por identificacion por foto, diagnostico y guias de cuidado."],
        ["PlantSnap", "App comercial de identificacion y comunidad de plantas", "Referencia por reconocimiento de plantas, comunidad y enciclopedia."],
        ["OpenStreetMap", "Proyecto de datos cartograficos abiertos", "Referencia por filosofia abierta y por el uso de mapas/datos geograficos en servicios moviles."],
    ], [2200, 3000, 3800]))

    parts.append(heading(3, "2.1.2. Empresas tipo indicando la estructura organizativa y las funciones de cada departamento"))
    add_paragraphs(parts, [
        "Una organizacion tipo para una plataforma como Plantaria tendria, como minimo, desarrollo de software, administracion de sistemas y datos, moderacion/validacion de contenido, documentacion y soporte a usuarios. En el MVP esas funciones estan representadas tecnicamente, aunque el trabajo se presenta como individual.",
        "El codigo separa usuario final, moderador y administrador mediante UserRole. Esa division permite trasladar el producto a una estructura organizativa sencilla: los usuarios aportan datos, los moderadores revisan registros y flags, y los administradores gestionan usuarios, analitica y ajustes avanzados.",
    ])
    parts.append(table([
        ["Area tipo", "Funcion dentro de una plataforma como Plantaria"],
        ["Desarrollo backend", "API, autenticacion, reglas de negocio, persistencia, tests y panel web."],
        ["Desarrollo movil", "Interfaz Android, mapa, camara, galeria, GPS, sesion y consumo de API."],
        ["Datos y sistemas", "PostgreSQL/PostGIS, backups, despliegue, rendimiento y disponibilidad."],
        ["Moderacion/validacion", "Revision de registros, nombres comunes/cientificos, flags y calidad del dato."],
        ["Analitica", "Eventos de uso, informes pandas, tendencias y señales de riesgo."],
        ["Soporte/documentacion", "Guias de demo, checklist movil, referencia API y ayuda a usuarios."],
    ], [2700, 6300]))

    parts.append(heading(2, "2.2. Identificacion y priorizacion de necesidades"))
    add_paragraphs(parts, [
        "La necesidad principal que resuelve el codigo es disponer de un registro colaborativo de plantas donde la fotografia, la ubicacion y el seguimiento temporal esten conectados. La aplicacion Android permite crear reportes y observaciones, y el backend conserva la trazabilidad mediante plant_records, observations y app_events.",
        "La segunda necesidad es la calidad del dato. El codigo diferencia nombre provisional y nombre verificado, guarda estado de verificacion y expone flujos de moderacion para validar o rechazar registros.",
        "La tercera necesidad es operar con datos geograficos. PostgreSQL/PostGIS se levanta mediante compose.yaml y el endpoint GET /api/records admite busqueda por radio con ST_DWithin y ST_Distance cuando la conexion real es pgsql.",
    ])
    parts.append(table([
        ["Necesidad", "Respuesta implementada"],
        ["Registro colaborativo", "Creacion de reportes y observaciones desde Android y API."],
        ["Dato con trazabilidad", "Observaciones temporales, eventos de uso y estados de verificacion."],
        ["Consulta geografica", "Mapa Android con MapLibre y endpoint por radio con PostGIS."],
        ["Moderacion", "Panel web y API para validar registros, gestionar flags y usuarios."],
        ["Analitica", "Exportacion CSV desde Laravel y procesamiento con pandas."],
    ], [3200, 5800]))

    parts.append(heading(3, "2.2.1. Necesidades mas demandadas a las empresas"))
    add_paragraphs(parts, [
        "Las plataformas revisadas muestran varias necesidades comunes: identificar organismos a partir de fotografias, guardar observaciones, asociarlas a ubicaciones, aprender de la comunidad y reutilizar datos para ciencia o conservacion.",
        "Plantaria responde parcialmente a esas necesidades desde un MVP: no implementa IA de identificacion, pero si implementa captura fotografica, mapa, registros, observaciones temporales, moderacion y analitica. La demanda no se plantea aqui como venta, sino como utilidad social y tecnica de una herramienta abierta.",
    ])

    parts.append(heading(3, "2.2.2. Oportunidades de negocio previsibles en el sector"))
    add_paragraphs(parts, [
        "Plantaria se presenta como TFG/MVP, no como producto economico. La oportunidad previsible no consiste en monetizar usuarios, sino en que el proyecto pueda escalar en numero de participantes y registros si se publica como codigo abierto de uso, lectura, escritura y ejecucion libre.",
        "Ese crecimiento podria aportar valor a comunidades educativas, grupos naturalistas, ayuntamientos, asociaciones ambientales o usuarios interesados en documentar vegetacion local. La posibilidad de escalar exige mejorar despliegue, privacidad, moderacion y proveedor de mapas, pero no cambia el enfoque no comercial del proyecto.",
    ])

    parts.append(heading(3, "2.2.3. Tipo de proyecto requerido para dar respuesta a las demandas previstas"))
    add_paragraphs(parts, [
        "El proyecto requerido es una plataforma cliente-servidor con aplicacion Android nativa, backend API, base de datos geoespacial y panel web administrativo. Esta arquitectura coincide con la estructura real del repositorio.",
        "No es un juego ni una aplicacion de escritorio. Si contiene una parte web, pero limitada al panel interno de administracion y moderacion; no hay una web publica completa de consulta para usuarios anonimos.",
    ])
    parts.append(table([
        ["Pregunta de la plantilla", "Respuesta basada en codigo"],
        ["¿Es una app?", "Si. Existe aplicacion Android nativa en android/."],
        ["¿Es un juego?", "No. No hay motor de juego ni logica ludica."],
        ["¿Es una aplicacion de escritorio?", "No. No hay cliente desktop."],
        ["¿Es una aplicacion web?", "Parcialmente. Existe panel web admin en Laravel/Blade, no web publica completa."],
        ["¿Tiene panel de control?", "Si. Rutas /admin para dashboard, moderacion, flags, usuarios, analitica y asistente."],
        ["¿Que datos guarda?", "Usuarios, tokens Sanctum, registros de plantas, observaciones, flags, eventos, sesiones, jobs/cache y rutas de fotos."],
    ], [3200, 5800]))

    parts.append(heading(3, "2.2.4. Caracteristicas especificas del proyecto segun los requerimientos"))
    add_paragraphs(parts, [
        "Plantaria requiere una API disponible para Android, una base PostgreSQL/PostGIS y permisos moviles de Internet, camara y localizacion. El AndroidManifest declara INTERNET, ACCESS_COARSE_LOCATION, ACCESS_FINE_LOCATION y CAMERA.",
        "La subida de fotografias se resuelve con multipart/form-data hacia /api/uploads/photos. Android comprime imagenes a JPEG antes de subirlas y el backend las almacena en el disco publico de Laravel.",
        "El mapa se renderiza con MapLibre Native Android usando una URL de estilo configurable por BuildConfig. La URL de desarrollo que aparece en build.gradle.kts es https://demotiles.maplibre.org/style.json.",
    ])

    parts.append(heading(2, "2.3. Identificacion de los aspectos que facilitan o dificultan el desarrollo de la posible intervencion"))
    add_paragraphs(parts, [
        "Facilitan el desarrollo la separacion clara entre backend, Android y analitica; el uso de frameworks conocidos; los tests feature del backend; y los scripts de arranque y validacion.",
        "Dificultan el desarrollo la dependencia de validacion fisica en movil, la configuracion ADB entre WSL y Windows, el uso de servicios externos para geocodificacion y la necesidad de una estrategia final de tiles si la aplicacion se publica fuera de un entorno de demostracion.",
    ])

    parts.append(heading(3, "2.3.1. Obligaciones fiscales, laborales y de prevencion de riesgos y sus condiciones de aplicacion"))
    add_paragraphs(parts, [
        "El repositorio no contiene documentacion fiscal, laboral ni de prevencion de riesgos. A nivel tecnico, si Plantaria tratase datos personales reales, habria que revisar proteccion de datos, condiciones de uso, consentimiento para ubicacion y tratamiento de fotografias.",
        "[PENDIENTE: explicar obligaciones fiscales, laborales, PRL y proteccion de datos aplicables segun el contexto academico o empresarial que se presente.]",
    ])

    parts.append(heading(3, "2.3.2. Posibles ayudas o subvenciones para la incorporacion de las nuevas tecnologias de produccion o de servicio propuestas"))
    add_paragraphs(parts, [
        "No hay referencias a subvenciones o ayudas en el repositorio. La infraestructura local usa herramientas de codigo abierto o gratuitas para desarrollo, pero eso no equivale a una ayuda publica.",
        "[PENDIENTE: investigar y explicar ayudas disponibles si se desea plantear Plantaria como proyecto empresarial.]",
    ])

    parts.append(heading(3, "2.3.3. Guion de trabajo que se va a seguir para la elaboracion del proyecto"))
    add_paragraphs(parts, [
        "El guion que se deduce del repositorio es: definir dominio, crear backend Laravel, conectar PostgreSQL/PostGIS, implementar API, crear app Android, integrar mapa y fotos, añadir panel de moderacion, incorporar analitica y cerrar scripts/documentacion.",
        "Este orden queda respaldado por migraciones, controladores, pantallas Android, scripts y registro de contexto. Para una defensa final conviene convertirlo en cronograma academico con fechas reales de ejecucion.",
        "[PENDIENTE: completar cronograma final con fechas academicas oficiales y horas dedicadas.]",
    ])

    parts.append(heading(1, "3. Diseno"))
    parts.append(heading(2, "3.1. Definicion o adaptacion de la intervencion"))
    add_paragraphs(parts, [
        "La intervencion consiste en construir un sistema que transforme observaciones vegetales aisladas en registros geolocalizados consultables, trazables y moderables. La aplicacion Android captura la informacion en campo y el backend conserva los datos y aplica reglas de seguridad.",
        "La arquitectura evita concentrar toda la logica en el cliente. El backend decide validaciones, roles, persistencia, subida de imagenes, moderacion y analitica. Android se centra en experiencia movil, captura de foto, ubicacion y visualizacion en mapa.",
    ])

    parts.append(heading(3, "3.1.1. Informacion relativa a los aspectos que van a ser tratados en el proyecto"))
    add_paragraphs(parts, [
        "Los aspectos tratados por el codigo son autenticacion, persistencia geoespacial, consulta de mapa, carga de fotos, observaciones, moderacion, denuncias, gestion de usuarios, actividad propia, analitica y soporte de demo.",
        "La tabla siguiente resume tecnologias verificadas en archivos de dependencias y configuracion.",
    ])
    parts.append(table([
        ["Capa", "Tecnologias y librerias reales"],
        ["Backend", "PHP ^8.3, Laravel ^13.0, Laravel Sanctum ^4.0, PHPUnit ^12.5.12."],
        ["Panel web", "Laravel Blade, Vite ^8, Tailwind CSS ^4, laravel-vite-plugin."],
        ["Base de datos", "PostgreSQL/PostGIS mediante imagen postgis/postgis:16-3.5."],
        ["Android", "Kotlin, Android Gradle Plugin 9.1.1, Kotlin Compose plugin 2.3.10, compileSdk 36, minSdk 26."],
        ["UI Android", "Jetpack Compose, Material 3, Navigation Compose, Lifecycle ViewModel Compose."],
        ["Mapa", "MapLibre Native Android 13.0.2."],
        ["Sesion Android", "DataStore Preferences 1.2.1."],
        ["Analitica", "Python 3, pandas, matplotlib, SQLAlchemy y psycopg."],
        ["Automatizacion", "Bash, PowerShell, Gradle Wrapper 9.3.1, Docker Compose."],
    ], [2600, 6400]))

    parts.append(paragraph("Flujo de datos principal:", bold=True))
    parts.append(table([
        ["Flujo", "Descripcion implementada"],
        ["Login", "Android envia handle y password a /api/auth/login; Laravel valida credenciales, comprueba estado y devuelve token Sanctum."],
        ["Mapa", "Android solicita /api/records; Laravel devuelve registros con coordenadas y fotos; MapLibre dibuja marcadores."],
        ["Busqueda de planta", "Android envia q a /api/records; Laravel busca en nombres provisional, comun verificado y cientifico."],
        ["Busqueda de lugar", "Android llama /api/geocoding/search; Laravel consulta Nominatim con cache y devuelve coordenadas normalizadas."],
        ["Nuevo reporte", "Android obtiene o selecciona foto, la sube a /api/uploads/photos, recibe path y crea /api/records con coordenadas."],
        ["Nueva observacion", "Android sube foto y llama /api/records/{publicId}/observations; Laravel añade fila en observations y actualiza latest_observation_at."],
        ["Moderacion", "MOD/ADMIN entra en /admin, revisa registros o flags y Laravel registra AppEvent."],
        ["Analitica", "Laravel exporta CSV; Python/pandas genera admin_dashboard.json; el dashboard lo lee con PandasAnalyticsReport."],
    ], [2600, 6400]))

    parts.append(heading(2, "3.2. Priorizacion y secuenciacion de las acciones"))
    add_paragraphs(parts, [
        "La priorizacion real del repositorio se centra primero en el nucleo funcional: dominio, autenticacion, registros, observaciones y mapa. Despues se incorporan moderacion, panel admin, datos demo, scripts y analitica.",
        "El cierre del MVP debe priorizar estabilidad y validacion fisica frente a ampliar alcance. El codigo ya contiene funciones suficientes para defender un producto de punta a punta, pero la prueba en telefono real sigue siendo critica.",
    ])
    parts.append(table([
        ["Prioridad", "Accion"],
        ["Alta", "Mantener backend, Android, PostGIS y flujos de foto/GPS estables."],
        ["Alta", "Validar en movil fisico login, mapa, busqueda, camara, galeria, GPS, reporte y observacion."],
        ["Media", "Documentar API, demo, instalacion y limitaciones."],
        ["Media", "Usar panel admin y analitica como apoyo de defensa."],
        ["Baja/Futura", "iOS, web publica completa, notificaciones e identificacion automatica de plantas."],
    ], [2600, 6400]))

    parts.append(heading(3, "3.2.1. Estudio de viabilidad tecnica del proyecto"))
    add_paragraphs(parts, [
        "La viabilidad tecnica es razonable para un TFG de DAM porque el MVP se concentra en una aplicacion Android, un backend Laravel y una base local PostgreSQL/PostGIS. No se intenta desarrollar simultaneamente Android, iOS y web publica completa.",
        "Las dependencias usadas son estandar dentro de sus ecosistemas. Laravel resuelve API, autenticacion, validacion y panel web; Android Compose cubre la interfaz; MapLibre cubre mapa; PostGIS aporta consulta geoespacial; pandas cubre analitica auxiliar.",
        "Los riesgos tecnicos mas relevantes son la configuracion del entorno local, la conexion Android-WSL/Windows, la gestion de permisos de camara y ubicacion, y la necesidad de proveedor de tiles si se evoluciona a produccion.",
    ])

    parts.append(heading(2, "3.3. La planificacion de la intervencion"))
    add_paragraphs(parts, [
        "La planificacion se puede estructurar como fases tecnicas. La evidencia de esas fases se encuentra en directorios separados y en scripts que automatizan arranque, validacion, perfilado e instalacion del APK.",
        "La tabla siguiente traduce el estado del codigo a fases defendibles de proyecto.",
    ])

    parts.append(heading(3, "3.3.1. Fases del proyecto especificando su contenido y plazos de ejecucion"))
    parts.append(table([
        ["Fase", "Contenido", "Estado"],
        ["1. Dominio y base", "Usuarios, roles, registros, observaciones, flags, eventos y migraciones.", "Implementado"],
        ["2. Backend API", "Auth, records, observations, uploads, profiles, flags, geocoding y admin API.", "Implementado"],
        ["3. Base geoespacial", "PostGIS local, migracion de extension y filtro por radio.", "Implementado"],
        ["4. Cliente Android", "Login, mapa, acciones, perfil, DataStore, camara, galeria y GPS.", "Implementado"],
        ["5. Panel admin", "Dashboard, moderacion, flags, usuarios, analitica y asistente.", "Implementado"],
        ["6. Analitica", "Export CSV desde Laravel y snapshot pandas JSON.", "Implementado como modulo auxiliar"],
        ["7. Validacion", "Tests backend, build Android, smoke PostGIS y perfilado.", "Parcialmente automatizado"],
        ["8. Entrega", "Memoria, guia de demo, checklist movil y backup.", "En preparacion"],
    ], [1800, 4700, 2500]))
    parts.append(paragraph("[PENDIENTE: sustituir estados por fechas reales de inicio y fin si el formato del centro exige cronograma temporal.]"))

    parts.append(heading(2, "3.4. Determinacion de recursos"))
    add_paragraphs(parts, [
        "Los recursos tecnicos aparecen reflejados en la estructura del proyecto y en los archivos de configuracion. Se requiere un equipo capaz de ejecutar PHP, Composer, Node/Vite para assets del panel, Docker, Android SDK/Gradle y Python.",
        "El proyecto tambien requiere un dispositivo Android o emulador para validar permisos, camara, ubicacion y conexion contra la API local.",
    ])

    parts.append(heading(3, "3.4.1. Objetivos que se pretenden conseguir identificando su alcance"))
    add_paragraphs(parts, [
        "Objetivo general: entregar un MVP funcional de Plantaria que permita registrar y consultar plantas geolocalizadas con trazabilidad temporal y moderacion.",
        "Objetivos especificos implementados: autenticacion con tokens, mapa de registros, creacion de reportes, subida de fotos, observaciones, busqueda textual, busqueda de lugares, panel admin, gestion de usuarios, flags y analitica.",
        "Objetivos fuera del alcance actual: publicacion en tiendas, app iOS, web publica completa, pagos, notificaciones push, recuperacion avanzada de contrasena e IA de identificacion botanica.",
    ])

    parts.append(heading(3, "3.4.2. Actividades necesarias para el desarrollo del proyecto"))
    for item in [
        "Configurar repositorio y dependencias de backend, Android y analitica.",
        "Definir migraciones y modelos Eloquent del dominio.",
        "Implementar rutas API y validaciones con FormRequest.",
        "Desarrollar pantallas Android con Compose y estado en ViewModel.",
        "Integrar MapLibre, permisos de localizacion, camara y Photo Picker.",
        "Crear panel web de administracion y moderacion.",
        "Preparar datos demo, scripts de validacion y documentacion.",
        "Validar el MVP con tests, compilacion Android y prueba real contra PostgreSQL/PostGIS.",
    ]:
        parts.append(bullet(item))

    parts.append(heading(3, "3.4.3. Recursos materiales y personales necesarios para realizar el proyecto"))
    parts.append(table([
        ["Tipo", "Recurso"],
        ["Material", "Ordenador de desarrollo con WSL/Linux, Docker, PHP, Composer, Node, Python y Android SDK."],
        ["Material", "Telefono Android fisico o emulador para probar APK, camara, permisos y ubicacion."],
        ["Software", "Laravel, PostgreSQL/PostGIS, Android Gradle, Kotlin, Jetpack Compose, MapLibre, pandas."],
        ["Personal", "Trabajo individual del alumno; no se plantea equipo de desarrollo como parte del MVP."],
        ["Operativo", "Acceso ADB desde PowerShell para instalacion fiable en movil fisico segun script install_debug_apk.ps1."],
    ], [2300, 6700]))

    parts.append(heading(3, "3.4.4. Necesidades de financiacion para la puesta en marcha del proyecto"))
    add_paragraphs(parts, [
        "Este apartado no se interpreta como plan de ingresos ni monetizacion. Plantaria se plantea como TFG/MVP y como codigo publico de uso, lectura, escritura y ejecucion libre. Por tanto, la financiacion necesaria se limita a recursos de ejecucion: equipo de desarrollo, tiempo dedicado, dispositivo Android de pruebas y posible infraestructura si se despliega fuera del entorno local.",
        "En local, el proyecto puede ejecutarse con herramientas gratuitas o de codigo abierto: Laravel, PostgreSQL/PostGIS, Docker, Gradle, Kotlin, MapLibre y pandas. Si escalase a mas usuarios, aparecerian costes operativos de servidor, dominio, backups, almacenamiento de fotos y proveedor/hosting de mapas.",
    ])
    parts.append(table([
        ["Concepto", "Coste estimado", "Comentario"],
        ["Desarrollo academico local", "0 € adicionales si ya se dispone de equipo y movil", "El MVP se ejecuta con herramientas gratuitas o de codigo abierto en entorno local."],
        ["Ordenador de desarrollo", "0 € imputados / ya disponible", "No se plantea comprar hardware nuevo para el TFG."],
        ["Telefono Android de pruebas", "0 € imputados / ya disponible", "Necesario para validar APK, camara, GPS y permisos."],
        ["Dominio .es", "10-35 €/año", "Los agentes registradores suelen ser mas baratos; Dominios.es marca tarifa directa superior."],
        ["Servidor VPS pequeno", "5-10 €/mes", "Suficiente para un MVP publico inicial con Laravel, PostgreSQL/PostGIS y panel admin."],
        ["Backups basicos", "0-5 €/mes", "Al inicio pueden hacerse con snapshots/exportaciones; si crece, conviene almacenamiento externo."],
        ["Almacenamiento de fotos", "0-5 €/mes inicial", "Cloudflare R2 tiene capa gratuita de 10 GB/mes; despues se paga por GB y operaciones."],
        ["Mapas/tiles", "0 €/mes en demo no comercial; 25 USD/mes si se usa MapTiler Flex", "Para produccion no conviene depender de demotiles; se necesita proveedor o hosting propio."],
        ["Publicacion Google Play", "25 USD una sola vez", "Solo si se publica en Google Play; para entrega academica basta APK local."],
        ["Certificado HTTPS", "0 €/año", "Puede resolverse con Let's Encrypt si se despliega publicamente."],
    ], [2600, 2300, 4100]))
    add_paragraphs(parts, [
        "Con estas cifras, la entrega academica local puede mantenerse en 0 € adicionales si ya existen equipo y movil. Un despliegue publico pequeño y no comercial estaria alrededor de 10-25 €/mes sin contar tiempo de mantenimiento, y una version con proveedor de mapas de pago podria situarse alrededor de 35-60 €/mes.",
    ])

    parts.append(heading(2, "3.5. Planificacion de la evaluacion"))
    add_paragraphs(parts, [
        "La evaluacion tecnica se apoya en tests backend, compilacion Android, comprobacion de sintaxis de scripts, smoke test contra PostgreSQL/PostGIS y perfilado de endpoints. El script scripts/validate_project.sh automatiza buena parte de esta verificacion.",
        "Los tests backend cubren autenticacion, registros, observaciones, subida de foto, flags, moderacion, autorizacion admin, geocodificacion, seeder, actividad propia, panel admin y exportacion analitica. Los tests se ejecutan con SQLite en memoria segun phpunit.xml, por lo que el smoke PostGIS es necesario para cubrir la base real.",
    ])
    parts.append(table([
        ["Prueba", "Comando o evidencia"],
        ["Tests backend", "cd backend && php artisan test"],
        ["Build Android", "cd android && ./gradlew :app:assembleDebug"],
        ["Sintaxis scripts", "bash -n scripts/*.sh y parseo PowerShell cuando esta disponible."],
        ["Smoke PostGIS", "scripts/validate_project.sh comprueba endpoint por radio y distance_km."],
        ["Perfilado", "scripts/profile_app_performance.sh mide endpoints criticos y tamano APK."],
    ], [3000, 6000]))

    parts.append(heading(2, "3.6. Diseno de documentacion"))
    add_paragraphs(parts, [
        "El repositorio contiene documentacion tecnica y de entrega: README raiz, README backend, README Android, guia de demo, checklist de validacion movil, referencia API, memoria tecnica base y backup OneDrive.",
        "La documentacion esta orientada a reproducir el entorno local, explicar la demo y dejar claras las limitaciones del MVP.",
    ])

    parts.append(heading(3, "3.6.1. Documentacion necesaria para su diseno"))
    for item in [
        "README.md para vista general, arranque rapido y validacion.",
        "backend/README.md para backend Laravel y panel.",
        "android/README.md para build e instalacion del cliente.",
        "docs/API.md para endpoints y payloads principales.",
        "docs/GUIA_DEMO.md para presentacion funcional.",
        "docs/CHECKLIST_VALIDACION_MOVIL.md para prueba fisica.",
        "docs/BACKUP_ONEDRIVE.md para empaquetado del proyecto.",
        "Contexto/ para memoria operativa entre sesiones.",
    ]:
        parts.append(bullet(item))

    parts.append(heading(2, "3.7. Plan de atencion al cliente"))
    add_paragraphs(parts, [
        "No existe en el codigo un modulo de soporte al cliente, tickets, chat ni centro de ayuda. El soporte actual es documentacion de demo, checklist y panel administrativo para revisar contenido.",
        "Si Plantaria evolucionase fuera del TFG, el plan de atencion deberia cubrir incidencias de cuenta, problemas con fotos, ubicacion incorrecta, denuncias, privacidad y solicitudes de eliminacion.",
        "[PENDIENTE: definir canal de soporte, tiempos de respuesta y responsable si se presenta como servicio real.]",
    ])

    parts.append(heading(3, "3.7.1. Aspectos que se deben controlar para garantizar la calidad del proyecto"))
    for item in [
        "Integridad de autenticacion y roles.",
        "Validez de coordenadas y funcionamiento de PostGIS.",
        "Subida correcta de fotos y disponibilidad de URLs publicas.",
        "Estabilidad de MapLibre en emulador y movil fisico.",
        "Validaciones de formularios backend y Android.",
        "No mezclar actividad propia de usuario con registros globales.",
        "Funcionamiento del panel de moderacion y flags.",
        "Rendimiento de endpoints usados por el mapa.",
    ]:
        parts.append(bullet(item))

    parts.append(heading(1, "4. Organizacion"))
    parts.append(heading(2, "4.1. Deteccion de demandas y necesidades"))
    add_paragraphs(parts, [
        "La demanda detectada a nivel de producto es disponer de una herramienta movil para registrar plantas en campo y centralizar los datos en un backend con control de calidad. El codigo prioriza esa necesidad mediante Android como cliente principal y Laravel como capa de negocio.",
        "La organizacion del repositorio separa responsabilidades por carpetas, lo que facilita trabajar, documentar y validar cada modulo de forma independiente.",
    ])

    parts.append(heading(3, "4.1.1. Tareas en funcion de las necesidades de implementacion"))
    parts.append(table([
        ["Necesidad", "Tarea tecnica"],
        ["Captura en campo", "Pantalla Acciones con camara, galeria, GPS y creacion de reportes/observaciones."],
        ["Consulta geografica", "Pantalla Mapa con MapLibre, marcadores, busqueda y ficha."],
        ["Persistencia", "Migraciones y modelos Laravel con usuarios, registros, observaciones, flags y eventos."],
        ["Calidad del dato", "Panel de moderacion, estados pending/verified/rejected y nombres verificados."],
        ["Analitica", "AppEvent, export CSV, pandas y dashboard."],
        ["Demo", "Seeder con cuentas por rol y registros alrededor de Barcelona."],
    ], [3000, 6000]))

    parts.append(heading(3, "4.1.2. Recursos y la logistica necesaria para cada tarea"))
    add_paragraphs(parts, [
        "Backend y base de datos requieren Docker/PostGIS y Laravel funcionando en el puerto local esperado. Android requiere Gradle y un emulador o telefono con depuracion USB.",
        "La logistica especial del proyecto es que el telefono fisico se detecta de forma fiable desde Windows PowerShell, por lo que se ha creado scripts/install_debug_apk.ps1 para ejecutar adb devices, adb reverse e instalacion del APK desde Windows.",
    ])

    parts.append(heading(3, "4.1.3. Necesidades de permisos y autorizaciones para llevar a cabo las tareas"))
    add_paragraphs(parts, [
        "Android solicita permisos de Internet, ubicacion aproximada/precisa y camara. La aplicacion tambien usa FileProvider para guardar fotos de camara en cache y entregarlas al flujo de subida.",
        "Para publicar la app haria falta una cuenta de desarrollador Android y revisar politicas de privacidad por uso de ubicacion y fotografias. Eso no esta implementado ni configurado en el repositorio.",
        "[PENDIENTE: confirmar si se publicara en Google Play o si la entrega sera solo APK local.]",
    ])

    parts.append(heading(2, "4.2. Programacion"))
    add_paragraphs(parts, [
        "La programacion del trabajo puede organizarse por modulos: backend, base de datos, Android, analitica, scripts y documentacion. El repositorio muestra que estos modulos ya existen y se relacionan mediante API HTTP y almacenamiento comun.",
        "No hay en el repositorio un diagrama Gantt formal. Las fechas de sesion se guardan en Contexto/RegistroDeSesiones.md, pero si el centro exige planificacion temporal oficial debe trasladarse a un cronograma.",
        "[PENDIENTE: añadir cronograma academico si se requiere en la entrega final.]",
    ])

    parts.append(heading(3, "4.2.1. Procedimientos para ejecucion de las tareas"))
    parts.append(table([
        ["Procedimiento", "Comando"],
        ["Arranque local movil", "./scripts/start_mobile_stack.sh"],
        ["Tests backend", "cd backend && php artisan test"],
        ["Build Android", "cd android && ./gradlew :app:assembleDebug"],
        ["Instalacion APK WSL", "./scripts/install_debug_apk.sh"],
        ["Instalacion APK PowerShell", "scripts/install_debug_apk.ps1"],
        ["Validacion integral", "./scripts/validate_project.sh"],
        ["Perfilado rapido", "./scripts/profile_app_performance.sh"],
        ["Backup", "./scripts/package_for_onedrive.sh"],
    ], [3000, 6000]))

    parts.append(heading(2, "4.3. Gestion"))
    add_paragraphs(parts, [
        "La gestion tecnica se apoya en Git, tests, scripts de validacion y documentacion viva. El repositorio contiene .gitignore raiz y scripts que evitan depender de pasos manuales dispersos.",
        "El codigo registra eventos de producto en app_events, lo que tambien permite gestionar actividad y analitica desde el panel admin.",
    ])

    parts.append(heading(3, "4.3.1. Riesgos inherentes a la ejecucion del proyecto, definiendo el plan de prevencion de riesgos y los medios necesarios"))
    parts.append(table([
        ["Riesgo", "Mitigacion visible o pendiente"],
        ["Fallo de conexion Android-backend", "URLs diferenciadas para emulador y movil; adb reverse documentado y script PowerShell."],
        ["Fotos demasiado pesadas", "Compresion Android antes de upload y limite backend de 20 MB."],
        ["Cuenta bloqueada con token antiguo", "Middleware active.user corta rutas autenticadas si la cuenta no esta activa."],
        ["Consulta geoespacial no cubierta por SQLite", "Smoke real PostGIS en validate_project.sh."],
        ["Mapa demo no apto para produccion", "Estilo configurable; pendiente proveedor final si se publica."],
        ["Perdida de trabajo", "Script package_for_onedrive.sh genera fuente, bundle Git, APK, manifest y hashes."],
    ], [3000, 6000]))

    parts.append(heading(2, "4.4. Coordinacion y supervision de la intervencion"))
    add_paragraphs(parts, [
        "La supervision tecnica se puede realizar con revisiones de tests, builds, git diff, perfilado y ejecucion de la checklist movil. El panel admin permite supervisar contenido de usuarios, flags y estados de verificacion.",
        "[PENDIENTE: explicar quien coordina o supervisa el proyecto en el contexto academico: alumno, tutor, profesor o equipo.]",
    ])

    parts.append(heading(3, "4.4.1. Asignacion de recursos materiales y humanos segun los tiempos de ejecucion"))
    add_paragraphs(parts, [
        "El trabajo se presenta como individual. Los recursos humanos se concentran en el alumno, que asume definicion, implementacion, pruebas y documentacion del MVP.",
        "Los recursos materiales son el equipo de desarrollo, el entorno local, Docker/PostGIS, la toolchain Android y un movil Android o emulador para validar el APK. La dedicacion horaria exacta puede incorporarse si el centro la solicita.",
    ])

    parts.append(heading(3, "4.4.2. Valoracion economica que da respuesta a las condiciones de la ejecucion del proyecto"))
    add_paragraphs(parts, [
        "La valoracion economica no se refiere a beneficios, ventas ni retorno economico. Se refiere al coste aproximado de ejecutar y mantener el proyecto si se llevase mas alla del entorno academico.",
        "Para el TFG/MVP local, el coste directo puede considerarse bajo si ya se dispone de ordenador y telefono Android. Para una version publica con mas usuarios habria que presupuestar servidor, dominio, backups, almacenamiento de imagenes, proveedor o hosting de tiles, mantenimiento tecnico y tiempo de desarrollo. El objetivo declarado sigue siendo abierto y no comercial.",
    ])
    parts.append(table([
        ["Escenario", "Coste inicial", "Coste mensual/anual", "Alcance"],
        ["Entrega TFG local", "0 € adicionales", "0 €/mes", "Ejecucion en local con Docker, Laravel y APK debug; no requiere dominio ni tienda."],
        ["Publicacion tecnica minima", "25 USD si se publica en Google Play", "10-25 €/mes + dominio anual", "VPS pequeño, dominio, backups basicos y almacenamiento moderado de fotos."],
        ["MVP comunitario con mapas estables", "25 USD si se publica en Google Play", "35-60 €/mes + dominio anual", "Incluye VPS, backups, almacenamiento externo y proveedor de tiles como MapTiler Flex."],
        ["Escala mayor", "Variable", "Desde 60 €/mes en adelante", "Requiere separar base de datos, almacenamiento, CDN, monitorizacion, backups y moderacion mas intensa."],
    ], [2200, 2100, 2400, 3300]))
    add_paragraphs(parts, [
        "El tiempo de desarrollo del alumno no se incluye como desembolso, porque el proyecto se presenta como trabajo academico individual. Si se quisiera valorar profesionalmente, se podria añadir una estimacion separada de horas por tarifa, pero no formaria parte del coste real pagado para entregar el TFG.",
    ])

    parts.append(heading(2, "4.5. Elaboracion de informes"))
    add_paragraphs(parts, [
        "El repositorio ya genera o contiene varios informes/documentos: memoria tecnica base, guia de demo, checklist movil, referencia API, README y snapshots de analitica en JSON.",
        "El comando de analitica produce admin_dashboard.json, que funciona como informe operativo para el panel de administracion.",
    ])

    parts.append(heading(3, "4.5.1. Documentacion necesaria para la ejecucion del proyecto"))
    for item in [
        "Variables .env de Laravel y servicios externos.",
        "Comandos de Docker Compose para PostGIS.",
        "Comandos Composer, Artisan y Gradle.",
        "Credenciales demo creadas por DatabaseSeeder.",
        "Guia para ADB reverse e instalacion del APK.",
        "Checklist de prueba fisica del flujo completo.",
    ]:
        parts.append(bullet(item))

    parts.append(heading(2, "4.6. Seguimiento y control"))
    add_paragraphs(parts, [
        "El seguimiento se realiza con pruebas automatizadas, revisiones de estado Git, validacion de scripts y prueba manual del APK. La base de eventos app_events permite, ademas, observar actividad de usuarios, busquedas y acciones administrativas.",
    ])

    parts.append(heading(3, "4.6.1. Procedimiento de evaluacion de las actividades o intervenciones realizadas durante la ejecucion del proyecto"))
    add_paragraphs(parts, [
        "Cada actividad tecnica debe cerrarse con al menos una comprobacion: test feature si afecta al backend, build si afecta a Android, py_compile si afecta a Python y prueba manual si toca permisos o hardware.",
        "Para cambios integrales se debe ejecutar scripts/validate_project.sh y, cuando sea posible, repetir el checklist de validacion movil.",
    ])

    parts.append(heading(3, "4.6.2. Indicadores de calidad para realizar la evaluacion del proyecto"))
    parts.append(table([
        ["Indicador", "Criterio"],
        ["Tests backend", "Suite php artisan test sin fallos."],
        ["Build Android", "APK debug generado correctamente."],
        ["Smoke PostGIS", "/api/records por radio devuelve distance_km y valida parametros."],
        ["Rendimiento local", "Endpoints del mapa medidos por profile_app_performance.sh."],
        ["Seguridad basica", "Roles y cuentas no activas bloquean rutas restringidas."],
        ["UX movil", "Flujo completo sin crash en dispositivo fisico."],
    ], [3000, 6000]))

    parts.append(heading(3, "4.6.3. Procedimiento para el registro y evaluacion de las incidencias que puedan presentarse durante la ejecucion del proyecto"))
    add_paragraphs(parts, [
        "Las incidencias tecnicas deben registrarse con descripcion, modulo afectado, pasos de reproduccion, resultado esperado, resultado real, log o captura y verificacion posterior.",
        "El repositorio ya registra trabajo relevante en Contexto/RegistroDeSesiones.md, pero no hay un sistema formal de issues versionado dentro del proyecto.",
        "[PENDIENTE: indicar si se usara GitHub Issues, documento interno o registro manual para incidencias de entrega.]",
    ])

    parts.append(heading(3, "4.6.4. Procedimiento para la solucion de las incidencias registradas"))
    add_paragraphs(parts, [
        "El procedimiento recomendado es reproducir, aislar el modulo, aplicar correccion minima, ejecutar prueba especifica, ejecutar validacion relacionada y registrar el resultado. Esta forma de trabajo encaja con los scripts y tests existentes.",
        "Ejemplos ya cubiertos por codigo: manejo de usuario bloqueado, validacion de filtros geograficos, compresion de fotos y uso de bitmap para marcadores MapLibre en lugar de drawables XML directos.",
    ])

    parts.append(heading(3, "4.6.5. Procedimiento para la gestion y registro de los cambios en los recursos y en las tareas"))
    add_paragraphs(parts, [
        "Los cambios deben reflejarse en Git, documentacion de Contexto/ y README o docs/ cuando afecten a uso, entorno o entrega. El propio AGENTS.md exige registrar decisiones y sesiones relevantes con fecha y hora local.",
        "Si cambia el procedimiento de trabajo, se debe actualizar primero Contexto/Contexto.md. Si cambia el entorno, debe actualizarse Contexto/EntornoYVersiones.md.",
    ])

    parts.append(heading(3, "4.6.6. Procedimiento para la participacion en la evaluacion de los usuarios y se han elaborado documentos especificos"))
    add_paragraphs(parts, [
        "El repositorio contiene docs/CHECKLIST_VALIDACION_MOVIL.md para guiar una prueba fisica. Tambien existen cuentas demo por rol y registros seedables que permiten ensayar flujos sin depender de datos reales.",
        "No hay encuestas, formularios de satisfaccion ni resultados de usuarios versionados.",
        "[PENDIENTE: recoger feedback de usuarios o compañeros si el centro exige evaluacion externa.]",
    ])

    parts.append(heading(3, "4.6.7. Sistema para garantizar el cumplimiento del pliego de condiciones del proyecto cuando este existe"))
    add_paragraphs(parts, [
        "No existe un pliego de condiciones formal dentro del repositorio. La forma mas cercana de control son los requisitos funcionales reflejados en codigo, tests y documentacion de entrega.",
        "[PENDIENTE: adjuntar o resumir pliego de condiciones si el centro lo ha proporcionado.]",
    ])

    parts.append(heading(1, "5. Actividades profesionales"))
    add_paragraphs(parts, [
        "Plantaria se relaciona con actividades profesionales de desarrollo de aplicaciones multiplataforma, administracion de sistemas, servicios tecnologicos, gestion de datos y moderacion de contenido.",
        "El proyecto combina cliente movil, API, base de datos, panel web y analitica, por lo que permite demostrar competencias transversales del ciclo DAM.",
    ])

    parts.append(heading(2, "5.1. Areas de sistemas y departamentos de informatica en cualquier sector de actividad"))
    add_paragraphs(parts, [
        "Un departamento de informatica podria usar Plantaria como ejemplo de sistema interno para inventario geolocalizado, gestion de observaciones y control de calidad del dato. La arquitectura cliente-servidor y el despliegue con Docker/PostGIS encajan con tareas habituales de sistemas.",
        "El codigo tambien muestra practicas de mantenimiento: scripts de validacion, backup, perfilado, variables de entorno y separacion de responsabilidades.",
    ])

    parts.append(heading(2, "5.2. Sector de servicios tecnologicos y comunicaciones"))
    add_paragraphs(parts, [
        "El proyecto puede situarse en servicios tecnologicos basados en datos geoespaciales y aplicaciones moviles. La API Laravel podria ser consumida por otros clientes en el futuro, aunque actualmente el cliente real implementado es Android.",
        "La analitica pandas y el panel admin permiten presentar informacion operativa a administradores. El asistente local con Ollama aparece como complemento interno y opcional, no como dependencia del flujo principal.",
    ])

    parts.append(heading(2, "5.3. Area comercial con gestion de transacciones por Internet"))
    add_paragraphs(parts, [
        "El codigo no implementa pagos, carrito, facturacion, pasarela bancaria ni transacciones comerciales. Por tanto, Plantaria no debe presentarse como comercio electronico.",
        "El proyecto se plantea como TFG/MVP con intencion de que el codigo pueda ser publico y libre para uso, lectura, escritura y ejecucion. Si en el futuro escalase, la finalidad seria aumentar comunidad, datos y utilidad social, no monetizar transacciones.",
        "En consecuencia, este apartado se responde de forma negativa: Plantaria no gestiona transacciones por Internet. Su posible encaje profesional estaria mas cerca de servicios publicos, educativos, ambientales o comunitarios que de un area comercial de venta online.",
    ])

    parts.append(heading(1, "6. Lineas de actuacion"))
    add_paragraphs(parts, [
        "Las lineas de actuacion describen como se ha enfocado el desarrollo y que competencias tecnicas se ejercitan. En Plantaria la actuacion principal es construir un MVP realista, verificable y dividido por modulos.",
    ])

    parts.append(heading(2, "6.1. La ejecucion de trabajos en equipo"))
    add_paragraphs(parts, [
        "El trabajo se presenta como individual. Esto significa que el alumno asume tanto la parte de analisis y diseno como la implementacion tecnica, pruebas y documentacion.",
        "Aunque no exista un equipo formal, la estructura modular del repositorio permitiria incorporar colaboradores en el futuro: backend, Android, analitica, documentacion, moderacion y despliegue pueden dividirse con claridad si el proyecto escalase.",
    ])

    parts.append(heading(2, "6.2. La autoevaluacion del trabajo realizado"))
    add_paragraphs(parts, [
        "La autoevaluacion tecnica puede basarse en resultados verificables: tests backend, build Android, smoke PostGIS, perfilado de endpoints, checklist movil y revision de documentacion.",
        "El repositorio no contiene una reflexion personal del alumno sobre aprendizaje, dificultades o mejora.",
        "[PENDIENTE: redactar autoevaluacion personal: que se ha aprendido, que dificultades han aparecido y que se mejoraria.]",
    ])

    parts.append(heading(2, "6.3. La autonomia y la iniciativa"))
    add_paragraphs(parts, [
        "El codigo muestra iniciativa tecnica al integrar varios bloques: Laravel, Android nativo, PostGIS, MapLibre, pandas, panel admin y scripts de automatizacion. Tambien muestra una decision de alcance prudente al dejar fuera iOS y web publica completa.",
        "La motivacion personal y el grado de autonomia real no se pueden extraer del codigo.",
        "[PENDIENTE: explicar motivacion personal para elegir Plantaria y decisiones autonomas tomadas durante el desarrollo.]",
    ])

    parts.append(heading(2, "6.4. El uso de las TIC"))
    add_paragraphs(parts, [
        "El uso de TIC es central en todo el proyecto. En backend se emplean PHP, Laravel, Sanctum, Eloquent, migraciones, tests y panel web. En movil se emplean Kotlin, Compose, MapLibre, permisos Android, DataStore, HTTP y manejo de imagenes.",
        "En datos se emplea PostgreSQL/PostGIS para persistencia y consulta geografica. En analitica se emplea Python/pandas para transformar CSV exportados desde Laravel en un snapshot JSON consumido por el dashboard. En operativa se emplean Docker, Gradle, Bash, PowerShell y Git.",
    ])

    parts.append(heading(1, "7. Bibliografia"))
    add_paragraphs(parts, [
        "La bibliografia combina tecnologias que aparecen realmente en el repositorio y plataformas externas usadas para contextualizar el sector. Debe adaptarse al formato bibliografico exigido por el centro si se requiere APA, ISO u otro estilo.",
    ])
    for item in [
        "Laravel Framework y documentacion de Laravel Sanctum, por el backend y autenticacion API.",
        "Documentacion de PHP 8.3 y Composer, por dependencias y ejecucion backend.",
        "PostgreSQL y PostGIS, por la base de datos geoespacial y consultas ST_DWithin/ST_Distance.",
        "Android Developers, Kotlin, Jetpack Compose, DataStore, Navigation Compose y permisos Android.",
        "MapLibre Native Android, por renderizado del mapa en la aplicacion movil.",
        "Docker y Docker Compose, por el servicio local postgis/postgis:16-3.5.",
        "pandas, matplotlib, SQLAlchemy y psycopg, por el modulo analytics/.",
        "OpenStreetMap Nominatim, por la ruta de geocodificacion backend configurada en .env.example.",
        "Ollama, por el asistente local opcional del panel administrativo.",
        "iNaturalist. About. https://www.inaturalist.org/pages/about.html",
        "Pl@ntNet. About / Read more. https://plantnet.org/en/about/",
        "GBIF. What is GBIF? https://www.gbif.org/what-is-gbif",
        "Observation International / ObsIdentify. Ficha oficial en Google Play. https://play.google.com/store/apps/details?id=org.observation.obsidentify",
        "Flora Incognita. The Flora Incognita App. https://floraincognita.com/flora-incognita-app/",
        "PictureThis. App oficial de identificacion de plantas. https://www.picturethisai.com/app",
        "PlantSnap. Plant Identifier App. https://www.plantsnap.com/",
        "OpenStreetMap. About. https://www.openstreetmap.org/about/eng",
        "Google Play Console Help. Get started with Play Console. https://support.google.com/googleplay/android-developer/answer/6112435",
        "Hetzner Docs. Price adjustment for cloud products. https://docs.hetzner.com/general/infrastructure-and-availability/price-adjustment/",
        "Cloudflare R2 Docs. Pricing. https://developers.cloudflare.com/r2/pricing/",
        "MapTiler Cloud. Pricing. https://www.maptiler.com/cloud/pricing",
        "Dominios.es. Cuanto cuesta registrar un dominio .es. https://www.dominios.es/es/registra-un-dominio/cuanto-registrarlo",
    ]:
        parts.append(bullet(item))

    parts.append(heading(1, "Preguntas pendientes para completar la version final"))
    for item in [
        "¿Que nombre completo, centro, tutor/a, convocatoria y curso academico deben aparecer en portada?",
        "¿Quieres pasar la guia de notas del centro para ajustar el peso de cada apartado?",
        "¿Se publicara el APK o la entrega sera local con demo en dispositivo?",
        "¿Hay pliego de condiciones, criterios del tutor o rubrica oficial que deban incorporarse literalmente?",
        "¿Quieres añadir una autoevaluacion personal con dificultades, aprendizaje y mejoras futuras?",
    ]:
        parts.append(bullet(item))

    section = (
        "<w:sectPr>"
        '<w:pgSz w:w="11906" w:h="16838"/>'
        '<w:pgMar w:top="1417" w:right="1417" w:bottom="1417" w:left="1417" w:header="708" w:footer="708" w:gutter="0"/>'
        '<w:cols w:space="708"/>'
        '<w:docGrid w:linePitch="360"/>'
        "</w:sectPr>"
    )

    return (
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        '<w:document xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas" '
        'xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" '
        'xmlns:o="urn:schemas-microsoft-com:office:office" '
        'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" '
        'xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math" '
        'xmlns:v="urn:schemas-microsoft-com:vml" '
        'xmlns:wp14="http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing" '
        'xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" '
        'xmlns:w10="urn:schemas-microsoft-com:office:word" '
        f'xmlns:w="{W_NS}" '
        'xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml" '
        'xmlns:wpg="http://schemas.microsoft.com/office/word/2010/wordprocessingGroup" '
        'xmlns:wpi="http://schemas.microsoft.com/office/word/2010/wordprocessingInk" '
        'xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml" '
        'xmlns:wps="http://schemas.microsoft.com/office/word/2010/wordprocessingShape" '
        'mc:Ignorable="w14 wp14">'
        f"<w:body>{''.join(parts)}{section}</w:body>"
        "</w:document>"
    )


def enable_update_fields(settings_xml: str) -> str:
    if "<w:updateFields" in settings_xml:
        return re.sub(r"<w:updateFields[^/]*/>", '<w:updateFields w:val="true"/>', settings_xml)
    return settings_xml.replace("</w:settings>", '<w:updateFields w:val="true"/></w:settings>')


def apply_spanish_replacements(document_xml: str) -> str:
    prefix, marker, body = document_xml.partition("<w:body>")
    target_xml = body if marker else document_xml
    word_chars = r"A-Za-zÁÉÍÓÚÜÑáéíóúüñ"
    for source, target in SPANISH_REPLACEMENTS:
        if re.search(r"\s", source):
            target_xml = target_xml.replace(source, target)
        else:
            pattern = rf"(?<![{word_chars}]){re.escape(source)}(?![{word_chars}])"
            target_xml = re.sub(pattern, target, target_xml)
    return f"{prefix}{marker}{target_xml}" if marker else target_xml


def main() -> None:
    if not TEMPLATE.exists():
        raise FileNotFoundError(f"No existe la plantilla: {TEMPLATE}")

    OUTPUT.parent.mkdir(parents=True, exist_ok=True)
    document_xml = apply_spanish_replacements(build_document_xml())

    with ZipFile(TEMPLATE, "r") as zin, ZipFile(OUTPUT, "w", ZIP_DEFLATED) as zout:
        for info in zin.infolist():
            data = zin.read(info.filename)
            if info.filename == "word/document.xml":
                data = document_xml.encode("utf-8")
            elif info.filename == "word/settings.xml":
                data = enable_update_fields(data.decode("utf-8")).encode("utf-8")
            zout.writestr(info, data)

    print(OUTPUT)


if __name__ == "__main__":
    main()
