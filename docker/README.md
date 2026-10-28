# Local HumHub 1.17 Docker environment

Pinned to **HumHub 1.17.x** to match production. Do not switch to `stable-nightly` or `1.18`.

## Image note

Docker Hub has **no** `humhub/humhub:1.17` tag (the official `humhub/humhub` registry starts at 1.18). This stack uses:

```text
mriedmann/humhub:1.17.1
```

That is the maintained all-in-one image that still ships HumHub 1.17.x. On Apple Silicon the image is `linux/amd64` (emulated).

## Prerequisites

- Docker Engine ≥ 20.10.13 and Compose v2
- A throwaway JaaS PEM key at `docker/keys/jaas_private.pem` (see `docker/keys/README.md`)

```bash
openssl genrsa -out docker/keys/jaas_private.pem 2048
```

## Up / down

From the **module repo root** (`jitsi-meet/`):

```bash
docker compose -f docker/docker-compose.dev.yml up -d
docker compose -f docker/docker-compose.dev.yml ps
docker compose -f docker/docker-compose.dev.yml down
# Wipe named volumes (full reset):
docker compose -f docker/docker-compose.dev.yml down -v
```

HumHub is published at **http://localhost:9091** (host `9091` → container `80`).

Persistence:

| Data | Storage |
|------|---------|
| HumHub config | named volume `humhub-config` |
| Uploads | named volume `humhub-uploads` |
| MariaDB | named volume `mysql-data` |
| Module source | bind-mount of this repo |
| JaaS key | bind-mount `docker/keys/jaas_private.pem` (read-only) |

Named volumes are intentional: on Docker Desktop for Mac, bind-mounted config files often appear as `root:root` inside the container and PHP `is_writable()` fails on `config/dynamic.php`. `docker/entrypoint.d/40-fix-perms.sh` chowns the named volumes on every start.

Host paths `docker/humhub-data/` and `docker/mysql-data/` remain gitignored for local leftovers.

## First-run install

`HUMHUB_AUTO_INSTALL=1` creates the site and admin account on first boot.

- URL: http://localhost:9091
- Admin login: `humhub` / `humhub`

If auto-install fails (rare under amd64 emulation), wait until MariaDB is healthy and re-run:

```bash
docker compose -f docker/docker-compose.dev.yml exec humhub \
  php /var/www/localhost/htdocs/protected/yii installer/install-db
```

## Enable this module

Confirmed 1.17.1 module path (do not invent a different one without re-checking):

```text
/var/www/localhost/htdocs/protected/modules/jitsi-meet-cloud-8x8
```

This repo is bind-mounted there. Enable via UI (**Administration → Modules**) or CLI:

```bash
docker compose -f docker/docker-compose.dev.yml exec humhub \
  php /var/www/localhost/htdocs/protected/yii module/enable jitsi-meet-cloud-8x8 --interactive=0
```

Confirm:

```bash
docker compose -f docker/docker-compose.dev.yml exec humhub \
  ls -la /var/www/localhost/htdocs/protected/modules/jitsi-meet-cloud-8x8/module.json
```

## Admin login

`humhub` / `humhub` (from auto-install env).

## Logs

```bash
docker compose -f docker/docker-compose.dev.yml logs -f humhub
docker compose -f docker/docker-compose.dev.yml logs -f db
```

Inside HumHub: Administration → Information → Logging.

## PHP lint and Composer inside the container

No PHP on the host for lint — use the container:

```bash
docker compose -f docker/docker-compose.dev.yml exec humhub \
  php -l /var/www/localhost/htdocs/protected/modules/jitsi-meet-cloud-8x8/Module.php
```

`composer` is **not** shipped in `mriedmann/humhub:1.17.1`. Install module PHP deps on a host with Composer, or exec a temporary Composer image against the module mount:

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 composer install --ignore-platform-reqs
```

Yii console:

```bash
docker compose -f docker/docker-compose.dev.yml exec humhub \
  php /var/www/localhost/htdocs/protected/yii
```

## Webhook signing harness

Set a local secret (once):

```bash
docker compose -f docker/docker-compose.dev.yml exec humhub \
  php /var/www/localhost/htdocs/protected/yii settings/set jitsi-meet-cloud-8x8 jaasWebhookSecret 'dev-webhook-secret' --interactive=0
```

Use the pretty URL (the `?r=` form redirects to login):

```bash
# From inside the container
docker compose -f docker/docker-compose.dev.yml exec humhub \
  php /var/www/localhost/htdocs/protected/modules/jitsi-meet-cloud-8x8/tools/webhook-sign.php \
    --payload=/var/www/localhost/htdocs/protected/modules/jitsi-meet-cloud-8x8/tools/sample-webhook-payload.json \
    --secret='dev-webhook-secret' \
    --url=http://localhost/jitsi-meet-cloud-8x8/webhook

# Fail-closed probes (Child 3)
# --tamper  → 401
# --stale   → 401
```

`--dry-run` prints the `X-Jaas-Signature` header without POSTing.

Algorithm (matches `WebhookController::verifySignature()`):

- Header: `X-Jaas-Signature: t=<unix_seconds>,v1=<base64>`
- Signed payload: `<timestamp>.<raw_json_body>`
- HMAC-SHA256, raw binary, then base64

## JaaS private key mount

`docker/keys/jaas_private.pem` → `/var/www/keys/jaas_private.pem` (read-only).  
Env: `HUMHUB_JAAS_PRIVATE_KEY_PATH=/var/www/keys/jaas_private.pem`.  
**Never commit a real key.**
