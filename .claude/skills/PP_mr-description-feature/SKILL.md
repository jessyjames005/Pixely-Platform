---
name: PP_mr-description-feature
description: "Use when the user invokes /PP_mr-description-feature to draft the description of a feature (development request) merge request from the current branch and diff, using the project's feature template. User-only — drafts text, never creates the MR."
disable-model-invocation: true
---

# Drafting a feature MR description

Produces a ready-to-paste MR description following the project's feature (development request) template. It only writes text: it never runs `gh pr create`, never commits, never pushes. To create the MR itself, use `/PP_create-mr`. For bug fixes, use `/PP_mr-description`.

The skill is written in English; the description it outputs is in **French** (the project's working language).

## Step 1 — Gather facts (read-only)

```bash
BRANCH=$(git branch --show-current)
git log --oneline master..HEAD
git diff --stat master...HEAD
git status --short
```

- The branch name usually carries the development request: `feature/<id>-<slug>` or `<id>-<slug>`. Extract the id for `[ID_DEV]` and turn the slug into a readable `NOM_DU_DEV`. The same number feeds `status_request_id=NUMERO` in the link; flag this assumption to the user.
- Ignore files that are not part of the feature (other extensions in the working tree, changes not meant to be committed). List only the extension(s) really concerned and tell the user which unrelated files you excluded.
- If the working tree holds uncommitted changes, read `git diff` for those files instead of the (empty) branch diff.

If the dev request number or title cannot be derived, ask the user with `AskUserQuestion` instead of guessing.

## Step 2 — Fill the template

Keep the structure, emoji shortcodes and heading levels exactly as below (the `:shortcode:` form is intentional: GitLab renders it).

```markdown
# :rocket: **Développement**

- Demande de dev: [[<ID_DEV>] : <NOM_DU_DEV>](https://erp.pixely.com/?m=tasking&tab=vw_ticket_requests&status_request_id=<NUMERO>)
- Emplacement du dev: <extension path and key files>

### :zap:️ **Actions:**

1. <how to test the nominal case>
2. <how to test the edge case>

### :heavy_check_mark: Checklist

- [ ] Cette MR comporte des tests.
- [ ] Cette MR a été testée par une personne du support / PM : (nom_de_la_personne)

### :wrench: **Branches:**

- **{+<branch name>+}**
```

Rules:
- **Emplacement du dev**: extension directory plus the 3-5 key files, in backticks.
- **Actions**: numbered, concrete UI steps a tester can follow, with the expected result of each. Cover the nominal case and the main edge or permission case when relevant.
- **Checklist**: tick `[x]` the "tests" line only if the diff really contains tests; otherwise drop the line and say so to the user. Leave the tester line unticked and never invent the tester's name — keep `(nom_de_la_personne)` unless the user provides it.
- **Branches**: the current branch name wrapped in `{+ +}` (GitLab green highlight), plus the target branch the same way if it is not `master`.
- No client names, developer names or patient names in the description.
- No reference to Claude or AI.

## Step 3 — Deliver

Print the description in one fenced `markdown` block so it can be copied as is. Right after it, list in 2-3 bullets what needs the user's check: the dev request number assumption, the tester name placeholder, and any unrelated modified files excluded.

