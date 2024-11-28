<?php

namespace App\Http\Controllers\Admin;

use App\Http\Logic\AuthLogic;
use App\Http\Logic\ResponseLogic;
use App\Http\Logic\ToolsLogic;
use App\Models\Admin;
use App\Models\File;
use App\Models\Material;
use App\Models\MaterialDetail;
use App\Models\MaterialFlow;
use App\Models\MaterialInventory;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class MaterialFlowHandle
{
    public function importFlow()
    {
        $fileName = "安防人员导入.xlsx";
        $spreadsheet = IOFactory::load($fileName);

        $startDate = '2024-06-29 23:59:59';

        $materialArr = Material::query()->pluck('mate_id','mate_name')->toArray();
        $adminArr = Admin::query()->pluck('admin_id','admin_name')->toArray();

        $sheetData = $spreadsheet->getSheet(0)->toArray(null, true, true, true);
        $error = [];
        $inList = [];
        $outList = [];

        foreach ($sheetData as $key => $value){
            $name = $value[0];
            $date = $value[1];
            $inFlowNumber = $value[2] ?? 0;
            $outFlowNumber = $value[3] ?? 0;
            $salesType = $value[4] ?? '销售性质';
            $productionDate = $value[5] ?? date('Y-m-d');
            $expireDate = $value[6] ?? date('Y-m-d');
            $applyName = $value[7] ?? '';
            $remark = $value[8] ?? '';

            $materialId = $materialArr[$name] ?? 0;
            if(empty($materialId)){
                $error[$key . '-找不到物品信息'] = $value;
                continue;
            }

            $type = 0;
            if(!empty($inFlowNumber) > 0){
                $type = 1;
            }elseif (!empty($outFlowNumber) > 0){
                $type = 2;
            }

            if(empty($type)){
                $error[$key . '-入库出库不存在'] = $value;
                continue;
            }

            if($type == 1){
                $inList[] = [
                    'mate_id' => $materialId,
                    'date' => $date,
                    'number' => $inFlowNumber,
                    'production_date' => $productionDate,
                    'expire_date' => $expireDate,
//                    'apply_id' => $applyAdminId,
                    'remark' => $remark,
                ];
            }else{
                $applyAdminId = $adminArr[$applyName] ?? 0;
                if(empty($applyAdminId)){
                    $error[$key . '-申请人id不存在'] = $value;
                    continue;
                }

                $outList = [
                    'mate_id' => $materialId,
                    'date' => $date,
                    'number' => $inFlowNumber,
                    'apply_id' => $applyAdminId,
                    'purpose' => $salesType,
                    'remark' => $remark,
                ];
            }
        }

        if(!empty($error)){
            return ResponseLogic::apiResult(-1,'初始化数据失败',$error);
        }

        $numberArr = [];

        #先处理入库
        if(!empty($inList)){
            foreach ($inList as $key => $value){
                $req = [
                    'material_id'  => $value['mate_id'],
                    'warehouse_id' => 2,
                    'number' => $value['number'],
                    'verify_user_id' => 44,
                    'production_date' => $value['production_date'],
                    'expire_date' => $value['expire_date'],
                    'remark' => $value['remark'],
                    'status' => 2,
                    'datetime' => $value['date'],
                ];

                if(!$this->inComing($req)){
                    $error[$key . '-入库失败：' . ResponseLogic::getMsg()] = $value;
                    continue;
                }

                if(!isset($numberArr[$value['mate_id']])){
                    $numberArr[$value['mate_id']] = 0;
                }

                $numberArr[$value['mate_id']] += $value['number'];
            }
        }

        if(!empty($error)){
            return ResponseLogic::apiResult(-1,'入库失败',$error);
        }


        if(!empty($outList)){
            foreach ($outList as $key => $value){
                $req = [
                    'material_id' => $value['mate_id'],
                    'warehouse_id' => 2,
                    'number' => $value['number'],
                    'purpose' => $value['purpose'],
                    'verify_user_id' => 44,
                    'apply_user_id' => $value['apply_id'],
                    'receive_user_id' => $value['apply_id'],
                    'datetime' => $value['date'],
                    'remark' => $value['remark'],
                ];


                if(!$this->outComing($req)){
                    $error[$key . '-出库失败：' . ResponseLogic::getMsg()] = $value;
                    continue;
                }

                if(!isset($numberArr[$value['mate_id']])){
                    $numberArr[$value['mate_id']] = 0;
                }

                $numberArr[$value['mate_id']] += $value['number'];
            }
        }

        if(!empty($error)){
            return ResponseLogic::apiResult(-1,'出库失败',$error);
        }

        #抵消本月出入库数据
        foreach ($numberArr as $key => $value){
            #如果库存大于0 则需要出库抵消
            if($value > 0){
                $req = [
                    'material_id' => $key,
                    'warehouse_id' => 2,
                    'number' => $value,
                    'purpose' => 2,
                    'verify_user_id' => 44,
                    'apply_user_id' => 10010,
                    'receive_user_id' => 10010,
                    'datetime' => $startDate,
                    'remark' => '抵消下月流水数据',
                ];

                if(!$this->outComing($req)){
                    $error[$key . '-入库抵消数据失败：' . ResponseLogic::getMsg()] = $req;
                    continue;
                }
            }

            if($value < 0){
                $req = [
                    'material_id' => $key,
                    'warehouse_id' => 2,
                    'number' => $value,
                    'verify_user_id' => 44,
                    'production_date' => $startDate,
                    'expire_date' => date("Y-m-d H:i:s",strtotime($value['expire_date'] . " +10 year")),
                    'remark' => '抵消下月流水数据',
                    'datetime' => $startDate,
                ];

                if(!$this->inComing($req)){
                    $error[$key . '-出库抵消数据失败：' . ResponseLogic::getMsg()] = $req;
                    continue;
                }
            }
        }

        return ResponseLogic::apiResult(0,'导入成功, 入库数：' .count($inList) . ',出库数：' . count($outList) ,[]);
    }

    public function inComing($params)
    {
        $materialData = Material::getDataById($params['material_id']);

        if(!$materialData){
            ResponseLogic::setMsg('物品数据不存在');
            return false;
        }

        # 获取默认单价
        $defaultPriceTax = $materialData['mate_price_tax'] ?? 0;
        $defaultTax = $materialData['mate_tax'] ?? 0;
        $defaultInvoiceType = $materialData['mate_invoice_type'] ?? 0;

        $incomingData = [
            'mafl_material_id'  => $params['material_id'],
            'mafl_warehouse_id'  => $params['warehouse_id'],
            'mafl_type' => 1,
            'mafl_number' => $params['number'],
            'mafl_tax' => $defaultTax,
            'mafl_price_tax' => $defaultPriceTax,
            'mafl_invoice_type' => $defaultInvoiceType,
            'mafl_verify_user_id' => $params['verify_user_id'],
            'mafl_production_date' => $params['production_date'],
            'mafl_expire_date' => $params['expire_date'],
            'mafl_datetime' => $params['datetime'],
            'mafl_remark' => $params['remark'] ?? '',
            'mafl_status' => 2,
            'mafl_operator_id' => 10010
        ];

        #查看是否有该仓库
        $inventoryId = MaterialInventory::query()
            ->where(['main_warehouse_id' => $params['warehouse_id'],'main_material_id' => $params['material_id']])
            ->value('main_id');

        #插入库存流水
        $flowId = MaterialFlow::query()->insertGetId($incomingData);
        if(!$flowId){
            ResponseLogic::setMsg('插入库存流水失败');
            return false;
        }

        if(empty($inventoryId)){
            if(MaterialInventory::query()->insert([
                    'main_warehouse_id' => $params['warehouse_id'],
                    'main_material_id' => $params['material_id'],
                    'main_number' => 0,
                ]) === false){
                ResponseLogic::setMsg('创建物品仓库库存失败');
                return false;
            }
        }

        Material::delCacheById($params['material_id']);

        return true;
    }

    public function outComing($params)
    {
        $materialData = Material::query()
            ->where(['mate_id' => $params['material_id']])->select(['mate_id','mate_number'])->first();

        if(!$materialData){
            ResponseLogic::setMsg('物品数据不存在');
            return false;
        }

        #查看物品仓库库存
        $inventoryData = MaterialInventory::query()
            ->where(['main_warehouse_id' => $params['warehouse_id'],'main_material_id' => $params['material_id']])
            ->first();

        if(!$inventoryData){
            ResponseLogic::setMsg('该仓库物品库存不足');
            return false;
        }


        if($inventoryData->main_number < $params['number']){
            ResponseLogic::setMsg('该库存物品不足，当前库存：' . $inventoryData->main_number);
            return false;
        }


        $outComingData = [
            'mafl_material_id'  => $params['material_id'],
            'mafl_warehouse_id'  => $params['warehouse_id'],
            'mafl_type' => 2,
            'mafl_number' => $params['number'],
            'mafl_purpose' => $params['purpose'],
            'mafl_price_tax' => 0,
            'mafl_tax' => 0,
            'mafl_invoice_type' => 0,
            'mafl_verify_user_id' => $params['verify_user_id'],
            'mafl_apply_user_id' => $params['apply_user_id'],
            'mafl_receive_user_id' => $params['receive_user_id'],
            'mafl_datetime' => $params['datetime'],
            'mafl_remark' => $params['remark'] ?? '',
            'mafl_operator_id' => 10010 #操作人 默认写死2
        ];

        #只插入流水
        if((MaterialFlow::query()->insertGetId($outComingData)) === false){
            ResponseLogic::setMsg('插入流水记录失败');
            return false;
        }

        Material::delCacheById($params['material_id']);

        return true;
    }
}
