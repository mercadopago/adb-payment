# Contributing to pp-adb-payment

This is the MercadoPago payment plugin for Adobe Commerce (Magento 2), part of the
**Plugins & Payments (P&P)** domain. It follows the team's centralized development process.

## Team references

- **Domain hub (P&P — source of truth):** https://github.com/melisource/fury_mp-op-pp-sdd
  — app inventory, cross-repo specs and the SDD tree for this module
  ([`sdd/magento/meli/`](https://github.com/melisource/fury_mp-op-pp-sdd/tree/master/sdd/magento/meli)).
- **Process hub (standards & review):** https://github.com/melisource/fury_mp-op-pp-development-cycle
  - Code review guide: [`docs/CODE_REVIEW_GUIDE.md`](https://github.com/melisource/fury_mp-op-pp-development-cycle/blob/master/docs/CODE_REVIEW_GUIDE.md)
  - Coding standards: [`docs/CODING_STANDARDS.md`](https://github.com/melisource/fury_mp-op-pp-development-cycle/blob/master/docs/CODING_STANDARDS.md)
  - Definition of Ready / Done: [`docs/DEFINITION_OF_READY.md`](https://github.com/melisource/fury_mp-op-pp-development-cycle/blob/master/docs/DEFINITION_OF_READY.md) · [`docs/DEFINITION_OF_DONE.md`](https://github.com/melisource/fury_mp-op-pp-development-cycle/blob/master/docs/DEFINITION_OF_DONE.md)

## Agent / repo context

Before making changes, read the agent-oriented docs in this repo:
[`AGENTS.md`](AGENTS.md), [`CLAUDE.md`](CLAUDE.md), the layer rules in
[`.claude/rules/`](.claude/rules), and [`docs/agent/`](docs/agent) (overview, architecture,
contracts, runbook, traps).

## PR process

1. Branch from the base branch (`main`): `feature/<description>` or `fix/<description>`.
2. Implement following the conventions in `CLAUDE.md` and the layer rules in `.claude/rules/`.
3. Run the local checks (see [`docs/agent/runbook.md`](docs/agent/runbook.md) → "Harness de Testes"):
   - Unit tests: `bash bin/run-test.sh`
   - Linters / static analysis: `bash bin/run-linters.sh`
4. Verify the local **Definition of Done** in `docs/agent/runbook.md` before opening the PR.
5. Open a PR against `main` using [`.github/pull_request_template.md`](.github/pull_request_template.md).
6. Update `CHANGELOG.md` and, if agent-relevant behavior changed, bump `version:`/`validated:`
   on the touched `docs/agent/*` guides.

## Ground rules

- Never expose secrets, tokens or credentials.
- User-facing strings use `__('…')` and must exist in **all** `i18n/*.csv` files.
- Never call the MercadoPago API directly — go through `Gateway/Http/` (see
  [`docs/agent/traps.md`](docs/agent/traps.md)).
