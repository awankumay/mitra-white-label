#!/usr/bin/env bash
# deny-dangerous.sh — Command Code PreToolUse guard (bash).
# Reads Command Code hook JSON from stdin. Denies rm -rf and direct
# pushes to main/master. Exit 0 = allow, exit 2 = deny.
set -euo pipefail

CMD=$(jq -r '.tool_input.command // ""')

if printf '%s' "$CMD" | grep -qiE '(^|;[[:space:]]*|&&[[:space:]]*|[|][|][[:space:]]*|[|][[:space:]]*)rm[[:space:]]' \
  && printf '%s' "$CMD" | grep -qiE '(^|[[:space:]])-[a-zA-Z]*[rR]|--recursive' \
  && printf '%s' "$CMD" | grep -qiE '(^|[[:space:]])-[a-zA-Z]*[fF]|--force'; then
  echo 'BLOCKED: Use trash instead of rm -rf' >&2
  exit 2
fi

if printf '%s' "$CMD" | grep -qE 'git[[:space:]]+push.*(main|master)'; then
  echo 'BLOCKED: Use feature branches, not direct push to main' >&2
  exit 2
fi

exit 0