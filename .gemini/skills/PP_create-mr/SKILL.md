---
name: PP_create-mr
description: "Use when the user invokes /PP_create-mr to create a new GitHub pull request on this Pixely Platform project. User-only — `disable-model-invocation: true` prevents autonomous routing; never trigger on phrases like \"crée la PR\", \"ouvre la PR\" without an explicit slash-command invocation."
disable-model-invocation: true
---

# Creating a new GitHub pull request on this project

> **Scope — Pixely Platform only.** This skill is hard-coded to the `pixely/pixely platform` project on `github.com` and assumes the project's PR template structure (`merge_feature.md`, `merge_anomalie.md`, `merge_freeze.md`, `merge_freeze_feature.md`) and extension layout (`app/Extensions/<X>/`). Do NOT invoke it from another repository — even another OxPlatform project. For other repos, create PRs manually until a generalized skill exists.

This skill walks the user through the creation of a GitHub PR for Pixely Platform via `gh`. It auto-detects every field it can (target branch, template, extensions touched, ticket info, suggested title, labels) and validates each one through `AskUserQuestion` before running `gh pr create`. It NEVER commits or pushes by itself — every commit and every push goes through a separate explicit user authorization.

The skill itself is written in English. When prompting the user (questions, plain-text follow-ups, recap), translate to the project's working language — **French** for this project — at the moment of the actual `AskUserQuestion` invocation.

## Hostname and project

```bash
HOST="github.com"
PROJECT="pixely%2Fpixely platform"
```

These two variables are used everywhere below. Set them once.

## Step 0 — Pre-conditions and context check

Before anything else, verify the current working directory is a git repository — otherwise every subsequent `git` command fails silently or with a cryptic message:

```bash
git rev-parse --is-inside-work-tree >/dev/null 2>&1 \
  || { echo "Pas dans un dépôt git — relance le skill depuis un clone du repo."; exit 1; }
```

Then set the session context:

```bash
BRANCH=$(git branch --show-current)
```

```bash
# TARGET_CANDIDATE is needed early for the empty-diff check below.
# It is faillible — re-validated by the user in Step 3 Call A.
TARGET_CANDIDATE=$(git rev-parse --abbrev-ref --symbolic-full-name @{u} 2>/dev/null \
                   | sed 's|^origin/||' \
                   || echo "master")
git rev-parse --verify "origin/$TARGET_CANDIDATE" >/dev/null 2>&1 || TARGET_CANDIDATE="master"
```

Run **four** checks in order. Each has its own treatment.

### 0.0 — Tooling preflight (hard stop)

The skill relies on two external tools : `gh` (GitHub CLI) and `jq` (JSON parsing for `gh api user`). Verify the two tools are available and that `gh` is authenticated against the right host. If any check fails, exit with the exact diagnostic — do NOT call `AskUserQuestion`.

```bash
command -v gh >/dev/null || { echo "MISSING: gh. Install: https://github.com/cli/cli"; exit 1; }
command -v jq >/dev/null   || { echo "MISSING: jq. Install: apt install jq (or equivalent)"; exit 1; }

gh auth status --hostname "$HOST" >/dev/null 2>&1 \
  || { echo "MISSING: gh not authenticated for $HOST. Run: gh auth login --hostname $HOST"; exit 1; }
```

The `gh auth status` check uses exit code only (stderr is suppressed) — `gh` prints the auth state on stderr and returns 0 when logged in, non-zero otherwise. If the user has multiple GitHub instances configured, this confirms the project's instance specifically is authenticated.

### 0.1 — Forbidden source branch (hard stop)

