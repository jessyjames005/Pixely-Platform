# Community skills catalog

Source: github.com/mrgoonie/claudekit-skills (MIT). Synced via
`scripts/sync-community-skills.sh`. See `../README.md` for the shared-store
mechanism (symlinks from `.claude/skills/`, `.gemini/skills/`, `.kilo/skills/`).

| Skill | Status | What it's for on Pixely |
|---|---|---|
| `design`, `design-system`, `ui-styling`, `brand`, `banner-design`, `slides` | ✅ synced ("UI/UX Pro Max") | Design tokens, Vuetify-era UI styling, brand/visual identity, banners, slides |
| `debugging` (+ systematic-debugging, root-cause-tracing, defense-in-depth, verification-before-completion) | ⏳ pending sync | Root-cause-first debugging discipline, matches the "no success claims without evidence" rule in `AGENTS.md` |
| `code-review` | ⏳ pending sync | Structured review / verification gates before a lot is marked done |
| `devops` | ⏳ pending sync | Docker, CI/CD, GitOps, security audit — matches the Docker-only, GitHub Actions CI setup |
| `web-testing` | ⏳ pending sync | Playwright/Vitest/k6 patterns (E2E, a11y, perf) — complements `PP_testing` |
| `ai-multimodal` | ⏳ pending sync | Google Gemini API: audio/video/image/PDF processing — relevant to the Media Converter, Blog, Shop, Camping extensions |
| `docs-seeker` | ⏳ pending sync | Up-to-date Laravel/Vue/Vuetify documentation lookup |
| `mermaidjs-v11` | ⏳ pending sync | Diagrams for ARCHITECTURE.md, ROADMAP.md, extension analyses |
| `skill-creator` | ⏳ pending sync | Authoring well-formed new `PP_*` project skills |

Run `sh scripts/sync-community-skills.sh` once to turn every "⏳ pending sync"
row into a real, working skill.

## Deliberately left out (checked, doesn't fit the stack)

- `better-auth` — JS/TS auth framework; Pixely uses Laravel's own auth.
- `databases` — covers MongoDB/PostgreSQL; Pixely runs MySQL.
- `shopify`, `bunny`, `threejs`, `web-frameworks` (Next.js), `frontend-dev-guidelines` (React/MUI) — different stacks entirely (Vue 3 + Vuetify here, not React/Next).
- `aesthetic`, `frontend-design` — overlap with the already-installed `design`/`ui-styling` pack.
- `mcp-builder`, `mcp-management`, `payment-integration`, `problem-solving` (+ sub-skills), `repomix`, `sequential-thinking`, `context-engineering`, `chrome-devtools` — plausible later, but no current Pixely need; add them the same way (new `MAPPINGS` line) if that changes.
