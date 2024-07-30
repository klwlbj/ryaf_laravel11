<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Logic\ResponseLogic;
use App\Http\Logic\FinancialIncomeLogic;
use Illuminate\Support\Facades\Validator;

class FinancialIncomeController extends BaseController
{
    protected function commonInitialization(): void
    {
        $this->logicClass = FinancialIncomeLogic::getInstance();
    }
    public function getList(Request $request)
    {
        return $this->baseMethod($request, []);
    }

    public function getStageInfo(Request $request)
    {
        return $this->baseMethod($request, [
            'id'                 => 'required',
            'order_project_type' => 'required',
        ]);
    }

    public function getArrearsInfo(Request $request)
    {
        return $this->baseMethod($request, [
            'id'                 => 'required',
            'order_project_type' => 'required',
        ]);
    }

}
