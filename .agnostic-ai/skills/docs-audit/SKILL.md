---
name: docs-audit
description: Scan the codebase and verify docs/ matches the current implementation
allowed-tools: Read, Grep, Glob, Bash
---

# Documentation Freshness Audit

Read-only check that `docs/` reflect the code. Report each area OK/STALE/N/A, then offer to fix STALE items.

## Areas

1. **Architecture** (`docs/architecture.md`) — module list and hexagonal layering described match `modules/*/{Domain,Application,Infrastructure}/`
2. **Backend dev guide** (`docs/backend-development.md`) — conventions, layer rules, and example paths still exist (cross-check with `.agnostic-ai/rules/backend-*.md`)
3. **Frontend dev guide** (`docs/frontend-development.md`) — stack (React 19, Vite, Tailwind 4, TipTap, Zustand), directory map under `resources/frontend/src/`, and design-system references match reality
4. **API endpoints** (`docs/api-endpoints.md`) — routes documented match the `routes.php` files / controllers under `modules/*/Infrastructure/Http/Controller/`
5. **Getting started** (`docs/getting-started.md`) — setup commands match `Makefile` targets (`make setup`, `make dev`, `make migrate`)
6. **AI tooling** (`docs/ai-tooling.md`) — describes `.agnostic-ai/` + `make ai-sync`/`ai-check` accurately
7. **Deployment** (`docs/deployment.md`) — referenced services match the stack (PostgreSQL 17, Redis, Docker Compose)

## Method

- Glob the modules and frontend dirs; diff against what each doc claims
- For commands quoted in docs, verify the `make` target exists (`make help`)
- For file/path references in backticks, glob concrete (non-`{}`) paths — missing path = STALE

## Output

```
| Area | Status | Notes |
|------|--------|-------|
```

After reporting, offer to fix STALE items.
