<?php

use App\Http\Controllers\StudentController;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\wbscte\Studentxi;
use Illuminate\Support\Facades\DB;
use App\Models\wbscte\AttendenceXi;
use App\Models\wbscte\MarksEntryXi;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\wbscte\AuthController;
use App\Http\Controllers\wbscte\OtherController;
use App\Http\Controllers\wbscte\PaymentController;
use App\Http\Controllers\wbscte\MgmtAdmissionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::post('/authenticate', [AuthController::class, 'authenticate']);

// test database connection
Route::get('/test-database', function () {
    try {
        DB::connection()->getPdo();
        echo "Connected successfully to the database!";
    } catch (\Exception $e) {
        die("Could not connect to the database. Error: " . $e->getMessage());
    }
});
Route::get('/admission-pdf/{appl_num}', [StudentController::class, 'downloadAdmissionForm']);


Route::prefix('payment')->group(function () {
    Route::post('/success', [PaymentController::class, 'paymentSuccess']);
    Route::post('/fail', [PaymentController::class, 'paymentFail']);
    Route::post('/push', [PaymentController::class, 'paymentPush']);
});


// payment Redirect page
Route::get('payment-redirect/{trans_id}/{order_id}/{paying_for}/{message}/{currency}/{trans_amount}/{trans_time}/{trans_status}', function ($trans_id, $order_id, $paying_for, $message, $currency, $trans_amount, $trans_time, $trans_status) {
    return view('redirect.Payment', [
        'trans_id' => $trans_id,
        'order_id' => $order_id,
        'paying_for' => $paying_for,
        'message' => $message,
        'currency' => $currency,
        'trans_amount' => $trans_amount,
        'trans_time' => $trans_time,
        // 'appl_num' => $appl_num,
        'trans_status' => $trans_status,
    ]);
})->name('payment.redirect');


Route::get('/clear', function () {
    Artisan::call('optimize:clear');
    Artisan::call('cache:clear');
    Artisan::call('route:clear');

    return "Cache cleared successfully";
});

Route::get('/symlink', function () {
    Artisan::call('storage:link');

    return "Symlink created successfully";
});
