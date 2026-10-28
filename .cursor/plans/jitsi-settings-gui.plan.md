---
name: Admin settings GUI on HumHub ActiveForm
disposition: active
parent: jitsi_module_hardening_meta_f78868ce
branch: feat/settings-gui-activeform
pr_url: https://github.com/jhweb/jitsi-meet/pull/15
pr_number: 15
branches:
  - feat/settings-gui-activeform
base_branch: jitsi-meet-improved
stage: 2
priority: P2
owner: "@ettienne"
dri: "@ettienne"
ide: cursor
todos:
  - id: c7-activeform
    content: "Swap yii\\bootstrap\\ActiveForm for humhub\\modules\\ui\\form\\widgets\\ActiveForm with ['id' => 'configure-form', 'acknowledge' => true] and enforce $form->validate() before $form->save() in ConfigController"
    status: completed
  - id: c7-collapsible-groups
    content: "Replace the flat 60-field form with $form->beginCollapsibleFields() / $form->endCollapsibleFields() groups: Connection, Features, Permission Defaults, Debug"
    status: completed
  - id: c7-js-module
    content: "Delete the 68-line inline registerJs block and reimplement show/hide as resources/humhub.jitsiMeet.config.js registered through assets/ConfigAssets.php"
    status: completed
  - id: c7-dual-domain
    content: "Fix the dual jitsiDomain field (duplicate DOM ids and disable-on-submit hack) using a preset dropdown + __custom__ text input, and ensure validation errors and non-JS/change sync work"
    status: in_progress
  - id: c7-dnone-fix
    content: "Fix the d-none bug — use Bootstrap 3 .hide class and .hide()/.show() so the custom-domain input actually hides on HumHub 1.17"
    status: completed
  - id: c7-clipboard
    content: "Replace the inline onclick with ClipboardJsAsset and data-action-click='jitsiCopyWebhookUrl' with aria-label and status feedback so it survives strict CSP"
    status: completed
  - id: c7-bs3-preserve
    content: "Keep Bootstrap 3 markup — panel panel-default, panel-heading, panel-body, label label-success, help-block, control-label, input-group-btn; introduce no BS4/BS5-only classes"
    status: completed
  - id: c7-i18n
    content: "i18n mode labels, the Copy button, and the whole Debug panel"
    status: completed
  - id: c7-filesize-guard
    content: "Guard filesize($keyPath) against the file vanishing after file_exists; surface validateJaasPrivateKeyPath readability failures as visible save-time errors"
    status: completed
  - id: c7-fix-branch-regressions
    content: "CRITICAL FIXES on feat/settings-gui-activeform: (1) remove duplicate attributeLabels() declaration in models/SettingsForm.php (fatal PHP compile error), (2) merge jitsi-meet-improved so Child 2 brand neutralization and meet.ffmuc.net removal are preserved and WebhookController.php matches base, (3) toggle self-hosted fields and Debug section cleanly in displayJaas() when switching modes without page reload"
    status: pending
  - id: c7-verify
    content: "Verify on HumHub 1.17 Docker: php -l passes on all touched files; saving persists in both modes; mode switch shows/hides self-hosted, JaaS, and Debug groups with no console errors; custom-domain input hides for preset domains; Copy works under strict CSP"
    status: pending
created_by_model: "auto"
created_at: "2026-07-27T11:48:03Z"
last_modified_by_model: "auto"
last_modified_at: "2026-10-27T10:10:08Z"
cursor_mode: "auto"
---
## Child 7 - Admin settings GUI on HumHub ActiveForm

