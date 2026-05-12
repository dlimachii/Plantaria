# Despliegue en VPS (demo)

Este documento permite desplegar el backend de Plantaria en un VPS usando Docker Compose.

Requisitos:

- VPS Ubuntu con puertos 80/443 abiertos.
- Docker y docker compose instalados.
- DNS: crear un registro `A` para `api.dlimachii.com` apuntando a la IP del VPS.

Pasos:

```bash
sudo apt-get update
sudo apt-get install -y git
```

Clonar el repo:

```bash
sudo mkdir -p /var/www
cd /var/www
sudo git clone https://github.com/TU_USUARIO/Plantaria.git
cd Plantaria/deploy/vps
```

Configurar variables:

```bash
cp .env.example .env
nano .env
```

Arrancar:

```bash
sudo docker compose up -d --build
```

Comprobar:

- Panel admin: `https://api.dlimachii.com/admin`
- API: `https://api.dlimachii.com/api/records`

Notas:

- Para demo, `MIGRATE_ON_BOOT=1` y `SEED_ON_BOOT=1` inicializan la base al primer arranque.
- El asistente Ollama queda visible pero deshabilitado con `OLLAMA_ENABLED=false`.
