<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Logic\ResponseLogic;
use App\Http\Logic\FinancialIncomeLogic;
use Illuminate\Support\Facades\Validator;

class FinancialIncomeController
{
    public function getList(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, []);

        if ($validate->fails()) {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = FinancialIncomeLogic::getInstance()->getList($params);
        if ($res === false) {
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0, 'ok', $res);
    }

    public function getStageInfo(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'id' => 'required',
        ]);

        if ($validate->fails()) {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = FinancialIncomeLogic::getInstance()->getStageInfo($params);
        if ($res === false) {
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0, 'ok', $res);
    }

    public function getArrearsInfo(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'id' => 'required',
        ]);

        if ($validate->fails()) {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $res = FinancialIncomeLogic::getInstance()->getArrearsInfo($params);
        if ($res === false) {
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0, 'ok', $res);
    }
}
