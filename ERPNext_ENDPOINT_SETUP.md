# ERPNext Endpoint Setup - Quick Solution

## Problem
You're getting `404 DoesNotExistError` because `/api/students` endpoint doesn't exist in ERPNext.

## Solution Options

### Option 1: Use ERPNext Standard REST API (Easiest - No Custom Code)

Update your `.env` to use ERPNext's built-in Customer API:

```env
ERP_BASE_URL=https://erpduc.delexesuniversity.edu.gh/api/resource
ERP_API_KEY=your_api_key
ERP_API_SECRET=your_api_secret
ERP_AUTH_TYPE=token
```

Then update the Laravel code to use `/Customer` endpoint instead of `/students`.

**I'll update the code for you to support this automatically.**

### Option 2: Create Custom Endpoint in ERPNext (More Control)

If you want a custom endpoint, follow these steps:

1. **SSH into your ERPNext server**

2. **Navigate to your ERPNext app directory**:
   ```bash
   cd /path/to/frappe-bench/apps
   ```

3. **Create a custom app** (if you don't have one):
   ```bash
   bench new-app sip_integration
   cd sip_integration
   ```

4. **Create the API file**:
   ```bash
   mkdir -p sip_integration/api
   ```

5. **Create `sip_integration/api/student_api.py`**:
   ```python
   import frappe
   import json
   
   @frappe.whitelist(allow_guest=False)
   def create_student(data):
       """Create or update student customer in ERPNext from Laravel SIP"""
       try:
           # Parse JSON data
           if isinstance(data, str):
               data = json.loads(data)
           
           student_id = data.get('student_id')
           if not student_id:
               return {'success': False, 'error': 'Student ID is required'}
           
           # Get biodata
           biodata = data.get('biodata', {})
           student_name = biodata.get('name', f"Student {student_id}")
           
           # Customer name format: STU-{student_id}
           customer_name = f"STU-{student_id}"
           
           # Check if customer exists
           if frappe.db.exists("Customer", customer_name):
               customer = frappe.get_doc("Customer", customer_name)
               customer.customer_name = student_name
           else:
               customer = frappe.new_doc("Customer")
               customer.customer_name = student_name
               customer.customer_group = "Student"
               customer.territory = "Ghana"
           
           # Update custom fields if they exist
           try:
               if hasattr(customer, 'student_id'):
                   customer.student_id = student_id
               if hasattr(customer, 'sip_synced'):
                   customer.sip_synced = 1
           except:
               pass  # Custom fields may not exist
           
           # Save customer
           customer.save(ignore_permissions=True)
           frappe.db.commit()
           
           return {
               'success': True,
               'customer_name': customer.name,
               'message': 'Student customer created/updated successfully'
           }
           
       except Exception as e:
           frappe.log_error(f"Error creating student: {str(e)}", "Student API Error")
           return {'success': False, 'error': str(e)}
   ```

6. **Update `sip_integration/__init__.py`**:
   ```python
   from .api.student_api import create_student
   ```

7. **Install the app**:
   ```bash
   bench --site your-site-name install-app sip_integration
   ```

8. **Restart ERPNext**:
   ```bash
   bench restart
   ```

9. **Update Laravel `.env`**:
   ```env
   ERP_BASE_URL=https://erpduc.delexesuniversity.edu.gh/api/method/sip_integration.api.student_api.create_student
   ERP_API_KEY=your_api_key
   ERP_API_SECRET=your_api_secret
   ERP_AUTH_TYPE=token
   ```

## Recommended: Use Option 1 (Standard REST API)

I'll update the code to automatically use ERPNext's standard Customer API, which requires no custom code in ERPNext.

