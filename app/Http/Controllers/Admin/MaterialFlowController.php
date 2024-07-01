<?php

namespace App\Http\Controllers\Admin;

use App\Http\Logic\MaterialFlowLogic;
use App\Http\Logic\ResponseLogic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MaterialFlowController
{
    public function view()
    {
        return view('admin.materialFlow');
    }

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

        $res = MaterialFlowLogic::getInstance()->getList($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function inComing(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'material_id' => 'required',
            'number' => 'required',
            'date' => 'required',
            'production_date' => 'required',
            'expire_date' => 'required',
        ],[
            'material_id.required' => '物品不得为空',
            'number.required' => '数量不得为空',
            'date.required' => '入库日期不得为空',
            'production_date.required' => '生产日期不得为空',
            'expire_date.required' => '质保期不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = MaterialFlowLogic::getInstance()->inComing($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function outComing(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'material_id' => 'required',
            'number' => 'required',
            'date' => 'required',
            'purpose' => 'required',
            'receive_user_id' => 'required',
        ],[
            'material_id.required' => '物品不得为空',
            'number.required' => '数量不得为空',
            'date.required' => '出库日期不得为空',
            'purpose.required' => '用途不得为空',
            'receive_user_id.required' => '领用人不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = MaterialFlowLogic::getInstance()->outComing($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }
}
