# Claude Code Configuration — Pixely Platform

Claude Code setup for the Pixely Platform repository.

## Setup

Setup is automatic: the first time you start Claude Code in this project, a SessionStart hook clones the shared configuration repository and installs everything. No manual step is required.

If the sync fails or you need to re-run it without restarting Claude:

```bash
sh .claude/sync-setup.sh
```

## For new developers

No action required. Clone the repository and start Claude Code — all configuration is pre-installed.

If MCP tokens are needed: `sh .claude/init.sh`

## Content

| File/Folder | Destination in project | Role |
|-------------|----------------------|------|
| `CLAUDE.md` | `<projet>/CLAUDE.md` | Project guidance for Claude |
| `settings.json` | `.claude/settings.json` | Permissions + SessionStart hook |
| `agents/` | `.claude/agents/` | Specialized agents |
| `skills/` | `.claude/skills/` | Invocable skills |
| `rules/` | `.claude/rules/` | Path-scoped rules (auto-loaded) |
| `references/` | `.claude/references/` | Patterns/conventions imported by agents |

## Local files not overwritten

- `.claude/settings.local.json`
- `.claude/mcp/config.json` (tokens)
- `.claude/worktrees/`