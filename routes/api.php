<?php

use App\Http\Controllers\Admin\ApprovalController;
use App\Http\Controllers\Admin\MaterialApplyController;
use App\Http\Controllers\Admin\MaterialFlowConsumeController;
use App\Http\Controllers\Admin\ReportController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\CheckIpMiddleware;
use App\Http\Middleware\SignatureMiddleware;
use App\Http\Controllers\Admin\AreaController;
use App\Http\Controllers\Admin\NodeController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\UploadController;
use App\Http\Controllers\Admin\AddressController;
use App\Http\Controllers\Admin\MaintainController;
use App\Http\Controllers\Admin\MaterialController;
use App\Http\Controllers\Admin\SecurityController;
use App\Http\Controllers\Admin\WarehouseController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\OtherOrderController;
use App\Http\Controllers\Admin\InstallationController;
use App\Http\Controllers\Admin\MaterialFlowController;
use App\Http\Controllers\Admin\AdvancedOrderController;
use App\Http\Controllers\Admin\AdminPermissionController;
use App\Http\Controllers\Admin\FinancialIncomeController;
use App\Http\Controllers\Admin\PreInstallationController;
use App\Http\Controllers\Admin\MaterialCategoryController;
use App\Http\Controllers\Admin\MaterialPurchaseController;
use App\Http\Controllers\Admin\ReceivableAccountController;
use App\Http\Controllers\Admin\InstallationRegisterController;
use App\Http\Controllers\Admin\MaterialManufacturerController;
use App\Http\Controllers\Admin\MaterialSpecificationController;

Route::any('/upload', [UploadController::class, 'upload']);

Route::any('/pushUnits', [\App\Http\Controllers\ScriptController::class, 'pushUnits']);

Route::any('/login', [AdminController::class, 'login']);

Route::post('/addPreInstallation', [PreInstallationController::class, 'add']);

Route::prefix('security')
    ->middleware([SignatureMiddleware::class, CheckIpMiddleware::class])
    ->group(function () {
        Route::any('/total', [SecurityController::class, 'total']);
        Route::any('/unitTotal', [SecurityController::class, 'unitTotal']);
        Route::any('/list', [SecurityController::class, 'list']);
        Route::any('/alertTotal', [SecurityController::class, 'alertTotal']);
        Route::any('/unitAlertTotal', [SecurityController::class, 'unitAlertTotal']);
        Route::any('/alertList', [SecurityController::class, 'alertList']);
        Route::get('/getGuangzhouList', [AreaController::class, 'getList2']);
    });

Route::prefix('test')->group(function () {
    Route::get('/getList', [\App\Http\Controllers\Admin\TestController::class, 'getList']);
    Route::get('/getList2', [\App\Http\Controllers\Admin\TestController::class, 'getList2']);
});

Route::any('/test', [\App\Http\Controllers\DemoController::class, 'test']);
Route::any('/test1', [\App\Http\Controllers\DemoController::class, 'test1']);
Route::any('/importDemo', [\App\Http\Controllers\DemoController::class, 'import']);
Route::any('/compareDemo', [\App\Http\Controllers\DemoController::class, 'compare']);

Route::prefix('yunChuang')->group(function () {
    Route::any('/updateDevice', [\App\Http\Controllers\YunChuangController::class, 'updateDevice']);
});


//标准地址
Route::any('address/getStandardAddress', [AddressController::class, 'getStandardAddress']);

