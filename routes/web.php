<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

//Route::get('/', function () {
//    return view('welcome');
//});

//Route::get('/myCommand', function () {
//    print_r(Artisan::call('config:cache'));die;
//});

Route::middleware(['login'])->group(function () {
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

    Route::prefix('advancedOrder')->group(function () {
        Route::view('/view', 'admin.advancedOrder');
    });

    Route::prefix('financialIncome')->group(function () {
        Route::view('/view', 'admin.financialIncome');
    });
});

