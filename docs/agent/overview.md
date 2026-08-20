---
type: Overview
version: 10fa0759
validated: 2026-07-23
update_when: when the repo's purpose, payment methods, or capabilities change
scope:
  - Api
  - Model
  - Gateway
  - Controller
  - Observer
  - Plugin
  - Cron
  - Console
  - Helper
  - etc
---

# Overview — pp-adb-payment

## What this module is

`pp-adb-payment` is the **MercadoPago payment plugin for Adobe Commerce (Magento 2)**. It installs as a `magento2-module` inside a merchant's Magento store and enables buyers to pay with MercadoPago payment methods at checkout. It also handles asynchronous payment status reconciliation (cron polling + webhook notifications) and exposes a small REST API for the storefront checkout UI to query payment state. It integrates MercadoPago as a payment provider inside the Adobe Commerce checkout, wiring the `mp-plugins/php-sdk` into Magento's Payment Gateway pipeline. It belongs to the **Plugins & Payments (P&P)** domain and is one of the merchant-facing e-commerce plugins alongside VTEX, TiendaNube, WooCommerce, PrestaShop, etc.

### Supported payment methods

Checkout Pro, Checkout Custom (credit/debit cards, two cards, vault/tokenized cards),
Credits, Pix, PSE, Ticket (boleto/offline), Webpay and Yape — across MLA, MLB, MLC, MLM,
MCO, MPE and MLU sites.

## Archetype

Magento 2 module (library-plus-controllers). The module has no independent runtime — it executes inside a Magento 2 application. It contributes REST API endpoints (via `etc/webapi.xml`), storefront controllers (under the `/mp/` URL prefix), cron jobs, console commands, observers, and DI plugins to the host Magento installation. There are no separate binaries or scopes; the host Magento handles all routing and DI wiring.

## Identifiers

- `application_name`: not applicable (no `.fury` — this is a Composer package, not a Fury app)
- Module / package: `mercadopago/adb-payment` v1.15.5; Magento module name `MercadoPago_AdbPayment`
- Runtime: PHP 7.3 / 7.4 / 8.1 / 8.2 / 8.3 / 8.4 / 8.5 inside Magento 2.4.x
- Owner / team: mp-pp-core — CODEOWNERS lists @albalmeida_meli @anacarolferr_meli @cleitoandrad_meli @ddlorenzetti_meli @ext-anlferna_meli @flira_meli @julidsilva_meli @klucena_meli @skhalil_meli

## Capabilities (and where they live)

| Capability | Lives in (code) |
|---|---|
| Accept and process card payments (credit, debit, two-card, vault) | `Gateway/`, `Model/Ui/` (`ConfigProviderCc.php`, `ConfigProviderTwoCc.php`), `Controller/Notification/CheckoutCustom.php`, `Cron/FetchPaymentMethodsOffOrderStatus.php` |
| Checkout Pro redirect flow (hosted checkout) | `Model/Ui/ConfigProviderCheckoutPro.php`, `Controller/Notification/CheckoutPro.php`, `Cron/CancelCheckoutPro.php` |
| Credits (BNPL) payment flow | `Model/Ui/ConfigProviderCheckoutCredits.php`, `Controller/Notification/CheckoutCredits.php`, `Cron/CancelCheckoutCredits.php` |
| Pix instant payment | `Gateway/`, `Controller/Notification/Order.php`, `Cron/FetchPixOrderStatus.php` |
| PSE bank transfer | `Model/Ui/ConfigProviderPse.php`, `Cron/FetchPseOrderStatus.php` |
| Webpay payment | `Model/Ui/ConfigProviderWebpay.php`, `Cron/FetchWebpayOrderStatus.php` |
| Yape QR payment | `Model/Ui/ConfigProviderYape.php`, `Cron/FetchYapeOrderStatus.php`, `Controller/Index/GenerateQrCode.php` |
| Ticket / offline payment | `Model/Ui/ConfigProviderPaymentMethodsOff.php`, `Cron/FetchPaymentMethodsOffOrderStatus.php` |
| Webhook notification handling | `Controller/Notification/` (CheckoutCredits, CheckoutCustom, CheckoutPro, Order), `Controller/Rest.php` |
| Finance cost (installments surcharge) management | `Api/FinanceCostManagementInterface.php`, `Api/GuestFinanceCostManagementInterface.php`, `Model/Api/FinanceCostManagement.php`, `Model/Api/GuestFinanceCostManagement.php`, `Model/Quote/Address/Total/FinanceCost.php`, `Model/Order/Total/Invoice/FinanceCost.php`, `Model/Order/Total/Creditmemo/FinanceCost.php` |
| Payment vault (card tokenization) | `Api/CreateVaultManagementInterface.php`, `Block/Customer/CardRenderer.php` |
| Async payment status polling | `Cron/` (8 jobs), `Console/Command/Notification/FetchOrderStatus.php` |
| Merchant info sync | `Cron/FetchMerchantInfo.php`, `Console/Command/Adminstrative/FetchMerchantInfo.php` |
| Metrics / observability | `Model/Metrics/`, `Helper/ApiErrorCategoryMapper.php` (maps API errors to a closed-set category — never raw free-text). Order API clients emit category in `message`; `Gateway/Http/Client/CreateOrderPaymentCustomClient.php` (card) emits category in `eventType` as `mp_api_error_{category}`. |
| Storefront & admin UI | `view/frontend/`, `view/adminhtml/`, `Block/` |
| Module DI and configuration | `etc/di.xml`, `etc/webapi.xml`, `etc/crontab.xml`, `etc/events.xml` |
| Data / schema patches | `Setup/Patch/` |
| Localization (9 locales) | `i18n/` |

