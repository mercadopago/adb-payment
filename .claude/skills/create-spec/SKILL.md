---
name: create-spec
description: >-
  Create a functional or technical specification document for a
  new feature or system change. Uses the spec-writer agent.
argument-hint: "[feature name or description]"
allowed-tools: Read, Write, Glob, Grep
---

# Create Specification

Create a spec document for: **$ARGUMENTS**

## Instructions

1. Use the **spec-writer** agent to research the codebase and generate the spec
2. The spec should cover:
   - Overview and motivation
   - Functional requirements
   - Technical design following the existing Magento 2 Payment Gateway pattern
   - MercadoPago API contracts
   - DI configuration needed (`etc/di.xml` entries)
   - Testing strategy (PHPUnit + Playwright E2E)
   - i18n strings for all supported locales
   - Open questions
3. Save the spec to the project's `docs/` directory
4. Ask the user to review before finalizing
