# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.15.6] - 2026-08-19
### Changed
- Unify the Checkout Custom pre-submit validation (form, installments, card number) into a single ordered gate before Place Order; selecting a card whose installments are shown but not chosen now displays a message instead of silently doing nothing. Covers one-card and two-card flows

### Fixed
- Reconfigure the checkout CVV (`securityCode`) secure field per card brand on BIN change, so a wrong-length code (Amex with 3 digits, Visa/Mastercard with 4) is blocked at the front instead of failing at the payment API with `Invalid security_code_length`. On brand change the field value and error state are cleared, the label floats above the inserted placeholder, and the CVV help tooltip updates to the detected brand
- Block card token creation when the card number length is invalid; configure the secure field size exactly via BIN lookup; translated length hint updated for Amex (15 digits)
- Clean up residual card type state on BIN change; reset the CVV secure field when the BIN is invalid or cleared
- Map `bin_not_found` error to an inline error message on the card number field instead of a generic error
- Apply `security_code.mode` to determine CVV optionality even when length is 0; reflect optionality on the required `(*)` marker
- Add the required `code` attribute to 53 `<message>` nodes in `mercadopago_error_mapping.xml` that used the invalid `message` attribute, fixing a schema validation failure in `setup:di:compile` that broke the unauthorized 3DS challenge error flow (PPSP-1506)
- Remove a stray closing double quote from the end of a Yape rejection message in `mercadopago_error_mapping.xml`

### Added
- Validate the card number's Luhn checksum on the Checkout Custom card field via the MP Secure Fields (`enableLuhnValidation`): an invalid checksum shows an inline, translatable field error and blocks submit before the payment API. A format (length) error takes precedence over the Luhn message. Covers one-card and two-card flows
- Show oriented message when payment API rejects with `INVALID_USER_IDENTIFICATION_NUMBER` (HTTP 400) instead of the generic communication error; message is generic across all sites and locales
- Map payment API errors to 15 categories via `ApiErrorCategoryMapper`; add `payment_method_id` dimension to payment metrics in the credit card flow
- Validate CPF/CNPJ documents on the backend before the payment API (MLB only); validation extends to per-card document in the two-card flow
- Segregate document validation metrics by payment flow (`cc`, `twocc`, `ticket`, `pix`)
- Agent readiness docs: `docs/agent/` (overview, architecture, contracts, runbook, traps), `CONTRIBUTING.md`, team hub links in `AGENTS.md` and `docs/agent/runbook.md`, DoD checklist in `.github/pull_request_template.md`.

## [1.15.5] - 2026-07-01
### Fixed
- Fixed SVG logo/icon dimension extraction by replacing `getimagesizefromstring()` with `simplexml_load_file()` across all ConfigProvider models
- Fixed console commands compatibility with Symfony 7 (Magento 2.4.9) by adding `: int` return type to `execute()` and replacing `Command::SUCCESS` with literal `0` (PSW-4152)

### Added
- Added PHP 8.5 to supported platform constraint in `composer.json` for Adobe Commerce 2.4.9 compatibility
- Declared `ext-simplexml` and `ext-libxml` as explicit `require` dependencies in `composer.json` (PSW-4159)
- Added alphanumeric CNPJ validation and uppercase normalization before sending to payment API, supporting RFB Nota Técnica 49/2024 format

### Changed
- Updated `mp-plugins/php-sdk` from `^3.3.2` to `^3.6.1` for PHP 8.5 compatibility (PSW-4172)

## [1.15.4] - 2026-05-12
### Fixed
- Fixed total calculation in Checkout Pro when coupon is applied (PPSP-1260)

### Changed
- Migrated payment methods endpoint from legacy to Core API (PSW-2841)

### Added
- Added user-friendly error message for Credits MLC minimum amount validation (PPSP-975)

## [1.15.3] - 2026-04-24
### Changed
- Replaced HTTP call to `/item_categories` endpoint with a static hardcoded array, removing dependency on the deprecated `checkout-off-api-v1` application

## [1.15.2] - 2026-03-31
### Fixed
- Fixed error handling and validation in 3DS challenge flow and modal initialization
- Fixed error handling in vault payment flow with error metrics support
- Fixed safer response handling in credit card and vault order placement
- Updated error messages for payment processing issues in multiple languages

## [1.15.1] - 2026-03-20
### Changed
- Forces the sending of the refund amount with Orders API

## [1.15.0] - 2026-02-20
### Added
- System now automatically supports both Order API and legacy Payment API transactions, with intelligent detection based on ID patterns and compatibility with existing flows

### Fixed
- Fixed metrics reporting for unmapped statuses to avoid false positives
- Fixed null pointer exception in notificationId extraction with proper validation
- Fixed display of disabled payment methods at multi-address checkout

## [1.14.0] - 2026-01-05
### Added
- Added Order API integration for PIX payments

### Changed
- Updated logo SVG with new design elements and color styling

## [1.13.2] - 2025-12-03
### Fixed
- Fixed order total due when Chopro coupon is applied

## [1.13.0] - 2025-11-19
### Added
- Added trackings to checkout buyer

## [1.12.1] - 2025-10-30
### Fixed
- Fixed external reference for payments with 3DS validation

## [1.12.0] - 2025-09-10
### Changed
- Change maximum pix expiration date

