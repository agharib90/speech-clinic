<?php

use App\Http\Controllers\AppointmentAvailabilityController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ArticulationAssessmentController;
use App\Http\Controllers\CaseHistoryController;
use App\Http\Controllers\ClinicalEvaluationQueueController;
use App\Http\Controllers\ClinicalHandoffQueueController;
use App\Http\Controllers\ClinicalProgressPointController;
use App\Http\Controllers\ClinicalTreatmentPlanController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DischargeSummaryController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\GuardianController;
use App\Http\Controllers\HomeTaskController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MilestoneController;
use App\Http\Controllers\PatientClinicalEvaluationAssignmentController;
use App\Http\Controllers\PatientClinicalEvaluationController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PatientServicePlanController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgramAttachmentController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\ReceptionController;
use App\Http\Controllers\ServiceCompletionController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SessionPackageController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SpecialtyController;
use App\Http\Controllers\StutteringAssessmentController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TherapistController;
use App\Http\Controllers\TherapistDashboardController;
use App\Http\Controllers\TherapistServiceController;
use App\Http\Controllers\TherapyProgramController;
use App\Http\Controllers\TrashController;
use App\Http\Controllers\Usercontroller;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'role_or_permission:أخصائي تخاطب|view reports'])
    ->name('dashboard');

Route::get('/my-dashboard', [TherapistDashboardController::class, 'index'])
    ->middleware(['auth', 'role:أخصائي تخاطب'])
    ->name('therapist.dashboard');

