---
name: PP_analyze-mr-staging
description: "Use when the user invokes /PP_analyze-mr-staging to analyze a branch and draft the MR description for a staging/YYYY_MM target (bugfix during freeze). Reads the diff against the staging branch and produces a copy-pasteable French description following the staging bugfix template. User-only - drafts text, never commits, pushes or creates the MR (see PP_create-mr for that)."
disable-model-invocation: true
---

# Analyze a branch and draft its staging MR description

Produces the description of a bugfix MR targeting `staging/YYYY_MM` (instance in freeze), grounded in the real diff. Output is a French markdown block, ready to paste. Read-only: no commit, no push, no `gh pr create`.

## Step 1 - Resolve the target and the range

```bash
BRANCH=$(git branch --show-current)
git fetch origin --quiet
```

- Target = the `staging/YYYY_MM` branch the user names; otherwise infer from the branch name suffix (`...-staging_2026_11` -> `staging/2026_11`) and confirm with `AskUserQuestion` if unsure. Never default to `master`.
- Range: `origin/<target>..HEAD`.

```bash
git log --no-merges --format='%h %s' origin/<target>..HEAD
git diff --stat origin/<target>...HEAD
```

If the range is empty, stop and say so.

## Step 2 - Collect facts (verify, never guess)

- **Ticket**: from branch name / commit messages (`#Tuleap-NNNNNN`). Title: ask the user if not in the commits. Link format: `https://tuleap-web.swmcloud.net/plugins/tracker/?aid=NNNNNN`.
- **Nature of the change**: plain fix, backport of a master fix, or **revert** (commit subjects starting with `Revert`, or a diff that is the inverse of commits already on master). For a revert, list the reverted commit hashes and the evolution tickets involved.
- **Extensions touched**: first path segment under `app/Extensions/`, plus other top-level areas (`src/`, `javascript/`, `lang`).
- **Setup / data impact**: any `CSetup*` change (revision numbers, `mod_version`), SQL, config. If a `CSetup` is deliberately left untouched or kept ahead, say why only if the diff or user states it - otherwise flag it as a question.
- **Files that look unrelated** to the ticket in the diff: list them as a warning to the user, do not hide them.
- All project files are UTF-8 (no `.tpl`, no legacy `locales/`, no ISO-8859-1). Search directly with the project's tools; do not rely on encoding-specific flags.

## Step 3 - Output the description

Fill the template below. Everything the diff does not prove stays as an explicit placeholder or a question - never invent an instance name, a tester or a rationale.

```markdown
Merge request de bugfix pour la branche staging/<YYYY_MM>

* Cette MR corrige un problème sur l'instance pour laquelle le freeze est en cours.

Informations sur la Merge Request

1. Lien vers l'anomalie : [#Tuleap-<id> : <titre>](https://tuleap-web.swmcloud.net/plugins/tracker/?aid=<id>)
2. Instance concernée : <toutes | nom de l'instance> — version de <mois> (staging/<YYYY_MM>)
3. Résumé de l'anomalie : <symptôme observable, 1-2 phrases>
4. Description de la correction : <ce qui change et pourquoi ; pour un revert : hashes revertés ; mentionner ce qui est volontairement conservé et la raison>
5. Fonctionnalités impactées : <fonctionnalités + extensions touchées> — <ce qui est volontairement conservé, le cas échéant>

Checklist

* J'ai vérifié et pris en compte les potentiels effets de bords sur d'autres fonctionnalités.
* Cette MR a été testée par une personne du support / PM : (nom de la personne)
```

Rules:
- The two checklist lines are copied as-is; the tester name stays `(nom de la personne)` - it is the user's to fill.
- No client names, developer names or patient names.
- Item 4 is written in functional French, short; no code dump.

## Step 4 - Report below the block

After the block, add a short "Points à vérifier" list (French) with only what is real: unrelated files in the diff, `CSetup` / `mod_version` changes, missing ticket title or instance, anything not grounded in the diff.

Then stop. Creating the MR is a separate step (`/PP_create-mr`) and needs the user's explicit go.

