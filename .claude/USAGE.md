# Claude Code Setup -- Usage Guide

This project is configured with Claude Code rules, agents, skills, and hooks.

## Quick Start

1. Open the project in your terminal
2. Run `claude` to start Claude Code
3. Type `/skills` to see available skills

## Rules (`.claude/rules/`)

Rules are automatically loaded when Claude Code edits files matching their path patterns.
Each rule file has a `paths:` frontmatter that scopes it to specific directories.

**Available rules:**
- `api-interfaces.md` -- Service contract interface conventions
- `api-handlers.md` -- Controller/HTTP handler patterns
- `payment-gateway.md` -- Magento Payment Gateway layer (largest layer)
- `domain-model.md` -- Core domain model conventions
- `helper.md` -- Stateless utility helper patterns
- `observers.md` -- Event observer conventions
- `plugins.md` -- Interceptor plugin patterns
- `cron-jobs.md` -- Scheduled job conventions
- `console-commands.md` -- CLI command patterns
- `setup-patches.md` -- Migration and patch conventions
- `observability.md` -- Metrics instrumentation
- `frontend-view.md` -- Storefront templates, JS, layout XML
- `adminhtml-view.md` -- Admin panel presentation
- `module-config.md` -- XML configuration files
- `i18n.md` -- Translation CSV conventions
- `testing-unit.md` -- PHPUnit test conventions
- `testing-e2e.md` -- Playwright E2E test conventions
- `ci-cd.md` -- GitHub Actions workflow conventions
- `scripts.md` -- Shell scripts and Docker setup

## Agents (`.claude/agents/`)

Agents are specialized sub-agents that can be invoked for specific tasks.

- **test-runner** -- Runs unit tests (PHPUnit) or E2E tests (Playwright) with smart scoping
- **researcher** -- Read-only codebase investigation (find patterns, trace dependencies)
- **spec-writer** -- Creates technical specifications for new features

## Skills (`.claude/skills/`)

Skills are repeatable workflows. Invoke them with `/skills` or `/{skill-name}`.

| Skill | Description |
|-------|-------------|
| `run-tests` | Run PHPUnit or Playwright tests |
| `fix-lint` | Run PHPCS, PHPStan, PHPMD checks |
| `create-spec` | Generate a technical specification |
| `review-changes` | Pre-commit review of staged changes |
| `commit` | Validate (lint + test) and commit |
| `pr-description` | Generate PR description and open PR via `gh` |

## Hooks (`.claude/settings.json`)

- **PostToolUse (Edit/Write):** Auto-runs PHPCS on modified PHP files
- **Stop:** Verification prompt to check for debug statements, TODOs, coding standard compliance

## Security Rules

This project includes MeLi AppSec security rules in `AGENTS.md` and `.agentic-rules/`.
These provide language-specific security patterns for AI agents.

## Customizing

### Adding a rule
Create a new `.md` file in `.claude/rules/` with path-scoping frontmatter:
```yaml
---
paths:
  - "your/glob/pattern/**"
---
```

### Adding a skill
Create a new directory in `.claude/skills/` with a `SKILL.md` file containing frontmatter:
```yaml
---
name: your-skill
description: What it does
argument-hint: "[arguments]"
allowed-tools: Bash, Read, Glob, Grep
---
```

### Modifying hooks
Edit `.claude/settings.json` to change PostToolUse or Stop hooks.
