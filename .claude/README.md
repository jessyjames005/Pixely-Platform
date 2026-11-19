# AI Agent Configuration — Pixely Platform

This directory contains shared AI agent configuration for the Pixely Platform repository. It is designed to work with **Cline, ChatGPT, Gemini, Claude Code, and GitHub Copilot** — all agents share the same content under `.claude/`, and community skills are additionally shared via `ai-skills/` (see below).

## Supported AI Tools

| Tool | How it uses this directory |
|------|---------------------------|
| **Cline** | Reads `.clinerules` at repo root, which imports from `.claude/rules/` |
| **ChatGPT** | Reads `.chatgpt/instructions.md` |
| **Gemini** | Reads `.gemini/rules/README.md` for project rules, and `.gemini/skills/` for Agent Skills (symlinked into `ai-skills/community/`) |
| **Claude Code** | Reads `.claude/CLAUDE.md`, `.claude/settings.json`, `.claude/agents/`, `.claude/skills/`, `.claude/rules/` |
| **GitHub Copilot** | Reads `.github/copilot/copilot-instructions.md` |
| **Kilo** | Reads `AGENTS.md` (via `kilo.json`) and `.kilo/skills/` (symlinked into `ai-skills/community/`) |

## Directory Structure

```
.claude/
├── README.md              # This file (tool-agnostic)
├── CLAUDE.md              # Claude Code specific guidance
├── settings.json          # Claude Code permissions + hooks
├── sync-setup.sh          # Configuration sync script
├── agents/                # Specialized agents (backend-dev, frontend-dev, workflow-helper)
├── skills/                # PP_* project skills (real files) + symlinks to ai-skills/community/
├── rules/                 # Path-scoped rules (auto-loaded)
└── references/            # Patterns/conventions imported by agents

ai-skills/
└── community/             # Single shared copy of every third-party skill
                            # (see ai-skills/README.md). .claude/skills/,
                            # .gemini/skills/ and .kilo/skills/ only hold
                            # symlinks here — nothing is duplicated.
```

## How to Use

### For Cline
Cline reads `.clinerules` at the repo root. This file imports all rules from `.claude/rules/`. No additional setup needed.

### For ChatGPT
ChatGPT reads `.chatgpt/instructions.md`. This file references the shared `.claude/` content.

### For Gemini
Gemini reads `.gemini/rules/README.md` for project rules. It also discovers Agent Skills in `.gemini/skills/`, which are symlinks into `ai-skills/community/` — the same files Claude Code and Kilo use.

### For All AI Agents
All AI agents use `.claude/` directly. The `CLAUDE.md` file contains project guidance, and `settings.json` configures permissions and hooks.

### For GitHub Copilot
Copilot reads `.github/copilot/copilot-instructions.md`. This file references the shared `.claude/` content.

## Content Organization

- **agents/** — Specialized agent definitions (backend-dev, frontend-dev, workflow-helper)
- **skills/** — Project skills prefixed with `PP_` (e.g., `PP_api-design`, `PP_extension-lifecycle`), plus symlinks to the shared community skills in `ai-skills/community/` (design/UI/UX pack, debugging, code-review, devops, web-testing, ai-multimodal, docs-seeker, mermaidjs-v11, skill-creator)
- **rules/** — Path-scoped rules that auto-load based on file paths
- **references/** — Architectural patterns and conventions

## Syncing Configuration

The `sync-setup.sh` script keeps this configuration in sync with the shared repository. It is triggered automatically by Claude Code's SessionStart hook, but can be run manually:

```bash
sh .claude/sync-setup.sh
```

Community skills are synced separately (they come from a different, public upstream repository — see `ai-skills/README.md`):

```bash
sh scripts/sync-community-skills.sh
```

## Local files not overwritten

- `.claude/settings.local.json`
- `.claude/mcp/config.json` (tokens)
- `.claude/worktrees/`

## Note on this file

This file previously contained a few mojibake characters (double UTF-8 encoded em-dashes/quotes, e.g. `â€”`) from an earlier edit. It has been rewritten here as clean UTF-8; no other content was changed beyond what's described above.
