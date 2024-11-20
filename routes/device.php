<?php

use App\Http\Controllers\Device\FireAlarmPanelController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;


Route::middleware(['checkIp'])->group(function () {
    Route::prefix('fireAlarmPanel')->group(function () {
        Route::any('/muffling', [FireAlarmPanelController::class, 'muffling']);
        Route::any('/setTime', [FireAlarmPanelController::class, 'setTime']);
        Route::any('/reset', [FireAlarmPanelController::class, 'reset']);
        Route::any('/setMode', [FireAlarmPanelController::class, 'setMode']);
        Route::any('/operateDetector', [FireAlarmPanelController::class, 'operateDetector']);
    });
});


