<?php

namespace App\Http\Logic;

use App\Models\AdvancedRelation;
use App\Models\Node;
use App\Models\Order;
use App\Models\AdvancedOrder;
use App\Http\Logic\Excel\ExcelGenerator;
use App\Models\ReceivableAccount;
use App\Models\ReceivableAccountFlow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
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

        $query = AdvancedOrder::query()
            ->leftJoin('node', 'node.node_id', '=', 'advanced_order.ador_node_id');

        if(isset($params['user_keyword']) && !empty($params['user_keyword'])) {
            $query->where(function (Builder $q) use ($params) {
                $q->orWhere('reac_user_name', 'like', "%{$params['user_keyword']}%")
                    ->orWhere('reac_user_mobile', 'like', "%{$params['user_keyword']}%");
            });
        }

        if(isset($params['address']) && !empty($params['address'])) {
            $query->where('ador_address','like',"%" . $params['address'] . "%");
        }

        if(isset($params['node_id']) && !empty($params['node_id'])) {
            $nodeIds = Node::getNodeChild($params['node_id']);
            $query->whereIn('ador_node_id', $nodeIds);
        }

        if(isset($params['status']) && !empty($params['status'])) {
            $query->where('ador_status', $params['status']);
        }

        if (!empty($params['start_date'])) {
            $query->where('ador_pay_date', '>=', $params['start_date']);
        }

        if (!empty($params['end_date'])) {
            $query->where('ador_pay_date', '<=', $params['end_date']);
        }

        $total = $query->count();

        $list = $query
            ->select([
                'ador_id',
                'ador_sn',
                'node_name',
                'ador_installation_date',
                'ador_pay_date',
                'ador_user_name',
                'ador_user_phone',
                'ador_address',
                'ador_installation_count',
                'ador_funds_received',
                'ador_remain_funds',
                'ador_pay_way',
                'ador_status',
                'ador_remark',
            ])->offset($offset)->limit($pageSize)->get()->toArray();

        foreach ($list as $key => &$value) {
            $value['ador_pay_way_msg'] = AdvancedOrder::$payWayArr[$value['ador_pay_way']] ?? '';
        }

        unset($value);
        //
        //        if (isset($params['export'])) {
        //            $list = Order::getCursorSortById($list);
        //            return $this->export($list, $params, $total);
        //        }

        return [
            'total' => $total,
            'list'  => $list,
        ];
    }

    public function getInfo($params)
    {
        $data = AdvancedOrder::query()
            ->leftJoin('node', 'node_id', '=', 'ador_node_id')
            ->where(['ador_id' => $params['id']])
            ->select([
                'advanced_order.*',
                'node.node_name',
            ])->first();

        if (!$data) {
            ResponseLogic::setMsg('记录不存在');
            return false;
        }
        $data                  = $data->toArray();
        $data['ador_node_arr'] = Node::getNodeParent($data['ador_node_id']);
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
            'ador_node_id'            => $params['node_id'],
            'ador_installation_date'  => $params['installation_date'] ?? null,
            'ador_pay_date'           => $params['pay_date'] ?? null,
            'ador_user_name'          => $params['user_name'] ?? '',
            'ador_user_phone'         => $params['user_phone'] ?? '',
            'ador_address'         => $params['address'] ?? '',
            'ador_installation_count' => $params['installation_count'] ?? '',
            'ador_pay_way'            => $params['pay_way'] ?? '',
            'ador_remark'             => $params['remark'] ?? '',
            'ador_operator_id'        => AuthLogic::$userId ?? 0,
        ];

        if(isset($params['id']) && !empty($params['id'])) {
            if(AdvancedOrder::query()->where(['ador_id' => $params['id']])->update($insertData) === false){
                ResponseLogic::setMsg('更新失败');
                return false;
            }
        }else{
            $insertData['ador_sn'] = 'AD'. date('YmdHis') . rand(1000,9999);
            $insertData['ador_funds_received'] = $params['funds_received'];
            $insertData['ador_remain_funds'] = $params['funds_received'];
            $insertData['ador_status'] = 1;
            if(AdvancedOrder::query()->insert($insertData) === false){
                ResponseLogic::setMsg('添加失败');
                return false;
            }
        }

        return [];
    }

    public function link($params)
    {
        $receivableData = ReceivableAccount::query()->where(['reac_id' => $params['receivable_id']])->first();

        if(!$receivableData){
            ResponseLogic::setMsg('应收款数据不存在');
            return false;
        }

        $advancedData = AdvancedOrder::query()->where(['ador_id' => $params['advanced_id']])->first();

        if(!$advancedData){
            ResponseLogic::setMsg('预收记录不存在');
            return false;
        }

        $receivableData = $receivableData->toArray();
        $advancedData = $advancedData->toArray();

        if(AdvancedRelation::query()->where(['adre_reac_id' => $params['receivable_id'],'adre_ador_id' => $params['advanced_id']])->exists()){
            ResponseLogic::setMsg('订单已被绑定');
            return false;
        }

        $needPay = $receivableData['reac_account_receivable'] - $receivableData['reac_funds_received'];

        if($needPay == 0){
            ResponseLogic::setMsg('该订单已完成回款');
            return false;
        }
//        print_r($receivableData);die;

        #如果预收金额大于订单金额
        if($advancedData['ador_remain_funds'] > $needPay){
            $update = [
                'ador_remain_funds' => $advancedData['ador_remain_funds'] - $needPay,
            ];

            $consume = $needPay;
        }else{
            $update = [
                'ador_remain_funds' => 0,
                'ador_status' => 2
            ];

            $consume = $advancedData['ador_remain_funds'];
        }

        $insert = [
            'adre_reac_id' => $params['receivable_id'],
            'adre_ador_id' => $params['advanced_id'],
            'adre_funds_received' => $consume,
            'adre_operator_id' => AuthLogic::$userId,
        ];

        DB::beginTransaction();

        #插入关联表
        if(AdvancedRelation::query()->insert($insert) === false){
            DB::rollBack();
            ResponseLogic::setMsg('插入关联失败');
            return false;
        }

        #更新预收订单
        if(AdvancedOrder::query()->where(['ador_id' => $params['advanced_id']])->update($update) === false){
            DB::rollBack();
            ResponseLogic::setMsg('更新预付订单失败');
            return false;
        }

        #更新订单实收并添加流水
        if(ReceivableAccount::query()->where(['reac_id' => $params['receivable_id']])->update(['reac_funds_received' => DB::raw("reac_funds_received+".$consume)]) === false){
            DB::rollBack();
            ResponseLogic::setMsg('更新应收款表失败');
            return false;
        }

        $flow = [
            'reac_account_id' => $params['receivable_id'],
            'reac_datetime' => $advancedData['ador_pay_date'],
            'reac_pay_way' => $advancedData['ador_pay_way'],
            'reac_funds_received' => $consume,
            'reac_type' => 1,
            'reac_remark' => '预收抵扣',
            'reac_status' => 2,
            'reac_operator_id' => AuthLogic::$userId
        ];

        if(ReceivableAccountFlow::query()->insert($flow) === false){
            DB::rollBack();
            ResponseLogic::setMsg('插入回款流水失败');
            return false;
        }

        DB::commit();
        return [];
    }

    public function delete($params)
    {
        $data = AdvancedOrder::query()->where(['ador_id' => $params['id']])->first();

        if(!$data){
            ResponseLogic::setMsg('记录不存在');
            return false;
        }

        if(AdvancedRelation::query()->where(['adre_ador_id' => $params['id']])->exists()){
            ResponseLogic::setMsg('已存在绑定记录 不能删除');
            return false;
        }
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
