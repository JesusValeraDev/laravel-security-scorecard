---
name: refactor-check
description: Analyze code for SOLID violations, clean code issues, and refactoring opportunities
---

# Refactor Check

Analyze code for SOLID violations, clean code issues, and refactoring opportunities.

## Arguments
- `$ARGUMENTS` - File path or directory to analyze

## Instructions

1. **Read the specified file(s)** from `$ARGUMENTS`

2. **Analyze for SOLID violations**

> See `solid-principles` skill for detailed patterns. Look for these symptoms:

| Principle  | Key Symptoms                                                 |
|------------|--------------------------------------------------------------|
| SRP        | Class has many methods, hard to name without "And"/"Manager" |
| OCP        | Switch/if-else chains that grow with features                |
| LSP        | `instanceof` checks, overridden methods that break behavior  |
| ISP        | Empty method implementations, "not implemented" exceptions   |
| DIP        | `new` in business logic, hard to test                        |

3. **Analyze for Clean Code issues:**

- **Naming**: descriptive/intention-revealing; class names = nouns, method names = verbs.
- **Functions**: small (< 20 lines), do one thing, ≤ 3 arguments.
- **Comments & Duplication**: no "what" comments, no commented-out code, no DRY violations.
- **Error Handling**: exceptions over error codes; errors specific and informative.

4. **Check Modular Monolith Architecture compliance:**

> See `laravel-hexagonal` skill for layer details.

| Layer          | Check                                       |
|----------------|---------------------------------------------|
| Domain         | Pure PHP? No framework dependencies?        |
| Application    | Depends on interfaces, not implementations? |
| Infrastructure | Implements domain interfaces?               |

5. **Generate report** with issues found, severity, line numbers, and suggested refactoring.

## Output Format

```markdown
# Refactor Analysis: <file/directory>

## Summary
- **SOLID Violations:** X issues
- **Clean Code Issues:** X issues
- **Architecture Issues:** X issues

## Critical Issues (High Priority)

### [SRP] <Class> has multiple responsibilities
**File:** `modules/User/Domain/Entity/User.php:10-50`
**Problem:** This class handles both user validation and email sending.
**Suggestion:** Extract email sending to a dedicated service.

## Moderate Issues (Medium Priority)
...

## Minor Issues (Low Priority)
...

## Architecture Compliance

| Module | Layer  | Status | Notes             |
|--------|--------|--------|-------------------|
| User   | Domain | OK     | No framework deps |

## Recommended Refactoring Steps
1. ...
```

## Checklist
- [ ] File(s) read and analyzed
- [ ] SOLID violations identified
- [ ] Clean code issues identified
- [ ] Architecture compliance checked
- [ ] Report generated with actionable suggestions
