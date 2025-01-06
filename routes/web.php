<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

//Route::get('/', function () {
//    return view('welcome');
//});

//Route::get('/myCommand', function () {
//    print_r(Artisan::call('make:command UpdateFakeHeartBeat'));die;
//});

Route::get('/storageLink', function () {
   Artisan::call('storage:link');
   dd(Artisan::output());
});
Route::view('/login', 'admin.login');
Route::prefix('preInstallation')->group(function () {
    Route::view('/view', 'admin.preInstallation');
});
Route::middleware(['login'])->group(function () {
    Route::view('/', 'admin.index');

    Route::view('/test', 'admin.test');

    Route::prefix('materialManufacturer')->group(function () {
        Route::view('/view', 'admin.materialManufacturer');
    });

    Route::prefix('materialCategory')->group(function () {
        Route::view('/view', 'admin.materialCategory');
    });

    Route::prefix('materialSpecification')->group(function () {
        Route::view('/view', 'admin.materialSpecification');
    });

    Route::prefix('material')->group(function () {
        Route::view('/view', 'admin.material');
    });

    Route::prefix('materialFlow')->group(function () {
        Route::view('/view', 'admin.materialFlow');
    });

    Route::prefix('materialPurchase')->group(function () {
        Route::view('/view', 'admin.materialPurchase');
    });

    Route::prefix('order')->group(function () {
        Route::view('/view', 'admin.order');
    });
    Route::prefix('otherOder')->group(function () {
        Route::view('/view', 'admin.otherOder');
    });

    Route::prefix('advancedOrder')->group(function () {
        Route::view('/view', 'admin.advancedOrder');
    });

    Route::prefix('financialIncome')->group(function () {
        Route::view('/view', 'admin.financialIncome');
    });
    Route::prefix('financialAdvancedOrder')->group(function () {
        Route::view('/view', 'admin.financialAdvancedOrder');
    });
    Route::prefix('securityDepositFundsOrder')->group(function () {
        Route::view('/view', 'admin.securityDepositFundsOrder');
    });

    Route::prefix('receivableFundsOrder')->group(function () {
        Route::view('/view', 'admin.receivableFundsOrder');
    });

    Route::prefix('department')->group(function () {
        Route::view('/view', 'admin.department');
    });
    Route::prefix('admin')->group(function () {
        Route::view('/view', 'admin.admin');
    });

    Route::prefix('adminPermission')->group(function () {
        Route::view('/view', 'admin.adminPermission');
    });

    Route::prefix('installationSummary')->group(function () {
        Route::view('/view', 'admin.installationSummary');
    });

    Route::prefix('installationRegister')->group(function () {
        Route::view('/view', 'admin.installationRegister');
    });

    Route::prefix('receivableAccount')->group(function () {
        Route::view('/view', 'admin.receivableAccount');
    });

    Route::prefix('maintain')->group(function () {
        Route::view('place/view', 'admin.maintainPlace');
        Route::view('noData/view', 'admin.maintainNoData');
        Route::view('installationCheck/view', 'admin.maintainInstallationCheck');
    });
    Route::prefix('preInstallationList')->group(function () {
        Route::view('/view', 'admin.preInstallationList');
    });
});

