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
| `GET` | `/api/bank/invoices/lookup` | Yes | Look up a student fee invoice |
| `GET` | `/api/bank/students/{student_id}/invoices` | Yes | List outstanding invoices for a student |
| `POST` | `/api/bank/invoices/pay` | Yes | Record bank payment & update invoice status |
| `GET` | `/api/bank/payments/{bank_transaction_id}` | Yes | Check payment by bank transaction id |

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
    "invoice_id": "DUC1756123456789",
    "receipt_number": "XK29F0A1B2C3D4E5F6G7",
    "amount_paid": 150,
    "currency": "GHS",
    "academic_year": "2025/2026",
    "transaction_date": "2026-08-14 01:00:00",
    "created_at": "2026-08-14 01:00:00"
  }
}
```

Give the applicant **`serial_number` + `pin`** so they can log in to the student portal. Store **`invoice_id`** and **`amount_paid`** for reconciliation (also printed on the bank receipt).

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
    "invoice_id": "DUC1756123456789",
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

`invoice_id` and `amount_paid` are always returned. `receipt_url` is the printable web receipt (requires a logged-in bank session in the browser). For system integration, use the JSON fields above.

---

## Recommended integration flow

```text
1. POST /api/bank/login
2. GET  /api/bank/form-types   → choose form_type_id
3. POST /api/bank/users        → create applicant after bank payment
4. Store returned invoice_id, amount_paid, serial_number, pin, receipt_number
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

## Student invoice payments (fee invoices)

These endpoints let the bank look up a student's fee invoice and mark it paid when the student pays at the counter / via core banking.

**Typical flow**

1. `POST /api/bank/login` → get Bearer token  
2. Look up invoice (`GET /invoices/lookup` or `GET /students/{student_id}/invoices`)  
3. Collect payment at bank  
4. `POST /api/bank/invoices/pay` with unique `bank_transaction_id`  
5. SIP creates a completed payment, updates invoice `paid` / `partial` / `pending`, and tries to sync to ERP  

**Idempotency:** sending the same `bank_transaction_id` twice returns the existing payment (`already_processed: true`) and does **not** double-apply the amount.

---

### 10. Look up invoice

`GET /api/bank/invoices/lookup`

### Query params

| Field | Required | Notes |
|-------|----------|-------|
| `invoice_number` | One of invoice_number / erp_invoice_id | SIP invoice number (also matches `erp_invoice_id` if used as number) |
| `erp_invoice_id` | One of | ERP Sales Invoice name |
| `student_id` | No | If sent, must match the invoice's student |

### Example

```bash
curl -G "https://sip.delexesuniversity.edu.gh/api/bank/invoices/lookup" \
  -H "Authorization: Bearer {{token}}" \
  -H "Accept: application/json" \
  --data-urlencode "invoice_number=ACC-SINV-2026-00041" \
  --data-urlencode "student_id=DUC/2024/00123"
```

### Success `200`

```json
{
  "success": true,
  "data": {
    "invoice_id": 15,
    "invoice_number": "ACC-SINV-2026-00041",
    "erp_invoice_id": "ACC-SINV-2026-00041",
    "invoice_type": "tuition",
    "academic_year": "2025/2026",
    "semester": "First Semester",
    "total_amount": 2500.0,
    "paid_amount": 0.0,
    "balance": 2500.0,
    "status": "pending",
    "due_date": "2026-10-30",
    "issued_date": "2026-09-01",
    "currency": "GHS",
    "student": {
      "student_id": "DUC/2024/00123",
      "name": "Jane Doe",
      "email": "jane@example.com",
      "program": "BSc Computer Science"
    }
  }
}
```

---

### 11. List outstanding invoices for a student

`GET /api/bank/students/{student_id}/invoices`

Returns invoices with status `pending` or `partial` and balance &gt; 0.

```bash
curl "https://sip.delexesuniversity.edu.gh/api/bank/students/DUC%2F2024%2F00123/invoices" \
  -H "Authorization: Bearer {{token}}" \
  -H "Accept: application/json"
```

### Success `200`

```json
{
  "success": true,
  "data": {
    "student": {
      "student_id": "DUC/2024/00123",
      "name": "Jane Doe",
      "email": "jane@example.com",
      "program": "BSc Computer Science",
      "total_balance": 2500.0
    },
    "invoices": [
      {
        "invoice_number": "ACC-SINV-2026-00041",
        "balance": 2500.0,
        "status": "pending"
      }
    ]
  }
}
```

