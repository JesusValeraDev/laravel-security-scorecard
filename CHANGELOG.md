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
  - `Scorecard` module: scanning engine, checks, grading, Livewire UI.
- `ScanRunner` now depends only on the Domain `Check` contract; concrete checks are
  assembled by `ScorecardServiceProvider` (Application no longer references
  Infrastructure).
- **Scans run inline and stateless.** `ScanForm` runs the checks in-request (`sync`
  queue), holds the graded result in its own component state, and renders the report
  on the same page. There is no persistence, no queued `RunScan` job, and no live
  polling.
- Adopted the toolkit quality pipeline: **Pint**, **Rector**, **PHPStan/Larastan
  (level max)**, PHPUnit test layout (`Unit` / `Integration` / `Feature`), git hooks,
  and GitHub Actions CI.
- Tests migrated from Pest to PHPUnit.
- Defaults tuned for zero-cost hosting: **no database**, `sync` queue, `file`
  cache/session, `log` mail.

### Removed

- **The database, entirely.** Dropped the `scans` table and all default Laravel
  migrations (`users`/`sessions`/`cache`/`jobs`), the `ScanModel` Eloquent model, the
  `RunScan` job, and the `ScanReport` component and `/r/{token}` route. Cache and
  session now use the `file` driver; nothing is persisted.
- The shareable graded report link (results now live only for the request that
  produced them; a refresh clears them).

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
