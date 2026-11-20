<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentDownloadController;
use App\Http\Controllers\EnrollmentDocumentController;
use App\Http\Controllers\Portal\DocumentRequestController as PortalDocumentRequestController;
use App\Http\Controllers\Portal\EnrollmentController;
use App\Http\Controllers\Portal\MyRecordsController;
use App\Http\Controllers\Staff\DocumentRequestController as StaffDocumentRequestController;
use App\Http\Controllers\Staff\EnrollmentApplicationController;
use App\Http\Controllers\Staff\LinkRequestController;
use App\Http\Controllers\Staff\OcrController;
use App\Http\Controllers\Staff\StudentController;
use App\Http\Controllers\Staff\StudentEnrollmentController;
use App\Http\Controllers\VerifyDocumentController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

// Opened by the QR code on a document. Public, but it shows no personal data.
Route::get('verify/{token}', VerifyDocumentController::class)
    ->middleware('throttle:30,1')
    ->name('verify');

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

    // Document requests made by students / parents / alumni.
    Route::prefix('my-requests')->name('portal.requests.')->group(function () {
        Route::get('/', [PortalDocumentRequestController::class, 'index'])->name('index');
        Route::get('create', [PortalDocumentRequestController::class, 'create'])->name('create');
        Route::post('/', [PortalDocumentRequestController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('store');
        Route::get('{documentRequest}', [PortalDocumentRequestController::class, 'show'])->name('show');
    });

    // PDF downloads: policy check, then a 2-minute signed link, both logged.
    Route::get('documents/request/{documentRequest}', [DocumentDownloadController::class, 'request'])
        ->name('documents.request');
    Route::get('documents/file/{document}', [DocumentDownloadController::class, 'file'])
        ->middleware('signed')
        ->name('documents.file');

    // Online enrollment (parent or learner).
    Route::prefix('my-enrollments')->name('portal.enrollments.')->group(function () {
        Route::get('/', [EnrollmentController::class, 'index'])->name('index');
        Route::get('create', [EnrollmentController::class, 'create'])->name('create');
        Route::post('/', [EnrollmentController::class, 'store'])->middleware('throttle:5,1')->name('store');
        Route::get('{application}', [EnrollmentController::class, 'show'])->name('show');
    });

    // Uploaded enrollment documents: policy check, then a 2-minute signed link.
    Route::get('enrollment-documents/request/{document}', [EnrollmentDocumentController::class, 'request'])
        ->name('enrollment-documents.request');
    Route::get('enrollment-documents/file/{document}', [EnrollmentDocumentController::class, 'file'])
        ->middleware('signed')
        ->name('enrollment-documents.file');

    // Registrar: online enrollment forms.
    Route::middleware(['permission:enrollments.view', 'two-factor'])
        ->prefix('staff/enrollments')->name('staff.enrollments.')->group(function () {
            Route::get('/', [EnrollmentApplicationController::class, 'index'])->name('index');
            Route::get('{application}', [EnrollmentApplicationController::class, 'show'])->name('show');
            Route::post('{application}/review', [EnrollmentApplicationController::class, 'review'])->name('review');
            Route::post('{application}/approve', [EnrollmentApplicationController::class, 'approve'])->name('approve');
            Route::post('{application}/reject', [EnrollmentApplicationController::class, 'reject'])->name('reject');
        });

    // Digitize old records: upload a scan, check the text, create the record.
    Route::middleware(['permission:ocr.use', 'two-factor'])->group(function () {
        Route::prefix('staff/ocr')->name('staff.ocr.')->group(function () {
            Route::get('/', [OcrController::class, 'index'])->name('index');
            Route::get('create', [OcrController::class, 'create'])->name('create');
            Route::post('/', [OcrController::class, 'store'])->middleware('throttle:30,1')->name('store');
            Route::get('{draft}', [OcrController::class, 'show'])->name('show');
            Route::post('{draft}/verify', [OcrController::class, 'verify'])->name('verify');
            Route::post('{draft}/reject', [OcrController::class, 'reject'])->name('reject');
        });
        Route::get('scans/file/{scan}', [OcrController::class, 'scanFile'])->middleware('signed')->name('scans.file');
    });

    // Registrar: document requests.
    Route::middleware(['permission:requests.view', 'two-factor'])
        ->prefix('staff/requests')->name('staff.requests.')->group(function () {
            Route::get('/', [StaffDocumentRequestController::class, 'index'])->name('index');
            Route::get('{documentRequest}', [StaffDocumentRequestController::class, 'show'])->name('show');
            Route::post('{documentRequest}/review', [StaffDocumentRequestController::class, 'review'])->name('review');
            Route::post('{documentRequest}/approve', [StaffDocumentRequestController::class, 'approve'])->name('approve');
            Route::post('{documentRequest}/reject', [StaffDocumentRequestController::class, 'reject'])->name('reject');
            Route::post('{documentRequest}/release', [StaffDocumentRequestController::class, 'release'])->name('release');
            Route::post('{documentRequest}/regenerate', [StaffDocumentRequestController::class, 'regenerate'])->name('regenerate');
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
