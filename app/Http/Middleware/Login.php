<?php

namespace App\Http\Middleware;

use App\Http\Logic\AuthLogic;
use App\Http\Logic\ResponseLogic;
use App\Http\Server\Hikvision\Response;
use Closure;

class Login
{
    public function handle($request, Closure $next)
    {
        $token = $request->get('token');
        if(!empty($token)){
            view()->share('token',$token);
        }else{
            if($request->ajax()){
                $token = $request->header('X-Token');
            }else{
                $token = $_COOKIE['X-Token'] ?? '';
            }
        }

        #目前先写死token属于用户2
        if($token == 'abcdefg'){
            $userId = 2;
        }


        if(empty($userId)){
            if($request->ajax()){
                return ResponseLogic::apiNoLoginResult();
            }else{
                return response('https://pingansuiyue.crzfxjzn.com/node/login.php', 302)->header('Location', 'https://pingansuiyue.crzfxjzn.com/node/login.php');
            }

        }

        AuthLogic::$userId = $userId;

        return $next($request);


    }
}
