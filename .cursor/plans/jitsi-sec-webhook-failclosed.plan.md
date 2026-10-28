---
name: Webhook fail-closed and required secret
disposition: implemented
parent: jitsi_module_hardening_meta_f78868ce
branch: fix/security-webhook-failclosed
branches:
  - fix/security-webhook-failclosed
base_branch: jitsi-meet-improved
stage: 1
stage_order: 1
priority: P1
owner: "@ettienne"
dri: "@ettienne"
ide: cursor
todos:
  - id: c3-failclosed
    content: "controllers/WebhookController.php L166-170 and L174-178 — both empty-secret 'return true' fail-opens become 'return false' with a Yii::error stating the webhook secret is unconfigured; add no mode check"
    status: completed
  - id: c3-log-oracle
    content: "L229 — stop logging expected/received signatures; log only 'Jitsi Webhook: signature mismatch' plus payload byte length"
    status: completed
  - id: c3-drift
    content: "Confirm the L199-214 drift check now always runs and reads jaasWebhookDriftTolerance with a sane default (never 0 or unbounded)"
    status: completed
  - id: c3-require-secret
    content: "models/SettingsForm.php rules() only — add jaasWebhookSecret to the L82-86 required-when-jaas set; rely on server-side validation (ActiveForm ships enableClientValidation = false)"
    status: completed
  - id: c3-admin-warning
    content: "views/config/index.php — single added block: dismissible warning when mode === 'jaas' and jaasWebhookSecret is empty, stating webhooks are rejected and how to fix it"
    status: completed
  - id: c3-verify-harness
    content: "Verify with Child 1 harness: valid signature 200; --tamper 401/403; --stale rejected; secret cleared means every POST rejected; no signature value in Administration > Information > Logging"
    status: completed
  - id: c3-security-review
    content: "Mandatory security-review subagent on the branch diff before merging into jitsi-meet-improved; record findings in this plan"
    status: completed
pr: "https://github.com/jhweb/jitsi-meet/pull/11"
pr_url: "https://github.com/jhweb/jitsi-meet/pull/11"
pr_number: 11
security_review: "PASS_conditional 2026-08-05 (medium still open: SettingsForm::validate() not called on save in ConfigController; note: HumHub debug $_SERVER dump logs received X-Jaas-Signature when YII_DEBUG=1)"
smoke_webhook: "2026-08-05 local docker HH1.17: valid=200; tamper=401; stale=401; wrong-secret=401; secret cleared -> unsigned=401 & valid-signed=401. Logging shows only 'signature mismatch (payload length: N)'; search 'Expected' = 0 results. Secret restored (len 18)."
created_by_model: "auto"
created_at: "2026-07-27T11:47:16Z"
last_modified_by_model: "auto"
last_modified_at: "2026-08-05T08:24:22Z"
cursor_mode: "auto"
---
## Child 3 - Webhook fail-closed and required secret

**Branch:** `fix/security-webhook-failclosed`. **Owns:** `controllers/WebhookController.php`, `models/SettingsForm.php` (`rules()` only), `views/config/index.php` (warning banner only — coordinate with Child 7 by keeping the change to a single added block).

This controller is publicly reachable: `getAccessRules()` allows `users => ['*']` for `index` and `$enableCsrfValidation = false`. Today, with no secret configured, anyone on the internet can POST forged events that rewrite stream metadata, recording URLs and participant counts.

1. **Remove both fail-opens.** In `verifySignature()`, [controllers/WebhookController.php](controllers/WebhookController.php) L166-170 and L174-178 currently `return true` when the secret is empty. Both become `return false` with a `Yii::error` explaining that the webhook secret is unconfigured. There is no `mode` check in this method and none should be added.
2. **Kill the signature oracle.** L229 logs `"Signature mismatch. Expected: $expectedSignature, Got: $signature"`. Log only `'Jitsi Webhook: signature mismatch'` plus the payload byte length. An attacker who can read logs must not learn the expected HMAC.
3. **Guarantee replay protection runs.** The drift check at L199-214 is currently unreachable when the secret is empty. After step 1 it always runs. Verify the drift window is read from `jaasWebhookDriftTolerance` with a sane default when unset (do not default to 0 or unbounded).
4. **Require the secret in JaaS mode.** [models/SettingsForm.php](models/SettingsForm.php) L82-86 requires `jaasAppId`, `jaasKid`, `jaasPrivateKeyPath` when `mode === 'jaas'`. Add `jaasWebhookSecret`. Server-side is what matters: HumHub's `ActiveForm` ships `enableClientValidation = false`, so the `whenClient` closure never fires. Add it for consistency but do not rely on it.
5. **Warn existing admins.** This is a breaking change for any install running JaaS mode with no secret set. Add a dismissible warning to the config view when `mode === 'jaas'` and `jaasWebhookSecret` is empty, stating that webhooks are being rejected and how to fix it.

**Verify with Child 1's harness:** valid signature returns 200; `--tamper` returns 401/403; `--stale` is rejected; with the secret cleared in settings, every POST is rejected. Confirm no signature value appears in Administration > Information > Logging.

