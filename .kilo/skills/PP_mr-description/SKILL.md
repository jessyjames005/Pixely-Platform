---
name: PP_mr-description
description: "Use when the user invokes /PP_mr-description to draft the description of a bug-fix (anomalie) merge request from the current branch and diff, using the project's anomaly template. User-only — drafts text, never creates the MR."
disable-model-invocation: true
---

# Drafting an anomaly MR description

Produces a ready-to-paste MR description following the project's anomaly template. It only writes text: it never runs `gh pr create`, never commits, never pushes. To create the MR itself, use `/PP_create-mr`.

The skill is written in English; the description it outputs is in **French** (the project's working language).

## Step 1 — Gather facts (read-only)

```bash
BRANCH=$(git branch --show-current)
git log --oneline master..HEAD
git diff --stat master...HEAD
git status --short
```

- Branch name usually carries the ticket: `<id>-anom-<number>-<slug>`. Extract `ANOM-<number>` and turn the slug into a readable title. The `<number>` after `anom-` feeds the `COXAnomalie-<number>` link; the leading number may be a different ticket id, so flag this assumption to the user.
- Ignore files that are not part of the fix (other extensions in the working tree, changes not meant to be committed). List only the extension(s) really concerned and tell the user which unrelated files you excluded.
- If the working tree holds uncommitted changes, read `git diff` for those files instead of the (empty) branch diff.

If the ticket number or title cannot be derived, ask the user with `AskUserQuestion` instead of guessing.

## Step 2 — Fill the template

Keep the structure, emojis and headings exactly as below.

```markdown
# 🚧 Anomalie - Bug

* Anomalie: [[ANOM-<number>] : <Title>](https://erp.pixely.com/?m=oxSupport&tab=vw_demandes_assistance&view_object_guid=COXAnomalie-<number>)
* Emplacement du dev: <extension path and key files>
* Contexte : <cause in 2-3 sentences, then what the MR changes and what it does not fix>

# ⚡️ Actions:

1. <how to reproduce the nominal case>
2. <how to reproduce the fixed case>

# ✔️ Checklist

* Cette MR comporte des tests.
* Cette MR a été testée par une personne du support / PM : (nom_de_la_personne)

# 🔧 Branches:

* **{+<branch name>+}**
```

Rules:
- **Headings**: the four section titles (Anomalie - Bug, Actions, Checklist, Branches) are H1 (`# `).
- **Emplacement du dev**: extension directory plus the 3-5 key files, in backticks.
- **Contexte**: a bullet in the same list as "Anomalie" and "Emplacement du dev", right after "Emplacement du dev", before "Actions". Optional but recommended; state the root cause, the change, and any known limit (cause outside the code, config to fix, etc.).
- **Actions**: numbered, concrete UI steps a tester can follow, with the expected result of each. Cover the nominal and the failure/edge case when relevant.
- **Checklist**: keep the "tests" line only if the diff really contains tests; otherwise drop it and say so to the user. Never invent the tester's name — leave `(nom_de_la_personne)` unless the user provides it.
- **Branches**: the current branch name wrapped in `**{+ +}**` (GitLab green highlight), plus the target branch the same way if it is not `master`.
- No client names, developer names or patient names in the description.
- No reference to Claude or AI.

## Step 3 — Deliver

Print the description in one fenced `markdown` block so it can be copied as is. Right after it, list in 2-3 bullets what needs the user's check: the ticket number assumption, the tester name placeholder, and any unrelated modified files excluded.

