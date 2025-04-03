<?php

namespace App\Http\Middleware;

use App\Http\Logic\AuthLogic;
use App\Http\Logic\ResponseLogic;
use App\Http\Logic\ToolsLogic;
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
            if(empty($token)){
                $token = $_COOKIE['X-Token'] ?? '';
            }

        }else{
            $token = $_COOKIE['X-Token'] ?? '';
        }


        $userInfo = Cache::get($token);

        if(empty($userInfo)){
            if($request->ajax()){
                return ResponseLogic::apiNoLoginResult();
            }else{
                return redirect('/login');
            }
        }

        $currentToken = Cache::get('admin_' . $userInfo['admin_id'] . '_token');
        #查询是否被其他账号登录
        if(!empty($currentToken) && $currentToken != $token){
            if($request->ajax()){
                return ResponseLogic::apiOtherLogin();
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
