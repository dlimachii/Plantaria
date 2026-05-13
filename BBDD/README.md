# BBDD demo de Plantaria

Esta carpeta esta pensada para la defensa del TFG.

PostgreSQL/PostGIS no guarda la base de datos como un unico archivo `.db` dentro del
repositorio. En desarrollo local, la BBDD real se levanta con `compose.yaml` y Docker
guarda los datos fuera del codigo, en un volumen persistente.

En este equipo, el volumen local verificado es:

```text
/var/lib/docker/volumes/proyecto_plantaria_postgis_data/_data
```

Para que el profesor pueda ver la base de datos desde GitHub, se incluye una exportacion
SQL limpia y reducida:

```text
BBDD/plantaria_demo.sql
```

## Que contiene

- 4 usuarios de demo: admin, usuario demo, usuario normal y moderador.
- 4 registros de plantas geolocalizadas en Barcelona.
- 4 observaciones iniciales asociadas a esos registros.
- Tablas principales del dominio: usuarios, registros, observaciones, flags y eventos.

No es un volcado completo del entorno local: se han dejado fuera sesiones, tokens,
cache, jobs, logs y eventos de pruebas manuales.

Los datos insertados son los datos demo por defecto del seeder del proyecto. Las tablas
`moderation_flags` y `app_events` aparecen en el SQL porque forman parte del esquema,
pero se dejan vacias si no tienen datos iniciales por defecto.

## Donde esta el esquema real

El esquema que usa la aplicacion esta definido en:

```text
backend/database/migrations/
```

Los datos demo que se generan al hacer `php artisan migrate --seed` estan en:

```text
backend/database/seeders/DatabaseSeeder.php
```

## Como explicarlo en la presentacion

Frase corta:

> La BBDD real es PostgreSQL/PostGIS. No es un fichero dentro del repo: en local vive en
> un volumen Docker. Para que pueda revisarse desde GitHub, he incluido aqui una
> exportacion SQL demo con las tablas principales y datos representativos.