Route::get('/my-cases', [TherapistDashboardController::class, 'cases'])
    ->middleware(['auth', 'role:أخصائي تخاطب'])
    ->name('therapist.cases');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/clinical/evaluations', [ClinicalEvaluationQueueController::class, 'index'])
        ->middleware('permission:manage clinical evaluations')
        ->name('clinical.evaluations.index');
    Route::get('/clinical/handoffs', [ClinicalHandoffQueueController::class, 'index'])
        ->middleware('permission:manage clinical evaluation assignments')
        ->name('clinical.handoffs.index');

    Route::middleware('permission:create patients')->group(function () {
        Route::resource('guardians', GuardianController::class)->only(['create', 'store']);
        Route::get('patients/guardian-search', [PatientController::class, 'guardianSearch'])
            ->name('patients.guardian-search');
        Route::resource('patients', PatientController::class)->only(['create', 'store']);
    });

    Route::middleware('permission:view patients')->group(function () {
        Route::resource('guardians', GuardianController::class)->only(['index', 'show']);
        Route::get('patients/{patient}/workspace', [PatientController::class, 'workspace'])->name('patients.workspace');
        Route::resource('patients', PatientController::class)->only(['index', 'show']);
        Route::get('patients/{patient}/print-card', [PatientController::class, 'printCard'])->name('patients.print-card');
    });
    Route::middleware('permission:edit patients')->group(function () {
        Route::resource('guardians', GuardianController::class)->only(['edit', 'update']);
        Route::resource('patients', PatientController::class)->only(['edit', 'update']);
        Route::post('/patients/{patient}/case-history', [CaseHistoryController::class, 'store'])
            ->name('patients.case-history.store');
    });

    Route::middleware('permission:manage clinical evaluations')->group(function () {
        Route::post('/patients/{patient}/clinical-evaluation/draft', [PatientClinicalEvaluationController::class, 'saveDraft'])
            ->name('patients.clinical-evaluation.save-draft');
        Route::post('/patients/{patient}/clinical-evaluations/{evaluation}/complete', [PatientClinicalEvaluationController::class, 'complete'])
            ->name('patients.clinical-evaluation.complete');
        Route::post('/patients/{patient}/clinical-treatment-plan/draft', [ClinicalTreatmentPlanController::class, 'saveDraft'])
            ->name('patients.clinical-plan.save-draft');
        Route::post('/patients/{patient}/clinical-treatment-plan/approve', [ClinicalTreatmentPlanController::class, 'approveNew'])
            ->name('patients.clinical-plan.approve-new');
        Route::post('/patients/{patient}/clinical-treatment-plans/{patientServicePlan}/approve', [ClinicalTreatmentPlanController::class, 'approve'])
            ->name('patients.clinical-plan.approve');
    });
    Route::middleware('permission:manage clinical evaluation assignments')->group(function () {
        Route::post('/patients/{patient}/clinical-evaluation-assignment', [PatientClinicalEvaluationAssignmentController::class, 'store'])
            ->name('patients.clinical-evaluation-assignment.store');
    });
    Route::middleware('permission:delete patients')->group(function () {
        Route::resource('guardians', GuardianController::class)->only(['destroy']);
        Route::resource('patients', PatientController::class)->only(['destroy']);
    });

    Route::middleware('permission:manage checkins')->group(function () {
        Route::get('/reception', [ReceptionController::class, 'index'])->name('reception.index');
        Route::post('/reception/scan', [ReceptionController::class, 'processScan'])->name('reception.scan');
        Route::post('/reception/appointments/{appointment}/attendance', [ReceptionController::class, 'confirmAttendance'])
            ->name('reception.attendance.confirm');
        Route::post('/reception/{checkin}/checkout', [ReceptionController::class, 'checkout'])->name('reception.checkout');
    });

    Route::get('/appointments', [AppointmentController::class, 'index'])
        ->middleware('permission:view appointments')
        ->name('appointments.index');
    Route::post('/appointments', [AppointmentController::class, 'store'])
        ->middleware('permission:create appointments')
        ->name('appointments.store');
    Route::get('/appointments/availability', AppointmentAvailabilityController::class)
        ->middleware('permission:create appointments')
        ->name('appointments.availability');
    Route::post('/appointments/{appointment}/update-status', [AppointmentController::class, 'updateStatus'])
        ->middleware('permission:edit appointments')
        ->name('appointments.update-status');
    Route::post('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])
        ->middleware('permission:edit appointments')
        ->name('appointments.updateStatus');
    Route::post('/appointments/{appointment}/convert', [AppointmentController::class, 'convertToSession'])
        ->middleware('permission:edit appointments')
        ->name('appointments.convert');
    Route::post('/appointments/{appointment}/complete-service', [ServiceCompletionController::class, 'store'])
        ->middleware('permission:edit therapy')
        ->name('appointments.complete-service');

    Route::middleware('permission:manage invoices')->group(function () {
        Route::resource('invoices', InvoiceController::class)->only(['create', 'store']);
        Route::post('invoices/{invoice}/payments', [InvoiceController::class, 'addPayment'])->name('invoices.payments');
    });

    Route::middleware('permission:view finance')->group(function () {
        Route::resource('invoices', InvoiceController::class)->only(['index', 'show']);
        Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'printPdf'])->name('invoices.pdf');
        Route::resource('quotations', QuotationController::class)->only(['index']);
        Route::resource('packages', SessionPackageController::class)->only(['index']);
    });

    Route::middleware('permission:manage quotations')->group(function () {
        Route::resource('quotations', QuotationController::class)->only(['create', 'store']);
        Route::post('quotations/{quotation}/convert', [QuotationController::class, 'convertToInvoice'])->name('quotations.convert');
    });
    Route::middleware('permission:manage packages')->group(function () {
        Route::resource('packages', SessionPackageController::class)->only(['create', 'store']);
    });

    Route::middleware('permission:manage payroll')->group(function () {
        Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
        Route::post('/payroll/calculate', [PayrollController::class, 'calculate'])->name('payroll.calculate');
        Route::post('/payroll/add-record', [PayrollController::class, 'addRecord'])->name('payroll.addRecord');
    });
    Route::resource('therapists', TherapistController::class)
        ->only(['create', 'store', 'edit', 'update'])
        ->middleware('permission:manage therapists');
    Route::resource('therapists', TherapistController::class)
        ->only(['index', 'show'])
        ->middleware('permission:view hr');
    Route::post('/therapists/attendance', [TherapistController::class, 'markAttendance'])
        ->middleware('permission:manage therapists')
        ->name('therapists.attendance');
    Route::put('/therapists/{therapist}/work-schedule', [TherapistController::class, 'updateSchedule'])
        ->middleware('permission:manage therapists')
        ->name('therapists.work-schedule.update');
    Route::middleware('permission:manage therapist services')->group(function () {
        Route::get('/therapists/{therapist}/services', [TherapistServiceController::class, 'edit'])
            ->name('therapists.services.edit');
        Route::put('/therapists/{therapist}/services', [TherapistServiceController::class, 'update'])
            ->name('therapists.services.update');
    });
    Route::middleware('permission:manage specialties')->group(function () {
        Route::resource('specialties', SpecialtyController::class)->except(['show', 'destroy']);
        Route::patch('/specialties/{specialty}/toggle-active', [SpecialtyController::class, 'toggleActive'])
            ->name('specialties.toggle-active');
    });
    Route::middleware('permission:manage services')->group(function () {
        Route::resource('services', ServiceController::class)->except(['show', 'destroy']);
        Route::patch('/services/{service}/toggle-active', [ServiceController::class, 'toggleActive'])
            ->name('services.toggle-active');
    });
    Route::middleware('permission:manage patient service plans')->group(function () {
        Route::get('/patients/{patient}/service-plans', [PatientServicePlanController::class, 'index'])
            ->name('patients.service-plans.index');
        Route::get('/patients/{patient}/service-plans/create', [PatientServicePlanController::class, 'create'])
            ->name('patients.service-plans.create');
        Route::post('/patients/{patient}/service-plans', [PatientServicePlanController::class, 'store'])
            ->name('patients.service-plans.store');
        Route::get('/patient-service-plans/{patientServicePlan}', [PatientServicePlanController::class, 'show'])
            ->name('patient-service-plans.show');
        Route::get('/patient-service-plans/{patientServicePlan}/edit', [PatientServicePlanController::class, 'edit'])
            ->name('patient-service-plans.edit');
        Route::put('/patient-service-plans/{patientServicePlan}', [PatientServicePlanController::class, 'update'])
            ->name('patient-service-plans.update');
        Route::delete('/patient-service-plans/{patientServicePlan}', [PatientServicePlanController::class, 'destroy'])
            ->name('patient-service-plans.destroy');
        Route::post('/patient-service-plans/{patientServicePlan}/activate', [PatientServicePlanController::class, 'activate'])
            ->name('patient-service-plans.activate');
        Route::post('/patient-service-plans/{patientServicePlan}/allocate-payment', [PatientServicePlanController::class, 'allocatePayment'])
            ->name('patient-service-plans.allocate-payment');
    });

    Route::resource('equipment', EquipmentController::class)
        ->only(['index', 'store'])
        ->middleware('permission:manage inventory');
    Route::resource('suppliers', SupplierController::class)
        ->only(['index', 'store'])
        ->middleware('permission:manage suppliers');

    Route::middleware('permission:view therapy')->group(function () {
        Route::resource('programs', TherapyProgramController::class)->only(['index', 'show']);
        Route::get('/programs/{program}/progress-report', [TherapyProgramController::class, 'progressReport'])->name('programs.progress-report');
    });
    Route::post('/programs/{program}/milestones', [MilestoneController::class, 'store'])
        ->middleware('permission:manage milestones')
        ->name('milestones.store');
    Route::post('/programs/{program}/attachments', [ProgramAttachmentController::class, 'store'])
        ->middleware('permission:edit therapy')
        ->name('attachments.store');
    Route::middleware('permission:edit therapy')->group(function () {
        Route::post('/programs/{program}/articulation-assessments', [ArticulationAssessmentController::class, 'store'])
            ->name('programs.articulation-assessments.store');
        Route::post('/programs/{program}/stuttering-assessments', [StutteringAssessmentController::class, 'store'])
            ->name('programs.stuttering-assessments.store');
        Route::post('/programs/{program}/progress-points', [ClinicalProgressPointController::class, 'store'])
            ->name('programs.progress-points.store');
        Route::post('/programs/{program}/discharge-summary', [DischargeSummaryController::class, 'store'])
            ->name('programs.discharge-summary.store');
    });
    Route::post('/tasks', [HomeTaskController::class, 'store'])
        ->middleware('permission:manage home tasks')
        ->name('tasks.store');
    Route::post('/tasks/{task}/feedback', [HomeTaskController::class, 'parentFeedback'])
        ->middleware('permission:manage home tasks')
        ->name('tasks.feedback');

    Route::middleware('role_or_permission:مدير النظام|manage settings')->group(function () {
        Route::get('/trash', [TrashController::class, 'index'])->name('trash.index');
        Route::post('/trash/restore', [TrashController::class, 'restore'])->name('trash.restore');
        Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    });

    Route::middleware(['role:مدير النظام'])->group(function () {
        Route::resource('users', Usercontroller::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::post('/users/{user}/toggle-active', [Usercontroller::class, 'toggleActive'])
            ->name('users.toggle-active');
        Route::post('/users/{user}/reset-password', [Usercontroller::class, 'resetPassword'])
            ->name('users.reset-password');
    });
});

require __DIR__.'/auth.php';
