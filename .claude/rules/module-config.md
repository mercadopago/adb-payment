---
paths:
  - "etc/**/*.xml"
  - "etc/**/*.json"
---

# Module Configuration

Magento module configuration files that define DI, routing, events, cron, payment, DB schema, and CSP rules.

## Key Files

- `etc/module.xml` -- Module declaration with dependencies (Customer, Payment, Checkout, Vault, Catalog, Quote, Sales)
- `etc/di.xml` -- Dependency injection: preferences, virtual types, plugin declarations, argument overrides
- `etc/webapi.xml` -- REST/SOAP API route definitions
- `etc/events.xml` -- Global event observer registrations
- `etc/crontab.xml` -- Cron job schedule definitions
- `etc/cron_groups.xml` -- Cron group configuration
- `etc/config.xml` -- Default configuration values
- `etc/payment.xml` -- Payment method declarations
- `etc/db_schema.xml` -- Declarative database schema
- `etc/db_schema_whitelist.json` -- Schema whitelist for safe column operations
- `etc/acl.xml` -- Admin ACL resource definitions
- `etc/sales.xml` -- Sales totals configuration
- `etc/fieldset.xml` -- Data conversion fieldsets (quote to order)
- `etc/extension_attributes.xml` -- Extension attribute declarations
- `etc/csp_whitelist.xml` -- Content Security Policy whitelisted domains
- `etc/mercadopago_error_mapping.xml` -- Error code to message mappings
- `etc/pdf.xml` -- PDF generation configuration

## Area-Specific Config

- `etc/frontend/` -- Frontend-only DI, events, routes
- `etc/adminhtml/` -- Admin-only DI, events, routes, system configuration

## Rules

- Always run `bin/magento setup:di:compile` after changing `di.xml` to verify wiring
- Virtual types must have unique names -- prefix with `MercadoPago` to avoid conflicts
- New payment methods must be declared in both `etc/payment.xml` and `etc/di.xml`
- Schema changes must update `etc/db_schema_whitelist.json` (run `bin/magento setup:db-declaration:generate-whitelist`)
- CSP whitelist updates require justification -- only add domains the module genuinely needs
