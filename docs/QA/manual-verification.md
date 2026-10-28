# Manual verification checklist — release gate

Release gate for Jitsi module hardening v1.3.0. Records local Docker acceptance criteria for Children 3–9 (all merged into `jitsi-meet-improved`) and staging outcomes for Child 10.

**Last updated:** 2026-10-28  
**Target integration branch:** `jitsi-meet-improved`  
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

> **Confirm `jaasWebhookSecret` is populated in staging HumHub admin settings BEFORE deploying `jitsi-meet-improved` (v1.3.0 candidate).**  
> Child 3 ([PR #11](https://github.com/jhweb/jitsi-meet/pull/11)) makes webhooks fail-closed: an empty secret causes every live 8x8 webhook to be rejected (HTTP 401).  
> Also confirm `YII_DEBUG` / `HUMHUB_DEBUG` is disabled in staging `index.php` / environment so Yii's core request logger does not dump `$_SERVER['HTTP_X_JAAS_SIGNATURE']` into Administration → Information → Logging.

- [ ] Staging `index.php` / environment has `YII_DEBUG` / `HUMHUB_DEBUG` disabled (`false` / `0`)
- [ ] Staging → Administration → Modules → Jitsi Meet → Configuration: `jaasWebhookSecret` is non-empty
- [ ] Secret value matches the secret configured in the 8x8 JaaS webhook endpoint
- [ ] Operator sign-off recorded below before any staging deploy of fail-closed code

| Field | Value |
|---|---|
| Verified by | _pending_ |
| Date | _pending_ |
| Secret populated (yes/no) | _pending_ |
| `YII_DEBUG` disabled (yes/no) | _pending_ |

---

## Merged PR index into `jitsi-meet-improved`

All Children 1–9 are merged into `jitsi-meet-improved` and verified in local HumHub 1.17.1 Docker:

| Order | PR | Branch | Child | Status |
|---|---|---|---|---|
| 1 | [#9](https://github.com/jhweb/jitsi-meet/pull/9) | `chore/dev-docker-env` | 1 — Docker dev env + webhook harness | ✅ MERGED |
| 2 | [#10](https://github.com/jhweb/jitsi-meet/pull/10) | `chore/remove-site-branding` | 2 — Brand neutralization | ✅ MERGED |
| 3 | [#11](https://github.com/jhweb/jitsi-meet/pull/11) | `fix/security-webhook-failclosed` | 3 — Webhook fail-closed | ✅ MERGED |
| 4 | [#12](https://github.com/jhweb/jitsi-meet/pull/12) | `fix/security-jwt-server-side` | 4 — JWT server-side | ✅ MERGED |
| 5 | [#13](https://github.com/jhweb/jitsi-meet/pull/13) | `fix/security-authorization` | 5 — Authorization / IDOR | ✅ MERGED |
| 6 | [#14](https://github.com/jhweb/jitsi-meet/pull/14) | `fix/security-ssrf-allowlist` | 6 — SSRF allowlist | ✅ MERGED |
| 7 | [#15](https://github.com/jhweb/jitsi-meet/pull/15) | `feat/settings-gui-activeform` | 7 — Admin settings GUI | ✅ MERGED |
| 8 | [#17](https://github.com/jhweb/jitsi-meet/pull/17) | `feat/room-ui-a11y` | 8 — Room UI a11y | ✅ MERGED |
| 9 | [#16](https://github.com/jhweb/jitsi-meet/pull/16) | `docs/repo-packaging` | 9 — Packaging & docs | ✅ MERGED |

**Legend:** ✅ PASS · ⬜ not yet run on staging · 🚫 staging only (human)

---

## Child 3 — Webhook fail-closed and required secret

**PR:** [#11](https://github.com/jhweb/jitsi-meet/pull/11) · **Branch:** `fix/security-webhook-failclosed` (MERGED)

### Local Docker (verified 2026-08-05 & 2026-10-28)

| Criterion | Status | Notes |
|---|---|---|
| Valid signature from `tools/webhook-sign.php` returns HTTP 200 | ✅ PASS | Harness against `http://localhost:9091` (`c9d4ada`) |
| `--tamper` returns HTTP 401 | ✅ PASS | Signature mismatch rejected |
| `--stale` rejected (outside drift window) | ✅ PASS | Timestamp drift rejected (default 300s) |
| With `jaasWebhookSecret` cleared, every POST rejected | ✅ PASS | Empty secret → HTTP 401 |
| No expected-signature oracle in Administration > Information > Logging | ✅ PASS | Oracle logging removed; note `HUMHUB_DEBUG=1` dumps `$_SERVER` so staging/prod must have debug off |
| Settings form requires `jaasWebhookSecret` when `mode === 'jaas'` | ✅ PASS | Server-side rule + `$form->validate()` enforced in `ConfigController` (PR #15) |
| Config view warning when JaaS mode has empty webhook secret | ✅ PASS | Dismissible admin banner |

### Staging

| Criterion | Status | Notes |
|---|---|---|
| Real recording / chat-log / transcript webhooks arrive and verify | ⬜ | 🚫 **requires human operator — do not auto-deploy** |
| Tamper and stale harness cases reject on staging | ⬜ | 🚫 **requires human operator — do not auto-deploy** |
| Logging contains no signature values after live traffic (`YII_DEBUG` off) | ⬜ | 🚫 **requires human operator — do not auto-deploy** |

---

## Child 4 — JWT server-side only and moderator cache key

**PR:** [#12](https://github.com/jhweb/jitsi-meet/pull/12) · **Branch:** `fix/security-jwt-server-side` (MERGED)

### Local Docker (verified 2026-08-04)

| Criterion | Status | Notes |
|---|---|---|
| Module logs contain no JWT material (redacted to `present(len)` / `absent`) | ✅ PASS | Console/log redact landed on #12 |
| Opening a room shows no `jwt=` in Network tab or address bar | ✅ PASS | Minted in `actionModal` only |
| `TestRoom` and `testroom` resolve to one moderator | ✅ PASS | Normalized via `strtolower()` |
| Logged-out user hitting open URL redirected to login | ✅ PASS | `loginRequired()` enforced |
| Legacy `vpaas-magic-cookie` redirects still work | ✅ PASS | Redirects without leaking JWT in URL |

### Staging

| Criterion | Status | Notes |
|---|---|---|
| No JWT in address bar, Network tab, or server access logs | ⬜ | 🚫 **requires human operator — do not auto-deploy** |
| Moderator cache key consistent under mixed-case room names | ⬜ | 🚫 **requires human operator — do not auto-deploy** |

---

## Child 5 — Permission wiring and container IDOR

**PR:** [#13](https://github.com/jhweb/jitsi-meet/pull/13) · **Branch:** `fix/security-authorization` (MERGED)

### Local Docker (verified 2026-10-28)

| Criterion | Status | Notes |
|---|---|---|
| Non-member cannot open `details`, `view-event`, `attend`, or `toggle-reminder` for another space's stream | ✅ PASS | `canAccessStreamDetails()` + space-scoped `ManageRecordings` (`906ab54`) |
| POST with foreign space GUID in `target_calendar` / `targetContainer` rejected | ✅ PASS | `resolveAllowedContainer()` throws `ForbiddenHttpException` (403) |
| User without `JoinVideoChat` cannot reach `open`/`modal` | ✅ PASS | Enforced via `getAccessRules()` |
| Admin unaffected by new permission wiring | ✅ PASS | Verified in HumHub 1.17.1 container |

### Staging

| Criterion | Status | Notes |
|---|---|---|
| Permission matrix: global admin, space admin, space member, non-member | ⬜ | 🚫 **requires human operator — do not auto-deploy** |
| Non-member cannot reach `details` for another space's stream | ⬜ | 🚫 **requires human operator — do not auto-deploy** |

---

## Child 6 — SSRF allowlist and mass assignment

**PR:** [#14](https://github.com/jhweb/jitsi-meet/pull/14) · **Branch:** `fix/security-ssrf-allowlist` (MERGED)

### Local Docker (verified 2026-10-28)

| Criterion | Status | Notes |
|---|---|---|
| Stream row pointing at `http://169.254.169.254/`, `https://169.254.169.254/`, or `https://[::1]/` produces rejection (no fetch) | ✅ PASS | `AllowlistedHttpFetcher` blocks non-HTTPS, IP literals, private ranges, and unlisted hosts |
| POST attempting to set `chat_log_url` / `recording_url` through schedule form does not persist | ✅ PASS | Dropped from `JitsiLiveStream::rules()` `safe` attributes |
| Legitimate 8x8 / `oraclecloud.com` HTTPS URLs pass allowlist validation | ✅ PASS | Verified in HumHub 1.17.1 container |

### Staging

| Criterion | Status | Notes |
|---|---|---|
| SSRF rejection verified with malicious URL in DB row | ⬜ | 🚫 **requires human operator — do not auto-deploy** |
| Legitimate 8x8 artifacts still render in `details` | ⬜ | 🚫 **requires human operator — do not auto-deploy** |

---

## Child 7 — Admin settings GUI on HumHub ActiveForm

**PR:** [#15](https://github.com/jhweb/jitsi-meet/pull/15) · **Branch:** `feat/settings-gui-activeform` (MERGED)

### Local Docker (verified 2026-10-28)

| Criterion | Status | Notes |
|---|---|---|
| Saving in both modes persists and validates (`$form->validate()`) | ✅ PASS | Verified in HumHub 1.17.1 container (`66edd79`, `9e5ee51`) |
| Switching mode shows/hides self-hosted, JaaS, and Debug groups with no console errors | ✅ PASS | Verified via Cursor browser CDP (`displayJaas`) |
| Custom-domain input hides when preset domain chosen (BS3 `.hide`, not `d-none`) | ✅ PASS | Verified via Cursor browser CDP (`toggleJitsiDomainTextInput`) |
| Copy works with strict CSP (`ClipboardJsAsset` + `data-action-click`) | ✅ PASS | Verified in `views/config/index.php` and `humhub.jitsiMeet.config.js` |
| Page renders correctly on HumHub 1.17 (Bootstrap 3 markup) | ✅ PASS | Screenshot verified on `http://localhost:9091/jitsi-meet-cloud-8x8/config/index` |

### Staging

| Criterion | Status | Notes |
|---|---|---|
| Settings save and mode toggle on staging HumHub 1.17 | ⬜ | 🚫 **requires human operator — do not auto-deploy** |

---

## Child 8 — Room UI accessibility and tour targets

**PR:** [#17](https://github.com/jhweb/jitsi-meet/pull/17) · **Branch:** `feat/room-ui-a11y` (MERGED)

### Local Docker (verified 2026-08-04)

| Criterion | Status | Notes |
|---|---|---|
| Tour visits every intended element | ✅ PASS | Verified on #17 |
| Delete control reachable and operable by keyboard with visible focus ring | ✅ PASS | Verified on #17 |
| No `console.log` remains in module JS | ✅ PASS | Verified on #17 |
| Animations stop under `prefers-reduced-motion` | ✅ PASS | Verified on #17 |
| All modals still close on HumHub 1.17 (`data-dismiss` unchanged) | ✅ PASS | Verified on #17 |

### Staging

| Criterion | Status | Notes |
|---|---|---|
| Tour and keyboard a11y verified on staging | ⬜ | 🚫 **requires human operator — do not auto-deploy** |

---

## Child 9 — Standalone repo packaging and docs

**PR:** [#16](https://github.com/jhweb/jitsi-meet/pull/16) · **Branch:** `docs/repo-packaging` (MERGED)

### Local / repo review (verified 2026-10-28)

| Criterion | Status | Notes |
|---|---|---|
| Root `README.md`, `LICENSE`, `SECURITY.md`, `CONTRIBUTING.md` present | ✅ PASS | Merged in `db066ea` |
| `module.json` version `1.3.0`, `humhub.minVersion` `1.17`, AGPL-3.0 attribution | ✅ PASS | Merged in `db066ea` |
| `composer.json` `jhweb/jitsi-meet`, PHP `>=8.1`; `composer.lock` committed | ✅ PASS | Generated in HumHub 1.17.1 container (`firebase/php-jwt` `v6.11.1`) |
| `docs/CHANGELOG.md` 1.3.0 entry with BREAKING webhook/permission notes + `Fix #55` + `1.2.2` | ✅ PASS | Merged in `db066ea` |
| `.github/workflows/rector-auto-pr.yml` renamed & `php-syntax-check.yml` present | ✅ PASS | Merged in `db066ea` |
| No site-specific strings (`oil press`, `lavivrus`) in module code/config | ✅ PASS | Verified across tracked module files |

---

## Child 10 — Staging verification and release gate

> 🚫 **All items below require a human operator. Do not auto-deploy to staging or production.**

### Snapshot and deploy

- [ ] Snapshot production into `staging.theoilpress.co.za` so it is a true copy — 🚫 **requires human operator — do not auto-deploy**
- [ ] Pre-deploy gate above completed (`jaasWebhookSecret` populated & `YII_DEBUG` off) — 🚫 **requires human operator — do not auto-deploy**
- [ ] Deploy `jitsi-meet-improved` (v1.3.0 candidate) to `staging.theoilpress.co.za` — 🚫 **requires human operator — do not auto-deploy**

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

- [ ] Merge `jitsi-meet-improved` into `feature/stream-space-association` — 🚫 **requires human operator — do not auto-deploy**
- [ ] Tag `v1.3.0` — 🚫 **requires human operator — do not auto-deploy**
- [ ] Call `mark_plan_merge_ready` on Child 10 plan — 🚫 **requires human operator — do not auto-deploy**

---

## Failure protocol

Any staging failure **re-opens the owning child plan** (Children 3–9). Do not patch code from this checklist — record the failure here, then fix on the child's branch and re-run verification.
