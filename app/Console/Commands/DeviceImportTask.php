<?php

namespace App\Console\Commands;

use App\Http\Library\AepApis\Aep_device_management;
use App\Http\Library\OneNetApis\OneNetDeviceManagement;
use App\Http\Logic\ToolsLogic;
use App\Models\DetectorImportTask;
use App\Models\SmokeDetector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DeviceImportTask extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:device-import-task';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
//        DB::setDefaultConnection('mysql2');

        ini_set( 'max_execution_time', 72000 );
        ini_set( 'memory_limit', '2048M');

        $list = DetectorImportTask::query()->where(['deim_status' => 0])->limit(100)->select([
            'deim_id',
            'deim_imei',
            'deim_brand_name',
            'deim_model_name',
            'deim_database_status',
            'deim_aep_status',
            'deim_onenet_status'
        ])->get()->toArray();

        if(empty($list)){
            return true;
        }

        $ids = array_column($list,'deim_id');

        #置为可执行状态
        DetectorImportTask::query()->where(['deim_status' => 0])->whereIn('deim_id',$ids)->update(['deim_status' => 1]);

//        $appKey = 'O6eqmX5VOJ';
//        $appSecret = 'dpeI2JriLl';
//        $oneNetUserId = '127713';
//        $oneNetAccessKey = '6XeJW0M/Im0DC2cmMD/pnGA/B59IEbS7LLX7M8P5D03pOjH+IaHScIIspDbZ4CI50x3M2RPvD05ba1HcwdzzNg=='; //平安穗粤

        $appKey = 'dZxzhRw8Uw4';
        $appSecret = 'cGG8G1Enjl';
        $oneNetUserId = '380729';
        $oneNetAccessKey = 'a1Ygz82hVkzOHUIfF1c0LfLTr41prJZpKq4EfeEEGXhUfCSVMPdhlScKa23xW4/gR9VDiyX4Lzxvc4eAN6i2IA=='; //本地
        $oneNet = new OneNetDeviceManagement();
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

        $total = 0;

        $notImported = "";
        $errorArr = [];
//        print_r($list);die;
        foreach ($list as $key => $value){
            try {
                $remark = '';
                $imei = $value['deim_imei'];
                $brandName = $value['deim_brand_name'];
                $modelName = $value['deim_model_name'];

                if(!isset($productArr[$modelName])){
                    continue;
                }

                $productInfo = $productArr[$modelName];

                $imei = str_replace( " ", "", $imei );
                $total++;

                #导入数据库
                if(empty($value['deim_database_status'])){
                    if(SmokeDetector::query()->where(['smde_imei' => $imei])->exists()){
                        ToolsLogic::writeLog('导入失败 imei：' . $imei . '已存在','deviceImportTask');
                        $remark .= '数据库导入：设备已存在。';
                    }else{
                        SmokeDetector::query()->insert([
                            "smde_type" => $productInfo['type'],
                            "smde_brand_name" => $value['deim_brand_name'],
                            "smde_model_name" => $value['deim_model_name'],
                            "smde_imei" => $imei,
                            "smde_model_tag" => $modelTag,
                            "smde_part_id" => $partId,
                        ]);

                        ToolsLogic::writeLog('导入成功 imei：' . $imei,'deviceImportTask');
                    }

                    $value['deim_database_status'] = 1;
                }

                if ( $modelName == "HS2SA" ){
                    continue;
                }

                #导入aep
                if(empty($value['deim_aep_status'])){
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
                        $value['deim_aep_status'] = 1;
                    }else{
                        $remark .= 'aep导入失败：' . $aepRes['msg'] . '。';
                    }
                }

                #导入onenet
                if(empty($value['deim_onenet_status'])){
                    $onenetReq = [
                        'product_id' => $productInfo['one_net_product_id'],
                        'device_name' => $imei,
                        'imei' => $imei,
                        'imsi' => $imei,
                    ];

                    $onenetRes = $oneNet->create($onenetReq,$oneNetUserId,$oneNetAccessKey);

                    ToolsLogic::writeLog('导入onenet imei：' . $imei,'deviceImportTask',$onenetRes);
                    $onenetRes = ToolsLogic::jsonDecode($onenetRes);

                    if($onenetRes['code'] == 0){
                        $value['deim_onenet_status'] = 1;
                    }else{
                        $remark .= 'onenet导入失败：' . $aepRes['msg'] . '。';
                    }
//                    print_r($onenetRes);die;

                }
            }catch (\Exception $e) {
                ToolsLogic::writeLog('exception' . $e->getMessage() .$e->getLine() . ' imei:' . $imei,'deviceImportTask');
            }

            DetectorImportTask::query()->where(['deim_id' =>  $value['deim_id']])->update(['deim_status' => 2,'deim_database_status' => $value['deim_database_status'],'deim_aep_status' => $value['deim_aep_status'],'deim_onenet_status' => $value['deim_onenet_status'],'deim_remark' => $remark]);
        }

        return true;
    }

   public function importAep($imeis,$productId,$appKey,$appSecret,$masterKey)
   {
       $devices = [];
       foreach ($imeis as $key => $value){
           $body = [
               "productId" => $productId,
               "operator" => "string",
           ];

           $device = [
               "deviceName" => $value,
               "deviceSn" => $value,
               "imei" => $value,
           ];

           $devices[] = $device;

           if(count($devices) >= 100){
               $body[ "devices" ] = $devices;

               $result = Aep_device_management::BatchCreateDevice( $appKey, $appSecret, $masterKey, json_encode( $body ) );
               $devices = [];
               $result = ToolsLogic::jsonDecode($result);
               ToolsLogic::writeLog('导入AEP结果:','deviceImportTask',['params' => $body,'result' => $result]);
//                die;
           }

       }

       if(!empty($devices)){
           $body = [
               "productId" => $productId,
               "operator" => "string",
               'devices' => $devices
           ];

           $result = Aep_device_management::BatchCreateDevice( $appKey, $appSecret, $masterKey, json_encode( $body ) );
           $devices = [];
           $result = ToolsLogic::jsonDecode($result);
           ToolsLogic::writeLog('导入AEP结果:','deviceImport',['params' => $body,'result' => $result]);
       }
   }

   public function importOneNet($imeis,$productId,$userId,$accessKey)
   {
        $oneNet = new OneNetDeviceManagement();
        $req = [
            'product_id' => $productId,
        ];

        $deviceList = [];
        foreach ($imeis as $key => $imei){
            $deviceList[] = [
                'device_name' => $imei,
                'imei' => $imei,
                'imsi' => $imei,
            ];

            if(count($deviceList) >= 100){
                $req['device_list'] = $deviceList;
                $deviceList = [];
                $result = $oneNet->batchCreate($req,$userId,$accessKey);
                if(!$result){
                    ToolsLogic::writeLog('导入ONENET失败:','deviceImport',['params' => $req]);
                }else{
                    $result = ToolsLogic::jsonDecode($result);
                    ToolsLogic::writeLog('导入ONENET结果:','deviceImport',['params' => $req,'result' => $result]);
                }
            }
        }

        if(!empty($deviceList)){
            $req['device_list'] = $deviceList;
            $deviceList = [];
            $result = $oneNet->batchCreate($req,$userId,$accessKey);
            if(!$result){
                ToolsLogic::writeLog('导入ONENET失败:','deviceImport',['params' => $req]);
            }else{
                $result = ToolsLogic::jsonDecode($result);
                ToolsLogic::writeLog('导入ONENET结果:','deviceImport',['params' => $req,'result' => $result]);
            }
        }
   }
}
