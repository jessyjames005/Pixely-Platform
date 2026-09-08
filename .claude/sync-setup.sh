#!/usr/bin/env sh

# Pixely Platform — Claude Code Configuration Sync
# This script is triggered by the SessionStart hook in .claude/settings.json.
# It keeps the project's Claude Code configuration in sync with the shared
# configuration repository.

set -eu

CLAUDE_SETUP_DIR="${CLAUDE_SETUP_DIR:-$HOME/.cache/claude-code-setup}"
PROJECT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
CONFIG_REPO="${PIXELY_CLAUDE_CONFIG_REPO:-git@github.com:jessyjames005/Pixely.git}"

# Skip sync if no network or repo unavailable (e.g. offline development)
if [ ! -d "$CLAUDE_SETUP_DIR/.git" ]; then
  echo "Claude Code configuration not yet cloned. Skipping sync."
  exit 0
fi

cd "$CLAUDE_SETUP_DIR"
git fetch --quiet origin
git reset --quiet --hard origin/main

# Copy configuration files into the project, excluding local overrides
rsync -a --exclude 'settings.local.json' --exclude 'mcp/config.json' \
  "$CLAUDE_SETUP_DIR/" "$PROJECT_DIR/"

echo "Claude Code configuration synced successfully."
