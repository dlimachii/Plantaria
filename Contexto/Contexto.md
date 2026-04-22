# Contexto

Esta carpeta existe para reducir el coste de contexto entre sesiones. La idea es que una futura instancia no tenga que releer todo el código desde cero cada vez, sino entrar por un resumen fiable y abrir más detalle solo si hace falta.

Ahora mismo esto debe entenderse como un procedimiento de trabajo para mantener contexto entre sesiones y como índice de la documentación técnica viva del proyecto. La documentación técnica debe mantenerse sincronizada con materiales reales del workspace.

## Orden de lectura recomendado

1. Leer siempre `ContextoGeneral.md`.
2. Si la tarea requiere más detalle o bajar a una parte concreta, leer `ContextoEspecifico.md`.
3. Si la tarea afecta a instalación, despliegue o entorno, leer `EntornoYVersiones.md`.
4. Revisar `DudasYDecisiones.md` para no reabrir dudas ya resueltas.
5. Consultar `RegistroDeSesiones.md` si hace falta entender cambios recientes o decisiones tomadas en conversaciones anteriores.

## Regla de mantenimiento

Cada vez que se haga trabajo relevante conviene actualizar:

- `ContextoGeneral.md` si cambia el alcance del proyecto o el enfoque general.
- `ContextoEspecifico.md` si aparece detalle relevante de una parte concreta.
- `EntornoYVersiones.md` si cambian requisitos, dependencias o pasos de arranque.
- `DudasYDecisiones.md` si se resuelve una duda o aparece una nueva.
- `RegistroDeSesiones.md` con una nota corta de lo hecho.

Regla de fecha/hora desde 2026-04-22 17:10 CEST:

- toda entrada nueva de estado, decisión o sesión debe llevar marca temporal explícita;
- formato preferido: `YYYY-MM-DD HH:MM CEST/CET`;
- si se actualiza una sección existente, añadir la marca temporal junto al cambio o en la cabecera de la sección;
- las entradas antiguas sin hora se mantienen como histórico y no deben considerarse más recientes que una entrada con marca temporal posterior.

## Importante sobre este workspace

La carpeta actual `/home/aviddrianimachie/CEAC/Proyecto` estaba vacía al generar el primer contexto, salvo el archivo `.codex`.

Ese estado inicial ya no aplica. Revisión de coherencia del 2026-04-22 17:10 CEST:

- existe backend Laravel real en `backend/`;
- existe cliente Android real en `android/`;
- existe módulo analítico en `analytics/`;
- existe `compose.yaml` para PostgreSQL/PostGIS;
- el contexto técnico reciente es más fiable que las notas históricas de arranque.

Las notas antiguas sobre una carpeta sin código deben tratarse como histórico, no como estado vigente.

## Objetivo documental

Esto está pensado para un TFC de DAM. El tono y el contenido intentan equilibrar dos cosas:

- servir como memoria operativa para futuras sesiones de trabajo;
- dejar una explicación defendible del proyecto, su stack y sus decisiones técnicas.
