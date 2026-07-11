---
name: codebase-exploration
description: Structured codebase exploration for the Scorecard monorepo before writing specs, plans, or code.
---

Understand the Scorecard codebase before writing specs, plans, or code.

Input: a scope or question (feature area, module, model, route path, or cross-layer concern).

Rules:

- Grep/Glob/Read the relevant module or frontend folder before implementing; lightest tool first (read a domain entity before querying the DB; read a route file before grepping controllers).
- For cross-layer features, cover `modules/` (backend) and `resources/frontend/src/` (frontend) separately, then synthesize.
- Ground every claim in file evidence. If a search returns nothing, say so rather than guessing.

## Repository Layout

```
scorecard/
├── modules/                   # Laravel modular monolith — one folder per bounded context
│   ├── {Module}/
│   │   ├── Domain/            # Pure PHP — entities, value objects, repository interfaces, exceptions
│   │   ├── Application/       # Use cases — Command/ and Query/ subdirs, each with DTO + Handler
│   │   └── Infrastructure/    # Laravel impl — Controllers, Eloquent repos, Providers, Form Requests, Resources
├── resources/frontend/src/    # React 19 SPA
│   ├── pages/                 # Route-level pages
│   ├── components/            # Feature-grouped components: editor/, scorecards/, planning/, story-bible/, etc.
│   ├── stores/                # Zustand stores (*Store.ts)
│   ├── hooks/                 # Custom hooks (use-*.ts)
│   ├── lib/                   # API client (api.ts), autosave, i18n translations
│   ├── design-system/         # tokens.css, components.css
│   └── types/                 # Shared TypeScript interfaces
├── database/migrations/       # Laravel migrations (schema source of truth)
├── routes/                    # Global Laravel API + web routes
└── bootstrap/providers.php    # Module service provider registration
```

## Modules

| Module       | Bounded context                                              |
|--------------|--------------------------------------------------------------|
| User         | Authentication, profiles, subscription plans                 |
| Auth         | Magic link + Google OAuth flows                              |
| Scorecard   | Scorecards, chapters, auto-save, content management         |
| Planning     | Spatial canvas with sticky notes and connections             |
| StoryBible   | Characters, locations, lore, plot beats (AI-extracted)       |
| StoryTracker | Story event tracking                                         |
| Annotation   | Text highlights + inline notes on chapter content            |
| Sharing      | Scorecard sharing / collaboration                           |
| Editorial    | AI editorial lenses (Plot, Character, Writing, Grammar)      |
| Billing      | Lemon Squeezy subscriptions, plan gating                     |
| Shared       | Common value objects (Email, Uuid), shared interfaces        |
| Admin        | Admin panel (Filament — no DDD layers, direct Eloquent)      |

## Exploration Patterns

### Pattern 1: Feature scoping (what exists, what must change)

For "what routes, models, and services are involved in X":

1. **Grep modules/** — find the relevant module(s) by domain keyword
   ```bash
   grep -rl "ChapterId\|Chapter" modules/ --include="*.php" | head -20
   ```
2. **Read Domain/Entity/** — understand the aggregate root and its value objects
3. **Read Application/Command/ and Application/Query/** — enumerate use cases
4. **Read Infrastructure/Http/Controller/** — see HTTP surface
5. **List routes** — get the full API surface
   ```bash
   php artisan route:list --path=api/chapters
   ```
6. **Read migrations** — confirm actual DB schema
   ```bash
   ls database/migrations/ | grep chapter
   ```
7. **Grep frontend** — find the pages, stores, and API calls that touch this feature
   ```bash
   grep -rl "chapter\|Chapter" resources/frontend/src/ --include="*.ts" --include="*.tsx"
   ```
8. Synthesize: which backend layers need changes, which frontend files need changes.

### Pattern 2: Understanding existing behavior

For "how does X work":

1. **Read CLAUDE.md / .claude/rules/** — check for documented conventions
2. **php artisan route:list** — find the entrypoint route
3. **Read the Controller** for that route to identify the handler
4. **Read Application/Command or Query handler** — core logic
5. **Read Domain Entity** — invariants and business rules
6. **Read Infrastructure/Repository** — actual DB access
7. **Read frontend store + API call** — see the full round-trip
8. Read source only for details not captured above

### Pattern 3: Schema and data model investigation

For data issues or migration planning:

1. **Read database/migrations/** — source of truth for table structure
   ```bash
   ls database/migrations/ | grep -i <table>
   cat database/migrations/<timestamp>_create_<table>_table.php
   ```
2. **Read the Eloquent model** at `modules/{Module}/Infrastructure/Persistence/Eloquent/Model/`
3. **Read the Domain entity** at `modules/{Module}/Domain/Entity/` — see how DB maps to domain

### Pattern 4: Frontend component/store investigation

For reviewing or extending frontend behavior:

1. **Glob pages and components** for the feature area
   ```bash
   find resources/frontend/src/components/editor -name "*.tsx" | sort
   ```
2. **Read the Zustand store** for the domain: `src/stores/{name}Store.ts`
3. **Read the API client** for the relevant calls: `src/lib/api.ts`
4. **Read i18n keys** for existing translation scope: `src/lib/i18n/en.ts`
5. **Grep for hardcoded strings** that should be translated:
   ```bash
   grep -rn ">[A-Z][a-z]" resources/frontend/src/pages/ --include="*.tsx"
   ```
6. **Read co-located tests** to understand expected behavior: `{Name}.test.tsx`

### Pattern 5: Cross-layer impact assessment

For a feature touching both backend and frontend:

1. Backend: module scope (Domain → Application → Infrastructure)
2. Frontend: pages, components, stores, API client, i18n keys
3. Database: migrations for schema changes
4. Routes: `php artisan route:list` for API surface
5. Document: which files change, what contracts exist between layers

## Key CLI Commands

```bash
# List all API routes
php artisan route:list

# Filter routes by path
php artisan route:list --path=api/scorecards

# List migrations
ls database/migrations/

# Run a specific test
cd resources/frontend && npm run test:fast
./vendor/bin/phpunit --filter=ChapterTest
```

## Important Notes

- Backend is API-only — no Inertia, no Blade views for the app
- Frontend proxies `/api` and `/sanctum` to backend via Vite dev server
- Auth: Laravel Sanctum (SPA cookie-based) — CSRF via `XSRF-TOKEN` cookie
- Content is stored as HTML (TipTap `editor.getHTML()`), not JSON
- Annotations live in their own DB table; highlights are TipTap marks embedded in HTML content
