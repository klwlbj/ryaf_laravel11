<?php

namespace App\Console\Commands;

use App\Http\Library\AepApis\Aep_device_management;
use App\Http\Library\OneNetApis\OneNetDeviceManagement;
use App\Http\Logic\ToolsLogic;
use App\Models\SmokeDetector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DeviceImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:device-import';

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
        DB::setDefaultConnection('mysql2');

        ini_set( 'max_execution_time', 72000 );
        ini_set( 'memory_limit', '2048M' );

        $fileName = public_path() . "/deviceImport.xlsx";
        $spreadsheet = IOFactory::load($fileName);


        $sheetData = $spreadsheet->getSheet(0)->toArray(null, true, true, true);
//        print_r($sheetData);die;
        $imeis = [];

        $appKey = 'O6eqmX5VOJ'; $appSecret = 'dpeI2JriLl'; $oneNetUserId = '127713';$oneNetAccessKey = '6XeJW0M/Im0DC2cmMD/pnGA/B59IEbS7LLX7M8P5D03pOjH+IaHScIIspDbZ4CI50x3M2RPvD05ba1HcwdzzNg=='; //平安穗粤

//        $appKey = 'dZxzhRw8Uw4'; $appSecret = 'cGG8G1Enjl'; $oneNetUserId = '380729';$oneNetAccessKey = 'a1Ygz82hVkzOHUIfF1c0LfLTr41prJZpKq4EfeEEGXhUfCSVMPdhlScKa23xW4/gR9VDiyX4Lzxvc4eAN6i2IA=='; //本地

//        $aep_product_id = "15599428"; $aep_master_key = "08d3fbc2c028477c8afc3e9ce60d714d"; $oneNetProductId = '407478'; //平安穗粤-海曼HM608NB      //温感
//        $aep_product_id = "16922967"; $aep_master_key = "0434f19136324920a51ba7287fffb667"; $oneNetProductId = '407478'; //平安穗粤-海曼HM608/618NB透传版
        //$aep_product_id = "17085637"; $aep_master_key = "9c6f31f078024c0c8ab9d288eeaec206"; $oneNetProductId = 'kC06Yb93QB'; //平安穗粤-六瑞-SA-JTY-GD02C
        $aep_product_id = "17102042"; $aep_master_key = "3859262b741f40d0a0c3bd8d64a5cebe"; $oneNetProductId = 'HzFl9NvY5q'; //平安穗粤-源流-YL-IOT-YW03（源流Y3_4G_烟感MQTT）
//        $aep_product_id = "17084269"; $aep_master_key = "c9636dcae10841aa859d5511589483b6"; $oneNetProductId = 'E2dMYR85jh'; //平安穗粤-海曼618-4G

//        $aep_product_id = "17090734"; $aep_master_key = "2eae2c21209842a6985af4cbd794bba4";$oneNetProductId = 'YeruR7viEL'; //平安穗粤-源流-YL-IOT-YW03 本地

        //$smde_type = "烟感"; $smde_brand_name = "海曼"; $smde_model_name = "HS2SA";
        //$smde_type = "烟感"; $smde_brand_name = "海曼"; $smde_model_name = "HM-608PH-NB";
//        $smde_type = "烟感"; $smde_brand_name = "海曼"; $smde_model_name = "HM-618PH-NB";
//        $smde_type = "温感"; $smde_brand_name = "海曼"; $smde_model_name = "HM-5HA-NB";
        //$smde_type = "烟感"; $smde_brand_name = "东昂"; $smde_model_name = "JTY-YG-002NB";
        //$smde_type = "烟感"; $smde_brand_name = "六瑞"; $smde_model_name = "SA-JTY-GD02C";
        $smde_type = "烟感"; $smde_brand_name = "源流"; $smde_model_name = "YL-IOT-YW03";
//        $smde_type = "烟感"; $smde_brand_name = "海曼"; $smde_model_name = "HM-618PH-4G";


        $smde_model_tag = "";
        $smde_part_id = 1; // 如约自己的设备

        $total = 0;
        $imported = 0;
        $not_imported = "";

        foreach ($sheetData as $key => $value){
            $value = array_values($value);
            $imei = $value[0];
            if(empty($imei)){
                continue;
            }

            $imei = str_replace( " ", "", $imei );
            $imeis[] = $imei;
            $total++;


            if(SmokeDetector::query()->where(['smde_imei' => $imei])->exists()){
                ToolsLogic::writeLog('导入失败 imei：' . $imei . '已存在','deviceImport');
            }else{
                SmokeDetector::query()->insert([
                    "smde_type" => $smde_type,
                    "smde_brand_name" => $smde_brand_name,
                    "smde_model_name" => $smde_model_name,
                    "smde_imei" => $imei,
                    "smde_model_tag" => $smde_model_tag,
                    "smde_part_id" => $smde_part_id,
                ]);

                $imported++;
                ToolsLogic::writeLog('导入成功 imei：' . $imei,'deviceImport');
            }
        }

        ToolsLogic::writeLog('导入结束: 共' . $total .'个，成功导入' . $imported . '个','deviceImport');

        if ( $smde_model_name == "HS2SA" ) return;


        //导入AEP
        $this->importAep($imeis,$aep_product_id,$appKey, $appSecret, $aep_master_key);

        //导入oneNet
        $this->importOneNet($imeis,$oneNetProductId,$oneNetUserId,$oneNetAccessKey);


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
               ToolsLogic::writeLog('导入AEP结果:','deviceImport',['params' => $body,'result' => $result]);
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
