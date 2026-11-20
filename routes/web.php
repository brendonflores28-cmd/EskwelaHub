<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Portal\MyRecordsController;
use App\Http\Controllers\Staff\LinkRequestController;
use App\Http\Controllers\Staff\StudentController;
use App\Http\Controllers\Staff\StudentEnrollmentController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // Student / parent / alumni portal: only records linked to the account.
    Route::prefix('my-records')->name('portal.')->group(function () {
        Route::get('/', [MyRecordsController::class, 'index'])->name('records');
        Route::post('link', [MyRecordsController::class, 'requestLink'])
            ->middleware('throttle:5,1')
            ->name('link');
        Route::get('{student}', [MyRecordsController::class, 'show'])->name('records.show');
    });

    // Registrar / staff portal. Staff and admin must have 2FA turned on.
    Route::middleware(['permission:students.view', 'two-factor'])
        ->prefix('staff')->name('staff.')->group(function () {
            Route::resource('students', StudentController::class);
            Route::post('students/{student}/enrollments', [StudentEnrollmentController::class, 'store'])
                ->name('students.enrollments.store');
            Route::delete('students/{student}/enrollments/{enrollment}', [StudentEnrollmentController::class, 'destroy'])
                ->name('students.enrollments.destroy');
            Route::put('students/{student}/graduation', [StudentEnrollmentController::class, 'graduation'])
                ->name('students.graduation');

            Route::get('links', [LinkRequestController::class, 'index'])->name('links.index');
            Route::post('links/{link}/approve', [LinkRequestController::class, 'approve'])->name('links.approve');
            Route::post('links/{link}/reject', [LinkRequestController::class, 'reject'])->name('links.reject');
        });

    // Principal / admin only.
    Route::middleware(['permission:audit.view', 'two-factor'])
        ->prefix('admin')->name('admin.')->group(function () {
            Route::get('audit-log', [AuditLogController::class, 'index'])->name('audit-log');
        });
});

require __DIR__.'/settings.php';
