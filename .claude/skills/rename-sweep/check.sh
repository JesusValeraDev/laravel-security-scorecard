#!/usr/bin/env bash
# Deterministic old-name reference sweep for renames.
# Companion to .claude/rules/rename-safety.md — replaces the hand-run rg commands.
#
# Usage:
#   check.sh inventory <old-name> [<old-name-2> ...]   # Step 1: list every hit, all layers
#   check.sh gate      <old-name> [<old-name-2> ...]   # Step 3: exit 1 on any hit outside
#                                                      #         CHANGELOG + migration history
#
# Pass names in kebab-case; each is matched case-insensitively across kebab, snake,
# camel, Pascal and space-separated variants. Pass singular AND plural explicitly —
# they are different strings and plural bites hardest.
set -euo pipefail

mode="${1:-}"
shift || true

if [[ "$mode" != "inventory" && "$mode" != "gate" ]] || [[ $# -lt 1 ]]; then
  echo "usage: $0 inventory|gate <old-name> [more-old-names...]" >&2
  echo "  names in kebab-case; pass singular and plural separately" >&2
  exit 2
fi

common_excludes=(
  -g '!node_modules' -g '!vendor' -g '!*.lock' -g '!dist'
  -g '!public/build' -g '!.git' -g '!storage'
  # AI-harness config documents renames as worked examples; not app code.
  -g '!.agnostic-ai' -g '!.claude' -g '!.codex'
)
# The SDD doc is a historical audit record, like the changelog.
gate_excludes=(-g '!CHANGELOG.md' -g '!database/migrations' -g '!docs/backend-structure-sdd.md')

status=0
for name in "$@"; do
  # "old-name" -> "old[-_ ]?name" : matches old-name, old_name, oldName, OldName, "old name"
  lower=$(printf '%s' "$name" | tr '[:upper:]' '[:lower:]' | tr '_' '-')
  pattern=$(printf '%s' "$lower" | sed 's/-/[-_ ]?/g')

  args=(--hidden -i "${common_excludes[@]}")
  [[ "$mode" == "gate" ]] && args+=("${gate_excludes[@]}")

  echo "=== sweep: '$name' (pattern: $pattern) ==="
  if rg "${args[@]}" -n "$pattern" .; then
    status=1
  else
    echo "0 hits"
  fi
  echo
done

if [[ "$mode" == "gate" ]]; then
  if [[ $status -eq 1 ]]; then
    echo "GATE FAILED: old-name references remain (outside CHANGELOG/migrations)." >&2
  else
    echo "GATE PASSED: zero old-name references."
  fi
  exit $status
fi

exit 0
