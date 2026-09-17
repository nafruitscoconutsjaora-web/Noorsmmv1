# Automated Background Tasks & Cron Guide

Apex SMM Panel relies on background tasks to continuously synchronize order statuses with upstream wholesale providers, retry failed order placements, reconcile pending payments, and perform periodic log maintenance.

---

## Setting up System Crontab

Add the following entry to your Linux server's crontab (`crontab -e`):

```bash
# Run SMM Panel scheduled jobs every 1 minute
* * * * * php /path/to/apex-smm/bin/cron.php all >> /path/to/apex-smm/storage/logs/cron.log 2>&1
```

---

## Individual Task Breakdown

You can run individual tasks on distinct schedules using:

```bash
php bin/cron.php [task_name]
```

### 1. `update_order_statuses`
- **Recommended Frequency**: Every 1 to 2 minutes (`*/1 * * * *`)
- **Action**: Queries upstream providers for orders in `pending`, `processing`, or `in_progress` status. Updates start counter, remaining units, and marks as `completed` or `canceled` (with automated wallet refund if partial/canceled).

### 2. `retry_failed_orders`
- **Recommended Frequency**: Every 5 minutes (`*/5 * * * *`)
- **Action**: Inspects orders that experienced transient provider API network errors or timeouts, retrying placement automatically up to 3 times before flagging for admin review.

### 3. `auto_refill`
- **Recommended Frequency**: Every 10 minutes (`*/10 * * * *`)
- **Action**: Submits refill requests to upstream providers for drop-protected services when refill requests are triggered by users.

### 4. `sync_provider_services`
- **Recommended Frequency**: Every 6 hours (`0 */6 * * *`)
- **Action**: Fetches updated service listings, status flags, and description changes from connected wholesale providers.

### 5. `sync_provider_prices`
- **Recommended Frequency**: Daily (`0 2 * * *`)
- **Action**: Audits upstream wholesale rate increases and recalibrates retail rates according to configured profit margin rules (percentage markup or fixed addition).

### 6. `payment_reconciliation`
- **Recommended Frequency**: Every 15 minutes (`*/15 * * * *`)
- **Action**: Polls Razorpay for unconfirmed or pending checkout intents to prevent missed webhooks or dropped network connections.

### 7. `cleanup`
- **Recommended Frequency**: Daily at midnight (`0 0 * * *`)
- **Action**: Purges expired sessions, rate limiting caches, and rotates execution logs older than 30 days.

---

## Execution Logs & Monitoring

All cron executions are recorded in the database table `cron_logs` and can be inspected in real-time under:
**Admin Panel → Settings & System Configuration → Background Cron Tasks & Scheduler**.
