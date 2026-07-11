---
name: code-to-spec
description: Reverse-engineers existing behavior into the repo's canonical Spec Kit spec.md. Use when the user wants to document current behavior instead of changing it.
---

Use this skill for requests like `document existing invoice retry flow`, `reverse spec the webhook handler`, or `document current behavior of order status transitions`.

Inputs: a brownfield capability description in `$ARGUMENTS`; the Spec Kit bootstrap and standard spec template; existing code, tests, configs, docs; optional path/symbol/subsystem hints.

Rules:
- Reverse-spec produces the canonical Spec Kit artifact, not a sidecar document track.
- Output must be the standard `spec.md` in the active feature directory, using the repo's normal Spec Kit structure.
- Only template family is `.specify/templates/spec-template.md`; never create a parallel code-to-spec template.
- Never invent APIs, events, DB tables/columns, topics, queue names, integrations, or operational guarantees.
- Ground every factual claim in repo evidence (path+symbol, config key, or test reference). Unprovable claims go to `Unknowns & Questions` or are labeled explicit assumptions.
- Keep confidence language cautious: `observed`, `implied by`, `appears to`, `not observable in code`.
- For broad scopes (3+ files, 2+ subsystems, end-to-end tracing), run `/workflow.team-run analysis <scope>` before synthesizing.
- CLI is bootstrap-only; this skill runs inside the interactive repo-local workflow, not shell runtime code.

Workflow:
1. Validate bootstrap:
   - `.specify/` exists
   - `.specify/memory/constitution.md` exists (and use `.specify/memory/constitution.effective.md` when present)
   - `.claude/commands/workflow.team-run.md` exists
2. Resolve or create the active Spec Kit feature directory:
   - Reuse the current feature directory when one already exists for the target scope.
   - Otherwise create one with the repo's normal Spec Kit feature bootstrap so the output lands in the standard `spec.md` path.
   - Load `.specify/templates/spec-template.md` before writing the final artifact.
3. Scope discovery:
   - Identify entrypoints, domain modules, integration boundaries, and likely evidence sources.
   - When a `codebase-exploration` skill is available, use its Lens tools for structured discovery: `list_routes` for entrypoints, `model_explorer` for domain models, `service_map` for cross-service boundaries.
   - Build an evidence table before drafting conclusions.
4. Parallel brownfield analysis gate:
   - If the scope is broad, run `/workflow.team-run analysis <scope>` and use its evidence in addition to direct inspection.
   - Capture impacted files, hidden dependencies, interface boundaries, and spec implications before synthesis.
5. Current-behavior extraction:
   - Describe current behavior strictly from code, tests, and docs.
   - Extract implied invariants and state transitions when present.
   - If events or background processing exist, describe delivery assumptions, idempotency, duplicate handling, ordering, retries, failure handling, and backward-compatibility behavior.
   - If any of those are not observable in code, say so explicitly.
6. Standard spec synthesis:
   - Map the observed behavior into the repo's normal `spec.md` sections.
   - Use `.specify/templates/spec-template.md` as the sole template source.
   - Keep scope, requirements, edge cases, constraints, observability, and open questions grounded in evidence.
   - When the feature already has a partially correct `spec.md`, update it in place rather than duplicating it.
7. Quality loop:
   - Run the self-check rubric in `evals/eval_template.md`.
   - Grade the draft with the strict rubric in `evals/grader_prompt.md`.
   - Pass threshold:
     - `structure >= 0.9`
     - `grounding >= 0.95`
     - `event_safety >= 0.9` when events or background processing exist
     - `observability >= 0.8`
     - `overall >= 0.9`
     - zero high-severity issues
   - If the draft fails, revise it using only evidence-backed fixes, then re-grade.
   - Maximum iterations: 3. If the draft still fails, keep the artifact grounded, document unresolved gaps in `Unknowns & Questions`, and report a safe fail rather than guessing.
8. Completion:
   - Leave the repo with an updated canonical `spec.md`.
   - Summarize the evidence sources, any remaining unknowns, whether parallel analysis was used, and whether the quality loop passed cleanly or ended in safe fail mode.
