#!/usr/bin/env sh
#
# Pixely Platform — Community AI skills sync
#
# Populates ai-skills/community/<name>/ with the real SKILL.md (and any
# references/scripts/templates) from the upstream claudekit-skills
# repository. ai-skills/community/ is the single on-disk copy; .claude/skills/,
# .gemini/skills/ and .kilo/skills/ only hold symlinks into it, so every AI
# tool sees the same, up-to-date content.
#
# Requires: git, network access. Run from the repo root:
#   sh scripts/sync-community-skills.sh
#
# Safe to re-run: it only refreshes the skills listed in SKILLS below and
# never touches .claude/skills/PP_* (the project's own skills).

set -eu

REPO_URL="${PIXELY_SKILLS_SOURCE_REPO:-https://github.com/mrgoonie/claudekit-skills}"
REPO_REF="${PIXELY_SKILLS_SOURCE_REF:-main}"
PROJECT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
TARGET_DIR="$PROJECT_DIR/ai-skills/community"
TMP_DIR="$(mktemp -d)"

# name_in_pixely:path_in_upstream_repo
# NOTE: banner-design, brand, design, design-system and slides are NOT in
# mrgoonie/claudekit-skills (they come from another claudekit repo); they are
# kept as-is in ai-skills/community/ and are deliberately not synced here.
# (most skills live at .claude/skills/<name>/ upstream; a few are nested
# under .claude/skills/document-skills/<name>/ — add more lines here if you
# sync additional skills later, e.g. pdf, docx, xlsx, pptx.)
MAPPINGS="
ui-styling:.claude/skills/ui-styling
debugging:.claude/skills/debugging
code-review:.claude/skills/code-review
devops:.claude/skills/devops
web-testing:.claude/skills/web-testing
ai-multimodal:.claude/skills/ai-multimodal
docs-seeker:.claude/skills/docs-seeker
mermaidjs-v11:.claude/skills/mermaidjs-v11
skill-creator:.claude/skills/skill-creator
"

cleanup() { rm -rf "$TMP_DIR"; }
trap cleanup EXIT

echo "Cloning $REPO_URL@$REPO_REF (shallow)..."
git clone --depth 1 --branch "$REPO_REF" --single-branch "$REPO_URL" "$TMP_DIR" --quiet

mkdir -p "$TARGET_DIR"

for mapping in $MAPPINGS; do
  name="${mapping%%:*}"
  upstream_path="${mapping#*:}"
  src="$TMP_DIR/$upstream_path"
  dest="$TARGET_DIR/$name"

  if [ ! -d "$src" ]; then
    echo "  ! skip $name: $upstream_path not found upstream (repo layout may have changed)"
    continue
  fi

  rm -rf "$dest"
  mkdir -p "$dest"
  cp -R "$src/." "$dest/"
  echo "  synced $name <- $upstream_path"
done

echo
echo "Done. $TARGET_DIR now holds the real SKILL.md files."
echo "Symlinks in .claude/skills/, .gemini/skills/ and .kilo/skills/ need no change."
echo
echo "Windows note: if a symlink appears as a broken/empty folder after"
echo "'git clone' or extracting a zip, enable symlink support once with:"
echo "    git config --global core.symlinks true"
echo "then re-checkout (or run scripts/recreate-symlinks.sh from Git Bash)."
