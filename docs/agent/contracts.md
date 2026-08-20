---
type: Contracts
version: 10fa0759
validated: 2026-07-31
update_when: when the exposed REST routes, storefront controllers, cron jobs, or the outbound MP SDK dependency changes
scope:
  - etc/webapi.xml
  - Controller
  - Cron
  - Console
  - Api
  - Gateway
  - composer.json
---

# Contracts — pp-adb-payment

> This module is a **Magento 2 module**, not a standalone HTTP service. Its "public surface"
> is a set of Magento Web API routes plus storefront front-controllers, both hosted by the
> merchant's Adobe Commerce installation. There is no independently deployable server here.

## Exposed — sync (REST API)

Routes defined in `etc/webapi.xml`. Auth column reflects the `<resource ref="...">` value: `self` = authenticated Magento customer session; `anonymous` = no auth required. Interface definitions live in [`Api/`](../../Api); routes are declared in [`etc/webapi.xml`](../../etc/webapi.xml).

| Method | Path | Service interface (`Api/`) | Caller intent | Auth | Idempotent |
|---|---|---|---|---|---|
| POST | `/V1/carts/mine/mp-create-vault` | `CreateVaultManagementInterface::createVault` | Save a payment card to the customer vault during checkout | self | ❌ |
| GET  | `/V1/orders/:orderId/mp-payment-information` | `PayInfoManagementInterface::paymentInformation` | Retrieve MercadoPago payment details for a completed order | anonymous | ✅ |
| POST | `/V1/guest-carts/:cartId/mp-set-finance-cost` | `GuestFinanceCostManagementInterface::saveFinanceCost` | Apply installment finance cost to a guest cart | anonymous | ❌ |
| POST | `/V1/carts/mine/mp-set-finance-cost` | `FinanceCostManagementInterface::saveFinanceCost` | Apply installment finance cost to an authenticated customer cart | self | ❌ |
| GET  | `/V1/quote/:quoteId/mp-payment-information` | `QuoteMpPaymentManagementInterface::getQuoteMpPayment` | Retrieve MercadoPago payment data attached to an active quote | anonymous | ✅ |
| GET  | `/V1/payment/mp-payment-status` | `PaymentStatusManagementInterface::getPaymentStatus` | Poll the current status of a MercadoPago payment | anonymous | ✅ |

## Exposed — sync (Storefront controllers)

Controllers registered under the `/mp/` frontend route (`etc/frontend/routes.xml`, route id `payment_mercadopago`). These receive webhook POST callbacks from the MercadoPago platform and handle inline checkout interactions.

| Controller class | URL pattern | Caller intent |
|---|---|---|
| `Controller/Notification/CheckoutPro.php` | `/mp/notification/checkoutpro` | Receive MercadoPago Checkout Pro webhook notification |
| `Controller/Notification/CheckoutCustom.php` | `/mp/notification/checkoutcustom` | Receive MercadoPago custom (card) checkout webhook notification |
| `Controller/Notification/CheckoutCredits.php` | `/mp/notification/checkoutcredits` | Receive MercadoPago Credits (BNPL) webhook notification |
| `Controller/Notification/Order.php` | `/mp/notification/order` | Receive generic MercadoPago order status webhook notification |
| `Controller/Index/GenerateQrCode.php` | `/mp/index/generateqrcode` | Generate and serve a Yape/Pix QR code image for the checkout |

> **Note — `Controller/Rest.php` is not a storefront controller and exposes no route.** It is a Magento DI preference (`etc/di.xml`: `<preference for="Magento\Webapi\Controller\Rest" type="MercadoPago\AdbPayment\Controller\Rest"/>`). It extends the Magento core REST dispatcher, overrides `dispatch()` to call `parent::dispatch()`, and then strips the `errorRedirectAction` response header when the response carries a `3DS` exception message. This intercepts all REST requests globally for 3DS handling — it is not a new endpoint and is invisible to API consumers.

## Exposed — async (Cron jobs)

All jobs run in the `mercadopago_adbpayment` cron group (defined in `etc/crontab.xml`).

| Job name | Class | Schedule | Purpose |
|---|---|---|---|
| `mercadopago_fetch_merchant_info` | `Cron/FetchMerchantInfo.php` | `0 0 1 * *` (monthly) | Refresh merchant credentials and account info from MP API |
| `mercadopago_fetch_pix_order_status` | `Cron/FetchPixOrderStatus.php` | `0 9 * * *` (daily) | Poll MP API for pending Pix payment orders and update status |
| `mercadopago_fetch_payment_methods_off_order_status` | `Cron/FetchPaymentMethodsOffOrderStatus.php` | `0 9 * * 1-5` (weekdays) | Poll MP API for pending offline/ticket orders and update status |
| `mercadopago_cancel_checkout_pro` | `Cron/CancelCheckoutPro.php` | `0 9 * * 1-5` (weekdays) | Cancel stale Checkout Pro orders that never completed |
| `mercadopago_cancel_checkout_credits` | `Cron/CancelCheckoutCredits.php` | `0 9 * * 1-5` (weekdays) | Cancel stale Credits orders that never completed |
| `mercadopago_fetch_pse_order_status` | `Cron/FetchPseOrderStatus.php` | `9 9 * * 1-5` (weekdays) | Poll MP API for pending PSE (Colombia bank transfer) orders |
| `mercadopago_fetch_webpay_order_status` | `Cron/FetchWebpayOrderStatus.php` | `13 9 * * 1-5` (weekdays) | Poll MP API for pending Webpay (Chile) orders |
| `mercadopago_fetch_yape_order_status` | `Cron/FetchYapeOrderStatus.php` | `0 9 * * *` (daily) | Poll MP API for pending Yape (Peru QR) orders |

