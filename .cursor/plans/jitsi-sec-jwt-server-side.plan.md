---
name: JWT server-side only and moderator cache key
disposition: implemented
parent: jitsi_module_hardening_meta_f78868ce
branch: fix/security-jwt-server-side
branches:
  - fix/security-jwt-server-side
base_branch: jitsi-meet-improved
pr: https://github.com/jhweb/jitsi-meet/pull/12
pr_url: https://github.com/jhweb/jitsi-meet/pull/12
pr_number: 12
merged_at: "2026-08-04T16:20:56Z"
security_review: "PASS_conditional (medium: console JWT log — hotfix expected)"
stage: 1
stage_order: 2
priority: P1
owner: "@ettienne"
dri: "@ettienne"
ide: cursor
last_modified_by_model: "auto"
last_modified_at: "2026-08-04T16:24:12Z"
todos:
  - id: c4-no-jwt-url
    content: "controllers/RoomController.php actionOpen L541-544 and L551-555 — stop putting the JWT in the room URL; pass only name and startSilent through views/room/open.php L20 modal.load()"
    status: completed
  - id: c4-modal-server-side
    content: "actionModal L608-620 — drop the jwt GET parameter entirely and mint via JaaSJwtService::createToken($user, $name, $isModerator) inside the action; never fall back to a supplied jwt"
    status: completed
  - id: c4-cache-key
    content: "Normalize the moderator cache key with strtolower in both ensureRoomCreator L1015-1016 and isModeratorForCurrentContext L1094 — must ship in the same commit as c4-modal-server-side"
    status: completed
  - id: c4-loginrequired
    content: "Return Yii::$app->user->loginRequired() at L527-530 and L551-554, and cover the self-hosted guest path at L548-558 (mode !== 'jaas' with enableJwt false)"
    status: completed
  - id: c4-log-scrub
    content: "widgets/RoomWidget.php L64-82 — log a redacted copy ('jwt' => present(len)/absent); remove the 'JWT generated and added to URL' message at RoomController L544"
    status: completed
  - id: c4-dead-getjwt
    content: "models/JoinRoomForm.php L53-75 — delete the dead getJwt() (hardcoded secret, never called, no return) and its now-unused JWT import"
    status: completed
  - id: c4-verify
    content: "Verify: no jwt= in address bar or Network tab; 'TestRoom' and 'testroom' resolve to one moderator; logged-out user is redirected to login; no JWT in module logs; legacy vpaas-magic-cookie redirects (L634, L929, L939, L958, L983) and config.php L20-22 URL rules still work"
    status: completed
  - id: c4-security-review
    content: "Mandatory security-review subagent on the branch diff before merging into jitsi-meet-improved; record findings in this plan"
    status: pending
created_by_model: "auto"
created_at: "2026-07-27T11:47:32Z"
last_modified_by_model: "auto"
last_modified_at: "2026-07-27T15:51:30Z"
cursor_mode: "auto"

---
## Child 4 - JWT server-side only and moderator cache key

**Branch:** `fix/security-jwt-server-side`. **Owns:** `controllers/RoomController.php`, `views/room/open.php`, `views/room/modal.php`, `widgets/RoomWidget.php`, `models/JoinRoomForm.php`.

The JWT currently travels through the query string, so it lands in browser history, proxy and server access logs, `Referer` headers, and `Yii::info` output.

1. **Stop emitting the JWT into the URL.** `actionOpen` L541-544 sets `$jitsiRoomUrl['jwt'] = $jwt`, and L551-555 does it again via `createJWT($name)`. [views/room/open.php](views/room/open.php) L20 then feeds that URL into `modal.load()`. Pass only `name` and `startSilent`.
2. **Regenerate server-side in `actionModal`.** L608-620 currently trusts `Yii::$app->request->get('jwt')`. Drop the GET parameter entirely and call `JaasJwtService::createToken($user, $name, $isModerator)` inside the action. Ignore any `jwt` query parameter that arrives — do not fall back to it.
3. **Fix the moderator cache key in the same commit.** `ensureRoomCreator` L1015-1016 keys on `strtolower($roomName)`; `isModeratorForCurrentContext` L1094 keys on the raw name. Since `fixRoomName()` applies `ucwords()`, mixed-case room names produce different keys and a second joiner can silently become moderator. Normalize with `strtolower` in both places. This must ship with step 2 — moving JWT minting server-side means the moderator decision now runs through `isModeratorForCurrentContext` on every modal load, so a mismatched key would flip moderator status.
4. **Return `loginRequired()`.** Two occurrences, L527-530 and L551-554, call it without returning, so execution continues for guests. `return Yii::$app->user->loginRequired();`. Also cover the self-hosted path at L548-558, where guests can currently join when `mode !== 'jaas'` and `enableJwt` is false.
5. **Scrub the JWT from logs.** [widgets/RoomWidget.php](widgets/RoomWidget.php) L64-82 json-encodes the whole data array including `jwt` into `Yii::info`. Log a redacted copy: `'jwt' => $this->jwt ? 'present(' . strlen($this->jwt) . ')' : 'absent'`. Also remove the "JWT generated and added to URL" message at `RoomController` L544.
6. **Delete the dead `getJwt()`.** [models/JoinRoomForm.php](models/JoinRoomForm.php) L53-75 — never called, hardcodes `my_jitsi_app_secret2`, and does not even return its result. Remove the method and its now-unused `JWT` import.

**Do not break** the legacy `vpaas-magic-cookie` URL redirects at `RoomController` L634, L929, L939, L958, L983, or the URL rules in [config.php](config.php) L20-22.

**Done when:** opening a room shows no `jwt=` anywhere in the browser Network tab or address bar; two users joining `TestRoom` and `testroom` resolve to one moderator; a logged-out user hitting the open URL is redirected to login; module logs contain no JWT.

---

## Parent and branch topology

Parent: [Jitsi module hardening meta](jitsi_module_hardening_meta_f78868ce.plan.md). Branch off `jitsi-meet-improved` and merge back into it — never off `master`. Stage 1, position 2 of 4: start only after `jitsi-sec-webhook-failclosed` has merged, and finish before `jitsi-sec-authorization` starts. `controllers/RoomController.php` is never edited by two children concurrently.

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
