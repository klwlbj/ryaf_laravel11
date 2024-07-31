<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Logic\ResponseLogic;
use App\Http\Logic\AdvancedOrderLogic;
use Illuminate\Support\Facades\Validator;

class AdvancedOrderController extends BaseController
{
    protected function commonInitialization(): void
    {
        $this->logicClass = AdvancedOrderLogic::getInstance();
    }

    public function getList(Request $request)
    {
        return $this->baseMethod($request, []);
    }

    public function getInfo(Request $request)
    {
        return $this->baseMethod($request, ['id' => 'required',]);
    }

    public function getLinkInfo(Request $request)
    {
        return $this->baseMethod($request, ['id' => 'required',]);
    }

    public function link(Request $request)
    {
        return $this->baseMethod($request,[
            'id' => 'required',
            'detail' => 'required',
        ]);
    }

    public function add(Request $request)
    {
        return $this->baseMethod($request,[
            'name'                     => 'required',
            'address'                  => 'required',
            'phone'                    => 'required',
            'advanced_amount'          => 'required|numeric',
            'advanced_total_installed' => 'required|int',
            'payment_type'             => 'required|int',
            'pay_way'                  => 'required|int',
        ], 'addOrUpdate');
    }

    public function update(Request $request)
    {
        return $this->baseMethod($request,[
            'id'                       => 'required|string|exists:advanced_order,ador_id',
            'name'                     => 'required',
            'address'                  => 'required',
            'phone'                    => 'required',
            'advanced_amount'          => 'required|numeric',
            'advanced_total_installed' => 'required|int',
            'payment_type'             => 'required|int',
            'pay_way'                  => 'required|int',
        ], 'addOrUpdate');
    }

    public function delete(Request $request)
    {
        return $this->baseMethod($request, ['id' => 'required',]);
    }
}