## What this module does NOT do (negative boundary)

- **Does not implement payment business logic itself** — it delegates to MercadoPago's
  backend through `mp-plugins/php-sdk`; this module is the *adapter* between Magento and
  the MP API, not the payment processor.
- **Does not call the MercadoPago API directly from Models, Controllers or Observers** —
  every outbound call goes through the `Gateway/Http/` clients (see `traps.md`).
- **Is not a standalone HTTP service.** It is a Magento module: it exposes only Magento
  Web API (`webapi.xml`) routes and storefront front-controllers hosted by the merchant's
  Adobe Commerce installation. There is no independently deployable Fury web server here.
- **Does not own the merchant's order/quote/customer data** — those are Magento core
  entities; this module reads and augments them.
- **Does not manage MercadoPago account credentials lifecycle** — it consumes the
  access token / public key configured by the merchant in the admin panel.

## Tech Stack

- **Language:** PHP (`~7.3` through `~8.5`, per `composer.json`)
- **Platform:** Adobe Commerce / Magento 2 (module type `magento2-module`, tested against
  Magento 2.4.4–2.4.7)
- **Key dependency:** `mp-plugins/php-sdk` (`^3.6.1`) — MercadoPago SDK for API communication
- **Magento modules consumed:** Customer, Payment, Checkout, Vault, Catalog, Quote, Sales
- **Frontend:** RequireJS modules with Knockout.js bindings (`view/frontend/`)

## Team hub

- **Domain hub (P&P — source of truth):** https://github.com/melisource/fury_mp-op-pp-sdd
  — this module is tracked in the hub app inventory (`AGENTS.md`) and has a dedicated SDD
  tree under [`sdd/magento/meli/`](https://github.com/melisource/fury_mp-op-pp-sdd/tree/master/sdd/magento/meli)
  (`PROJECT.md`, `PATTERNS.md`, `specs/technical-spec.md`).
- **Process hub (DoR/DoD, code review, standards):** https://github.com/melisource/fury_mp-op-pp-development-cycle

## Spec relacionada (hub)

Este módulo aparece como "App envolvido" nas seguintes features do domínio P&P, no índice
de specs do hub ([`tech/architecture/sdd-index.md`](https://github.com/melisource/fury_mp-op-pp-sdd/blob/master/tech/architecture/sdd-index.md)):

- **Magento — CNPJ alfanumérico** — [`sdd/magento/meli/features/20260429-alphanumeric-cnpj/`](https://github.com/melisource/fury_mp-op-pp-sdd/tree/master/sdd/magento/meli/features/20260429-alphanumeric-cnpj)
- **Hub — CNPJ alfanumérico (hub scope)** — feature cross-platform que envolve também
  `fury_salesforce-mp-plugin` e `fury_woocommerce-plugins-enablers`

Spec técnica dedicada do módulo (reverse-engineering + padrões):
[`sdd/magento/meli/`](https://github.com/melisource/fury_mp-op-pp-sdd/tree/master/sdd/magento/meli).

`referenciado_na_spec_do_hub`: **SIM** (verificado no `sdd-index.md`).
`features_locais` (BDD `.feature`): **pendência honesta** — ver seção abaixo.

### Cenários de domínio (BDD ainda não wired)

O repo **não tem runner BDD** (Cucumber/Behat) instalado — os testes são PHPUnit (unit,
`Tests/Unit/`) e Playwright (E2E, `e2e/`, escritos em JS puro, não Gherkin). Criar arquivos
`.feature` sem runner seria artefato inerte, então ficam **descritos em prosa** aqui até que
um runner BDD (p.ex. Behat) seja adotado:

- **Pagamento com cartão via Checkout Custom:** dado um carrinho válido e dados de cartão,
  quando o comprador confirma o pagamento, então a ordem é criada e o status reflete a
  resposta da API MP (aprovado/recusado/pendente).
- **Pagamento offline (Pix/PSE/Ticket):** dado um método offline selecionado, quando a ordem
  é criada, então um QR/código é gerado e a ordem fica pendente até o cron de reconciliação
  (`Cron/Fetch*OrderStatus`) confirmar o pagamento.
- **Cancelamento de ordem obsoleta:** dado uma ordem pendente expirada, quando o cron
  `mercadopago_cancel_*` roda, então a ordem é cancelada.

## Where to look next

- [`architecture.md`](architecture.md) — layers, data flow, invariants, observability &
  resilience examples (with file references)
- [`contracts.md`](contracts.md) — Web API routes, storefront controllers, crons, upstream/downstream
- [`runbook.md`](runbook.md) — how to build/test/run + test harness + Definition of Done
- [`traps.md`](traps.md) — what the agent must NOT do in this module
