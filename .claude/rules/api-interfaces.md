---
paths:
  - "Api/**/*.php"
---

# API Interfaces (Service Contracts)

These are Magento service contract interfaces defining the module's public API surface.

## Conventions

- Every interface must be in the `MercadoPago\AdbPayment\Api\` namespace
- Data interfaces go in `Api/Data/` -- these define DTOs for the service layer
- Management interfaces define business operations (e.g., `PaymentStatusManagementInterface`)
- Repository interfaces define CRUD operations (e.g., `QuoteMpPaymentRepositoryInterface`)

## Existing Interfaces

- `CreateVaultManagementInterface` -- Vault token creation
- `FinanceCostManagementInterface` -- Finance cost calculations
- `GuestFinanceCostManagementInterface` -- Guest checkout finance costs
- `PayInfoManagementInterface` -- Payment info management
- `PaymentStatusManagementInterface` -- Payment status queries
- `QuoteMpPaymentManagementInterface` -- Quote-level MP payment management
- `QuoteMpPaymentRepositoryInterface` -- Quote MP payment CRUD

## Rules

- All public methods MUST have PHPDoc with `@param`, `@return`, and `@throws` annotations
- Use Magento type hints: `\Magento\Framework\Api\SearchCriteriaInterface` for search
- Interfaces MUST NOT contain implementation logic
- Method signatures must use scalar types or other interfaces -- never concrete classes
- New interfaces must be registered as preferences in `etc/di.xml`
- REST-exposed interfaces must be declared in `etc/webapi.xml`
- Guest-facing interfaces must have separate `Guest*` variants with different ACL
