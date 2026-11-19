# Patch — AI skills: add Claude/Code/Gemini skills + reorganize into one shared store

## What changed

1. **New shared location — `ai-skills/community/`**
   Single on-disk copy of every third-party AI skill. `.claude/skills/`,
   `.gemini/skills/` (new) and `.kilo/skills/` now only contain **symlinks**
   into it, instead of full duplicated folders.

2. **De-duplicated the existing "UI/UX Pro Max" pack**
   `design`, `design-system`, `ui-styling`, `brand`, `banner-design`, `slides`
   were physically duplicated in `.claude/skills/` and `.kilo/skills/`
   (confirmed byte-identical). They are now moved once into
   `ai-skills/community/`, and `.claude/skills/`, `.kilo/skills/`,
   `.gemini/skills/` each get a symlink.

3. **8 new skills added** (same upstream, github.com/mrgoonie/claudekit-skills,
   MIT), covering the three bundles you picked plus the "single shared
   location" request:
   - Qualité/debug + code-review: `debugging` (+ `systematic-debugging`,
     `root-cause-tracing`, `defense-in-depth`, `verification-before-completion`),
     `code-review`
   - DevOps + web-testing: `devops`, `web-testing`
   - Gemini: `ai-multimodal`
   - Docs/diagrams/meta: `docs-seeker`, `mermaidjs-v11`, `skill-creator`

   **Important caveat**: these 8 ship as `PENDING.md` placeholders only — I
   don't have git/network access in this session, so I could not pull their
   exact upstream `SKILL.md` content without risking an inaccurate
   reconstruction from scattered web excerpts. Instead I built the sync
   mechanism (`scripts/sync-community-skills.sh`) that fetches the *exact*
   upstream files via a shallow `git clone` of the real repo. **Run it once**
   after applying this patch:
   ```bash
   sh scripts/sync-community-skills.sh
   ```
   This also means `debugging`, `code-review`, `devops`, `web-testing`,
   `ai-multimodal`, `docs-seeker`, `mermaidjs-v11` and `skill-creator` are
   *not yet active* until that command is run once. `design`,
   `design-system`, `ui-styling`, `brand`, `banner-design`, `slides` are
   already fully populated (just moved, not re-fetched).

4. **Skills deliberately left out** (checked against your stack — Laravel/
   MySQL/Vue3/Vuetify/Docker — and found not to fit): `better-auth`,
   `databases` (Mongo/Postgres, you're on MySQL), `shopify`, `bunny`,
   `threejs`, `web-frameworks` (Next.js), `frontend-dev-guidelines`
   (React/MUI), `aesthetic`/`frontend-design` (overlap with the design pack
   already installed). Listed with reasons in
   `ai-skills/community/README.md` in case priorities change later.

5. **Docs updated** to reflect the new structure: `.claude/README.md`,
   `AGENTS.md` ("UI/UX Pro Max Skills" section renamed/expanded to
   "Community Skills (`ai-skills/`)"), `.gemini/rules/README.md`.

6. **Small cleanups found along the way**:
   - Removed `.claude/skills/PP_sprint-delivery/` — an empty leftover
     directory (the real skill is `PP_sprint_delivery/`, underscore).
   - `.claude/README.md` had a handful of mojibake characters (double
     UTF-8-encoded em-dashes/quotes, e.g. `â€”`) from an earlier edit —
     confirmed at the byte level, not a display artifact — and rewrote the
     file as clean UTF-8. No wording changed beyond the additions described
     above; flagging per your encoding rule rather than changing it silently.

## New files

- `ai-skills/README.md` — mechanism overview (symlinks, sync, Windows note)
- `ai-skills/community/README.md` — catalog of every community skill with
  status and the "left out" list with reasons
- `ai-skills/community/<name>/` — the skills themselves (6 populated, 8
  pending sync)
- `scripts/sync-community-skills.sh` — pulls real upstream content
- `scripts/recreate-symlinks.sh` — Windows fallback if symlinks don't survive
  a checkout/extraction

## Caveats / things to check

- **Windows + symlinks**: you work locally on Windows/PowerShell. Git for
  Windows needs `git config --global core.symlinks true` (plus Developer
  Mode or an elevated shell on older Git versions) for these symlinks to
  check out as real symlinks rather than empty folders. If that happens,
  `sh scripts/recreate-symlinks.sh` (from Git Bash) fixes it without
  touching anything else. Worth a quick test on your machine before relying
  on this across the team.
- **Run the sync script once** — see point 3 above — before expecting
  `debugging`/`code-review`/`devops`/`web-testing`/`ai-multimodal`/
  `docs-seeker`/`mermaidjs-v11`/`skill-creator` to do anything.
- Nothing under `app/`, `config/`, tests, or CI was touched — this patch is
  scoped entirely to AI agent configuration (`.claude/`, `.gemini/`,
  `.kilo/`, `ai-skills/`, `AGENTS.md`, `scripts/`).
- As always: nothing has been committed or pushed.
