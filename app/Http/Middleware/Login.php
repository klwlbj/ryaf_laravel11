<?php

namespace App\Http\Middleware;

use App\Http\Logic\AuthLogic;
use App\Http\Logic\ResponseLogic;
use App\Http\Logic\ToolsLogic;
use App\Http\Server\Hikvision\Response;
use App\Models\Admin;
use App\Models\NodeAccount;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;

class Login
{
    public function handle($request, Closure $next)
    {
        if($request->ajax() || in_array($request->getMethod(),['POST'])){
            $token = $request->header('X-Token');
        }else{
            $token = $_COOKIE['X-Token'] ?? '';
        }
//        print_r($token);die;
        $userInfo = Cache::get($token);

        if(empty($userInfo)){
            if($request->ajax()){
                return ResponseLogic::apiNoLoginResult();
            }else{
                return redirect('/login');
            }
        }

        if(!AuthLogic::checkPermission($userInfo['admin_id'])){
            return ResponseLogic::apiErrorResult('没有权限访问');
        }

        view()->share('adminInfo',$userInfo);

        AuthLogic::$userId = $userInfo['admin_id'];

        return $next($request);


    }
}
