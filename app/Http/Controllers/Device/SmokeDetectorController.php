<?php

namespace App\Http\Controllers\Device;

use App\Http\Logic\Device\SmokeDetectorLogic;
use App\Http\Logic\ResponseLogic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SmokeDetectorController
{
    public function getOneNetCommand(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'imei' => 'required',
        ],[
            'imei.required' => 'imei 不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = SmokeDetectorLogic::getInstance()->getOneNetCommand($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }
}
