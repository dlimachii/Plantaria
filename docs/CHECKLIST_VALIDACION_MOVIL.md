# Checklist de validacion movil

Usar esta lista para cerrar la prueba fisica del APK actual.

## Preparacion

- [ ] `docker compose ps` muestra `plantaria-postgis` healthy.
- [ ] `./scripts/start_mobile_stack.sh` esta ejecutandose.
- [ ] `adb devices` detecta el telefono.
- [ ] `adb reverse tcp:8000 tcp:8000` ejecutado si se usa USB.
- [ ] APK instalado con `./scripts/install_debug_apk.sh`.
- [ ] URL API en Android: `http://127.0.0.1:8000/api/` para USB o IP LAN para Wi-Fi.

## Sesion

- [ ] La app abre sin cierre inesperado.
- [ ] Login con `plantaria_demo` funciona.
- [ ] La sesion queda guardada al cerrar y reabrir la app.
- [ ] Logout funciona y vuelve a autenticacion.

## Mapa

- [ ] El mapa carga tiles.
- [ ] Los registros demo aparecen como marcadores.
- [ ] Tocar marcador abre preview.
- [ ] Cerrar preview funciona.
- [ ] Tocar preview abre ficha completa.
- [ ] Ficha muestra foto principal.
- [ ] Ficha muestra observaciones.
- [ ] `Anadir observacion` abre `Acciones` con ID prellenado.

## Busqueda

- [ ] Busqueda de registros por `Lavanda` filtra resultados.
- [ ] Limpiar busqueda recupera registros.
- [ ] Busqueda de zona por `Barcelona` recentra mapa.
- [ ] Busqueda por coordenadas `41.3851, 2.1734` recentra mapa.
- [ ] El foco de busqueda se diferencia visualmente de los registros.

## Ubicacion

- [ ] La app solicita permisos de ubicacion cuando corresponde.
- [ ] `Mi ubicacion` centra el mapa si el dispositivo devuelve posicion.
- [ ] El marcador de usuario se distingue de los registros.
- [ ] En `Acciones`, `Usar ubicacion actual` rellena latitud y longitud.
- [ ] Si GPS falla, las coordenadas manuales permiten continuar.

## Fotos

- [ ] Seleccionar foto desde galeria funciona.
- [ ] Hacer foto con camara funciona.
- [ ] La app conserva la foto seleccionada durante el flujo.
- [ ] La subida no falla con foto real de movil.
- [ ] Si falla, el mensaje es comprensible.

## Crear reporte

- [ ] Crear reporte con foto de galeria.
- [ ] Crear reporte con foto de camara.
- [ ] El reporte nuevo aparece en el mapa tras refrescar.
- [ ] La ficha del reporte nuevo se abre correctamente.
- [ ] La foto se sirve desde el backend.

## Crear observacion

- [ ] Crear observacion desde ID prellenado.
- [ ] Crear observacion con foto de galeria.
- [ ] Crear observacion con foto de camara.
- [ ] La observacion aparece en la ficha del registro.

## Panel web tras la prueba

- [ ] Entrar en `/admin`.
- [ ] Ver el reporte nuevo en cola o listado.
- [ ] Ver detalle del reporte.
- [ ] Verificar o rechazar reporte.
- [ ] Comprobar que la app refleja el cambio tras refrescar.

## Resultado

Fecha/hora:

```text

```

Telefono probado:

```text

```

Conexion usada:

```text
USB / Wi-Fi / Emulador
```

Incidencias:

```text

```

Conclusion:

```text
Validado / Validado con incidencias / No validado
```
