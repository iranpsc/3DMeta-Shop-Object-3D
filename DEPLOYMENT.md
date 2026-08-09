# Dokploy Deployment Guide — 3D RGB Backend API

This document explains how to deploy this Laravel backend API to [Dokploy](https://dokploy.com) using the included Docker setup.

## Overview

| Item | Detail |
|------|--------|
| App type in Dokploy | **Docker Compose** |
| Compose file | `docker-compose.yml` |
| App Dockerfile | `Dockerfile` (multi-stage, PHP 8.4) |
| Base images | Arvan Cloud mirror (`docker.arvancloud.ir`) |
| Services | `app` (API), `mysql`, `redis` |
| Container HTTP port | `80` |
| Health check | `GET /up` |
| Queue worker | Runs inside `app` via Supervisor |
| Scheduler | `schedule:work` in Supervisor — runs `sitemap:generate` every 3 hours |

The `app` container runs **Nginx + PHP-FPM + queue worker**. On boot the entrypoint can wait for MySQL, cache config, run migrations, and create the storage symlink.

> Prefer **Docker Compose** over the single “Application” type so MySQL and Redis stay on the same private network as the API.

---

## Prerequisites

1. A Dokploy server with Docker installed and enough disk/RAM for MySQL + Redis + the API image.
2. Access to this Git repository (GitHub / GitLab / Gitea / Bitbucket).
3. A domain (or subdomain) for the API, e.g. `api.example.com`.
4. Outbound access from the Dokploy host to:
   - your Git provider
   - `docker.arvancloud.ir` (base images)
   - Composer / Packagist mirrors used during image build

---

## Repository layout (Docker-related)

```text
.
├── Dockerfile                 # Multi-stage Laravel API image
├── docker-compose.yml         # app + mysql + redis
├── .dockerignore
├── .env.docker.example        # Sample env values for local / Dokploy
└── docker/
    ├── entrypoint.sh
    ├── supervisord.conf
    ├── nginx/default.conf
    └── php/
        ├── php.ini
        └── opcache.ini
```

---

## 1. Create the project in Dokploy

1. Open Dokploy → **Create Project** (or use an existing project).
2. Click **Create Service** → choose **Docker Compose**.
3. Select your **Git provider** and repository for this backend.
4. Set:
   - **Branch**: `main` (or your production branch)
   - **Compose path**: `docker-compose.yml` (repo root)
5. Save the service.

Dokploy will clone the repo and use `docker-compose.yml` for deploy.

---

## 2. Configure environment variables

Open the Compose service → **Environment** tab.

Dokploy writes these values to a `.env` file next to the compose file. This project already references them with `${VAR}` in `docker-compose.yml`.

### Required / recommended

Generate a stable Laravel key once (do not leave `APP_KEY` empty in production — an empty key is auto-generated on each boot and will break sessions/cookies):

```bash
# On any machine with PHP/Laravel or openssl:
openssl rand -base64 32
# Then set: APP_KEY=base64:<output>
```

Or run locally:

```bash
php artisan key:generate --show
```

Paste values into Dokploy Environment (one `KEY=value` per line):

```env
# Public URL / port mapping
APP_PORT=8080
APP_URL=https://api.example.com
APP_KEY=base64:REPLACE_WITH_STABLE_KEY

# Database (shared by app + mysql service)
DB_DATABASE=3drgb
DB_USERNAME=3drgb
DB_PASSWORD=CHANGE_ME_STRONG_PASSWORD
DB_ROOT_PASSWORD=CHANGE_ME_ROOT_PASSWORD

# Optional host port mappings (prefer leaving DB/Redis closed in production)
MYSQL_PORT=3306
REDIS_PORT=6379

# Boot behaviour
RUN_MIGRATIONS=true

# Frontend / Sanctum / CORS (adjust to your Next.js / SPA domains)
FRONTEND_URL=https://example.com
SESSION_DOMAIN=.example.com
SANCTUM_STATEFUL_DOMAINS=example.com,api.example.com
CORS_ALLOWED_ORIGINS=https://example.com
```

### Notes

| Variable | Purpose |
|----------|---------|
| `APP_URL` | Canonical API URL (used by Laravel URL generation) |
| `APP_KEY` | Encryption / session signing — **must be stable** |
| `RUN_MIGRATIONS` | `true` runs `php artisan migrate --force` on container start |
| `DB_HOST` / `REDIS_HOST` | Already set in compose to `mysql` / `redis` service names — do not point them at `localhost` |
| `CACHE_DRIVER` / `SESSION_DRIVER` / `QUEUE_CONNECTION` | Set to `redis` in compose |

Use strong unique passwords for `DB_PASSWORD` and `DB_ROOT_PASSWORD`.

---

## 3. Domains & Traefik / proxy

1. Open the Compose service → **Domains**.
2. Add a domain pointing at the **`app`** service.
3. Set the container port to **`80`** (Nginx inside the image).
4. Enable HTTPS / Let’s Encrypt if your Dokploy instance supports it.
5. Set `APP_URL` to the same public HTTPS URL.

Example:

| Field | Value |
|-------|--------|
| Domain | `api.example.com` |
| Service | `app` |
| Container port | `80` |
| HTTPS | Enabled |

The API trusts forwarded headers (`X-Forwarded-*`) so HTTPS termination at Dokploy/Traefik works correctly.

---

## 4. Volumes & persistence

### Named volumes (MySQL / Redis)

| Volume | Purpose |
|--------|---------|
| `mysql_data` | MySQL data |
| `redis_data` | Redis AOF data |

Use Dokploy **Volume Backups** for these named volumes when you need automated DB/cache backups.

### Host bind mounts (uploads + sitemaps)

The `app` service binds durable file paths to the host under `/opt/3dmeta` (override with env vars if needed):

| Host path | Container path | Contents |
|-----------|----------------|----------|
| `/opt/3dmeta/storage/app` | `/var/www/html/storage/app` | Uploads / local disk files |
| `/opt/3dmeta/sitemap` | `/var/www/html/public/sitemap` | Generated sitemap XML |

Only these narrow paths are mounted — **not** all of `storage/` (so `storage/framework` and `storage/logs` stay in the container) and **not** all of `public/`.

**Prepare the host once** (on the Dokploy server):

```bash
sudo mkdir -p /opt/3dmeta/storage/app /opt/3dmeta/sitemap
# Alpine www-data is typically UID/GID 82 — confirm with: docker compose exec app id www-data
sudo chown -R 82:82 /opt/3dmeta
sudo chmod -R ug+rwX /opt/3dmeta
```

Optional overrides in Dokploy **Environment** (or `.env`):

```env
DATA_STORAGE_PATH=/opt/3dmeta/storage/app
DATA_SITEMAP_PATH=/opt/3dmeta/sitemap
```

For Dokploy-managed file mounts instead of `/opt`, you can set:

```env
DATA_STORAGE_PATH=../files/3dmeta/storage/app
DATA_SITEMAP_PATH=../files/3dmeta/sitemap
```

Back up `/opt/3dmeta` (or your override paths) with your own host/S3 backup job — bind mounts are **not** covered by Dokploy Volume Backups.

---

## 5. Deploy

1. Open **Deployments** (or General) → click **Deploy**.
2. Watch the build logs. The image build will:
   - Pull Arvan Cloud base images (`php`, `composer`, `mysql`, `redis`)
   - Install Composer dependencies (production, no npm)
   - Produce the runtime image with Nginx + PHP-FPM + Supervisor
3. After deploy, confirm services in **Monitoring** / container list:
   - `app` → healthy
   - `mysql` → healthy
   - `redis` → healthy

### Auto-deploy (optional)

In **Deployments**, enable the Git webhook so pushes to the selected branch trigger a new deploy.

---

## 6. Verify the deployment

### Health endpoint

```bash
curl -fsS https://api.example.com/up
```

Expect HTTP **200** and an “Application up” response.

### Logs

In Dokploy → **Logs**, select:

- `app` — entrypoint, Nginx, PHP-FPM, queue worker
- `mysql` / `redis` — database / cache issues

On first boot you should see migrations and supervisord starting PHP-FPM, Nginx, and the queue worker.

### Quick API smoke check

```bash
curl -i https://api.example.com/api/v1/...
```

(Use a real public route from your API.)

---

## 7. What happens on container start

`docker/entrypoint.sh` runs before Supervisor:

1. Ensures `storage/` and `bootstrap/cache` are writable  
2. Generates `APP_KEY` only if it is empty (prefer setting it in Dokploy)  
3. Waits for MySQL (`DB_HOST`)  
4. Runs `config:cache`, `route:cache`, `view:cache`  
5. Runs migrations when `RUN_MIGRATIONS=true`  
6. Runs `storage:link`  
7. Starts Supervisor → PHP-FPM + Nginx + `queue:work` + `schedule:work`  

`schedule:work` triggers `php artisan sitemap:generate` every **3 hours**. That command dispatches `SitemapGenerator` to the queue; the queue worker writes XML files into `public/sitemap` (host: `/opt/3dmeta/sitemap`).

---

## 8. Updating / redeploying

1. Push to the configured branch (or click **Deploy**).
2. Dokploy rebuilds the `app` image and recreates containers.
3. MySQL/Redis data remains in named volumes.
4. Keep `RUN_MIGRATIONS=true` if you want migrations applied automatically on each deploy, or set it to `false` and run migrations manually when you prefer controlled releases:

```bash
# From Dokploy terminal on the app service
php artisan migrate --force
```

After changing env vars that affect cached config, redeploy (or clear caches) so `config:cache` picks up the new values.

---

## 9. Production recommendations

1. **Do not publish MySQL/Redis ports** to the public internet. Prefer removing `ports:` from `mysql` and `redis` in compose for production, or firewall them on the host. Keep only the API reachable via Dokploy domain routing.
2. Set `APP_DEBUG=false` and a stable `APP_KEY`.
3. Align `FRONTEND_URL`, `SESSION_DOMAIN`, `SANCTUM_STATEFUL_DOMAINS`, and `CORS_ALLOWED_ORIGINS` with your real SPA domains.
4. Enable HTTPS on the Dokploy domain.
5. Configure Volume Backups for `mysql_data` (and app storage if mounted).
6. Monitor CPU/memory for `app` and `mysql` under Dokploy **Monitoring**.

---

## 10. Local verification (optional)

Before Dokploy, you can validate the same stack locally:

```bash
# Copy sample env (edit secrets as needed)
cp .env.docker.example .env

# Build & start
docker compose up -d --build

# Health
curl -fsS http://localhost:8080/up
```

Stop:

```bash
docker compose down
```

---

## 11. Troubleshooting

| Symptom | Likely cause | Fix |
|---------|--------------|-----|
| Build cannot pull images | Host cannot reach Arvan registry | Check DNS/network; confirm `docker.arvancloud.ir` is reachable from the Dokploy server |
| `app` unhealthy / `/up` fails | App still booting, DB down, or bad env | Check `app` logs; wait for MySQL healthy; verify `APP_KEY`, DB credentials |
| Composer platform / PHP errors | Wrong PHP version | Image must be PHP **8.4** (default in Dockerfile) |
| Sessions / auth break after restart | `APP_KEY` regenerated each boot | Set a permanent `APP_KEY` in Dokploy Environment |
| CORS / Sanctum cookie issues | Domain mismatch | Update `FRONTEND_URL`, `SESSION_DOMAIN`, `SANCTUM_STATEFUL_DOMAINS`, `CORS_ALLOWED_ORIGINS` |
| Migrations not applied | `RUN_MIGRATIONS=false` or DB not ready | Set `RUN_MIGRATIONS=true` or run `php artisan migrate --force` in the app terminal |
| Uploaded files lost after redeploy | Bind mounts missing or wrong path | Ensure `/opt/3dmeta/storage/app` and `/opt/3dmeta/sitemap` exist and are mounted (see §4) |
| Permission denied writing uploads/sitemaps | Host dir owned by root | `chown -R 82:82 /opt/3dmeta` (confirm UID with `id www-data` in the container) |

---

## Reference: service map

```text
Internet
   │
   ▼
Dokploy proxy (HTTPS) ──► app:80 (Nginx → PHP-FPM)
                              │
                              ├── mysql:3306
                              └── redis:6379
```

Internal DNS names (`mysql`, `redis`) only work between containers on the compose network. Do not use `127.0.0.1` for DB/Redis from the `app` container.
