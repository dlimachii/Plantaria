# Plantaria Docs

Portal de documentación técnica de la rama `TFG`.

## Qué encontrarás aquí

- arquitectura general del proyecto;
- contrato principal de la API;
- guía de despliegue local y VPS;
- guía de demo;
- memoria técnica base;
- referencia técnica resumida para lectura rápida.

## Rutas recomendadas

- [Referencia técnica](REFERENCIA_TECNICA.md)
- [API](API.md)
- [Backend](../backend/README.md)
- [Android](../android/README.md)
- [Despliegue VPS](DEPLOY_VPS.md)
- [Guía de demo](GUIA_DEMO.md)
- [Memoria técnica](MEMORIA_TFC.md)

## Servir la documentación

Desde la raíz del repositorio:

```bash
./scripts/serve_docs.sh
```

Después abre:

```text
http://127.0.0.1:4173
```

## Documentación Android opcional

Si además quieres una referencia HTML de API para el cliente Android:

```bash
./scripts/generate_technical_docs.sh
```

Salida:

```text
android/app/build/documentation/html/index.html
```
