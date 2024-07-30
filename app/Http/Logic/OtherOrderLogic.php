<?php

namespace App\Http\Logic;

use App\Models\OtherOrder;

class OtherOrderLogic extends BaseLogic
{
    public function getInfo($params)
    {
        $data = OtherOrder::query()
            ->with(['area', 'area.parentArea', 'area.parentArea.parentArea'])
            ->where(['order_id' => $params['id']])
            ->first();

        $area                 = $data->area;
        $data->community_id   = $area?->id;
        $data->community_name = $area?->name;
        // 访问上级
        $parentArea        = $area?->parentArea;
        $data->street_id   = $parentArea?->id;
        $data->street_name = $parentArea?->name;
        // 访问上上级
        $grandParentArea     = $parentArea?->parentArea;
        $data->district_id   = $grandParentArea?->id;
        $data->district_name = $grandParentArea?->name;

        $data->area_all_id = [$data->district_id, $data->street_id, $data->community_id];

        if (!$data) {
            ResponseLogic::setMsg('记录不存在');
            return false;
        }

        return $data;
    }

    public function addOrUpdate($params)
    {
        $insertData = [
            'order_area_id'              => $params['order_area_id_2'],
            'order_address'              => $params['order_address'] ?? '',
            'order_user_name'            => $params['order_user_name'] ?? '',
            'order_phone'                => $params['order_phone'] ?? '',
            'order_remark'               => $params['order_remark'] ?? '',
            'order_actual_delivery_date' => $params['order_actual_delivery_date'] ?? '',
            'order_prospecter_date'      => $params['order_prospecter_date'] ?? '',
            'order_delivery_number'      => $params['order_delivery_number'] ?? 0,
            'order_project_type'         => $params['order_project_type'] ?? 0,
            'order_pay_cycle'            => $params['order_pay_cycle'] ?? 0,
            'order_pay_way'              => $params['order_pay_way'] ?? 0,
            'order_account_receivable'   => $params['order_account_receivable'] ?? 0,
            'order_funds_received'       => $params['order_funds_received'] ?? 0,
            'security_deposit_funds'       => $params['security_deposit_funds'] ?? 0,
            'order_contract_type'        => $params['order_contract_type'] ?? 0,
            'order_operator_user_id'     => AuthLogic::$userId ?? 0,
        ];

        if (!isset($params['id'])) {
            $insertData['order_iid'] = date('YmdHis') . rand(1, 9);
        }

        $res = isset($params['id']) ? OtherOrder::where(['order_id' => $params['id']])->update($insertData) : OtherOrder::insert($insertData);
        if ($res === false) {
            ResponseLogic::setMsg('添加或更新失败');
            return false;
        }

        return ['id' => $res];
    }

    public function delete($params)
    {
        OtherOrder::where(['order_id' => $params['id']])->delete();
        return [];
    }
}
