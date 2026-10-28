---
name: Branding neutralization and module identity
disposition: implemented
parent: jitsi_module_hardening_meta_f78868ce
branch: chore/remove-site-branding
branches:
  - chore/remove-site-branding
base_branch: jitsi-meet-improved
pr: https://github.com/jhweb/jitsi-meet/pull/10
pr_url: https://github.com/jhweb/jitsi-meet/pull/10
pr_number: 10
merged_at: "2026-08-04T16:20:50Z"
stage: 0
priority: P2
owner: "@ettienne"
dri: "@ettienne"
ide: cursor
last_modified_by_model: "auto"
last_modified_at: "2026-08-04T16:24:09Z"
todos:
  - id: c2-settings-defaults
    content: "models/SettingsForm.php L263 'The Oil Press Broadcast Room' -> 'Live Streams'; review the L264 'Broadcast' liveStreamRoomName default (generic 'Live' or empty-with-hint)"
    status: completed
  - id: c2-stream-card
    content: "views/room/_stream_card.php L62 — replace 'The Oil Press' creator fallback with Yii::$app->name or a translated 'Unknown'"
    status: completed
  - id: c2-css-comment
    content: "resources/jitsi-meet.css L2 — drop 'Lavivrus' from the header comment (comment only)"
    status: completed
  - id: c2-widget-title
    content: "widgets/LiveStreamWidget.php L38 — align the 'Live Streams' fallback with the SettingsForm default so they cannot drift"
    status: completed
  - id: c2-identity
    content: "module.json homepage and composer.json name -> own repo identity; keep an upstream AGPL-3.0 attribution line to jhweb/jitsi-meet in the description or README"
    status: completed
  - id: c2-dsstore
    content: "git rm --cached the four tracked .DS_Store files (root, messages/, views/, widgets/) — already gitignored"
    status: completed
  - id: c2-verify
    content: "Verify: case-insensitive search for 'oil press', 'oilpress', 'lavivrus' across tracked files returns nothing; live-stream widget still renders a sensible title with default settings; 8x8.vc, meet.jit.si, vpaas-magic-cookie patterns and messages/** left untouched"
    status: completed
pr: "https://github.com/jhweb/jitsi-meet/pull/10"
pr_url: "https://github.com/jhweb/jitsi-meet/pull/10"
pr_number: 10
created_by_model: "auto"
created_at: "2026-07-27T11:47:08Z"
last_modified_by_model: "auto"
last_modified_at: "2026-07-27T15:41:28Z"
cursor_mode: "auto"

---
## Child 2 - Branding neutralization and module identity

**Branch:** `chore/remove-site-branding`. **Owns:** `models/SettingsForm.php` (defaults only), `views/room/_stream_card.php`, `resources/jitsi-meet.css` (L2 comment only), `widgets/LiveStreamWidget.php`, `module.json`, `composer.json`, tracked `.DS_Store` files.

Confirmed inventory — these are the only site-specific strings in shippable code:

1. [models/SettingsForm.php](models/SettingsForm.php) L263 — `'The Oil Press Broadcast Room'` becomes `'Live Streams'`.
2. Same file L264 — review the `'Broadcast'` default `liveStreamRoomName`; a generic `'Live'` or empty-with-hint is better.
3. [views/room/_stream_card.php](views/room/_stream_card.php) L62 — `'The Oil Press'` creator fallback becomes the site name via `Yii::$app->name`, or a translated `'Unknown'`.
4. [resources/jitsi-meet.css](resources/jitsi-meet.css) L2 — drop `Lavivrus` from the comment.
5. [widgets/LiveStreamWidget.php](widgets/LiveStreamWidget.php) L38 — align the `'Live Streams'` fallback with the SettingsForm default so they cannot drift.
6. `module.json` `homepage` and `composer.json` `name` still point at `jhweb/jitsi-meet`. Set them to your own repo. Keep an upstream attribution line in the description or README — the module is AGPL-3.0 and derived from `jhweb/jitsi-meet`.
7. `git rm --cached` the four tracked `.DS_Store` files (`.DS_Store`, `messages/.DS_Store`, `views/.DS_Store`, `widgets/.DS_Store`). They are already in `.gitignore`.

Leave `8x8.vc`, `meet.jit.si`, the `vpaas-magic-cookie-[a-f0-9]{32}` patterns and `/var/www/keys/jaas_private.pem` alone — those are generic upstream defaults, not branding. `messages/**` contains no branding; do not touch it.

**Done when:** a case-insensitive search for `oil press`, `oilpress`, `lavivrus` across tracked files returns nothing, and the live-stream widget still renders a sensible title with default settings.

---

## Parent and branch topology

Parent: [Jitsi module hardening meta](jitsi_module_hardening_meta_f78868ce.plan.md). Branch off `jitsi-meet-improved` and merge back into it — never off `master`. Stage 0, may run in parallel with `jitsi-dev-env-docker`.

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
