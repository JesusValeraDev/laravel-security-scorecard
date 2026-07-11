---
name: pr
description: Create a pull request with auto-generated description and changelog update
---

# Create Pull Request

Create a PR with auto-generated description and changelog update.

## Arguments
- `$ARGUMENTS` - PR title (optional, generated from branch if empty)

## Instructions

1. Get current branch:
```bash
   git branch --show-current
```

2. Get commits since master:
```bash
   git log master..HEAD --oneline
```

3. Push branch:
```bash
   git push -u origin HEAD
```

4. Create PR with generated description using a heredoc so newlines render correctly:
```bash
   gh pr create --title "$ARGUMENTS" --body "$(cat <<'EOF'
## Changes

$(git log master..HEAD --oneline)

## Testing

- [ ] Tests pass
- [ ] Code reviewed
EOF
)"
```

7. Report the PR URL.
