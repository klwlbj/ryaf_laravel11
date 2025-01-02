<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class MaterialFlow extends BaseModel
{
    protected $table   = 'material_flow';
    public $timestamps = null;

    public static $invoiceTypeArr = [
        '0' => '未确认',
        '1' => '专票',
        '2' => '普票',
    ];

    public static function getLastFlow($ids = [])
    {
        $query = Material::query()
            ->leftJoin('material_flow','material.mate_id','=','material_flow.mafl_material_id');

        if(!empty($ids)){
            $query->whereIn('material.mate_id',$ids);
        }

        return $query->select([
            'mate_id',
            DB::raw("SUBSTRING_INDEX(GROUP_CONCAT(mafl_id ORDER BY mafl_id DESC), ',', 1) AS last_flow_id")
        ])->groupBy(['mate_id'])->pluck('last_flow_id','mate_id')->toArray();

    }
}
