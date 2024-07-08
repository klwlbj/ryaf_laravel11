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

        $query = MaterialPurchase::query();

        if (isset($params['start_date']) && $params['start_date']) {
            $query->where('mapu_crt_time', '>=', $params['start_date']);
        }

        if (isset($params['end_date']) && $params['end_date']) {
            $query->where('mapu_crt_time', '<=', $params['end_date']);
        }

        if (isset($params['material_id']) && $params['material_id']) {
            $ids = MaterialPurchaseDetail::query()->where(['mapu_material_id' => $params['material_id']])->pluck('mapu_pid')->toArray();
            $query->whereIn('mapu_id', $ids);
        }

        $total = $query->count();

        $list = $query
            ->orderBy('mapu_id', 'desc')
            ->offset($point)->limit($pageSize)->get()->toArray();

        $ids = array_column($list, 'mapu_id');

        $detailArr = MaterialPurchaseDetail::query()
            ->leftJoin('material', 'material.mate_id', '=', 'material_purchase_detail.mapu_material_id')
            ->whereIn('mapu_pid', $ids)
            ->select([
                'material_purchase_detail.mapu_id',
                'material_purchase_detail.mapu_pid',
                'material.mate_name as mapu_material_name',
                'material.mate_unit as mapu_unit',
                'material_purchase_detail.mapu_number',
                'material_purchase_detail.mapu_remain',
            ])
            ->get()->groupBy('mapu_pid')->toArray();

        foreach ($list as $key => &$value) {
            if (isset($detailArr[$value['mapu_id']])) {
                $value['detail'] = $detailArr[$value['mapu_id']];
            } else {
                $value['detail'] = [];
            }

            $value['complete_auth'] = ($value['mapu_status'] == 2) ? true : false;
        }

        unset($value);

        return [
            'total' => $total,
            'list' => $list,
        ];
    }

    public function getInfo($params)
    {
        $data = MaterialPurchase::query()->where(['mapu_id' => $params['id']])->first();
        if(!$data){
            ResponseLogic::setMsg('数据不存在');
            return false;
        }

        $data = $data->toArray();

        $detail = MaterialPurchaseDetail::query()->where(['mapu_pid' => $data['mapu_id']])->get()->toArray();
        $data['detail'] = $detail;
        return $data;
    }

    public function add($params)
    {
        $detail = ToolsLogic::jsonDecode($params['detail']);

        if (empty($detail)) {
            ResponseLogic::setMsg('申报详情格式有误');
            return false;
        }

        $insertData = [
            'mapu_status' => 1,
            'mapu_remark' => $params['remark'] ?? '',
            'mapu_operator_id' => AuthLogic::$userId
        ];

        $id = MaterialPurchase::query()->insertGetId($insertData);

        $realDetail = [];

        foreach ($detail as $key => $value) {
            if(!isset($value['number']) || empty($value['number'])){
                continue;
            }

            $realDetail[] = [
                'mapu_pid' => $id,
                'mapu_material_id' => $value['id'],
                'mapu_number' => $value['number'],
                'mapu_remain' => $value['remain']
            ];
        }

        MaterialPurchaseDetail::query()->insert($realDetail);

        return ['id' => $id];
    }

    public function update($params)
    {
        $data = MaterialPurchase::getDataById($params['id']);
        if(!$data){
            ResponseLogic::setMsg('数据不存在');
            return false;
        }

        if($data['mapu_status'] != 1){
            ResponseLogic::setMsg('申购记录不为待审批状态，不能修改');
            return false;
        }

        $detail = ToolsLogic::jsonDecode($params['detail']);

        if (empty($detail)) {
            ResponseLogic::setMsg('申报详情格式有误');
            return false;
        }

        $insertData = [
            'mapu_status' => 1,
            'mapu_remark' => $params['remark'] ?? '',
            'mapu_operator_id' => AuthLogic::$userId
        ];

        MaterialPurchase::query()->where(['mapu_id' => $params['id']])->update($insertData);

        $realDetail = [];

        # 把该id下详情数据全删全插

        foreach ($detail as $key => $value) {
            if(!isset($value['number']) || empty($value['number'])){
                continue;
            }

            $realDetail[] = [
                'mapu_pid' => $params['id'],
                'mapu_material_id' => $value['id'],
                'mapu_number' => $value['number'],
                'mapu_remain' => $value['remain']
            ];
        }
        MaterialPurchaseDetail::query()->where(['mapu_pid' => $params['id']])->delete();
        MaterialPurchaseDetail::query()->insert($realDetail);
        MaterialPurchase::delCacheById($params['id']);

        return ['id' => $params['id']];
    }

    public function delete($params)
    {
        $data = MaterialPurchase::getDataById($params['id']);
        if(!$data){
            ResponseLogic::setMsg('数据不存在');
            return false;
        }

        if($data['mapu_status'] != 1){
            ResponseLogic::setMsg('申购记录不为待审批状态，不能删除');
            return false;
        }

        MaterialPurchaseDetail::query()->where(['mapu_pid' => $params['id']])->delete();
        MaterialPurchase::query()->where(['mapu_id' => $params['id']])->delete();
        MaterialPurchase::delCacheById($params['id']);
        return [];
    }

    /**驳回
     * @param $params
     * @return false|array
     */
    public function complete($params): false|array
    {
        $data = MaterialPurchase::getDataById($params['id']);
        if(!$data){
            ResponseLogic::setMsg('数据不存在');
            return false;
        }

        if($data['mapu_status'] != 2){
            ResponseLogic::setMsg('申购记录不为申购中状态，不能完成');
            return false;
        }

        MaterialPurchase::query()->where(['mapu_id' => $params['id']])->update([
            'mapu_operator_id' => AuthLogic::$userId,
            'mapu_status' => 2,
        ]);
        MaterialPurchase::delCacheById($params['id']);
        return [];
    }
}
