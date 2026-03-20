---
paths:
  - "Tests/Unit/**/*.php"
  - "Tests/coverage-checker.php"
  - "Tests/pr-coverage.php"
---

# Unit Tests

PHPUnit unit tests mirroring the full module structure, plus coverage enforcement scripts.

## Structure

Tests mirror the module directory layout:
- `Tests/Unit/Block/` -- Block class tests
- `Tests/Unit/Console/` -- Console command tests
- `Tests/Unit/Controller/` -- Controller tests
- `Tests/Unit/Cron/` -- Cron job tests
- `Tests/Unit/Gateway/` -- Gateway layer tests (Config, Http, Request, Response)
- `Tests/Unit/Helper/` -- Helper tests
- `Tests/Unit/Model/` -- Model tests
- `Tests/Unit/Observer/` -- Observer tests

## Mock Data

- `Tests/Unit/Mocks/Gateway/` -- Gateway mock data (API responses, request payloads)
- `Tests/Unit/Mocks/Model/` -- Model mock data
- `Tests/Unit/Mocks/Notification/` -- Notification mock data
- `Tests/Unit/Mocks/PaymentRefundMock.php` -- Refund mock
- `Tests/Unit/Mocks/PaymentRequestMock.php` -- Payment request mock
- `Tests/Unit/Mocks/PaymentResponseMock.php` -- Payment response mock

## Coverage Scripts

- `Tests/coverage-checker.php` -- Validates overall coverage threshold
- `Tests/pr-coverage.php` -- Validates coverage for PR-changed files

## Running Tests

```bash
bash bin/run-test.sh
```
This runs inside Docker: `docker exec magento-php magento2/vendor/phpunit/phpunit/phpunit --configuration magento2/app/code/MercadoPago/AdbPayment/phpunit.xml`

## Conventions

- Test class names: `{ClassName}Test.php` in the matching directory
- Use PHPUnit's `MockObject` for mocking dependencies
- Reuse mock data from `Tests/Unit/Mocks/` -- do not duplicate mock payloads
- Each test method tests one behavior: `testMethodName_condition_expectedResult`

## Rules

- New PHP classes MUST have corresponding unit tests
- Gateway request builders and response handlers are the highest-priority test targets
- Mock MP API responses using files in `Tests/Unit/Mocks/Gateway/`
- Never make real HTTP calls in unit tests -- always mock the HTTP client
- Coverage must pass `Tests/coverage-checker.php` threshold before merging
