<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Logic\ResponseLogic;
use App\Http\Logic\OtherOrderLogic;
use Illuminate\Support\Facades\Validator;

class OtherOrderController extends BaseController
{
    protected function commonInitialization(): void
    {
        $this->logicClass = OtherOrderLogic::getInstance();
    }

    public function getInfo(Request $request)
    {
        return $this->baseMethod($request, ['id' => 'required']);
    }

    public function add(Request $request)
    {
        return $this->baseMethod($request,[
            'order_user_name'            => 'required',
            'order_address'              => 'required',
            'order_phone'                => 'required',
            'order_prospecter_date'      => 'required|date',
            'order_actual_delivery_date' => 'required|date',
            'order_account_receivable'   => 'required|numeric',
            'security_deposit_funds'     => 'required|numeric',
            'order_funds_received'       => 'required|numeric',
            'order_delivery_number'      => 'required|int',
            'order_project_type'         => 'required|int',
            'order_pay_cycle'            => 'required|int',
            'order_pay_way'              => 'required|int',
        ], 'addOrUpdate');
    }

    public function update(Request $request)
    {
        return $this->baseMethod($request,[
            'id'                         => 'required|string|exists:other_order,order_id',
            'order_user_name'            => 'required',
            'order_address'              => 'required',
            'order_phone'                => 'required',
            'order_prospecter_date'      => 'required|date',
            'order_actual_delivery_date' => 'required|date',
            'security_deposit_funds'     => 'required|numeric',
            'order_account_receivable'   => 'required|numeric',
            'order_funds_received'       => 'required|numeric',
            'order_delivery_number'      => 'required|int',
            'order_project_type'         => 'required|int',
            'order_pay_cycle'            => 'required|int',
            'order_pay_way'              => 'required|int',
        ], 'addOrUpdate');
    }

    public function delete(Request $request)
    {
        return $this->baseMethod($request, ['id' => 'required']);
    }
}
