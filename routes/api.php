<?php

use App\Http\Controllers\API\V1\Admin\AccountController;
use App\Http\Controllers\API\V1\Admin\ActionLogController;
use App\Http\Controllers\API\V1\Admin\AdminController;
use App\Http\Controllers\API\V1\Admin\BankController;
use App\Http\Controllers\API\V1\Admin\DashboardController;
use App\Http\Controllers\API\V1\Admin\InvestmentController;
use App\Http\Controllers\API\V1\Admin\InvestorController;
use App\Http\Controllers\API\V1\Admin\NextOfKinController;
use App\Http\Controllers\API\V1\Admin\PaymentHistoryController;
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
        Route::get('/admin/dashboard/totalinvestors', [DashboardController::class, 'allInvestors']);
        Route::get('/admin/dashboard/totalinvestments', [DashboardController::class, 'allInvestments']);
        Route::get('/admin/dashboard/payableamount', [DashboardController::class, 'GetPayingAmount']);
        Route::get('/admin/dashboard/payableamountmonth', [DashboardController::class, 'GetPayingAmountM']);
        Route::get('/admin/dashboard/totalongoinginvestment', [DashboardController::class, 'OngoingInvestments']);
        Route::get('/admin/dashboard/collectedamount', [DashboardController::class, 'GetCollectedAmount']);
        Route::get('/admin/dashboard/returningamount', [DashboardController::class, 'GetReturnsAmount']);
        Route::get('/admin/dashboard/collectedamountsingle', [DashboardController::class, 'GetCollectedAmountIndividual']);
        Route::get('/admin/dashboard/returningamountsingle', [DashboardController::class, 'GetReturnsAmountIndividual']);
        Route::get('/admin/dashboard/paidoutamount', [DashboardController::class, 'GetPayedOutAmount']);
        Route::get('/admin/dashboard/debtamount', [DashboardController::class, 'GetDebtAmount']);
        Route::get('/admin/dashboard/recentinvestments', [DashboardController::class, 'recentinvestments']);
        Route::get('/admin/dashboard/recentinvestors', [DashboardController::class, 'recentinvestors']);

        //Investors
        Route::apiResource('/admin/investors', InvestorController::class);
        //Investor Next of Kin
        Route::apiResource('/admin/investor/nextofkins', NextOfKinController::class);
        //Investor Account
        Route::apiResource('/admin/investor/accounts', AccountController::class);
        //plan
        Route::apiResource('/admin/plans', PlanController::class);
        //Investment
        Route::post('/admin/investments/create', [InvestmentController::class, 'store']);
        Route::post('/admin/investments/update/{investment}', [InvestmentController::class, 'update']);
        Route::post('/admin/investments/quickupdate/{investment}', [InvestmentController::class, 'sharpupdate']);
        Route::post('/admin/investments/quickupdatemonth/{investment}', [InvestmentController::class, 'sharpupdateM']);
        Route::post('/admin/investments/quickupdatemonthu', [InvestmentController::class, 'sharpupdateUM']);
        Route::post('/admin/investments/uploadold', [InvestmentController::class, 'uploadold']);
        //Route::post('/admin/investments/create', [InvestmentController::class, 'store']);
        Route::post('/admin/investments/checkpast', [InvestmentController::class, 'updatePast']);
        Route::get('/admin/investments/fetchinvestments', [InvestmentController::class, 'index']);
        Route::get('/admin/investments/fetchexportableinvestments', [InvestmentController::class, 'exportable']);
        Route::get('/admin/investments/get-investment/{investment}', [InvestmentController::class, 'show']);
        Route::get('/admin/investments/getweeklypayoutamount', [InvestmentController::class, 'GetPayingAmount']);
        Route::get('/admin/investments/getmonthlypayoutamount', [InvestmentController::class, 'GetMPayingAmount']);
        Route::get('/admin/investments/getsplitamount', [InvestmentController::class, 'GetSplitPayingAmount']);
        Route::get('/admin/investments/getremainingpayoutamount', [InvestmentController::class, 'GetRemainingAmount']);
        Route::get('/admin/investments/getremainingpayoutamountmonthly', [InvestmentController::class, 'GetRemainingMonthlyAmount']);
        Route::post('/admin/investments/unfreezeinvestments', [InvestmentController::class, 'unfreezeinvestments']);
        //Route::post('/admin/investments/unexpireinvestments', [InvestmentController::class, 'unexpireinvestments']);
        Route::get('/admin/investments/unfreezeinvestment', [InvestmentController::class, 'unfreezeinvestment']);
        Route::get('/admin/investments/freezeinvestment', [InvestmentController::class, 'freezeinvestment']);
        Route::get('/admin/investments/getfrozeninvestmentsbyid', [InvestmentController::class, 'getfrozeninvestmentsbyid']);
        Route::get('/admin/investments/getfrozeninvestmentsbyid2', [InvestmentController::class, 'getfrozeninvestmentsbyid2']);
        Route::get('/admin/investments/getfrozeninvestmentsaccountdetails', [InvestmentController::class, 'getfrozeninvestmentsaccountdetails']);
        Route::get('/admin/investments/getfrozeninvestmentsaccountdetails2', [InvestmentController::class, 'getfrozeninvestmentsaccountdetails2']);
        Route::post('/admin/investments/sendotp', [InvestmentController::class, 'sendOTP']);

        //Payment History
        Route::get('/admin/payments/fetchhistories', [PaymentHistoryController::class, 'index']);
        Route::get('/admin/payments/getweekpayedamount', [PaymentHistoryController::class, 'GetPayedAmount']);
        //Action Logs
        Route::get('/admin/fetchlogs', [ActionLogController::class, 'index']);

        Route::middleware(['customersupport', 'techsupport'])->group(function () {
            Route::post('/admin/investments/approve/{investment}', [InvestmentController::class, 'approve']);
            Route::post('/admin/investments/haltinvestments', [InvestmentController::class, 'haltinvestments']);
            Route::post('/admin/investments/unhaltinvestments', [InvestmentController::class, 'unhaltinvestments']);
        });
        Route::middleware(['techsupport'])->group(function () {
            Route::post('/admin/create', [AdminController::class, 'register']);
            Route::post('/admin/edit/{admin}', [AdminController::class, 'update']);
            Route::get('/admin/get-admins', [AdminController::class, 'index']);
            Route::get('/admin/get-admin/{admin}', [AdminController::class, 'show']);
            Route::delete('/admin/delete/{admin}', [AdminController::class, 'destroy']);
            Route::delete('/admin/investments/delete/{investment}', [InvestmentController::class, 'destroy']);
            Route::get('/admin/investments/freezebiginvestments', [InvestmentController::class, 'freezebiginvestments']);
            Route::get('/admin/investments/freezebiginvestments2', [InvestmentController::class, 'freezebiginvestments2']);
            Route::post('/admin/investments/getready', [InvestmentController::class, 'updateReady']);
            Route::post('/admin/investments/getreadymonth', [InvestmentController::class, 'updateReadyMonth']);
        });
        Route::middleware(['restrictothers'])->group(function () {
            Route::post('/admin/investments/payinvestment', [InvestmentController::class, 'payInvestment']);
            Route::post('/admin/investments/justpayinvestment', [InvestmentController::class, 'justpayInvestment']);
            //Route::post('/admin/investments/retrypayment', [InvestmentController::class, 'retrypayment']);
            Route::post('/admin/investments/payweeklyinvestment', [InvestmentController::class, 'paybulkWeeklyInvestment']);
            Route::post('/admin/investments/payweeklyfrozeninvestment', [InvestmentController::class, 'paybulkWeeklyFrozenInvestment']);
            //Route::post('/admin/investments/paymonthlyinvestment', [InvestmentController::class, 'paybulkMonthlyInvestment']);
            //Route::post('/admin/investments/paymonthlyfrozeninvestment', [InvestmentController::class, 'paybulkMonthlyFrozenInvestment']);
            //Route::post('/admin/investments/paymonthlyinvestment20th', [InvestmentController::class, 'paybulkMonthlyInvestment2']);
            //Route::post('/admin/investments/paymonthlyinvestmentsharp', [InvestmentController::class, 'paybulkMonthlyInvestmentsharp']);
            //Route::post('/admin/investments/payweekremaininginvestment', [InvestmentController::class, 'payweekRemaining']);
        });

    });
    Route::post('/admin/auth/login', [AdminAuthController::class, 'login']);
    Route::post('/admin/auth/verifyotp', [AdminAuthController::class, 'check_otp']);

    Route::get('/fetchbanks', [BankController::class, 'fetchbanks']);
    Route::get('/fetchaccountdetails', [AccountController::class, 'getAccountName']);
    Route::get('/explodebanks', [BankController::class, 'explodebanks']);
    Route::get('/fixinvestments', [BankController::class, 'fixinvestments']);
    Route::get('/fixinvestments3', [BankController::class, 'fixinvestments3']);
    Route::get('/fixinvestments4', [BankController::class, 'fixinvestments4']);
    Route::get('/fixinvestments5', [BankController::class, 'fixinvestments5']);

});
