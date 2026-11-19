# ai-skills/ — Single shared store for community AI skills

This directory is the **one physical copy** of every third-party ("community")
AI skill used on Pixely Platform. `.claude/skills/`, `.gemini/skills/` and
`.kilo/skills/` only contain **symlinks** into `ai-skills/community/<name>/`,
so Claude Code, Gemini CLI and Kilo all read the exact same files — nothing is
duplicated, and updating a skill once updates it everywhere.

Pixely's own project skills (`PP_*`) are not affected: they stay as real
files directly under `.claude/skills/PP_*`, since they're specific to this
codebase rather than shared third-party content.

## Layout

```
ai-skills/
└── community/
    ├── design/            # "UI/UX Pro Max" pack
    ├── design-system/
    ├── ui-styling/
    ├── brand/
    ├── banner-design/
    ├── slides/
    ├── debugging/          (+ systematic-debugging, root-cause-tracing,
    │                         defense-in-depth, verification-before-completion)
    ├── code-review/
    ├── devops/
    ├── web-testing/
    ├── ai-multimodal/      # Google Gemini API (audio/video/image/PDF)
    ├── docs-seeker/
    ├── mermaidjs-v11/
    └── skill-creator/
```

`ui-styling` and the 8 newer skills are verbatim copies of the upstream skills
from **github.com/mrgoonie/claudekit-skills** (MIT licensed). `design`,
`design-system`, `brand`, `banner-design` and `slides` come from another
claudekit repo and are kept as-is (not synced by the script). Nothing here is
rewritten or summarized by an AI — content is pulled as-is by
`scripts/sync-community-skills.sh` so instructions stay exactly as the
upstream authors wrote them.

## First-time setup / updating

```bash
sh scripts/sync-community-skills.sh
```

This shallow-clones `mrgoonie/claudekit-skills` and copies the skills listed
in the script into `ai-skills/community/`. Needs network + git; run it once
to populate any folder still containing a `PENDING.md` placeholder, and
re-run it later to pick up upstream updates.

`design`, `design-system`, `ui-styling`, `brand`, `banner-design` and `slides`
("UI/UX Pro Max") are already populated in this patch. `debugging`,
`code-review`, `devops`, `web-testing`, `ai-multimodal`, `docs-seeker`,
`mermaidjs-v11` and `skill-creator` ship as `PENDING.md` placeholders only —
run the sync script to fetch their real content before relying on them.

## Windows note

Symlinks need `git config --global core.symlinks true` (and, on older Git for
Windows, Developer Mode or an elevated prompt) to check out correctly. If a
skill folder looks empty instead of a symlink, run:

```bash
sh scripts/recreate-symlinks.sh
```

## Adding another community skill later

1. Add a `name:path` line to `MAPPINGS` in `scripts/sync-community-skills.sh`.
2. Add `name` to the `SKILLS` list in `scripts/recreate-symlinks.sh`.
3. Re-run both scripts.
