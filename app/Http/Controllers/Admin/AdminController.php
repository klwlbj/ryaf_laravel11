<?php

namespace App\Http\Controllers\Admin;

use App\Http\Logic\AdminLogic;
use App\Http\Logic\ResponseLogic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminController
{
    public function login(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'mobile' => 'required',
            'password' => 'required',
        ],[
            'mobile.required' => '账号不得为空',
            'password.required' => '密码不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = AdminLogic::getInstance()->login($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function logout(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [

        ],[

        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = AdminLogic::getInstance()->logout($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function resetPassword(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'password' => 'required',
            'new_password' => 'required',
            'confirm_password' => 'required',
        ],[
            'password.required' => '原密码不得为空',
            'new_password.required' => '新密码不得为空',
            'confirm_password.required' => '确认密码不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = AdminLogic::getInstance()->resetPassword($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
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

        $res = AdminLogic::getInstance()->getList($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

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

        $res = AdminLogic::getInstance()->getAllList($params);
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

        $res = AdminLogic::getInstance()->getInfo($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function add(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'department_id' => 'required',
            'name' => 'required',
            'mobile' => 'required',
        ],[
            'department_id.required' => '部门不得为空',
            'name.required' => '成员名称不得为空',
            'mobile.required' => '手机号不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = AdminLogic::getInstance()->add($params);
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
            'department_id' => 'required',
            'name' => 'required',
            'mobile' => 'required',
        ],[
            'id.required' => 'id不得为空',
            'department_id.required' => '部门不得为空',
            'name.required' => '成员名称不得为空',
            'mobile.required' => '手机号不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = AdminLogic::getInstance()->update($params);
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

        $res = AdminLogic::getInstance()->delete($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }

    public function getBacklogCount(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [

        ],[

        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = AdminLogic::getInstance()->getBacklogCount($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',$res);
    }
}
