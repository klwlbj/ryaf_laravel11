<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\MaterialCategoryController;
use App\Http\Controllers\Admin\MaterialController;
use App\Http\Controllers\Admin\MaterialFlowController;
use App\Http\Controllers\Admin\MaterialManufacturerController;
use App\Http\Controllers\Admin\MaterialSpecificationController;
use App\Http\Controllers\Admin\UploadController;
use App\Http\Controllers\Admin\WarehouseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::any('/upload', [UploadController::class, 'upload']);

//管理员
Route::prefix('admin')->group(function () {
    Route::post('/getAllList', [AdminController::class, 'getAllList']);
});

Route::prefix('warehouse')->group(function () {
    Route::post('/getAllList', [WarehouseController::class, 'getAllList']);
});

//物品厂家
Route::prefix('materialManufacturer')->group(function () {
    Route::view('/view', 'admin.materialManufacturer');
//    Route::any('/view', [MaterialManufacturerController::class, 'view']);
    Route::post('/getList', [MaterialManufacturerController::class, 'getList']);
    Route::post('/getInfo', [MaterialManufacturerController::class, 'getInfo']);
    Route::post('/getAllList', [MaterialManufacturerController::class, 'getAllList']);
    Route::post('/add', [MaterialManufacturerController::class, 'add']);
    Route::post('/update', [MaterialManufacturerController::class, 'update']);
    Route::post('/delete', [MaterialManufacturerController::class, 'delete']);
});

//物料分类
Route::prefix('materialCategory')->group(function () {
    Route::view('/view', 'admin.materialCategory');
//    Route::any('/view', [MaterialCategoryController::class, 'view']);
    Route::post('/getList', [MaterialCategoryController::class, 'getList']);
    Route::post('/getInfo', [MaterialCategoryController::class, 'getInfo']);
    Route::post('/getAllList', [MaterialCategoryController::class, 'getAllList']);
    Route::post('/add', [MaterialCategoryController::class, 'add']);
    Route::post('/update', [MaterialCategoryController::class, 'update']);
    Route::post('/delete', [MaterialCategoryController::class, 'delete']);
});

//物料分类规格
Route::prefix('materialSpecification')->group(function () {
    Route::view('/view', 'admin.materialSpecification');
//    Route::any('/view', [MaterialSpecificationController::class, 'view']);
    Route::post('/getList', [MaterialSpecificationController::class, 'getList']);
    Route::post('/getInfo', [MaterialSpecificationController::class, 'getInfo']);
    Route::post('/getAllList', [MaterialSpecificationController::class, 'getAllList']);
    Route::post('/add', [MaterialSpecificationController::class, 'add']);
    Route::post('/update', [MaterialSpecificationController::class, 'update']);
    Route::post('/delete', [MaterialSpecificationController::class, 'delete']);
});


//物料
Route::prefix('material')->group(function () {
    Route::view('/view', 'admin.material');
//    Route::any('/view', [MaterialController::class, 'view']);
    Route::post('/getList', [MaterialController::class, 'getList']);
    Route::post('/getInfo', [MaterialController::class, 'getInfo']);
    Route::post('/getAllList', [MaterialController::class, 'getAllList']);
    Route::post('/add', [MaterialController::class, 'add']);
    Route::post('/update', [MaterialController::class, 'update']);
    Route::post('/delete', [MaterialController::class, 'delete']);
});

Route::prefix('materialFlow')->group(function () {
    Route::view('/view', 'admin.materialFlow');
//    Route::any('/view', [MaterialFlowController::class, 'view']);
    Route::post('/getList', [MaterialFlowController::class, 'getList']);
    Route::post('/inComing', [MaterialFlowController::class, 'inComing']);
    Route::post('/outComing', [MaterialFlowController::class, 'outComing']);
});
