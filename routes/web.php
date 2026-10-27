<?php

use App\Http\Controllers\Admin\OrganisationController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\SearchController;
use App\Http\Controllers\Admin\LoanProductController;
use App\Http\Controllers\Applications\LoanApplicationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Borrower\BorrowerController;
use App\Http\Controllers\Finance\FinanceController;
use App\Http\Controllers\Kyc\KycOcrController;
use App\Http\Controllers\Kyc\KycVerificationController;
use App\Http\Controllers\Mpesa\MpesaStkController;
use App\Http\Controllers\Mpesa\MpesaOperationsController;
use App\Http\Controllers\Statement\StatementController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Supervisor\DashboardController as SupervisorDashboardController;
use App\Http\Controllers\Supervisor\GuarantorController as SupervisorGuarantorController;
use App\Http\Controllers\LoanOfficer\DashboardController as LoanOfficerDashboardController;
use App\Http\Controllers\LoanOfficer\GuarantorController as LoanOfficerGuarantorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    if (!auth()->check()) {
        return view('auth.login');
    }

    $user = auth()->user();

    if ($user->hasRole('supervisor') && !$user->hasRole(['admin', 'super-admin'])) {
        return redirect()->route('supervisor.dashboard');
    }

    if ($user->hasRole('loan-officer')) {
        return redirect()->route('loan-officer.dashboard');
    }

    return redirect()->route('dashboard');
});

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {

    Route::get('/login', [LoginController::class, 'showLoginForm'])
        ->name('login');

    Route::post('/login', [LoginController::class, 'login'])
        ->name('login.attempt');
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'role:supervisor'])->prefix('supervisor')->name('supervisor.')->group(function () {
    Route::get('/', [SupervisorDashboardController::class, 'index'])->name('dashboard');
    Route::get('/guarantors', [SupervisorGuarantorController::class, 'index'])->name('guarantors.index');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});

Route::middleware(['auth', 'role:loan-officer'])->prefix('loan-officer')->name('loan-officer.')->group(function () {
    Route::get('/', [LoanOfficerDashboardController::class, 'index'])->name('dashboard');
    Route::get('/guarantors', [LoanOfficerGuarantorController::class, 'index'])->name('guarantors.index');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});

