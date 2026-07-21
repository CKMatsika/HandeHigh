# Invoice Features - Quick Start Guide

## What Was Fixed

The following issues have been resolved:

1. **Route Ordering Fixed** - Bulk creation and auto-generation routes now work properly
2. **View Route Names Fixed** - All views now use correct `admin.invoices.*` route names
3. **Scheduled Command Created** - New command for automatic invoice generation
4. **Task Scheduler Configured** - Monthly automatic generation scheduled

## Features Available

### ✅ 1. Bulk Invoice Creation
**Location:** Navigate to `/admin/invoices` → Click "Bulk Create"

- Manually create invoices for multiple selected students
- Add custom fee items
- Specify academic year and term
- All invoices properly tagged for accounting reports

### ✅ 2. Auto-Generate Invoices
**Location:** Navigate to `/admin/invoices` → Click "Auto Generate"

- Automatically generate invoices for ALL active students
- Uses pre-configured fee structures per grade
- Options to include/exclude boarding, transport, optional fees
- Duplicate prevention (skips if invoice already exists)

### ✅ 3. Scheduled Auto-Generation
**Command:** `php artisan invoices:generate-scheduled`

- Runs automatically monthly on the 1st at 6:00 AM
- Can be run manually anytime
- Supports dry-run mode for testing
- Full logging and error tracking

## Quick Test

### Test the Command Manually

```powershell
# Test without creating invoices (dry run)
php artisan invoices:generate-scheduled --dry-run

# Generate for specific academic year and term
php artisan invoices:generate-scheduled --academic_year=2025-2026 --term="Term 1"

# Generate for specific school
php artisan invoices:generate-scheduled --school_id=1
```

### Test the Web Interface

1. **Login** to the admin panel
2. **Navigate** to Invoices page (`/admin/invoices`)
3. **Click** "Bulk Create" or "Auto Generate" buttons
4. **Follow** the on-screen instructions

## Setup Requirements

Before using these features, ensure:

### 1. Database Setup
- Schools table has active schools with `status = 'active'`
- Students have active enrollments
- Fee structures are configured for each grade level

### 2. School Settings (Optional)
Update `schools.settings` JSON field for each school:

```json
{
  "auto_generate_invoices": true,
  "default_term": "Term 1",
  "invoice_due_days": 30
}
```

### 3. Task Scheduler (For Auto-Generation)

**Windows:**
1. Open Task Scheduler
2. Create new task: "Laravel Scheduler"
3. Trigger: Daily at midnight
4. Action: 
   ```
   Program: php
   Arguments: artisan schedule:run
   Start in: C:\Users\User\Desktop\School System
   ```

**Linux/Mac:**
Add to crontab:
```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

## File Changes Made

### New Files
- `app/Console/Commands/GenerateScheduledInvoices.php` - Scheduled invoice generation command
- `INVOICE_FEATURES.md` - Comprehensive documentation
- `INVOICE_QUICK_START.md` - This quick start guide

### Modified Files
- `routes/web.php` - Fixed route ordering for bulk/auto-generate
- `routes/console.php` - Added scheduled task
- `resources/views/admin/invoices/index.blade.php` - Fixed route names
- `resources/views/admin/invoices/bulk-create.blade.php` - Fixed route names
- `resources/views/admin/invoices/auto-generate.blade.php` - Fixed route names

### Existing Files (Already Working)
- `app/Http/Controllers/Admin/InvoiceController.php` - Contains all logic
- `app/Models/Invoice.php` - Invoice model with term tracking
- `app/Models/InvoiceItem.php` - Invoice items
- `app/Models/LedgerEntry.php` - Accounting entries

## How Invoices Are Tagged by Term

Every invoice includes:
- `academic_year` field (e.g., "2025-2026")
- `term` field (e.g., "Term 1", "Term 2", "Term 3")
- `issued_at` date
- `due_date` date

This allows for:
- Filtering invoices by term
- Term-based financial reports
- Proper accounting per academic period
- Historical tracking

## Next Steps

1. **Test Bulk Creation:**
   - Create a few students
   - Enroll them in classes
   - Try bulk invoice creation

2. **Setup Fee Structures:**
   - Define fees for each grade level
   - Set academic year and term
   - Mark mandatory vs. optional fees

3. **Test Auto-Generation:**
   - Use the "Auto Generate" button
   - Review generated invoices
   - Check term assignments

4. **Configure Scheduler:**
   - Set up task scheduler
   - Test with manual command
   - Monitor logs for errors

## Troubleshooting

### "No schools found to process"
- Add schools to database with `status = 'active'`
- Check database connection

### "No active enrollments found"
- Enroll students in classes
- Set enrollment status to 'active'

### "No fee structure found"
- Create fee structures in Admin → Fees
- Set correct grade, academic year, and term
- Mark as not optional for mandatory fees

### Routes not working
- Clear route cache: `php artisan route:clear`
- Regenerate routes: `php artisan route:cache`

### Views showing errors
- Clear view cache: `php artisan view:clear`
- Check route names in views

## Support

For more detailed information, see `INVOICE_FEATURES.md`.

For technical implementation details, review the controller:
`app/Http/Controllers/Admin/InvoiceController.php`
