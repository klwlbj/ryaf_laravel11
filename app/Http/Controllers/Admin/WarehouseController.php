<?php

namespace App\Http\Controllers\Admin;

use App\Http\Logic\MaterialLogic;
use App\Http\Logic\ResponseLogic;
use App\Http\Logic\WarehouseLogic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WarehouseController
{
    public function getAllList(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [

        ],[

        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = WarehouseLogic::getInstance()->getAllList($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }
}
