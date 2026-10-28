---
name: Standalone repo packaging and docs
disposition: active
parent: jitsi_module_hardening_meta_f78868ce
branch: docs/repo-packaging
branches:
  - docs/repo-packaging
base_branch: jitsi-meet-improved
stage: 3
priority: P2
owner: "@ettienne"
dri: "@ettienne"
ide: cursor
todos:
  - id: c9-readme
    content: "Root README.md — purpose, HumHub/PHP compatibility, committed screenshots, Marketplace and manual install, 8x8 JaaS setup with mandatory webhook secret, self-hosted setup, permission matrix, Calendar dependency, Docker dev loop, QA pointer, CSP troubleshooting, upgrade notes with the breaking webhook change, license and upstream attribution"
    status: completed
  - id: c9-license
    content: "Add canonical AGPL-3.0 LICENSE at repo root (currently only docs/LICENCE.md, which GitHub does not detect)"
    status: completed
  - id: c9-security-md
    content: "SECURITY.md — private reporting contact and supported versions"
    status: completed
  - id: c9-contributing
    content: "CONTRIBUTING.md — branch naming, Docker loop, PHP CS Fixer, Rector, and the Bootstrap 3 constraint so contributors do not modernize the markup"
    status: completed
  - id: c9-module-json
    content: "module.json — version 1.3.0, humhub.minVersion 1.17 justified by PHP 8.1 and the ui\\form\\widgets\\ActiveForm API (not Bootstrap 5), preserving Child 2's AGPL-3.0 upstream attribution description"
    status: in_progress
  - id: c9-composer-php
    content: "composer.json — raise php from >=7.4 to >=8.1 while preserving Child 2's package name 'jhweb/jitsi-meet' and AGPL-3.0 attribution description"
    status: in_progress
  - id: c9-composer-lock
    content: "Generate composer.lock inside the Child 1 container to pin firebase/php-jwt, and commit it (still missing on docs/repo-packaging)"
    status: pending
  - id: c9-docs-cleanup
    content: "docs/ cleanup — rewrite stale docs/TODO.md 'Implement JWT support' entry, fold generic marketing copy from docs/README.md into root README, keep and cross-link CSP guidance in docs/INSTALLATION.md"
    status: completed
  - id: c9-changelog
    content: "docs/CHANGELOG.md 1.3.0 entry with a prominent BREAKING heading (webhook secret + permissions) while preserving upstream 1.2.2 and 1.2.3 (meet.ffmuc.net removal) entries from jitsi-meet-improved"
    status: in_progress
  - id: c9-ci
    content: "Add .github/workflows/php-syntax-check.yml AND rename .github/workflows/rector-auto-pr.yaml.yml to rector-auto-pr.yml; confirm marketplace-upload.yml targets post-rename identity"
    status: in_progress
  - id: c9-gitignore
    content: "Review .gitignore for public release and add Child 1's Docker state paths; leave the BRAINDRAIN block alone"
    status: completed
  - id: c9-verify
    content: "Merge jitsi-meet-improved into docs/repo-packaging after Children 5, 6, 7 land; verify a fresh clone plus README install steps produces a working module on clean HumHub 1.17 with no old vendor identity or site-specific strings"
    status: pending
pr: "https://github.com/jhweb/jitsi-meet/pull/16"
pr_url: "https://github.com/jhweb/jitsi-meet/pull/16"
pr_number: 16
created_by_model: "auto"
created_at: "2026-07-27T11:48:21Z"
last_modified_by_model: "auto"
last_modified_at: "2026-10-27T10:10:38Z"
cursor_mode: "auto"
---
## Child 9 - Standalone repo packaging and docs

