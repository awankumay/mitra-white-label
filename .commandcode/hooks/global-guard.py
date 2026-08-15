#!/usr/bin/env python3
"""global-guard.py — Security guard for Command Code (Windows).

Command Code adaptation of the Claude Code hook. Command Code sends
tool_name as shell_command/read_file/write_file/edit_file and read
paths under absolute_path, so this version normalizes those fields.
Exit 0 = allow, Exit 2 = block (stderr first line shown to user).
"""

import json
import os
import re
import shlex
import sys
from datetime import datetime, timezone
from pathlib import Path

HOME_DIR = str(Path.home())

SECRETS_RE = re.compile(r"\.(env|key|pem|secret|keystore|credentials)$|\.env\.|\.credentials\.", re.IGNORECASE)

WRITABLE_ROOTS = [HOME_DIR, os.getcwd()]
READABLE_ROOTS = WRITABLE_ROOTS + [
    str(Path("D:/Project/Example").resolve()),
    str(Path("C:/Users/LENOVO").resolve()),
]

PASSTHROUGH_TOOLS = {
    "WebSearch", "WebFetch", "AskUserQuestion",
    "TaskCreate", "TaskUpdate", "TaskList", "TaskGet",
}

READ_CMDS = {"cat", "head", "tail", "less", "more", "bat", "sed", "awk", "grep"}
DESTRUCTIVE_CMDS = {"rm", "rmdir", "mv", "cp", "touch", "mkdir", "chmod", "chown", "ln"}

SHELL_OP_RE = re.compile(r"\s*(?:&&|\|\||[;|])\s*")

GUARD_LOG = Path.home() / ".claude" / "state" / "guard-log.jsonl"
def log_block(tool: str, reason: str, context: str = "") -> None:
    """Append blocked tool call to audit log."""
    try:
        entry = {
            "ts": datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ"),
            "tool": tool,
            "reason": reason,
        }
        if context:
            entry["context"] = context[:200]
        GUARD_LOG.parent.mkdir(parents=True, exist_ok=True)
        with open(GUARD_LOG, "a") as f:
            f.write(json.dumps(entry) + "\n")
    except OSError:
        pass  # Don't fail the guard if logging fails

def block(tool: str, reason: str, context: str = "") -> None:
    log_block(tool, reason, context)
    print(reason, file=sys.stderr)
    sys.exit(2)

def resolve(p: str) -> str:
    return str(Path(os.path.expanduser(p)).resolve())

def inside(path: str, roots: list) -> bool:
    try:
        r = Path(os.path.expanduser(path)).resolve()
    except (OSError, RuntimeError):
        return False
    for root in roots:
        try:
            r.relative_to(root)
            return True
        except ValueError:
            continue
    return False

def is_secrets(p: str) -> bool:
    return bool(SECRETS_RE.search(os.path.basename(p)))

def tokenize(cmd: str) -> list:
    try:
        return shlex.split(cmd)
    except ValueError:
        return cmd.split()

def looks_like_path(token: str) -> bool:
    return token.startswith("/") or token.startswith("~") or token.startswith(".")
