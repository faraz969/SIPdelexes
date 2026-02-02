# ERPNext Integration - Quick Start Guide

## Quick Setup Checklist

### 1. ERPNext Configuration (5 minutes)

- [ ] Create API User in ERPNext
- [ ] Generate API Key and Secret
- [ ] Note down ERPNext base URL (e.g., `https://erpnext.yourdomain.com`)

### 2. Laravel Configuration (2 minutes)

Add to `.env`:
```env
ERP_BASE_URL=https://erpnext.yourdomain.com/api
ERP_API_KEY=your_api_key_from_erpnext
ERP_API_SECRET=your_api_secret_from_erpnext
```

### 3. Test Connection

Run this command to test:
```bash
php artisan tinker
```

Then:
```php
$erp = app(\App\Services\ERPIntegrationService::class);
$result = $erp->createStudentRecord([
    'student_id' => '11000001',
    'biodata' => ['name' => 'Test Student'],
    'program_id' => 1,
    'academic_year' => '2024/2025'
]);
dd($result);
```

## Common Workflows

### Creating an Invoice in ERPNext

1. **Create Customer** (if not exists):
   - Customer Name: Student's Full Name
   - Custom Field `student_id`: Laravel Student ID (e.g., `11000001`)

2. **Create Sales Invoice**:
   - Customer: Select student
   - Items: Add tuition fee items
   - Custom Fields: Set `academic_year` and `semester`
   - Save and Submit

3. **Sync to Laravel** (Automatic if webhook set up):
   - Invoice automatically syncs via webhook
   - Or manually call: `POST /api/method/your_app.api.invoice_api.sync_invoice_to_sip`

### Processing a Payment

1. **Create Payment Entry** in ERPNext:
   - Party: Student Customer
   - Amount: Payment amount
   - Mode: Payment method
   - Save and Submit

2. **Sync to Laravel** (Automatic):
   - Payment automatically confirms in Laravel
   - Student balance updates automatically

## API Endpoints Reference

### Laravel → ERPNext (Outgoing)

**Create Student:**
```
POST {ERP_BASE_URL}/students
Headers: Authorization: Bearer {API_KEY}
Body: {
  "student_id": "11000001",
  "biodata": {...},
  "program_id": 1,
  "academic_year": "2024/2025"
}
```

**Get Invoice:**
```
GET {ERP_BASE_URL}/invoices/{invoice_id}
Headers: Authorization: Bearer {API_KEY}
```

**Process Payment:**
```
POST {ERP_BASE_URL}/payments
Headers: Authorization: Bearer {API_KEY}
Body: {
  "payment_reference": "PAY-001",
  "amount": 5000.00,
  ...
}
```

### ERPNext → Laravel (Incoming)

**Sync Invoice:**
```
POST http://your-laravel-app.com/api/erp/invoices/sync
Body: {
  "erp_invoice_id": "SINV-00001",
  "student_id": "11000001",
  "invoice_number": "SINV-00001",
  "total_amount": 5000.00,
  "academic_year": "2024/2025",
  "semester": "First Semester"
}
```

**Confirm Payment:**
```
POST http://your-laravel-app.com/api/erp/payments/confirm
Body: {
  "payment_reference": "PAY-001",
  "erp_payment_id": "PE-00001",
  "status": "completed"
}
```

**Get Student Balance:**
```
GET http://your-laravel-app.com/api/erp/students/{student_id}/balance
```

## Troubleshooting Quick Fixes

| Issue | Quick Fix |
|-------|-----------|
| 401 Unauthorized | Check API key in `.env` |
| 404 Not Found | Verify ERPNext URL is correct |
| Connection Timeout | Check firewall/network settings |
| Student Not Found | Verify `student_id` matches in both systems |
| Invoice Not Syncing | Check webhook is triggered, review logs |

## Testing Commands

```bash
# Test invoice sync
curl -X POST http://localhost:8000/api/erp/invoices/sync \
  -H "Content-Type: application/json" \
  -d '{"erp_invoice_id":"TEST-001","student_id":"11000001","invoice_number":"TEST-001","total_amount":5000,"academic_year":"2024/2025"}'

# Test payment confirmation
curl -X POST http://localhost:8000/api/erp/payments/confirm \
  -H "Content-Type: application/json" \
  -d '{"payment_reference":"PAY-TEST","erp_payment_id":"PE-TEST","status":"completed"}'

# Check student balance
curl http://localhost:8000/api/erp/students/11000001/balance
```

## Next Steps

1. ✅ Set up API authentication
2. ✅ Configure webhooks
3. ✅ Create test invoice
4. ✅ Test payment processing
5. ✅ Set up automated sync

For detailed instructions, see `ERPNext_INTEGRATION_GUIDE.md`

