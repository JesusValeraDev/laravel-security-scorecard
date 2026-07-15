# Laravel Security Scorecard

A passive, external security checkup for Laravel apps. Enter a URL and get a graded report card that flags common
misconfigurations — exposed `.env`/`.git`, unauthenticated Telescope/Horizon/Pulse, the Ignition RCE endpoint, missing
security headers, and more — each with a plain-English explanation and the exact fix.

[![PHP 8.5](https://img.shields.io/badge/PHP-8.5+-777BB4?logo=php&logoColor=white)](https://php.net)
[![Laravel 13](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)

Every check is a non-invasive `GET` a crawler could make anyway. The scanner **never attempts exploitation**, and all
analysis is **rule-based — no AI at runtime**, so it runs cheaply and offline.

---

## What it checks

13 passive checks, worst-first on the report card:

| Severity   | Check                 | What it catches                                 |
|------------|-----------------------|-------------------------------------------------|
| Critical   | `.env` exposed        | App key, DB credentials, API secrets            |
| Critical   | `.git` exposed        | Full source history is downloadable             |
| Critical   | Laravel log exposed   | Stack traces, SQL, tokens in `laravel.log`      |
| Critical   | Ignition RCE          | CVE-2021-3129 endpoint reachable                |
| High       | Telescope open        | Every request, query, and payload               |
| High       | Horizon open          | Queue control panel                             |
| High       | Pulse open            | App performance internals                       |
| Medium     | composer.lock exposed | Exact dependency versions (CVE shopping list)   |
| Medium     | No HTTPS redirect     | Cleartext cookies and credentials               |
| Medium/Low | Security headers      | HSTS, CSP, X-Frame-Options, and more            |
| Medium/Low | Cookie flags          | Session cookie missing Secure/HttpOnly/SameSite |
| Low        | Directory listing     | Browsable file indexes                          |
| Low        | Version banners       | `Server` / `X-Powered-By` disclosure            |

Findings roll up into an **A–F grade** (any single critical caps it at F).

---

## Architecture

A **modular monolith** with **hexagonal / DDD layering**. Source lives in `modules/`, autoloaded under the `Modules\`
namespace.

```
modules/{Module}/
├── Domain/           # Pure PHP: value objects, contracts (no framework)
├── Application/      # Use cases (e.g. ScanRunner)
└── Infrastructure/   # Laravel: checks, HTTP client, Livewire, providers
```

The scan is stateless: `ScanForm` runs the checks inline, holds the graded result in component state, and renders it on
the same page. Nothing is persisted — there is no database and no shareable link; a refresh clears the result.

**Dependency rule:** Domain → Application → Infrastructure (never the reverse). The`ScanRunner` use case depends only on
the Domain `Check` contract; the concrete checks are assembled in the module's service provider.

Modules are registered in `bootstrap/providers.php`.

**Stack:** PHP 8.4+ · Laravel 13 · Livewire + Blade + Tailwind CSS 4 · PHPUnit · Larastan · Rector · Pint.

---

## Quick start

```bash
composer setup      # install, env, key, npm build (no database)
composer dev        # serve + logs + vite (http://localhost:8000)
```

No queue worker or mail provider is required for local development (see below).

### Everyday commands

```bash
composer test       # pint (style) + phpstan (max) + phpunit
composer fix        # auto-fix code style (Pint)
composer rector:fix # apply Rector code-quality rules
php artisan test    # tests only
```

Git hooks (`.githooks/`) run the same checks on commit/push; `composer setup` wires them up.

---

## Running for free

The defaults are chosen so the app **costs nothing to run** — no database, no queue worker, no Redis, no mail provider:

| Concern         | Default | Why it's free                                |
|-----------------|---------|----------------------------------------------|
| Database        | None    | Stateless; scan results live only in-request |
| Queue           | `sync`  | Scans run in-request; no worker process      |
| Cache / session | `file`  | No database/Redis store needed               |
| Mail            | `log`   | No mail provider needed                      |

### Deploying to Laravel Cloud

1. Point Laravel Cloud at the repo — it auto-detects Laravel.
2. Set `APP_KEY`. **No database** is needed — the app persists nothing.
3. Keep `QUEUE_CONNECTION=sync` — scans run in-request, so **no queue worker** is needed.
4. **No scheduler** is required — the app runs no scheduled commands.
5. Health checks are served at `/up`.

The result is a single stateless web service — no database, no background worker, no scheduled-task process.

---

## Guardrails

- **Passive only** — `GET` requests a crawler could make; never a payload or exploit.
- **Precision over coverage** — a finding fires only on concrete evidence (e.g. real env markers, not a bare `200`).
- **SSRF hygiene** — local/private/`.test` hosts are refused.
