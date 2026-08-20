---
type: Runbook
version: 10fa0759
validated: 2026-07-03
update_when: when build/test/lint/CI commands, the test harness, or the Definition of Done changes
scope:
  - Makefile
  - bin
  - build
  - .github/workflows
  - composer.json
  - phpunit.xml
  - phpstan.neon
  - e2e
---

# Runbook + Definition of Done

## Build / run

All development requires Docker. Copy `build/.env.sample` to `build/.env` and `build/credentials/auth.json` (Magento Marketplace credentials) before running any `make` target. Full guide: [`HOWTORUN.md`](../../HOWTORUN.md).

```bash
# One-time: create the env file the Makefile expects
cp build/.env.sample build/.env        # then edit credentials/versions

# Install (generates SSL certs, brings up Docker, installs Magento + module)
make install

# Start / stop the running environment
make run
make stop

# Tear down (removes volumes)
make uninstall
```

| Task | Command |
|---|---|
| Full install (first time) | `make install` |
| Start Docker environment | `make run` |
| Stop Docker environment | `make stop` |
| Uninstall (wipe volumes) | `make uninstall` |
| SSH into PHP container | `bash bin/bash.sh` |

`make install` generates SSL certs, starts Docker Compose from `build/docker-compose.yaml`, runs `composer require`, installs Magento with a full `setup:install`, runs DI compile, deploys sample data, and sets developer mode. It requires `ADMIN_FIRSTNAME`, `ADMIN_LASTNAME`, `ADMIN_EMAIL`, `ADMIN_USER`, `ADMIN_PASSWORD` to be set in `build/.env`.

Prerequisites: Docker (+ Compose), OpenSSL, and `build/credentials/auth.json` for `repo.magento.com`. Node.js + Playwright are only needed for E2E (`cd e2e && npm install`).

After any change to `etc/di.xml`: run `docker exec magento_php bin/magento setup:di:compile` to verify DI wiring.

After any change to `etc/db_schema.xml`: run `docker exec magento_php bin/magento setup:db-declaration:generate-whitelist --module-name=MercadoPago_AdbPayment` to update `etc/db_schema_whitelist.json`.

## Test

All test commands execute **inside Docker** (`docker exec magento_php ...`). Running them directly on the host fails because PHPUnit and the Magento bootstrap are only available in the container.

```bash
# Unit tests (PHPUnit inside the magento-php container, with coverage)
bash bin/run-test.sh
```

`bin/run-test.sh` runs `vendor/phpunit/phpunit/phpunit --configuration .../phpunit.xml --coverage-clover clover.xml --coverage-text --coverage-html reports/ .../Tests` and always produces coverage output.

```bash
# End-to-end (Playwright, storefront checkout flows)
cd e2e && npx playwright test
```

| Task | Command |
|---|---|
| Run all unit tests | `bash bin/run-test.sh` |
| PHPCS (Magento2 standard) | `bash bin/run-phpcs.sh` |
| PHPStan | `bash bin/run-phpstan.sh` |
| PHPMD | `bash bin/run-phpmd.sh` |
| All linters | `bash bin/run-linters.sh` |
| E2E tests (Playwright) | `cd e2e && npm install && npx playwright install && npx playwright test` |
| PHPCS (direct, without Docker) | `docker exec magento-php magento2/vendor/bin/phpcs -q --report=full --standard=Magento2 magento2/app/code/MercadoPago/AdbPayment/` |

PHPUnit config: `phpunit.xml` at module root. PHPStan config: `phpstan.neon`.

## Lint / static analysis

```bash
bash bin/run-linters.sh   # runs: sync-files → phpcs → phpstan → phpmd
bash bin/run-phpcs.sh     # Magento2 coding standard only
bash bin/run-phpstan.sh   # phpstan.neon
bash bin/run-phpmd.sh     # PHPMD
```

## Harness de Testes

### Comandos
- **Build:** `make install` (primeira vez) / `make run` (subir o ambiente Docker)
- **Testes unitários:** `bash bin/run-test.sh` (PHPUnit dentro do container `magento-php`)
- **Testes E2E:** `cd e2e && npx playwright test` (Playwright, fluxos de checkout)
- **Coverage:** gerado pelo `bin/run-test.sh` via `--coverage-clover clover.xml`
  (+ `--coverage-text` e HTML em `reports/`)

