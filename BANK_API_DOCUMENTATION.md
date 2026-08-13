# Bank Integration API Documentation

APIs for banks to create admission-form voucher applicants programmatically — the same flow as the **Bank Dashboard**.

**Base URL**

```
{APP_URL}/api/bank
```

Examples:

- Production: `https://sip.delexesuniversity.edu.gh/api/bank`

**Content type**

```
Content-Type: application/json
Accept: application/json
```

**Authentication**

1. Call `POST /api/bank/login` with a **bank role** account.
2. Use the returned token on every protected request:

```
Authorization: Bearer {token}
```

Only users with `role = bank` can use these endpoints.

---

## Endpoints overview

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/api/bank/login` | No | Get API token |
| `POST` | `/api/bank/logout` | Yes | Revoke current token |
| `GET` | `/api/bank/me` | Yes | Bank profile |
| `GET` | `/api/bank/form-types` | Yes | Active form types / prices |
| `GET` | `/api/bank/countries` | Yes | Nationalities list |
| `POST` | `/api/bank/users` | Yes | Create applicant + voucher |
| `GET` | `/api/bank/users` | Yes | List applicants created by this bank |
| `GET` | `/api/bank/users/{id}` | Yes | Get one applicant |
| `GET` | `/api/bank/users/{id}/receipt` | Yes | Receipt details (PIN, serial, amount) |

---

## 1. Login

`POST /api/bank/login`

### Request body

```json
{
  "email": "bank@example.com",
  "password": "your-password",
  "device_name": "gcb-core-banking"
}
```

| Field | Required | Notes |
|-------|----------|-------|
| `email` | Yes | Bank account email |
| `password` | Yes | Bank account password |
| `device_name` | No | Label for the token (default: `bank-api`) |

### Success `200`

```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "token_type": "Bearer",
    "bank": {
      "id": 12,
      "name": "GCB Tema Branch",
      "email": "bank@example.com",
      "bank_name": "GCB Bank",
      "branch": "Tema"
    }
  }
}
```

### Errors

- `422` – validation / wrong credentials  
- `403` – account is not a bank role  

### cURL

```bash
curl -X POST "https://sip.delexesuniversity.edu.gh/api/bank/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "bank@example.com",
    "password": "your-password",
    "device_name": "gcb-core-banking"
  }'
```

---

## 2. Logout

`POST /api/bank/logout`

```bash
curl -X POST "https://sip.delexesuniversity.edu.gh/api/bank/logout" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 3. Bank profile

`GET /api/bank/me`

Returns the authenticated bank account details.

---

## 4. List form types

`GET /api/bank/form-types`

Use these IDs when creating a user (`form_type_id`).

### Success `200`

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Undergraduate Application Form",
      "local_price": 150.00,
      "international_price": 50.00,
      "conversion_rate": 12.5000,
      "description": "..."
    }
  ]
}
```

**Pricing rule (same as dashboard):**

- Nationality `Ghana` → `local_price` (GHS)
- Other nationalities → `international_price` × `conversion_rate` (converted to GHS)

---

## 5. List countries

`GET /api/bank/countries`

Returns nationality options (name, dial code, flag, etc.).

---

## 6. Create applicant (main integration endpoint)

`POST /api/bank/users`

Creates an applicant, generates **serial number + PIN**, stores receipt/payment data, and optionally sends SMS.

### Request body

```json
{
  "name": "Ama Mensah",
  "email": "ama.mensah@example.com",
  "phone": "+233551234567",
  "nationality": "Ghana",
  "form_type_id": 1,
  "voucher_for": "Ama Mensah",
  "send_sms": true
}
```

| Field | Required | Notes |
|-------|----------|-------|
| `name` | Yes | Full name |
| `email` | No | Must be unique if provided. Auto-generated if omitted |
| `phone` | Yes | Used for SMS |
| `nationality` | Yes | Use exact country name (e.g. `Ghana`) |
| `form_type_id` | Yes | From `/form-types` |
| `voucher_for` | No | Name shown on receipt |
| `send_sms` | No | Default `true`. Set `false` to skip SMS |

### Success `201`

```json
{
  "success": true,
  "message": "User created successfully",
  "data": {
    "user_id": 101,
    "name": "Ama Mensah",
    "email": "ama.mensah@example.com",
    "phone": "+233551234567",
    "nationality": "Ghana",
    "form_type": {
      "id": 1,
      "name": "Undergraduate Application Form"
    },
    "serial_number": "DUC482193",
    "pin": "A1B2C3D4",
    "pin_expires_at": "2026-11-14 01:00:00",
    "receipt_number": "XK29F0A1B2C3D4E5F6G7",
    "amount_paid": 150,
    "currency": "GHS",
    "academic_year": "2025/2026",
    "transaction_date": "2026-08-14 01:00:00",
    "created_at": "2026-08-14 01:00:00"
  }
}
```

Give the applicant **`serial_number` + `pin`** so they can log in to the student portal.

### Errors

- `401` – missing/invalid token  
- `403` – not a bank account  
- `404` – form type unavailable  
- `422` – validation (e.g. duplicate email)  

### cURL

```bash
curl -X POST "https://sip.delexesuniversity.edu.gh/api/bank/users" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "name": "Ama Mensah",
    "email": "ama.mensah@example.com",
    "phone": "+233551234567",
    "nationality": "Ghana",
    "form_type_id": 1,
    "voucher_for": "Ama Mensah",
    "send_sms": true
  }'
