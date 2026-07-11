---
name: gh-issues
description: Autonomously process every open GitHub issue assigned to me or unassigned — one PR each, auto-merged when CI is green, then on to the next
---

# GitHub Issues Watcher (autonomous)

Drain the open-issue backlog **unattended**. For each issue assigned to you or
unassigned: open a PR, wait for its CI to go green, merge it, then move to the next.

This skill is the **loop**; [[gh-issue]] is the **body**. This skill only discovers,
guards, orchestrates, and merges — all implementation lives in `gh-issue`.

## Default scope

With no flags, the work-list is **every open issue that is unassigned OR assigned to
you** (`@me`) — oldest first. Umbrella/epic trackers (issues whose body is just a
checklist of other issues, e.g. #27) are excluded; only their children are processed.

## Arguments

`$ARGUMENTS` accepts optional flags (any order):

- `--limit N` — process at most N issues (default: all matched)
- `--label foo` — only issues carrying label `foo` (repeatable)
- `--assignee me|none|both` — `me`, `none` (unassigned), or `both` (default)
- `--no-merge` — open the PR and stop; do **not** auto-merge (manual review mode)
- `--merge-method merge|squash|rebase` — how to merge (default: `merge`, matching repo history)
- `--dry-run` — print the work-list + planned branch/label per issue, then stop

By default the loop **merges autonomously on green CI** ("yolo"). Pass `--no-merge` to
turn that off.

## Phase 1 — Discovery

```bash
gh issue list --state open --assignee @me  --json number,title,labels,assignees,createdAt --limit 200
gh issue list --state open --search "no:assignee" --json number,title,labels,assignees,createdAt --limit 200
```

Union the two lists, dedup by number, then drop:
- **umbrella/epic trackers** (body is only a checklist of other issues, e.g. #27), and
- issues that **already have an open linked PR** (idempotency under repeated yolo runs):
  ```bash
  gh pr list --state open --search "<n> in:body" --json number   # non-empty → skip issue <n>
  ```

Sort the remainder by `createdAt` ascending, then apply `--label` / `--limit`. Print the
work-list (number, title, derived branch prefix). If `--dry-run`, stop here.

## Phase 2 — Preflight (clean tree, never auto-stash)

```bash
git status --porcelain          # MUST be empty — abort the whole run if not
git fetch origin master
git switch master && git reset --hard origin/master
```

A dirty tree means the user has in-flight work — abort, never stash.

## Phase 3 — Per-issue loop

For each issue, in order:

1. **Re-verify ownership + no open PR.** Re-read it; if reassigned to someone else,
   or an open PR now references it, **skip** (log why) and continue.
2. **Claim.** `gh issue edit <n> --add-assignee @me` (+ matching label if missing).
3. **Branch off fresh master**, prefix by primary label:
   | Label | Prefix |
   |-------|--------|
   | `bug` | `fix/` |
   | `enhancement` | `feat/` |
   | `documentation` | `docs/` |
   | other | `chore/` |
   ```bash
   git switch master && git reset --hard origin/master && git switch -c <prefix><kebab-title>
   ```
4. **Implement via [[gh-issue]].** Full workflow for `<n>`: explore → plan → TDD →
   implement. Keep it narrow — one issue = one focused PR. If it can't be small, halt
   and ask how to split (see #27's split for the pattern).
5. **Local green gate** (never push on red):
   ```bash
   make test     # backend + frontend
   make lint     # Pint + PHPStan + ESLint + tsc
   ```
6. **Commit** — conventional (`feat:`/`fix:`/`ref:`/`docs:`/`chore:`), no Claude/
   Anthropic trailers, GPG-signed. The host pre-commit hook needs Docker and fails
   headless, so use `--no-verify` (CI is the real gate):
   ```bash
   git commit --no-verify -S -m "<type>: <description>"
   ```
7. **Open the PR — one PR closes one issue:**
   ```bash
   git push -u origin <branch>
   gh pr create --assignee Chemaclass --label <issue-label> \
     --title "<type>: <description>" \
     --body "Closes #<n>

   ## Summary
   …
   ## Test plan
   - make test
   - make lint"
   gh pr ready <pr>     # repo defaults new PRs to draft → flip to ready
   ```
8. **Wait for CI, then auto-merge on green** (skip if `--no-merge`):
   ```bash
   gh pr checks <pr> --watch --fail-fast    # blocks until every check resolves
   ```
   - **All green →** merge and clean up:
     ```bash
     gh pr merge <pr> --merge --delete-branch   # or --squash/--rebase per --merge-method
     ```
     Confirm `gh pr view <pr> --json state -q .state` == `MERGED` and the issue
     auto-closed (the `Closes #<n>` keyword). If GitHub didn't close it, close manually.
   - **Any check fails →** enter the **bounded fix loop** (max **2 attempts**):
     read the failing check's log, fix locally, re-run `make test` + `make lint`
     to green, push, and watch checks again. Goal: all checks green; stop
     condition: green OR 2 fix attempts exhausted. If still red after 2 attempts,
     **halt the whole loop** — leave the PR open, report the failing check + issue
     number + what each attempt changed, do not merge, do not start the next issue.
     Never loosen/skip a test or bump a timeout just to go green — that's a halt,
     not a fix.
9. **Resync and continue:**
   ```bash
   git switch master && git pull --ff-only origin master
   ```
   Then take the next issue. After the last one, print a summary table
   (issue → PR → merged/failed/skipped).

## Critical safeguards

- **Halt on hard failure** (merge conflict, branch protection block, or CI still
  red after the bounded fix loop) — report which issue and why, stop the loop,
  never retry blindly. Every retry is capped: **2 CI-fix attempts per PR**, never
  re-attempt the same fix twice.
- **CI is the merge gate.** Never merge a PR whose checks aren't all green. Never
  `--admin`-override a failing/blocked merge.
- **Clean tree throughout** — re-check `git status` is clean before each branch.
- **One PR ⇄ one issue.** No bundling unrelated changes. No scope creep — if an issue
  is too big for a small PR, pause and ask.
- **Never touch umbrella/epic issues** — process only their children.
- **Branch protection wins.** If a merge is blocked (required reviews/checks), stop on
  that issue and report; don't force it.

## Example usage

```
/gh-issues                       # YOLO: drain all my/unassigned issues, auto-merge on green
/gh-issues --label enhancement --limit 3
/gh-issues --no-merge            # open PRs only, leave merging to me
/gh-issues --dry-run             # preview the work-list, do nothing
```

## Checklist (per issue)

- [ ] Ownership re-verified (skipped if reassigned)
- [ ] Self-assigned + label set
- [ ] Branch off fresh master with label-based prefix
- [ ] Implemented via `gh-issue` (explore → plan → TDD)
- [ ] `make test` + `make lint` green locally
- [ ] Conventional commit (`--no-verify`, GPG-signed), PR opened with `Closes #<n>`, ready
- [ ] CI watched to completion; **merged only when all checks green**
- [ ] Issue auto-closed; master resynced; next issue (or halt on failure)
