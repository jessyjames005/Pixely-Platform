# Worked example — PP_create-mr step-by-step run-through

This is a synthetic walk-through of a nominal MR creation. All names are placeholders. Use this as a sanity check when implementing or extending the skill.

## Setup

- **Branch**: `feature/<feature-name>`
- **Parent**: `feature/<parent>` (the actual MR target)
- **Commits**: 3, all `Fnc:`-prefixed
- **Workspace**: clean, branch up to date with origin

## Run-through

1. **Step 0 — Pre-conditions and context check**
   - 0.0 Tooling preflight: `glab` and `jq` available, `glab auth status` returns 0 for `github.com`. Pass.
   - 0.1 `BRANCH = feature/<feature-name>` matches `feature/*`, not `master`/`release/*`. Pass.
   - 0.2 `glab mr list --source-branch "feature/<feature-name>"` returns `[]` — no open MR. Pass.
   - 0.3 `git rev-list --count "origin/<TARGET_CANDIDATE>..HEAD"` returns `3`. Pass.

2. **Step 1 — Preflight workspace**
   - 1.1 `git status --porcelain` empty → no dirty-workspace prompt.
   - 1.2 `git status -sb` does not match `[ahead|gone` → no push prompt.

3. **Step 2 — Auto-detect MR fields**
   - `TARGET_CANDIDATE = feature/<parent>` (from `@{u}`)
   - `TEMPLATE_CANDIDATE = merge_feature.md` (branch is `feature/*`, dominant prefix `Fnc:`)
   - `TITLE_CANDIDATE = "<Extension> - <fonctionnalité>"`
   - `EXTENSIONS = ["<extension>"]`

4. **Step 3, Call A** — `AskUserQuestion` (target, template, title). User accepts the three recommended values in one click.

5. **Step 3, Call B** — `AskUserQuestion` (ticket type). User picks "Tuleap":
   - Plain text: link `https://tuleap.pixely.com/...?aid=<NNNNN>`
   - Plain text: name `"<Extension> - <fonctionnalité>"`
   - Body now contains: `Demande de dev: [<NNNNN> : <Extension> - <fonctionnalité>](#Tuleap-<NNNNN>)`

6. **Step 3, Call C** — body preview rendered with `Actions:` (3 commit subjects) and `Emplacement du dev:` (extensions touchées). User picks "OK — passer aux labels".

7. **Step 3, Call D** — `gh label list --per-page 100` returns the project labels grouped. User picks `Review Priority::<priority>` and `Product::<product>`. `LABELS_CSV = "Review Priority::<priority>,Product::<product>"`.

8. **Step 4 — Recap and final confirmation** — markdown récap printed in French. User picks "Créer".

9. **Step 5 — `gh pr create`** — heredoc-fed `$BODY`, all flags set, succeeds. `PR_URL = https://github.com/pixely/pixely platform/-/pulls/<IID>`.

10. **Step 6 — Reviewers**:
    - Plain text: user types `dev-a, dev-b`
    - Both look like exact usernames (lowercase, no spaces) → no `/users?search=` resolution needed
    - `AskUserQuestion`: user confirms
    - `gh pr edit "<IID>" --reviewer "dev-a,dev-b"`

11. **Step 6 — Lisa (separate question)**:
    - `AskUserQuestion`: user picks "Ajouter Lisa"
    - `RESOLVED_CSV = "dev-a,dev-b"` non-empty → `NEW_REVIEWERS = "dev-a,dev-b,Lisa"`
    - `gh pr edit "<IID>" --reviewer "dev-a,dev-b,Lisa"`

## Final output

Skill ends with the success message in French:

> *"MR créée : `https://github.com/.../<IID>`. Reviewers : dev-a, dev-b, Lisa."*

## Variations covered by other paths (not in this example)

- **Dirty workspace** → Step 1.1 commit gate triggers, user provides commit message validated against the regex
- **Branch not pushed** → Step 1.2 push gate triggers
- **No ticket** → Step 3 Call B "Aucun" → ticket line removed from body
- **No human reviewer** → Step 6 user submits empty CSV → `RESOLVED_CSV` empty, then Lisa added alone
- **Project has no labels** → Step 3 Call D defensive guard: `LABELS_CSV=""`, no `AskUserQuestion`
- **Ahead of master with empty diff vs target** → Step 0.3 hard stop
- **Branch is `master` or `release/*`** → Step 0.1 hard stop
- **MR already open** → Step 0.2 fork via `AskUserQuestion`

