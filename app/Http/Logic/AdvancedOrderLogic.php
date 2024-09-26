<?php

namespace App\Http\Logic;

use App\Models\Order;
use App\Models\AdvancedOrder;
use App\Http\Logic\Excel\ExcelGenerator;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class AdvancedOrderLogic extends ExcelGenerator
{
    public string $exportTitle = '预付订单管理导出excel';

    public bool $openLastRowTotal = false;

    public bool $lockFirstRow = true;

    /**
     * 获取可导出字段
     *
     * @return array
     */
    public function getExportColumns(): array
    {
        return [
            [
                "name"  => 'Id',
                "index" => 'ador_id',
                "type"  => DataType::TYPE_STRING,
                "width" => 20,
            ],
            [
                "name"  => '区',
                "index" => 'district_name',
                "width" => 30,
            ],
            [
                "name"  => '街道',
                "index" => 'street_name',
                "width" => 30,
            ],
            [
                "name"  => '村委/经济联社/社区',
                "index" => 'community_name',
                "width" => 30,
            ],
            [
                "name"      => '详细地址',
                "index"     => 'address',
                "width"     => 50,
                "wrap_text" => true,
            ],
            [
                "name"  => '单位/用户名称',
                "index" => 'name',
                "width" => 40,
            ],
            [
                "name"              => '联系方式',
                "index"             => 'phone',
                "horizontal_center" => self::FIRST_ROW,
                "type"              => DataType::TYPE_STRING,
                "bold"              => self::FIRST_ROW,
                "width"             => 30,
            ],
            [
                "name"  => '客户类型',
                "index" => 'customer_type_name',
                "width" => 20,
            ],
            [
                "name"  => '预计安装总数',
                "index" => 'advanced_total_installed',
                "width" => 30,
            ],
            [
                "name"  => '预付金额（元）',
                "index" => 'advanced_amount',
                "type"  => DataType::TYPE_NUMERIC,
                "width" => 20,
            ],
            [
                "name"  => '付款方案',
                "index" => 'payment_type_name',
            ],
            [
                "name"  => '收款方式',
                "index" => 'pay_way_name',
            ],
            [
                "name"  => '备注',
                "index" => 'remark',
            ],
        ];
    }

    public function getList($params)
    {
        $page     = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 10;
        $offset   = ($page - 1) * $pageSize;

        $query = AdvancedOrder::query();

        $queryConditions = ['name', 'street_id', 'address', 'phone', 'customer_type', 'payment_type', 'pay_way', 'remark'];

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
                    if (!empty($params['street_id_0'])) {
                        $query->where('area_id', 'like', $params['street_id_0'] . '%');
                    }
                    if (!empty($params['street_id_1'])) {
                        $query->where('area_id', 'like', $params['street_id_1'] . '%');
                    }
                    if (!empty($params['street_id_2'])) {
                        $query->where('area_id', $params['street_id_2']);
                    }
                    break;
                case 'customer_type':
                case 'payment_type':
                case 'pay_way':
                    if (!empty($params[$queryCondition])) {
                        $query->where($queryCondition, $params[$queryCondition]);
                    }
                    break;
            }
        }

        if (!empty($params['start_date'])) {
            $query->where('created_at', '>=', $params['start_date']);
        }

        if (!empty($params['end_date'])) {
            $query->where('created_at', '<=', $params['end_date']);
        }

        $total = $query->count();

        $list = $query->with(['area', 'area.parentArea', 'area.parentArea.parentArea'])
            ->when(!isset($params['export']), function ($query) use ($offset, $pageSize) {
                return $query->orderBy('created_at', 'desc')
                ->offset($offset)->limit($pageSize)
                ->get()
                ->map(function ($item) {
                    return $this->handleRow($item);
                });
            });

        if (isset($params['export'])) {
            $list = Order::getCursorSortById($list);
            return $this->export($list, $params, $total);
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
            ->where(['ador_id' => $params['id']])
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

    public function getLinkInfo($params)
    {
        $data = Order::query()->where('advanced_order_id', $params['id'])->pluck('order_iid');
        if (!$data) {
            ResponseLogic::setMsg('记录不存在');
            return false;
        }

        return ['detail' => $data];
    }

    public function addOrUpdate($params)
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
            'pay_way'                  => $params['pay_way'] ?? '',
            'operator_user_id'         => AuthLogic::$userId ?? 0,
        ];

        $res = isset($params['id']) ? AdvancedOrder::where(['ador_id' => $params['id']])->update($insertData) : AdvancedOrder::insert($insertData);
        if ($res === false) {
            ResponseLogic::setMsg('添加或更新失败');
            return false;
        }

        return ['id' => $res];
    }

    public function linkOrder($params)
    {
        $arrayData = json_decode($params['detail'], true); // 将 JSON 数据转换为 PHP 数组
        $detail    = collect($arrayData); // 将数组转换为集合
        $res       = Order::whereIn('order_iid', $detail->pluck('orderId'))->update([
            'advanced_order_id' => $params['id'],
        ]);
        if ($res === false) {
            ResponseLogic::setMsg('更新失败');
            return false;
        }

        return ['id' => $res];
    }

    public function delete($params)
    {
        AdvancedOrder::where(['ador_id' => $params['id']])->delete();
        return [];
    }

     protected function handleRow($item, $params = [])
     {
         $item->pay_way_name       = AdvancedOrder::$formatPayWayMaps[$item->pay_way] ?? '';
         $item->customer_type_name = AdvancedOrder::$formatCustomerTypeMaps[$item->customer_type] ?? '';
         $item->payment_type_name  = AdvancedOrder::$formatPaymentTypeMaps[$item->payment_type] ?? '';

         $area                 = $item->area;
         $item->community_name = $area?->name;
         // 访问上级
         $parentArea        = $area?->parentArea;
         $item->street_name = $parentArea?->name;
         // 访问上上级
         $grandParentArea     = $parentArea?->parentArea;
         $item->district_name = $grandParentArea?->name;
         return $item;
     }

     protected function handleLastRow($sheet, int $lastRow, array $lastRowTotal = [])
     {
     }
}
