<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Logic\ResponseLogic;
use App\Http\Logic\OtherOrderLogic;
use Illuminate\Support\Facades\Validator;

class OtherOrderController
{
    public function getInfo(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'id' => 'required',
        ]);

        if ($validate->fails()) {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = OtherOrderLogic::getInstance()->getInfo($params['id']);
        if ($res === false) {
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0, 'ok', $res);
    }

    public function add(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'order_user_name'            => 'required',
            'order_address'              => 'required',
            'order_phone'                => 'required',
            'order_prospecter_date'      => 'required|date',
            'order_actual_delivery_date' => 'required|date',
            'order_account_receivable'   => 'required|numeric',
            'security_deposit_funds'   => 'required|numeric',
            'order_funds_received'       => 'required|numeric',
            'order_delivery_number'      => 'required|int',
            'order_project_type'         => 'required|int',
            'order_pay_cycle'            => 'required|int',
            'order_pay_way'              => 'required|int',
        ]);

        if ($validate->fails()) {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = OtherOrderLogic::getInstance()->addOrUpdate($params);
        if ($res === false) {
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0, 'ok', $res);
    }

    public function update(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'id'                         => 'required|string|exists:other_order,order_id',
            'order_user_name'            => 'required',
            'order_address'              => 'required',
            'order_phone'                => 'required',
            'order_prospecter_date'      => 'required|date',
            'order_actual_delivery_date' => 'required|date',
            'security_deposit_funds'   => 'required|numeric',
            'order_account_receivable'   => 'required|numeric',
            'order_funds_received'       => 'required|numeric',
            'order_delivery_number'      => 'required|int',
            'order_project_type'         => 'required|int',
            'order_pay_cycle'            => 'required|int',
            'order_pay_way'              => 'required|int',
        ]);

        if ($validate->fails()) {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = OtherOrderLogic::getInstance()->addOrUpdate($params, $params['id']);
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

        $res = OtherOrderLogic::getInstance()->delete($params);
        if ($res === false) {
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0, 'ok', $res);
    }
}