## CLI commands

Commands registered as `bin/magento` subcommands via DI.

| Class | `bin/magento` command name | Purpose |
|---|---|---|
| `Console/Command/Adminstrative/FetchMerchantInfo.php` | `mercadopago:admin:fetch_merchant_info` | Manually trigger merchant info sync from MP API |
| `Console/Command/Adminstrative/PaymentExpirations.php` | `mercadopago:admin:expire_payment` | Manually trigger payment expiration processing |
| `Console/Command/Notification/FetchOrderStatus.php` | `mercadopago:order:fetch_status` | Manually fetch and update a specific order's payment status |
| `Console/Command/Notification/CheckoutProAddChild.php` | `mercadopago:order:checkout_pro_add_child` | Manually process a Checkout Pro child payment notification |
| `Console/Command/Notification/CheckoutCreditsAddChild.php` | `mercadopago:order:checkout_credits_add_child` | Manually process a Credits child payment notification |

> Command names are set via `setName()` in each class's `configure()` method and confirmed by reading the source directly.

## Outbound dependencies

| Dependency | Code host / client | What it's used for | Timeout | Retry | Failure mode | Criticality |
|---|---|---|---|---|---|---|
| MercadoPago API | `mp-plugins/php-sdk` ^3.6.1 (`Gateway/Http/`, `Model/`) | All MercadoPago API calls: payment creation, status polling, merchant info, payment methods | — | — | Exceptions propagate to the Magento payment error flow; cron jobs log errors and continue | — |
| Core Monitor / Datadog | `Model/Metrics/CoreMonitorAdapter.php` | Metrics egress for observability | 2 s | — | Fail-open | — |

> Note: `mp-plugins/php-sdk` is the MercadoPago-owned PHP SDK (non-Fury, external package via Packagist). Timeout and retry behavior is delegated to the SDK's internal HTTP client configuration — not explicitly set in this module's config files.

> Note: for Brazil (MLB) the payer's CPF/CNPJ is check-digit-validated before the outbound payment call — `Gateway/Request/DocumentIdentificationDataRequest` (via `Helper/DocumentValidator`) rejects an invalid document locally with a `LocalizedException` instead of letting it reach the API as `INVALID_USER_IDENTIFICATION_NUMBER`. Single-card flows validate the emitted `payer.identification`; twocc validates each per-card `payer_<i>_document_identification`. Other sites are not validated.

**Magento core module dependencies** — Customer, Payment, Checkout, Vault, Catalog, Quote, Sales (declared in `etc/module.xml` and `composer.json`; these are in-process Magento module dependencies, not HTTP calls).

## Consumers (who calls this repo — snapshot)

> Consumer snapshot: 2026-07-03 (9a54e589). This is a Composer package installed into merchant Magento stores — there is no platform-level consumer graph. The effective consumers are the Magento checkout flow (storefront) and the MercadoPago platform (webhook callbacks). Re-query the Magento merchant list or MercadoPago partner portal for installed-merchant data.

This module is a `magento2-module` package. Its consumers are Magento 2 merchant stores that install it via Composer. Platform consumer tracking (MeliSystem MCP) does not apply — the module has no Fury app identity.

- **MercadoPago** calls the notification controllers above as webhooks.

For the P&P ecosystem view of which repos share specs/features with this module, see the
domain hub inventory and SDD index:
https://github.com/melisource/fury_mp-op-pp-sdd (`tech/architecture/sdd-index.md`).

## External Config Service dependency

> Snapshot: 2026-07-03 (9a54e589) · source: config-derived. Verify merchant-specific keys in the Magento admin panel (Stores > Configuration > Payment Methods > MercadoPago).

Per-merchant configuration is managed inside Magento's config storage (database-backed), not a standalone config service. The module reads its settings via `Gateway/Config/`. Key config-driven behaviors:

| Profile / scope | Keys that drive behavior | Where the code reads them |
|---|---|---|
| Per website / store view | Access Token, Public Key, payment method enable flags, installment settings, debug mode | `Gateway/Config/Config.php` |
| Per payment method | Enabled flag, minimum/maximum order amounts, credentials, 3DS toggle, vault enable | `Gateway/Config/Config<Method>.php` (e.g. `ConfigCheckoutPro.php`, `ConfigCc.php`, `ConfigPix.php`) |

**Note:** Magento's config can be overridden per scope (default → website → store view) in the admin panel. The committed `etc/config.xml` is the module-level default; the production values are in the database.

## Blast radius

Changes to the Web API routes, notification controller contracts, or the request/response
mapping in `Gateway/` affect **live merchant checkouts** across all supported sites and
payment methods. Before changing any public contract or the MP payload shape, cross-check the
domain hub SDD tree (`sdd/magento/meli/`) and coordinate through the P&P hub.

## Specs

No OpenAPI/AsyncAPI spec file exists in the repo. The REST API surface is fully declared in `etc/webapi.xml`. The service contract interfaces in `Api/` are the authoritative type definitions for each endpoint's input/output.
