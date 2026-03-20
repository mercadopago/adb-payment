---
paths:
  - "view/adminhtml/templates/**/*.phtml"
  - "view/adminhtml/web/**/*"
  - "view/adminhtml/layout/**/*.xml"
  - "view/adminhtml/requirejs-config.js"
---

# Admin Panel View Layer

Admin panel presentation: templates, JS, and layout XML for back-office configuration and order management screens.

## Structure

- `view/adminhtml/templates/` -- Admin PHTML templates
- `view/adminhtml/web/` -- Admin JS and CSS assets
- `view/adminhtml/layout/` -- Admin layout XML
- `view/adminhtml/requirejs-config.js` -- Admin RequireJS configuration

## Conventions

- Admin layout handles follow `<route_id>_<controller>_<action>.xml` pattern
- System configuration UI is defined in `etc/adminhtml/system.xml`
- Admin blocks must check ACL resources before rendering sensitive data
- Admin JS follows the same RequireJS/Knockout patterns as frontend

## Rules

- Admin templates must escape all output (`escapeHtml`, `escapeUrl`, `escapeJs`)
- Never expose API keys or secrets in admin panel HTML source
- Configuration fields with sensitive values must use `<backend_model>Magento\Config\Model\Config\Backend\Encrypted</backend_model>`
- Admin layout changes must not break core Magento admin pages