Route::middleware(['login'])->group(function () {
    Route::post('/logout', [AdminController::class, 'logout']);
    //管理员
    Route::prefix('admin')->group(function () {
        Route::post('/getList', [AdminController::class, 'getList']);
        Route::post('/getAllList', [AdminController::class, 'getAllList']);
        Route::post('/add', [AdminController::class, 'add']);
        Route::post('/update', [AdminController::class, 'update']);
        Route::post('/getInfo', [AdminController::class, 'getInfo']);
        Route::post('/resetPassword', [AdminController::class, 'resetPassword']);
        Route::post('/getBacklogCount', [AdminController::class, 'getBacklogCount']);
    });

    Route::prefix('warehouse')->group(function () {
        Route::post('/getAllList', [WarehouseController::class, 'getAllList']);
    });

    //物品厂家
    Route::prefix('materialManufacturer')->group(function () {
        Route::post('/getList', [MaterialManufacturerController::class, 'getList']);
        Route::post('/getInfo', [MaterialManufacturerController::class, 'getInfo']);
        Route::post('/getAllList', [MaterialManufacturerController::class, 'getAllList']);
        Route::post('/add', [MaterialManufacturerController::class, 'add']);
        Route::post('/update', [MaterialManufacturerController::class, 'update']);
        Route::post('/delete', [MaterialManufacturerController::class, 'delete']);
    });

    //物料分类
    Route::prefix('materialCategory')->group(function () {
        Route::post('/getList', [MaterialCategoryController::class, 'getList']);
        Route::post('/getInfo', [MaterialCategoryController::class, 'getInfo']);
        Route::post('/getAllList', [MaterialCategoryController::class, 'getAllList']);
        Route::post('/add', [MaterialCategoryController::class, 'add']);
        Route::post('/update', [MaterialCategoryController::class, 'update']);
        Route::post('/delete', [MaterialCategoryController::class, 'delete']);
    });

    //物料分类规格
    Route::prefix('materialSpecification')->group(function () {
        Route::post('/getList', [MaterialSpecificationController::class, 'getList']);
        Route::post('/getInfo', [MaterialSpecificationController::class, 'getInfo']);
        Route::post('/getAllList', [MaterialSpecificationController::class, 'getAllList']);
        Route::post('/add', [MaterialSpecificationController::class, 'add']);
        Route::post('/update', [MaterialSpecificationController::class, 'update']);
        Route::post('/delete', [MaterialSpecificationController::class, 'delete']);
    });

    //物料
    Route::prefix('material')->group(function () {
        Route::post('/getList', [MaterialController::class, 'getList']);
        Route::post('/getInfo', [MaterialController::class, 'getInfo']);
        Route::post('/getAllList', [MaterialController::class, 'getAllList']);
        Route::post('/add', [MaterialController::class, 'add']);
        Route::post('/update', [MaterialController::class, 'update']);
        Route::post('/delete', [MaterialController::class, 'delete']);
        Route::post('/getDetailList', [MaterialController::class, 'getDetailList']);
        Route::post('/getDetail', [MaterialController::class, 'getDetail']);
        Route::post('/reportExport', [MaterialController::class, 'reportExport']);
        Route::post('/verifyWarn', [MaterialController::class, 'verifyWarn']);
    });

    Route::prefix('materialFlow')->group(function () {
        Route::post('/getList', [MaterialFlowController::class, 'getList']);
        Route::post('/inComing', [MaterialFlowController::class, 'inComing']);
        Route::post('/outComing', [MaterialFlowController::class, 'outComing']);
        Route::post('/getInfo', [MaterialFlowController::class, 'getInfo']);
        Route::post('/verify', [MaterialFlowController::class, 'verify']);
        Route::post('/setPrice', [MaterialFlowController::class, 'setPrice']);
        Route::post('/inComingUpdate', [MaterialFlowController::class, 'inComingUpdate']);
        Route::post('/cancel', [MaterialFlowController::class, 'cancel']);
    });

    Route::prefix('materialFlowConsume')->group(function () {
        Route::post('/getList', [MaterialFlowConsumeController::class, 'getList']);
        Route::post('/addConsumeFlow', [MaterialFlowConsumeController::class, 'addConsumeFlow']);
        Route::post('/getConsumeList', [MaterialFlowConsumeController::class, 'getConsumeList']);
        Route::post('/deleteConsumeFlow', [MaterialFlowConsumeController::class, 'deleteConsumeFlow']);
    });

    Route::prefix('materialApply')->group(function () {
        Route::post('/getList', [MaterialApplyController::class, 'getList']);
        Route::post('/getSelectList', [MaterialApplyController::class, 'getSelectList']);
        Route::post('/getRelationList', [MaterialApplyController::class, 'getRelationList']);
        Route::post('/getPreInfo', [MaterialApplyController::class, 'getPreInfo']);
        Route::post('/add', [MaterialApplyController::class, 'add']);
        Route::post('/update', [MaterialApplyController::class, 'update']);
        Route::post('/getInfo', [MaterialApplyController::class, 'getInfo']);
    });

    Route::prefix('materialPurchase')->group(function () {
        Route::post('/getList', [MaterialPurchaseController::class, 'getList']);
        Route::post('/add', [MaterialPurchaseController::class, 'add']);
        Route::post('/getInfo', [MaterialPurchaseController::class, 'getInfo']);
        Route::post('/update', [MaterialPurchaseController::class, 'update']);
        Route::post('/delete', [MaterialPurchaseController::class, 'delete']);
        Route::post('/approve', [MaterialPurchaseController::class, 'approve']);
        Route::post('/complete', [MaterialPurchaseController::class, 'complete']);
    });

    Route::prefix('order')->group(function () {
        Route::post('/getList', [OrderController::class, 'getList']);
        Route::post('/getInfo', [OrderController::class, 'getInfo']);
        Route::post('/update', [OrderController::class, 'update']);
        Route::post('/addAccountFlow', [OrderController::class, 'addAccountFlow']);
        Route::post('/getAccountFlow', [OrderController::class, 'getAccountFlow']);
        Route::post('/approveAccountFlow', [OrderController::class, 'approveAccountFlow']);
    });

    Route::prefix('advancedOrder')->group(function () {
        Route::post('/getList', [AdvancedOrderController::class, 'getList']);
        Route::post('/getInfo', [AdvancedOrderController::class, 'getInfo']);
        Route::post('/link', [AdvancedOrderController::class, 'link']);
        Route::post('/getInfo', [AdvancedOrderController::class, 'getInfo']);
        Route::post('/getLinkInfo', [AdvancedOrderController::class, 'getLinkInfo']);
        Route::post('/add', [AdvancedOrderController::class, 'add']);
        Route::post('/update', [AdvancedOrderController::class, 'update']);
        Route::post('/delete', [AdvancedOrderController::class, 'delete']);
    });

    Route::prefix('otherOrder')->group(function () {
        Route::post('/getInfo', [OtherOrderController::class, 'getInfo']);
        Route::post('/add', [OtherOrderController::class, 'add']);
        Route::post('/update', [OtherOrderController::class, 'update']);
        Route::post('/delete', [OtherOrderController::class, 'delete']);
    });

    Route::prefix('area')->group(function () {
        Route::any('/getList', [AreaController::class, 'getList']);
        Route::any('/getList2', [AreaController::class, 'getList2']);
        Route::get('/generateJson', [AreaController::class, 'generateJson']);
        Route::get('/generateJson2', [AreaController::class, 'generateJson2']);
    });

    Route::prefix('financialIncome')->group(function () {
        Route::post('/getList', [FinancialIncomeController::class, 'getList']);
        Route::post('/getStageInfo', [FinancialIncomeController::class, 'getStageInfo']);
        Route::post('/getArrearsInfo', [FinancialIncomeController::class, 'getArrearsInfo']);
    });

    Route::prefix('department')->group(function () {
        Route::post('/getTreeList', [DepartmentController::class, 'getTreeList']);
        Route::post('/add', [DepartmentController::class, 'add']);
        Route::post('/update', [DepartmentController::class, 'update']);
        Route::post('/getInfo', [DepartmentController::class, 'getInfo']);
        Route::post('/delete', [DepartmentController::class, 'delete']);
    });

    Route::prefix('adminPermission')->group(function () {
        Route::post('/getTreeList', [AdminPermissionController::class, 'getTreeList']);
        Route::post('/add', [AdminPermissionController::class, 'add']);
        Route::post('/update', [AdminPermissionController::class, 'update']);
        Route::post('/getInfo', [AdminPermissionController::class, 'getInfo']);
        Route::post('/delete', [AdminPermissionController::class, 'delete']);
    });

    Route::prefix('installation')->group(function () {
        Route::post('/summary', [InstallationController::class, 'summary']);
    });

    Route::prefix('installationRegister')->group(function () {
        Route::post('/getList', [InstallationRegisterController::class, 'getList']);
        Route::post('/add', [InstallationRegisterController::class, 'add']);
        Route::post('/update', [InstallationRegisterController::class, 'update']);
        Route::post('/getInfo', [InstallationRegisterController::class, 'getInfo']);
        Route::post('/delete', [InstallationRegisterController::class, 'delete']);
    });

    Route::prefix('node')->group(function () {
        Route::post('/getAllList', [NodeController::class, 'getAllList']);
        Route::post('/getTreeList', [NodeController::class, 'getTreeList']);
    });

    Route::prefix('receivableAccount')->group(function () {
        Route::post('/getList', [ReceivableAccountController::class, 'getList']);
        Route::post('/getInfo', [ReceivableAccountController::class, 'getInfo']);
        Route::post('/update', [ReceivableAccountController::class, 'update']);
        Route::post('/batchUpdate', [ReceivableAccountController::class, 'batchUpdate']);
        Route::post('/delete', [ReceivableAccountController::class, 'delete']);
        Route::post('/import', [ReceivableAccountController::class, 'import']);
        Route::post('/addFlow', [ReceivableAccountController::class, 'addFlow']);
        Route::post('/batchAddFlow', [ReceivableAccountController::class,'batchAddFlow']);
        Route::post('/deleteFlow', [ReceivableAccountController::class,'deleteFlow']);
        Route::post('/getFlow', [ReceivableAccountController::class, 'getFlow']);
        Route::post('/syncOrder', [ReceivableAccountController::class, 'syncOrder']);
        Route::post('/exportFinance', [ReceivableAccountController::class, 'exportFinance']);
    });

    Route::prefix('test')->group(function () {
        Route::get('/getList', [\App\Http\Controllers\Admin\TestController::class, 'getList']);
    });

    Route::prefix('maintain')->group(function () {
        Route::post('/placeList', [MaintainController::class, 'placeList']);
        Route::post('/installationCheckList', [MaintainController::class, 'installationCheckList']);
        Route::post('/getPlaceInfo', [MaintainController::class, 'getPlaceInfo']);
        Route::post('/getRemarkInfo', [MaintainController::class, 'getRemarkInfo']);
        Route::post('/updatePlace', [MaintainController::class, 'updatePlace']);
        Route::post('/setRemark', [MaintainController::class, 'setRemark']);
        Route::post('/noDataList', [MaintainController::class, 'noDataList']);
        Route::post('/importList', [MaintainController::class, 'importList']);
        Route::post('/importDevice', [MaintainController::class, 'importDevice']);
    });

    Route::prefix('preInstallation')->group(function () {
        Route::post('/getList', [PreInstallationController::class, 'getList']);
        Route::post('/getInfo', [PreInstallationController::class, 'getInfo']);
        Route::post('/delete', [PreInstallationController::class, 'delete']);
        Route::post('/update', [PreInstallationController::class, 'addOrUpdate']);
    });

    Route::prefix('report')->group(function () {
        Route::post('/online', [ReportController::class, 'online']);
    });

    Route::prefix('approval')->group(function () {
        Route::post('/getList', [ApprovalController::class, 'getList']);
        Route::post('/getInfo', [ApprovalController::class, 'getInfo']);
        Route::post('/updateInfo', [ApprovalController::class, 'updateInfo']);
        Route::post('/agree', [ApprovalController::class, 'agree']);
        Route::post('/reject', [ApprovalController::class, 'reject']);
        Route::post('/cancel', [ApprovalController::class, 'cancel']);
    });
});
