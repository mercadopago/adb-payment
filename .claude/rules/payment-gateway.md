---
paths:
  - "Gateway/**/*.php"
---

# Payment Gateway Layer

The largest layer in the module (131 files). Implements the Magento Payment Gateway pattern, wiring `mp-plugins/php-sdk` to the Magento payment flow.

## Structure

- `Gateway/Command/PaymentCommand.php` -- Entry point for gateway command execution
- `Gateway/Config/` -- Gateway configuration (payment method configs, feature flags)
- `Gateway/Data/` -- Data transfer objects for gateway operations
- `Gateway/Http/` -- HTTP client for MercadoPago API communication via the PHP SDK
- `Gateway/Request/` -- Request builders that transform Magento data into MP API payloads
- `Gateway/Response/` -- Response handlers that process MP API responses back into Magento
- `Gateway/Validator/` -- Validators for gateway responses (success/error checks)
- `Gateway/SubjectReader.php` -- Helper to read payment subject data

## Conventions

- Request builders implement `\Magento\Payment\Gateway\Request\BuilderInterface`
- Response handlers implement `\Magento\Payment\Gateway\Response\HandlerInterface`
- Validators implement `\Magento\Payment\Gateway\Validator\AbstractValidator`
- All builders are composed via `BuilderComposite` in `etc/di.xml`
- Use `SubjectReader` to safely extract data from the payment subject array
- Never call the MP SDK directly from a builder -- use `Gateway/Http/` client
- Each payment method (Checkout Pro, Custom CC, Pix, PSE, etc.) has its own set of request builders

## Testing

Tests mirror this structure under `Tests/Unit/Gateway/`:
- `Tests/Unit/Gateway/Config/` -- Config unit tests
- `Tests/Unit/Gateway/Http/` -- HTTP client tests
- `Tests/Unit/Gateway/Request/` -- Request builder tests
- `Tests/Unit/Gateway/Response/` -- Response handler tests
- Mock data in `Tests/Unit/Mocks/Gateway/`

## Rules

- Never bypass the gateway pattern by calling MP API directly from Models or Controllers
- All API requests must include idempotency keys (via `Helper/IdempotencyKeyGenerator`)
- Error responses must be mapped through `etc/mercadopago_error_mapping.xml`
- Gateway config values come from `etc/config.xml` defaults and admin settings
