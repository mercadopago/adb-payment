---
name: run-tests
description: >-
  Run the project test suite. Supports running all tests or
  targeting specific modules/files. Use when you need to verify
  changes or check test status.
argument-hint: "[module or file path, e.g. 'Gateway' or 'e2e/tests/pix']"
allowed-tools: Bash, Read, Glob, Grep
---

# Run Tests

Run tests for the pp-adb-payment MercadoPago module.

## Commands
- **Full unit suite:** `bash bin/run-test.sh`
- **E2E tests:** `cd e2e && npx playwright test`
- **E2E specific method:** `cd e2e && npx playwright test tests/{method}/`

## Instructions

1. If `$ARGUMENTS` specifies a module or file, run tests for that scope only
2. If `$ARGUMENTS` mentions a payment method (pix, pse, yape, etc.), run E2E: `cd e2e && npx playwright test tests/{method}/`
3. If no arguments, run the full unit test suite: `bash bin/run-test.sh`
4. Parse output and report:
   - Number of tests passed/failed/skipped
   - For failures: test name, error message, file location
   - Suggest fixes for obvious failures
5. If tests fail, do NOT proceed with any commits or pushes
