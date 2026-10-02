# AI Agent Configuration — Pixely Platform

This directory configures **Google Gemini** as an AI coding agent for the
Pixely Platform. Gemini reads every `.md` file under `.gemini/rules/` as
project-level instructions.

The project maintains **one unified ruleset for every AI tool**
(Claude Code, Gemini, ChatGPT, GitHub Copilot, Cline). Rather than duplicate
the rules here, this file points to the single source of truth.

## Read first — single source of truth

1. **`AGENTS.md`** (project root) — the canonical agent rules for ALL AI
   assistants: project overview, Docker requirement, RTK IA command prefixes,
   commit conventions, and which tool reads which file.
2. **`docs/development/PROJECT_RULES.md`** — the detailed reference for
   architecture, API design, frontend, testing, git, and Docker rules.
3. **`CLAUDE.md`** — Claude Code entry (mirrors `AGENTS.md`).

## TL;DR for Gemini

- **Environment**: never run PHP or Node locally — use
  `docker exec docker-php-1 …` / `docker exec docker-node-1 …`.
- **Commands**: prefix Git, test and search commands with `rtk`
  (e.g. `rtk git status`, `rtk php artisan test`, `rtk rg "<pattern>"`). Do
  not prefix `composer install`, `npm ci`, `npm run build`.
- **Commits**: never commit/push without explicit user authorization;
  Conventional Commits with a domain scope; no AI attribution.
- **Code**: delegate to the `backend-dev` / `frontend-dev` agents described in
  `CLAUDE.md`.

## Multi-tool configuration overview

| Tool | Config location | Notes |
|------|----------------|-------|
| Kilo | `AGENTS.md` (loaded via `kilo.json` → `instructions`) | this session's agent loader |
| Claude Code | `CLAUDE.md` | mirrors `AGENTS.md` |
| Gemini | `.gemini/rules/` (this directory) | reads these as project instructions |
| ChatGPT | `.chatgpt/instructions.md` | → points to `AGENTS.md` |
| GitHub Copilot | `.github/copilot/copilot-instructions.md` | → points to `AGENTS.md` |
| Cline | `.clinerules` | → points to `AGENTS.md` |
