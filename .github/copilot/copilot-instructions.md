# GitHub Copilot — Project Instructions

Project-wide AI agent rules live in **`AGENTS.md`** (the single source of truth
for every AI tool on this repo) and in
**`docs/development/PROJECT_RULES.md`** (detailed architecture, API, frontend,
testing, git and Docker rules).

Please read `AGENTS.md` before acting. Key points:
- PHP & Node must run in Docker dev containers (no local PHP/Node).
- Prefix Git, test, and search commands with `rtk`.
- Never commit/push without explicit user authorization.
- For sprint work, follow `docs/development/SPRINT_WORKFLOW.md`: complete and validate one numbered lot at a time, propose its commit message, and prepare the sprint PR description only after all lots are validated.
