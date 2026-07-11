---
name: news
description: Executive digest of recent PRs, commits, and closed issues for sharing
argument-hint: "[since] [until]"
allowed-tools: Bash, Read, Grep, Glob, Write
---

# News — Executive Summary

## Arguments
- `$ARGUMENTS` — Date range: empty (yesterday–today), `3d`/`7d` (last N days), `YYYY-MM-DD` (from date), `YYYY-MM-DD YYYY-MM-DD` (range)

## Data Sources (run in parallel)

1. Merged PRs: `gh pr list --state merged --search "merged:>=SINCE" --limit 50`
2. Git log: `git log --oneline --since=SINCE --until=UNTIL --first-parent master`
3. Closed issues: `gh issue list --state closed --search "closed:>=SINCE" --limit 30`

## Deduplication

Read `.agnostic-ai/news/history.md` — skip items already shared. Create the file if missing.

## Output

Group by theme, chat-app-compatible (no markdown links, plain text + emoji):

```
🚀 *Scorecard Update — DD Mon YYYY*

📦 *Module / Area*
• Concise description
```

**Rules**: max 15 bullets, merge related PRs, skip internal/chore commits, no backticks or code, 1 line per bullet.

## Save & Output

Append to `.agnostic-ai/news/history.md` under today's date header. Output the digest for copy-paste.
