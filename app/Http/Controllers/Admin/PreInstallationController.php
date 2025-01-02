<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Logic\ToolsLogic;
use App\Models\PreInstallation;
use App\Http\Logic\ResponseLogic;
use App\Http\Logic\PreInstallationLogic;
use Illuminate\Support\Facades\Validator;

class PreInstallationController extends BaseController
{
    protected function commonInitialization(): void
    {
        $this->logicClass = PreInstallationLogic::getInstance();
    }

    public function getList(Request $request)
    {
        return $this->baseMethod($request, []);
    }

    public function add(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'phone'               => 'required',
            'name'                => 'required',
            'number'              => 'required|integer',
            'date'                => 'required|date',
        ], [
            'phone.required'                       => '手机号不得为空',
            'name.required'                        => '姓名不得为空',
            'number.required'                      => '安装数量不得为空',
            'date.required'                        => '安装日期不得为空',
        ]);

        if ($validate->fails()) {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }

        $addressList = ToolsLogic::jsonDecode($params['address_list']);
        if (empty($addressList[0]['code']) && $params['handwritten_address'] == '') {
            return ResponseLogic::apiErrorResult('地址不得为空');
        }

        $preInstallation                      = new PreInstallation();
        $preInstallation->phone               = $params['phone'];
        $preInstallation->name                = $params['name'];
        $preInstallation->installation_count  = $params['number'];
        $preInstallation->registration_date   = $params['date'];
        $preInstallation->handwritten_address = $params['handwritten_address'];
        $preInstallation->address             = $addressList[0]['standard_address'] ?? '';
        $preInstallation->address_code        = $addressList[0]['code'] ?? '';
        $preInstallation->ip_address          = $request->ip();

        $res = $preInstallation->save();
        if ($res === false) {
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0, 'ok', $res);
    }
}
