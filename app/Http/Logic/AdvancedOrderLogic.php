<?php

namespace App\Http\Logic;

use App\Models\AdvancedOrder;

class AdvancedOrderLogic extends BaseLogic
{
    public function getList($params)
    {
        $page     = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 10;
        $point    = ($page - 1) * $pageSize;

        $query = AdvancedOrder::query();

        $queryConditions = ['name', 'street_id', 'address', 'phone', 'customer_type', 'payment_type', 'income_type', 'remark'];

        foreach ($queryConditions as $queryCondition) {
            switch ($queryCondition) {
                case 'address':
                case 'name':
                case 'remark':
                case 'phone':
                    if (!empty($params[$queryCondition])) {
                        $query->where($queryCondition, 'like', '%' . $params[$queryCondition] . '%');
                    }
                    break;
                case 'street_id':
                    if (!empty($params['street_id_2'])) {
                        $query->where($queryCondition, $params['street_id_2']);
                    }
                    break;
                case 'customer_type':
                case 'payment_type':
                case 'income_type':
                    if (!empty($params[$queryCondition])) {
                        $query->where($queryCondition, $params[$queryCondition]);
                    }
                    break;
            }
        }
        // if (!empty($params['keyword'])) {
        //     $query->where('name', 'like', '%' . $params['keyword'] . '%');
        // }

        $total = $query->count();

        $list = $query->with(['area', 'area.parentArea', 'area.parentArea.parentArea'])
            ->orderBy('created_at', 'desc')
            ->offset($point)->limit($pageSize)
            ->get()
            ->map(function ($item) {
                $item->income_type_name   = AdvancedOrder::$formatIncomeTypeMaps[$item->income_type] ?? '';
                $item->customer_type_name = AdvancedOrder::$formatCustomerTypeMaps[$item->customer_type] ?? '';
                $item->payment_type_name  = AdvancedOrder::$formatPaymentTypeMaps[$item->payment_type] ?? '';
                return $item;
            });

        foreach ($list as $item) {
            $area                 = $item->area;
            $item->community_name = $area?->name;
            // 访问上级
            $parentArea        = $area?->parentArea;
            $item->street_name = $parentArea?->name;
            // 访问上上级
            $grandParentArea     = $parentArea?->parentArea;
            $item->district_name = $grandParentArea?->name;
        }

        return [
            'total' => $total,
            'list'  => $list,
        ];
    }

    public function getInfo($params)
    {
        $data = AdvancedOrder::query()
            ->with(['area', 'area.parentArea', 'area.parentArea.parentArea'])
            ->where(['id' => $params['id']])
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

    public function addOrUpdate($params, $id = null)
    {
        $insertData = [
            'area_id'                  => $params['street_id_2'],
            'address'                  => $params['address'] ?? '',
            'name'                     => $params['name'] ?? '',
            'phone'                    => $params['phone'] ?? '',
            'remark'                   => $params['remark'] ?? '',
            'advanced_amount'          => $params['advanced_amount'] ?? '',
            'customer_type'            => $params['customer_type'] ?? '',
            'advanced_total_installed' => $params['advanced_total_installed'] ?? '',
            'payment_type'             => $params['payment_type'] ?? '',
            'income_type'              => $params['income_type'] ?? '',
            'operator_user_id'         => AuthLogic::$userId ?? 0,
        ];

        $res = isset($id) ? AdvancedOrder::where(['id' => $id])->update($insertData) : AdvancedOrder::insert($insertData);
        if ($res === false) {
            ResponseLogic::setMsg('添加或更新失败');
            return false;
        }

        return ['id' => $res];
    }

    public function delete($params)
    {
        AdvancedOrder::where(['id' => $params['id']])->delete();
        return [];
    }
}
