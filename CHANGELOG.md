# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
