<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Logic\AdvancedOrderLogic;

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
        return $this->baseMethod($request, ['id' => 'required']);
    }

    public function getLinkInfo(Request $request)
    {
        return $this->baseMethod($request, ['id' => 'required']);
    }

    public function link(Request $request)
    {
        return $this->baseMethod($request, [
            'advanced_id'     => 'required',
            'receivable_id' => 'required',
        ]);
    }

    public function add(Request $request)
    {
        return $this->baseMethod($request, [
            'funds_received' => 'required',
            'node_id' => 'required',
        ], 'addOrUpdate');
    }

    public function update(Request $request)
    {
        return $this->baseMethod($request, [
            'id'             => 'required|string|exists:advanced_order,ador_id',
            'funds_received' => 'required',
            'node_id' => 'required',
        ], 'addOrUpdate');
    }

    public function delete(Request $request)
    {
        return $this->baseMethod($request, ['id' => 'required']);
    }
}
