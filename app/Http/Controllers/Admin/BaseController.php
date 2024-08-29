<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Logic\ResponseLogic;
use Illuminate\Support\Facades\Validator;

abstract class BaseController
{
    public $logicClass = null;

    public function __construct()
    {
        $this->commonInitialization();
    }

    abstract protected function commonInitialization();

    /**
     * 基方法，减少代码冗余
     * @param Request $request
     * @param array $inputRules
     * @param $parentMethod
     * @return \Illuminate\Http\JsonResponse
     */
    public function baseMethod(Request $request, array $inputRules, $parentMethod = null): \Illuminate\Http\JsonResponse
    {
        $params = $request->all();
        $this->validateParams($params, $inputRules);

        $parentMethod = $parentMethod ?? (debug_backtrace()[1]['function'] ?? null);
        if (!isset($parentMethod)) {
            return ResponseLogic::apiErrorResult('方法不存在');
        }

        $res = $this->logicClass->{$parentMethod}($params);
        if ($res === false) {
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0, 'ok', $res);
    }

    protected function validateParams($params, $rules)
    {
        // 进行验证
        $validator = Validator::make($params, $rules);

        if ($validator->fails()) {
            return ResponseLogic::apiErrorResult($validator->errors()->first());
        }
    }
}
