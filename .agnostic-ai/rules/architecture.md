# Laravel Security Scorecard — Architecture Overview

A **modular monolith** with **hexagonal / DDD** layering. Source lives in `modules/`,
autoloaded under the `Modules\` namespace; there is no `app/` directory.

## Layout

```
modules/{Module}/
├── Domain/               # Pure PHP — no framework
│   ├── ValueObject/      # Severity, Finding, Grade, Target, ScanResult
│   └── Check/            # Check interface (contract)
├── Application/          # Use cases
│   └── ScanRunner.php    # runs Checks against a Target → ScanResult
└── Infrastructure/       # Laravel adapters
    ├── Check/            # concrete passive checks (HTTP)
    ├── Http/Client/      # ProbeClient, PublicHostGuard
    ├── Http/Livewire/    # ScanForm (runs the scan and renders the report)
    └── Provider/         # {Module}ServiceProvider
```

Modules register in `bootstrap/providers.php`. Shipped modules: `Scorecard` (scanning,
grading, report UI) and `Shared` (`Email`, `Uuid`).

## Dependency rule

Domain → Application → Infrastructure, **never the reverse**. Concretely: `ScanRunner`
(Application) depends only on the `Domain\Check\Check` contract; the concrete
`Infrastructure\Check\*` implementations are assembled and injected by
`ScorecardServiceProvider`. Views stay global (`resources/views`). There is no database:
the app is stateless and persists nothing.

## Flow

1. `ScanForm` (Livewire) validates a URL, resolves it past `PublicHostGuard` (SSRF guard),
   and rate-limits per IP.
2. It runs `ScanRunner` inline (sync queue), which runs each `Check` against the `Target`
   and aggregates the findings into a graded `ScanResult`.
3. `ScanForm` maps the `ScanResult` onto its own component state and renders the graded
   report on the same page. Nothing is stored — a refresh clears it, no shareable link.

## Design principles

- **Passive and safe** — GET-only probes; no payloads or exploitation.
- **Precision over coverage** — a check fires only on concrete response evidence.
- **Checks are pure and isolated** — one class + one test each; faked `Http` in tests.
- **Free by default** — no database; `file` cache/session, `sync` queue, `log` mail.
- **Stateless** — a scan result lives only for the request that produced it.

## Key decisions

| Decision     | Choice               | Rationale                                    |
|--------------|----------------------|----------------------------------------------|
| Structure    | Modular monolith/DDD | Clear boundaries, testable, splittable later |
| UI           | Livewire + Blade     | Server-rendered; scan and report on one page |
| Scans        | Inline in `ScanForm` | Sync queue; result held in component state   |
| Persistence  | None                 | Stateless; no DB to provision or migrate     |
| Queue (free) | `sync`               | No worker/Redis cost                         |
| Runtime AI   | None                 | Rules-based; cheap, offline                  |
| Deploy       | Laravel Cloud        | Auto-detected; `/up` health check            |
