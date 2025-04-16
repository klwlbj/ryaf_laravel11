<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));
ini_set( "expose_php", "Off" );
header('X-Frame-Options: SAMEORIGIN');
//header( "Content-Security-Policy: default-src 'self' 'unsafe-inline' 'unsafe-eval' blob: data: https://*.is.autonavi.com https://*.amap.com https://cache.amap.com https://webapi.amap.com https://a.amap.com https://webapi.amap.com https://restapi.amap.com https://vdata.amap.com https://cdn.jsdelivr.net https://cstaticdun.126.net https://c.dun.163.com;" );
// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
