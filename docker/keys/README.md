# Dev JaaS private key (gitignored)

Place a PEM private key at `jaas_private.pem` in this directory. The compose file mounts it read-only at `/var/www/keys/jaas_private.pem` and sets `HUMHUB_JAAS_PRIVATE_KEY_PATH` to that path.

## Generate a throwaway key for local use

```bash
openssl genrsa -out docker/keys/jaas_private.pem 2048
```

## Rules

- **Never commit a real production JaaS key.** Only `.gitkeep` and this README are tracked.
- `docker/keys/*` is gitignored except `.gitkeep` and `README.md`.
- The mounted key is for local JWT signing during module development only.