**Staging caution:** before deploying this to staging, confirm `jaasWebhookSecret` is actually populated there, or live webhooks will start failing.

---

## Verification results (2026-08-05, local Docker HumHub 1.17.1)

**Upstream sync:** merged `upstream/master` (humhub-contrib, `51d0bf2`) into `jitsi-meet-improved`, keeping fork's HH 1.17 / JaaS APIs and porting only safe fixes (remove `meet.ffmuc.net`, `(string)` casts, `module.json` 1.2.3, CHANGELOG, vendor gitignore). Skipped HH 1.18-only APIs (`ControllerHelper::isActivePath`, `DeviceDetectorHelper`, BS5 `Button`, `humhub\components\assets\AssetBundle`). Then merged `jitsi-meet-improved` into `fix/security-webhook-failclosed` (clean, ort strategy).

**Harness matrix** (`tools/webhook-sign.php` run inside container against `http://localhost/jitsi-meet-cloud-8x8/webhook`):

| Case | Result |
|------|--------|
| Valid signature | HTTP 200 `{"status":"success"}` |
| `--tamper` | HTTP 401 |
| `--stale` (drift default 300s) | HTTP 401 |
| Wrong secret | HTTP 401 |
| Secret cleared → unsigned POST | HTTP 401 |
| Secret cleared → valid-signed POST | HTTP 401 |

**Logging (Administration > Information > Logging):** module emits only `Jitsi Webhook: signature mismatch (payload length: 127)`, `Signature verification failed.`, `webhook secret is unconfigured`, `Missing X-Jaas-Signature header`. Search for `Expected` returns **0 results** — the signature oracle is gone. Caveat: with `HUMHUB_DEBUG=1`, HumHub core's error handler dumps `$_SERVER`, which includes the **received** `HTTP_X_JAAS_SIGNATURE` (attacker's own value, not the expected HMAC). This is HumHub core debug behaviour, not the module, and does not appear when debug is off (staging/prod).

**Admin warning banner:** with `mode=jaas` + empty secret, the config view renders the amber dismissible alert "Webhook security: No webhook secret is configured. All incoming JaaS webhooks are being rejected until you set the Webhook Secret below and save." (Bootstrap 3 `alert alert-warning alert-dismissible`, verified via screenshot). Original DB state restored afterward (`jaasWebhookSecret` len 18, no `mode` row).

**Security review:** PASS_conditional (subagent, 2026-08-05). Webhook path is fail-closed; no unauthenticated forgery. One **medium** remains open from the prior conditional pass: `ConfigController` saves via `$form->load() && $form->save()` with no `$form->validate()`, so an admin can persist an empty `jaasWebhookSecret` in JaaS mode despite the model `required` rule (self-inflicted outage; runtime stays fail-closed, not an auth bypass). Recommended follow-up: add `$form->validate()` before `save()` and cap `jaasWebhookDriftTolerance`.

**Merge verdict:** conditionally safe to merge into `jitsi-meet-improved` provided staging/prod has `jaasWebhookSecret` provisioned before deploy (Child 10 checklist), with the `validate()`-on-save fix tracked as the remaining medium item.

---

## Parent and branch topology

Parent: [Jitsi module hardening meta](jitsi_module_hardening_meta_f78868ce.plan.md). Branch off `jitsi-meet-improved` and merge back into it — never off `master`. Stage 1, position 1 of 4: this must merge before `jitsi-sec-jwt-server-side` starts, because Stage 1 children share `controllers/RoomController.php` and `models/SettingsForm.php`.

## Rules for implementing agents (non-negotiable)

1. **Target HumHub 1.17 / Bootstrap 3.** Never change `data-dismiss` to `data-bs-dismiss`. Never change `panel`/`panel-body`/`panel-heading` to `card`. Never change `label label-success` to `badge`. Never introduce `d-none`, `ms-*`, `me-*`, `text-bg-*` or other BS4/5-only classes.
2. **Stay inside your owned file list.** If the fix seems to require another file, stop and report instead of editing it.
3. **Line numbers in this plan are advisory.** Verify the surrounding code matches before editing. If it does not match, stop and report — do not guess.
4. **No PHP on the host.** Lint with `docker compose -f docker/docker-compose.dev.yml exec humhub php -l <file>`.
5. **Push your branch immediately** after the first commit: `git push -u origin <branch>`.
6. **Forbidden without explicit human approval:** `git reset --hard`, `git clean -fd`, `git push --force`, `git rebase`, `docker system prune`, deleting `docker/`, `protected/`, `themes/`, `uploads/`.
7. **Never log secrets.** No JWTs, HMAC signatures, private keys or webhook secrets in `Yii::info`/`Yii::warning`, even at debug level. Log booleans and lengths instead.
8. **Every security child (3-6) must pass a `security-review` subagent** on its branch diff before merging into jitsi-meet-improved. Record findings in the child plan.
9. **Do not edit** `.braindrain/`, `.ruler/`, `AGENTS.md`, `.cursor/rules/`, or any other child's plan file.
