<?php

namespace App\Http\Controllers\Admin;

use App\Http\Logic\MaterialPurchaseLogic;
use App\Http\Logic\ResponseLogic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MaterialPurchaseController
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

        $res = MaterialPurchaseLogic::getInstance()->getList($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function getInfo(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [

        ],[

        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = MaterialPurchaseLogic::getInstance()->getInfo($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function add(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'detail' => 'required',
        ],[
            'detail.required' => '申购详情不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = MaterialPurchaseLogic::getInstance()->add($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function update(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'id' => 'required',
            'detail' => 'required',
        ],[
            'id.required' => 'id不得为空',
            'detail.required' => '申购详情不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = MaterialPurchaseLogic::getInstance()->update($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function delete(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'id' => 'required',
        ],[
            'id.required' => 'id不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = MaterialPurchaseLogic::getInstance()->delete($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function complete(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'id' => 'required',
        ],[
            'id.required' => 'id不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = MaterialPurchaseLogic::getInstance()->complete($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }
}
