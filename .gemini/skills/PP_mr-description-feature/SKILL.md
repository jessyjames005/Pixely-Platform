---
name: PP_mr-description-feature
description: "Use when the user invokes /PP_mr-description-feature to draft the description of a feature pull request from the current branch and diff, using the project's feature template. User-only — drafts text, never creates the PR."
disable-model-invocation: true
---

# Drafting a feature PR description

Produces a ready-to-paste PR description following the project's feature template. It only writes text: it never runs `gh pr create`, never commits, never pushes. To create the PR itself, use `/PP_create-mr`. For bug fixes, use `/PP_mr-description`.

The skill is written in English; the description it outputs is in **French** (the project's working language).

## Step 1 — Gather facts (read-only)

```bash
BRANCH=$(git branch --show-current)
git log --oneline develop..HEAD
git diff --stat develop...HEAD
git status --short
```

- Ignore files that are not part of the feature (other extensions in the working tree, changes not meant to be committed). List only the extension(s) really concerned and tell the user which unrelated files you excluded.
- If the working tree holds uncommitted changes, read `git diff` for those files instead of the (empty) branch diff.

## Step 2 — Fill the template

Keep the structure, emoji shortcodes and heading levels exactly as below.

```markdown
# :rocket: **Développement**

- Emplacement du dev: <extension path and key files>

### :zap:️ **Actions:**

1. <how to test the nominal case>
2. <how to test the edge case>

### :heavy_check_mark: Checklist

- [ ] Cette PR comporte des tests.

### :wrench: **Branches:**

- **{+<branch name>+}**
```

Rules:
- **Emplacement du dev**: extension directory plus the 3-5 key files, in backticks.
- **Actions**: numbered, concrete UI steps a tester can follow, with the expected result of each. Cover the nominal case and the main edge or permission case when relevant.
- **Checklist**: tick `[x]` the "tests" line only if the diff really contains tests; otherwise drop the line and say so to the user.
- **Branches**: the current branch name wrapped in `{+ +}` green highlight, plus the target branch the same way if it is not `develop`.
- No client names, developer names or patient names in the description.
- No reference to Claude or AI.

## Step 3 — Deliver

Print the description in one fenced `markdown` block so it can be copied as is. Right after it, list in 2-3 bullets what needs the user's check and any unrelated modified files excluded.
