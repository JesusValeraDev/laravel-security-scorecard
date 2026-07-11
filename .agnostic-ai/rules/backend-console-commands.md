---
globs: "modules/*/Infrastructure/Console/*Command.php"
---

# Console Command Naming

All console commands follow a **module-prefixed** naming convention to prevent collisions and make ownership obvious.

## Class Naming

Pattern: `{Module}{Action}{Subject}Command`

| Module | Example Class |
|--------|--------------|
| User | `UserPurgeInactiveCommand` |
| Scorecard | `ScorecardReindexChaptersCommand` |
| Planning | `PlanningRecalculateMilestonesCommand` |

## Signature Naming

Pattern: `{module}:{action}-{subject}`

- Module prefix in lowercase, matching the module name
- Action and subject in kebab-case after the colon
- Example: `scorecard:reindex-chapters`, `user:purge-inactive`

## File Location

`modules/{Module}/Infrastructure/Console/{Module}{Action}{Subject}Command.php`

## Registration

Register in the module's ServiceProvider `boot()` method via `$this->commands([...])`.
Commands belong to their module — never under `app/Console/` (no code lives in `app/`).

## Renaming

Scheduling is internal — no alias/deprecation needed. Grep and update all references at once:
```
grep -r "Schedule::" --include="*.php" app/ bootstrap/ modules/ routes/
```

## Anti-Patterns

- Missing module prefix on the class (`PurgeCommand` → `UserPurgeInactiveCommand`)
- Signature prefix not matching the module
