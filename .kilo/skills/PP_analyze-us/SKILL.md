---
name: PP_analyze-us
description: "Use when asked to \"faire l'analyse\" of a US (user story) or ticket before implementation on pixely platform. Produces a short, grounded technical analysis (not code) — backend-focused by default, verified against real files and existing branches, delivered as a copy-pasteable French markdown block."
---

# Analyzing a US/ticket before implementation

This skill produces a **document, not code**. It runs before any `backend-dev`/`frontend-dev` dispatch, and never writes implementation files itself.

## Default scope

Backend-focused by default — this user works backend/frontend separately and does not want a frontend section unless they ask for it (see memory `user-scope-backend-only`). Concretely:

- Default request ("analyse cette US") -> backend only.
- "pas trop pour le frontend" / "léger côté front" -> backend detailed, 1-2 bullets max for frontend.
- "séparer les devs backend et frontend" -> two explicit sections, `### Développements Backend` / `### Développements Frontend`, instead of the default template below.

## Output format (default case)

Always end the turn with the result wrapped in a fenced ```markdown block (not rendered prose) so it can be pasted as-is into Tuleap/Confluence/an MR description. Written in French.

```markdown
## Analyse back

### Contexte

### Technique

### Questions métier

**Estimation : N p** (durée)
```

- `Contexte`: 2-4 sentences restating the need/current state. No more.
- `Technique`: concise bullets, each backed by a `[file:line](path)` link. No long prose, no pasted code blocks. When it spans distinct concerns (several extensions, "coeur du changement" vs "impacts/ecarts" vs "hors perimetre", backend vs frontend), split into labeled `####` sub-sections instead of one flat list.
- `Questions métier`: only real points blocking a business/design decision (ambiguous AC, missing mockup detail, conflicting existing behavior). Write "Aucune" rather than inventing a question to fill the section.
- The **last line of the block** is always the estimate, in French: `**Estimation : N p** (<duration>)`. Scale: Fibonacci points, 1 p = 1 to 2 h, 2 p = half a day, 3 p = 1 to 2 days, 5 p = 4 to 5 days, 8 p = one sprint (8 to 10 days). Never exceed 8 p: if the work is larger, write "Estimation : > 8 p, US à découper" and suggest the split in one bullet of `Technique`. The estimate covers the scope analysed (backend by default).

When redoing the analysis after feedback, output the **full corrected document again**, not a diff — but say in one short sentence what changed and why before the block.

## Grounding rules

1. **Never assert a route/class/enum/line from memory:** read or grep the actual file before writing it into the analysis as fact. An Explore agent's report can miss things — verify the one or two facts the whole recommendation hinges on.
2. **Read directly instead of spawning Explore** when the files are already known (from the ticket, screenshots, git status, or earlier in the conversation) — reserve Explore for genuinely unknown territory (which module owns X, does an async/push mechanism already exist, how is a given business state characterized).
3. **Search files directly** with the project's tools. All project files are UTF-8 (no `.tpl`, no legacy `locales/`, no ISO-8859-1). Use standard file search; do not rely on encoding-specific flags.
4. **Spot-check the load-bearing claims yourself** before trusting them — re-grep the one or two facts the whole recommendation hinges on (an enum's real values, whether a function is actually wired to a route, whether a flagged risk still applies after a design pivot).
5. **Reuse existing code aggressively.** Before proposing a new route, service, or state, check whether an existing single-item action, enum, or JS function already does most of the job — most "gaps" turn out to be "wire this existing piece", not new design. State clearly what is reused vs genuinely new.

## Checking for parallel/prior work

Before finalizing, check whether a colleague has already started or explored this exact feature:

```bash
git log --all --author="<name>" --oneline | head -50
git branch -a --contains <relevant-commit>
git diff --stat HEAD <candidate-branch> -- <relevant paths>
```

A branch that only touches adjacent concerns (filters, styling) is not prior art for the feature itself — say so plainly. A commit that reads like a stub (`dump(...)`, hardcoded `sleep(random_int(...))`, placeholder return values) is a spike/PoC, not production-ready: usable as a precedent for "this pattern works", never as a dependency to build directly on without flagging the gap (is it merged? is there an operational process/worker behind it in prod?).

## Architecture forks

When two or more designs have real trade-offs (push vs polling, sync vs async, new infra vs reuse of an existing mechanism), use `AskUserQuestion` with a recommended option first — never silently pick one and present it as the only path. Expect the answer to overturn a prior recommendation entirely; redo the affected sections rather than patching around the old design.

## Scenario coverage check

When asked "tous les scénarios sont pris en compte ?" (or equivalent), go through each Acceptance Criteria scenario **one by one** against the current analysis text and state plainly which are covered and which are not. Do not claim full coverage without having just re-checked each scenario individually — a scenario that depends on a sub-topic deliberately deferred to another task (see below) is *not* covered, say so.

## Decoupling sub-topics

When a rule or scenario clearly deserves its own investigation (enough nuance to be its own ticket), say so and defer it explicitly rather than solving it inline — but keep tracking any concrete implication it creates for the current US (e.g. a field that would stay unpopulated because of the deferred topic) as an open point in `Technique`/`Questions métier`.

