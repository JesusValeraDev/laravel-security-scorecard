# Laravel Security Scorecard — Architecture Overview

A **modular monolith** with **hexagonal / DDD** layering. Source lives in `modules/`,
autoloaded under the `Modules\` namespace; there is no `app/` directory.

## Layout

```
modules/{Module}/
├── Domain/           # Pure PHP — no framework
│   ├── ValueObject/      # Severity, Finding, Grade, Target, ScanResult, Email, Uuid
│   └── Check/            # Check interface (contract)
├── Application/      # Use cases
│   └── ScanRunner.php    # runs Checks against a Target → ScanResult
└── Infrastructure/   # Laravel adapters
    ├── Check/            # concrete passive checks (HTTP)
    ├── Persistence/Eloquent/Model/   # ScanModel, MonitorModel
    ├── Http/Livewire/    # ScanForm, ScanReport, ManageMonitor
    ├── Queue/            # RunScan job
    ├── Console/          # RescanMonitors command
    ├── Mail/             # GradeDroppedMail
    ├── Verification/     # DomainVerifier, TxtRecordLookup
    └── Provider/         # {Module}ServiceProvider
```

Modules register in `bootstrap/providers.php`. Shipped modules: `Scorecard` (scanning,
grading, report UI, monitoring) and `Shared` (`Email`, `Uuid`).

## Dependency rule

Domain → Application → Infrastructure, **never the reverse**. Concretely: `ScanRunner`
(Application) depends only on the `Domain\Check\Check` contract; the concrete
`Infrastructure\Check\*` implementations are assembled and injected by
`ScorecardServiceProvider`. Views and migrations stay global (`resources/views`,
`database/migrations`); Eloquent models set an explicit `$table`.

## Flow

1. `ScanForm` (Livewire) validates a URL, creates a `ScanModel`, dispatches `RunScan`.
2. `RunScan` sets status `scanning`, resolves `ScanRunner` from the container, and runs
   each `Check`, writing per-check progress to the row.
3. `ScanReport` polls (`wire:poll`) the row and renders the graded report.
4. For monitors, `RunScan` calls `MonitorModel::evaluateAfterScan()` → emails on a
   grade drop when `scorecard.alerts_enabled` is true.

## Design principles

- **Passive and safe** — GET-only probes; no payloads or exploitation.
- **Precision over coverage** — a check fires only on concrete response evidence.
- **Checks are pure and isolated** — one class + one test each; faked `Http` in tests.
- **Free by default** — `sync` queue, `file` cache/session, `log` mail, alerts off.

## Key decisions

| Decision       | Choice                | Rationale                                   |
|----------------|-----------------------|---------------------------------------------|
| Structure      | Modular monolith/DDD  | Clear boundaries, testable, splittable later |
| UI             | Livewire + Blade      | Server-rendered; polling drives progress    |
| Scans          | Queued `RunScan`      | One path for interactive + scheduled scans  |
| Queue (free)   | `sync`                | No worker/Redis cost                         |
| Runtime AI     | None                  | Rules-based; cheap, offline                  |
| Deploy         | Laravel Cloud         | Auto-detected; `/up` health check           |
