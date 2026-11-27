---
name: PP_po-test-guide
description: "Use when asked to give a Product Owner (PO) the detailed way to test a bug fix or feature on pixely platform (\"marche a suivre pour le PO\", \"comment tester\", \"detail du test\"). Reads the fix, finds the real UI path, and produces a French, non-technical test guide with prerequisites, data to create, screens to open and expected results."
---

# PO test guide

Goal: turn a fix (branch, commits or ticket) into a test guide a PO can follow without any technical knowledge. Analysis only: never modify code, never commit.

## 1. Identify the fix

- Branch name given by the user may not exist on the remote. Check with `git ls-remote --heads origin '*<ticket number>*'`, then search commits: `git log --all --oneline -i --grep='<ticket>'`.
- If nothing matches by branch, look for the ticket number in commit messages (cherry-picks land on `master` / `staging/*`). Say clearly which branch/commits were actually found.
- Read the diff of the fix commits. Read the tests added with it: they list the scenarios the developer considered.

## 2. Understand the cause

- Find the exact condition that triggered the bug and what the fix changes in behavior.
- Cross-check with the ticket context and any screenshot the user provides (patient type, stay type, extension, message displayed).
- Distinguish what is verified in code from what is assumed. Never present an assumption as a fact.

## 3. Find the real UI path

For every step the PO must do, resolve the real entry point instead of guessing:

- Extension controllers: `app/Extensions/<Extension>/Http/Controllers/*.php`, labels in `app/Extensions/<Extension>/lang/fr.php`.
- Message shown to the user: search its key in `app/Extensions/<Extension>/lang/fr.php`, then find who emits it (PHP controller/service) and who calls it (Vue component/JS).
- Configuration involved: configuration key, section and value that enable the behavior (all configuration files are UTF-8; no `.tpl`, no `locales/`, no legacy encoding).
- If a label or link between objects cannot be verified in code, say so explicitly and propose a fallback (ask the original developer).

## 4. Design the scenarios

Always cover, in this order:

1. The reported case (nominal, must now work). Use the IPP / stay of the ticket when given.
2. The negative control (the check must still block when it should).
3. The edge cases the fix explicitly handles (cancelled items, mutation, several objects, etc.).
4. Any behavior that became stricter or looser than before: flag it as a business decision for the PO to confirm.

## 5. Output format (always in French, no technical terms)

Write for the PO: no class names, no config keys in code form, no commit hashes in the PO message. Use business vocabulary and the labels as shown on screen.

Structure:

- **Prerequisites**: configuration to set (extension > section > setting > value), rights, data needed.
- **Data to create**: patient, stay type, linked objects, with the one detail that makes the bug reproducible.
- **Steps**: numbered, each with extension, tab, button as displayed.
- **Expected results**: table `Scenario | Expected result`, including the case that must still be blocked.
- **Point to confirm**: any behavior change needing a business decision, phrased as a question.

Two versions when useful:

- A detailed guide (full steps and screens).
- A short message to send as is (greeting, cause in one sentence, scenario list, one question, thanks).

## 6. Honesty rules

- State what was not run (tests, reproduction on a database) and what is deduced from code only.
- Mark UI labels not verified in the interface as such.
- Mention the branch discrepancy when the requested branch does not exist.
- Do not commit, push or create branches. Analysis requests stay analysis.