```

---

## 7. List applicants created by this bank

`GET /api/bank/users?search=ama&per_page=20`

| Query | Default | Notes |
|-------|---------|-------|
| `search` | — | Matches name, email, phone, serial number |
| `per_page` | `20` | Max `100` |

Only returns users with `created_by = authenticated bank`.

---

## 8. Get applicant

`GET /api/bank/users/{id}`

Includes PIN for that applicant (scoped to this bank).

---

## 9. Get receipt

`GET /api/bank/users/{id}/receipt`

### Success `200`

```json
{
  "success": true,
  "data": {
    "receipt_number": "XK29F0A1B2C3D4E5F6G7",
    "institution": "Delexes University College",
    "form_type": "Undergraduate Application Form",
    "serial_number": "DUC482193",
    "pin": "A1B2C3D4",
    "bank_name": "GCB Bank",
    "branch": "Tema",
    "bank_logo": null,
    "academic_year": "2025/2026",
    "transaction_date": "2026-08-14 01:00:00",
    "payment_description": "Payment of Voucher for Ama Mensah",
    "amount_paid": 150,
    "currency": "GHS",
    "paid_by": "Ama Mensah",
    "voucher_for": "Ama Mensah",
    "receipt_url": "https://sip.delexesuniversity.edu.gh/bank/users/101/receipt"
  }
}
```

`receipt_url` is the printable web receipt (requires a logged-in bank session in the browser). For system integration, use the JSON fields above.

---

## Recommended integration flow

```text
1. POST /api/bank/login
2. GET  /api/bank/form-types   → choose form_type_id
3. POST /api/bank/users        → create applicant after bank payment
4. Store returned serial_number, pin, receipt_number, amount_paid
5. Optionally GET /api/bank/users/{id}/receipt for reprint/sync
```

---

## Error response format

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email has already been taken."]
  }
}
```

Common HTTP codes: `200`, `201`, `401`, `403`, `404`, `422`, `500`.

---

## Security notes

- Keep the Bearer token secret. Prefer short-lived tokens per environment/device and call `/logout` when done.
- Only bank-role accounts can call protected endpoints.
- Applicants created via API are linked with `created_by` to the bank user (same as the dashboard).
- PIN is returned in create/get/receipt responses so the bank can print it on the voucher; treat it as sensitive.

---

## Sample Postman / collection order

1. Login → copy `data.token`
2. Form types
3. Create user
4. List users
5. Get receipt

Header for steps 2–5:

```
Authorization: Bearer {{token}}
Accept: application/json
```

---

## Relation to bank dashboard

| Dashboard action | API equivalent |
|------------------|----------------|
| Login to `/bank/dashboard` | `POST /api/bank/login` |
| Create user form | `POST /api/bank/users` |
| Users table | `GET /api/bank/users` |
| Download receipt | `GET /api/bank/users/{id}/receipt` |

Business rules (PIN generation, serial `DUC######`, pricing, receipt JSON) match the web dashboard.
