<?php

use App\Http\Controllers\Admin\SubmissionTemplateController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BudgetBucketController;
use App\Http\Controllers\BudgetImportController;
use App\Http\Controllers\BudgetVersionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EarlyWarningController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\Master\BudgetStructureController;
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

// Master Data Management (Admin Module - Master Organisasi)
Route::prefix('master')->name('master.')->group(function () {
    Route::resource('departments', DepartmentController::class)->except(['create', 'show', 'edit']);
    Route::post('departments/{department}/toggle-active', [DepartmentController::class, 'toggleActive'])->name('departments.toggle-active');

    // Program Studi Actions
    Route::post('study-programs', [DepartmentController::class, 'storeStudyProgram'])->name('study-programs.store');
    Route::put('study-programs/{studyProgram}', [DepartmentController::class, 'updateStudyProgram'])->name('study-programs.update');
    Route::delete('study-programs/{studyProgram}', [DepartmentController::class, 'destroyStudyProgram'])->name('study-programs.destroy');
    Route::post('study-programs/{studyProgram}/toggle-active', [DepartmentController::class, 'toggleActiveStudyProgram'])->name('study-programs.toggle-active');

    // Funding Sources Actions
    Route::resource('funding-sources', FundingSourceController::class)->except(['create', 'show', 'edit']);
    Route::post('funding-sources/{fundingSource}/toggle-active', [FiscalYearController::class, 'toggleFundingSourceActive'])->name('funding-sources.toggle-active');

    // Fiscal Years & Budget Versions (Tahun & Versi Pagu)
    Route::resource('fiscal-years', FiscalYearController::class)->except(['create', 'show', 'edit']);
    Route::post('fiscal-years/{fiscalYear}/set-active', [FiscalYearController::class, 'setActive'])->name('fiscal-years.set-active');
    Route::post('budget-versions', [FiscalYearController::class, 'storeBudgetVersion'])->name('budget-versions.store');
    Route::put('budget-versions/{budgetVersion}', [FiscalYearController::class, 'updateBudgetVersion'])->name('budget-versions.update');
    Route::post('budget-versions/{budgetVersion}/set-active', [FiscalYearController::class, 'setActiveBudgetVersion'])->name('budget-versions.set-active');
    Route::delete('budget-versions/{budgetVersion}', [FiscalYearController::class, 'destroyBudgetVersion'])->name('budget-versions.destroy');

    // Master Struktur Anggaran (Program, Kegiatan, KRO, RO, Komponen, Subkomponen, Akun, Subakun)
    Route::get('budget-structure', [BudgetStructureController::class, 'index'])->name('budget-structure.index');
    Route::put('budget-structure/{type}/{id}', [BudgetStructureController::class, 'update'])->name('budget-structure.update');
    Route::post('budget-structure/{type}/{id}/toggle-status', [BudgetStructureController::class, 'toggleStatus'])->name('budget-structure.toggle-status');
});

// Budget Management & Quick Search API
Route::get('/api/budgets/search', [BudgetBucketController::class, 'search'])->name('api.budgets.search');
Route::get('/budgets-import', [BudgetImportController::class, 'index'])->name('budgets.import.index');
Route::post('/budgets-import', [BudgetImportController::class, 'upload'])->name('budgets.import.upload');
Route::get('/budgets-import/template', [BudgetImportController::class, 'downloadTemplate'])->name('budgets.import.template');
Route::get('/budgets-import/{importHistory}', [BudgetImportController::class, 'show'])->name('budgets.import.show');
Route::post('/budgets-import/{importHistory}/commit', [BudgetImportController::class, 'commit'])->name('budgets.import.commit');

Route::resource('budgets', BudgetBucketController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
Route::post('budgets/{budgetBucket}/revise', [BudgetBucketController::class, 'revise'])->name('budgets.revise');

// Budget Version & Revision Management (Page P16)
Route::get('/budget-versions', [BudgetVersionController::class, 'index'])->name('budget-versions.index');
Route::get('/budget-versions/compare', [BudgetVersionController::class, 'compare'])->name('budget-versions.compare');
Route::post('/budget-versions/{budgetVersion}/activate', [BudgetVersionController::class, 'activate'])->name('budget-versions.activate');
Route::post('/budget-versions/{budgetVersion}/archive', [BudgetVersionController::class, 'archive'])->name('budget-versions.archive');
Route::post('/budget-versions', [BudgetVersionController::class, 'store'])->name('budget-versions.store');

// Transactions / Submissions
Route::resource('submissions', SubmissionController::class)->only(['index', 'create', 'store', 'show']);
Route::get('submissions/{submission}/print', [SubmissionController::class, 'printDocument'])->name('submissions.print');
Route::get('submissions/{submission}/export-pdf', [SubmissionController::class, 'exportPdf'])->name('submissions.export-pdf');
Route::get('submissions/{submission}/export-docx', [SubmissionController::class, 'exportDocx'])->name('submissions.export-docx');
Route::get('submissions/documents/{document}/download', [SubmissionController::class, 'downloadDocument'])->name('submissions.documents.download');

// Bulk Import Transactions
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
Route::get('/warnings/{earlyWarning}', [EarlyWarningController::class, 'show'])->name('warnings.show');
Route::post('/warnings/{earlyWarning}/acknowledge', [EarlyWarningController::class, 'acknowledge'])->name('warnings.acknowledge');
Route::post('/warnings/{earlyWarning}/resolve', [EarlyWarningController::class, 'resolve'])->name('warnings.resolve');
Route::post('/warnings/reevaluate', [EarlyWarningController::class, 'reevaluate'])->name('warnings.reevaluate');

// Admin Submission Templates & Format
Route::get('/admin/submission-templates', [SubmissionTemplateController::class, 'index'])->name('admin.submission-templates.index');
Route::post('/admin/submission-templates', [SubmissionTemplateController::class, 'store'])->name('admin.submission-templates.store');

// Reports (Page P24 & P25)
Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
Route::get('/reports/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.export-pdf');
Route::get('/reports/export-xlsx', [ReportController::class, 'exportXlsx'])->name('reports.export-xlsx');
Route::get('/reports/export-csv', [ReportController::class, 'exportCsv'])->name('reports.export-csv');
Route::get('/reports/export-docx', [ReportController::class, 'exportDocx'])->name('reports.export-docx');

// Audit Log
Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
