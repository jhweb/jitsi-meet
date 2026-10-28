---
name: Docker dev environment and webhook signing harness
overview: ""
todos:
  - id: c1-verify
    content: "Verify: compose up yields working HumHub 1.17 on http://localhost:9091, module enables cleanly in Administration > Modules, signed POST from webhook-sign.php returns 200"
    status: completed
isProject: false
disposition: implemented
parent: jitsi_module_hardening_meta_f78868ce
branch: chore/dev-docker-env
base_branch: jitsi-meet-improved
pr: https://github.com/jhweb/jitsi-meet/pull/9
pr_url: https://github.com/jhweb/jitsi-meet/pull/9
pr_number: 9
merged_at: "2026-08-04T16:20:47Z"
last_modified_by_model: "auto"
last_modified_at: "2026-08-04T16:24:05Z"
created_by_model: "auto"
created_at: "2026-07-27T15:38:20Z"
cursor_mode: "auto"

---
## Child 1 - Docker dev environment and webhook signing harness

**Branch:** `chore/dev-docker-env`. **Owns:** `docker/**`, `tools/**`, `docs/QA/**` (all new files). Touches no existing module code.

Pin HumHub **1.17** deliberately — this matches production. Do not use `stable-nightly` or `1.18`.

1. `docker/docker-compose.dev.yml` — `humhub/humhub:1.17` plus `mariadb` with a healthcheck, per the [official image](https://github.com/humhub/docker). Env: `HUMHUB_DEBUG=true`, `SERVER_NAME=http://localhost:9091`, DB DSN via `HUMHUB_CONFIG__COMPONENTS__DB__DSN`. Map host `9091:80`. Persist `./humhub-data:/data` and `./mysql-data:/var/lib/mysql`, both gitignored.
2. Bind-mount this repo into the container's module directory. **Research the exact path for the 1.17 image before writing it** — the official image keeps persistent state under `/data`, and modules resolve under the HumHub `modules/` path. Confirm by shelling into a running container; do not assume.
3. Mount a dev JaaS private key read-only at `/var/www/keys/jaas_private.pem` from a gitignored `docker/keys/` dir, and set `HUMHUB_JAAS_PRIVATE_KEY_PATH` to match. Commit `docker/keys/.gitkeep` and `docker/keys/README.md` explaining how to supply a key. **Never commit a real key.**
4. `tools/webhook-sign.php` — CLI helper that reads a JSON payload file, computes the same HMAC the module expects (read the algorithm from `verifySignature()`; match its header name and timestamp handling exactly), and POSTs to the local webhook endpoint. This is the substitute for real 8x8 webhook traffic. Must support a `--tamper` flag that corrupts the signature, and `--stale` that sets a timestamp outside the drift window, so fail-closed behaviour is testable.
5. `docker/README.md` — up, down, install wizard walkthrough, enabling the module, admin login, log locations, how to run `php -l` and `composer install` in the container.
6. `docs/QA/manual-verification.md` — checklist skeleton with an unchecked entry per acceptance criterion from Children 3-8. Later children fill in results.
7. Add `docker/humhub-data/`, `docker/mysql-data/`, `docker/keys/*` (except `.gitkeep`/`README.md`) to `.gitignore`. Leave the BRAINDRAIN block untouched.

**Done when:** `docker compose -f docker/docker-compose.dev.yml up -d` yields a working HumHub 1.17 at `http://localhost:9091`, the module appears in Administration > Modules and enables cleanly, and `tools/webhook-sign.php` gets a 200 with a valid signature.

---

## Parent and branch topology

Parent: [Jitsi module hardening meta](jitsi_module_hardening_meta_f78868ce.plan.md). Branch off `jitsi-meet-improved` and merge back into it — never off `master`. Stage 0, may run in parallel with `jitsi-brand-neutralization`.

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
