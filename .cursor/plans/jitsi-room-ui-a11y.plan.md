---
name: Room UI accessibility and tour targets
disposition: implemented
parent: jitsi_module_hardening_meta_f78868ce
branch: feat/room-ui-a11y
pr: https://github.com/jhweb/jitsi-meet/pull/17
pr_url: https://github.com/jhweb/jitsi-meet/pull/17
pr_number: 17
merged_at: "2026-08-04T16:20:54Z"
branches:
  - feat/room-ui-a11y
base_branch: jitsi-meet-improved
stage: 2
priority: P2
owner: "@ettienne"
dri: "@ettienne"
ide: cursor
last_modified_by_model: "auto"
last_modified_at: "2026-08-04T16:24:24Z"
todos:
  - id: c8-tour-ids
    content: "Add stable #jitsi-join-panel, #jitsi-active-grid, #jitsi-ended-grid and #jitsi-guide-button IDs to views/room/index.php so widgets/views/streamGuide.php tour steps resolve — add IDs rather than loosening selectors"
    status: completed
  - id: c8-a11y-focus
    content: "Make the hover-only delete controls (CSS ~L532-544) focus-visible; add aria-current='page' to the active filter link, a screen-reader label for the live indicator, and honour prefers-reduced-motion in both the CSS and Events.php"
    status: completed
  - id: c8-theme-vars
    content: "Replace hardcoded #f9f9f9 and inline opacity values with the HumHub theme variables already used in the file (--primary, --background-color-main)"
    status: completed
  - id: c8-js-cleanup
    content: "resources/humhub.jitsiMeet.js — remove debug console.log calls; replace innerHTML assignments and raw error.message interpolation with textContent or HumHub's status API"
    status: completed
  - id: c8-assets-forcecopy
    content: "assets/Assets.php — gate forceCopy => true on YII_DEBUG so production stops republishing assets every request"
    status: completed
  - id: c8-encode-emoji
    content: "Html::encode() the webhook-sourced reaction emoji output at views/room/modal_details.php L270"
    status: completed
  - id: c8-verify
    content: "Verify: tour visits every intended element; delete control is keyboard-reachable with a visible focus ring; no console.log remains; animations stop under reduced-motion; all modals still close on HumHub 1.17 with data-dismiss untouched"
    status: completed
created_by_model: "auto"
created_at: "2026-07-27T11:48:09Z"
last_modified_by_model: "auto"
last_modified_at: "2026-07-27T15:58:10Z"
cursor_mode: "auto"

---
## Child 8 - Room UI accessibility and tour targets

**Branch:** `feat/room-ui-a11y`. **Owns:** `widgets/views/streamGuide.php`, `views/room/index.php`, `resources/jitsi-meet.css`, `resources/humhub.jitsiMeet.js`, `assets/Assets.php`, `Events.php`, `views/room/modal_details.php`.

**Explicitly out of scope: any `data-dismiss` to `data-bs-dismiss` change.** Production runs Bootstrap 3 and that edit would break every modal close button. Leave `views/room/modal_details.php`, `views/room/schedule_modal.php` and `views/room/modal_event.php` dismiss attributes exactly as they are.

1. **Fix the broken tour.** [widgets/views/streamGuide.php](widgets/views/streamGuide.php) targets `#jitsi-join-panel`, `#jitsi-active-grid`, `#jitsi-ended-grid` and `#jitsi-guide-button`, but [views/room/index.php](views/room/index.php) only has `.jitsi-filter-bar` and `.jitsi-actions` — none of those IDs exist, so every tour step misses. Add stable IDs to the markup rather than loosening the selectors.
2. **Accessibility:** delete controls are hover-only in CSS around L532-544, so they are unreachable by keyboard — make them focus-visible. Add `aria-current="page"` to the active filter link, a screen-reader label for the live indicator, and honour `prefers-reduced-motion` for the pulse animations in both the CSS and `Events.php`.
3. Replace the hardcoded `#f9f9f9` and inline opacity values with the HumHub theme variables the rest of the file already uses (`--primary`, `--background-color-main`).
4. **JS cleanup** in [resources/humhub.jitsiMeet.js](resources/humhub.jitsiMeet.js): remove debug `console.log` calls; replace `innerHTML` assignments and raw `error.message` interpolation with `textContent` or HumHub's status API to close the XSS path.
5. `assets/Assets.php` sets `forceCopy => true`, which republishes assets on every request. Gate it on `YII_DEBUG` so production does not pay for it.
6. `Html::encode()` the reaction emoji output at [views/room/modal_details.php](views/room/modal_details.php) L270 — it comes from webhook data.

**Done when:** the tour visits every intended element; the delete control is reachable and operable by keyboard with a visible focus ring; no `console.log` remains; animations stop under reduced-motion; all modals still close on HumHub 1.17.

---

## Parent and branch topology

Parent: [Jitsi module hardening meta](jitsi_module_hardening_meta_f78868ce.plan.md). Branch off `jitsi-meet-improved` and merge back into it — never off `master`. Stage 2, may run in parallel with `jitsi-settings-gui`. `resources/jitsi-meet.css` was already touched by `jitsi-brand-neutralization` (L2 comment only) — keep that change.

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
