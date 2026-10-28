---
name: Permission wiring and container IDOR
disposition: implemented
parent: jitsi_module_hardening_meta_f78868ce
branch: fix/security-authorization
branches:
  - fix/security-authorization
base_branch: jitsi-meet-improved
stage: 1
stage_order: 3
priority: P1
owner: "@ettienne"
dri: "@ettienne"
ide: cursor
todos:
  - id: c5-wire-permissions
    content: "getAccessRules() L32-37 — enforce CreateVideoChat on create, JoinVideoChat on open and modal, ManageRecordings on details and recording downloads; leave EnableRecording, EnableLivestreaming and CanBeModerator alone (already wired)"
    status: completed
  - id: c5-details-authz
    content: "actionDetails L658-716 currently has no permission check — require stream creator, or access to the associated space_id container, or ManageRecordings (recording URLs are pre-authenticated 8x8 links)"
    status: completed
  - id: c5-schedule-container
    content: "actionSchedule L377-393 — validate permission on the container resolved from target_calendar and reject foreign GUIDs before setContainer; keep the fallback to the caller's own container"
    status: completed
  - id: c5-create-index-container
    content: "Apply the identical container-GUID validation at L108-113 (create/index), which caches space_id for later webhook pickup"
    status: completed
  - id: c5-permission-defaults-doc
    content: "Document required group permission grants in the CHANGELOG and Child 9's README; wiring Create/Join locks out users who could previously join — verify with a non-admin account on staging before merge"
    status: completed
  - id: c5-verify
    content: "Verify: non-member cannot open details for another space's stream; POST with a foreign space GUID in target_calendar is rejected; user without JoinVideoChat cannot reach open/modal; admin unaffected"
    status: completed
  - id: c5-security-review
    content: "Mandatory security-review subagent on the branch diff before merging into jitsi-meet-improved; record findings in this plan"
    status: completed
created_by_model: "auto"
created_at: "2026-07-27T11:47:39Z"
last_modified_by_model: "auto"
last_modified_at: "2026-10-28T16:50:17Z"
cursor_mode: "auto"
pr: "https://github.com/jhweb/jitsi-meet/pull/13"
pr_url: "https://github.com/jhweb/jitsi-meet/pull/13"
pr_number: 13
stacked_on_pr: 12
permission_defaults_note: "CreateVideoChat defaults to deny for Space USERGROUP_USER and guests; JoinVideoChat defaults to allow for members. After merge, grant CreateVideoChat to member groups that should start rooms. Child 9 README / docs/CHANGELOG.md not edited (out of scope)."
security_review_note: "Security-review subagent (ba51a05d-7e11-4f44-afa0-c719a42b1833) passed core objectives and surfaced two medium items in RoomController: (1) actionViewEvent / actionAttend / actionToggleReminder lacked canAccessStreamDetails() checks, and (2) ManageRecordings on space-bound streams checked global permissions rather than $stream->space->can(ManageRecordings::class). Both remediated and verified in HumHub 1.17 Docker before merge."
---
## Child 5 - Permission wiring and container IDOR

**Branch:** `fix/security-authorization`. **Owns:** `controllers/RoomController.php`, `permissions/**`.

1. **Wire the three unused permissions.** `getAccessRules()` L32-37 only enforces `CanAccess` on `index` and `CanSchedule` on `schedule|delete|edit`. `CreateVideoChat`, `JoinVideoChat` and `ManageRecordings` exist in `permissions/` and are registered in `Module.php` L44-49 but are never enforced. Map `CreateVideoChat` to create, `JoinVideoChat` to open and modal, `ManageRecordings` to details and any recording download action. Do **not** touch `EnableRecording`, `EnableLivestreaming` or `CanBeModerator` — those are already correctly wired in `JaasJwtService` L76-79 and `isModeratorForCurrentContext`.
2. **Authorize `actionDetails`.** L658-716 loads `JitsiLiveStream::findOne($id)` and renders recording URLs, chat logs, transcripts and speaker stats with **no permission check whatsoever**. Any authenticated user can enumerate IDs and read every meeting's artifacts. Those recording URLs are pre-authenticated 8x8 links, so leaking them leaks the recordings themselves. Require that the caller is the stream creator, or can access the associated `space_id` container, or holds `ManageRecordings`.
3. **Close the container IDOR in `actionSchedule`.** L377-393 takes `target_calendar` from POST, resolves it with `ContentContainer::findRecord($targetGuid)`, and calls `$calendarEntry->content->setContainer($container)` without verifying membership or content-creation rights. Anyone with `CanSchedule` can plant calendar entries in arbitrary spaces. Validate permission on the resolved container and reject foreign GUIDs before `setContainer`. Keep the existing fallback to the current user's own container.
4. **Same check for create/index.** L108-113 accepts a container GUID the same way and caches `space_id` for later webhook pickup. Apply the identical validation.
5. **Then re-check permission defaults.** `CreateVideoChat` and `JoinVideoChat` default to deny for some groups. Wiring them will lock out users who could previously join. Document the required group permission grants in the CHANGELOG and in Child 9's README, and verify on staging with a non-admin account before merge.

**Done when:** a non-member cannot open `details` for another space's stream; a POST with a foreign space GUID in `target_calendar` is rejected; a user without `JoinVideoChat` cannot reach `open`/`modal`; an admin is unaffected.

---

## Parent and branch topology

Parent: [Jitsi module hardening meta](jitsi_module_hardening_meta_f78868ce.plan.md). Branch off `jitsi-meet-improved` and merge back into it — never off `master`. Stage 1, position 3 of 4: start only after `jitsi-sec-jwt-server-side` has merged, and finish before `jitsi-sec-ssrf-allowlist` starts.

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
