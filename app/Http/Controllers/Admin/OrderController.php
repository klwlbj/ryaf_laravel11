<?php

namespace App\Http\Controllers\Admin;

use App\Http\Logic\MaterialLogic;
use App\Http\Logic\OrderLogic;
use App\Http\Logic\ResponseLogic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController
{
    public function getList(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [

        ],[

        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = OrderLogic::getInstance()->getList($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }
}
