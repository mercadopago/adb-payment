---
paths:
  - "Helper/**/*.php"
---

# Helper Classes

Stateless utility helpers for cross-cutting concerns.

## Existing Helpers

- `ApiTypeDetector.php` -- Determines the API type for a given payment context
- `HttpErrorCodeExtractor.php` -- Extracts error codes from HTTP responses
- `IdempotencyKeyGenerator.php` -- Generates unique idempotency keys for MP API requests
- `OrderApiHeadersBuilder.php` -- Builds HTTP headers for order API calls
- `OrderApiResponseValidator.php` -- Validates responses from the order API

## Conventions

- Helpers must be stateless -- no instance properties that change between calls
- All public methods must have PHPDoc annotations
- Prefer small, focused helpers over large utility classes
- Helpers are injected via constructor injection, never instantiated directly

## Rules

- Do NOT add business logic to helpers -- keep them as pure utility functions
- If a helper grows beyond 5-6 methods, consider splitting it
- Idempotency keys must be unique per request -- never reuse or cache them
- Error extraction must handle both SDK exceptions and raw HTTP response codes
