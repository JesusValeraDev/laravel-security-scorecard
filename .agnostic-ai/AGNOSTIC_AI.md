# Laravel Security Scorecard

A passive, external security checkup for Laravel apps. Enter a URL and get a graded,
shareable report card that flags common misconfigurations — exposed `.env`/`.git`,
unauthenticated Telescope/Horizon/Pulse, the Ignition RCE endpoint, missing security
headers — each with a plain-English explanation and the exact fix.

Every check is a non-invasive GET request a crawler could make anyway. The scanner
never attempts exploitation. All analysis is static/rule-based — no AI at runtime.

## Stack

| Layer     | Tech                                              |
|-----------|---------------------------------------------------|
| Backend   | Laravel 13, PHP 8.4+                              |
| Structure | Modular monolith, hexagonal/DDD (`modules/`)      |
| Frontend  | Livewire + Blade + Tailwind CSS 4                 |
| Queue     | `sync` (free-tier; scans run in-request)          |
| Database  | SQLite (local) / PostgreSQL (production)          |
| Testing   | PHPUnit (Unit / Integration / Feature)            |
| Quality   | Pint, Rector, PHPStan/Larastan (level max)        |
| Deploy    | Laravel Cloud                                     |

## Architecture

Source lives in `modules/`, autoloaded as `Modules\`. Each module has three layers:

```
modules/{Module}/
├── Domain/           # Pure PHP: value objects, contracts (no framework)
├── Application/      # Use cases (e.g. ScanRunner)
└── Infrastructure/   # Laravel: checks, Eloquent, Livewire, jobs, mail, providers
```

Dependency rule: Domain → Application → Infrastructure (never the reverse). Modules
register in `bootstrap/providers.php`.

- **`Scorecard`** — scanning, grading, report UI, monitoring.
- **`Shared`** — cross-cutting value objects (`Email`, `Uuid`).

## Core concepts

- **Check** (`Domain/Check/Check`) — one passive HTTP probe → `Finding` or null.
  Concrete checks live in `Infrastructure/Check` and are assembled by the module
  service provider, so `ScanRunner` (Application) depends only on the Domain contract.
- **Scan** — a queued `RunScan` job runs the checks, streams per-check progress, and
  persists an A–F graded result (Livewire polls it for a live view).
- **Monitor** — a watched host + email. Re-scanned by `monitors:rescan`; alerts on a
  grade drop (gated behind `scorecard.alerts_enabled`). Ownership-verified before any
  scheduled scan; managed/unsubscribed via a tokenized page.

## Guardrails (non-negotiable)

- Passive only: GET requests, no payloads, no exploitation.
- Detect by response-body evidence, never a bare 200.
- Scheduled monitoring requires domain-ownership verification.
- Precision over coverage: every critical finding must be certain.

## Commands

```bash
composer setup   # install + env + key + sqlite + migrate + build
composer dev     # serve + logs + vite
composer test    # pint + phpstan + phpunit
composer fix     # Pint auto-fix
composer rector:fix
```

## Rules

Backend conventions, testing, security, and coding-style rules auto-load from
`.claude/rules/` (`backend-*`, plus cross-cutting `architecture.md`, `git-workflow.md`).
