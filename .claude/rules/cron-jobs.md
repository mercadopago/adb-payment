---
paths:
  - "Cron/**/*.php"
---

# Cron Jobs

Scheduled jobs that poll external payment status from MercadoPago and handle stale order cancellation.

## Existing Jobs

- `CancelCheckoutCredits.php` -- Cancels stale Checkout Credits orders
- `CancelCheckoutPro.php` -- Cancels stale Checkout Pro orders
- `FetchMerchantInfo.php` -- Fetches merchant account information
- `FetchPaymentMethodsOffOrderStatus.php` -- Fetches offline payment method order statuses
- `FetchPixOrderStatus.php` -- Polls Pix payment status
- `FetchPseOrderStatus.php` -- Polls PSE payment status
- `FetchWebpayOrderStatus.php` -- Polls Webpay payment status
- `FetchYapeOrderStatus.php` -- Polls Yape payment status

## Configuration

- Cron groups defined in `etc/cron_groups.xml`
- Cron schedules defined in `etc/crontab.xml`
- Each job class has an `execute()` method invoked by the Magento cron runner

## Rules

- Cron jobs must be idempotent -- safe to run multiple times on the same data
- Include proper error handling and logging -- cron failures are silent by default
- Limit batch sizes to avoid memory issues with large order volumes
- Use `Model/Metrics/MetricsClient` to instrument cron execution times and error counts
- Status fetch jobs must handle API timeouts gracefully without marking orders as failed
- Cancellation jobs must verify the order hasn't been paid in the meantime (race condition)
