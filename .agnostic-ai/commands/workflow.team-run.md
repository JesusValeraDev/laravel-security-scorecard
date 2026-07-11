---
description: Coordinate the repo's parallel team-run stage for analysis, implementation, or attack work using the OMC/Claude multi-agent capabilities available in the current environment.
argument-hint: "<analysis|implementation|attack> [scope-hint]"
allowed-tools: Read, Grep, Glob, Bash, Task
disable-model-invocation: true
---

Run when the workflow needs bounded parallel work rather than a single in-process pass.

Inputs: the stage in `$ARGUMENTS` (`analysis`, `implementation`, or `attack`); an optional scope hint after the stage; the repo's Spec Kit artifacts and workflow outputs.

Required workflow:
1. Validate input. First token is the stage, the rest is the scope hint. If the stage is missing or unsupported, stop and explain the accepted values.
2. Resolve the active scope from the scope hint, changed files, and current Spec Kit artifacts.
3. Use the OMC/Claude parallel agent capabilities in the current environment — prefer the installed agent/task orchestration surface, don't assume a separate external team binary. If parallel execution isn't available, stop and explain the blocker rather than faking a team run.
4. Launch bounded parallel work for the stage:
   - `analysis` stage:
     - worker 1 traces impacted files, entrypoints, and interfaces: Grep/Read `modules/{Module}/{Domain,Application,Infrastructure}` and `resources/frontend/src/` to map backend handlers, routes (`php artisan route:list`), and frontend stores/components.
     - worker 2 maps hidden dependencies, risks, and architectural constraints: read `database/migrations/` for schema, cross-check module boundaries in `modules/`.
     - worker 3 synthesizes what must change in the next spec or plan update
   - `implementation` stage:
     - worker 1 owns approved task set A
     - worker 2 owns approved task set B
     - worker 3 handles integration checks, verification support, and fix follow-through
   - `attack` stage:
     - worker 1 attacks concurrency, retries, races, and idempotency
     - worker 2 attacks traffic, timeout, backpressure, and failure-mode behavior
     - worker 3 normalizes findings, repro quality, and remediation guidance
5. Wait for all parallel work to reach a terminal state before continuing.
6. Surface execution evidence explicitly: which roles/lanes ran, the scope each covered, terminal status for each.
7. Stage-specific outputs:
   - `analysis`: impacted files, hidden dependencies, spec/plan implications
   - `implementation`: completed work, verification status, residual blockers
   - `attack`: confirm or reference `adversarial-findings.md`, then summarize confirmed risks and what to fix next
8. Return control with a concise next-step handoff.

Exit criteria:
- Parallel agent execution was actually used
- Execution evidence was surfaced
- All worker lanes reached terminal states before handoff
- No silent downgrade to a fake team run occurred
