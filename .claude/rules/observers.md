---
paths:
  - "Observer/**/*.php"
---

# Event Observers

Magento event observers reacting to checkout and order lifecycle events.

## Existing Observers

- `ChangeConfigModule.php` -- Reacts to module configuration changes
- `CovertFinanceCostToOrderObserver.php` -- Converts finance cost data from quote to order
- `DataAssignCheckoutCustomObserver.php` -- Assigns custom checkout data to payment
- `DataAssignObserverCc.php` -- Assigns credit card data to payment info
- `DataAssignObserverCcVault.php` -- Assigns vault credit card data
- `DataAssignObserverTwoCc.php` -- Assigns two credit cards data (split payment)
- `DataAssignObserverYape.php` -- Assigns Yape payment data
- `OrderCancelAfterObserver.php` -- Handles post-cancellation logic
- `PaymentMethodAvailable.php` -- Controls payment method availability dynamically

## Conventions

- Observers implement `\Magento\Framework\Event\ObserverInterface`
- One observer per event -- do not combine multiple event reactions
- Register in `etc/events.xml` (global) or `etc/frontend/events.xml` (storefront only)
- Observer names in XML must match class names for traceability
- Use `$observer->getEvent()->getData('key')` to read event data

## Rules

- Observers must be lightweight -- delegate heavy logic to service classes in Model/
- Never throw exceptions from observers (can break checkout flow)
- DataAssign observers must validate data before assigning to payment info
- Finance cost observer must handle currency conversion correctly
- PaymentMethodAvailable must not block checkout if MP API is unreachable
