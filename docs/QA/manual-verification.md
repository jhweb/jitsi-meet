# Manual verification checklist

Skeleton for Children 3–8 acceptance criteria. Later children fill in results. Child 10 (staging) records real 8x8 traffic outcomes here.

Environment notes:
- Local Docker: `http://localhost:9091` via `docker/docker-compose.dev.yml`
- Staging: `https://staging.theoilpress.co.za`
- Harness: `tools/webhook-sign.php`

---

## Child 3 — Webhook fail-closed and required secret

Verified 2026-08-05 on local Docker (HumHub 1.17.1), branch `fix/security-webhook-failclosed` after merging upstream/master → jitsi-meet-improved → this branch.

- [x] Valid signature from `tools/webhook-sign.php` returns HTTP 200
- [x] `--tamper` returns 401/403 (401)
- [x] `--stale` is rejected (outside drift window) — 401, drift default 300s
- [x] With `jaasWebhookSecret` cleared in settings, every POST is rejected (unsigned + valid-signed both 401)
- [x] No expected-signature oracle in Administration > Information > Logging (search "Expected" = 0; only "signature mismatch (payload length: N)"). Caveat: HumHub core `$_SERVER` debug dump logs the *received* signature when `HUMHUB_DEBUG=1` (not present with debug off).
- [~] Settings form requires `jaasWebhookSecret` when `mode === 'jaas'` — model rule present, but `ConfigController` skips `$form->validate()` on save (medium; empty secret can still persist). Runtime stays fail-closed.
- [x] Config view shows warning when JaaS mode has empty webhook secret (amber dismissible banner, screenshot-verified)

## Child 4 — JWT server-side only and moderator cache key

- [ ] Opening a room shows no `jwt=` in the browser Network tab or address bar
- [ ] Two users joining `TestRoom` and `testroom` resolve to one moderator
- [ ] Logged-out user hitting the open URL is redirected to login
- [ ] Module logs contain no JWT material

## Child 5 — Permission wiring and container IDOR

- [ ] Non-member cannot open `details` for another space's stream
- [ ] POST with a foreign space GUID in `target_calendar` is rejected
- [ ] User without `JoinVideoChat` cannot reach `open`/`modal`
- [ ] Admin is unaffected by the new permission wiring

## Child 6 — SSRF allowlist and mass assignment

- [ ] Stream row pointing at `http://169.254.169.254/` produces a rejection (no fetch)
- [ ] POST attempting to set `chat_log_url` through the schedule form does not persist
- [ ] Legitimate 8x8 chat logs still render

## Child 7 — Admin settings GUI on HumHub ActiveForm

- [ ] Saving in both modes persists correctly
- [ ] Switching mode shows/hides the right groups with no console errors
- [ ] Custom-domain input hides when a preset domain is chosen
- [ ] Copy works with a strict CSP
- [ ] Page renders correctly on HumHub 1.17 (Bootstrap 3 markup)

## Child 8 — Room UI accessibility and tour targets

- [ ] Tour visits every intended element
- [ ] Delete control is reachable and operable by keyboard with a visible focus ring
- [ ] No `console.log` remains in module JS
- [ ] Animations stop under `prefers-reduced-motion`
- [ ] All modals still close on HumHub 1.17 (`data-dismiss` unchanged)

## Child 10 — Staging verification (real 8x8)

- [ ] Staging has `jaasWebhookSecret` populated before fail-closed deploy
- [ ] Real meeting recording / chat-log / transcript webhooks arrive
- [ ] Tamper and stale harness cases still reject on staging
- [ ] Permission matrix verified: global admin, space admin, space member, non-member
- [ ] No JWT in address bar, Network tab, or access logs
- [ ] Module install / disable / re-enable lifecycle passes
- [ ] Logging contains no secret, JWT, or signature values
