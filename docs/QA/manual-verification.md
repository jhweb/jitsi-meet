# Manual verification checklist — release gate

Release gate for Jitsi module hardening v1.3.0. Records acceptance criteria for Children 3–9 and staging outcomes for Child 10.

**Last updated:** 2026-07-27  
**Target integration branch:** `integration/module-hardening-v1.3.0`  
**Merge target:** `jitsi-meet-improved` → `feature/stream-space-association` → tag `v1.3.0`

---

## Environments

| Environment | URL | Purpose |
|---|---|---|
| Local Docker | `http://localhost:9091` via `docker/docker-compose.dev.yml` | Inner loop; webhook harness (`tools/webhook-sign.php`) |
| Staging | `https://staging.theoilpress.co.za` | Real 8x8 traffic; permission matrix with live accounts |
| Production | `https://theoilpress.co.za` | **Do not deploy from this checklist** |

---

## Pre-deploy gate (mandatory — human operator)

> **Confirm `jaasWebhookSecret` is populated in staging HumHub admin settings BEFORE deploying [PR #11](https://github.com/jhweb/jitsi-meet/pull/11).**  
> Child 3 makes webhooks fail-closed: an empty secret causes every live 8x8 webhook to be rejected (HTTP 401).

- [ ] Staging → Administration → Modules → Jitsi Meet → Configuration: `jaasWebhookSecret` is non-empty
- [ ] Secret value matches the secret configured in the 8x8 JaaS webhook endpoint
- [ ] Operator sign-off recorded below before any staging deploy of fail-closed code

| Field | Value |
|---|---|
| Verified by | _pending_ |
| Date | _pending_ |
| Secret populated (yes/no) | _pending_ |

---

## Merge order into `jitsi-meet-improved`

Merge open PRs in this order. Do not skip ahead — later children depend on earlier security fixes.

| Order | PR | Branch | Child |
|---|---|---|---|
| 1 | [#9](https://github.com/jhweb/jitsi-meet/pull/9) | `chore/dev-docker-env` | 1 — Docker dev env |
| 2 | [#10](https://github.com/jhweb/jitsi-meet/pull/10) | `chore/remove-site-branding` | 2 — Brand neutralization |
| 3 | [#11](https://github.com/jhweb/jitsi-meet/pull/11) | `fix/security-webhook-failclosed` | 3 — Webhook fail-closed |
| 4 | [#12](https://github.com/jhweb/jitsi-meet/pull/12) | `fix/security-jwt-server-side` | 4 — JWT server-side |
| 5 | [#13](https://github.com/jhweb/jitsi-meet/pull/13) | `fix/security-authorization` | 5 — Authorization / IDOR |
| 6 | [#14](https://github.com/jhweb/jitsi-meet/pull/14) | `fix/security-ssrf-allowlist` | 6 — SSRF allowlist |
| 7 | [#15](https://github.com/jhweb/jitsi-meet/pull/15) | `feat/settings-gui-activeform` | 7 — Admin settings GUI |
| 8 | [#16](https://github.com/jhweb/jitsi-meet/pull/16) / [#17](https://github.com/jhweb/jitsi-meet/pull/17) | `docs/repo-packaging` / `feat/room-ui-a11y` | 9 / 8 — Packaging & a11y |

After all PRs merge into `jitsi-meet-improved`, cut `integration/module-hardening-v1.3.0` and deploy to staging for Child 10 verification.

**Legend:** ✅ PASS · ⏳ pending merge + re-test · ⬜ not yet run · 🚫 staging only (human)

---

## Child 3 — Webhook fail-closed and required secret

**PR:** [#11](https://github.com/jhweb/jitsi-meet/pull/11) · **Branch:** `fix/security-webhook-failclosed`

### Local Docker (verified 2026-07-27)

| Criterion | Status | Notes |
|---|---|---|
| Valid signature from `tools/webhook-sign.php` returns HTTP 200 | ✅ PASS | Harness against `http://localhost:9091` |
| `--tamper` returns HTTP 401 | ✅ PASS | Signature mismatch rejected |
| `--stale` rejected (outside drift window) | ✅ PASS | Timestamp drift rejected |
| With `jaasWebhookSecret` cleared, every POST rejected | ✅ PASS | Empty secret → HTTP 401 |
| No signature value in Administration > Information > Logging | ✅ PASS | Oracle logging removed |
| Settings form requires `jaasWebhookSecret` when `mode === 'jaas'` | ✅ PASS | Server-side rule enforced |
| Config view warning when JaaS mode has empty webhook secret | ✅ PASS | Dismissible admin banner |

### Staging

| Criterion | Status | Notes |
|---|---|---|
| Real recording / chat-log / transcript webhooks arrive and verify | ⬜ | 🚫 **requires human operator — do not auto-deploy** |
| Tamper and stale harness cases reject on staging | ⬜ | 🚫 **requires human operator — do not auto-deploy** |
| Logging contains no signature values after live traffic | ⬜ | 🚫 **requires human operator — do not auto-deploy** |

---

## Child 4 — JWT server-side only and moderator cache key

**PR:** [#12](https://github.com/jhweb/jitsi-meet/pull/12) · **Branch:** `fix/security-jwt-server-side`

### Local Docker

| Criterion | Status | Notes |
|---|---|---|
| Module logs contain no JWT material (redacted to `present(len)` / `absent`) | ✅ PASS | Console/log redact landed on #12 (2026-07-27) |
| Opening a room shows no `jwt=` in Network tab or address bar | ⏳ | pending merge + re-test |
| `TestRoom` and `testroom` resolve to one moderator | ⏳ | pending merge + re-test |
| Logged-out user hitting open URL redirected to login | ⏳ | pending merge + re-test |
| Legacy `vpaas-magic-cookie` redirects still work | ⏳ | pending merge + re-test |

### Staging

| Criterion | Status | Notes |
|---|---|---|
| No JWT in address bar, Network tab, or server access logs | ⬜ | 🚫 **requires human operator — do not auto-deploy** |
| Moderator cache key consistent under mixed-case room names | ⬜ | 🚫 **requires human operator — do not auto-deploy** |

---

## Child 5 — Permission wiring and container IDOR

**PR:** [#13](https://github.com/jhweb/jitsi-meet/pull/13) · **Branch:** `fix/security-authorization`

### Local Docker

| Criterion | Status | Notes |
|---|---|---|
| Non-member cannot open `details` for another space's stream | ⏳ | pending merge + re-test |
| POST with foreign space GUID in `target_calendar` rejected | ⏳ | pending merge + re-test |
| User without `JoinVideoChat` cannot reach `open`/`modal` | ⏳ | pending merge + re-test |
| Admin unaffected by new permission wiring | ⏳ | pending merge + re-test |

### Staging

| Criterion | Status | Notes |
|---|---|---|
| Permission matrix: global admin, space admin, space member, non-member | ⬜ | 🚫 **requires human operator — do not auto-deploy** |
| Non-member cannot reach `details` for another space's stream | ⬜ | 🚫 **requires human operator — do not auto-deploy** |

---

## Child 6 — SSRF allowlist and mass assignment

**PR:** [#14](https://github.com/jhweb/jitsi-meet/pull/14) · **Branch:** `fix/security-ssrf-allowlist`

### Local Docker

| Criterion | Status | Notes |
|---|---|---|
| Stream row pointing at `http://169.254.169.254/` produces rejection (no fetch) | ⏳ | pending merge + re-test |
| POST attempting to set `chat_log_url` through schedule form does not persist | ⏳ | pending merge + re-test |
| Legitimate 8x8 chat logs still render | ⏳ | pending merge + re-test |

### Staging

| Criterion | Status | Notes |
|---|---|---|
| SSRF rejection verified with malicious URL in DB row | ⬜ | 🚫 **requires human operator — do not auto-deploy** |
| Legitimate 8x8 artifacts still render in `details` | ⬜ | 🚫 **requires human operator — do not auto-deploy** |

---

## Child 7 — Admin settings GUI on HumHub ActiveForm

**PR:** [#15](https://github.com/jhweb/jitsi-meet/pull/15) · **Branch:** `feat/settings-gui-activeform`

### Local Docker

| Criterion | Status | Notes |
|---|---|---|
| Saving in both modes persists correctly | ⏳ | pending merge + re-test |
| Switching mode shows/hides right groups with no console errors | ⏳ | pending merge + re-test |
| Custom-domain input hides when preset domain chosen (BS3, not `d-none`) | ⏳ | pending merge + re-test |
| Copy works with strict CSP | ⏳ | pending merge + re-test |
| Page renders correctly on HumHub 1.17 (Bootstrap 3 markup) | ⏳ | pending merge + re-test |

### Staging

| Criterion | Status | Notes |
|---|---|---|
| Settings save and mode toggle on staging HumHub 1.17 | ⬜ | 🚫 **requires human operator — do not auto-deploy** |

---

## Child 8 — Room UI accessibility and tour targets

**PR:** [#17](https://github.com/jhweb/jitsi-meet/pull/17) · **Branch:** `feat/room-ui-a11y`

### Local Docker

| Criterion | Status | Notes |
|---|---|---|
| Tour visits every intended element | ⏳ | pending merge + re-test |
| Delete control reachable and operable by keyboard with visible focus ring | ⏳ | pending merge + re-test |
| No `console.log` remains in module JS | ⏳ | pending merge + re-test |
| Animations stop under `prefers-reduced-motion` | ⏳ | pending merge + re-test |
| All modals still close on HumHub 1.17 (`data-dismiss` unchanged) | ⏳ | pending merge + re-test |

### Staging

| Criterion | Status | Notes |
|---|---|---|
| Tour and keyboard a11y verified on staging | ⬜ | 🚫 **requires human operator — do not auto-deploy** |

---

## Child 9 — Standalone repo packaging and docs

**PR:** [#16](https://github.com/jhweb/jitsi-meet/pull/16) · **Branch:** `docs/repo-packaging`

### Local / repo review

| Criterion | Status | Notes |
|---|---|---|
| Root `README.md`, `LICENSE`, `SECURITY.md`, `CONTRIBUTING.md` present | ⏳ | pending merge + re-test |
| `module.json` version `1.3.0`, `humhub.minVersion` `1.17` | ⏳ | pending merge + re-test |
| `composer.json` PHP `>=8.1`; `composer.lock` committed | ⏳ | pending merge + re-test |
| `docs/CHANGELOG.md` 1.3.0 entry with BREAKING webhook/permission notes | ⏳ | pending merge + re-test |
| Fresh clone + README install steps produce working module on clean 1.17 | ⏳ | pending merge + re-test |
| No site-specific strings (`oil press`, `lavivrus`) in tracked files | ⏳ | pending merge + re-test (Child 2 [#10](https://github.com/jhweb/jitsi-meet/pull/10)) |

---

## Child 10 — Staging verification and release gate

> 🚫 **All items below require a human operator. Do not auto-deploy to staging or production.**

### Snapshot and deploy

- [ ] Snapshot production into `staging.theoilpress.co.za` so it is a true copy — 🚫 **requires human operator — do not auto-deploy**
- [ ] Pre-deploy gate above completed (`jaasWebhookSecret` populated) — 🚫 **requires human operator — do not auto-deploy**
- [ ] Deploy `integration/module-hardening-v1.3.0` to staging — 🚫 **requires human operator — do not auto-deploy**

### Real 8x8 meeting verification

- [ ] Start a real meeting; recording webhook arrives and verifies — 🚫 **requires human operator — do not auto-deploy**
- [ ] Chat-log webhook arrives and verifies — 🚫 **requires human operator — do not auto-deploy**
- [ ] Transcript webhook arrives and verifies — 🚫 **requires human operator — do not auto-deploy**
- [ ] Drift and tamper harness cases still reject on staging — 🚫 **requires human operator — do not auto-deploy**

### Cross-cutting staging checks

- [ ] Permission matrix with real accounts (global admin, space admin, space member, non-member) — 🚫 **requires human operator — do not auto-deploy**
- [ ] No JWT in address bar, Network tab, or access logs — 🚫 **requires human operator — do not auto-deploy**
- [ ] Module install / disable / re-enable lifecycle passes — 🚫 **requires human operator — do not auto-deploy**
- [ ] Administration > Information > Logging: no secret, JWT, or signature values — 🚫 **requires human operator — do not auto-deploy**

### Release (on full pass only)

- [ ] Merge `integration/module-hardening-v1.3.0` into `feature/stream-space-association` — 🚫 **requires human operator — do not auto-deploy**
- [ ] Tag `v1.3.0` — 🚫 **requires human operator — do not auto-deploy**
- [ ] Call `mark_plan_merge_ready` on each child plan — 🚫 **requires human operator — do not auto-deploy**

---

## Failure protocol

Any staging failure **re-opens the owning child plan** (Children 3–9). Do not patch code from this checklist — record the failure here, then fix on the child's branch and re-run verification.

---

## Open PR index (Children 1–9)

| PR | Title area | Branch |
|---|---|---|
| [#9](https://github.com/jhweb/jitsi-meet/pull/9) | Docker dev env + webhook harness | `chore/dev-docker-env` |
| [#10](https://github.com/jhweb/jitsi-meet/pull/10) | Brand neutralization | `chore/remove-site-branding` |
| [#11](https://github.com/jhweb/jitsi-meet/pull/11) | Webhook fail-closed | `fix/security-webhook-failclosed` |
| [#12](https://github.com/jhweb/jitsi-meet/pull/12) | JWT server-side | `fix/security-jwt-server-side` |
| [#13](https://github.com/jhweb/jitsi-meet/pull/13) | Authorization / IDOR | `fix/security-authorization` |
| [#14](https://github.com/jhweb/jitsi-meet/pull/14) | SSRF allowlist | `fix/security-ssrf-allowlist` |
| [#15](https://github.com/jhweb/jitsi-meet/pull/15) | Admin settings GUI | `feat/settings-gui-activeform` |
| [#16](https://github.com/jhweb/jitsi-meet/pull/16) | Repo packaging + docs | `docs/repo-packaging` |
| [#17](https://github.com/jhweb/jitsi-meet/pull/17) | Room UI a11y | `feat/room-ui-a11y` |
