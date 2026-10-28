---
branch: bugfix/jitsi-module-hardening-meta-f78868ce
branches:
  - bugfix/jitsi-module-hardening-meta-f78868ce
---
---
name: Jitsi module hardening meta
overview: "Meta umbrella plan splitting the jitsi-meet-cloud-8x8 hardening work into 10 child plans across 5 stages: security fail-closed fixes, authorization/SSRF, branding neutralization, admin settings GUI, room a11y, and standalone-repo packaging — each scoped to disjoint file ownership so weaker implementing agents cannot conflict."
isProject: false
disposition: meta
priority: P2
owner: "@ettienne"
dri: "@ettienne"
ide: cursor
supersedes: jitsi_module_improvements_0e48bb7c
branch: jitsi-meet-improved
branches:
  - jitsi-meet-improved
  - integration/module-hardening-v1.3.0
children_spec:
  - id: env-docker
    file: jitsi-dev-env-docker.plan.md
    name: Docker dev environment and webhook signing harness
    branch: chore/dev-docker-env
    section: Child 1 - Docker dev environment and webhook signing harness
  - id: brand-neutral
    file: jitsi-brand-neutralization.plan.md
    name: Branding neutralization and module identity
    branch: chore/remove-site-branding
    section: Child 2 - Branding neutralization and module identity
  - id: sec-webhook
    file: jitsi-sec-webhook-failclosed.plan.md
    name: Webhook fail-closed and required secret
    branch: fix/security-webhook-failclosed
    section: Child 3 - Webhook fail-closed and required secret
  - id: sec-jwt
    file: jitsi-sec-jwt-server-side.plan.md
    name: JWT server-side only and moderator cache key
    branch: fix/security-jwt-server-side
    section: Child 4 - JWT server-side only and moderator cache key
  - id: sec-authz
    file: jitsi-sec-authorization.plan.md
    name: Permission wiring and container IDOR
    branch: fix/security-authorization
    section: Child 5 - Permission wiring and container IDOR
  - id: sec-ssrf
    file: jitsi-sec-ssrf-allowlist.plan.md
    name: SSRF allowlist and mass assignment
    branch: fix/security-ssrf-allowlist
    section: Child 6 - SSRF allowlist and mass assignment
  - id: settings-gui
    file: jitsi-settings-gui.plan.md
    name: Admin settings GUI on HumHub ActiveForm
    branch: feat/settings-gui-activeform
    section: Child 7 - Admin settings GUI on HumHub ActiveForm
  - id: room-ui-a11y
    file: jitsi-room-ui-a11y.plan.md
    name: Room UI accessibility and tour targets
    branch: feat/room-ui-a11y
    section: Child 8 - Room UI accessibility and tour targets
  - id: repo-packaging
    file: jitsi-repo-packaging.plan.md
    name: Standalone repo packaging and docs
    branch: docs/repo-packaging
    section: Child 9 - Standalone repo packaging and docs
  - id: qa-staging
    file: jitsi-qa-staging.plan.md
    name: Staging verification and release gate
    branch: chore/qa-staging-verification
    section: Child 10 - Staging verification and release gate
execution_order:
  - jitsi-dev-env-docker
  - jitsi-brand-neutralization
  - jitsi-sec-webhook-failclosed
  - jitsi-sec-jwt-server-side
  - jitsi-sec-authorization
  - jitsi-sec-ssrf-allowlist
  - jitsi-settings-gui
  - jitsi-room-ui-a11y
  - jitsi-repo-packaging
  - jitsi-qa-staging
created_by_model: "auto"
created_at: "2026-07-27T11:43:12Z"
last_modified_by_model: "auto"
last_modified_at: "2026-07-27T15:42:17Z"
cursor_mode: "auto"
children:
  - jitsi-dev-env-docker.plan.md
  - jitsi-brand-neutralization.plan.md
  - jitsi-sec-webhook-failclosed.plan.md
  - jitsi-sec-jwt-server-side.plan.md
  - jitsi-sec-authorization.plan.md
  - jitsi-sec-ssrf-allowlist.plan.md
  - jitsi-settings-gui.plan.md
  - jitsi-room-ui-a11y.plan.md
  - jitsi-repo-packaging.plan.md
  - jitsi-qa-staging.plan.md
todos:
  - id: prereq-prime
    content: "Human: run prime_workspace(bundle=\\"cursor-orchestration\\") to deploy scripts/plan_meta_closeout.py, daily_plan_audit.py and plan_build_guard.py"
    status: completed
  - id: prereq-frontmatter
    content: "Stamp disposition: meta and the children_spec block into this plan's frontmatter; archive the superseded jitsi_module_improvements_0e48bb7c plan"
    status: completed
  - id: prereq-integration-branch
    content: "Create integration/module-hardening-v1.3.0 off feature/stream-space-association and push it"
    status: completed
  - id: split-env-docker
    content: "Split child: Docker dev environment and webhook signing harness (chore/dev-docker-env)"
    status: completed
  - id: split-brand-neutral
    content: "Split child: Branding neutralization and module identity (chore/remove-site-branding)"
    status: completed
  - id: split-sec-webhook
    content: "Split child: Webhook fail-closed and required secret (fix/security-webhook-failclosed)"
    status: completed
  - id: split-sec-jwt
    content: "Split child: JWT server-side only and moderator cache key (fix/security-jwt-server-side)"
    status: completed
  - id: split-sec-authz
    content: "Split child: Permission wiring and container IDOR (fix/security-authorization)"
    status: completed
  - id: split-sec-ssrf
    content: "Split child: SSRF allowlist and mass assignment (fix/security-ssrf-allowlist)"
    status: completed
  - id: split-settings-gui
    content: "Split child: Admin settings GUI on HumHub ActiveForm (feat/settings-gui-activeform)"
    status: completed
  - id: split-room-ui-a11y
    content: "Split child: Room UI accessibility and tour targets (feat/room-ui-a11y)"
    status: completed
  - id: split-repo-packaging
    content: "Split child: Standalone repo packaging and docs (docs/repo-packaging)"
    status: completed
  - id: split-qa-staging
    content: "Split child: Staging verification and release gate (chore/qa-staging-verification)"
    status: completed
  - id: closeout-masterplan
    content: "Run /masterplan to refresh plan reports and the build queue with the new children"
    status: completed
base_branch: jitsi-meet-improved
phase_branches:
  - branch: jitsi-meet-improved
    phase: "improved"
  - branch: integration/module-hardening-v1.3.0
    phase: """""""""""legacy"---"---"---"---"---"---"---"---"---"---"---
## Close-out sequence

1. `prime_workspace(bundle="cursor-orchestration")`
2. Add the `children_spec` block above to this file's frontmatter; set the old plan to `disposition: archived`
3. `python3 scripts/plan_meta_closeout.py --meta .cursor/plans/<this file> --repo-root . --dry-run`, then without `--dry-run`
4. Extract each `Child N` section into its child plan and replace the placeholder `impl-*` todos with the concrete steps
5. `/masterplan`
6. Create `integration/module-hardening-v1.3.0` off `feature/stream-space-association`
7. **Start a new chat**, then Plan Build one child at a time via `python3 scripts/plan_build_guard.py --plan .cursor/plans/<child>.plan.md --repo-root .`