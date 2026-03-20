---
paths:
  - "Model/Metrics/**/*.php"
---

# Observability / Metrics

Metrics instrumentation layer for payment operations.

## Structure

- `Model/Metrics/Config.php` -- Metrics configuration
- `Model/Metrics/CoreMonitorAdapter.php` -- Adapter to core monitoring system
- `Model/Metrics/MetricsClient.php` -- Client for recording metrics

## Conventions

- All payment operations (create, capture, refund, cancel) should record metrics
- Use `MetricsClient` as the single entry point for instrumentation
- Metric names should follow a consistent naming convention
- Record both success and failure counts, plus latency

## Rules

- Never let metrics failures break payment flows -- always catch and log
- Metrics calls should be non-blocking where possible
- Include payment method type as a dimension/tag in all metrics
- Cron job execution should also be instrumented via MetricsClient