### Bootstrap
PHPUnit config em [`phpunit.xml`](../../phpunit.xml) na raiz do módulo. Não há classe base de
bootstrap centralizada; cada teste unitário faz seu próprio `setUp()` usando os mocks da suíte.
Os testes rodam **dentro do container Docker** (`docker exec magento-php …`), não no host.

### Mocks centralizados
Mocks e fixtures ficam em [`Tests/Unit/Mocks/`](../../Tests/Unit/Mocks) e são reutilizados
pelos ~89 testes unitários — não reinventar mock por arquivo.

### Estrutura de testes
`Tests/Unit/` espelha a estrutura do módulo: `Gateway/`, `Model/`, `Observer/`,
`Controller/`, `Helper/`, `Cron/`, `Block/`, `Console/`. E2E em `e2e/` (Playwright, JS).

### Configuração de coverage
- Ferramenta: **PHPUnit + Xdebug/PCOV** (relatório Clover + texto + HTML)
- Whitelist: [`phpunit.xml`](../../phpunit.xml) → `<coverage><include><directory>./</directory>`
  (inclui todo o módulo; não há excludes ocultando `src/`)
- Threshold: não há gate de percentual configurado no `phpunit.xml`
- Relatório: `clover.xml` (raiz) + `reports/` (HTML, servido em `http://localhost:8080/reports`)

> **Lacunas conhecidas (ação manual do time):** não há `test_builders`/factories fluentes
> para as entidades de domínio, e os cenários E2E dependem de credenciais/ambiente de stage.
> Ambos exigem conhecimento de domínio e não foram gerados automaticamente.

## CI

GitHub Actions workflows (all trigger on `pull_request`):

| Workflow | File | What it checks |
|---|---|---|
| Magento Coding Quality | `.github/workflows/magento-coding-quality.yml` | PHPCS against Magento2 standard via Docker |
| PHPCS | `.github/workflows/phpcs.yml` | Additional PHPCS validation |
| Magento 2.4.4 tests | `.github/workflows/test-m2.4.4.yml` | Integration tests on Magento 2.4.4 |
| Magento 2.4.5 tests | `.github/workflows/test-m2.4.5.yml` | Integration tests on Magento 2.4.5 |
| Magento 2.4.6 tests | `.github/workflows/test-m2.4.6.yml` | Integration tests on Magento 2.4.6 |
| Magento 2.4.7 tests | `.github/workflows/test-m2.4.7.yml` | Integration tests on Magento 2.4.7 |
| Versioning | `.github/workflows/versioning.yml` | Release versioning automation |
| Doc freshness | `.github/workflows/doc-freshness.yml` | Fails a PR that changes code under a `docs/agent/*.md` guide's `scope:` without bumping that guide's `version:` to the new HEAD SHA; override with the `docs-exempt` label when the change truly doesn't affect the guide |

PHPCS and Magento coding quality checks are blocking. Multi-version integration tests cover Magento 2.4.4 through 2.4.7. All `setup-php` steps pin `tools: composer:v2` to install a hardened Composer 2.x (>= 2.10.2), mitigating CVE-2026-59946/59947/59948 (PSW-4300).

## Definition of Done (ready for PR)

- [ ] Unit tests pass (`bash bin/run-test.sh`)
- [ ] PHPCS passes (`bash bin/run-phpcs.sh`)
- [ ] PHPStan passes (`bash bin/run-phpstan.sh`)
- [ ] Linters pass (`bash bin/run-linters.sh`)
- [ ] No secrets, tokens, or credentials in code
- [ ] If REST routes changed: update `etc/webapi.xml` and the `Api/` interface; update [contracts.md](contracts.md) and bump its `version`
- [ ] If `etc/di.xml` changed: run `bin/magento setup:di:compile` and confirm no errors
- [ ] If `etc/db_schema.xml` changed: regenerate `etc/db_schema_whitelist.json`
- [ ] If adding a user-facing string: add entries to all 9 `i18n/*.csv` files using `__('…')`
- [ ] Update the relevant `docs/agent/` guide in the same PR; bump its `version` to the current HEAD SHA
- [ ] Add any new non-obvious gotcha to [traps.md](traps.md)
- [ ] Update `CHANGELOG.md` under `## [Unreleased]`
- [ ] Observability updated when applicable (metrics in `Model/Metrics/`)
- [ ] Product gates (feature flags, gradual rollout, TL/PL approvals): [`checklists/FEATURE_CHECKLIST.md`](https://github.com/melisource/fury_mp-op-pp-development-cycle/blob/master/checklists/FEATURE_CHECKLIST.md)
