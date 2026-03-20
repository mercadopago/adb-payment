---
name: review-changes
description: >-
  Review staged or unstaged changes before committing. Runs lint
  and tests on changed files and provides a summary.
argument-hint: ""
allowed-tools: Bash, Read, Glob, Grep
---

# Review Changes Before Commit

## Instructions

1. **Identify changes:** `git diff --name-only HEAD`
2. **Run linters** on changed files: `bash bin/run-linters.sh`
3. **Run tests** related to changed files: `bash bin/run-test.sh`
4. **Review the diff** for:
   - Debug statements left in code (`var_dump`, `print_r`, `error_log`)
   - TODO comments without ticket references
   - Hardcoded values that should be in `etc/config.xml`
   - Missing error handling in Gateway request/response handlers
   - Security concerns (exposed API keys, missing input validation)
   - Missing translations in `i18n/*.csv` for new user-facing strings
   - Missing PHPDoc on public methods
5. **Report:**
   - Lint status (PHPCS, PHPStan, PHPMD -- pass/fail with details)
   - Test status (pass/fail with details)
   - Code review findings (grouped by severity)
   - Recommendation: safe to commit or needs fixes

## Rules
- Do NOT commit automatically -- only review and report
- Be specific about issues: file, line number, what's wrong, suggested fix
- Check that new payment-related code has corresponding unit tests
