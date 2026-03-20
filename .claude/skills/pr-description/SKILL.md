---
name: pr-description
description: >-
  Generate a structured PR description from current branch changes and open
  the pull request using GitHub CLI. No copy-paste needed.
argument-hint: "[base-branch] [--lang es|en]"
allowed-tools: Bash, Read, Glob, Grep, AskUserQuestion
---

# Expert PR Description Generator

You are an expert in GitHub, code analysis, and technical communication. Your mission:
create PRECISE, CONCISE, and ACTIONABLE PR descriptions, and open the PR
directly from the terminal using GitHub CLI.

---

## Step 1: Parse Arguments and Resolve Language

Parse `$ARGUMENTS` to extract:
- `--lang es` or `--lang en` -- set `LANG` accordingly
- Remaining value (if any) -- use as `BASE_BRANCH`

If `--lang` is not provided, default to `LANG=es`.

## Step 1b: Resolve Base Branch

If `BASE_BRANCH` was extracted from arguments, use it directly.

If not, auto-detect:
```bash
git symbolic-ref refs/remotes/origin/HEAD 2>/dev/null | sed 's|refs/remotes/origin/||'
```

Fallback in order: `develop` then `main` then `master`.

## Step 2: Verify Prerequisites

```bash
gh auth status 2>&1 | head -3
```

Verify commits ahead of base:
```bash
git log origin/${BASE_BRANCH}..HEAD --oneline 2>/dev/null | wc -l
```

## Step 2.5: Check If PR Already Exists

```bash
gh pr view --json number,title,url,state 2>/dev/null
```

If open PR exists, ask: Update its description or cancel?

## Step 3: Identify Real Changes

```bash
git log --oneline --no-merges --first-parent origin/${BASE_BRANCH}..HEAD
git diff origin/${BASE_BRANCH}..HEAD --stat
git diff origin/${BASE_BRANCH}..HEAD -- . ':(exclude)*.lock' ':(exclude)composer.lock' | head -600
```

## Step 4: Generate Title and Description

Follow the format:
- Title: `[EMOJI] [TYPE]: [Description in 8 words max]`
- General description: 3-4 lines (WHAT, WHY, impact)
- Changes made: grouped by significance (UI > functional > refactor > tests)
- Metrics: files new/modified, endpoints/components affected
- Impact: affected endpoints, breaking changes, config requirements

## Step 4.5: Merge with Repo PR Template

Check for `PULL_REQUEST_TEMPLATE.md` in root or `.github/`.

## Step 5: Confirm with User

Show generated title and description, ask for confirmation.

## Step 6: Push and Create PR

```bash
git push -u origin HEAD 2>&1
gh pr create --base "${BASE_BRANCH}" --title "{title}" --body "{description}"
```

## Rules

- NEVER force push
- NEVER open a PR without user confirmation
- NEVER include raw diffs in the description
