---
name: Staging verification and release gate
disposition: active
parent: jitsi_module_hardening_meta_f78868ce
branch: chore/qa-staging-verification
branches:
  - chore/qa-staging-verification
base_branch: jitsi-meet-improved
stage: 4
priority: P1
owner: "@ettienne"
dri: "@ettienne"
ide: cursor
todos:
  - id: c10-scaffold-checklist
    content: "Scaffold the comprehensive release-gate verification checklist in docs/QA/manual-verification.md covering Children 3–10"
    status: completed
  - id: c10-reconcile-base
    content: "Merge jitsi-meet-improved into chore/qa-staging-verification once Children 5, 6, 7, and 9 merge; update PR status table (#9, #10, #11, #12, #17 already merged) and fold in Child 3's HUMHUB_DEBUG=0 logging caveat"
    status: pending
  - id: c10-snapshot
    content: "Snapshot production into staging.theoilpress.co.za so it is a true copy, and confirm HUMHUB_DEBUG is disabled on staging"
    status: pending
  - id: c10-deploy
    content: "Confirm jaasWebhookSecret is populated in staging settings FIRST, then deploy jitsi-meet-improved (v1.3.0 candidate) to staging.theoilpress.co.za"
    status: pending
  - id: c10-webhooks-real
    content: "Run a real 8x8 meeting on staging: confirm recording, chat-log, and transcript webhooks arrive and verify; confirm drift and tamper cases still reject"
    status: pending
  - id: c10-permission-matrix
    content: "Test the permission matrix with real accounts (global admin, space admin, space member, non-member); confirm a non-member cannot reach details for another space's stream"
    status: pending
  - id: c10-jwt-absent
    content: "Confirm no JWT appears in the browser address bar, Network tab, or server access logs"
    status: pending
  - id: c10-lifecycle
    content: "Run the module install/disable/re-enable lifecycle on staging per the workspace devops rules"
    status: pending
  - id: c10-logs
    content: "Read Administration > Information > Logging for warnings; confirm no secret, JWT, or signature value is present (and YII_DEBUG / HUMHUB_DEBUG is off)"
    status: pending
  - id: c10-record
    content: "Record every staging result and operator sign-off in docs/QA/manual-verification.md; any failure re-opens the owning child rather than being patched here"
    status: pending
  - id: c10-release
    content: "On a full pass: merge jitsi-meet-improved into feature/stream-space-association, tag v1.3.0, and call mark_plan_merge_ready on each child plan"
    status: pending
created_by_model: "auto"
created_at: "2026-07-27T11:48:28Z"
last_modified_by_model: "auto"
last_modified_at: "2026-10-27T10:10:39Z"
pr: "https://github.com/jhweb/jitsi-meet/pull/18"
pr_url: "https://github.com/jhweb/jitsi-meet/pull/18"
pr_number: 18
cursor_mode: "auto"
---
## Child 10 - Staging verification and release gate