def check_bash(tool: str, cmd: str) -> None:
    if re.search(r"git\s+push\s+.*(-f\b|--force\b)", cmd):
        block(tool, "SECURITY: Force-push is blocked", cmd)

    if re.search(r"\bartisan\s+test\b", cmd):
        if not re.search(r"--filter|--testsuite|\btests/[\w/]", cmd):
            block(tool, "TEST POLICY: Full 'php artisan test' blocked during development. "
                        "Run tests with --filter, --testsuite, or a specific test file path instead.", cmd)

    if re.search(r"git\s+worktree\s+(add|remove|prune)", cmd):
        block(tool, "SECURITY: Git worktree operations are blocked. Work directly in the root project.", cmd)

    if re.search(r"git\s+add\s", cmd):
        if re.search(r"\.(env|key|pem|secret|keystore)\b|\.env\.", cmd, re.IGNORECASE):
            block(tool, "SECURITY: Cannot stage secrets files", cmd)

    for m in re.finditer(r">{1,2}\s*(/[^\s;|&>]+)", cmd):
        target = m.group(1)
        if target != "/dev/null" and not inside(target, WRITABLE_ROOTS):
            block(tool, "BOUNDARY: Redirect targets outside allowed directories", cmd)
    for subcmd in SHELL_OP_RE.split(cmd):
        subcmd = subcmd.strip()
        if not subcmd:
            continue

        tokens = tokenize(subcmd)
        if not tokens:
            continue

        cmd_idx = 0
        for i, t in enumerate(tokens):
            if "=" in t and not t.startswith("-") and t.index("=") > 0:
                continue
            cmd_idx = i
            break

        command = tokens[cmd_idx] if cmd_idx < len(tokens) else ""
        args = tokens[cmd_idx + 1:]

        if command in READ_CMDS:
            for arg in args:
                if arg.startswith("-"):
                    continue
                if looks_like_path(arg):
                    if not inside(arg, READABLE_ROOTS):
                        block(tool, "BOUNDARY: Shell read targets outside allowed directories", cmd)
                    if is_secrets(arg):
                        block(tool, "SECURITY: Cannot read secrets files via shell commands", cmd)
        if command in ("rm", "rmdir"):
            for arg in args:
                if arg.startswith("-"):
                    continue
                if looks_like_path(arg):
                    resolved = resolve(arg)
                    cwd = os.getcwd()
                    sdd_root = os.path.join(cwd, ".superpowers", "sdd")
                    parts = Path(resolved).parts
                    allowed = (
                        resolved.startswith(sdd_root + os.sep) or resolved == sdd_root
                        or "worktrees" in parts
                        or ".worktrees" in parts
                    )
                    if not allowed:
                        block(tool, "SECURITY: rm only allowed under .superpowers/sdd/ or worktrees/", cmd)

        elif command in DESTRUCTIVE_CMDS:
            for arg in args:
                if arg.startswith("-"):
                    continue
                if looks_like_path(arg):
                    if not inside(arg, WRITABLE_ROOTS):
                        block(tool, "BOUNDARY: Destructive command targets outside allowed directories", cmd)

        if command == "tee":
            for arg in args:
                if arg.startswith("-"):
                    continue
                if looks_like_path(arg):
                    if not inside(arg, WRITABLE_ROOTS):
                        block(tool, "BOUNDARY: tee targets outside allowed directories", cmd)
def main() -> None:
    raw = sys.stdin.read()
    try:
        data = json.loads(raw)
    except (json.JSONDecodeError, TypeError):
        sys.exit(0)

    tool = data.get("tool_name", "")
    inp = data.get("tool_input", {})

    NAME_MAP = {
        "read": "read_file", "write": "write_file", "edit": "edit_file",
        "bash": "shell_command",
    }
    tool_key = NAME_MAP.get(tool.lower(), tool)

    if tool in PASSTHROUGH_TOOLS or tool.startswith("mcp__") or tool_key.startswith("mcp__"):
        sys.exit(0)

    if tool_key in ("grep", "glob"):
        p = inp.get("path", "")
        if p and not inside(p, READABLE_ROOTS):
            block(tool_key, "BOUNDARY: Search outside allowed directories", p)
        sys.exit(0)

    if tool_key == "read_file":
        p = inp.get("absolute_path", "") or inp.get("file_path", "")
        if p:
            if not inside(p, READABLE_ROOTS):
                block(tool_key, "BOUNDARY: Read outside allowed directories", p)
            if is_secrets(p):
                block(tool_key, "SECURITY: Cannot read secrets file: " + os.path.basename(p), p)
        sys.exit(0)

    if tool_key in ("write_file", "edit_file"):
        p = inp.get("file_path", "")
        if p:
            if not inside(p, WRITABLE_ROOTS):
                block(tool_key, "BOUNDARY: Write outside allowed directories", p)
            if is_secrets(p):
                block(tool_key, "SECURITY: Cannot write secrets file: " + os.path.basename(p), p)
        sys.exit(0)

    if tool_key == "shell_command":
        cmd = inp.get("command", "")
        if cmd:
            check_bash(tool_key, cmd)
        sys.exit(0)

    sys.exit(0)

if __name__ == "__main__":
    main()