---
paths:
  - "Plugin/**/*.php"
---

# Interceptor Plugins

Magento interceptor plugins that modify core behavior via before/after/around methods.

## Existing Plugins

- `AddressConfigValidation.php` -- Validates address configuration for MP payments
- `MaximumAmountOrderValidation.php` -- Enforces maximum order amount limits
- `PaymentToken.php` -- Handles payment token processing for vault
- `VaultIsInitializeNeeded.php` -- Controls vault initialization behavior

## Conventions

- Plugins are registered in `etc/di.xml` with `<plugin>` entries
- Method names follow Magento convention: `beforeMethodName`, `afterMethodName`, `aroundMethodName`
- Around plugins must always call `$proceed()` unless intentionally blocking execution
- The first parameter of before/after plugins is the subject (intercepted class instance)

## Rules

- Prefer before/after plugins over around plugins (better performance and composability)
- Never use plugins to replace core logic entirely -- use preferences in `di.xml` instead
- Plugin sort order matters: check existing plugins in `etc/di.xml` before adding new ones
- Address validation plugin must handle all supported countries (AR, MX, BR, CL, CO, PE, UY)
- Amount validation must use the correct currency for comparison
