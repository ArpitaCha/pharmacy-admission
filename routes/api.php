<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\MgmtAdmissionController;
use App\Http\Controllers\SpotcounsellingController;
use App\Http\Controllers\AdmissionController;



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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/is-maintenance', [AuthController::class, 'maintenance']);

Route::post('/authenticate', [AuthController::class, 'authenticate']);
Route::post('/validate-security-code', [AuthController::class, 'validateSecurityCode']);

Route::post('/change-password', [AuthController::class, 'changePassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::middleware('auth.token')->group(function () {

    Route::prefix('master')->group(function () {
        Route::get('/district-list/{state_code?}/{user_type?}', [CommonController::class, 'allDistricts']);
        Route::post('/institute-stream-wise/{user_type?}', [CommonController::class, 'allInstList']);
        Route::post('/institute-wise-stream', [CommonController::class, 'streamListinstListWise']);
        Route::post('/trade-list/{user_type?}', [CommonController::class, 'streamList']);
        Route::post('/state-list/{user_type?}', [CommonController::class, 'allStates']);
        Route::post('/religion-list/{user_type?}', [CommonController::class, 'allReligions']);
        Route::post('/caste-list/{user_type?}', [CommonController::class, 'allCastes']);
        Route::get('/subdivision-list/{dist_id?}/{user_type?}', [CommonController::class, 'allSubdivisions']);
        Route::get('/eligibility-list/{user_type?}', [CommonController::class, 'eligibilityList']);
        Route::get('/eligibility-state-list/{user_type?}', [CommonController::class, 'eligibilityStateList']);
        Route::get('/board-list/{code}/{user_type?}', [CommonController::class, 'boardList']);
        Route::get('/verifier-type', [CommonController::class, 'verifierType']);
        Route::post('/district-wise-institute', [CommonController::class, 'districtWiseInstitute']);
        Route::get('/all_roles', [CommonController::class, 'allRoles'])->withoutMiddleware('auth.token');
        Route::get('/other-board', [CommonController::class, 'OtherBoard']);
        Route::get('/block-list/{subdivision?}/{user_type?}', [CommonController::class, 'allBlocks']);
    });
    Route::get('/dashboard-count', [DashboardController::class, 'countDashboardCards']);
    Route::prefix('student')->group(function () {
        Route::get('/student-info/{form_num}', [StudentController::class, 'getStudentInfo'])->withoutMiddleware('auth.token');
        Route::post('/student-update', [StudentController::class, 'studentInfoUpdate']);
        Route::get('/student-details/{form_num}', [StudentController::class, 'studentDetails'])->withoutMiddleware('auth.token');
        Route::get('/student-admission-fees-download/{form_num}/{type}', [StudentController::class, 'downloadAdmissionFees'])->withoutMiddleware('auth.token');
    });
    Route::post('/students/export', [StudentController::class, 'downloadAdmissionFeesExcel'])->withoutMiddleware('auth.token');
    Route::post('/admission-payment-fees', [PaymentController::class, 'AdmissionPaymentFees'])->withoutMiddleware('auth.token');
    Route::prefix('admission')->group(function () {
        Route::post('/submit', [AdmissionController::class, 'submitStudents'])->withoutMiddleware('auth.token');
        Route::get('/admission-list', [AdmissionController::class, 'admissionList']);
        Route::post('/approve-council', [AdmissionController::class, 'approveCouncil']);
        Route::get('/verifier-list', [AdmissionController::class, 'verifierList']);
        Route::post('/add-verifier', [AdmissionController::class, 'addVerifier']);
        Route::post('/update-verifier', [AdmissionController::class, 'updateVerifier']);
        Route::post('/inActive-verifier', [AdmissionController::class, 'inActiveVerifier']);
        Route::post('/business-address', [AdmissionController::class, 'addressBusiness']);
        Route::get('/check-validation-fields/{role}', [AdmissionController::class, 'checkValidationFields'])->withoutMiddleware('auth.token');

        Route::post('/other-district-verifier', [AdmissionController::class, 'otherdistrictverifier']);

        Route::post('/district-wise-verifier', [AdmissionController::class, 'districtWiseVerifier']);
        Route::post('/district-wise-assign', [AdmissionController::class, 'districtWiseAssign']);
    });
    Route::get('/get-branch/{ifsc}', [AdmissionController::class, 'getBranchByIfsc'])->withoutMiddleware('auth.token');
});


Route::get('/clear', function () {
    Artisan::call('optimize:clear');
    Artisan::call('cache:clear');
    Artisan::call('route:clear');

    return "Cache cleared successfully";
});
