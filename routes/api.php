<?php

use App\Http\Controllers\Notification\NotificationController;
use App\Http\Controllers\VehicleModel\VehicleModelController;
use App\Http\Controllers\VehicleMake\VehicleMakeController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Companies\CompaniesController;
use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\Employee\EmployeeController;
use App\Http\Controllers\Estimate\EstimateController;
use App\Http\Controllers\Services\ServiceController;
use App\Http\Controllers\Invoice\InvoiceController;
use App\Http\Controllers\Comment\CommentController;
use App\Http\Controllers\Files\FilesController;
use App\Http\Controllers\Const\ConstController;
use App\Http\Repository\Email\EmailRepository;
use App\Http\Repository\Const\ConstRepository;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Role\RoleController;
use App\Http\Controllers\Job\JobController;
use App\Events\Invoice\MakeInvoiceEvent;
use App\Http\Controllers\MotController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Authentication Api.
Route::post('login', [AuthController::class, 'login'])->name('login');
Route::post('/forgetpassword', [AuthController::class, 'forgetPassword'])->name('forget_password');
Route::post('/resetpassword', [AuthController::class, 'resetPassword'])->name('reset_password');

// Authenticated route group.
Route::middleware('auth:api')->group(function () {
    // change password
    Route::post('changepassword', [AuthController::class, 'changepassword']);
    // Company Apis.
    Route::get('company/{id??}', [CompaniesController::class, 'allCompanies']);
    Route::post('company', [CompaniesController::class, 'createCompany']);
    Route::put('company/{id}', [CompaniesController::class, 'updateCompany']);
    Route::delete('company/{id}', [CompaniesController::class, 'deleteCompany']);

    // Employee Apis.
    Route::get('team/{id??}', [EmployeeController::class, 'allEmployee']);
    Route::post('team', [EmployeeController::class, 'createEmployee']);
    Route::put('team/{id}', [EmployeeController::class, 'updateEmployee']);
    Route::delete('team/{id}', [EmployeeController::class, 'deleteEmployee']);
    Route::post('invitation/{id}', [EmployeeController::class, 'invitationSend']);

    // Customer Aips.
    Route::get('customer/{customer}/work-history', [CustomerController::class, 'workHistoryPdf']);
    Route::get('customer/{id??}', [CustomerController::class, 'allCustomer']);
    Route::post('customer', [CustomerController::class, 'createCustomer']);
    Route::put('customer/{id}', [CustomerController::class, 'updateCustomer']);
    Route::delete('customer/{id}', [CustomerController::class, 'deleteCustomer']);

    // Files Apis.
    Route::post('file', [FilesController::class, 'createFile']);

    // Estimate Aips.
    Route::get('estimate/{id??}', [EstimateController::class, 'allEstimate']);
    Route::post('estimate', [EstimateController::class, 'createEstimate']);
    Route::put('estimate/{id}', [EstimateController::class, 'updateEstimate']);
    Route::delete('estimate/{id}', [EstimateController::class, 'deleteEstimate']);
    Route::put('estimate/status/{id}', [EstimateController::class, 'allStatus']);  // status update.

    // Vehicle Make Api.
    Route::get('make/{id??}', [VehicleMakeController::class, 'allMake']);
    Route::post('make', [VehicleMakeController::class, 'createMake']);
    Route::put('make/{id}', [VehicleMakeController::class, 'updateMake']);
    Route::delete('make/{id}', [VehicleMakeController::class, 'deleteMake']);

    // Vehicle Model Api.
    Route::get('model/{id??}', [VehicleModelController::class, 'allModel']);
    Route::post('model', [VehicleModelController::class, 'createModel']);
    Route::put('model/{id}', [VehicleModelController::class, 'updateModel']);
    Route::delete('model/{id}', [VehicleModelController::class, 'deleteModel']);

    // Job apis.
    Route::get('job/{id??}', [JobController::class, 'allJob']);
    Route::post('job', [JobController::class, 'createJob']);
    Route::put('job/{id}', [JobController::class, 'updateJob']);
    Route::delete('job/{id}', [JobController::class, 'deleteJob']);
    Route::post('job/convertjob', [JobController::class, 'jobConvert']);
    Route::get('/job/{id}/history', [JobController::class, 'jobHistory']);

    // Comment apis.
    Route::get('comment', [CommentController::class, 'allComment']);
    Route::post('comment', [CommentController::class, 'createComment']);
    Route::put('comment/{id}', [CommentController::class, 'updateComment']);
    Route::delete('comment/{id}', [CommentController::class, 'deleteComment']);

    // Services apis
    Route::get('service/{id??}', [ServiceController::class, 'allService']);
    Route::post('service', [ServiceController::class, 'createService']);
    Route::put('service/{id}', [ServiceController::class, 'updateService']);
    Route::delete('service/{id}', [ServiceController::class, 'deleteService']);

    // Role apis
    Route::get('role/{id??}', [RoleController::class, 'allRole']);
    Route::post('role', [RoleController::class, 'createRole']);
    Route::put('role/{id}', [RoleController::class, 'updateRole']);
    Route::delete('role/{id}', [RoleController::class, 'deleteRole']);

    // Invoice apis
    Route::get('invoice/export', [InvoiceController::class, 'exportCsv']);
    Route::get('invoice/{id??}', [InvoiceController::class, 'allInvoice']);
    Route::post('invoice/{id??}', [InvoiceController::class, 'createInvoice']);
    Route::put('invoice/{id??}', [InvoiceController::class, 'updateInvoice']);
    Route::delete('invoice', [InvoiceController::class, 'deleteInvoice']);
    Route::post('sendinvoice', [InvoiceController::class, 'sendInvoice']);

    // // Contract
    // Route::get('/contract',[ContractController::class,'index']);
    // Route::post('/contract',[ContractController::class,'send']);

    //Dashboard 
    Route::get('/dashboard', [DashboardController::class, 'getDashboardData']);
    Route::get('/mot/{registration}', [MotController::class, 'fetchMotData']);
});

// Constant api.
Route::get('const', [ConstController::class, 'conts']);

// Third party api.
Route::get('thirdparty', [ConstRepository::class, 'ThirdPartyApi']);

Route::get('/notify', [NotificationController::class, 'sendAppNotification']);

// ONLY FOR TESTING.
Route::get('test', function() {
    // // Assuming $user is the newly registered user
    // event(new MakeInvoiceEvent("hii"));
    $emailData = EmailRepository::SendEmail("devnetmaxims@gmail.com", "test", "welcome", $data = [], $attachment = []);
    dd($emailData);
})->name('test');

Route::get('testsms', function(){
    $data = sendTwilioSms("8447118561", "Test messagess");
    dd($data);
});





