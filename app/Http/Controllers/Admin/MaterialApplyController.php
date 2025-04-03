<?php

namespace App\Http\Controllers\Admin;

use App\Http\Logic\MaterialApplyLogic;
use App\Http\Logic\MaterialPurchaseLogic;
use App\Http\Logic\ResponseLogic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MaterialApplyController
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

        $res = MaterialApplyLogic::getInstance()->getList($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function getSelectList(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [

        ],[

        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = MaterialApplyLogic::getInstance()->getSelectList($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function getRelationList(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [

        ],[

        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = MaterialApplyLogic::getInstance()->getRelationList($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function getInfo(Request $request)
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

        $res = MaterialApplyLogic::getInstance()->getInfo($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function getPreInfo(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'category_id' => 'required',
            'detail' => 'required',
            'purpose' => 'required',
        ],[
            'category_id.required' => '申购详情不得为空',
            'detail.required' => '申购详情不得为空',
            'purpose.required' => '销售用途不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = MaterialApplyLogic::getInstance()->getPreInfo($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function add(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'category_id' => 'required',
            'name' => 'required',
            'reason' => 'required',
            'detail' => 'required',
            'purpose' => 'required',
            'file_list' => 'required',
        ],[
            'category_id.required' => '申购类型不得为空',
            'name.required' => '申领类型不得为空',
            'reason.required' => '申领事由不得为空',
            'detail.required' => '申购详情不得为空',
            'purpose.required' => '销售用途不得为空',
            'file_list.required' => '附件列表不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = MaterialApplyLogic::getInstance()->add($params);
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
            'category_id' => 'required',
            'name' => 'required',
            'reason' => 'required',
            'detail' => 'required',
            'purpose' => 'required',
            'file_list' => 'required',
        ],[
            'id.required' => '申领名称不得为空',
            'category_id.required' => '申购类型不得为空',
            'name.required' => '申领名称不得为空',
            'reason.required' => '申领事由不得为空',
            'detail.required' => '申购详情不得为空',
            'purpose.required' => '销售用途不得为空',
            'file_list.required' => '附件列表不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = MaterialApplyLogic::getInstance()->update($params);
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
}
