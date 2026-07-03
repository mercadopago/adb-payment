# pp-adb-payment

MercadoPago payment plugin for Adobe Commerce (Magento 2). Module name: `MercadoPago_AdbPayment`.
Composer package: `mercadopago/adb-payment` (v1.15.0).

## Architecture

Standard Magento 2 module following Adobe Commerce conventions:

- **Api/** -- Service contract interfaces (repositories, management interfaces, data interfaces)
- **Controller/** -- Front-controllers for storefront notifications, REST endpoints, checkout redirects
- **Gateway/** -- Magento Payment Gateway layer: commands, HTTP client, request builders, response handlers, validators (wires `mp-plugins/php-sdk` to payment flow)
- **Model/** -- Core domain: payment methods, order/quote management, MP API integration, config, resource models, UI data providers
- **Model/Metrics/** -- Metrics client and adapter for instrumenting payment operations
- **Helper/** -- Stateless utilities: API type detection, error extraction, idempotency keys, order API headers
- **Observer/** -- Event observers for checkout and order lifecycle (data assignment, finance-cost conversion, payment availability, cancellation)
- **Plugin/** -- Interceptor plugins for address validation, order amount limits, payment tokens, vault initialization
- **Cron/** -- Scheduled jobs polling external payment status (Pix, PSE, Webpay, Yape, Checkout Pro/Credits) and cancelling stale orders
- **Console/Command/** -- CLI commands exposed via `bin/magento`
- **Setup/Patch/** -- Data/schema patches for module installation and upgrades
- **Block/** -- PHP block classes for storefront and admin rendering
- **view/frontend/** -- Storefront PHTML templates, RequireJS/JS components (Knockout.js), layout XML
- **view/adminhtml/** -- Admin panel templates, JS, layout XML
- **etc/** -- Module configuration: DI (`di.xml`), web API routing (`webapi.xml`), events, cron, payment, DB schema, CSP rules
- **i18n/** -- Translation CSVs (es_AR, es_MX, es_CL, es_CO, es_PE, es_UY, pt_BR, en_US)

### Payment Methods

Checkout Pro, Checkout Custom (credit/debit cards, two cards, vault), Credits, Pix, PSE, Ticket, Webpay, Yape.

### Key Dependencies

- `mp-plugins/php-sdk` (^3.3.2) -- MercadoPago SDK for API communication
- Magento modules: Customer, Payment, Checkout, Vault, Catalog, Quote, Sales

## Commands

| Task | Command |
|------|---------|
| Build (Docker) | `docker-compose up -d --build` |
| Install | `make install` |
| Dev server | `make run` |
| Run tests | `bash bin/run-test.sh` |
| Run linters | `bash bin/run-linters.sh` |
| PHPCS only | `docker exec magento-php magento2/vendor/bin/phpcs -q --report=full --standard=Magento2 magento2/app/code/MercadoPago/AdbPayment/` |
| PHPStan | `bash bin/run-phpstan.sh` |
| PHPMD | `bash bin/run-phpmd.sh` |
| E2E tests | `cd e2e && npx playwright test` |

Tests run inside Docker via `docker exec magento-php`. The PHPUnit config is at `phpunit.xml` in the module root.

## Project Conventions

- **Namespace:** `MercadoPago\AdbPayment\` (PSR-4 autoloading from module root)
- **Coding standard:** Magento2 PHPCS standard (`magento/magento-coding-standard`)
- **DI configuration:** All dependency wiring is in `etc/di.xml` (constructor injection, virtual types, preferences)
- **Payment gateway:** Follow Magento Payment Gateway pattern -- request builders in `Gateway/Request/`, response handlers in `Gateway/Response/`, validators in `Gateway/Validator/`, HTTP client in `Gateway/Http/`
- **Observers:** One observer per event. Named after the event they react to. Registered in `etc/events.xml` and `etc/frontend/events.xml`
- **Plugins:** Interceptor plugins in `Plugin/` -- registered in `etc/di.xml` with `<plugin>` entries
- **Cron jobs:** Each cron class handles one payment method status fetch. Registered in `etc/crontab.xml`
- **Translations:** All user-facing strings must use `__('string')` and have entries in all `i18n/*.csv` files
- **Frontend JS:** RequireJS modules with Knockout.js bindings. Config in `view/frontend/requirejs-config.js`
- **Test structure:** Unit tests mirror module structure under `Tests/Unit/`. Mock data in `Tests/Unit/Mocks/`

## CI/CD

GitHub Actions workflows:
- `magento-coding-quality.yml` -- PHPCS coding standard checks
- `phpcs.yml` -- Additional PHPCS validation
- `test-m2.4.4.yml` through `test-m2.4.7.yml` -- Multi-version Magento integration tests
- `versioning.yml` -- Release versioning

<!-- claudify:managed-start -->

## Prerequisites

The following tools are required for the development workflow and hooks:

| Tool | Install |
|------|---------|
| Docker & docker-compose | Required for running tests and linters |
| PHP 7.3+ / 8.1+ | Local development (module supports 7.3-8.4) |
| Composer | `brew install composer` or system package |
| Node.js | Required for E2E tests (`cd e2e && npm install`) |
| Playwright | `cd e2e && npx playwright install` |

## Security Rules

This project includes MeLi AppSec security rules for AI agents.
See `AGENTS.md` and `.agentic-rules/` for language-specific security patterns.

## Claude Code Setup

This project is configured with Claude Code rules, agents, and skills:

- **Rules** (`.claude/rules/`): Layer-specific coding guidelines scoped by file path
- **Agents** (`.claude/agents/`): Specialized sub-agents for testing, research, and spec writing
- **Skills** (`.claude/skills/`): Repeatable workflows for tests, lint, review, commits, and PRs
- **Settings** (`.claude/settings.json`): Hooks for auto-linting on edit and pre-stop verification

Run `/skills` in Claude Code to see available skills. See `.claude/USAGE.md` for full documentation.

<!-- claudify:managed-end -->
