---
paths:
  - ".github/workflows/**/*.yml"
---

# CI/CD Workflows

GitHub Actions workflows for quality checks, testing, and releases.

## Workflows

- `magento-coding-quality.yml` -- PHPCS coding standard validation
- `phpcs.yml` -- Additional PHPCS checks
- `test-m2.4.4.yml` -- Tests against Magento 2.4.4
- `test-m2.4.5.yml` -- Tests against Magento 2.4.5
- `test-m2.4.6.yml` -- Tests against Magento 2.4.6
- `test-m2.4.7.yml` -- Tests against Magento 2.4.7
- `versioning.yml` -- Release version management

## Conventions

- All workflows must pass before merging PRs
- Tests run against multiple Magento versions to ensure compatibility
- PHPCS uses the Magento2 coding standard
- Version bumps are handled by the versioning workflow

## Rules

- Do not skip CI checks -- fix the root cause instead
- New features should pass on ALL supported Magento versions (2.4.4-2.4.7)
- If a test fails on one Magento version but passes on others, investigate version-specific APIs
- Workflow changes must be tested on a feature branch before merging to main
