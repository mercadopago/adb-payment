---
type: Architecture
version: 10fa0759
validated: 2026-07-31
update_when: when folder layout, layers, entrypoints, the payment gateway flow, or the observability/resilience patterns change
scope:
  - Api
  - Block
  - Console
  - Controller
  - Cron
  - Gateway
  - Helper
  - Model
  - Observer
  - Plugin
  - Setup
  - view
  - etc
---

# Architecture — pp-adb-payment

This is a standard Adobe Commerce / Magento 2 module. All code lives at the module root with PSR-4 autoloading under the `MercadoPago\AdbPayment\` namespace. Layers follow Magento conventions and each layer has an auto-loaded rule in [`.claude/rules/`](../../.claude/rules) scoped by path.

## Folder layout

| Directory | Layer | What it holds |
|---|---|---|
| `Api/` | Service contracts | PHP interfaces for repositories and management services (service contract layer used by `etc/webapi.xml` for REST routing) |
| `Api/Data/` | Data transfer objects | Interfaces for domain data objects (FinanceCost, QuoteMpPayment, RulesForFinanceCost) |
| `Block/` | Presentation | PHP block classes for storefront (checkout) and admin (sales info, form, system config) rendering |
| `Console/Command/` | CLI | `bin/magento` commands — administrative (FetchMerchantInfo, PaymentExpirations) and notification-trigger (FetchOrderStatus, CheckoutCreditsAddChild, CheckoutProAddChild) |
| `Controller/` | HTTP dispatch | Storefront front-controllers for webhook notifications (`Notification/`), QR code generation (`Index/`), and a generic REST handler (`Rest.php`) |
| `Cron/` | Async/scheduled | Cron jobs polling MercadoPago API for payment status (Pix, PSE, Webpay, Yape, Ticket/offline, Checkout Pro, Credits) and fetching merchant info |
| `Gateway/` | Payment Gateway | Magento Payment Gateway integration: commands, HTTP client, request builders (`Request/`), response handlers (`Response/`), validators (`Validator/`), and config (`Config/`) |
| `Helper/` | Utilities | Stateless helper classes: API type detection, error extraction, idempotency keys, order API headers, CPF/CNPJ check-digit validation (`DocumentValidator`, MLB) |
| `i18n/` | Localization | Translation CSV files for es_AR, es_MX, es_CL, es_CO, es_PE, es_UY, pt_BR, en_US (8 locales + base) |
| `Model/` | Domain | Core domain: payment method models, order/quote management, MP API integration, configuration providers, resource models, UI data providers, Metrics client |
| `Model/Metrics/` | Observability | Metrics client and adapter for instrumenting payment operations |
| `Observer/` | Event listeners | One observer per event: checkout data assignment, finance-cost conversion, payment availability, order cancellation |
| `Plugin/` | Interceptors | DI plugins: address validation, order amount limits, payment tokens, vault initialization (registered in `etc/di.xml`) |
| `Setup/Patch/` | Migrations | Data and schema patches for module installation and upgrades |
| `Tests/Unit/` | Tests | PHPUnit unit tests mirroring module structure; mock data in `Tests/Unit/Mocks/` |
| `view/frontend/` | Frontend assets | Storefront PHTML templates, RequireJS modules with Knockout.js bindings, layout XML |
| `view/adminhtml/` | Admin assets | Admin panel templates, JS components, layout XML |
| `etc/` | Configuration | DI (`di.xml`), web API routing (`webapi.xml`), events, cron, payment, DB schema, CSP rules, error mappings |
| `etc/frontend/` | Frontend-area config | Frontend-only DI, events, routes |
| `etc/adminhtml/` | Admin-area config | Admin-only DI, events, routes, system config |
| `build/` | Dev environment | Docker Compose setup for local Magento installation (nginx, PHP, MariaDB, Redis, Elasticsearch) |
| `bin/` | Dev scripts | Shell scripts for building, testing, linting, and syncing to remote environments |

## Layers (and where they live)

| Layer | Directory | Responsibility | Rule |
|---|---|---|---|
| Service contracts | `Api/` | Interfaces for Web API + repositories (data/management interfaces) | `api-interfaces.md` |
| Front controllers | `Controller/` | Storefront notification endpoints, REST entry, QR generation, checkout redirects | `api-handlers.md` |
| Payment Gateway | `Gateway/` | Magento Payment Gateway pattern: commands, HTTP clients, request builders, response handlers, validators; wires `mp-plugins/php-sdk` | `payment-gateway.md` |
| Domain model | `Model/` | Payment methods, order/quote management, MP API integration, config, resource models, UI data providers | `domain-model.md` |
| Observability | `Model/Metrics/` | Metrics client + adapter for instrumenting payment operations | `observability.md` |
| Helpers | `Helper/` | Stateless utilities: API type detection, error extraction, idempotency keys, order headers | `helper.md` |
| Observers | `Observer/` | React to checkout/order lifecycle events (one observer per event) | `observers.md` |
| Plugins (interceptors) | `Plugin/` | Address validation, order amount limits, payment tokens, vault init | `plugins.md` |
| Cron | `Cron/` | Poll external payment status (Pix, PSE, Webpay, Yape, Pro/Credits) and cancel stale orders | `cron-jobs.md` |
| Console | `Console/Command/` | CLI commands via `bin/magento` | `console-commands.md` |
| Setup patches | `Setup/Patch/` | Install/upgrade data & schema patches | `setup-patches.md` |
| Config | `etc/` | DI (`di.xml`), Web API routing (`webapi.xml`), events, cron, payment, DB schema, CSP | `module-config.md` |
| Storefront view | `view/frontend/` | PHTML, RequireJS/Knockout.js JS components, layout XML | `frontend-view.md` |
| Admin view | `view/adminhtml/` | Admin templates, JS, layout XML | `adminhtml-view.md` |
| i18n | `i18n/` | Translation CSVs (es_*, pt_BR, en_US) | `i18n.md` |

## Low-signal / generated areas (safe to skip; don't hand-edit)

- **`vendor/`** — Composer-installed dependencies; regenerate via `composer install` inside the Docker container.
- **`build/logs/`** — Docker runtime logs; not tracked in git.
- **`coverage/`** — PHPUnit coverage output; regenerate via `bash bin/run-test.sh`.
- **`test-results/`** — Test result artifacts.
- **`Tests/Unit/Mocks/`** — These are hand-written mocks, **not generated code**; see [traps.md](traps.md) for the deliberate deviation note.

## Entrypoints / exposed surface

This module has no independent entrypoint — Magento bootstraps it. The effective entrypoints are:

1. **REST API handlers** — service classes wired in `etc/webapi.xml`; Magento routes HTTP requests to them.
2. **Storefront controllers** — under the `/mp/` frontend route (registered in `etc/frontend/routes.xml`); handle webhook POST callbacks from MercadoPago platform and QR code generation.
3. **Cron jobs** — 8 jobs in the `mercadopago_adbpayment` cron group (see `etc/crontab.xml`); Magento cron triggers them on schedule.
4. **Console commands** — 5 `bin/magento` commands registered via DI; executable manually or from scripts.
5. **Payment Gateway commands** — invoked by Magento's payment processing pipeline when a buyer places an order; entry via DI-wired command pool in `etc/di.xml`.

## Request / data flow

**Online payment (e.g. card checkout):**
1. Buyer submits checkout form. For Checkout Custom (card/two-card), `beforePlaceOrder` (`view/frontend/web/js/view/payment/method-renderer/cc.js`, `twocc.js`) calls the shared `_runPreSubmitGate()` (`view/frontend/web/js/view/payment/mp-sdk.js`), which validates the form (jQuery), installments and card number (Luhn+length) before tokenizing; only then Magento triggers the payment gateway command pool.
2. `Gateway/Request/` builders assemble the MP API payload from the order/quote.
3. `Gateway/Http/` client (wrapping `mp-plugins/php-sdk`) sends the request to MercadoPago API.
4. `Gateway/Response/` handlers process the API response, updating the order transaction.
5. `Gateway/Validator/` validates the response code.

**Async status reconciliation (webhook path):**
1. MercadoPago platform POSTs a webhook to `/mp/notification/<method>`.
2. `Controller/Notification/<Method>.php` receives it, validates it, and triggers the appropriate model update.
3. `Model/` updates order status; `Observer/` may fire additional events.

**Async status reconciliation (cron path):**
1. Magento cron triggers the relevant `Cron/Fetch*.php` job on schedule.
2. The cron class calls the MP API via `mp-plugins/php-sdk`, fetches current payment status, and updates orders accordingly.

**REST API call (e.g. payment status query):**
1. Storefront JS calls `/V1/payment/mp-payment-status` (or another `webapi.xml` route).
2. Magento routes to the service class implementing the corresponding `Api/*ManagementInterface`.
3. Service reads from Model/database and returns the response.

## Payment data flow (synchronous authorization)

```
Storefront checkout (Knockout.js, view/frontend/)
  → Magento Payment method (Model/…/Payment)
    → Gateway/Command/PaymentCommand
      → Gateway/Request/*  (build MP API payload from Magento subject; idempotency key added)
        → Gateway/Http/Client/*  (single point that calls mp-plugins/php-sdk → MercadoPago API)
      → Gateway/Response/*  (map MP response back onto Magento payment/order)
      → Gateway/Validator/*  (success/error decision; errors mapped via etc/mercadopago_error_mapping.xml)
  → Order placed (Sales) with payment status
```

## Asynchronous / offline flow (Pix, PSE, Webpay, Yape, Ticket)

The order is created **pending**; MercadoPago notifies via storefront notification
controllers (`Controller/Notification/*`), and `Cron/Fetch*OrderStatus` jobs reconcile the
status on a schedule (see `contracts.md` for the crontab). `Cron/Cancel*` jobs cancel stale
pending orders.

## Architectural invariants (verifiable, with file references)

- **All outbound MercadoPago calls go through `Gateway/Http/`.** Builders/models/controllers
  never call the SDK directly — see the wiring in
  [`Gateway/Command/PaymentCommand.php`](../../Gateway/Command/PaymentCommand.php) and the
  clients in [`Gateway/Http/Client/`](../../Gateway/Http/Client).
- **API requests carry an idempotency key.** Generated by
  [`Helper/`](../../Helper) (idempotency-key helper) and attached to request headers, so a
  retried request does not double-charge.
- **Request/response builders are composed via DI**, not hand-instantiated — see
  `BuilderComposite` entries in [`etc/di.xml`](../../etc/di.xml).
- **MP API client timeout is declared but NOT currently wired.**
  `Config::getClientConfigs()` returns `'timeout' => 45`
  ([`Gateway/Config/Config.php`](../../Gateway/Config/Config.php)), but that method has no
  caller — the SDK clients are built as `new HttpClient($baseUrl, new CurlRequester())`
  without applying it, so the 45s bound is **not enforced**. Set the timeout explicitly when
  adding or debugging a client.
- **Metrics never block or break the payment flow** — the metrics HTTP client uses a short
  `2s` timeout and failures are swallowed (see Observability below).
- **User-facing strings are translated.** Every string uses `__('…')` and has an entry in all
  [`i18n/*.csv`](../../i18n) files.

## Observability (real examples from this module)

Metrics are centralized in `Model/Metrics/`. `MetricsClient` is the single entry point and it
**catches and logs** any failure so instrumentation can never break a payment
([`Model/Metrics/MetricsClient.php`](../../Model/Metrics/MetricsClient.php)):

```php
public function sendEvent(string $eventType, $value, ?string $message = null): void
{
    try {
        $this->adapter->sendEvent($eventType, $value, $message);
    } catch (\Exception $e) {
        $this->logger->error(
            'Metrics client failed',
            [
                'error' => $e->getMessage(),
                'event_type' => $eventType
            ]
        );
    }
}
```

The adapter ships events to Datadog through the Core Monitor over HTTP with a **hard 2-second
timeout** ([`Model/Metrics/CoreMonitorAdapter.php`](../../Model/Metrics/CoreMonitorAdapter.php)):

```php
private const HTTP_TIMEOUT = 2; // seconds
// ...
$this->httpClient->setTimeout(self::HTTP_TIMEOUT);
```

Convention (from `.claude/rules/observability.md`): all payment operations record both
success and failure counts plus latency, tagged with the payment-method type.

## Resilience (real examples from this module)

- **Fail-open instrumentation:** `MetricsClient::sendEvent()` above — a monitoring outage
  degrades observability, never the checkout.
- **Bounded timeouts:** the metrics client applies a real `2s` timeout
  (`CoreMonitorAdapter::HTTP_TIMEOUT` → `setTimeout(2)`). Note: the `45s` MP value in
  `Config::getClientConfigs()` is **declared but not wired** into the SDK clients, so it is
  not actually enforced (see the invariants note above).
- **Idempotency for safe retries:** every MP API request includes an idempotency key
  (`Helper/`), so retries of create/capture/refund do not duplicate side effects.
- **Reconciliation instead of blocking:** offline/async payments are not awaited inline —
  `Cron/Fetch*OrderStatus` polls MercadoPago and updates order status later, and
  `Cron/Cancel*` cleans up stale pending orders (`etc/crontab.xml`).
