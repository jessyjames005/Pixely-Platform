# AI Agent Configuration â€” Pixely Platform

This directory contains shared AI agent configuration for the Pixely Platform repository. It is designed to work with **Cline, ChatGPT, Gemini, Claude Code, and GitHub Copilot** â€” all agents share the same content under `.claude/`.

## Supported AI Tools

| Tool | How it uses this directory |
|------|---------------------------|
| **Cline** | Reads `.clinerules` at repo root, which imports from `.claude/rules/` |
| **ChatGPT** | Reads `.chatgpt/instructions.md` |
| **Gemini** | Reads `.gemini/rules/README.md` |
| **Claude Code** | Reads `.claude/CLAUDE.md`, `.claude/settings.json`, `.claude/agents/`, `.claude/skills/`, `.claude/rules/` |
| **GitHub Copilot** | Reads `.github/copilot/copilot-instructions.md` |

## Directory Structure

```
.claude/
â”œâ”€â”€ README.md              # This file (tool-agnostic)
â”œâ”€â”€ CLAUDE.md              # Claude Code specific guidance
â”œâ”€â”€ settings.json          # Claude Code permissions + hooks
â”œâ”€â”€ sync-setup.sh          # Configuration sync script
â”œâ”€â”€ agents/                # Specialized agents (backend-dev, frontend-dev, workflow-helper)
â”œâ”€â”€ skills/                # Executable workflows (PP_*)
â”œâ”€â”€ rules/                 # Path-scoped rules (auto-loaded)
â””â”€â”€ references/            # Patterns/conventions imported by agents
```

## How to Use

### For Cline
Cline reads `.clinerules` at the repo root. This file imports all rules from `.claude/rules/`. No additional setup needed.

### For ChatGPT
ChatGPT reads `.chatgpt/instructions.md`. This file references the shared `.claude/` content.

### For Gemini
Gemini reads `.gemini/rules/README.md`. This file references the shared `.claude/` content.

### For All AI Agents
All AI agents use \`.claude/\` directly. The `CLAUDE.md` file contains project guidance, and `settings.json` configures permissions and hooks.

### For GitHub Copilot
Copilot reads `.github/copilot/copilot-instructions.md`. This file references the shared `.claude/` content.

## Content Organization

- **agents/** â€” Specialized agent definitions (backend-dev, frontend-dev, workflow-helper)
- **skills/** â€” Executable workflows prefixed with `PP_` (e.g., `PP_api-design`, `PP_extension-lifecycle`)
- **rules/** â€” Path-scoped rules that auto-load based on file paths
- **references/** â€” Architectural patterns and conventions

## Syncing Configuration

The `sync-setup.sh` script keeps this configuration in sync with the shared repository. It is triggered automatically by Claude Code's SessionStart hook, but can be run manually:

```bash
sh .claude/sync-setup.sh
```

## Local files not overwritten

- `.claude/settings.local.json`
- `.claude/mcp/config.json` (tokens)
- `.claude/worktrees/`

