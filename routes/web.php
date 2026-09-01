<?php

use App\Http\Controllers\Admin\SubmissionTemplateController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BudgetBucketController;
use App\Http\Controllers\BudgetImportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EarlyWarningController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\Master\DepartmentController;
use App\Http\Controllers\Master\FiscalYearController;
use App\Http\Controllers\Master\FundingSourceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\SubmissionImportController;
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
Route::get('/budgets-import', [BudgetImportController::class, 'index'])->name('budgets.import.index');
Route::post('/budgets-import', [BudgetImportController::class, 'upload'])->name('budgets.import.upload');
Route::get('/budgets-import/template', [BudgetImportController::class, 'downloadTemplate'])->name('budgets.import.template');
Route::get('/budgets-import/{importHistory}', [BudgetImportController::class, 'show'])->name('budgets.import.show');
Route::post('/budgets-import/{importHistory}/commit', [BudgetImportController::class, 'commit'])->name('budgets.import.commit');

Route::resource('budgets', BudgetBucketController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
Route::post('budgets/{budgetBucket}/revise', [BudgetBucketController::class, 'revise'])->name('budgets.revise');

// Submissions
Route::resource('submissions', SubmissionController::class)->only(['index', 'create', 'store', 'show']);
Route::get('submissions/{submission}/print', [SubmissionController::class, 'printDocument'])->name('submissions.print');
Route::get('submissions/documents/{document}/download', [SubmissionController::class, 'downloadDocument'])->name('submissions.documents.download');

// Bulk Import Submissions
Route::get('/submissions-import', [SubmissionImportController::class, 'index'])->name('submissions.import.index');
Route::post('/submissions-import', [SubmissionImportController::class, 'upload'])->name('submissions.import.upload');
Route::get('/submissions-import/template', [SubmissionImportController::class, 'downloadTemplate'])->name('submissions.import.template');
Route::get('/submissions-import/{batch}', [SubmissionImportController::class, 'show'])->name('submissions.import.show');
Route::post('/submissions-import/{batch}/commit', [SubmissionImportController::class, 'commit'])->name('submissions.import.commit');

// Approvals & Electronic Sign-off
Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
Route::post('/approvals/{submission}/decide', [ApprovalController::class, 'decide'])->name('approvals.decide');

// Early Warning System
Route::get('/warnings', [EarlyWarningController::class, 'index'])->name('warnings.index');
Route::post('/warnings/{earlyWarning}/acknowledge', [EarlyWarningController::class, 'acknowledge'])->name('warnings.acknowledge');
Route::post('/warnings/{earlyWarning}/resolve', [EarlyWarningController::class, 'resolve'])->name('warnings.resolve');
Route::post('/warnings/reevaluate', [EarlyWarningController::class, 'reevaluate'])->name('warnings.reevaluate');

// Admin Submission Templates & Format
Route::get('/admin/submission-templates', [SubmissionTemplateController::class, 'index'])->name('admin.submission-templates.index');
Route::post('/admin/submission-templates', [SubmissionTemplateController::class, 'store'])->name('admin.submission-templates.store');

// Reports
Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
Route::get('/reports/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.export-pdf');
Route::get('/reports/export-csv', [ReportController::class, 'exportCsv'])->name('reports.export-csv');

// Audit Log
Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