/*
|--------------------------------------------------------------------------
| Organisation
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin,super-admin'])->prefix('admin/organisation')->name('organisation.')->group(function () {
    Route::get('/', [OrganisationController::class, 'index'])->name('index');
    Route::get('/edit', [OrganisationController::class, 'edit'])->name('edit');
    Route::put('/', [OrganisationController::class, 'update'])->name('update');
    Route::patch('/status', [OrganisationController::class, 'updateStatus'])->name('update.status');

    // Configuration sections
    Route::get('/general', [OrganisationController::class, 'general'])->name('general');
    Route::get('/lending', [OrganisationController::class, 'lending'])->name('lending');
    Route::get('/payments', [OrganisationController::class, 'payments'])->name('payments');
    Route::get('/notifications', [OrganisationController::class, 'notifications'])->name('notifications');
    Route::get('/branding', [OrganisationController::class, 'branding'])->name('branding');

    // Configuration updates
    Route::put('/general', [OrganisationController::class, 'updateGeneral'])->name('update.general');
    Route::put('/lending', [OrganisationController::class, 'updateLending'])->name('update.lending');
    Route::post('/lending/tiers', [OrganisationController::class, 'storeTier'])->name('lending.tiers.store');
    Route::put('/lending/tiers/{tier}', [OrganisationController::class, 'updateTier'])->name('lending.tiers.update');
    Route::delete('/lending/tiers/{tier}', [OrganisationController::class, 'destroyTier'])->name('lending.tiers.destroy');
    Route::put('/payments', [OrganisationController::class, 'updatePayments'])->name('update.payments');
    Route::put('/notifications', [OrganisationController::class, 'updateNotifications'])->name('update.notifications');
    Route::put('/branding', [OrganisationController::class, 'updateBranding'])->name('update.branding');
});

// Global search — JSON endpoint for topnav
Route::get('/search', SearchController::class)
    ->middleware(['auth', 'role:admin,super-admin,loan-officer'])
    ->name('search');

Route::get('/admin/payments', [PaymentController::class, 'index'])
    ->middleware(['auth', 'role:admin,super-admin'])
    ->name('payments.index');

Route::middleware(['auth', 'role:admin,super-admin'])->group(function () {
    Route::get('/admin/repayments', fn () => redirect()->route('payments.index', ['view' => 'incoming']))
        ->name('loans.repayments');
    Route::patch('/admin/loan-products/{loan_product}/toggle-status', [LoanProductController::class, 'toggleStatus'])
        ->name('loan-products.toggle-status');
    Route::resource('admin/loan-products', LoanProductController::class)
        ->except(['destroy'])
        ->names('loan-products');
    Route::delete('/admin/loan-products/{loan_product}', [LoanProductController::class, 'destroy'])
        ->name('loan-products.destroy');
    Route::get('/admin/payments/reversals', [PaymentController::class, 'reversals'])->name('payments.reversals');
    Route::get('/admin/payments/create', [PaymentController::class, 'create'])->name('payments.create');
    Route::post('/admin/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::get('/admin/payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');
    Route::get('/admin/payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
    Route::get('/admin/payments/{payment}/edit', [PaymentController::class, 'edit'])->name('payments.edit');
    Route::put('/admin/payments/{payment}', [PaymentController::class, 'update'])->name('payments.update');
    Route::delete('/admin/payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');
    Route::post('/admin/payments/{payment}/match', [PaymentController::class, 'match'])->name('payments.match');
    Route::post('/admin/payments/{payment}/unmatch', [PaymentController::class, 'unmatch'])->name('payments.unmatch');
    Route::post('/admin/payments/{payment}/allocate', [PaymentController::class, 'allocate'])->name('payments.allocate');
    Route::post('/admin/payments/{payment}/reverse', [PaymentController::class, 'reverse'])->name('payments.reverse');
});

/*
|--------------------------------------------------------------------------
| M-Pesa STK
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin,super-admin'])->prefix('admin/mpesa/stk')->name('mpesa.stk.')->group(function () {
    Route::get('/', [MpesaStkController::class, 'index'])->name('index');
    Route::get('/create', [MpesaStkController::class, 'create'])->name('create');
    Route::post('/', [MpesaStkController::class, 'store'])->name('store');
    Route::post('/sync', [MpesaStkController::class, 'sync'])->name('sync');
    Route::get('/{transaction}', [MpesaStkController::class, 'show'])->name('show');
    Route::get('/{transaction}/edit', [MpesaStkController::class, 'edit'])->name('edit');
    Route::put('/{transaction}', [MpesaStkController::class, 'update'])->name('update');
    Route::delete('/{transaction}', [MpesaStkController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth', 'role:admin,super-admin'])->prefix('admin/mpesa/operations')->name('mpesa.operations.')->group(function () {
    Route::get('/', [MpesaOperationsController::class, 'index'])->name('index');
    Route::post('/', [MpesaOperationsController::class, 'store'])->name('store');
    Route::post('/c2b-register', [MpesaOperationsController::class, 'storeC2bRegister'])->name('c2b.register');
});

/*
|--------------------------------------------------------------------------
| Finance — Operations Cockpit
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin,super-admin'])->prefix('admin/finance')->name('finance.')->group(function () {
    Route::get('/', [FinanceController::class, 'overview'])->name('overview');
    Route::get('/reports', [FinanceController::class, 'reports'])->name('reports');
    Route::get('/reconciliation', [FinanceController::class, 'reconciliation'])->name('reconciliation');
    Route::get('/transactions', [FinanceController::class, 'transactions'])->name('transactions');
    Route::get('/reversals', [FinanceController::class, 'reversals'])->name('reversals');
    Route::get('/disbursed', [FinanceController::class, 'disbursed'])->name('disbursed');
});

/*
|--------------------------------------------------------------------------
| KYC
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin,super-admin,loan-officer,supervisor'])->group(function () {
    Route::post('/kyc/ocr', [KycOcrController::class, 'extract'])->name('kyc.ocr');
    Route::get('/kyc/ocr/{jobId}', [KycOcrController::class, 'poll'])->name('kyc.ocr.poll');
    Route::post('/kyc/verify/{borrower}', [KycVerificationController::class, 'update'])->name('kyc.verify');
});

/*
|--------------------------------------------------------------------------
| Statement documents
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin,super-admin,loan-officer,supervisor'])->group(function () {
    Route::post('/statements/upload', [StatementController::class, 'upload'])->name('statements.upload');
    Route::post('/borrowers/{borrower}/statement/reupload', [StatementController::class, 'reupload'])->name('statements.reupload');
});

/*
|--------------------------------------------------------------------------
| Borrowers
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin,super-admin,loan-officer,supervisor'])->group(function () {
    Route::get('/borrowers', [BorrowerController::class, 'index'])->name('borrowers.index');
    Route::get('/borrowers/create', [BorrowerController::class, 'create'])->name('borrowers.create');
    Route::post('/borrowers', [BorrowerController::class, 'store'])->name('borrowers.store');
    Route::get('/borrowers/{borrower}', [BorrowerController::class, 'show'])->name('borrowers.show');
    Route::get('/borrowers/{borrower}/edit', [BorrowerController::class, 'edit'])->name('borrowers.edit');
    Route::put('/borrowers/{borrower}', [BorrowerController::class, 'update'])->name('borrowers.update');
    Route::delete('/borrowers/{borrower}', [BorrowerController::class, 'destroy'])->name('borrowers.destroy');

    Route::get('/applications', [LoanApplicationController::class, 'index'])->name('applications.index');
    Route::get('/applications/active-loans', [LoanApplicationController::class, 'activeLoans'])->name('loans.active');
    Route::get('/applications/create', [LoanApplicationController::class, 'create'])->name('applications.create');
    Route::post('/applications', [LoanApplicationController::class, 'store'])->name('applications.store');
    // AJAX eligibility check — must be before {application} wildcard
    Route::get('/applications/eligibility/{borrower}', [LoanApplicationController::class, 'eligibility'])->name('applications.eligibility');
    Route::post('/applications/guarantor-match', [LoanApplicationController::class, 'guarantorMatch'])->name('applications.guarantor-match');
    Route::get('/applications/{application}/security-files/{file}', [LoanApplicationController::class, 'securityFile'])->name('applications.security-file');
    Route::get('/applications/{application}', [LoanApplicationController::class, 'show'])->name('applications.show');
    Route::get('/applications/{application}/edit', [LoanApplicationController::class, 'edit'])->name('applications.edit');
    Route::put('/applications/{application}', [LoanApplicationController::class, 'update'])->name('applications.update');

    Route::post('/applications/{application}/first-approve', [LoanApplicationController::class, 'firstLevelApprove'])->name('applications.first-approve');
    Route::post('/applications/{application}/return', [LoanApplicationController::class, 'returnForCorrection'])->name('applications.return');
    Route::post('/applications/{application}/collateral/{index}/verify', [LoanApplicationController::class, 'verifyCollateral'])->name('applications.collateral.verify');
    Route::post('/applications/{application}/approve', [LoanApplicationController::class, 'approve'])->name('applications.approve');
    Route::post('/applications/{application}/reject', [LoanApplicationController::class, 'reject'])->name('applications.reject');
    Route::post('/applications/{application}/disburse', [LoanApplicationController::class, 'disburse'])->name('applications.disburse');
});

/*
|--------------------------------------------------------------------------
| System Settings
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\System\SystemController;

Route::middleware(['auth', 'role:admin,super-admin'])->prefix('admin/system')->name('system.')->group(function () {
    Route::get('/', [SystemController::class, 'index'])->name('index');
    Route::get('/create', [SystemController::class, 'create'])->name('create');
    Route::post('/', [SystemController::class, 'store'])->name('store');
    Route::get('/{setting}/edit', [SystemController::class, 'edit'])->name('edit');
    Route::put('/{setting}', [SystemController::class, 'update'])->name('update');
    Route::delete('/{setting}', [SystemController::class, 'destroy'])->name('destroy');
    Route::patch('/{setting}/toggle-active', [SystemController::class, 'toggleActive'])->name('toggle-active');
});

/*
|--------------------------------------------------------------------------
| Notification Templates
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\System\NotificationsController;

Route::middleware(['auth', 'role:admin,super-admin'])->prefix('admin/notifications')->name('notifications.')->group(function () {
    Route::get('/', [NotificationsController::class, 'index'])->name('index');
    Route::get('/create', [NotificationsController::class, 'create'])->name('create');
    Route::post('/', [NotificationsController::class, 'store'])->name('store');
    Route::get('/{template}/edit', [NotificationsController::class, 'edit'])->name('edit');
    Route::put('/{template}', [NotificationsController::class, 'update'])->name('update');
    Route::delete('/{template}', [NotificationsController::class, 'destroy'])->name('destroy');
    Route::patch('/{template}/toggle-active', [NotificationsController::class, 'toggleActive'])->name('toggle-active');
    Route::post('/{template}/test', [NotificationsController::class, 'test'])->name('test');
});

/*
|--------------------------------------------------------------------------
| Integrations
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\System\IntegrationsController;

Route::middleware(['auth', 'role:admin,super-admin'])->prefix('admin/integrations')->name('integrations.')->group(function () {
    Route::get('/', [IntegrationsController::class, 'index'])->name('index');
    Route::get('/create', [IntegrationsController::class, 'create'])->name('create');
    Route::post('/', [IntegrationsController::class, 'store'])->name('store');
    Route::get('/{integration}', [IntegrationsController::class, 'show'])->name('show');
    Route::get('/{integration}/edit', [IntegrationsController::class, 'edit'])->name('edit');
    Route::put('/{integration}', [IntegrationsController::class, 'update'])->name('update');
    Route::delete('/{integration}', [IntegrationsController::class, 'destroy'])->name('destroy');
    Route::patch('/{integration}/toggle-active', [IntegrationsController::class, 'toggleActive'])->name('toggle-active');
    Route::post('/{integration}/test', [IntegrationsController::class, 'test'])->name('test');
});

/*
|--------------------------------------------------------------------------
| Security Policies
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\System\SecurityController;

Route::middleware(['auth', 'role:admin,super-admin'])->prefix('admin/security')->name('security.')->group(function () {
    Route::get('/', [SecurityController::class, 'index'])->name('index');
    Route::put('/password', [SecurityController::class, 'updatePassword'])->name('update.password');
    Route::put('/session', [SecurityController::class, 'updateSession'])->name('update.session');
    Route::put('/login', [SecurityController::class, 'updateLogin'])->name('update.login');
    Route::put('/ip-allowlist', [SecurityController::class, 'updateIpAllowlist'])->name('update.ip-allowlist');
});
