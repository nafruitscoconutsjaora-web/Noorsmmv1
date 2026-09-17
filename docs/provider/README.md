# Upstream SMM Provider Integration Guide

Apex SMM Panel integrates seamlessly with any wholesale SMM provider that supports the standard **SMM API v2** specification.

---

## 1. Connecting a Wholesale Provider

1. Navigate to **Admin Panel → Providers**.
2. Click **Add New Provider**.
3. Enter the following details:
   - **Provider Name**: e.g., `Wholesale SMM Hub`
   - **API URL**: e.g., `https://wholesalesmmhub.com/api/v2`
   - **API Key**: The secret key provided in your upstream provider's account.
   - **Currency**: `USD`, `INR`, `EUR`, etc.
4. Click **Save Provider**.
5. Once saved, click **Test Connection** to verify API connectivity and sync live upstream balance.

---

## 2. Importing Wholesale Services

1. Click **Import Services** next to the connected provider.
2. Select your desired profit margin rule:
   - **Percentage Margin**: e.g., `50%` markup (Wholesale \$1.00 becomes \$1.50).
   - **Fixed Amount Margin**: e.g., `+$0.20` markup added to wholesale cost.
3. Review the preview list showing:
   - Service ID
   - Original Service Name
   - Upstream Wholesale Rate
   - Calculated Retail Rate
4. Check the services you want to offer in your catalog and click **Import Selected Services**.
5. All imported services are immediately assigned to corresponding categories or imported under their wholesale category names.

---

## 3. Automated Order Forwarding & Real-Time Sync

When a customer places an order:
1. Customer funds are deducted atomically from their internal wallet.
2. The order is placed in the local queue with status `pending`.
3. If provider automation is active, the order is dispatched via `POST` to the provider's `/api/v2` with `action=add`.
4. The provider's `order` identifier is stored locally (`provider_order_id`).
5. Every minute, the background cron queries the provider (`action=status`) to sync `start_count`, `remains`, and changes in status (`in_progress`, `completed`, `partial`, `canceled`).
6. If an order is canceled or partially fulfilled by the provider, the remaining balance is automatically refunded to the customer's wallet ledger.
