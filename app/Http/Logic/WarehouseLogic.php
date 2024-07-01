<?php

namespace App\Http\Logic;

use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class WarehouseLogic extends BaseLogic
{
    public function getAllList($params)
    {
        $query = Warehouse::query();

        return $query
            ->orderBy('waho_id','asc')
            ->get()->toArray();
    }
}
