---
paths:
  - "Setup/**/*.php"
---

# Setup Patches

Magento data and schema patches for module installation and upgrades.

## Structure

- `Setup/Patch/` -- Contains data and schema patch classes

## Conventions

- Data patches implement `\Magento\Framework\Setup\Patch\DataPatchInterface`
- Schema patches implement `\Magento\Framework\Setup\Patch\SchemaPatchInterface`
- Patch classes must implement `getAliases()` and `getDependencies()`
- The `apply()` method contains the migration logic
- DB schema is also declared in `etc/db_schema.xml` and `etc/db_schema_whitelist.json`

## Rules

- Patches are run once and recorded -- they must be idempotent anyway for safety
- Never drop columns or tables in patches without a migration path
- Use `$setup->getConnection()` for raw SQL only when the schema API is insufficient
- Test patches against all supported Magento versions (2.4.4 through 2.4.7)
- New schema changes should prefer `etc/db_schema.xml` (declarative schema) over PHP patches
