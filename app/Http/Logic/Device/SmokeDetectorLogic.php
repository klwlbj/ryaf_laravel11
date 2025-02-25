<?php

namespace App\Http\Logic\Device;

use App\Http\Library\AepApis\Aep_device_management;
use App\Http\Library\OneNetApis\OneNetDeviceManagement;
use App\Http\Logic\BaseLogic;
use App\Http\Logic\ResponseLogic;
use App\Http\Logic\ToolsLogic;
use App\Models\DetectorImportTask;
use App\Models\DetectorSignal;
use App\Models\IotNotification;
use App\Models\SmokeDetector;
use Illuminate\Support\Facades\DB;

class SmokeDetectorLogic extends BaseLogic
{
    public function importDevice($data)
    {
//        $appKey = 'O6eqmX5VOJ';
//        $appSecret = 'dpeI2JriLl';
//        $oneNetUserId = '127713';
//        $oneNetAccessKey = '6XeJW0M/Im0DC2cmMD/pnGA/B59IEbS7LLX7M8P5D03pOjH+IaHScIIspDbZ4CI50x3M2RPvD05ba1HcwdzzNg=='; //平安穗粤

        $appKey = 'dZxzhRw8Uw4';
        $appSecret = 'cGG8G1Enjl';
        $oneNetUserId = '380729';
        $oneNetAccessKey = 'a1Ygz82hVkzOHUIfF1c0LfLTr41prJZpKq4EfeEEGXhUfCSVMPdhlScKa23xW4/gR9VDiyX4Lzxvc4eAN6i2IA=='; //本地

        $productArr = [
            'YL-IOT-YW03' => [
                'type' => '烟感',
                'aep_product_id' => '17090734',
                'aep_master_key' => "2eae2c21209842a6985af4cbd794bba4",
                'one_net_product_id' => 'YeruR7viEL',
            ],
//            'HM-618PH-4G' => [
//                'type' => '烟感',
//                'aep_product_id' => '17084269',
//                'aep_master_key' => "c9636dcae10841aa859d5511589483b6",
//                'one_net_product_id' => 'E2dMYR85jh',
//            ],
            'HM-618PH-4G' => [
                'type' => '烟感',
                'aep_product_id' => '17187368',
                'aep_master_key' => "6ed2e2cc7ca54a4e9c79eaa0c843f714",
                'one_net_product_id' => '0UL337T8R2',
            ],
            'HM-618PH-NB' => [
                'type' => '烟感',
                'aep_product_id' => '16922967',
                'aep_master_key' => "0434f19136324920a51ba7287fffb667",
                'one_net_product_id' => '407478',
            ],
        ];


        $modelTag = "";
        $partId = 1; // 如约自己的设备

        try {
            $remark = '';
            $imei = $data['deim_imei'];
            $brandName = $data['deim_brand_name'];
            $modelName = $data['deim_model_name'];

            if(!isset($productArr[$modelName])){
                ResponseLogic::setMsg('产品信息不存在');
                $this->updateImportStatus($data,'产品信息不存在');
                return false;
            }

            $productInfo = $productArr[$modelName];

            $imei = str_replace( " ", "", $imei );

            #导入数据库
            if(empty($data['deim_database_status'])){
                if(SmokeDetector::query()->where(['smde_imei' => $imei])->exists()){
                    ToolsLogic::writeLog('导入失败 imei：' . $imei . '已存在','deviceImportTask');
                    $remark .= '数据库导入：设备已存在。';
                }else{
                    SmokeDetector::query()->insert([
                        "smde_type" => $productInfo['type'],
                        "smde_brand_name" => $data['deim_brand_name'],
                        "smde_model_name" => $data['deim_model_name'],
                        "smde_imei" => $imei,
                        "smde_model_tag" => $modelTag,
                        "smde_part_id" => $partId,
                    ]);

                    ToolsLogic::writeLog('导入成功 imei：' . $imei,'deviceImportTask');
                }

                $data['deim_database_status'] = 1;
            }

            #导入aep
            if(empty($data['deim_aep_status'])){
                $aepReq = [
                    "productId" => $productInfo['aep_product_id'],
                    "operator" => "string",
                    "deviceName" => $imei,
                    "deviceSn" => $imei,
                    "imei" => $imei,
                    "other" => [
                        'autoObserver' => 0
                    ],
                ];

                $aepRes = Aep_device_management::CreateDevice($appKey, $appSecret, $productInfo['aep_master_key'], json_encode( $aepReq ));
                $aepRes = ToolsLogic::jsonDecode($aepRes);
                ToolsLogic::writeLog('导入aep imei：' . $imei,'deviceImportTask',$aepRes);

                if($aepRes['code'] == 0){
                    $data['deim_aep_status'] = 1;
                }else{
                    $remark .= 'aep导入失败：' . $aepRes['msg'] . '。';
                }
            }

            #导入onenet
            if(empty($data['deim_onenet_status'])){
                $onenetReq = [
                    'product_id' => $productInfo['one_net_product_id'],
                    'device_name' => $imei,
                    'imei' => $imei,
                    'imsi' => $imei,
                ];

                $onenetRes = OneNetDeviceManagement::create($onenetReq,$oneNetUserId,$oneNetAccessKey);

                ToolsLogic::writeLog('导入onenet imei：' . $imei,'deviceImportTask',$onenetRes);
                $onenetRes = ToolsLogic::jsonDecode($onenetRes);

                if($onenetRes['code'] == 0){
                    $data['deim_onenet_status'] = 1;
                }else{
                    $remark .= 'onenet导入失败：' . $aepRes['msg'] . '。';
                }
//                    print_r($onenetRes);die;

            }
        }catch (\Exception $e) {
            ToolsLogic::writeLog('exception' . $e->getMessage() .$e->getLine() . ' imei:' . $imei,'deviceImportTask');
        }

        $this->updateImportStatus($data,$remark);

        return true;
    }

    public function updateImportStatus($data,$remark)
    {
        return DetectorImportTask::query()->where(['deim_id' =>  $data['deim_id']])->update(['deim_status' => 2,'deim_database_status' => $data['deim_database_status'],'deim_aep_status' => $data['deim_aep_status'],'deim_onenet_status' => $data['deim_onenet_status'],'deim_remark' => $remark]);
    }
}
