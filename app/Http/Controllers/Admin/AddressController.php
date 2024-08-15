<?php

namespace App\Http\Controllers\Admin;

use App\Http\Logic\AddressLogic;
use App\Http\Logic\ResponseLogic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AddressController
{
    public function getStandardAddress(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'keyword' => 'required',
        ],[
            'keyword.required' => '关键字 不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = AddressLogic::getInstance()->getStandardAddress($params);

        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }

        return ResponseLogic::apiResult(0,'ok',$res);
    }
}