## [1.11.0] - 2025-07-21
### Added
- Add option in the admin to change the order of address lines
- Add end-to-end testing

### Changed
- Change the display of taxes for installment in Argentina

## [1.10.1] - 2025-06-18
### Fixed
- Correction of discount calculation in CHOPRO processing
- Fixed import image on README file

### Changed
- Add compatibility with PHP 8.4 in composer.json
- Refactored methods to ensure compatibility with PHP 8.4
- Updated SDK version to 3.3.2

## [1.10.0] - 2025-05-19
### Changed
- Updated the Mercado Pago branding across all checkouts, admin panel, and success pages.
- Updated plugin code to be compatible with the latest PHP SDK version.
### Fixed
- Resolved issue preventing the use of saved cards (Vault) with other payment methods in the Mercado Pago plugin.

## [1.9.3] - 2025-04-10
### Fixed
- Adjustments to the Pix QR code sent by email
- Adjustments to the address fields for Boleto
- Adjustments to the total amount in payments with ChoPro

## [1.9.2] - 2025-03-26
### Changed
- Adjustments on payments without postcode

## [1.9.1] - 2025-02-06
### Changed
- Translation for user invalid email message on checkout
- Adjustments on CSS for Yape

### Fixed
- Adjustment on Vault for pending payment
- Fixed binary mode options

## [1.9.0] - 2024-12-18
### Changed
- Changed magento order cancellation flow in MP rejected status
- Updated support admin link

### Fixed
- Off payment methods disabled in MLC
- Adjustment on installments info in vault
- Translate customer invalid email message
- Adjustment on cancel orders cron

## Added
- Added new Yape payment method for Peru

## [1.8.5] - 2024-10-30
### Changed
- Adjustments on maximum order amount when payment has financial cost
- Adjustments on partial refund
- Update binary mode default value
- Fixed financial cost amount exhibition on order view and success page with cards payment
- Improved logs on cancel orders with expirated preferences cron
- Updated text for congrats page on MLB "Lotérica" payment

## [1.8.4] - 2024-09-23
### Changed
- Rebranding of Mercado Credits
- Ajustments in Checkout Pro's layout

## Added
- Added online refund option for payment with Cho Pro

## [1.8.3] - 2024-09-05
### Changed
- Adjusting the rule used to obtain expired orders and cancel them via Cron
- Separate device fingerprint from SDK + add nonce to load script

### Added
- Added logs to errors with MPClient or SDK requests

## [1.8.2] - 2024-05-27
### Fixed
- Fixed intermittent error when saving payment details

## [1.8.1] - 2024-04-25
### Fixed
- Fix added existing value validation for the financial_institution field
- Update anotations references
- Add php version 8.3.0 in compose.json

## [1.8.0] - 2024-04-09
### Fixed
- Adjust 3ds modal sizing to be compliant with documentation
- Fix area code not set on setup:upgrade
- Fix/quote mp payment int in 3DS flow
- Fix sending payer.id in any payment flow

## [1.7.0] - 2024-03-27
### Added
- Added trackings in selected paths for melidata

### Fixed
- Validation for expired credentials
- Correction for area code error in old platform versions

## [1.6.3] - 2024-03-07
### Changed
- Adjusting Iframe creation with 3Ds.
- PSJ/PCJ adequacy in online payment.
- Adding the checkout_type of type two_cards to the metadata

### Fixed
- Adding a translation fix in FetchPaymentHandler

## [1.6.2] - 2024-01-29
### Changed
- Get document types from payment methods to PSE.

### Fixed
- Translate fix.

## [1.6.1] - 2024-01-15
### Fixed
- Regular expression adjustment to accept alphanumeric in RUT type document.

## [1.6.0] - 2024-01-03
### Added
- Added plugin version + site id information on admin

### Fix
- Refactor of refund flow to improve performance and fix minor bugs
- Translate fix for checkout credits and finance cost
- Fix store scope information when saving site id
- Fix date expiration information on front

## [1.5.0] - 2023-10-09
### Added
- Feature 3DS
- State Machine
- Remedies
- PSE Avanza
### Fixed
- Fixed notification update CRON
- Fixed PIX PDF

## [1.4.2] - 2023-09-11
### Fixed
- Adjust installments and finance cost calculation when applying the coupon
- Fixed two card flow


## [1.4.1] - 2023-07-18
### Added
- Added support for PHP 8.2 in composer

### Changed
- Changed wiki link in README.md

## [1.4.0] - 2023-07-07
### Added
- SDK implementation
- Inclusion of the Mercado Credits payment methods
- Compatibility with Magento version 2.4.6
- Inclusion of PF data

### Fixed
- Changed expiration date fields Checkout Pro
- Fixed decimal places on front end of two cards
- Remove policy prefetch-src
- Fixed credit card flags
- Remove sponsor id from test user flow
- Fixed on credentials links
- Fixed default success page

## [1.3.0] - 2023-06-13
### Fixed
- Fixed manual capture flow
- Fixed refund flow
- Fixed the refund process and update information in notifications
- Fixed validation of color save in Checkout Pro options in admin
- Fix installments flickr

## [1.2.1] - 2023-05-26
### Update version management

## [1.2.0] - 2023-05-25
### Stable version

## [1.1.0] - 2023-05-22
### Added
- Improve refund flow

## [1.0.0] - 2023-05-08
### First Release
