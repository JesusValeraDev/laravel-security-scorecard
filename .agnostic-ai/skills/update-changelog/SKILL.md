---
name: update-changelog
description: Update CHANGELOG.md with recent changes from the current work session
---

# Update Changelog

Update CHANGELOG.md with recent changes from the current work session.

## Arguments
- `$ARGUMENTS` - Optional: change description (e.g., `feat: add user authentication`)

## Instructions

1. **Read or create CHANGELOG.md**: create with the standard header (see Format Template) if missing; otherwise read it to match existing format.

2. **Check today's date section**: add to today's section if it exists, else create one at the top (after the header).

3. **Categorize** under: `### Features` (new), `### Improvements` (enhancements), `### Fixes` (bugs), `### Breaking Changes`.

4. **Write a concise entry**: imperative mood ("Add" not "Added"), lead with bold module (`**Module**: Description`), under 100 chars, focus on user impact.

5. **Update the file** with Edit.

## Format Template

```markdown
## YYYY-MM-DD

### Features
- **ModuleName**: Brief description of new feature

### Improvements
- **ModuleName**: Brief description of improvement

### Fixes
- **ModuleName**: Brief description of fix
```

## Examples

Good entries:
- `**User**: Add user registration endpoint`
- `**Order**: Auto-generate order number on creation`
- `**Dashboard**: Fix chart rendering on mobile`

Bad entries:
- `Added stuff to the user module` (too vague)
- `Refactored the CreateUserHandler to use dependency injection...` (too technical)

## Quick Usage

After completing any feature, fix, or improvement:
```
/update-changelog feat: add user profile page
```

Or invoke without arguments to be prompted for details.

## Checklist
- [ ] Entry added under correct date
- [ ] Entry categorized correctly (Feature/Improvement/Fix)
- [ ] Entry is concise and user-focused
- [ ] Module/area is clearly identified