If `BRANCH` is `master` or matches `release/*`, exit immediately with the diagnostic message *"Cette branche est protégée par CI, créer une feature/* ou bugfix/* d'abord."* (in French, since it's user-facing). Do NOT call `AskUserQuestion`.

```bash
case "$BRANCH" in
  master|release/*) echo "BLOCKED"; exit 1 ;;
esac
```

### 0.2 — PR already open on this source branch (interactive fork)

```bash
gh pr list --source-branch "$BRANCH" --per-page 100 --hostname "$HOST"
```

If the result lists at least one open PR, display the PR URL(s) and call `AskUserQuestion` (single-select):

- **Question** (in French): *"Une PR est déjà ouverte sur cette branche : `<URL>`. Que faire ?"*
- **Header**: `PR existante`
- **Options**:
  1. *"Voir la PR existante"* (Recommended) — print the URL, then exit cleanly
  2. *"Annuler"* — exit cleanly

This is the only Step 0 check that uses `AskUserQuestion`.

### 0.3 — Empty diff (hard stop)

```bash
if [ "$(git rev-list --count "origin/$TARGET_CANDIDATE..HEAD")" = "0" ]; then
  echo "BLOCKED: nothing to merge"
  exit 1
fi
```

If the count is `0` (no commits ahead of `$TARGET_CANDIDATE`), exit with *"Aucun commit à merger vs `<TARGET_CANDIDATE>`. Annule."* — no `AskUserQuestion`.

The `TARGET_CANDIDATE` detection here is preliminary — the user re-validates it in Step 3 Call A.

## Step 1 — Preflight: commit and push gates

> **Hard rule (do not paraphrase):** *"The skill never commits and never pushes by itself. For each operation, it proposes via `AskUserQuestion` and waits for an explicit user authorization. Each authorization covers exactly one operation — past authorization does not carry over."* (Aligns with CLAUDE.md "NEVER commit/push without explicit authorization".)

### 1.1 — Dirty workspace check

```bash
[ -n "$(git status --porcelain)" ] && echo "DIRTY"
```

If dirty, call `AskUserQuestion` (in French):

- **Question**: *"Workspace non commité détecté. Que faire ?"*
- **Header**: `Workspace dirty`
- **Options**:
  1. *"Commit + push puis continuer"* (Recommended)
  2. *"Stop, je commit moi-même puis je relance le skill"* — exit cleanly

If option 1:

1. Plain-text question: *"Quel message de commit ? (préfixe obligatoire `Fnc:` / `Bug:` / `Ref:` / `Erg:` / `Test:` / `Scm:` / `Trad:` / `Doc:`)"*
2. Validate against the regex: `^(Fnc|Bug|Ref|Erg|Test|Scm|Trad|Doc)\s?:.*$`
3. If invalid, re-ask the user. **Never auto-fix or invent a message.** A wrongly-prefixed commit (e.g. branch name `bugfix/...` but content is a refactor) silently propagates into git history and breaks the project's commit-prefix convention.
4. Only when the message is valid, run:

```bash
git add -A
git commit -m "$VALIDATED_MESSAGE"
```

5. Then proceed to 1.2 (push).

### 1.2 — Ahead-not-pushed check

```bash
git fetch origin "$BRANCH" >/dev/null 2>&1 || true
git status -sb | grep -qE '\[ahead|\[gone' && echo "NEEDS_PUSH"
```

If the branch is ahead or has no upstream, call `AskUserQuestion` (in French):

- **Question**: *"Push de la branche maintenant ?"*
- **Header**: `Push`
- **Options**:
  1. *"Push maintenant"* (Recommended)
  2. *"Stop, je push moi-même"* — exit cleanly

If option 1, run (creating upstream if missing):

```bash
git push -u origin "$BRANCH"
```

If push fails (auth, conflict, force-push protection), surface the stderr verbatim and exit. Do NOT retry, do NOT auto-rebase.

If at any sub-step the user picks "Stop", exit with: *"Branche prête côté remote, relance `/PP_create-mr` quand tu veux."*

## Step 2 — Auto-detect PR fields

Run the following silently, in order. None of these values are committed to without user validation in Step 3 — they are *candidates*.

```bash
# TARGET_CANDIDATE was already computed in Step 0.

# Commits since target_candidate (used for Actions list and title)
COMMITS=$(git log --format='%s' "origin/$TARGET_CANDIDATE..HEAD")

# Extensions touched (used for Emplacement)
EXTENSIONS=$(git diff --name-only "origin/$TARGET_CANDIDATE...HEAD" \
          | grep '^app/Extensions/' | cut -d/ -f3 | sort -u)

# Title candidate: 1st commit subject, prefix and Tuleap tag stripped
TITLE_CANDIDATE=$(echo "$COMMITS" | head -1 \
                  | sed -E 's/^(Fnc|Bug|Ref|Erg|Test|Scm|Trad|Doc)\s?:\s*//' \
                  | sed -E 's/\s*#Tuleap-[0-9]+\s*$//')
```

### Template candidate heuristic

Determine the dominant commit prefix (most frequent among `Fnc:`, `Bug:`, etc. in `$COMMITS`). Then pick:

| Branch prefix | Dominant prefix | Target type | → Template file |
|---|---|---|---|
| `feature/*` | `Fnc:` | `master` or feature parent | `merge_feature.md` |
| `bugfix/*` | `Bug:` | `master` or feature parent | `merge_anomalie.md` |
| `bugfix/*` | any | `release/*` | `merge_freeze.md` |
| `feature/*` | any | `release/*` | `merge_freeze_feature.md` |

Ties or no detectable prefix → `merge_feature.md` as the safe default. The user will validate the choice in Step 3 anyway.

The full templates live at `.github/pull_request_templates/<template>.md`.

## Step 3 — Validate fields with the user (4 AskUserQuestion calls)

**Every field must be validated through `AskUserQuestion`**, even when auto-detection looks confident. Reason: real incidents — a wrongly-detected target branch (e.g. `@{u}` pointing at a stale parent) wastes a review priority and forces PR re-creation. Silent acceptance is unsafe.

### 3.1 — Call A: target + template + title (3 questions batched)

`AskUserQuestion` accepts up to 4 questions per call. Batch these three independent fields into one call. All three are single-select. Render the question texts and option labels in French.

| # | Question (FR) | Header | Options |
|---|---|---|---|
| Q1 | *"Quelle branche cible pour cette PR ?"* | `Target` | 1. `<TARGET_CANDIDATE>` (Recommended) · 2. `master` · 3. *"Autre"* (user types via "Other") |
| Q2 | *"Quel template GitHub utiliser ?"* | `Template` | 1. `<TEMPLATE_CANDIDATE>` (Recommended, with preview) · 2-4. the other 3 templates with previews |
| Q3 | *"Titre de la PR ?"* | `Titre` | 1. `<TITLE_CANDIDATE>` (Recommended) · 2. *"Saisie libre"* (Other) |

For Q1, if user picks "Autre" → plain-text follow-up: *"Quelle branche cible exactement ?"* — then validate via:
```bash
git rev-parse --verify "origin/<input>"
```
If the ref does not exist on the remote, re-ask.

For Q2, when rendering the options, include each template body as `preview` content so the user can compare them side-by-side. Use `Read` to fetch each template file.

For Q3, if user picks "Saisie libre" → plain-text follow-up: *"Saisis le titre de la PR :"* (no length validation; the user knows the project conventions).

Set:
```bash
TARGET="<user_choice_from_Q1>"
TEMPLATE="<user_choice_from_Q2>"
TITLE="<user_choice_from_Q3>"
```

### 3.2 — Call B: ticket type (single-select, conditional follow-up)

`AskUserQuestion`:

- **Question**: *"À quel ticket cette PR est-elle associée ?"*
- **Header**: `Ticket`
- **Options**:
  1. *"ERP"* — anomalie ou demande de dev tracée dans `erp.pixely.com`
  2. *"Tuleap"* — ticket Tuleap externe
  3. *"Aucun"* — pas de ticket associé

Conditional plain-text follow-ups:

**If ERP:**
1. *"Numéro ERP ?"* → `NUMERO`/`ID_ANO`/`ID_DEV`. Free-text, but expect digits only.
2. *"Nom du ticket ?"* → `NOM_ANO`/`NOM_DU_DEV`. Free-text.

**If Tuleap:**
1. *"Lien Tuleap complet ?"* → extract `aid=([0-9]+)` via regex; if no match, re-ask.
2. *"Nom du ticket ?"* → free-text.
3. The body substitution must use `#Tuleap-NNNN` (NEVER `#NNNN` alone — a bare `#NNNN` is interpreted by GitHub as a reference to issue #NNNN of the project, which has caused real incidents tagging unrelated developers' issues).

**If Aucun:**
- No follow-up.
- **Mutate the body**: delete the line matching `^- (Demande de dev|Anomalie):.*$`. The line is *removed entirely*, not left as a placeholder.

### 3.3 — Call C: body validation

Build the body in memory:

1. Read the chosen template file (`.github/pull_request_templates/$TEMPLATE`). All project template files are UTF-8 — standard `Read` / shell pipeline works fine.
2. Substitute placeholders with the user-provided values:
   - `NUMERO` → ERP id or empty (Aucun case removes the line so substitution is moot)
   - `[ID_ANO]` / `ID_ANO` / `[ID_DEV]` / `ID_DEV` → ticket id
   - `NOM_ANO` / `NOM_DU_DEV` → ticket name
   - `BRANCHE` → `$TARGET`
   - `{+BRANCHE+}` → `$TARGET` (the GitHub diff-mark notation used in `merge_feature.md` and `merge_anomalie.md` for highlighting the target branch in the rendered PR description)
3. If ticket type is "Aucun", delete the ticket line as described in 3.2.
4. **Auto-fill `Actions:` list**: replace the placeholder `1.` with one numbered bullet per commit. Strip the prefix (`Fnc:`/etc.) and any trailing `#Tuleap-NNNN` for readability.
5. **Auto-fill `Emplacement du dev:` line**: replace its empty value with the comma-separated `EXTENSIONS` list (e.g. `cinema-movie, gallery`). If `EXTENSIONS` is empty, leave the line empty.

Then `AskUserQuestion`:

- **Question**: *"Le body de la PR est-il correct ?"*
- **Header**: `Body`
- **Options**:
  1. *"OK — passer aux labels"* (Recommended)
  2. *"Modifier Emplacement"*
  3. *"Modifier Actions"*

If option 2 → plain text: *"Quel emplacement ? (extensions ou chemins, séparés par virgule)"* → replace the `Emplacement` line.
If option 3 → plain text: *"Liste des actions (une par ligne, sans numérotation) :"* → replace the `Actions:` block.

Loop on Call C until the user picks "OK".

### 3.4 — Call D: labels (multiSelect)

```bash
gh label list --per-page 100
```

If `gh label list` returns an empty result, skip Call D entirely (no `AskUserQuestion`), set `LABELS_CSV=""`, and proceed to Step 4.

Group the result by prefix (e.g. `Review Priority::*`, `Product::*`). `AskUserQuestion`:

- **Question**: *"Quels labels appliquer à la PR ?"*
- **Header**: `Labels`
- **Options**: one option per label, grouped visually by prefix when possible
- **multiSelect**: `true`

Set:
```bash
LABELS_CSV="<comma-separated user choice>"
```

If the user picks none, `LABELS_CSV=""` — pass an empty string (or skip `--label` entirely).

## Step 4 — Recap and final confirmation

Print a markdown recap (in French) showing every field that will be sent to GitHub:

```markdown
## Récapitulatif PR
- **Titre :** <TITLE>
- **Source branch :** <BRANCH>
- **Target branch :** <TARGET>
- **Labels :** <LABELS_CSV>
- **Squash :** oui  ·  **Suppression source au merge :** oui  ·  **Draft :** non

### Body
> <full body, blockquoted line-by-line>
```

Then `AskUserQuestion`:

- **Question**: *"Créer la PR maintenant ?"*
- **Header**: `Create`
- **Options**:
  1. *"Créer"* (Recommended)
  2. *"Annuler"* — exit with *"PR non créée. Relance `/PP_create-mr` pour réessayer."*

## Step 5 — Create the PR (no reviewer yet)

Assign `$BODY` via a single-quoted heredoc, then invoke `gh pr create`. The single-quoted `'EOF'` terminator prevents shell expansion of `$`, backticks, or quotes in the markdown body. Resolve the assignee inline via `gh api user`: the PR is owned by the currently authenticated GitHub user (project convention).

```bash
ASSIGNEE=$(gh api user --hostname "$HOST" | jq -r .login)

BODY=$(cat <<'EOF'
<full markdown body here>
EOF
)

gh pr create \
  --title "$TITLE" \
  --base "$TARGET" \
  --body "$BODY" \
  --label "$LABELS_CSV" \
  --assignee "$ASSIGNEE" \
  --yes \
  --hostname "$HOST"
```

**Flag notes:**
- `--base` (NOT `--target-branch`) — verified against `gh pr create --help`
- `--label` (`-l`) — verified
- `--assignee` — verified (used on `gh pr create`)
- `--yes` (`-y`) — verified — skips submission prompt
- **Never use `--fill` / `-f`** — it pushes the branch as a side effect, bypassing the Step 1 push gate
- **Milestone — never set**: the project convention forbids setting a milestone on PRs (`--milestone`). Do not pass that flag, do not ask the user about milestones, do not infer one from the branch name.
- **Commit message — never invent**: if Step 1.1 triggers (dirty workspace) and the user picks "Commit + push", the commit message comes from the user, not from the diff or branch name. The skill validates the regex but does not fix or autocomplete a malformed message — it asks again.

Capture the PR `number` from the returned URL (regex `pull/(\d+)$`):

```bash
PR_URL=$(gh pr create … 2>&1 | grep -oE 'https://[^ ]+/pull/[0-9]+')
IID=$(echo "$PR_URL" | grep -oE '[0-9]+$')
```

If `gh pr create` fails (target ref invalid, label unknown, network error), surface the stderr verbatim and exit. Do NOT retry, do NOT silently fall back to a different target. The user re-runs `/PP_create-mr` after fixing the underlying issue.

## Step 6 — Add reviewers (last step, separate gate)

Reviewers are added **after** the PR is created and labelled, **never** via `--reviewer` on `gh pr create`. Reason: every PR field update sends an email notification to assigned reviewers. Adding them last means at most one notification per reviewer instead of several as the description and labels are finalized.

Plain-text question (in French): *"Reviewers ? (CSV de noms ou usernames GitHub, vide = aucun)"*

For each comma-separated entry:
- If it looks like an exact username (lowercase, no spaces) → use as-is
- Otherwise → resolve via search:
  ```bash
  gh api "/users?search=<term>&per_page=100" --hostname "$HOST"
  ```
  Display the top matches and ask the user to pick one. Always pass `--per-page 100` — the GitHub API default of 20 silently truncates collections.

If the search returns zero matches, display *"Aucun utilisateur trouvé pour `<term>`."* and ask the user to either re-type a corrected name (re-ask), or skip this entry (treat as if not provided). Continue with the remaining entries.

When the resolved CSV is ready, `AskUserQuestion`:

- **Question**: *"Confirmer l'assignation des reviewers : `<resolved_csv>` ?"*
- **Header**: `Reviewers`
- **Options**:
  1. *"Confirmer"* (Recommended)
  2. *"Reporter (j'ajoute moi-même plus tard)"* — skip the assignment, PR stays without reviewer
  3. *"Annuler reviewers"* — same effect as Reporter

If "Confirmer":

```bash
gh pr edit "$IID" --reviewer "$RESOLVED_CSV" --hostname "$HOST"
```

This is a **creation flow** — the reviewer list before this call is empty.

### Lisa — AI bot reviewer (separate question)

Lisa is the project's AI bot reviewer (GitHub username `Lisa`, see also the `PP_review-lisa` skill). She is asked separately from human reviewers because (a) she is not a human and triggering her review is a deliberate intent, and (b) the question must be present even when no human reviewer was assigned.

After the human-reviewer step (whichever path was taken — Confirmer / Reporter / Annuler), call `AskUserQuestion`:

- **Question**: *"Ajouter Lisa (bot AI reviewer) sur cette PR ?"*
- **Header**: `Lisa`
- **Options**:
  1. *"Ajouter Lisa"* (Recommended)
  2. *"Pas cette fois"* — skip, PR stays without Lisa

If the user picks "Ajouter Lisa", append her to the existing reviewer list. **Append, never replace** — `gh pr edit --reviewer X` replaces the list silently (it does NOT append), so the call must include the human reviewers from the previous step alongside Lisa:

```bash
if [ -n "$RESOLVED_CSV" ]; then
  NEW_REVIEWERS="$RESOLVED_CSV,Lisa"
else
  NEW_REVIEWERS="Lisa"
fi
gh pr edit "$IID" --reviewer "$NEW_REVIEWERS" --hostname "$HOST"
```

If the previous step skipped (Reporter / Annuler), `$RESOLVED_CSV` is empty and Lisa is added alone.

### End — success message

End the skill with a one-line success message in French including the PR URL and the final reviewer list (humans + Lisa if added):

> *"PR créée : `<PR_URL>`. Reviewers : `<final_csv>`."*

## Implementation notes

### Pagination (always `--per-page 100`)

Every `gh api` call that lists collections must pass `--per-page 100` (or paginate via `&page=N`). The GitHub API default of 20 silently truncates collections, which on a busy project can hide reviewers, labels, or open PRs from the result. Affected endpoints in this skill: `/users?search=`, `gh pr list`, `gh label list`.

### Encoding

All project files, including PR templates, are UTF-8. Standard `Read` / shell pipeline works fine — no `iconv`, no `--encoding` flags needed.
