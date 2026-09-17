# Apex SMM Reseller API v2 Documentation

Apex SMM Services provides a standardized **SMM API v2** endpoint for resellers, external panels, and automation bots.

- **Endpoint URL**: `https://your-domain.com/api/v2`
- **HTTP Method**: `POST`
- **Content-Type**: `application/x-www-form-urlencoded` or `multipart/form-data`
- **Authentication**: Pass your API Key in the `key` parameter. Find your API key in **Account Settings**.

---

## Actions Overview

| Action | Description |
|---|---|
| `services` | List all available services with rates and limits |
| `add` | Place a new order |
| `status` | Query the fulfillment status of an existing order |
| `balance` | Check current account balance and currency |

---

## 1. Service List (`action=services`)

Retrieve the list of all active wholesale services.

### Request
```bash
curl -X POST https://your-domain.com/api/v2 \
  -d "key=YOUR_API_KEY" \
  -d "action=services"
```

### Response
```json
[
  {
    "service": "1",
    "name": "Instagram High Quality Followers [Non-Drop] [30 Days Refill]",
    "type": "Default",
    "category": "Instagram Followers & Likes",
    "rate": "79.50",
    "min": "50",
    "max": "100000",
    "refill": true,
    "cancel": false
  },
  {
    "service": "6",
    "name": "TikTok Custom Comments [Real Looking Users]",
    "type": "Custom Comments",
    "category": "TikTok Engagement & Views",
    "rate": "320.00",
    "min": "10",
    "max": "5000",
    "refill": false,
    "cancel": false
  }
]
```

---

## 2. Add Order (`action=add`)

Submit a new order for automated processing.

### Request (Default Service)
```bash
curl -X POST https://your-domain.com/api/v2 \
  -d "key=YOUR_API_KEY" \
  -d "action=add" \
  -d "service=1" \
  -d "link=https://instagram.com/techcreator_official" \
  -d "quantity=1000"
```

### Request (Custom Comments)
```bash
curl -X POST https://your-domain.com/api/v2 \
  -d "key=YOUR_API_KEY" \
  -d "action=add" \
  -d "service=6" \
  -d "link=https://www.tiktok.com/@username/video/123456789" \
  -d "comments=Awesome video!
Loved the edits!
Keep it up!"
```

### Success Response
```json
{
  "order": 1042
}
```

### Error Response
```json
{
  "error": "Not enough funds on balance"
}
```

---

## 3. Order Status (`action=status`)

Check the status of a single order or batch of orders.

### Single Order Request
```bash
curl -X POST https://your-domain.com/api/v2 \
  -d "key=YOUR_API_KEY" \
  -d "action=status" \
  -d "order=1042"
```

### Response
```json
{
  "charge": "79.50",
  "start_count": "12450",
  "status": "In progress",
  "remains": "250",
  "currency": "INR"
}
```

### Multiple Orders Request
```bash
curl -X POST https://your-domain.com/api/v2 \
  -d "key=YOUR_API_KEY" \
  -d "action=status" \
  -d "orders=1041,1042,1043"
```

### Response
```json
{
  "1041": {
    "charge": "18.00",
    "start_count": "340",
    "status": "Completed",
    "remains": "0",
    "currency": "INR"
  },
  "1042": {
    "charge": "79.50",
    "start_count": "12450",
    "status": "In progress",
    "remains": "250",
    "currency": "INR"
  },
  "1043": {
    "error": "Incorrect order ID"
  }
}
```

---

## 4. Account Balance (`action=balance`)

Query the current wallet funds of the API key owner.

### Request
```bash
curl -X POST https://your-domain.com/api/v2 \
  -d "key=YOUR_API_KEY" \
  -d "action=balance"
```

### Response
```json
{
  "balance": "1420.50",
  "currency": "INR"
}
```

---

## Error Codes Reference

| Error Message | Meaning |
|---|---|
| `Invalid API key` | The provided key does not exist or account is inactive |
| `Missing action parameter` | No `action` was provided |
| `Not enough funds on balance` | Account balance is below the order charge |
| `Quantity must be between min and max` | Quantity is outside bounds for this service |
| `Incorrect order ID` | Order was not found under this user account |
