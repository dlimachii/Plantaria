# API de Plantaria

Base local habitual:

```text
http://127.0.0.1:8000/api/
```

En emulador Android:

```text
http://10.0.2.2:8000/api/
```

Las rutas autenticadas usan:

```text
Authorization: Bearer <token>
Accept: application/json
```

Las rutas autenticadas exigen que la cuenta siga activa. Si un usuario queda bloqueado, los tokens existentes dejan de poder usar la API y reciben `403 Cuenta no activa.`.

## Autenticacion

### Registro

```http
POST /api/auth/register
```

```json
{
  "handle": "plantaria_demo",
  "display_name": "Plantaria Demo",
  "email": "demo@example.com",
  "password": "Password1",
  "password_confirmation": "Password1",
  "country": "Espana",
  "province": "Barcelona",
  "city": "Barcelona",
  "birthdate": "2000-01-01",
  "device_name": "android"
}
```

Devuelve `token` y `user`.

### Login

```http
POST /api/auth/login
```

```json
{
  "handle": "plantaria_demo",
  "password": "PlantariaDemo1",
  "device_name": "android"
}
```

### Usuario actual

```http
GET /api/auth/me
```

Requiere token.

### Logout

```http
POST /api/auth/logout
```

Requiere token.

## Registros

### Listar registros

```http
GET /api/records
```

Filtros:

- `q`: ID publico, nombre provisional, nombre comun validado o nombre cientifico.
- `status`: `pending`, `verified`, `rejected`.
- `limit`: de `1` a `100`.
- `latitude`, `longitude`, `radius_km`: filtro por radio.

Ejemplos:

```http
GET /api/records?q=Lavanda&limit=5
GET /api/records?status=verified&limit=20
GET /api/records?latitude=41.3851&longitude=2.1734&radius_km=8&limit=10
```

Cuando se usa radio, PostgreSQL/PostGIS calcula la distancia y la respuesta incluye `distance_km`.

### Ver ficha

```http
GET /api/records/{publicId}
```

Devuelve el registro con observaciones.

### Crear reporte

```http
POST /api/records
```

Requiere token.

```json
{
  "provisional_common_name": "Lavanda",
  "description": "Mata aromatica en zona ajardinada",
  "primary_photo_path": "uploads/lavanda.jpg",
  "plant_condition": "good",
  "latitude": 41.36355,
  "longitude": 2.15766
}
```

`plant_condition` puede ser `good`, `regular`, `bad`, `dry` o `unknown`.

## Observaciones

### Crear observacion

```http
POST /api/records/{publicId}/observations
```

Requiere token.

```json
{
  "photo_path": "uploads/lavanda-semana-2.jpg",
  "note": "Floracion mas visible",
  "plant_condition": "good",
  "latitude": 41.36355,
  "longitude": 2.15766
}
```

## Fotos

### Subir foto

```http
POST /api/uploads/photos
```

Requiere token y `multipart/form-data`:

```text
photo=<archivo jpg/png/webp>
```

Devuelve:

```json
{
  "data": {
    "path": "uploads/archivo.jpg",
    "url": "http://127.0.0.1:8000/storage/uploads/archivo.jpg"
  }
}
```

## Perfiles

### Perfil publico

```http
GET /api/profiles/{handle}
```

### Actualizar perfil propio

```http
PATCH /api/profile
```

Requiere token.

## Flags

### Crear denuncia

```http
POST /api/flags
```

Requiere token.

```json
{
  "target_type": "record",
  "target_reference": "PLANTARIADEMOBCN000001",
  "reason": "Contenido incorrecto"
}
```

`target_reference` es el ID publico del registro, el ID publico de la observacion o el `handle` del usuario, segun `target_type`.

## Geocodificacion

### Buscar lugar

```http
GET /api/geocoding/search?q=Barcelona&limit=5
```

Devuelve resultados normalizados:

```json
{
  "data": [
    {
      "display_name": "Barcelona, Barcelones, Barcelona, Catalunya, Espana",
      "latitude": 41.38289,
      "longitude": 2.17743,
      "type": "city",
      "category": "place"
    }
  ]
}
```

La ruta usa Nominatim desde el backend y cachea resultados.

## Administracion API

Estas rutas requieren token de usuario `MOD` o `ADMIN` segun el caso:

- `GET /api/admin/analytics/summary`
- `GET /api/admin/analytics/trends`
- `GET /api/admin/analytics/top-searches`
- `GET /api/admin/moderation/pending`
- `POST /api/admin/moderation/records/{publicId}/verify`
- `GET /api/admin/moderation/flags`
- `POST /api/admin/moderation/flags/{uid}/resolve`
- `GET /api/admin/users`
- `GET /api/admin/users/{handle}`
- `PATCH /api/admin/users/{handle}`
- `POST /api/admin/users/{handle}/ban`
- `DELETE /api/admin/users/{handle}`

El panel web administrativo cubre estos flujos desde `/admin`.

Reglas principales:

- Analitica API: solo `ADMIN`.
- Gestion de usuarios API: solo `ADMIN`.
- Moderacion y flags API: `MOD` o `ADMIN`.
