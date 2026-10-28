# Contributing

Thank you for contributing to the Jitsi Meet Cloud (8x8 JaaS) HumHub module.

## Branch naming

Use descriptive branch names off `jitsi-meet-improved`:

| Prefix | Use |
|--------|-----|
| `fix/` | Bug fixes and security patches |
| `feat/` | New features |
| `chore/` | Tooling, Docker, CI, docs |
| `docs/` | Documentation-only changes |

Example: `fix/security-webhook-failclosed`, `docs/repo-packaging`.

## Development environment

1. Check out `jitsi-meet-improved` (or a feature branch based on it).
2. Start the local HumHub 1.17 stack (from the `chore/dev-docker-env` branch):

   ```bash
   docker compose -f docker/docker-compose.dev.yml up -d
   ```

3. Open [http://localhost:9091](http://localhost:9091) and enable the module.
4. Edit module files on the host — they are bind-mounted into the container.

**Do not run PHP on the host.** Lint inside the container:

```bash
docker compose -f docker/docker-compose.dev.yml exec humhub php -l path/to/file.php
```

## HumHub 1.17 / Bootstrap 3 constraint

This module targets **HumHub 1.17 with Bootstrap 3**. Do **not** modernize markup:

| Do not use (BS4/5) | Keep (BS3) |
|--------------------|------------|
| `data-bs-dismiss` | `data-dismiss` |
| `card`, `card-body` | `panel`, `panel-body`, `panel-heading` |
| `badge`, `text-bg-*` | `label label-success`, etc. |
| `d-none`, `ms-*`, `me-*` | `hidden`, `pull-left`, etc. |

HumHub 1.18 / Bootstrap 5 support is future work on a separate branch.

## Code style

- **PHP CS Fixer** — CI runs on push via `.github/workflows/php-cs-fixer.yml`. Match existing style in surrounding files.
- **Rector** — Automated refactoring PRs may be proposed via CI when configured.

Run fixers locally inside Docker if needed, or let CI handle formatting.

## Pull requests

1. Branch from `jitsi-meet-improved`, not `master`.
2. Keep changes focused; one concern per PR when possible.
3. Push your branch early: `git push -u origin <branch>`.
4. Open a PR targeting `jitsi-meet-improved`.
5. For security changes, note verification steps and avoid logging secrets (JWTs, HMAC values, private keys).

## Testing

There is no automated test suite. Verify manually:

- Module enable/disable lifecycle
- JaaS and self-hosted modes
- Permission matrix with admin, member, and guest accounts
- Webhook signature verification (when Docker harness is available)

Document results in [docs/QA/manual-verification.md](docs/QA/manual-verification.md).

## Documentation

- User-facing docs: root `README.md` and `docs/`
- Changelog: `docs/CHANGELOG.md` — include **BREAKING** headings for behaviour changes
- License: AGPL-3.0 — preserve upstream attribution to [jhweb/jitsi-meet](https://github.com/jhweb/jitsi-meet)

## Questions

Open a [GitHub Discussion](https://github.com/jhweb/jitsi-meet/discussions) or issue for non-security questions.
