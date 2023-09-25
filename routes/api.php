<?php

use App\Http\Controllers\API\V1\Admin\AccountController;
use App\Http\Controllers\API\V1\Admin\AdminController;
use App\Http\Controllers\API\V1\Admin\BankController;
use App\Http\Controllers\API\V1\Admin\InvestorController;
use App\Http\Controllers\API\V1\Admin\NextOfKinController;
use App\Http\Controllers\API\V1\Admin\PlanController;
use App\Http\Controllers\API\V1\Auth\AdminAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return $request->user();
    });

    // Only for admin
    Route::middleware(['auth:sanctum', 'type.admin'])->group(function () {
        Route::post('/admin/logout', [AdminAuthController::class, 'logout']);
        Route::get('/admin', [AdminController::class, 'getA']);
        Route::post('/admin/updateprofile', [AdminController::class, 'changeprofile']);
        Route::post('/admin/changepassword', [AdminController::class, 'changepassword']);

        //dashboard

        //Investors
        Route::apiResource('/admin/investors', InvestorController::class);
        //Investor Next of Kin
        Route::apiResource('/admin/investor/nextofkins', NextOfKinController::class);
        //Investor Account
        Route::apiResource('/admin/investor/accounts', AccountController::class);
        //plan
        Route::apiResource('/admin/plans', PlanController::class);

        Route::middleware(['restrictothers'])->group(function () {
            Route::post('/admin/create', [AdminController::class, 'register']);
            Route::post('/admin/edit/{admin}', [AdminController::class, 'update']);
            Route::get('/admin/get-admins', [AdminController::class, 'index']);
            Route::get('/admin/get-admin/{admin}', [AdminController::class, 'show']);
            Route::delete('/admin/delete/{admin}', [AdminController::class, 'destroy']);
        });

    });
    Route::post('/admin/auth/login', [AdminAuthController::class, 'login']);


    Route::get('/fetchbanks', [BankController::class, 'fetchbanks']);
    Route::get('/fetchaccountdetails', [AccountController::class, 'getAccountName']);
    Route::get('/explodebanks', [BankController::class, 'explodebanks']);

});