**Branch:** `feat/settings-gui-activeform` ([PR #15](https://github.com/jhweb/jitsi-meet/pull/15)).
**Owns:** `views/config/index.php`, `models/SettingsForm.php` (`attributeLabels()`/`attributeHints()`), `controllers/ConfigController.php` (`$form->validate()` before `$form->save()`), `resources/humhub.jitsiMeet.config.js`, `assets/ConfigAssets.php`, `assets/Assets.php`.

Verified against local HumHub 1.17 core: `humhub\modules\ui\form\widgets\ActiveForm` exposes `beginCollapsibleFields($title, $isClosed = true)` / `endCollapsibleFields()` and supports `'acknowledge' => true`.

---

## Codebase Audit Findings on `feat/settings-gui-activeform` (2026-10-27)

Auditing commits `6d284c8` and `a2b151f` on `feat/settings-gui-activeform` against `jitsi-meet-improved` found **four concrete bugs** that must be fixed before PR #15 can pass verification or merge:

1. **FATAL PHP compile error in `models/SettingsForm.php` (duplicate `attributeLabels()`):**
   - `public function attributeLabels()` is declared **twice** in `models/SettingsForm.php` (lines 98–127 and lines 184–213). Any request that loads `SettingsForm` crashes immediately with `Fatal error: Cannot redeclare humhubContrib\modules\jitsiMeetCloud8x8\models\SettingsForm::attributeLabels()`.
   - **Fix:** Delete the duplicate declaration and keep a single consolidated `attributeLabels()` method with all 24 attributes translated via `Yii::t('JitsiMeetCloud8x8Module.base', ...)`.

2. **Branch is 26 commits behind `jitsi-meet-improved` (stale branding & `WebhookController.php` drift):**
   - `feat/settings-gui-activeform` branched before `jitsi-meet-improved` merged `upstream/master` (`meet.ffmuc.net` removal), Child 1 (#9), Child 2 (#10 brand neutralization), Child 3 (#11), Child 4 (#12), and Child 8 (#17).
   - Consequently, `models/SettingsForm.php` on this branch still has `'The Oil Press Broadcast Room'` at line 327 and `meet.ffmuc.net` in `DEFAULT_JITSI_DOMAINS`, and the branch carries duplicate Child 3 commits touching `controllers/WebhookController.php`.
   - **Fix:** Merge `jitsi-meet-improved` into `feat/settings-gui-activeform`, keep `jitsi-meet-improved`'s `WebhookController.php` and neutralized defaults (`'Live Broadcast Room'`), and verify `git diff jitsi-meet-improved...feat/settings-gui-activeform` touches only Child 7's owned files.

3. **Mode switching (`displayJaas`) leaves self-hosted fields and `Debug` section in inconsistent states:**
   - In `views/config/index.php` line 183, the `Debug` collapsible group is wrapped in server-side `<?php if ($model->mode === 'jaas'): ?>`. If the page loads in `self_hosted` mode and the admin switches the dropdown to `jaas`, the `Debug` section does not exist in the DOM; if loaded in `jaas` mode and switched to `self_hosted`, the `Debug` section stays visible.
   - In `resources/humhub.jitsiMeet.config.js`, `displayJaas()` shows/hides `.field-settingsform-jaas*`, but never hides self-hosted-only fields (`.field-settingsform-jitsidomain`, `.field-settingsform-enablejwt`, `.field-settingsform-jitsiappid`, `.field-settingsform-jitsiappsecret`) when `mode === 'jaas'`.
   - **Fix:** Always render the `Debug` group inside a `.jitsi-debug-section` wrapper (`<?= $model->mode === 'jaas' ? '' : ' hide' ?>`) and have `displayJaas()` toggle `.jitsi-debug-section` as well as self-hosted vs. JaaS fields (calling `displayJwtParams()` only when `!isJaas`).

4. **Custom `jitsiDomain` widget omits validation error rendering and `change`-time value sync:**
   - In `views/config/index.php` lines 69–84, the manual `.field-settingsform-jitsidomain` block renders `jitsiDomainSelect`, `jitsiDomainCustom`, and the hidden `SettingsForm[jitsiDomain]`, but omits `Html::error($model, 'jitsiDomain', ['class' => 'help-block help-block-error'])` and only syncs `#settingsform-jitsidomain-value` on form `submit` (not when the dropdown or custom input changes).
   - **Fix:** Update `prepareJitsiDomainSubmit()` to run on `change` / `input` of `#settingsform-jitsidomain` and `#settingsform-jitsidomain-custom` (in addition to `submit`), and render `<?= Html::error($model, 'jitsiDomain', ['class' => 'help-block help-block-error']) ?>` inside `.field-settingsform-jitsidomain`.

---

## Implementation Checklist

1. Swap `yii\bootstrap\ActiveForm` for `humhub\modules\ui\form\widgets\ActiveForm` with `['id' => 'configure-form', 'acknowledge' => true]`, and ensure `ConfigController::actionIndex()` calls `$form->validate()` before `$form->save()`.
2. Organize the form with `$form->beginCollapsibleFields()` / `$form->endCollapsibleFields()` groups: **Connection**, **Features**, **Permission Defaults**, and **Debug**.
3. Extract inline JS into `resources/humhub.jitsiMeet.config.js` (`humhub.module('jitsiMeet.config', ...)`) registered via `assets/ConfigAssets.php`.
4. Reconcile `jitsiDomain` preset select + `__custom__` input cleanly without duplicate DOM IDs, keeping `#settingsform-jitsidomain-value` synced on `change`/`input`/`submit` and displaying any model validation errors.
5. Use Bootstrap 3 `.hide` + jQuery `.hide()`/`.show()` (no Bootstrap 4/5 `d-none`).
6. Use `ClipboardJsAsset` + `data-action-click="jitsiCopyWebhookUrl"` with `aria-label` and `ui.status` feedback so copying the webhook URL works under strict CSP.
7. Preserve Bootstrap 3 classes (`panel panel-default`, `panel-heading`, `panel-body`, `label label-success`, `help-block`, `control-label`, `input-group-btn`).
8. Wrap all labels, hints, mode options, Copy button, and Debug status strings in `Yii::t('JitsiMeetCloud8x8Module.base', ...)`.
9. Guard `@filesize($keyPath)` when `$keyExists` is true, and ensure `ConfigController` runs `$form->validate()` so `validateJaasPrivateKeyPath` errors display on save.
10. Resolve all 4 audit findings (`c7-fix-branch-regressions`) and run live HumHub 1.17 verification (`c7-verify`).

**Done when:** `php -l` passes on all modified PHP files; saving in both modes persists and validates properly; switching mode dynamically toggles self-hosted fields, JaaS fields, and the Debug group with zero console errors; the custom-domain input hides when a preset domain is chosen; Copy works under strict CSP.

---

## Parent and branch topology

Parent: [Jitsi module hardening meta](jitsi_module_hardening_meta_f78868ce.plan.md). Branch off `jitsi-meet-improved` and merge back into it — never off `master`.

## Rules for implementing agents (non-negotiable)

1. **Target HumHub 1.17 / Bootstrap 3.** Never change `data-dismiss` to `data-bs-dismiss`. Never change `panel`/`panel-body`/`panel-heading` to `card`. Never change `label label-success` to `badge`. Never introduce `d-none`, `ms-*`, `me-*`, `text-bg-*` or other BS4/5-only classes.
2. **Stay inside your owned file list.**
3. **No PHP on the host.** Lint with `docker compose -f docker/docker-compose.dev.yml exec humhub php -l <file>`.
4. **Push your branch immediately** after commits: `git push -u origin <branch>`.
5. **Forbidden without explicit human approval:** `git reset --hard`, `git clean -fd`, `git push --force`, `git rebase`, `docker system prune`, deleting `docker/`, `protected/`, `themes/`, `uploads/`.
6. **Never log secrets.**
