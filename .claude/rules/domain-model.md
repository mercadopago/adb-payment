---
paths:
  - "Model/**/*.php"
---

# Domain Model

Core domain layer containing payment methods, order/quote management, MP API integration, configuration, resource models, and UI data providers.

## Structure

- `Model/Adminhtml/` -- Admin-specific models
- `Model/Api/` -- API-related model logic
- `Model/Checks/` -- Validation checks
- `Model/Config/` -- Configuration readers and source models
- `Model/Console/` -- Console command support models
- `Model/Method/Vault.php` -- Vault payment method implementation
- `Model/Metrics/` -- Metrics instrumentation (see observability rule)
- `Model/MPApi/` -- Direct MercadoPago API integration layer
- `Model/Notification/` -- Notification processing models
- `Model/Order/` -- Order-related business logic
- `Model/Quote/` -- Quote-related business logic
- `Model/QuoteMpPayment.php` -- MP payment data model attached to quotes
- `Model/ResourceModel/` -- Database resource models
- `Model/Ui/` -- UI data providers for payment method renderers

## Conventions

- Models use constructor injection -- never use `ObjectManager` directly
- Resource models extend `\Magento\Framework\Model\ResourceModel\Db\AbstractDb`
- Collections extend `\Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection`
- Config models read from `etc/config.xml` paths via `\Magento\Framework\App\Config\ScopeConfigInterface`
- UI data providers implement `\Magento\Checkout\Model\ConfigProviderInterface`
- Notification models must be idempotent -- processing the same notification twice must be safe

## Rules

- Database operations go through resource models, not direct SQL
- Never store sensitive payment data (card numbers, CVV) in Magento DB
- Use Magento's `\Magento\Framework\Encryption\EncryptorInterface` for tokens
- Quote models must handle both logged-in and guest checkout flows
