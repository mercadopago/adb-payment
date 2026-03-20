---
name: fix-lint
description: >-
  Fix lint issues in the project. Runs PHPCS, PHPStan, and PHPMD
  checks on the codebase. Use when you need to clean up code style.
argument-hint: "[file path or 'all']"
allowed-tools: Bash, Read, Glob, Grep
---

# Fix Lint Issues

## Commands
- **All linters:** `bash bin/run-linters.sh`
- **PHPCS only:** `bash bin/run-phpcs.sh`
- **PHPStan only:** `bash bin/run-phpstan.sh`
- **PHPMD only:** `bash bin/run-phpmd.sh`

## Instructions

1. If `$ARGUMENTS` specifies a file, focus lint analysis on that file
2. If `$ARGUMENTS` is "all" or empty, run the full linter suite: `bash bin/run-linters.sh`
3. For PHPCS issues: most can be auto-fixed by applying Magento2 standard patterns
4. For PHPStan issues: check type annotations and return types
5. For PHPMD issues: check method complexity, naming, unused code
6. Report:
   - Files with issues
   - Remaining issues that need manual fix (with file:line and description)
   - Magento2 coding standard rule that was violated
