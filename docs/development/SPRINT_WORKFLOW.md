# Sprint and Lot Delivery Workflow

## Planning model

- A **sprint** groups one cohesive outcome and has a stable identifier, such as `U12`.
- A **lot** is a numbered, independently reviewable unit within that sprint, such as `U12.1`.
- The sprint plan identifies the goal, lot order, acceptance criteria, dependencies, and deferred work before implementation begins.
- Audit the existing implementation and tests before each lot. Reuse existing architecture and record any scope changes with the user before proceeding.

## Lot gate

Complete these steps for one lot before moving on:

1. Confirm the lot's acceptance criteria and dependencies.
2. Inspect relevant code, tests, and documentation; summarize existing behavior and the intended change.
3. Implement only the agreed lot scope, including focused automated tests and directly related documentation.
4. Run the smallest relevant tests and checks; fix regressions caused by the lot.
5. Review the diff and report changed files, behavior, validation results, limitations, and any remaining risk.
6. Propose exactly one Conventional Commit message for the lot, for example:

   ```text
   feat(extensions): add U12.4 approved release installation
   ```

7. Wait for explicit user approval before creating that commit. Never infer commit authorization from a request to provide a commit message.
8. Present the lot result and wait for user validation before beginning the next lot.

Keep a lot's commit limited to its intended logical change. Do not mix unfinished future lots into a completed lot's commit. If validation fails or scope changes, explain it and resolve or obtain direction before declaring the lot complete.

## Sprint close

After every lot is validated:

1. Run the agreed sprint-level regression suite and required static/style checks.
2. Review the branch diff and worktree; confirm no unrelated changes, generated artifacts, secrets, or temporary files are included.
3. Update `ROADMAP.md` to reflect completed and deferred work, and add user-relevant unreleased changes to `CHANGELOG.md`.
4. Verify the technical documentation describes the shipped behavior accurately. Do not claim a UI, rollback, migration, or security guarantee that the implementation does not provide.
5. Prepare a complete PR description using [the repository PR template](../../.github/PULL_REQUEST_TEMPLATE.md).
6. Report the full PR draft and a sprint-level summary. Do not create the PR, push, or commit sprint-close documentation without explicit user authorization.

## Pull request content

The final sprint description must include:

- **Context and objective:** why the sprint exists and the user or platform need.
- **Scope by lot:** each lot identifier and its result.
- **Technical implementation:** affected architecture, services, routes and HTTP methods, API request/response contract, Artisan commands, administration views, permissions, migrations, and persistence as applicable.
- **Security and data behavior:** validation, trust boundaries, approval gates, backup/rollback limitations, and audit behavior.
- **Validation:** exact test and quality-check commands with pass/fail results; call out skipped or unavailable checks.
- **Deployment or upgrade notes:** migrations, configuration, commands, backups, compatibility, or operational steps, if any.
- **User-facing release notes:** concise behavior changes in language suitable for administrators and end users; distinguish visible changes from internal work.
- **Known limitations and deferred work:** explicitly list anything not delivered.

Only list technical surfaces that actually changed. When a surface such as routes or views is unchanged, state that when it helps prevent ambiguity.
