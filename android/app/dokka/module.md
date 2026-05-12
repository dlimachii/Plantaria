# Module Plantaria Android

Cliente Android oficial de Plantaria.

## Alcance del módulo

- autenticación contra Laravel con token;
- persistencia ligera de sesión y servidor;
- mapa MapLibre con registros y detalle;
- creación de reportes y observaciones con foto;
- perfil y actividad propia del usuario.

## Relación con el resto del proyecto

Este módulo consume la API definida en `backend/`, usa `compose.yaml` para desarrollo local
y puede apuntar tanto a un backend local como a un despliegue público configurable.
