---
paths:
  - "Controller/**/*.php"
---

# Controllers (API Handlers)

Magento front-controllers handling HTTP requests for storefront notifications, REST endpoints, and checkout redirects.

## Structure

- `Controller/Index/` -- Main index controllers
- `Controller/Notification/` -- Payment notification webhooks from MercadoPago
- `Controller/MpIndex.php` -- MercadoPago-specific index controller
- `Controller/Rest.php` -- REST API controller

## Conventions

- Controllers extend `\Magento\Framework\App\Action\Action` or implement `\Magento\Framework\App\ActionInterface`
- One action per controller class (Magento single-action controller pattern)
- Use constructor injection for all dependencies
- Validate request parameters before processing
- Return `\Magento\Framework\Controller\ResultInterface` (JSON, redirect, or page)
- Notification controllers must verify webhook signatures before processing
- Log all incoming notification payloads at debug level

## Security

- Never trust input from payment notifications without signature verification
- CSRF protection: storefront controllers must use `\Magento\Framework\App\CsrfAwareActionInterface`
- Admin controllers must check ACL permissions via `_isAllowed()`
- Never expose internal payment IDs or tokens in error responses