**Branch:** `docs/repo-packaging` ([PR #16](https://github.com/jhweb/jitsi-meet/pull/16)).
**Owns:** `README.md`, `LICENSE`, `CONTRIBUTING.md`, `SECURITY.md`, `docs/**`, `module.json` (version/minVersion), `composer.json` (PHP/version), `composer.lock`, `.github/workflows/**`, `.gitignore`.

---

## Codebase Audit Findings on `docs/repo-packaging` (2026-10-27)

Commit `a47de3a` on `docs/repo-packaging` already implemented 6 of the 12 deliverables (`README.md`, `LICENSE`, `SECURITY.md`, `CONTRIBUTING.md`, `docs/TODO.md` + `docs/README.md`, and `.gitignore`), which were previously still marked `pending` in this plan. However, comparing `docs/repo-packaging` against `jitsi-meet-improved` surfaced **four gaps and regressions** that must be corrected before merging PR #16:

1. **`composer.lock` is still missing (`c9-composer-lock`):**
   - `a47de3a` bumped `composer.json` to `"php": ">=8.1"`, but never generated or committed `composer.lock`.
   - **Fix:** Generate `composer.lock` inside the Child 1 HumHub 1.17 container (`docker compose -f docker/docker-compose.dev.yml exec humhub composer update --working-dir=/var/www/localhost/htdocs/protected/modules/jitsi-meet-cloud-8x8 --no-interaction`) so `firebase/php-jwt` (`^6.9`) is pinned, and commit `composer.lock`.

2. **`.github/workflows/rector-auto-pr.yaml.yml` was not renamed (`c9-ci`):**
   - `a47de3a` added `.github/workflows/php-syntax-check.yml`, but after `jitsi-meet-improved` merged `upstream/master` (`a50fd57`), `.github/workflows/rector-auto-pr.yaml.yml` (double extension) is present on `jitsi-meet-improved` and still needs `git mv .github/workflows/rector-auto-pr.yaml.yml .github/workflows/rector-auto-pr.yml`.

3. **`composer.json` and `module.json` overwrite Child 2 (`#10`) identity/attribution (`c9-composer-php`, `c9-module-json`):**
   - Because `docs/repo-packaging` branched before PR #10 (`chore/remove-site-branding`) merged into `jitsi-meet-improved`:
     - `composer.json` on `docs/repo-packaging` has `"name": "jhweb/jitsi-meet-cloud"` instead of `jitsi-meet-improved`'s `"name": "jhweb/jitsi-meet"`, and drops the `"AGPL-3.0; derived from humhub-contrib/jhweb jitsi-meet."` description suffix.
     - `module.json` on `docs/repo-packaging` drops the `"AGPL-3.0; derived from humhub-contrib/jhweb jitsi-meet."` description suffix from `jitsi-meet-improved`.
   - **Fix:** Merge `jitsi-meet-improved` into `docs/repo-packaging` and preserve `jhweb/jitsi-meet` + AGPL-3.0 attribution in both `composer.json` and `module.json` while setting `"version": "1.3.0"`, `"minVersion": "1.17"`, and `"php": ">=8.1"`.

4. **`docs/CHANGELOG.md` is missing upstream `1.2.2` and `1.2.3` entries from `jitsi-meet-improved` (`c9-changelog`):**
   - `jitsi-meet-improved` includes `1.2.2 (July 8, 2026)` (`Fix #49`, `Fix #54`) and `1.2.3 (Unreleased)` (`Fix #55: Remove meet.ffmuc.net server`).
   - **Fix:** When merging `jitsi-meet-improved` into `docs/repo-packaging`, keep `1.2.2 (July 8, 2026)` below `1.3.0` and fold `Fix #55: Remove meet.ffmuc.net server` into the `1.3.0` entry.

---

## Deliverables Checklist

1. **Root `README.md`** *(Done in `a47de3a`)* — purpose, HumHub 1.17 / PHP 8.1+ compatibility, committed screenshots (`resources/screen1.PNG`–`screen4.PNG`), Marketplace and manual install, 8x8 JaaS setup with mandatory webhook secret, self-hosted setup, permission matrix, Calendar dependency, Docker dev loop, QA pointer, CSP troubleshooting, upgrade notes, AGPL-3.0 license and attribution.
2. **`LICENSE` at root** *(Done in `a47de3a`)* — canonical GNU AGPLv3 text at repo root so GitHub detects the license.
3. **`SECURITY.md`** *(Done in `a47de3a`)* — private reporting contact and supported versions (`1.3.x`).
4. **`CONTRIBUTING.md`** *(Done in `a47de3a`)* — branch naming, Docker loop, PHP CS Fixer, Rector, and the Bootstrap 3 constraint.
5. **`module.json`** *(Needs merge resolution)* — `"version": "1.3.0"`, `"humhub": { "minVersion": "1.17" }`, preserving Child 2's AGPL-3.0 description.
6. **`composer.json`** *(Needs merge resolution)* — `"name": "jhweb/jitsi-meet"`, `"php": ">=8.1"`, preserving Child 2's AGPL-3.0 description.
7. **`composer.lock`** *(Pending)* — generate inside the HumHub 1.17 container and commit.
8. **`docs/` cleanup** *(Done in `a47de3a`)* — `docs/TODO.md` and `docs/README.md` cleaned up and cross-linked with `docs/INSTALLATION.md`.
9. **`docs/CHANGELOG.md`** *(Needs merge resolution)* — `1.3.0` entry with prominent `BREAKING` heading + `Fix #55` (`meet.ffmuc.net` removal) + preserved `1.2.2` section.
10. **CI workflows** *(Needs `rector-auto-pr.yml` rename)* — `.github/workflows/php-syntax-check.yml` added; rename `.github/workflows/rector-auto-pr.yaml.yml` to `.github/workflows/rector-auto-pr.yml`.
11. **`.gitignore`** *(Done in `a47de3a`)* — Child 1 Docker state paths (`docker/humhub-data/`, `docker/mysql-data/`, `docker/keys/*`) ignored while preserving `.gitkeep` and `README.md`.
12. **End-to-end verification (`c9-verify`)** — verify fresh install on clean HumHub 1.17 after Children 5, 6, and 7 merge into `jitsi-meet-improved`.

---

## Parent and branch topology

Parent: [Jitsi module hardening meta](jitsi_module_hardening_meta_f78868ce.plan.md). Branch off `jitsi-meet-improved` and merge back into it — never off `master`. Stage 3: finalize and merge only after Stages 1–2 (Children 5, 6, 7) have merged into `jitsi-meet-improved`.

## Rules for implementing agents (non-negotiable)

1. **Target HumHub 1.17 / Bootstrap 3.**
2. **Stay inside your owned file list.**
3. **No PHP on the host.** Lint with `docker compose -f docker/docker-compose.dev.yml exec humhub php -l <file>`.
4. **Push your branch immediately** after commits: `git push -u origin <branch>`.
5. **Forbidden without explicit human approval:** `git reset --hard`, `git clean -fd`, `git push --force`, `git rebase`, `docker system prune`, deleting `docker/`, `protected/`, `themes/`, `uploads/`.
6. **Never log secrets.**
