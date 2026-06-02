<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GuardianController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ReceptionController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\TherapistController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TherapyProgramController;
use App\Http\Controllers\MilestoneController;
use App\Http\Controllers\ProgramAttachmentController;
use App\Http\Controllers\HomeTaskController;
use App\Http\Controllers\SessionPackageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TrashController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TherapistDashboardController;




Route::get('/', function () {
    return view('welcome');
});

// لوحة التحكم الرئيسية لإظهار إحصائيات عامة ومواعيد اليوم القادمة
Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

// لوحة تحكم الأخصائي
Route::get('/my-dashboard', [TherapistDashboardController::class, 'index'])
    ->middleware(['auth', 'role:أخصائي تخاطب'])
    ->name('therapist.dashboard');
Route::middleware('auth')->group(function () {
    // إدارة الملف الشخصي
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    // تحديث الملف الشخصي
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // حذف الحساب
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    // إدارة الأوصياء والمرضى
    Route::resource('guardians', GuardianController::class);
    // إدارة المرضى
    Route::resource('patients', PatientController::class);
    // طباعة بطاقة المريض
    Route::get('patients/{patient}/print-card', [PatientController::class, 'printCard'])->name('patients.print-card');
    // استقبال المرضى وتسجيل الحضور والمواعيد
    Route::get('/reception', [ReceptionController::class, 'index'])->name('reception.index');
    // مسح رمز الاستجابة السريعة لتسجيل الحضور أو الوصول إلى معلومات المريض
    Route::post('/reception/scan', [ReceptionController::class, 'processScan'])->name('reception.scan');
    // تسجيل خروج المريض عند انتهاء الجلسة
    Route::post('/reception/{checkin}/checkout', [ReceptionController::class, 'checkout'])->name('reception.checkout');
    // إدارة المواعيد
    Route::post('/appointments/{appointment}/update-status', [AppointmentController::class, 'updateStatus'])->name('appointments.update-status');
    // تحويل الموعد إلى جلسة علاجية
    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    //  إنشاء موعد جديد
    Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    // تحديث حالة الموعد (مثل الحضور، الإلغاء، التأجيل)
    Route::post('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.updateStatus');
    // تحويل الموعد إلى جلسة علاجية (عند حضور المريض)
    Route::post('/appointments/{appointment}/convert', [AppointmentController::class, 'convertToSession'])->name('appointments.convert');
    // إدارة الفواتير والعروض
    Route::resource('invoices', InvoiceController::class);
    // إضافة دفعة إلى فاتورة
    Route::post('invoices/{invoice}/payments', [InvoiceController::class, 'addPayment'])->name('invoices.payments');
    // طباعة الفاتورة بصيغة PDF
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'printPdf'])->name('invoices.pdf');
    // إدارة العروض وتحويلها إلى فواتير
    Route::resource('quotations', QuotationController::class);
    // تحويل العرض إلى فاتورة
    Route::post('quotations/{quotation}/convert', [QuotationController::class, 'convertToInvoice'])->name('quotations.convert');
    // إدارة الرواتب والموظفين
    Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
    // حساب الرواتب تلقائيًا بناءً على الحضور والأداء
    Route::post('/payroll/calculate', [PayrollController::class, 'calculate'])->name('payroll.calculate');
    // إضافة سجل يدوي للراتب (للحالات الخاصة أو التصحيحات)
    Route::post('/payroll/add-record', [PayrollController::class, 'addRecord'])->name('payroll.addRecord');
    // إدارة المعالجين وتسجيل الحضور
    Route::resource('therapists', TherapistController::class);
    // تسجيل حضور المعالجين في بداية اليوم أو الجلسة
    Route::post('/therapists/attendance', [TherapistController::class, 'markAttendance'])->name('therapists.attendance');
    // إدارة المعدات والأجهزة الطبية
    Route::resource('equipment', EquipmentController::class);
    // إدارة الموردين والمخزون
    Route::resource('suppliers', SupplierController::class);

    // البرامج العلاجية (الملف السريري للطفل)

    // ⬇️ مسار تقرير الـ PDF (يجب أن يكون قبل الـ resource ليعمل بشكل صحيح)
    Route::get('/programs/{program}/progress-report', [TherapyProgramController::class, 'progressReport'])->name('programs.progress-report');

    Route::resource('programs', TherapyProgramController::class);

    // تتبع التطور (Milestones)
    Route::post('/programs/{program}/milestones', [MilestoneController::class, 'store'])->name('milestones.store');

    // المرفقات وتسجيلات قبل/بعد
    Route::post('/programs/{program}/attachments', [ProgramAttachmentController::class, 'store'])->name('attachments.store');

    // الواجبات المنزلية
    Route::post('/tasks', [HomeTaskController::class, 'store'])->name('tasks.store');
    // تسجيل ملاحظات ولي الأمر أو اكتمال الواجب
    Route::post('/tasks/{task}/feedback', [HomeTaskController::class, 'parentFeedback'])->name('tasks.feedback');
    // باقات الجلسات العلاجية
    Route::resource('packages', SessionPackageController::class);
    // سلة المحذوفات
    Route::get('/trash', [TrashController::class, 'index'])->name('trash.index');
    // استعادة عنصر من سلة المحذوفات
    Route::post('/trash/restore', [TrashController::class, 'restore'])->name('trash.restore');
    // إعدادات العيادة
    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');


});

require __DIR__.'/auth.php';
