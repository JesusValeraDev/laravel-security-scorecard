---
name: loop-recipes
description: Ready-made /goal, /loop, and /schedule recipes for Scorecard with deterministic stop conditions and right-sized intervals
---

# Loop Recipes

Loops are agents repeating cycles of work until a stop condition is met. Every loop
in this repo must have a **deterministic** stop condition (a measurable gate plus a
max-attempt cap) — "looks done" is not a stop condition. Verification gates come from
the `verify` skill.

## Picking the primitive

| Situation | Primitive |
|-----------|-----------|
| One-off task, you're watching | Plain prompt (turn-based) |
| Measurable target, bounded attempts | `/goal … , stop after N tries` |
| Recurring while your machine is on | `/loop <interval> <prompt>` |
| Recurring unattended (cloud) | `/schedule` |

## Goal recipes (deterministic target + cap)

```
/goal make test green, stop after 5 tries
/goal make lint reports zero errors, stop after 3 tries
/goal node .claude/skills/i18n-parity/check.mjs exits 0 for all 5 locales, stop after 3 tries
/goal PHPStan passes at level max on modules/<Module>, stop after 4 tries
```

Always phrase the goal as a command exit code or a number — never "improve" or
"clean up" without a measurable finish line.

## Time-based recipes (interval ≈ how often the input actually changes)

| Task | Recipe | Why this interval |
|------|--------|-------------------|
| Babysit an open PR | `/loop 5m check my PR, address review comments, fix failing CI` | CI cycles take minutes |
| Drain issue backlog | `/loop 30m /gh-issues --limit 1` | Issues arrive slowly; each pass is expensive |
| i18n drift audit | `/schedule` weekly: run i18n-parity, open an issue if red | Locale files change a few times a week |
| Docs drift audit | `/schedule` weekly: `/docs-audit`, open an issue per gap | Docs rot slowly |
| Changelog hygiene | `/schedule` weekly before release: `/update-changelog` | Matches release cadence |

Don't poll faster than the watched thing changes — a 1m loop over a weekly-changing
input burns tokens for nothing.

## Pilot before scaling

Before letting a recurring loop run unattended:

1. Run one iteration manually and read the full transcript.
2. Check where it stalled, overreached, or wasted turns; tighten the prompt or add a
   script for any step it "reasoned through" deterministically.
3. Only then schedule it — and start with `--limit 1` / `--dry-run` variants where
   the skill supports them (e.g. `gh-issues`).

## Token discipline

- Prefer a script over model reasoning for any deterministic step (see `i18n-parity`
  and `rename-sweep` for the pattern).
- Give every loop explicit success criteria up front — vague goals multiply turns.
- Review consumption with `/usage` after the first scheduled runs and adjust
  intervals before adding more loops.
