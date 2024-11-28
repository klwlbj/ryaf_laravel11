<?php

namespace App\Http\Controllers\Admin;

use App\Http\Logic\ResponseLogic;
use App\Http\Logic\MaintainLogic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MaintainController
{
    public function __construct()
    {
        DB::setDefaultConnection('mysql2');
    }

    public function placeList(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [

        ],[

        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = MaintainLogic::getInstance()->placeList($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }
}
