# ERPNext Invoice Sync Setup Guide

## Problem
When you create a Sales Invoice in ERPNext, it's not automatically syncing to Laravel SIP. This guide will help you set up automatic invoice syncing.

## Solution: Create ERPNext Hook to Auto-Sync Invoices

### Step 1: Create Python Script in ERPNext

1. **SSH into your ERPNext server** or access the file system

2. **Navigate to your ERPNext app directory**:
   ```bash
   cd /path/to/frappe-bench/apps
   ```

3. **Create or navigate to your custom app** (if you don't have one, create it):
   ```bash
   bench new-app sip_integration
   cd sip_integration
   ```

4. **Create the API directory**:
   ```bash
   mkdir -p sip_integration/api
   ```

5. **Create `sip_integration/api/invoice_api.py`**:
   ```python
   import frappe
   import json
   import requests
   from frappe.utils import nowdate, add_days
   
   def sync_invoice_to_sip(invoice_name):
       """Sync invoice from ERPNext to Laravel SIP"""
       try:
           invoice = frappe.get_doc("Sales Invoice", invoice_name)
           
           # Get student ID from customer
           customer = frappe.get_doc("Customer", invoice.customer)
           
           # Try to get student_id from custom field or customer name
           student_id = None
           
           # Method 1: Check custom field
           if hasattr(customer, 'student_id') and customer.student_id:
               student_id = customer.student_id
           
           # Method 2: Extract from customer name (e.g., "Student 11000006")
           if not student_id and customer.customer_name:
               if 'Student' in customer.customer_name:
                   # Extract student ID from name like "Student 11000006"
                   parts = customer.customer_name.split()
                   if len(parts) > 1:
                       student_id = parts[-1]  # Get last part as student ID
           
           # Method 3: Extract from customer ID (e.g., "STU-11000006")
           if not student_id and customer.name:
               if customer.name.startswith('STU-'):
                   student_id = customer.name.replace('STU-', '')
           
           if not student_id:
               frappe.log_error(f"Student ID not found for customer {customer.name}", "Invoice Sync Error")
               return {'success': False, 'error': 'Student ID not found in customer'}
           
           # Calculate paid amount and balance
           paid_amount = float(invoice.grand_total) - float(invoice.outstanding_amount)
           balance = float(invoice.outstanding_amount)
           
           # Determine status
           if invoice.outstanding_amount == 0:
               status = 'paid'
           elif paid_amount > 0:
               status = 'partial'
           else:
               status = 'pending'
           
           # Prepare invoice data
           invoice_data = {
               'erp_invoice_id': invoice.name,
               'student_id': student_id,
               'invoice_number': invoice.name,
               'invoice_type': invoice.get('invoice_type') or 'tuition',
               'total_amount': float(invoice.grand_total),
               'paid_amount': paid_amount,
               'balance': balance,
               'status': status,
               'due_date': invoice.due_date.strftime('%Y-%m-%d') if invoice.due_date else None,
               'issued_date': invoice.posting_date.strftime('%Y-%m-%d') if invoice.posting_date else None,
               'academic_year': invoice.get('academic_year') or '',
               'semester': invoice.get('semester') or '',
               'line_items': [
                   {
                       'item': item.item_name or item.item_code,
                       'description': item.description or '',
                       'quantity': float(item.qty),
                       'rate': float(item.rate),
                       'amount': float(item.amount)
                   }
                   for item in invoice.items
               ]
           }
           
           # Get Laravel API URL from site config or use default
           laravel_url = frappe.conf.get('laravel_api_url') or 'https://sip.delexesuniversity.edu.gh/api/erp/invoices/sync'
           
           # Send to Laravel API
           response = requests.post(
               laravel_url,
               json=invoice_data,
               headers={'Content-Type': 'application/json'},
               timeout=10
           )
           
           if response.status_code == 200:
               result = response.json()
               # Mark as synced if custom field exists
               try:
                   frappe.db.set_value("Sales Invoice", invoice_name, "sip_synced", 1)
               except:
                   pass  # Custom field may not exist
               
               frappe.log_error(f"Invoice {invoice_name} synced successfully to SIP", "Invoice Sync Success")
               return {'success': True, 'message': 'Invoice synced successfully', 'response': result}
           else:
               error_msg = f"Laravel API Error: {response.status_code} - {response.text}"
               frappe.log_error(error_msg, "Invoice Sync Error")
               return {'success': False, 'error': error_msg}
               
       except Exception as e:
           error_msg = f"Error syncing invoice: {str(e)}"
           frappe.log_error(error_msg, "Invoice Sync Error")
           return {'success': False, 'error': error_msg}
   
   @frappe.whitelist(allow_guest=False)
   def sync_invoice_manual(invoice_name):
       """Manual sync function - can be called from ERPNext UI"""
       return sync_invoice_to_sip(invoice_name)
   ```

6. **Update `sip_integration/__init__.py`**:
   ```python
   from .api.invoice_api import sync_invoice_to_sip, sync_invoice_manual
   ```

### Step 2: Create Hook to Auto-Sync on Invoice Submit

1. **Create or update `sip_integration/hooks.py`**:
   ```python
   from .api.invoice_api import sync_invoice_to_sip
   
   def on_submit_sales_invoice(doc, method):
       """Automatically sync invoice to Laravel SIP when submitted"""
       try:
           # Only sync if not already synced
           if not doc.get('sip_synced'):
               result = sync_invoice_to_sip(doc.name)
               if result.get('success'):
                   frappe.msgprint(f"Invoice synced to SIP successfully")
               else:
                   frappe.log_error(f"Failed to sync invoice: {result.get('error')}", "Invoice Sync Error")
       except Exception as e:
           frappe.log_error(f"Error in invoice sync hook: {str(e)}", "Invoice Sync Hook Error")
   ```

2. **Update `sip_integration/hooks.py`** to register the hook:
   ```python
   # Add this to hooks.py
   hooks = {
       "on_submit": [
           {
               "doctype": "Sales Invoice",
               "method": "sip_integration.hooks.on_submit_sales_invoice"
           }
       ]
   }
   ```

### Step 3: Configure Laravel API URL in ERPNext

1. **Login to ERPNext** as Administrator

2. **Go to Settings** → **System Settings** → **Site Config**

3. **Add or update** the Laravel API URL:
   ```json
   {
     "laravel_api_url": "https://sip.delexesuniversity.edu.gh/api/erp/invoices/sync"
   }
   ```

   **OR** add it via console:
   ```bash
   bench --site your-site-name set-config laravel_api_url "https://sip.delexesuniversity.edu.gh/api/erp/invoices/sync"
   ```

### Step 4: Install and Restart

1. **Install the app** (if not already installed):
   ```bash
   bench --site your-site-name install-app sip_integration
   ```

2. **Restart ERPNext**:
   ```bash
   bench restart
   ```

### Step 5: Test the Integration

1. **Create a Sales Invoice** in ERPNext for a student customer
2. **Submit the invoice**
3. **Check Laravel SIP** → Admin → ERP Dashboard → Invoices
4. **Verify** the invoice appears in Laravel

## Alternative: Manual Sync Button

If you prefer manual syncing, you can add a custom button to Sales Invoice:

1. **Go to Sales Invoice** doctype
2. **Customize Form**
3. **Add Custom Script**:
   ```javascript
   frappe.ui.form.on('Sales Invoice', {
       refresh: function(frm) {
           if (frm.doc.docstatus === 1) { // Only show if submitted
               frm.add_custom_button(__('Sync to SIP'), function() {
                   frappe.call({
                       method: 'sip_integration.api.invoice_api.sync_invoice_manual',
                       args: {
                           invoice_name: frm.doc.name
                       },
                       callback: function(r) {
                           if (r.message.success) {
                               frappe.show_alert({
                                   message: __('Invoice synced to SIP successfully'),
                                   indicator: 'green'
                               });
                           } else {
                               frappe.msgprint({
                                   title: __('Sync Failed'),
                                   message: r.message.error || 'Unknown error',
                                   indicator: 'red'
                               });
                           }
                       }
                   });
               });
           }
       }
   });
   ```

## Troubleshooting

### Issue: Invoice not syncing automatically

**Check:**
1. Verify hook is registered: Check ERPNext logs
2. Check if customer has student_id field populated
3. Verify Laravel API URL is correct
4. Check ERPNext Error Log: Settings → Error Log

### Issue: Student ID not found

**Solution:**
- Ensure customer has `student_id` custom field OR
- Customer name format: "Student 11000006" OR
- Customer ID format: "STU-11000006"

### Issue: Laravel API returns error

**Check:**
1. Laravel API endpoint is accessible: `https://sip.delexesuniversity.edu.gh/api/erp/invoices/sync`
2. Check Laravel logs: `storage/logs/laravel.log`
3. Verify student_id exists in Laravel database

## Testing the API Endpoint Directly

Test the Laravel endpoint manually:

```bash
curl -X POST https://sip.delexesuniversity.edu.gh/api/erp/invoices/sync \
  -H "Content-Type: application/json" \
  -d '{
    "erp_invoice_id": "SINV-00001",
    "student_id": "11000006",
    "invoice_number": "SINV-00001",
    "total_amount": 5000.00,
    "academic_year": "2024/2025",
    "semester": "First Semester"
  }'
```

## Next Steps

After setting up invoice syncing:
1. Set up payment syncing (similar process)
2. Configure automatic balance updates
3. Set up email notifications for invoice creation

