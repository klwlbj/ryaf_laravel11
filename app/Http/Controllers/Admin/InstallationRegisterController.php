<?php

namespace App\Http\Controllers\Admin;

use App\Http\Logic\InstallationRegisterLogic;
use App\Http\Logic\ResponseLogic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InstallationRegisterController
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

        $res = InstallationRegisterLogic::getInstance()->getList($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function add(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'datetime' => 'required',
            'node_id' => 'required',
            'address_list' => 'required',
            'user_name' => 'required',
            'user_phone' => 'required',
            'user_type' => 'required',
            'install_count' => 'required',
            'pay_way' => 'required',
        ],[
            'datetime.required' => '预装日期不得为空',
            'node_id.required' => '节点不得为空',
            'address_list.required' => '地址信息不得为空',
            'user_name.required' => '单位/用户名不得为空',
            'user_phone.required' => '联系方式不得为空',
            'user_type.required' => '客户类型不得为空',
            'install_count.required' => '预装日期不得为空',
            'pay_way.required' => '预装日期不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = InstallationRegisterLogic::getInstance()->add($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }
}
