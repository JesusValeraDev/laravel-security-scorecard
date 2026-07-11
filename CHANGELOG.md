# Changelog

All notable changes to this project are documented here. The format is based on
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- **Re-architected into a modular monolith** (hexagonal / DDD). All source moved from
  the flat `app/` layout into `modules/`, autoloaded under the `Modules\` namespace,
  with `Domain` / `Application` / `Infrastructure` layers and per-module service
  providers registered in `bootstrap/providers.php`.
  - `Scorecard` module: scanning engine, checks, grading, Livewire UI, monitoring.
  - `Shared` module: `Email` and `Uuid` value objects.
- `ScanRunner` now depends only on the Domain `Check` contract; concrete checks are
  assembled by `ScorecardServiceProvider` (Application no longer references
  Infrastructure).
- Adopted the toolkit quality pipeline: **Pint**, **Rector**, **PHPStan/Larastan
  (level max)**, PHPUnit test layout (`Unit` / `Integration` / `Feature`), git hooks,
  and GitHub Actions CI.
- Tests migrated from Pest to PHPUnit.
- Defaults tuned for zero-cost hosting: `sync` queue, `file` cache/session, `log` mail.

### Added

- Free-tier friendly config `config/scorecard.php` with `SCORECARD_ALERTS_ENABLED`
  (default off) gating grade-drop emails, so the app runs without a mail provider.
- Laravel Cloud deployment notes (SQLite locally, env-driven PostgreSQL in production,
  `sync` queue, `/up` health check).

## [0.1.0] - 2026-07-11

Initial build (flat Laravel app).

### Added

- Passive security scanner with 13 checks (exposed `.env`/`.git`/logs/`composer.lock`,
  Ignition RCE endpoint, open Telescope/Horizon/Pulse, HTTPS redirect, cookie flags,
  server version disclosure, security headers, directory listing).
- A–F grading, with any critical finding capping the grade at F.
- Livewire UI: landing scan form, live per-check progress view (queued job +
  polling), and a shareable graded report card.
- Monitoring: watch a site by email, scheduled re-scans (`monitors:rescan`), and
  grade-drop email alerts.
- Domain-ownership verification (DNS TXT / meta tag / well-known file) before a
  monitor is scanned, plus a tokenized manage/unsubscribe page.
