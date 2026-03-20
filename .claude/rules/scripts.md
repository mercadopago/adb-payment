---
paths:
  - "bin/**/*"
  - "build/**/*"
---

# Scripts and Build Tools

Shell scripts and Docker setup for local development.

## Key Scripts

- `bin/run-test.sh` -- Runs PHPUnit tests inside Docker
- `bin/run-linters.sh` -- Runs all linters (PHPCS, PHPStan, PHPMD)
- `bin/run-phpcs.sh` -- PHPCS coding standard check
- `bin/run-phpstan.sh` -- PHPStan static analysis
- `bin/run-phpmd.sh` -- PHPMD mess detector
- `bin/run-sync-files.sh` -- Syncs files to Docker container

## Docker Setup

- `docker-compose up -d --build` -- Build and start the development environment
- `make install` -- Full Magento installation with the module
- `make run` -- Start the development server

## Conventions

- All scripts run inside the `magento-php` Docker container via `docker exec`
- Magento module is mounted at `magento2/app/code/MercadoPago/AdbPayment/` inside the container
- Linter scripts run sequentially: sync files, then PHPCS, PHPStan, PHPMD

## Rules

- Scripts must be executable (`chmod +x`)
- Use `#!/bin/bash` shebang consistently
- Always sync files before running linters (ensures container has latest code)
- Do not modify Docker or Makefile without testing the full install flow
