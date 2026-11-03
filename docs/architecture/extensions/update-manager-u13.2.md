# Extension Update Dry-Run Batches — U13.2

U13.2 connects `pixely:update --dry-run` to
`ExtensionUpdatePlanner::planBatch()`. The command validates the selected local
ZIP packages as a set, then displays their global deterministic dependency-first
order. A candidate dependency's minimum version may be satisfied by another
candidate in that same selected set, even when package filenames sort in the
opposite order.

The existing `--extension` option filters the set before batch planning. If the
batch cannot be planned, the command displays the planner's blocker and records
the affected packages as failed report items. Dry-run reports also include an
`execution_order` array; it is empty when global planning is blocked. Existing
report fields and schema version remain unchanged.

Dry-run only validates and plans packages. It does not invoke the installer or
change installed extension versions or files. Real apply behavior and execution
ordering are unchanged in this lot.
