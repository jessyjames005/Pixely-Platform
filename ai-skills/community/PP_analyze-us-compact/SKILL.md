---
name: PP_analyze-us-compact
description: "Use when asked for a short, simplified analysis (\"analyse compacte\", \"version courte\", \"analyse rapide\") of a US or ticket before implementation on pixely platform. Compact variant of PP_analyze-us - same grounding, much shorter French markdown block, backend only, no code."
---

# Compact US/ticket analysis

Produces a **document, not code**, before any `backend-dev`/`frontend-dev` dispatch. Never writes implementation files.
Use `PP_analyze-us` instead when the user wants the full version (sub-sections, scenario coverage, architecture forks).

## Scope

Backend only, unless the user explicitly asks for the frontend.

## Output format

End the turn with a single fenced ```markdown block, in French, ready to paste into Tuleap/Confluence/an MR:

```markdown
## Analyse back

### Contexte

### Technique

### Questions métier

**Estimation : N p** (durée)
```

- `Contexte`: 2-3 sentences. Need and current state, nothing else.
- `Technique`: 5-8 flat bullets max, no `####` sub-sections, no pasted code. Each fact carries a `[file:line](path)` link. Cover only:
  1. where the problem/change lives,
  2. what already exists and is reused (field, route, enum, service),
  3. the proposed change (one line if possible) and one alternative only if real trade-offs exist,
  4. points to verify on the platform,
  5. tests: existing fixtures to reuse.
- `Questions métier`: only real blockers for a business/design decision. Write "Aucune" otherwise.
- The **last line of the block** is always the estimate, in French: `**Estimation : N p** (<duration>)`. Scale: Fibonacci points, 1 p = 1 to 2 h, 2 p = half a day, 3 p = 1 to 2 days, 5 p = 4 to 5 days, 8 p = one sprint (8 to 10 days). Never exceed 8 p: if the work is larger, write "Estimation : > 8 p, US à découper" and suggest the split in one bullet of `Technique`. The estimate covers the scope analysed (backend by default).

Anything beyond that (out of scope, deferred sub-topics, long impact lists) is dropped, or reduced to one bullet.

## Grounding (kept from the full skill)

1. Never state a route/class/enum/line from memory: read or grep the real file first.
2. Read known files directly; use Explore only for unknown territory.
3. Search project files directly. All project files are UTF-8 (no `.tpl`, no legacy `locales/`, no ISO-8859-1). Do not rely on encoding-specific flags.
4. Re-check the one or two facts the recommendation hinges on.
5. Reuse existing code first: say what is reused vs genuinely new.
6. Quick prior-work check: `git log --all --oneline --grep="<keyword>" | head`. Mention a colleague's branch only if it covers the feature itself.

## Rules

- If two designs have real trade-offs, ask with `AskUserQuestion` (recommended option first) before writing the block.
- After feedback, output the full corrected block, preceded by one sentence saying what changed.
- Never commit or push.

