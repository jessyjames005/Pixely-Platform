---
description: Run the frontend CI verification suite (ESLint, Vitest, typecheck, build) in Docker
---

Run the **frontend** CI verification suite exactly as CI would, using the Node
Docker container (never run Node locally). Execute each command and report the
result.

Commands to run, in order:

1. `docker exec pixely_node npm run lint`
2. `docker exec pixely_node npm test`
3. `docker exec pixely_node npm run typecheck`
4. `docker exec pixely_node npm run build`

Rules:
- Run them sequentially; do not stop on the first failure — run all four so the
  full picture is reported.
- Do **not** auto-fix or modify code unless the user asks. If a step fails,
  report the exact failing files, line numbers and error messages.
- Note: `npm run typecheck` is wired with `|| true` in package.json, so it never
  fails the gate — still run it and surface any reported type errors.
- The gate is **green** only if all steps pass with **0 errors** (ESLint warnings
  are tolerated but report them).
- Finish with a concise PASS/FAIL table (one row per command) and, if green, a
  one-line "Frontend CI: GREEN" summary.