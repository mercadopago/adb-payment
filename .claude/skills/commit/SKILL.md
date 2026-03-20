---
name: commit
description: >-
  Validate and commit changes. Runs lint and test checks,
  then commits with a conventional message.
argument-hint: "[commit message or empty for auto-generated]"
allowed-tools: Bash, Read, Glob, Grep, Task, AskUserQuestion
---

# Commit Changes

Validate and commit staged changes with pre-commit verification.

## Step 1: Pre-flight -- Check for changes

```bash
git status --short
```

If no changes exist, stop and inform the user.

## Step 2: Stage changes

- Show unstaged and staged files
- Ask the user what to stage (if not already staged)
- Prefer `git add <specific-files>` over `git add -A`
- NEVER stage `.env`, credentials, or Docker volumes
- If user says "all", use `git add -A` but warn about sensitive files first

## Step 3: Run validations

Run lint and test checks sequentially:

### Lint check
```bash
bash bin/run-linters.sh
```

### Test check
```bash
bash bin/run-test.sh
```

### Handling failures

- **Lint fails** -- show the issues and ask: "Fix lint issues manually? [Yes/No]"
- **Tests fail** -- show failing test names and abort
- **ALL pass** -- proceed to Step 4

## Step 4: Generate commit message

- If `$ARGUMENTS` is provided, use it as the commit message
- If no arguments, analyze the staged diff and generate a conventional commit:
  - `feat:` for new features
  - `fix:` for bug fixes
  - `refactor:` for code restructuring
  - `test:` for test changes only
  - `docs:` for documentation only
  - `chore:` for maintenance, deps, config
- Keep the first line under 72 characters
- Add a body (after blank line) only if the change needs explanation
- Show the proposed message and ask for confirmation

## Step 5: Commit

```bash
git commit -m "{message}"
```

If pre-commit hooks run and fail, show the output and ask the user how to proceed.
Do NOT use `--no-verify`.

## Step 6: Post-commit

Show: commit hash, files committed, branch name.

## Rules

- NEVER use `--no-verify` to skip pre-commit hooks
- NEVER commit files that contain secrets or credentials
- NEVER use `git add -A` without explicit user confirmation
- ALL validations MUST pass before committing -- no exceptions
