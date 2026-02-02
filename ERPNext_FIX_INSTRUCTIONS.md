# Fix ERPNext Integration - Step by Step

## Problem Analysis

From your logs, I can see two issues:

1. **First Error**: `Failed to connect to localhost port 8000` - Your `.env` file has the wrong ERP_BASE_URL
2. **Second Error**: `AuthenticationError` - Authentication format or credentials are incorrect

## Solution

### Step 1: Update Your `.env` File

Add/update these lines in your `.env` file on the server:

```env
# ERPNext Integration
ERP_BASE_URL=https://your-erpnext-domain.com/api
ERP_API_KEY=your_api_key_here
ERP_API_SECRET=your_api_secret_here
ERP_AUTH_TYPE=token
```

**Important**: 
- Replace `your-erpnext-domain.com` with your actual ERPNext domain
- Get the API Key and Secret from ERPNext (see Step 2)
- Use `token` for ERPNext standard API authentication

### Step 2: Get API Credentials from ERPNext

1. **Login to ERPNext** as Administrator
2. Go to **Settings** → **Integrations** → **API Keys**
3. Click **New** to create a new API key
4. Fill in:
   - **User**: Select or create an API user
   - **Key Name**: `Laravel SIP Integration`
5. **Save** and **copy**:
   - **API Key**
   - **API Secret**

### Step 3: Create Custom API Endpoint in ERPNext

Your Laravel app is trying to POST to `/api/students`, but ERPNext doesn't have this endpoint by default. You need to create it.

**Option A: Create Custom Endpoint (Recommended)**

1. **SSH into your ERPNext server** or access the file system
2. **Navigate to your ERPNext app directory**:
   ```bash
   cd /path/to/frappe-bench/apps/your_app
   ```
   (If you don't have a custom app, create one: `bench new-app your_app_name`)

3. **Create the API file**:
   ```bash
   mkdir -p your_app/api
   touch your_app/api/student_api.py
   ```

4. **Add this code to `student_api.py`**:
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
           if hasattr(customer, 'student_id'):
               customer.student_id = student_id
           if hasattr(customer, 'sip_synced'):
               customer.sip_synced = 1
           
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

5. **Add to `__init__.py`**:
   ```python
   from .api.student_api import create_student
   ```

6. **Restart ERPNext**:
   ```bash
   bench restart
   ```

**Option B: Use ERPNext Standard REST API (Alternative)**

If you prefer to use ERPNext's standard REST API, you'll need to update the Laravel code to use `/api/resource/Customer` instead of `/api/students`. This requires more complex data mapping.

### Step 4: Update Laravel Configuration

After creating the endpoint, your `.env` should be:

```env
ERP_BASE_URL=https://your-erpnext-domain.com/api/method/your_app.api.student_api.create_student
ERP_API_KEY=your_api_key
ERP_API_SECRET=your_api_secret
ERP_AUTH_TYPE=token
```

**OR** if you created a simpler endpoint at `/api/students`:

```env
ERP_BASE_URL=https://your-erpnext-domain.com/api
ERP_API_KEY=your_api_key
ERP_API_SECRET=your_api_secret
ERP_AUTH_TYPE=token
```

### Step 5: Clear Laravel Config Cache

After updating `.env`, run on your server:

```bash
cd /path/to/your/laravel/app
php artisan config:clear
php artisan cache:clear
```

### Step 6: Test the Integration

1. **Test via Tinker**:
   ```bash
   php artisan tinker
   ```
   
   Then:
   ```php
   $erp = app(\App\Services\ERPIntegrationService::class);
   $result = $erp->createStudentRecord([
       'student_id' => '11000006',
       'biodata' => ['name' => 'Test Student'],
       'program_id' => 1,
       'academic_year' => '2024/2025'
   ]);
   dd($result);
   ```

2. **Or approve a new application** and check:
   - ERPNext → Customer list
   - Laravel logs for success messages

## Quick Fix (If You Can't Create Custom Endpoint Right Now)

If you can't create the custom endpoint immediately, you can temporarily disable ERP integration:

1. **Comment out the ERP call** in `SIPAutomationService.php`:
   ```php
   // Temporarily disabled - uncomment after ERPNext setup
   /*
   try {
       $this->erpService->createStudentRecord([...]);
   } catch (\Exception $e) {
       \Log::warning('ERP API call failed (non-critical)', [...]);
   }
   */
   ```

2. **Students will still be created in Laravel**, just not in ERPNext until you set up the endpoint.

## Verification Checklist

- [ ] `.env` file has correct `ERP_BASE_URL` (not localhost)
- [ ] `.env` file has `ERP_API_KEY` and `ERP_API_SECRET`
- [ ] `.env` file has `ERP_AUTH_TYPE=token`
- [ ] Custom API endpoint created in ERPNext
- [ ] API endpoint is accessible and returns success
- [ ] Laravel config cache cleared
- [ ] Tested with tinker or new application approval

## Common Issues

### Issue: Still connecting to localhost

**Solution**: 
- Check `.env` file on server (not local)
- Run `php artisan config:clear`
- Verify with: `php artisan tinker` → `config('services.erp.base_url')`

### Issue: Authentication Error

**Solution**:
- Verify API Key and Secret are correct
- Check `ERP_AUTH_TYPE=token` is set
- Verify API user has permissions in ERPNext
- Check ERPNext logs for detailed error

### Issue: 404 Not Found

**Solution**:
- Verify endpoint URL is correct
- Check endpoint is created and accessible
- Test endpoint directly: `curl -X POST https://your-erpnext.com/api/method/your_app.api.student_api.create_student`

## Need Help?

Check these files for more details:
- `ERPNext_INTEGRATION_GUIDE.md` - Full integration guide
- `ERPNext_QUICK_START.md` - Quick reference
- Laravel logs: `storage/logs/laravel.log`
- ERPNext logs: ERPNext → Settings → Error Log

