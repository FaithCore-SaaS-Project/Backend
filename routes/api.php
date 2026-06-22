<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController as WebAuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\CertificateController;
use App\Http\Controllers\Api\LetterController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\FamilyController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\FinanceController;
use App\Http\Controllers\Api\ReportController;

use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\WebhookController;

// Web SaaS Authentication APIs
Route::post('/login', [WebAuthController::class, 'login']);

// Mobile Authentication APIs
Route::post('/mobile/login', [WebAuthController::class, 'login']);

// Webhook Endpoints (Unprotected)
Route::post('/webhooks/stripe', [WebhookController::class, 'stripe']);
Route::post('/webhooks/payhere', [WebhookController::class, 'payhere']);

// Protected Mobile APIs
Route::middleware(['auth:sanctum', 'tenant'])->prefix('mobile')->group(function () {
    Route::post('/logout', [WebAuthController::class, 'logout']);
    
    Route::get('/user', function (Request $request) {
        return $request->user()->load('church');
    });

    Route::apiResource('members', \App\Http\Controllers\Api\Mobile\MemberController::class);
});

// Protected Web SaaS APIs (Basic Tenant Access)
Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::post('/logout', [WebAuthController::class, 'logout']);
    Route::get('/me', [WebAuthController::class, 'me']);

    // Subscriptions and Checkout (Must be accessible even if subscription is expired)
    Route::apiResource('subscriptions', SubscriptionController::class);
    Route::post('/subscription/upgrade', [SubscriptionController::class, 'upgrade']);
    Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel']);
    
    Route::post('/checkout/stripe', [CheckoutController::class, 'stripeSession']);
    Route::post('/checkout/payhere', [CheckoutController::class, 'payhereSession']);
});

// Protected Web SaaS APIs (Strict Subscription Access)
Route::middleware(['auth:sanctum', 'tenant', 'subscription'])->group(function () {
    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    
    // Core Modules
    Route::apiResource('members', MemberController::class);
    Route::apiResource('families', FamilyController::class);
    Route::apiResource('departments', DepartmentController::class);
    Route::apiResource('events', EventController::class);
    Route::post('/events/register', [EventController::class, 'register']);
    
    // Finance Routes
    Route::get('/finance/records', [FinanceController::class, 'recordsIndex']);
    Route::delete('/finance/records/{id}', [FinanceController::class, 'recordsDestroy']);

    Route::post('/income', [FinanceController::class, 'incomeStore']);
    Route::put('/income/{id}', [FinanceController::class, 'incomeUpdate']);

    Route::post('/expenses', [FinanceController::class, 'expenseStore']);
    Route::put('/expenses/{id}', [FinanceController::class, 'expenseUpdate']);

    Route::get('/finance-categories', [FinanceController::class, 'categoriesIndex']);
    Route::post('/finance-categories', [FinanceController::class, 'categoriesStore']);
    Route::put('/finance-categories/{id}', [FinanceController::class, 'categoriesUpdate']);
    Route::delete('/finance-categories/{id}', [FinanceController::class, 'categoriesDestroy']);

    Route::get('/bank-accounts', [FinanceController::class, 'bankAccountsIndex']);
    Route::post('/bank-accounts', [FinanceController::class, 'bankAccountsStore']);
    Route::put('/bank-accounts/{id}', [FinanceController::class, 'bankAccountsUpdate']);
    Route::delete('/bank-accounts/{id}', [FinanceController::class, 'bankAccountsDestroy']);

    Route::get('/budgets', [FinanceController::class, 'budgetIndex']);
    Route::post('/budgets', [FinanceController::class, 'budgetStore']);
    Route::put('/budgets/{id}', [FinanceController::class, 'budgetUpdate']);
    Route::delete('/budgets/{id}', [FinanceController::class, 'budgetDestroy']);

    // Documents & Certificates
    Route::apiResource('documents', DocumentController::class);
    Route::post('/documents/upload', [DocumentController::class, 'upload']);
    Route::apiResource('certificates', CertificateController::class);
    Route::get('/certificates/{id}/pdf', [CertificateController::class, 'generatePdf']);
    Route::get('/certificates/{id}/verify', [CertificateController::class, 'verify']);
    Route::apiResource('letters', LetterController::class);

    // Users, Roles & Permissions
    Route::apiResource('users', UserController::class);
    Route::apiResource('roles', RoleController::class);
    Route::apiResource('permissions', PermissionController::class);

    // Settings & Notifications
    Route::apiResource('settings', SettingsController::class);
    Route::apiResource('notifications', NotificationController::class);
    Route::post('/notifications/send', [NotificationController::class, 'send']);

    // Reports
    Route::get('/reports/saved', [ReportController::class, 'getSavedReports']);
    Route::post('/reports/saved', [ReportController::class, 'storeSavedReport']);
    Route::delete('/reports/saved/{id}', [ReportController::class, 'deleteSavedReport']);
    Route::get('/reports/financial', [ReportController::class, 'financial']);
    Route::get('/reports/members', [ReportController::class, 'members']);
    Route::get('/reports/attendance', [ReportController::class, 'attendance']);
});
