<?php

namespace App\Http\Middleware;

use App\Http\Logic\ResponseLogic;
use Closure;

class CheckIp
{
    public function handle($request, Closure $next)
    {
        $clientIp = $request->ip();

        $serverName = gethostname(); // 获取当前主机名
        $localIP = gethostbyname($serverName);

        $localIps = ['127.0.0.1','47.104.10.228','113.111.6.40', $localIP];

        if(!in_array($clientIp,$localIps)){
            return ResponseLogic::apiResult(403 ,'ip有误' ,[]);
        }
        return $next($request);
    }
}
