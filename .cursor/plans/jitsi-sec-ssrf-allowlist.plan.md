---
name: SSRF allowlist and mass assignment
disposition: implemented
parent: jitsi_module_hardening_meta_f78868ce
branch: fix/security-ssrf-allowlist
pr: https://github.com/jhweb/jitsi-meet/pull/14
pr_number: 14
pr_url: https://github.com/jhweb/jitsi-meet/pull/14
branches:
  - fix/security-ssrf-allowlist
base_branch: jitsi-meet-improved
stage: 1
stage_order: 4
priority: P1
owner: "@ettienne"
dri: "@ettienne"
ide: cursor
todos:
  - id: c6-mass-assignment
    content: "models/JitsiLiveStream.php L68 — drop recording_url, transcription_url, chat_log_url, file_urls, highlights_url, screen_sharing_url, rtcstats_url, ytstream_url from safe attributes and assign them explicitly on the webhook/admin paths (actionSchedule L315 load(post()) makes them user-writable today)"
    status: completed
  - id: c6-allowlist-helper
    content: "New fetch helper under components/ — require https, match host against an 8x8/JaaS allowlist, reject IP-literal hosts and private/link-local ranges (10/8, 172.16/12, 192.168/16, 127/8, 169.254/16, ::1, fc00::/7), cap response size, short timeout, redirects disabled; replace the @file_get_contents calls in actionDetails L668-709"
    status: completed
  - id: c6-fail-notice
    content: "On rejection render an admin-facing notice and Yii::warning only the rejected host — never the full URL with query parameters (may carry 8x8 pre-auth tokens)"
    status: completed
  - id: c6-allowlist-setting
    content: "Expose the allowlist as a module setting with a sensible 8x8 default so self-hosted deployments can extend it"
    status: completed
  - id: c6-verify
    content: "Verify: a DB row hand-edited to http://169.254.169.254/ is rejected rather than fetched; a schedule-form POST setting chat_log_url does not persist; legitimate 8x8 chat logs still render"
    status: completed
  - id: c6-security-review
    content: "Mandatory security-review subagent on the branch diff before merging into jitsi-meet-improved; record findings in this plan"
    status: completed
created_by_model: "auto"
created_at: "2026-07-27T11:47:47Z"
last_modified_by_model: "auto"
last_modified_at: "2026-10-28T16:54:03Z"
cursor_mode: "auto"
security_review_note: "Security-review subagent (3dac61a6-9cdd-498d-9262-864351789564) validated AllowlistedHttpFetcher, RoomController fetch rejection handling, and JitsiLiveStream mass-assignment removal with zero medium+ findings on Child 6 owned files. Runtime verification in HumHub 1.17 Docker passed."
---
## Child 6 - SSRF allowlist and mass assignment

**Branch:** `fix/security-ssrf-allowlist`. **Owns:** `controllers/RoomController.php` (fetch logic only), `models/JitsiLiveStream.php`, plus a new fetch helper under `components/`.

The old plan treated this as webhook-only. It is not: the URLs are user-writable.

1. **Remove mass assignment.** [models/JitsiLiveStream.php](models/JitsiLiveStream.php) L68 marks `recording_url`, `transcription_url`, `chat_log_url`, `file_urls`, `highlights_url`, `screen_sharing_url`, `rtcstats_url`, `ytstream_url` and friends as `safe`. Combined with `$model->load(Yii::$app->request->post())` in `actionSchedule` L315, a user can POST `JitsiLiveStream[chat_log_url]=http://169.254.169.254/latest/meta-data/` directly. These fields are written by the webhook and admin paths only — drop them from `safe` and assign them explicitly where they are legitimately set.
2. **Allowlist outbound fetches.** `actionDetails` L668-709 calls `@file_get_contents()` on `chat_log_url` and `screen_sharing_url`. Add a helper that: parses the URL, requires `https`, matches the host against an 8x8/JaaS allowlist, rejects IP-literal hosts, rejects private and link-local ranges (`10/8`, `172.16/12`, `192.168/16`, `127/8`, `169.254/16`, `::1`, `fc00::/7`), caps response size, and keeps a short timeout. Prefer a real HTTP client with redirects disabled over `file_get_contents` — an allowed host that 302s to `169.254.169.254` defeats a host-only check.
3. **Fail visibly but safely.** On rejection, render an admin-facing notice and `Yii::warning` the rejected host — never the full URL with query parameters, which may carry 8x8 pre-auth tokens.
4. Make the allowlist a module setting with a sensible 8x8 default so self-hosted deployments can extend it.

**Done when:** a stream row hand-edited in the DB to point at `http://169.254.169.254/` produces a rejection rather than a fetch; a POST attempting to set `chat_log_url` through the schedule form does not persist; legitimate 8x8 chat logs still render.

---

## Parent and branch topology

Parent: [Jitsi module hardening meta](jitsi_module_hardening_meta_f78868ce.plan.md). Branch off `jitsi-meet-improved` and merge back into it — never off `master`. Stage 1, position 4 of 4: start only after `jitsi-sec-authorization` has merged. This is the last child to touch `controllers/RoomController.php`.

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
