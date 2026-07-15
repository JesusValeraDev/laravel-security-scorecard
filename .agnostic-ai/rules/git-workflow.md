# Git Workflow Rules

## Trunk-Based Development

**We work trunk-based. Always commit directly to `master`. Do NOT create branches.**

- All work happens on `master` — no feature/fix/ref branches.
- Commit small, frequent, self-contained changes straight to `master`.
- Keep `master` releasable at all times: each commit passes hooks and tests.
- No long-lived branches, no PR-per-feature flow. Push directly to `origin master`.
- Use feature flags / incomplete-but-inert code instead of branches to hide unfinished work.

## Git Hooks

Version-controlled hooks in `.githooks/`. Install once after cloning:

```bash
./.githooks/install.sh
```

| Hook         | Enforces                                                          |
|--------------|-------------------------------------------------------------------|
| `pre-commit` | Pint + PHPStan on staged PHP files, ESLint + tsc on staged TS/TSX |
| `commit-msg` | Conventional commit format (see below)                            |

## Commit Message Format

Conventional commits. **Enforced by the `commit-msg` hook.**

```
<type>: <description>
<type>(<scope>): <description>

[optional body]
```

### Types

| Type    | Use For                                 |
|---------|-----------------------------------------|
| `feat`  | New features                            |
| `fix`   | Bug fixes                               |
| `ref`   | Code restructuring (no behavior change) |
| `test`  | Adding/updating tests                   |
| `docs`  | Documentation changes                   |
| `chore` | Maintenance tasks                       |
| `perf`  | Performance improvements                |
| `ci`    | CI/CD changes                           |

### Rules

- Description must be at least 3 characters
- Description starts lowercase
- No period at the end
- Scope is optional: `feat(editor): add highlight colors`
- Merge and revert commits are always allowed

### Examples

```
feat: add text highlight annotations
feat(editor): add annotation color picker
fix: prevent empty annotation creation
ref: extract email validation to value object
test: add ErrorPage i18n tests for all languages
docs: update architecture rules for Annotation module
```

## Feature Development Cycle

### 1. Planning

- Understand requirements fully
- Identify affected modules (backend + frontend)
- Plan test strategy

### 2. Test-Driven Development

- Write failing test first
- Implement minimum code to pass
- Refactor while green
- Every feature must include:
    - **Happy path** tests (expected success)
    - **Edge case** tests (defaults, trimming, boundaries)
    - **Sad path** tests (invalid input, not-found, errors)

### 3. Review

- Self-review before PR
- Check for SOLID violations
- Verify no debug code remains
- Verify translations added to all 5 languages (en, es, fr, de, ar)

### 4. Integration

- Write descriptive commit message
- Commit directly to `master` and push: `git push origin master`
- Keep changes small so review can happen post-merge on trunk

## Before Pushing

The `pre-commit` hook catches most issues. Additionally verify:

- [ ] All tests pass (`composer test` in backend, `npm run test` in frontend)
- [ ] Commit messages follow convention (enforced by hook)
- [ ] Local `master` is up to date with `origin/master` (pull/rebase before pushing)
- [ ] New UI strings are translated in all 5 languages
