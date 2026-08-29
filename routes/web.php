<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BudgetBucketController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EarlyWarningController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\Master\DepartmentController;
use App\Http\Controllers\Master\FiscalYearController;
use App\Http\Controllers\Master\FundingSourceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Public Landing Page
Route::get('/', [LandingController::class, 'index'])->name('landing');

// Auth routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
Route::post('/login/{user}', [AuthController::class, 'loginAs'])->name('login.as');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Dashboard & App Routes
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// User Management (Admin Module)
Route::resource('users', UserController::class);

// Master Data Management (Admin Module)
Route::prefix('master')->name('master.')->group(function () {
    Route::resource('departments', DepartmentController::class)->except(['create', 'show', 'edit']);
    Route::post('departments/{department}/toggle-active', [DepartmentController::class, 'toggleActive'])->name('departments.toggle-active');

    Route::resource('funding-sources', FundingSourceController::class)->except(['create', 'show', 'edit']);

    Route::resource('fiscal-years', FiscalYearController::class)->except(['create', 'show', 'edit']);
    Route::post('fiscal-years/{fiscalYear}/set-active', [FiscalYearController::class, 'setActive'])->name('fiscal-years.set-active');
});

// Budget Management
Route::resource('budgets', BudgetBucketController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
Route::post('budgets/{budgetBucket}/revise', [BudgetBucketController::class, 'revise'])->name('budgets.revise');

// Submissions
Route::resource('submissions', SubmissionController::class)->only(['index', 'create', 'store', 'show']);
Route::post('submissions/{submission}/status', [SubmissionController::class, 'updateStatus'])->name('submissions.status');

// Early Warning System
Route::get('/warnings', [EarlyWarningController::class, 'index'])->name('warnings.index');
Route::post('/warnings/{earlyWarning}/acknowledge', [EarlyWarningController::class, 'acknowledge'])->name('warnings.acknowledge');

// Reports
Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

// Audit Log
Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
