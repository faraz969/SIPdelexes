# ERPNext Integration Setup Guide

This guide will help you set up ERPNext integration with your Laravel SIP system to create and manage invoices and payments.

## Table of Contents
1. [Prerequisites](#prerequisites)
2. [ERPNext Setup](#erpnext-setup)
3. [Laravel Configuration](#laravel-configuration)
4. [Creating API Endpoints in ERPNext](#creating-api-endpoints-in-erpnext)
5. [Setting Up Webhooks](#setting-up-webhooks)
6. [Creating Invoices in ERPNext](#creating-invoices-in-erpnext)
7. [Syncing Payments](#syncing-payments)
8. [Testing the Integration](#testing-the-integration)

---

## Prerequisites

- ERPNext instance installed and accessible
- Admin access to ERPNext
- Laravel application running
- Both systems accessible over network (same network or public URLs)

---

## ERPNext Setup

### Step 1: Create API User in ERPNext

1. **Login to ERPNext** as Administrator
2. Go to **Settings** → **Users and Permissions** → **User**
3. Click **New** to create a new user
4. Fill in the details:
   - **Email**: `api@delexesuniversity.edu.gh` (or your preferred email)
   - **Full Name**: `API Integration User`
   - **User Type**: `System User`
   - **Role**: Create a new role or use existing with appropriate permissions
5. **Save** the user

### Step 2: Generate API Key and Secret

1. Go to **Settings** → **Integrations** → **API Keys**
2. Click **New** to create a new API key
3. Fill in:
   - **User**: Select the API user you created
   - **Key Name**: `Laravel SIP Integration`
   - **Expires On**: Set expiration date (optional)
4. **Save** and **copy** the generated:
   - **API Key**
   - **API Secret**

**Important**: Store these credentials securely. You'll need them for Laravel configuration.

### Step 3: Set Up Custom Fields (Optional but Recommended)

To link ERPNext documents with Laravel, add custom fields:

1. **For Customer (Student)**:
   - Go to **Customer** doctype
   - Go to **Customize** → **Customize Form**
   - Add custom fields:
     - `student_id` (Data) - Link to Laravel student ID
     - `sip_synced` (Check) - Track sync status

2. **For Sales Invoice**:
   - Go to **Sales Invoice** doctype
   - Add custom fields:
     - `student_id` (Data) - Link to Laravel student ID
     - `sip_invoice_id` (Data) - Laravel invoice ID
     - `academic_year` (Data)
     - `semester` (Data)
     - `sip_synced` (Check)

---

## Laravel Configuration

### Step 1: Update Environment Variables

Edit your `.env` file:

```env
# ERPNext Integration
ERP_BASE_URL=https://your-erpnext-instance.com/api
ERP_API_KEY=your_api_key_here
ERP_API_SECRET=your_api_secret_here
```

**Note**: 
- Replace `your-erpnext-instance.com` with your actual ERPNext URL
- Use the API Key and Secret from Step 2 above

### Step 2: Update Services Configuration

The configuration is already set up in `config/services.php`. Verify it matches:

```php
'erp' => [
    'base_url' => env('ERP_BASE_URL', 'http://localhost:8000/api'),
    'api_key' => env('ERP_API_KEY', ''),
],
```

**Important**: ERPNext REST API uses `/api/resource/` endpoints. Your base URL should be:
- Full URL: `https://your-erpnext.com/api/resource`
- Or just: `https://your-erpnext.com/api` (if you're using custom endpoints)

### Step 3: Update ERPIntegrationService for ERPNext Authentication

ERPNext uses a specific authentication format. You may need to update `app/Services/ERPIntegrationService.php`:

**Current Implementation** (Bearer token):
```php
'Authorization' => 'Bearer ' . $this->erpApiKey
```

**ERPNext Standard Format** (token api_key:api_secret):
```php
'Authorization' => 'token ' . $this->erpApiKey . ':' . $this->erpApiSecret
```

**Option 1**: Update the service to support ERPNext format:
```php
// In ERPIntegrationService constructor
$this->erpApiSecret = config('services.erp.api_secret', '');

// In each HTTP request
'Authorization' => 'token ' . $this->erpApiKey . ':' . $this->erpApiSecret
```

**Option 2**: Use custom API endpoints (recommended) that accept Bearer tokens.

Add to `.env`:
```env
ERP_API_SECRET=your_api_secret_from_erpnext
```

---

## Creating API Endpoints in ERPNext

You need to create custom API endpoints in ERPNext to:
1. Receive student data from Laravel
2. Send invoice data to Laravel
3. Send payment confirmations to Laravel

### Method 1: Using ERPNext REST API (Standard)

ERPNext has built-in REST API endpoints. However, for custom workflows, you'll need custom endpoints.

**Standard ERPNext REST API Endpoints:**
- Create Customer: `POST /api/resource/Customer`
- Create Sales Invoice: `POST /api/resource/Sales Invoice`
- Create Payment Entry: `POST /api/resource/Payment Entry`
- Get Document: `GET /api/resource/{DocType}/{name}`

**Authentication:**
```bash
# Get API Key and Secret from ERPNext
# Use format: api_key:api_secret
curl -X GET "https://your-erpnext.com/api/resource/Customer/STU-11000001" \
  -H "Authorization: token api_key:api_secret"
```

### Method 2: Create Custom API Endpoints (Recommended for Integration)

ERPNext has built-in REST API. You can use it directly, but you may need custom scripts for specific workflows.

### Method 2: Create Custom API Endpoints (Python Script)

Create a custom API endpoint in ERPNext using Python:

1. **Create a new file** in your ERPNext instance:
   ```
   /home/frappe/frappe-bench/apps/your_app/your_app/api/
   ```

2. **Create `student_api.py`**:
   ```python
   import frappe
   from frappe import _
   from frappe.utils import nowdate
   
   @frappe.whitelist(allow_guest=False)
   def create_student_from_sip(data):
       """Create or update student in ERPNext from Laravel SIP"""
       try:
           # Parse JSON data
           if isinstance(data, str):
               data = json.loads(data)
           
           student_id = data.get('student_id')
           customer_name = f"STU-{student_id}"
           
           # Check if customer exists
           if frappe.db.exists("Customer", customer_name):
               customer = frappe.get_doc("Customer", customer_name)
           else:
               customer = frappe.new_doc("Customer")
               customer.customer_name = data.get('biodata', {}).get('name', f"Student {student_id}")
               customer.customer_group = "Student"
               customer.territory = "Ghana"
           
           # Update custom fields
           customer.db_set('student_id', student_id)
           customer.db_set('sip_synced', 1)
           
           customer.save(ignore_permissions=True)
           
           return {
               'success': True,
               'customer_name': customer.name,
               'message': 'Student created/updated successfully'
           }
       except Exception as e:
           frappe.log_error(f"Error creating student: {str(e)}")
           return {'success': False, 'error': str(e)}
   ```

3. **Create `invoice_api.py`**:
   ```python
   import frappe
   import json
   from frappe.utils import nowdate, add_days
   
   @frappe.whitelist(allow_guest=False)
   def sync_invoice_to_sip(invoice_name):
       """Sync invoice from ERPNext to Laravel SIP"""
       try:
           invoice = frappe.get_doc("Sales Invoice", invoice_name)
           
           # Get student ID from customer
           customer = frappe.get_doc("Customer", invoice.customer)
           student_id = customer.get('student_id')
           
           if not student_id:
               return {'success': False, 'error': 'Student ID not found'}
           
           # Prepare invoice data
           invoice_data = {
               'erp_invoice_id': invoice.name,
               'student_id': student_id,
               'invoice_number': invoice.name,
               'invoice_type': 'tuition',  # or get from custom field
               'total_amount': float(invoice.grand_total),
               'paid_amount': float(invoice.outstanding_amount),
               'balance': float(invoice.outstanding_amount),
               'status': 'paid' if invoice.outstanding_amount == 0 else 'pending',
               'due_date': invoice.due_date.strftime('%Y-%m-%d') if invoice.due_date else None,
               'issued_date': invoice.posting_date.strftime('%Y-%m-%d'),
               'academic_year': invoice.get('academic_year') or '',
               'semester': invoice.get('semester') or '',
               'line_items': [
                   {
                       'item': item.item_name,
                       'description': item.description,
                       'quantity': item.qty,
                       'rate': float(item.rate),
                       'amount': float(item.amount)
                   }
                   for item in invoice.items
               ]
           }
           
           # Send to Laravel API
           import requests
           laravel_url = frappe.conf.get('laravel_api_url', 'http://your-laravel-app.com/api/erp/invoices/sync')
           
           response = requests.post(
               laravel_url,
               json=invoice_data,
               headers={'Content-Type': 'application/json'},
               timeout=10
           )
           
           if response.status_code == 200:
               # Mark as synced
               frappe.db.set_value("Sales Invoice", invoice_name, "sip_synced", 1)
               return {'success': True, 'message': 'Invoice synced successfully'}
           else:
               return {'success': False, 'error': response.text}
               
       except Exception as e:
           frappe.log_error(f"Error syncing invoice: {str(e)}")
           return {'success': False, 'error': str(e)}
   ```

4. **Create `payment_api.py`**:
   ```python
   import frappe
   import json
   import requests
   
   @frappe.whitelist(allow_guest=False)
   def confirm_payment_to_sip(payment_entry_name):
       """Send payment confirmation to Laravel SIP"""
       try:
           payment = frappe.get_doc("Payment Entry", payment_entry_name)
           
           # Get payment reference from custom field or reference
           payment_reference = payment.get('payment_reference') or payment.name
           
           payment_data = {
               'payment_reference': payment_reference,
               'erp_payment_id': payment.name,
               'status': 'completed' if payment.docstatus == 1 else 'failed',
               'amount': float(payment.paid_amount),
               'payment_date': payment.posting_date.strftime('%Y-%m-%d'),
               'payment_method': payment.mode_of_payment,
           }
           
           # Send to Laravel API
           laravel_url = frappe.conf.get('laravel_api_url', 'http://your-laravel-app.com/api/erp/payments/confirm')
           
           response = requests.post(
               laravel_url,
               json=payment_data,
               headers={'Content-Type': 'application/json'},
               timeout=10
           )
           
           if response.status_code == 200:
               return {'success': True, 'message': 'Payment confirmed in SIP'}
           else:
               return {'success': False, 'error': response.text}
               
       except Exception as e:
           frappe.log_error(f"Error confirming payment: {str(e)}")
           return {'success': False, 'error': str(e)}
   ```

5. **Add to `__init__.py`**:
   ```python
   from .student_api import create_student_from_sip
   from .invoice_api import sync_invoice_to_sip
   from .payment_api import confirm_payment_to_sip
   ```

---

## Setting Up Webhooks

### Option 1: ERPNext Document Events (Recommended)

Use ERPNext's built-in hooks to automatically sync data:

1. **Create a hook file** in your ERPNext app:
   ```
   hooks.py
   ```

2. **Add hooks**:
   ```python
   # Automatically sync invoice when submitted
   def on_submit_sales_invoice(doc, method):
       # Call your sync function
       from your_app.api.invoice_api import sync_invoice_to_sip
       sync_invoice_to_sip(doc.name)
   
   # Automatically sync payment when submitted
   def on_submit_payment_entry(doc, method):
       from your_app.api.payment_api import confirm_payment_to_sip
       confirm_payment_to_sip(doc.name)
   ```

### Option 2: Scheduled Job

Create a scheduled job in ERPNext to periodically sync invoices:

1. Go to **Scheduler** in ERPNext
2. Create a new scheduled job:
   - **Method**: `your_app.api.invoice_api.sync_pending_invoices`
   - **Frequency**: Daily or Hourly

---

## Creating Invoices in ERPNext

### Step 1: Create Customer (Student) in ERPNext

When a student is created in Laravel, it automatically calls ERPNext API. You can also manually create:

1. Go to **Selling** → **Customer** → **New**
2. Fill in:
   - **Customer Name**: Student's full name
   - **Customer Group**: Student
   - **Territory**: Ghana
   - **Custom Field - Student ID**: Enter the Laravel student ID (e.g., `11000001`)
3. **Save**

### Step 2: Create Sales Invoice

1. Go to **Accounts** → **Sales Invoice** → **New**
2. Fill in:
   - **Customer**: Select the student customer
   - **Posting Date**: Invoice date
   - **Due Date**: Payment due date
   - **Items**: Add invoice items:
     - **Item**: Tuition Fee (or create item)
     - **Quantity**: 1
     - **Rate**: Amount
   - **Custom Fields**:
     - **Academic Year**: e.g., "2024/2025"
     - **Semester**: e.g., "First Semester"
3. **Save** and **Submit**

### Step 3: Sync Invoice to Laravel

**Automatic**: If webhooks are set up, invoice syncs automatically on submit.

**Manual**: 
1. Go to the submitted invoice
2. Click **Custom Button** (if added) or use API:
   ```
   POST /api/method/your_app.api.invoice_api.sync_invoice_to_sip
   Body: {"invoice_name": "SINV-00001"}
   ```

---

## Syncing Payments

### Step 1: Create Payment Entry in ERPNext

1. Go to **Accounts** → **Payment Entry** → **New**
2. Fill in:
   - **Payment Type**: Receive
   - **Party Type**: Customer
   - **Party**: Select student customer
   - **Paid Amount**: Payment amount
   - **Mode of Payment**: Cash/Bank Transfer/etc.
   - **Reference No.**: Payment reference from Laravel (if syncing from SIP)
3. **Save** and **Submit**

### Step 2: Sync Payment to Laravel

**Automatic**: If webhooks are set up, payment syncs automatically.

**Manual**: Use API endpoint or custom button.

---

## Testing the Integration

### Test 1: Create Student in Laravel

1. Approve an application in Laravel (Registrar)
2. Check ERPNext → Customer
3. Verify student customer is created with correct Student ID

### Test 2: Create Invoice in ERPNext

1. Create a Sales Invoice in ERPNext for a student
2. Submit the invoice
3. Check Laravel → Admin → ERP Dashboard → Invoices
4. Verify invoice appears with correct data

### Test 3: Process Payment

1. Create Payment Entry in ERPNext
2. Submit payment
3. Check Laravel → Admin → ERP Dashboard → Payments
4. Verify payment status is updated

### Test 4: Check API Endpoints

Test Laravel API endpoints directly:

```bash
# Test invoice sync endpoint
curl -X POST http://your-laravel-app.com/api/erp/invoices/sync \
  -H "Content-Type: application/json" \
  -d '{
    "erp_invoice_id": "SINV-00001",
    "student_id": "11000001",
    "invoice_number": "SINV-00001",
    "total_amount": 5000.00,
    "academic_year": "2024/2025",
    "semester": "First Semester"
  }'

# Test payment confirmation
curl -X POST http://your-laravel-app.com/api/erp/payments/confirm \
  -H "Content-Type: application/json" \
  -d '{
    "payment_reference": "PAY-001",
    "erp_payment_id": "PE-00001",
    "status": "completed"
  }'

# Test student balance
curl http://your-laravel-app.com/api/erp/students/11000001/balance
```

---

## Troubleshooting

### Issue: API Authentication Fails

**Solution**:
- Verify API Key and Secret in `.env`
- Check ERPNext API user permissions
- Ensure API user has access to required doctypes

### Issue: Invoices Not Syncing

**Solution**:
- Check ERPNext logs: `frappe.log_error()`
- Verify webhook is triggered
- Check Laravel logs: `storage/logs/laravel.log`
- Test API endpoint manually

### Issue: Student Not Found in Laravel

**Solution**:
- Ensure Student ID in ERPNext matches Laravel student_id
- Check customer custom field `student_id` is set
- Verify student exists in Laravel database

### Issue: Payment Status Not Updating

**Solution**:
- Verify payment reference matches
- Check payment entry is submitted in ERPNext
- Review Laravel payment model and relationships

---

## Security Considerations

1. **Use HTTPS**: Always use HTTPS for API communication
2. **API Authentication**: Use Bearer tokens or API keys
3. **Rate Limiting**: Implement rate limiting on API endpoints
4. **Input Validation**: Validate all incoming data
5. **Error Handling**: Don't expose sensitive information in errors

---

## Next Steps

1. Set up automated invoice generation based on student enrollment
2. Create payment gateway integration
3. Set up automated email notifications
4. Create reports and dashboards
5. Implement reconciliation processes

---

## Support

For issues or questions:
- Check Laravel logs: `storage/logs/laravel.log`
- Check ERPNext logs: ERPNext → Settings → Error Log
- Review API responses in browser network tab
- Test endpoints using Postman or curl

---

## Additional Resources

- [ERPNext API Documentation](https://frappeframework.com/docs/user/en/api)
- [Laravel HTTP Client](https://laravel.com/docs/http-client)
- [ERPNext Customization Guide](https://frappeframework.com/docs/user/en/guides/customization)

