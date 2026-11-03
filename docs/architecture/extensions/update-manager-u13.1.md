# Extension Update Planning — U13.1

U13.1 adds batch planning for the complete set of locally available extension
update candidates. `ExtensionUpdatePlanner::planBatch()` receives candidate
manifests, their minimum dependency versions and conflicts, plus the installed
versions and installed dependency graph. It returns the existing per-extension
plan data in deterministic dependency-first order.

For each declared dependency, planning succeeds when the installed version
already meets the minimum or when an included update candidate's version meets
it. Missing dependencies and requirements below the minimum in both installed
and candidate versions are rejected. Candidate conflicts, candidates for
extensions that are not installed, duplicate candidates, non-increasing update
versions, and dependency cycles are also rejected.

The planner is read-only. It does not download, install, upgrade, or remove
dependencies, and this lot does not change `pixely:update` command behavior or
update confirmation and execution behavior.
