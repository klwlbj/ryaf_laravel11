<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Logic\ResponseLogic;
use App\Http\Logic\AdvancedOrderLogic;
use Illuminate\Support\Facades\Validator;

class AdvancedOrderController
{
    public function getList(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, []);

        if ($validate->fails()) {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = AdvancedOrderLogic::getInstance()->getList($params);
        if ($res === false) {
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0, 'ok', $res);
    }

    public function getInfo(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'id' => 'required',
        ]);

        if ($validate->fails()) {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = AdvancedOrderLogic::getInstance()->getInfo($params['id']);
        if ($res === false) {
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0, 'ok', $res);
    }

    public function getLinkInfo(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'id' => 'required',
        ]);

        if ($validate->fails()) {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = AdvancedOrderLogic::getInstance()->getLinkInfo($params['id']);
        // $res = ['detail' => [13412,123414]];
        if ($res === false) {
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0, 'ok', $res);
    }

    public function link(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [

        ]);

        if ($validate->fails()) {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = AdvancedOrderLogic::getInstance()->linkOrder($params, $params['id']);
        if ($res === false) {
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0, 'ok', $res);
    }

    public function add(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'name'                     => 'required',
            'address'                  => 'required',
            'phone'                    => 'required',
            'advanced_amount'          => 'required|numeric',
            'advanced_total_installed' => 'required|int',
            'payment_type'             => 'required|int',
            'pay_way'                  => 'required|int',
        ]);

        if ($validate->fails()) {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = AdvancedOrderLogic::getInstance()->addOrUpdate($params);
        if ($res === false) {
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0, 'ok', $res);
    }

    public function update(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'id'                       => 'required|string|exists:advanced_order,ador_id',
            'name'                     => 'required',
            'address'                  => 'required',
            'phone'                    => 'required',
            'advanced_amount'          => 'required|numeric',
            'advanced_total_installed' => 'required|int',
            'payment_type'             => 'required|int',
            'pay_way'                  => 'required|int',
        ]);

        if ($validate->fails()) {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = AdvancedOrderLogic::getInstance()->addOrUpdate($params, $params['id']);
        if ($res === false) {
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0, 'ok', $res);
    }

    public function delete(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'id' => 'required',
        ]);

        if ($validate->fails()) {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = AdvancedOrderLogic::getInstance()->delete($params);
        if ($res === false) {
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0, 'ok', $res);
    }
}
