---
paths:
  - "view/frontend/templates/**/*.phtml"
  - "view/frontend/web/**/*"
  - "view/frontend/layout/**/*.xml"
  - "view/frontend/requirejs-config.js"
  - "Block/**/*.php"
---

# Frontend View Layer

Storefront presentation: PHTML templates, RequireJS/JS components, layout XML, and Block classes for checkout and customer-facing pages.

## Structure

- `view/frontend/templates/` -- PHTML templates rendered by Block classes
- `view/frontend/web/js/action/` -- JS action modules (API calls, checkout steps)
- `view/frontend/web/js/mixin/` -- Magento JS mixins for extending core checkout
- `view/frontend/web/js/view/` -- Knockout.js view components for checkout UI
- `view/frontend/layout/` -- Layout XML defining page structure and block assignments
- `view/frontend/requirejs-config.js` -- RequireJS module configuration and mixins
- `Block/` -- PHP block classes providing data to templates

## Conventions

- JS modules follow RequireJS AMD pattern: `define(['dep1', 'dep2'], function(dep1, dep2) { ... })`
- Knockout.js templates use `<!-- ko -->` bindings for dynamic UI
- Block classes extend `\Magento\Framework\View\Element\Template`
- Layout XML uses handles matching route/controller/action pattern
- Mixins declared in `requirejs-config.js` for extending core Magento JS

## Payment Method Renderers

Each payment method has its own JS view component in `view/frontend/web/js/view/`:
- Checkout Pro, Checkout Custom (CC, two CCs), Credits, Pix, PSE, Ticket, Yape

## Rules

- Always escape output in PHTML: `$block->escapeHtml()`, `$block->escapeUrl()`
- Never put business logic in templates -- delegate to Block classes
- JS components must clean up event listeners on destroy to prevent memory leaks
- All user-facing strings must use `$t('string')` (JS) or `__('string')` (PHP) for i18n
- CSP (Content Security Policy) rules for external scripts go in `etc/csp_whitelist.xml`
- Test checkout flows in all supported payment methods after any JS change
