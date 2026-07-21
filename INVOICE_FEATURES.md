# Invoice Features Documentation

## Overview
This document describes the bulk invoice creation and automatic invoice generation features implemented in the School System.

## Features

### 1. Bulk Invoice Creation
Manually create multiple invoices at once for selected students.

**Access:** Admin Dashboard → Invoices → "Bulk Create" button

**How it works:**
- Select multiple students from active enrollments
- Specify academic year and term
- Add custom fee items with descriptions, categories, and amounts
- System creates invoices for all selected students with the same fee structure
- Invoices are properly categorized by term for accounting reports
- Duplicate invoices for the same student/term are automatically skipped

**Use cases:**
- Creating invoices for a specific group of students
- Adding special fees or charges to multiple students
- Creating invoices with custom fee structures

### 2. Auto-Generate Invoices
Automatically generate invoices for all active students based on pre-configured fee structures.

**Access:** Admin Dashboard → Invoices → "Auto Generate" button

**How it works:**
- Select target academic year and term
- System fetches fee structures configured for each grade level
- Automatically creates invoices for all active enrollments
- Optional: Include/exclude boarding, transport, and optional fees
- Invoices are tagged with the correct term for accounting purposes
- Duplicate checking prevents re-creating existing invoices

**Configuration options:**
- Include boarding fees for boarding students
- Include transport fees for students with transport
- Include optional fees (activities, extracurriculars, etc.)

### 3. Scheduled Automatic Invoice Generation
Invoices can be automatically generated on a set schedule using Laravel's task scheduler.

**Command:** `php artisan invoices:generate-scheduled`

**Schedule:** Runs monthly on the 1st at 6:00 AM (configurable in `routes/console.php`)

**Command options:**
```bash
# Generate for all schools
php artisan invoices:generate-scheduled

# Generate for a specific school
php artisan invoices:generate-scheduled --school_id=1

# Generate for a specific academic year and term
php artisan invoices:generate-scheduled --academic_year=2025-2026 --term="Term 1"

# Test without creating invoices (dry run)
php artisan invoices:generate-scheduled --dry-run
```

**How it works:**
- Checks school settings for auto-generation configuration
- Determines current academic year/term automatically
- Generates invoices for all active enrollments
- Skips students who already have invoices for that term
- Creates proper ledger entries for accounting
- Logs all operations for audit purposes

### 4. Term-Based Accounting
All invoices are properly categorized by:
- Academic Year (e.g., "2025-2026")
- Term (e.g., "Term 1", "Term 2", "Term 3")

This ensures accurate financial reporting per term and academic year.

## Database Structure

### Invoices Table
Key fields for term tracking:
- `academic_year` - The academic year (e.g., "2025-2026")
- `term` - The term within the year (e.g., "Term 1")
- `issued_at` - Date the invoice was issued
- `due_date` - Payment due date
- `status` - Payment status (unpaid, partial, paid, cancelled)

### Ledger Entries
All invoice operations create proper double-entry accounting ledger entries:
- Debit: FEES_RECEIVABLE
- Credit: FEES_REVENUE

## Configuration

### School Settings
Add these settings to the `schools.settings` JSON field:

```json
{
  "auto_generate_invoices": true,
  "default_term": "Term 1",
  "invoice_due_days": 30
}
```

### Scheduler Setup
Ensure Laravel's scheduler is running via cron:

**For Linux/Mac:**
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

**For Windows (Task Scheduler):**
- Create a new task
- Trigger: Daily at midnight
- Action: Run `php artisan schedule:run` in your project directory

## Usage Workflow

### Manual Bulk Creation
1. Navigate to Admin → Invoices
2. Click "Bulk Create"
3. Select academic year and term
4. Choose students from the list
5. Add fee items (tuition, boarding, transport, etc.)
6. Click "Create Invoices"

### Manual Auto-Generation
1. Navigate to Admin → Invoices
2. Click "Auto Generate"
3. Specify target academic year and term
4. Select fee options (boarding, transport, optional)
5. Click "Generate Invoices"

### Scheduled Auto-Generation
1. Configure school settings in database
2. Set up fee structures for each grade level
3. Ensure Laravel scheduler is running
4. System automatically generates invoices on schedule

## Best Practices

1. **Set up fee structures first**: Before auto-generating, ensure fee structures are configured for all grade levels

2. **Test with dry-run**: Use the `--dry-run` flag when testing scheduled generation

3. **Monitor logs**: Check Laravel logs for any generation errors

4. **Term planning**: Generate invoices at the start of each term

5. **Duplicate prevention**: The system automatically prevents duplicate invoices for the same student/term

## Troubleshooting

### No invoices generated
- Check that fee structures exist for the target academic year/term/grade
- Verify students have active enrollments
- Check that auto-generation is enabled in school settings

### Wrong term assignment
- Verify the academic year and term parameters
- Check school settings for default term configuration

### Scheduler not running
- Verify cron job is configured correctly
- Check Laravel logs for scheduler execution
- Test manually with `php artisan schedule:run`

### Invoice numbering conflicts
- Invoice numbers are auto-generated sequentially per school
- Format: `INV-[YEAR]-[SEQUENCE]` (e.g., INV-20252026-000001)

## API Endpoints (Routes)

- **GET** `/admin/invoices` - List all invoices
- **GET** `/admin/invoices/bulk-create` - Bulk creation form
- **POST** `/admin/invoices/bulk-store` - Process bulk creation
- **GET** `/admin/invoices/auto-generate` - Auto-generation form
- **POST** `/admin/invoices/process-auto-generate` - Process auto-generation
- **GET** `/admin/invoices/{invoice}` - View invoice details
- **GET** `/admin/invoices/{invoice}/edit` - Edit invoice
- **GET** `/admin/invoices/{invoice}/print` - Print invoice

## Future Enhancements

Potential improvements:
1. Email notifications when invoices are generated
2. SMS notifications for parents/guardians
3. PDF generation and automatic delivery
4. Payment reminders for overdue invoices
5. Bulk discount application
6. Invoice templates per grade level
7. Multi-currency support
8. Payment plan automation
