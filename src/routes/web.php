<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\StampCorrectionRequestController;

Route::get('/admin/login', function () {
    return view('admin.auth.login');
})->name('admin.login');
Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');
Route::post('/email/verification-notification', function (\Illuminate\Http\Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('status', 'verification-link-sent');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');
Route::get('/email/verify/{id}/{hash}', function (\Illuminate\Foundation\Auth\EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect('/attendance');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index'])
    ->name('attendance.index');
    Route::post('/attendance/work-start', [AttendanceController::class, 'workStart'])
    ->name('attendance.workStart');
    Route::post('/attendance/work-end', [AttendanceController::class, 'workEnd'])
    ->name('attendance.workEnd');
    Route::post('/attendance/rest-start', [AttendanceController::class, 'restStart'])
    ->name('attendance.restStart');
    Route::post('/attendance/rest-end', [AttendanceController::class, 'restEnd'])
    ->name('attendance.restEnd');
    Route::get('/attendance/list', [AttendanceController::class, 'list'])
    ->name('attendance.list');
    Route::get('/attendance/{id?}', [AttendanceController::class, 'show'])
    ->name('attendance.show');
    Route::put('/attendance/update/{id}', [AttendanceController::class, 'update'])
    ->name('attendance.update');
    Route::post('/attendance/store', [AttendanceController::class, 'store'])
    ->name('attendance.store');
    Route::get('/stamp_correction_request/list', [StampCorrectionRequestController::class, 'index'])->name('stamp_correction.index');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('auth')->group(function () {
        Route::get('/attendance/list', [AdminAttendanceController::class, 'index'])->name('attendance.list');
        Route::get('/attendance/{id}', [AdminAttendanceController::class, 'show'])
        ->name('attendance.show')
        ->where('id', '[0-9]+');
        Route::patch('/attendance/update/{id}', [AdminAttendanceController::class, 'update'])->name('attendance.update');
        Route::post('/attendance/store', [AdminAttendanceController::class, 'store'])->name('attendance.store');
    
        // スタッフ関連
        Route::get('/staff/list', [StaffController::class, 'index'])->name('staff.list');
        Route::get('/attendance/staff/{id}', [AdminAttendanceController::class, 'staffAttendance'])->name('staff.attendance');
        Route::get('/staff/export/{id}', [AdminAttendanceController::class, 'export'])->name('staff.export');
    
        // 申請関連（承認・更新処理）
        Route::get('/stamp_correction_request/list', [AdminAttendanceController::class, 'requestList'])->name('request.list');
    
        // 承認画面・更新処理用
        Route::get('/stamp_correction_request/approve/{id}', [AdminAttendanceController::class, 'approveRequest'])->name('attendance.approve.show');
        Route::patch('/stamp_correction_request/approve/{id}', [AdminAttendanceController::class, 'updateRequest'])->name('request.update');
        Route::post('/stamp_correction_request/approve/{id}', [AdminAttendanceController::class, 'updateRequest'])->name('attendance.approve');
    });
});