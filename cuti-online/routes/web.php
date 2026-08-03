<?php

use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuthorizedOfficialController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentTemplateController;
use App\Http\Controllers\EmployeeCareerController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeImportController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\OrganizationProfileController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('/pegawai', [EmployeeController::class, 'index'])->name('employees.index');
    Route::get('/pegawai/tambah', [EmployeeController::class, 'create'])->name('employees.create');
    Route::get('/pegawai/impor/template', [EmployeeImportController::class, 'downloadTemplate'])->name('employees.import.template');
    Route::get('/pegawai/impor', [EmployeeImportController::class, 'create'])->name('employees.import.create');
    Route::post('/pegawai/impor/pratinjau', [EmployeeImportController::class, 'upload'])->name('employees.import.upload');
    Route::get('/pegawai/impor/{employeeImport}/pratinjau', [EmployeeImportController::class, 'preview'])->name('employees.import.preview');
    Route::post('/pegawai/impor/{employeeImport}', [EmployeeImportController::class, 'store'])->name('employees.import.store');
    Route::post('/pegawai/{employee}/pulihkan', [EmployeeController::class, 'restore'])->withTrashed()->name('employees.restore');
    Route::get('/pegawai/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
    Route::get('/pegawai/{employee}/riwayat/pangkat/tambah', [EmployeeCareerController::class, 'createRank'])->name('employees.rank-histories.create');
    Route::post('/pegawai/{employee}/riwayat/pangkat', [EmployeeCareerController::class, 'storeRank'])->name('employees.rank-histories.store');
    Route::get('/pegawai/{employee}/riwayat/jabatan/tambah', [EmployeeCareerController::class, 'createPosition'])->name('employees.position-histories.create');
    Route::post('/pegawai/{employee}/riwayat/jabatan', [EmployeeCareerController::class, 'storePosition'])->name('employees.position-histories.store');
    Route::post('/pegawai', [EmployeeController::class, 'store'])->name('employees.store');
    Route::put('/pegawai/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
    Route::delete('/pegawai/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
    Route::get('/pegawai/{employee}', [EmployeeController::class, 'show'])->name('employees.show');

    Route::get('/formulir-cuti', [LeaveRequestController::class, 'index'])->name('leave-requests.index');
    Route::get('/formulir-cuti/buat', [LeaveRequestController::class, 'create'])->name('leave-requests.create');
    Route::post('/formulir-cuti', [LeaveRequestController::class, 'store'])->name('leave-requests.store');
    Route::get('/formulir-cuti/{leaveRequest}', [LeaveRequestController::class, 'show'])->name('leave-requests.show');
    Route::get('/dokumen/{generatedDocument}/unduh', [LeaveRequestController::class, 'download'])->name('documents.download');
    Route::get('/template-dokumen', [DocumentTemplateController::class, 'index'])->name('document-templates.index');
    Route::post('/template-dokumen', [DocumentTemplateController::class, 'store'])->name('document-templates.store');
    Route::post('/template-dokumen/{documentTemplate}/aktif', [DocumentTemplateController::class, 'activate'])->name('document-templates.activate');

    Route::get('/pengaturan', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/pengaturan/saldo-cuti', [SettingsController::class, 'storeBalance'])->name('settings.balances.store');
    Route::post('/pengaturan/atasan-langsung', [SettingsController::class, 'storeSupervisor'])->name('settings.supervisors.store');

    Route::get('/integrasi-api', [ApiTokenController::class, 'index'])->name('api-tokens.index');
    Route::post('/integrasi-api/token', [ApiTokenController::class, 'store'])->name('api-tokens.store');
    Route::delete('/integrasi-api/token/{personalAccessToken}', [ApiTokenController::class, 'destroy'])->name('api-tokens.destroy');

    Route::get('/pejabat-berwenang', [AuthorizedOfficialController::class, 'index'])->name('authorized-official.index');
    Route::post('/pejabat-berwenang', [AuthorizedOfficialController::class, 'store'])->name('authorized-official.store');

    Route::get('/profil-instansi', [OrganizationProfileController::class, 'index'])->name('organization-profile.index');
    Route::post('/profil-instansi', [OrganizationProfileController::class, 'store'])->name('organization-profile.store');

    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});
