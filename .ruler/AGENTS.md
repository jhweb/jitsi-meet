---
description: 
alwaysApply: false
---

# BRAINDRAIN — Protocol

## Rules (Core)

1. **Environment first**: call `get_env_context()` at start.
2. **Discover before loading**: call `search_tools()`.
3. **Route big outputs**: use `route_output()`.
4. **Measure**: call `get_token_dashboard()`.
5. **Keep docs current**: update progress/ops logs.
6. **No self-probing**: use the cached context.

| Tool | Purpose |
|---|---|
| `get_env_context()` | Cached OS fingerprint |
| `prime_workspace()` | Deploy rules to project |
| `search_tools()` | Discover deferred tools |
| `route_output()` | Index large text |
| `search_index()` | Retrieve from index |
| `get_token_dashboard()` | Savings snapshot |

---

## Environment Context Protocol

Before running any shell commands, installing packages, or assuming tool
availability — call `get_env_context()` first.

It tells you:
- Exact hostname, username, and LAN IPs
- Which package manager to use (`brew` vs `apt` vs `dnf` etc.)
- Which Python interpreters are in PATH and which is active via pyenv
- Active runtimes and their version managers
- Which modern CLI tools are installed (`fd`, `bat`, `rg`, `fzf` …)
- Which IDEs/agents have MCP configs and where those files live
- Which LLM servers are running locally and on what ports
- Shell type, browsers, VM tools, GUI tools
- Agent behaviour hints (what to prefer and avoid on this OS)

**Never probe the environment yourself.** If something seems missing,
call `refresh_env_context()`.

---



---

## Project rules workflow (Cursor)

- `.cursor/rules/braindrain.mdc` — BRAINDRAIN protocol (from Ruler `RULES.md`); do not duplicate this text elsewhere.
- `.cursor/rules/project-rules.mdc` — **project-only** context, refreshed from `.braindrain/` (`AGENT_MEMORY.md`, `OPS.md`, `SESSION_PROGRESS.md`) when you run `prime_workspace()`. Legacy `.devdocs/` is used only if `.braindrain/` is missing.
- When you learn **durable** project facts or recurring corrections across chats, **suggest updates** to `project-rules.mdc` (and the underlying `.braindrain/` files) so future sessions stay aligned — do not paste the full protocol into `project-rules.mdc`.

## Model provenance policy

- Read model provenance policy from `config/hub_config.yaml` (`provenance.*`).
- If `provenance.chat_footer.enabled=true`, append a compact footer based on scope:
  - `all_agents` -> all completion messages
  - `planning_only` -> planning/audit/coordination completion messages
  - `off` -> disabled
- Footer format: `model: <model_name> | date: <YYYY-MM-DD>`.
- If exact model is unknown (for example Cursor Auto without exposed model id), use `model: auto`.
- In `planning_only` scope, when creating or materially rewriting any `*.plan.md`, include YAML frontmatter metadata at first write (not later patch-up), including:
  - plan metadata: `name`, `owner`, `dri`, `disposition`, `priority`, `parent`, `ide`, `isProject`, `todos`
  - provenance metadata: `created_by_model`, `created_at`, `last_modified_by_model`, `last_modified_at`, `cursor_mode`
- Plans and planning reports should keep provenance in frontmatter fields:
  `created_by_model`, `created_at`, `last_modified_by_model`, `last_modified_at`, `cursor_mode`.
