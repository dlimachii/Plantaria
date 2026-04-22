# Instrucciones de arranque para Codex

## Lectura obligatoria al comenzar

Antes de responder o trabajar sobre este proyecto, leer en este orden:

1. `Contexto/Contexto.md`
2. `Contexto/ContextoGeneral.md`

## Lectura adicional solo si hace falta

Abrir estos archivos solo cuando la tarea lo requiera:

- `Contexto/ContextoEspecifico.md`
- `Contexto/EntornoYVersiones.md`
- `Contexto/DudasYDecisiones.md`
- `Contexto/RegistroDeSesiones.md`

## Regla de uso

- No inventar detalles del proyecto si todavía no existen materiales reales.
- Usar `Contexto/ContextoGeneral.md` como resumen de entrada.
- Usar `Contexto/ContextoEspecifico.md` solo cuando el general no baste.
- Registrar decisiones nuevas en `Contexto/DudasYDecisiones.md`.
- Registrar trabajo relevante en `Contexto/RegistroDeSesiones.md`.
- Si cambia el procedimiento, actualizar primero `Contexto/Contexto.md`.
- Desde 2026-04-22 17:10 CEST, toda entrada nueva de contexto, decisión o sesión debe llevar fecha y hora local en formato `YYYY-MM-DD HH:MM CEST/CET`.

## Estado actual

Esta estructura de contexto define el procedimiento de trabajo entre sesiones y ya debe leerse junto al estado real del proyecto.

El workspace ya contiene materiales reales de `Plantaria`:

- backend Laravel en `backend/`;
- cliente Android Kotlin/Jetpack Compose en `android/`;
- módulo analítico Python en `analytics/`;
- `compose.yaml` para PostgreSQL/PostGIS local.
- repositorio Git inicializado en la raíz, rama `main`, con `.gitignore` raíz para excluir dependencias, builds y ficheros locales.

No asumir más arquitectura, stack ni funcionalidad que la documentada o verificada en el árbol real del proyecto.