---

### 12. Record payment (updates invoice status) — main bank callback

`POST /api/bank/invoices/pay`

Call this after the bank has successfully collected funds.

### Request body

```json
{
  "invoice_number": "ACC-SINV-2026-00041",
  "student_id": "DUC/2024/00123",
  "amount": 2500.00,
  "bank_transaction_id": "GCB-20260929-984512",
  "paid_at": "2026-09-29T14:30:00",
  "teller_id": "T-00421",
  "branch_code": "TEMA-01",
  "narration": "Tuition fee payment"
}
```

| Field | Required | Notes |
|-------|----------|-------|
| `invoice_number` | One of invoice_number / erp_invoice_id | SIP / ERP invoice reference |
| `erp_invoice_id` | One of | Alternative to invoice_number |
| `student_id` | Recommended | Must match invoice if provided |
| `amount` | Yes | GHS; must be &gt; 0 and ≤ outstanding balance |
| `bank_transaction_id` | Yes | Unique bank txn id (idempotency key) |
| `paid_at` | No | ISO datetime of payment |
| `teller_id` | No | Teller / operator code |
| `branch_code` | No | Branch code |
| `narration` | No | Free text |

### Success `201` (new) / `200` (duplicate txn)

```json
{
  "success": true,
  "message": "Payment recorded successfully. Invoice status updated.",
  "data": {
    "already_processed": false,
    "erp_synced": true,
    "payment": {
      "payment_reference": "BANKTXN-GCB-20260929-984512",
      "bank_transaction_id": "GCB-20260929-984512",
      "amount": 2500.0,
      "status": "completed",
      "payment_method": "bank",
      "created_at": "2026-09-29T14:30:05+00:00"
    },
    "invoice": {
      "invoice_number": "ACC-SINV-2026-00041",
      "total_amount": 2500.0,
      "paid_amount": 2500.0,
      "balance": 0.0,
      "status": "paid"
    }
  }
}
```

Invoice `status` becomes:

- `paid` when balance ≤ 0  
- `partial` when some amount paid but balance remains  
- unchanged / error if already paid or cancelled  

### Errors

- `404` – invoice not found (lookup only)  
- `422` – validation, student mismatch, amount &gt; balance, already paid, cancelled  
- `401` / `403` – missing/invalid token or not a bank user  

### cURL

```bash
curl -X POST "https://sip.delexesuniversity.edu.gh/api/bank/invoices/pay" \
  -H "Authorization: Bearer {{token}}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "invoice_number": "ACC-SINV-2026-00041",
    "student_id": "DUC/2024/00123",
    "amount": 2500.00,
    "bank_transaction_id": "GCB-20260929-984512",
    "paid_at": "2026-09-29T14:30:00",
    "teller_id": "T-00421",
    "branch_code": "TEMA-01",
    "narration": "Tuition fee payment"
  }'
```

---

### 13. Payment status by bank transaction id

`GET /api/bank/payments/{bank_transaction_id}`

```bash
curl "https://sip.delexesuniversity.edu.gh/api/bank/payments/GCB-20260929-984512" \
  -H "Authorization: Bearer {{token}}" \
  -H "Accept: application/json"
```

---

## Security notes

- Keep the Bearer token secret. Prefer short-lived tokens per environment/device and call `/logout` when done.
- Only bank-role accounts can call protected endpoints.
- Applicants created via API are linked with `created_by` to the bank user (same as the dashboard).
- PIN is returned in create/get/receipt responses so the bank can print it on the voucher; treat it as sensitive.

---

## Sample Postman / collection order

**Admission vouchers**

1. Login → copy `data.token`
2. Form types
3. Create user
4. List users
5. Get receipt

**Student invoice payments**

1. Login → copy `data.token`
2. Look up invoice **or** list student invoices
3. Pay invoice (`POST /invoices/pay`)
4. (Optional) Payment status by `bank_transaction_id`

Header for protected steps:

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
| Counter fee payment (new) | `POST /api/bank/invoices/pay` |

Business rules (PIN generation, serial `DUC######`, pricing, receipt JSON) match the web dashboard for vouchers. Fee invoice payments update the SIP `invoices` / `payments` tables the same way as completed Paystack payments.
