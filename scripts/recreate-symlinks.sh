#!/usr/bin/env sh
#
# Pixely Platform — recreate the AI-skills symlinks.
#
# Use this if .claude/skills/<name>, .gemini/skills/<name> or
# .kilo/skills/<name> show up as empty/broken folders instead of symlinks
# (this happens on Windows when a zip was extracted without symlink support,
# or when git checked out the repo before `core.symlinks` was enabled).
#
# Run from Git Bash / WSL at the repo root:
#   sh scripts/recreate-symlinks.sh

set -eu

PROJECT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$PROJECT_DIR"

SKILLS="banner-design brand design design-system slides ui-styling debugging code-review devops web-testing ai-multimodal docs-seeker mermaidjs-v11 skill-creator"

mkdir -p .gemini/skills

for tool in .claude/skills .gemini/skills .kilo/skills; do
  for s in $SKILLS; do
    link="$tool/$s"
    target="../../ai-skills/community/$s"
    # Only replace if it's not already a correct symlink (e.g. an empty dir
    # left over from a non-symlink-aware extraction).
    if [ -L "$link" ]; then
      continue
    fi
    rm -rf "$link"
    ln -s "$target" "$link"
    echo "  recreated $link -> $target"
  done
done

echo "Done."
