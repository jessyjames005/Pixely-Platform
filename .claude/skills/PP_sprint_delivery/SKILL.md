---
name: PP_sprint_delivery
description: Run Pixely Platform work as validated numbered sprint lots; use for sprint planning, lot implementation handoffs, commit-message proposals, roadmap/changelog updates, and final pull request descriptions.
---

# Pixely Sprint Delivery

## When to use

- Starting or continuing a named sprint such as U12.
- Completing a numbered lot such as U12.3.
- Preparing the lot commit message or sprint-close pull request content.
- Updating sprint progress in the roadmap and changelog.

## Workflow

1. Read `docs/development/SPRINT_WORKFLOW.md`, the canonical project rules in `AGENTS.md`, and the relevant domain skill/rules.
2. Confirm sprint goal, lot boundaries, acceptance criteria, order, and dependencies with the user. Do not begin a later lot before the current lot is validated.
3. Audit the existing code, tests, and documentation for the current lot. Reuse existing behavior and report any scope ambiguity before changing it.
4. Implement only the current lot; include focused automated tests and directly related technical documentation.
5. Run the smallest relevant test suite and required static/style checks. Inspect the diff and report validation truthfully, including failures and skipped checks.
6. Summarize the lot, list changes and remaining limitations, and propose one Conventional Commit message that includes the lot identifier.
7. Wait for explicit authorization before committing. A request for a commit message is not commit authorization. Wait for the user's validation before starting the next lot.
8. At sprint close, run the final regression checks, reconcile `ROADMAP.md` and `CHANGELOG.md`, and draft every section in `.github/PULL_REQUEST_TEMPLATE.md`.
9. Include context, scope by lot, technical surfaces (routes, commands, views, data), security/recovery behavior, exact validation results, deployment notes, user-facing release notes, and known limitations.
10. Do not commit, push, or create a pull request without separate explicit authorization.

## Commit message format

Use the repository's Conventional Commit format and domain scope. Keep each commit scoped to one completed lot. Example:

```text
feat(extensions): add U12.4 approved release installation
```

See `docs/development/SPRINT_WORKFLOW.md` for the complete acceptance and PR checklist.