**Branch:** `chore/qa-staging-verification` ([PR #18](https://github.com/jhweb/jitsi-meet/pull/18)).
**Owns:** `docs/QA/manual-verification.md` only. Writes verification results, not application code.

Docker covers the local inner loop (`http://localhost:9091`) and synthetic HMAC webhook harness (`tools/webhook-sign.php`), but cannot receive real 8x8 JaaS webhook traffic. Staging (`https://staging.theoilpress.co.za`) is the release gate that closes that gap using real 8x8 API keys and multi-role user accounts.

---

## Audit & Corrections on `chore/qa-staging-verification` (2026-10-27)

Reviewing `chore/qa-staging-verification` (`ef6ebe3`) against `jitsi-meet-improved` and `_master.plan.md` identified four corrections now captured in this plan:

1. **Separated checklist scaffolding (`c10-scaffold-checklist`, completed) from recording live staging results (`c10-record`, pending):**
   - Previously, `c10-record` ("Record every result in `docs/QA/manual-verification.md`") was marked `completed` when the empty checklist template was committed (`ef6ebe3`), even though none of the staging tests (`c10-snapshot` through `c10-logs`) had been executed yet.

2. **Durable integration branch is `jitsi-meet-improved` (not `integration/module-hardening-v1.3.0`):**
   - Per `_master.plan.md`, `jitsi-meet-improved` replaced `integration/module-hardening-v1.3.0` as the durable hardening base branch for all child PRs (#9–#18). Staging deploys `jitsi-meet-improved` once Children 5 (#13), 6 (#14), 7 (#15), and 9 (#16) have merged into it.

3. **Reconcile `docs/QA/manual-verification.md` with `jitsi-meet-improved` and Child 3's post-merge verification (`c9d4ada`):**
   - `jitsi-meet-improved` already carries the initial `docs/QA/manual-verification.md` from Child 1 (#9), and PRs #9, #10, #11, #12, and #17 are already **MERGED** into `jitsi-meet-improved` (whereas `chore/qa-staging-verification` was branched earlier and still lists them as unmerged).
   - Child 3's local Docker verification (`c9d4ada` on `fix/security-webhook-failclosed`) discovered two important operational facts that must be checked in Child 10:
     - **HumHub debug-mode header dump:** When `HUMHUB_DEBUG=1` / `YII_DEBUG=true`, Yii2's core request logger dumps `$_SERVER` (including `HTTP_X_JAAS_SIGNATURE`) into Administration → Information → Logging. Staging and production **must** have `YII_DEBUG` / `HUMHUB_DEBUG` disabled, and step `c10-logs` must explicitly verify that no `HTTP_X_JAAS_SIGNATURE` or `Authorization` dump appears.
     - **Settings validation on save:** `ConfigController::actionIndex()` previously called `$form->save()` without `$form->validate()`, allowing an empty `jaasWebhookSecret` to be saved in the UI even though `SettingsForm::rules()` required it. Child 7 ([PR #15](https://github.com/jhweb/jitsi-meet/pull/15)) adds `$form->validate()` to `ConfigController::actionIndex()` and must be merged before staging verification.

4. **Prerequisite gate before running staging verification:**
   - Do **not** execute the staging deploy (`c10-deploy`) until:
     - Child 5 ([PR #13](https://github.com/jhweb/jitsi-meet/pull/13) — authorization & container IDOR) is verified and merged into `jitsi-meet-improved`.
     - Child 6 ([PR #14](https://github.com/jhweb/jitsi-meet/pull/14) — SSRF allowlist & mass assignment) is verified and merged into `jitsi-meet-improved`.
     - Child 7 ([PR #15](https://github.com/jhweb/jitsi-meet/pull/15) — ActiveForm settings GUI + `$form->validate()` + duplicate `attributeLabels()` fix) is verified and merged into `jitsi-meet-improved`.
     - Child 9 ([PR #16](https://github.com/jhweb/jitsi-meet/pull/16) — repo packaging, `1.3.0` bump, `composer.lock`) is merged into `jitsi-meet-improved`.

---

## Staging Verification Sequence (Human Operator + Release Gate)

1. **Reconcile branch (`c10-reconcile-base`):** Merge `jitsi-meet-improved` into `chore/qa-staging-verification`, update `docs/QA/manual-verification.md` with the local Docker pass results for Children 3–9, and update the merged PR table.
2. **Snapshot production (`c10-snapshot`):** Snapshot `theoilpress.co.za` into `staging.theoilpress.co.za` so staging is a true copy, and confirm `YII_DEBUG` / `HUMHUB_DEBUG` is disabled on staging.
3. **Populate webhook secret & deploy (`c10-deploy`):**
   - **FIRST:** Confirm `jaasWebhookSecret` is populated in staging HumHub settings (Administration → Modules → Jitsi Meet → Configuration) and matches the 8x8 JaaS Console secret.
   - **SECOND:** Deploy `jitsi-meet-improved` (`v1.3.0` candidate) to `staging.theoilpress.co.za`.
4. **Real 8x8 meeting & webhooks (`c10-webhooks-real`):** Start a real 8x8 meeting on staging; confirm recording, chat-log, and transcript webhooks arrive and pass signature verification; run `tools/webhook-sign.php` `--tamper` and `--stale` against staging to confirm `401` rejection.
5. **Permission matrix (`c10-permission-matrix`):** Test with four accounts (Global Admin, Space Admin, Space Member, Non-Member / Guest):
   - Non-member cannot access `/jitsi-meet-cloud-8x8/room/details` for another space's stream.
   - Foreign space GUID in `target_calendar` is rejected.
   - User without `CreateVideoChat` / `JoinVideoChat` / `ManageRecordings` is blocked from the corresponding action.
6. **JWT server-side only (`c10-jwt-absent`):** Confirm no `jwt=` parameter appears in the browser address bar, Network request URLs, or cPanel/Apache/Nginx access logs when opening or joining a room.
7. **Module lifecycle (`c10-lifecycle`):** Disable module cleanly in staging admin, confirm other HumHub modules and spaces work with no log errors, re-enable module, and confirm settings and streams remain intact.
8. **Log audit (`c10-logs`):** Inspect Administration → Information → Logging; confirm zero secrets, JWTs, expected/received HMAC signatures, or `$_SERVER` debug dumps appear.
9. **Record & sign off (`c10-record`):** Record all outcomes, timestamps, and operator sign-off in `docs/QA/manual-verification.md` and merge PR #18 into `jitsi-meet-improved`. Any failure re-opens the owning child plan (Children 3–9) rather than patching code on this branch.
10. **Release (`c10-release`):** On a full pass, merge `jitsi-meet-improved` into `feature/stream-space-association`, tag `v1.3.0`, and call `mark_plan_merge_ready` on each child plan.

---

## Parent and branch topology

Parent: [Jitsi module hardening meta](jitsi_module_hardening_meta_f78868ce.plan.md). Branch off `jitsi-meet-improved` and merge back into it — never off `master`. Stage 4, the release gate: runs only after Stages 0–3 have merged into `jitsi-meet-improved`.

## Rules for implementing agents (non-negotiable)

1. **Target HumHub 1.17 / Bootstrap 3.**
2. **Stay inside your owned file list (`docs/QA/manual-verification.md` only).**
3. **No PHP on the host.**
4. **Push your branch immediately** after commits: `git push -u origin <branch>`.
5. **Forbidden without explicit human approval:** `git reset --hard`, `git clean -fd`, `git push --force`, `git rebase`, `docker system prune`, deleting `docker/`, `protected/`, `themes/`, `uploads/`.
6. **Never log secrets.**
