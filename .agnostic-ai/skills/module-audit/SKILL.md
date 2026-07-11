---
name: module-audit
description: Verify a module follows hexagonal layering conventions and has proper test coverage
argument-hint: "[Module]"
allowed-tools: Read, Grep, Glob, Bash
---

# Module Audit

Read-only audit of one module (or all) against the hexagonal architecture under `modules/<Module>/{Domain,Application,Infrastructure}/`.

## Arguments
- `$ARGUMENTS` — Module name (e.g., `Scorecard`, `Editorial`, `StoryBible`). Omit to audit ALL modules in `modules/`.

## Instructions

### 1. Layer Completeness

Expected structure (data-only modules may lack `Application/`):

- `Domain/` — `Entity/`, `ValueObject/`, `Repository/`, `Service/`, `Contract/`, `Exception/`
- `Application/` — `Command/`, `Query/`
- `Infrastructure/` — `Http/{Controller,Request,Resource}/`, `Persistence/{Eloquent,InMemory}/`, `Provider/`, plus optional `Console/`, `Mail/`, `Export/`

### 2. Test Coverage

| Type | Location | Check |
|------|----------|-------|
| Unit | `tests/Unit/<Module>/` | Entity, ValueObject, Service, and handler tests |
| Integration | `tests/Integration/<Module>/` | Eloquent repository tests |
| Feature | `tests/Feature/<Module>/` | HTTP endpoint tests |

### 3. Conventions

- **Domain purity** — no `use Illuminate\` / `use Laravel\` in `Domain/`
- **Repositories** — `Domain/Repository/` are interfaces; Eloquent impls are `final readonly class` in `Infrastructure/Persistence/Eloquent/`; an `InMemory` impl exists for tests
- **Handlers** — Application command/query handlers expose a single entry point (e.g. `__invoke()`)
- **Provider wiring** — the module's `Infrastructure/Provider/*` is registered in `bootstrap/providers.php`, and every `Domain/Repository/` interface has a binding
- **No cross-module model imports** (Admin excepted)

### 4. Output

Single module:
```
Module Audit: <Module>
Layers: ✓/✗ Domain, Application, Infrastructure
Tests:  ✓/✗ Unit, Integration, Feature
Conventions: ✓/✗ per check
Issues: ...
Score: X/10
```

All modules: summary table
`| Module | Domain | App | Infra | Unit | Integ | Feature | Score |`

When a check is ambiguous, confirm with `make test-be` or `make lint` rather than guessing.
