---
paths:
  - "e2e/**/*.js"
---

# End-to-End Tests

Playwright E2E tests covering all payment flows.

## Structure

- `e2e/tests/checkoutcustom/` -- Checkout Custom (CC, debit) flows
- `e2e/tests/checkoutpro/` -- Checkout Pro redirect flows
- `e2e/tests/chocredits/` -- Checkout Credits flows
- `e2e/tests/pix/` -- Pix payment flows
- `e2e/tests/pse/` -- PSE payment flows (Colombia)
- `e2e/tests/ticket/` -- Ticket/boleto payment flows
- `e2e/tests/yape/` -- Yape payment flows (Peru)
- `e2e/helpers.js` -- Shared test utilities
- `e2e/data/` -- Test data fixtures
- `e2e/flows/` -- Reusable flow definitions

## Configuration

- `e2e/playwright.config.js` -- Playwright configuration
- `e2e/package.json` -- Node.js dependencies

## Running

```bash
cd e2e && npx playwright test
cd e2e && npx playwright test tests/pix/  # specific payment method
```

## Rules

- Each payment method has its own test directory -- do not mix flows
- Use shared helpers from `e2e/helpers.js` for common actions (fill address, select payment)
- Test data in `e2e/data/` -- never hardcode card numbers or credentials in test files
- E2E tests require a running Magento instance with the module installed
- Tests must be independent -- no test should depend on another test's state
- Add new payment method E2E tests when adding new payment methods to the module
