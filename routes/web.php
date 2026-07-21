<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\SchoolController;
use App\Http\Controllers\Admin\EnrollmentController;
use App\Http\Controllers\Admin\FeeStructureController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\CurriculumController;
use App\Http\Controllers\Admin\ExamController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\CommunicationController;
use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\JournalBatchController;
use App\Http\Controllers\Admin\FinancialReportController;
use App\Http\Controllers\Admin\BankReconciliationController;
use App\Http\Controllers\Admin\InterbankTransferController;
use App\Http\Controllers\Admin\BudgetController;
use App\Http\Controllers\Admin\PaymentMethodController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\BillController;
use App\Http\Controllers\Admin\ReceiptController;
use App\Http\Controllers\Admin\DebtorCreditorReportController;
use App\Http\Controllers\Admin\SchoolClassController;
use App\Http\Controllers\Admin\StatementController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Portal\LibrarianController;
use App\Http\Controllers\Portal\ParentController;
use App\Http\Controllers\Portal\StudentController;
use App\Http\Controllers\Admin\TeacherController as AdminTeacherController;
use App\Http\Controllers\Admin\CurrencyController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // SDA Portal Routes (for committee members)
    Route::middleware(['auth'])->prefix('sda')->name('sda.')->group(function () {
        // Main SDA dashboard - redirects based on role
        Route::get('/dashboard', [App\Http\Controllers\Sda\SdaController::class, 'dashboard'])->name('dashboard');
        
        // Role-specific dashboards
        Route::get('/chairman/dashboard', [App\Http\Controllers\Sda\SdaController::class, 'chairmanDashboard'])->name('chairman.dashboard');
        Route::get('/secretary/dashboard', [App\Http\Controllers\Sda\SdaController::class, 'secretaryDashboard'])->name('secretary.dashboard');
        Route::get('/finance/dashboard', [App\Http\Controllers\Sda\SdaController::class, 'financeDashboard'])->name('finance.dashboard');
        Route::get('/procurement/dashboard', [App\Http\Controllers\Sda\SdaController::class, 'procurementDashboard'])->name('procurement.dashboard');
        Route::get('/member/dashboard', [App\Http\Controllers\Sda\SdaController::class, 'memberDashboard'])->name('member.dashboard');
        
        // SDA Meetings
        Route::resource('meetings', App\Http\Controllers\Sda\SdaMeetingController::class)->only(['index', 'show', 'create', 'store']);
        Route::post('meetings/{meeting}/attendance', [App\Http\Controllers\Sda\SdaMeetingController::class, 'recordAttendance'])->name('meetings.attendance');
        
        // SDA Minutes
        Route::resource('minutes', App\Http\Controllers\Sda\SdaMinuteController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update']);
        Route::post('minutes/{minute}/submit', [App\Http\Controllers\Sda\SdaMinuteController::class, 'submit'])->name('minutes.submit');
        Route::post('minutes/{minute}/approve', [App\Http\Controllers\Sda\SdaMinuteController::class, 'approve'])->name('minutes.approve');
        Route::post('minutes/{minute}/publish', [App\Http\Controllers\Sda\SdaMinuteController::class, 'publish'])->name('minutes.publish');
        
        // SDA Resolutions
        Route::resource('resolutions', App\Http\Controllers\Sda\SdaResolutionController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update']);
        Route::post('resolutions/{resolution}/second', [App\Http\Controllers\Sda\SdaResolutionController::class, 'second'])->name('resolutions.second');
        
        // SDA Reports
        Route::resource('reports', App\Http\Controllers\Sda\SdaReportController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update']);
        Route::post('reports/{report}/submit', [App\Http\Controllers\Sda\SdaReportController::class, 'submit'])->name('reports.submit');
        Route::get('reports/{report}/download', [App\Http\Controllers\Sda\SdaReportController::class, 'download'])->name('reports.download');
        
        // SDA Tasks
        Route::resource('tasks', App\Http\Controllers\Sda\SdaTaskController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update']);
        Route::post('tasks/{task}/start', [App\Http\Controllers\Sda\SdaTaskController::class, 'start'])->name('tasks.start');
        Route::post('tasks/{task}/complete', [App\Http\Controllers\Sda\SdaTaskController::class, 'complete'])->name('tasks.complete');
        Route::post('tasks/{task}/cancel', [App\Http\Controllers\Sda\SdaTaskController::class, 'cancel'])->name('tasks.cancel');
    });

    Route::middleware(['role:super-admin|school-admin'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::resource('schools', SchoolController::class)->except(['show']);
            Route::get('enrollments/{enrollment}/print', [EnrollmentController::class, 'print'])->name('enrollments.print');
            Route::get('enrollments/bulk-create', [EnrollmentController::class, 'bulkCreate'])->name('enrollments.bulk-create');
            Route::post('enrollments/bulk-store', [EnrollmentController::class, 'bulkStore'])->name('enrollments.bulk-store');
            Route::get('enrollments/template', [EnrollmentController::class, 'downloadTemplate'])->name('enrollments.template');
            Route::resource('enrollments', EnrollmentController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);

            Route::get('fees/print', [FeeStructureController::class, 'print'])->name('fees.print');
            Route::get('fees/{fee}', [FeeStructureController::class, 'show'])->name('fees.show');
            Route::resource('fees', FeeStructureController::class)->only(['index', 'store', 'update', 'destroy']);

            Route::get('invoices/bulk-create', [InvoiceController::class, 'bulkCreate'])->name('invoices.bulk.create');
            Route::post('invoices/bulk-store', [InvoiceController::class, 'bulkStore'])->name('invoices.bulk.store');
            Route::get('invoices/auto-generate', [InvoiceController::class, 'autoGenerate'])->name('invoices.auto-generate');
            Route::post('invoices/process-auto-generate', [InvoiceController::class, 'processAutoGenerate'])->name('invoices.process-auto-generate');
            Route::get('invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
            Route::post('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
            Route::resource('invoices', InvoiceController::class);
            Route::get('invoices/{invoice}/payments/create', [PaymentController::class, 'create'])->name('payments.create');
            Route::post('invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('payments.store');
            Route::get('students/{student}/statement', [StatementController::class, 'create'])->name('students.statement.create');
            Route::post('students/{student}/statement', [StatementController::class, 'show'])->name('students.statement.show');
            Route::get('students/{student}/statement/print', [StatementController::class, 'print'])->name('students.statement.print');
            Route::get('students/{student}/statement/download', [StatementController::class, 'download'])->name('students.statement.download');
            Route::post('students/{student}/statement/email', [StatementController::class, 'email'])->name('students.statement.email');

            Route::resource('classes', SchoolClassController::class);
            Route::resource('subjects', SubjectController::class);
            Route::resource('curricula', CurriculumController::class);
            Route::resource('teachers', AdminTeacherController::class);
            Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
            Route::post('attendance/student', [AttendanceController::class, 'storeStudent'])->name('attendance.student.store');
            Route::post('attendance/staff', [AttendanceController::class, 'storeStaff'])->name('attendance.staff.store');
            Route::put('attendance/{attendance}', [AttendanceController::class, 'update'])->name('attendance.update');

            // Role-specific Dashboards
            Route::get('dashboard/headmaster', [\App\Http\Controllers\Admin\DashboardController::class, 'headmaster'])->name('admin.dashboard.headmaster')->middleware('permission:dashboard.headmaster');
            Route::get('dashboard/deputy-headmaster', [\App\Http\Controllers\Admin\DashboardController::class, 'deputyHeadmaster'])->name('admin.dashboard.deputy-headmaster')->middleware('permission:dashboard.deputy-headmaster');
            Route::get('dashboard/accounts-clerk', [\App\Http\Controllers\Admin\DashboardController::class, 'accountsClerk'])->name('admin.dashboard.accounts-clerk')->middleware('permission:dashboard.accounts-clerk');
            Route::get('dashboard/bursar', [\App\Http\Controllers\Admin\DashboardController::class, 'bursar'])->name('admin.dashboard.bursar')->middleware('permission:dashboard.bursar');
            Route::get('dashboard/procurement-officer', [\App\Http\Controllers\Admin\DashboardController::class, 'procurementOfficer'])->name('admin.dashboard.procurement-officer')->middleware('permission:dashboard.procurement-officer');

            // Communication
            Route::get('communication', [CommunicationController::class, 'index'])->name('communication.index');
            Route::get('communication/chat/{conversation}', [CommunicationController::class, 'showConversation'])->name('communication.chat');
            Route::post('conversation/create', [CommunicationController::class, 'createConversation'])->name('conversation.create');
            Route::post('communication/message/{conversation}', [CommunicationController::class, 'sendMessage'])->name('communication.message.send');
            Route::get('communication/sms', [CommunicationController::class, 'showSMS'])->name('communication.sms');
            Route::post('communication/sms/send', [CommunicationController::class, 'sendSMS'])->name('communication.sms.send');
            Route::get('communication/email', [CommunicationController::class, 'showEmail'])->name('communication.email');
            Route::post('communication/email/send', [CommunicationController::class, 'sendEmail'])->name('communication.email.send');
            Route::get('communication/search-users', [CommunicationController::class, 'searchUsers'])->name('communication.search-users');

            // Accounting - Chart of Accounts
            Route::get('accounts', [AccountController::class, 'index'])->name('accounts.index');
            Route::get('accounts/create', [AccountController::class, 'create'])->name('accounts.create');
            Route::post('accounts', [AccountController::class, 'store'])->name('accounts.store');
            Route::get('accounts/{account}/edit', [AccountController::class, 'edit'])->name('accounts.edit');
            Route::put('accounts/{account}', [AccountController::class, 'update'])->name('accounts.update');
            Route::put('accounts/{account}/toggle', [AccountController::class, 'toggle'])->name('accounts.toggle');

            // Accounting - Journals
            Route::get('journals', [JournalBatchController::class, 'index'])->name('journals.index');
            Route::get('journals/create', [JournalBatchController::class, 'create'])->name('journals.create');
            Route::post('journals', [JournalBatchController::class, 'store'])->name('journals.store');
            Route::get('journals/{journalBatch}', [JournalBatchController::class, 'show'])->name('journals.show');

            // Accounting - Reports
            Route::get('reports/trial-balance', [FinancialReportController::class, 'trialBalance'])->name('reports.trial-balance');
            Route::get('reports/income-statement', [FinancialReportController::class, 'incomeStatement'])->name('reports.income-statement');
            Route::get('reports/balance-sheet', [FinancialReportController::class, 'balanceSheet'])->name('reports.balance-sheet');
            Route::get('reports/cash-flow', [FinancialReportController::class, 'cashFlowStatement'])->name('reports.cash-flow');
            Route::get('reports/general-ledger', [FinancialReportController::class, 'generalLedger'])->name('reports.general-ledger');
            Route::get('reports/aged-receivables', [FinancialReportController::class, 'agedReceivables'])->name('reports.aged-receivables');
            Route::get('reports/budget-vs-actual', [FinancialReportController::class, 'budgetVsActual'])->name('reports.budget-vs-actual');
            Route::get('reports/student-fee-collection', [FinancialReportController::class, 'studentFeeCollection'])->name('reports.student-fee-collection');
            Route::get('reports/expense-analysis', [FinancialReportController::class, 'expenseAnalysis'])->name('reports.expense-analysis');
            Route::get('reports/departmental-performance', [FinancialReportController::class, 'departmentalPerformance'])->name('reports.departmental-performance');

            // Bank Reconciliation & Transfers
            Route::resource('bank-reconciliations', BankReconciliationController::class)->only(['index','create','store','show']);
            Route::get('bank-reconciliations/transactions/{account}', [BankReconciliationController::class, 'transactions'])->name('bank-reconciliations.transactions');
            Route::post('bank-reconciliations/import-statement', [BankReconciliationController::class, 'importStatement'])->name('bank-reconciliations.import-statement');
            Route::post('bank-reconciliations/auto-match', [BankReconciliationController::class, 'autoMatch'])->name('bank-reconciliations.auto-match');
            Route::post('bank-reconciliations/add-bank-charge', [BankReconciliationController::class, 'addBankCharge'])->name('bank-reconciliations.add-bank-charge');
            Route::post('bank-reconciliations/manual-match', [BankReconciliationController::class, 'manualMatch'])->name('bank-reconciliations.manual-match');
            Route::post('bank-reconciliations/unmatch', [BankReconciliationController::class, 'unmatchTransaction'])->name('bank-reconciliations.unmatch');
            Route::post('bank-reconciliations/add-cashbook-transaction', [BankReconciliationController::class, 'addCashbookTransaction'])->name('bank-reconciliations.add-cashbook-transaction');
            Route::post('bank-reconciliations/add-bank-transaction', [BankReconciliationController::class, 'addBankTransaction'])->name('bank-reconciliations.add-bank-transaction');
            Route::resource('interbank-transfers', InterbankTransferController::class)->only(['index','create','store']);

            // Budgets
            Route::resource('budgets', BudgetController::class)->only(['index','create','store','show','edit','update']);
            
            // Budget Workflow
            Route::post('budgets/{budget}/submit', [BudgetController::class, 'submit'])->name('budgets.submit');
            Route::post('budgets/{budget}/bursar-review', [BudgetController::class, 'bursarReview'])->name('budgets.bursar-review');
            Route::post('budgets/{budget}/committee-review', [BudgetController::class, 'committeeReview'])->name('budgets.committee-review');
            Route::post('budgets/{budget}/activate', [BudgetController::class, 'activate'])->name('budgets.activate');
            Route::post('budgets/{budget}/close', [BudgetController::class, 'close'])->name('budgets.close');
            
            // Budget Lines
            Route::get('budgets/{budget}/lines/create', [BudgetController::class, 'createLine'])->name('budgets.lines.create');
            Route::post('budgets/{budget}/lines', [BudgetController::class, 'storeLine'])->name('budgets.lines.store');
            Route::get('budgets/{budget}/lines/{line}/edit', [BudgetController::class, 'editLine'])->name('budgets.lines.edit');
            Route::put('budgets/{budget}/lines/{line}', [BudgetController::class, 'updateLine'])->name('budgets.lines.update');
            Route::delete('budgets/{budget}/lines/{line}', [BudgetController::class, 'destroyLine'])->name('budgets.lines.destroy');
            
            // Bills & Vendors
            Route::resource('bills', BillController::class)->only(['index','create','store','show','edit','update']);
            Route::resource('vendors', VendorController::class)->only(['index','create','store','edit','update']);
            
            // Projects
            Route::resource('projects', ProjectController::class)->only(['index','create','store','show','edit','update']);
            
            // Customers
            Route::resource('customers', CustomerController::class);

            // Receipts (non-student)
            Route::resource('receipts', ReceiptController::class);
            Route::get('receipts/{receipt}/print', [ReceiptController::class, 'print'])->name('receipts.print');
            Route::get('receipts/{receipt}/duplicate', [ReceiptController::class, 'duplicate'])->name('receipts.duplicate');

            // Debtor/Creditor summary
            Route::get('reports/debtor-creditor', [DebtorCreditorReportController::class, 'index'])->name('reports.debtor-creditor');
            
            // Analytics & Reports
            Route::get('/analytics', [App\Http\Controllers\Admin\AnalyticsController::class, 'dashboard'])->name('analytics.dashboard');
            Route::get('/analytics/academic', [App\Http\Controllers\Admin\AnalyticsController::class, 'academicPerformance'])->name('analytics.academic');
            Route::get('/analytics/financial', [App\Http\Controllers\Admin\AnalyticsController::class, 'financialAnalytics'])->name('analytics.financial');
            
            // Currency Management
            Route::get('/currency', [CurrencyController::class, 'index'])->name('currency.index');
            Route::post('/currency', [CurrencyController::class, 'update'])->name('currency.update');
            Route::post('/currency/convert', [CurrencyController::class, 'convert'])->name('currency.convert');
            
            // Payment Methods
            Route::resource('payment-methods', PaymentMethodController::class);
            Route::post('payment-methods/{paymentMethod}/toggle', [PaymentMethodController::class, 'toggle'])->name('payment-methods.toggle');
            Route::get('api/payment-methods', [PaymentMethodController::class, 'getActivePaymentMethods'])->name('api.payment-methods');
            Route::post('api/payment-methods/calculate-fee', [PaymentMethodController::class, 'calculateFee'])->name('api.payment-methods.calculate-fee');
            
            // School Development Association (SDA) Routes
            Route::prefix('sda')->name('sda.')->group(function () {
                // SDA Dashboard
                Route::get('/', [\App\Http\Controllers\Admin\SdaController::class, 'dashboard'])->name('dashboard');
                
                // Committees
                Route::resource('committees', \App\Http\Controllers\Admin\SdaCommitteeController::class);
                
                // Committee Members
                Route::post('committees/{committee}/members', [\App\Http\Controllers\Admin\SdaCommitteeMemberController::class, 'store'])->name('committees.members.store');
                Route::put('committees/{committee}/members/{member}', [\App\Http\Controllers\Admin\SdaCommitteeMemberController::class, 'update'])->name('committees.members.update');
                Route::delete('committees/{committee}/members/{member}', [\App\Http\Controllers\Admin\SdaCommitteeMemberController::class, 'destroy'])->name('committees.members.destroy');
                
                // Roles
                Route::resource('roles', \App\Http\Controllers\Admin\SdaRoleController::class);
                
                // Meetings
                Route::resource('meetings', \App\Http\Controllers\Sda\SdaMeetingController::class);
                Route::post('meetings/{meeting}/start', [\App\Http\Controllers\Sda\SdaMeetingController::class, 'start'])->name('meetings.start');
                Route::post('meetings/{meeting}/complete', [\App\Http\Controllers\Sda\SdaMeetingController::class, 'complete'])->name('meetings.complete');
                Route::post('meetings/{meeting}/cancel', [\App\Http\Controllers\Sda\SdaMeetingController::class, 'cancel'])->name('meetings.cancel');
                Route::post('meetings/{meeting}/attendance', [\App\Http\Controllers\Sda\SdaMeetingController::class, 'recordAttendance'])->name('meetings.attendance');
                
                // Meeting Minutes
                Route::get('meetings/{meeting}/minutes/create', [\App\Http\Controllers\Admin\SdaMinuteController::class, 'create'])->name('minutes.create');
                Route::post('meetings/{meeting}/minutes', [\App\Http\Controllers\Admin\SdaMinuteController::class, 'store'])->name('minutes.store');
                Route::resource('minutes', \App\Http\Controllers\Admin\SdaMinuteController::class)->except(['create', 'store']);
                Route::post('minutes/{minute}/submit', [\App\Http\Controllers\Admin\SdaMinuteController::class, 'submit'])->name('minutes.submit');
                Route::post('minutes/{minute}/chairman-review', [\App\Http\Controllers\Admin\SdaMinuteController::class, 'chairmanReview'])->name('minutes.chairman-review');
                Route::post('minutes/{minute}/approve', [\App\Http\Controllers\Admin\SdaMinuteController::class, 'approve'])->name('minutes.approve');
                Route::post('minutes/{minute}/publish', [\App\Http\Controllers\Admin\SdaMinuteController::class, 'publish'])->name('minutes.publish');
                Route::get('minutes/{minute}/download', [\App\Http\Controllers\Admin\SdaMinuteController::class, 'download'])->name('minutes.download');
                
                // Resolutions
                Route::resource('resolutions', \App\Http\Controllers\Admin\SdaResolutionController::class);
                Route::post('resolutions/{resolution}/second', [\App\Http\Controllers\Admin\SdaResolutionController::class, 'second'])->name('resolutions.second');
                Route::post('resolutions/{resolution}/debate', [\App\Http\Controllers\Admin\SdaResolutionController::class, 'debate'])->name('resolutions.debate');
                Route::post('resolutions/{resolution}/vote', [\App\Http\Controllers\Admin\SdaResolutionController::class, 'vote'])->name('resolutions.vote');
                Route::post('resolutions/{resolution}/implement', [\App\Http\Controllers\Admin\SdaResolutionController::class, 'implement'])->name('resolutions.implement');
                Route::post('resolutions/{resolution}/cancel', [\App\Http\Controllers\Admin\SdaResolutionController::class, 'cancel'])->name('resolutions.cancel');
                Route::post('resolutions/{resolution}/create-tasks', [\App\Http\Controllers\Admin\SdaResolutionController::class, 'createTasks'])->name('resolutions.create-tasks');
                
                // Reports
                Route::resource('reports', \App\Http\Controllers\Admin\SdaReportController::class);
                Route::post('reports/{report}/submit', [\App\Http\Controllers\Admin\SdaReportController::class, 'submit'])->name('reports.submit');
                Route::post('reports/{report}/review', [\App\Http\Controllers\Admin\SdaReportController::class, 'review'])->name('reports.review');
                Route::post('reports/{report}/approve', [\App\Http\Controllers\Admin\SdaReportController::class, 'approve'])->name('reports.approve');
                Route::post('reports/{report}/publish', [\App\Http\Controllers\Admin\SdaReportController::class, 'publish'])->name('reports.publish');
                Route::get('reports/{report}/download', [\App\Http\Controllers\Admin\SdaReportController::class, 'download'])->name('reports.download');
                
                // Tasks
                Route::resource('tasks', \App\Http\Controllers\Admin\SdaTaskController::class);
                Route::post('tasks/{task}/start', [\App\Http\Controllers\Admin\SdaTaskController::class, 'start'])->name('tasks.start');
                Route::post('tasks/{task}/complete', [\App\Http\Controllers\Admin\SdaTaskController::class, 'complete'])->name('tasks.complete');
                Route::post('tasks/{task}/cancel', [\App\Http\Controllers\Admin\SdaTaskController::class, 'cancel'])->name('tasks.cancel');
                Route::post('tasks/{task}/reassign', [\App\Http\Controllers\Admin\SdaTaskController::class, 'reassign'])->name('tasks.reassign');
                Route::get('tasks/overdue', [\App\Http\Controllers\Admin\SdaTaskController::class, 'overdue'])->name('tasks.overdue');
                Route::get('tasks/high-priority', [\App\Http\Controllers\Admin\SdaTaskController::class, 'highPriority'])->name('tasks.high-priority');
                Route::get('committees/{committee}/tasks', [\App\Http\Controllers\Admin\SdaTaskController::class, 'committeeTasks'])->name('tasks.committee');
                Route::get('users/{user}/tasks', [\App\Http\Controllers\Admin\SdaTaskController::class, 'userTasks'])->name('tasks.user');
                
                // API Endpoints
                Route::prefix('api')->name('api.')->group(function () {
                    Route::get('search-users', [\App\Http\Controllers\Admin\SdaController::class, 'searchUsers'])->name('search-users');
                    Route::get('committees/{committee}/members', [\App\Http\Controllers\Admin\SdaController::class, 'getCommitteeMembers'])->name('committees.members');
                });
            });
            
            // Bank Accounts & Cashbook
            Route::get('/bank-accounts', [App\Http\Controllers\Admin\BankAccountController::class, 'index'])->name('bank-accounts.index');
            Route::get('/bank-accounts/create', [App\Http\Controllers\Admin\BankAccountController::class, 'create'])->name('bank-accounts.create');
            Route::post('/bank-accounts', [App\Http\Controllers\Admin\BankAccountController::class, 'store'])->name('bank-accounts.store');
            Route::get('/bank-accounts/{bankAccount}', [App\Http\Controllers\Admin\BankAccountController::class, 'show'])->name('bank-accounts.show');
            Route::get('/bank-accounts/{bankAccount}/edit', [App\Http\Controllers\Admin\BankAccountController::class, 'edit'])->name('bank-accounts.edit');
            Route::put('/bank-accounts/{bankAccount}', [App\Http\Controllers\Admin\BankAccountController::class, 'update'])->name('bank-accounts.update');
            Route::delete('/bank-accounts/{bankAccount}', [App\Http\Controllers\Admin\BankAccountController::class, 'destroy'])->name('bank-accounts.destroy');
            
            // Cash Transfers
            Route::get('/cash-transfers', [App\Http\Controllers\Admin\CashTransferController::class, 'index'])->name('cash-transfers.index');
            Route::get('/cash-transfers/create', [App\Http\Controllers\Admin\CashTransferController::class, 'create'])->name('cash-transfers.create');
            Route::post('/cash-transfers', [App\Http\Controllers\Admin\CashTransferController::class, 'store'])->name('cash-transfers.store');
            
            // Cashbook
            Route::get('/cashbook', [App\Http\Controllers\Admin\CashbookController::class, 'index'])->name('cashbook.index');
            Route::get('/cashbook/create', [App\Http\Controllers\Admin\CashbookController::class, 'create'])->name('cashbook.create');
            Route::post('/cashbook', [App\Http\Controllers\Admin\CashbookController::class, 'store'])->name('cashbook.store');
            Route::get('/cashbook/{cashbook}', [App\Http\Controllers\Admin\CashbookController::class, 'show'])->name('cashbook.show');
            Route::get('/cashbook/{cashbook}/edit', [App\Http\Controllers\Admin\CashbookController::class, 'edit'])->name('cashbook.edit');
            Route::put('/cashbook/{cashbook}', [App\Http\Controllers\Admin\CashbookController::class, 'update'])->name('cashbook.update');
            Route::delete('/cashbook/{cashbook}', [App\Http\Controllers\Admin\CashbookController::class, 'destroy'])->name('cashbook.destroy');
            
            // Smart Rules
            Route::get('/smart-rules', [App\Http\Controllers\Admin\SmartRuleController::class, 'index'])->name('smart-rules.index');
            Route::put('/smart-rules/fees', [App\Http\Controllers\Admin\SmartRuleController::class, 'updateFeeRules'])->name('smart-rules.fees.update');
            Route::put('/smart-rules/attendance', [App\Http\Controllers\Admin\SmartRuleController::class, 'updateAttendanceRules'])->name('smart-rules.attendance.update');
            Route::put('/smart-rules/academic', [App\Http\Controllers\Admin\SmartRuleController::class, 'updateAcademicRules'])->name('smart-rules.academic.update');
            
            // School Setup
            Route::get('/school-setup', [App\Http\Controllers\Admin\SchoolSetupController::class, 'index'])->name('school-setup.index');
            Route::get('/school-setup/edit', [App\Http\Controllers\Admin\SchoolSetupController::class, 'edit'])->name('school-setup.edit');
            Route::put('/school-setup', [App\Http\Controllers\Admin\SchoolSetupController::class, 'update'])->name('school-setup.update');
            Route::get('/school-setup/settings', [App\Http\Controllers\Admin\SchoolSetupController::class, 'settings'])->name('school-setup.settings');
            Route::put('/school-setup/settings', [App\Http\Controllers\Admin\SchoolSetupController::class, 'updateSettings'])->name('school-setup.settings.update');
            Route::get('/school-setup/logs', [App\Http\Controllers\Admin\SchoolSetupController::class, 'logs'])->name('school-setup.logs');
            
            // User Management
            Route::get('/user-management', [App\Http\Controllers\Admin\UserManagementController::class, 'index'])->name('user-management.index');
            Route::get('/user-management/create', [App\Http\Controllers\Admin\UserManagementController::class, 'create'])->name('user-management.create');
            Route::post('/user-management', [App\Http\Controllers\Admin\UserManagementController::class, 'store'])->name('user-management.store');
            Route::get('/user-management/{user}', [App\Http\Controllers\Admin\UserManagementController::class, 'show'])->name('user-management.show');
            Route::get('/user-management/{user}/edit', [App\Http\Controllers\Admin\UserManagementController::class, 'edit'])->name('user-management.edit');
            Route::put('/user-management/{user}', [App\Http\Controllers\Admin\UserManagementController::class, 'update'])->name('user-management.update');
            Route::delete('/user-management/{user}', [App\Http\Controllers\Admin\UserManagementController::class, 'destroy'])->name('user-management.destroy');
            Route::put('/user-management/{user}/reset-password', [App\Http\Controllers\Admin\UserManagementController::class, 'resetPassword'])->name('user-management.reset-password');
            Route::put('/user-management/{user}/toggle-status', [App\Http\Controllers\Admin\UserManagementController::class, 'toggleStatus'])->name('user-management.toggle-status');
            
            // Employee Management (HR)
            Route::get('/employees', [App\Http\Controllers\Admin\EmployeeController::class, 'index'])->name('employees.index');
            Route::get('/employees/create', [App\Http\Controllers\Admin\EmployeeController::class, 'create'])->name('employees.create');
            Route::post('/employees', [App\Http\Controllers\Admin\EmployeeController::class, 'store'])->name('employees.store');
            Route::get('/employees/{employee}', [App\Http\Controllers\Admin\EmployeeController::class, 'show'])->name('employees.show');
            Route::get('/employees/{employee}/edit', [App\Http\Controllers\Admin\EmployeeController::class, 'edit'])->name('employees.edit');
            Route::put('/employees/{employee}', [App\Http\Controllers\Admin\EmployeeController::class, 'update'])->name('employees.update');
            Route::delete('/employees/{employee}', [App\Http\Controllers\Admin\EmployeeController::class, 'destroy'])->name('employees.destroy');
            Route::put('/employees/{employee}/terminate', [App\Http\Controllers\Admin\EmployeeController::class, 'terminate'])->name('employees.terminate');
            
            // Department Management
            Route::get('/departments', [App\Http\Controllers\Admin\DepartmentController::class, 'index'])->name('departments.index');
            Route::get('/departments/create', [App\Http\Controllers\Admin\DepartmentController::class, 'create'])->name('departments.create');
            Route::post('/departments', [App\Http\Controllers\Admin\DepartmentController::class, 'store'])->name('departments.store');
            Route::get('/departments/{department}', [App\Http\Controllers\Admin\DepartmentController::class, 'show'])->name('departments.show');
            Route::get('/departments/{department}/edit', [App\Http\Controllers\Admin\DepartmentController::class, 'edit'])->name('departments.edit');
            Route::put('/departments/{department}', [App\Http\Controllers\Admin\DepartmentController::class, 'update'])->name('departments.update');
            Route::delete('/departments/{department}', [App\Http\Controllers\Admin\DepartmentController::class, 'destroy'])->name('departments.destroy');
            
            // Timetable Management
            Route::get('/timetables', [App\Http\Controllers\Admin\TimetableController::class, 'index'])->name('timetables.index');
            Route::get('/timetables/create', [App\Http\Controllers\Admin\TimetableController::class, 'create'])->name('timetables.create');
            Route::post('/timetables', [App\Http\Controllers\Admin\TimetableController::class, 'store'])->name('timetables.store');
            Route::get('/timetables/{timetable}', [App\Http\Controllers\Admin\TimetableController::class, 'show'])->name('timetables.show');
            Route::get('/timetables/{timetable}/edit', [App\Http\Controllers\Admin\TimetableController::class, 'edit'])->name('timetables.edit');
            Route::put('/timetables/{timetable}', [App\Http\Controllers\Admin\TimetableController::class, 'update'])->name('timetables.update');
            Route::delete('/timetables/{timetable}', [App\Http\Controllers\Admin\TimetableController::class, 'destroy'])->name('timetables.destroy');
            Route::post('/timetables/{timetable}/generate', [App\Http\Controllers\Admin\TimetableController::class, 'generate'])->name('timetables.generate');
            Route::get('/timetables/conflicts', [App\Http\Controllers\Admin\TimetableController::class, 'conflicts'])->name('timetables.conflicts');
            Route::get('/timetables/create', [App\Http\Controllers\Admin\TimetableController::class, 'create'])->name('timetables.create');
            Route::post('/timetables', [App\Http\Controllers\Admin\TimetableController::class, 'store'])->name('timetables.store');
            Route::get('/timetables/{timetable}', [App\Http\Controllers\Admin\TimetableController::class, 'show'])->name('timetables.show');
            Route::get('/timetables/{timetable}/edit', [App\Http\Controllers\Admin\TimetableController::class, 'edit'])->name('timetables.edit');
            Route::put('/timetables/{timetable}', [App\Http\Controllers\Admin\TimetableController::class, 'update'])->name('timetables.update');
            Route::post('/timetables/{timetable}/generate', [App\Http\Controllers\Admin\TimetableController::class, 'generate'])->name('timetables.generate');
            Route::post('/timetables/{timetable}/publish', [App\Http\Controllers\Admin\TimetableController::class, 'publish'])->name('timetables.publish');
            Route::get('/timetables/{timetable}/conflicts', [App\Http\Controllers\Admin\TimetableController::class, 'conflicts'])->name('timetables.conflicts');
            Route::post('/timetables/{timetable}/resolve-conflicts', [App\Http\Controllers\Admin\TimetableController::class, 'resolveConflicts'])->name('timetables.resolve-conflicts');
        });

        Route::middleware(['role:teacher|super-admin'])
            ->prefix('teacher')
            ->name('teacher.')
            ->group(function () {
                Route::get('/dashboard', [\App\Http\Controllers\Portal\TeacherController::class, 'dashboard'])->name('dashboard');
                Route::get('/profile', [\App\Http\Controllers\Portal\TeacherController::class, 'profile'])->name('profile');
            });

        Route::middleware(['role:student|super-admin'])
            ->prefix('student')
            ->name('student.')
            ->group(function () {
                Route::get('/dashboard', [StudentController::class, 'dashboard'])->name('dashboard');
                Route::get('/profile', [StudentController::class, 'profile'])->name('profile');
                Route::get('/fees', [StudentController::class, 'fees'])->name('fees');
                Route::get('/results', [StudentController::class, 'results'])->name('results');
            });

        Route::middleware(['role:parent|super-admin'])
            ->prefix('parent')
            ->name('parent.')
            ->group(function () {
                Route::get('/dashboard', [ParentController::class, 'dashboard'])->name('dashboard');
                Route::get('/profile', [ParentController::class, 'profile'])->name('profile');
                Route::get('/students/{student}/fees', [ParentController::class, 'studentFees'])->name('students.fees');
                Route::get('/students/{student}/results', [ParentController::class, 'studentResults'])->name('students.results');
                Route::get('/students/{student}/statement', [ParentController::class, 'studentStatement'])->name('students.statement');
            });

        Route::middleware(['role:librarian|super-admin'])
            ->prefix('librarian')
            ->name('librarian.')
            ->group(function () {
                Route::get('/dashboard', [LibrarianController::class, 'dashboard'])->name('dashboard');
                Route::get('/profile', [LibrarianController::class, 'profile'])->name('profile');
                
                // Book Management
                Route::get('/books', [LibrarianController::class, 'booksIndex'])->name('books.index');
                Route::get('/books/create', [LibrarianController::class, 'booksCreate'])->name('books.create');
                Route::post('/books', [LibrarianController::class, 'booksStore'])->name('books.store');
                Route::get('/books/{book}', [LibrarianController::class, 'booksShow'])->name('books.show');
                Route::get('/books/{book}/edit', [LibrarianController::class, 'booksEdit'])->name('books.edit');
                Route::put('/books/{book}', [LibrarianController::class, 'booksUpdate'])->name('books.update');
                Route::delete('/books/{book}', [LibrarianController::class, 'booksDestroy'])->name('books.destroy');
                
                // Borrow Management
                Route::get('/borrow', [LibrarianController::class, 'borrowIndex'])->name('borrow.index');
                Route::post('/borrow', [LibrarianController::class, 'borrowStore'])->name('borrow.store');
                Route::post('/return/{borrowRecord}', [LibrarianController::class, 'returnBook'])->name('borrow.return');
                
                // Reports
                Route::get('/reports/popular', [LibrarianController::class, 'popularBooksReport'])->name('reports.popular');
                Route::get('/reports/overdue', [LibrarianController::class, 'overdueBooksReport'])->name('reports.overdue');
            });
});
