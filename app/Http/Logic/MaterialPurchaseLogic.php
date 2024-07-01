<?php

namespace App\Http\Logic;

use App\Models\MaterialPurchase;
use App\Models\MaterialPurchaseDetail;

class MaterialPurchaseLogic extends BaseLogic
{
    public function getList($params)
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 10;
        $point = ($page - 1) * $pageSize;

        $query = MaterialPurchase::query()
        ;

        if(isset($params['start_date']) && $params['start_date']){
            $query->where('mapu_crt_time','>=',$params['start_date']);
        }

        if(isset($params['end_date']) && $params['end_date']){
            $query->where('mapu_crt_time','<=',$params['end_date']);
        }

        $total = $query->count();

        $list = $query
            ->orderBy('mapu_id','desc')
            ->offset($point)->limit($pageSize)->get()->toArray();

        $ids = array_column($list,'mapu_id');

        $detailArr = MaterialPurchaseDetail::query()
            ->leftJoin('material','material.mate_id','=','material_purchase_detail.mapu_material_id')
            ->whereIn('mapu_pid',$ids)
            ->select([
                'material_purchase_detail.mapu_id',
                'material.mate_name as mapu_material_name',
                'material_purchase_detail.mapu_number',
                'material_purchase_detail.mapu_remain',
            ])
            ->get()->groupBy('pid')->toArray();

        foreach ($list as $key => &$value){
            if(isset($detailArr[$value['id']])){
                $value['detail'] = $detailArr[$value['id']];
            }else{
                $value['detail'] = [];
            }
        }

        unset($value);

        return [
            'total' => $total,
            'list' => $list,
        ];
    }
}
