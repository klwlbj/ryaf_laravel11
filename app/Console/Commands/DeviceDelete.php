<?php

namespace App\Console\Commands;

use App\Http\Library\AepApis\Aep_device_management;
use App\Http\Library\OneNetApis\OneNetDeviceManagement;
use App\Http\Library\YunChuang\YunChuangUtil;
use App\Http\Logic\ToolsLogic;
use App\Models\SmokeDetector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DeviceDelete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:device-delete';

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

//        $fileName = public_path() . "/deviceDelete1.xlsx";
//        $spreadsheet = IOFactory::load($fileName);
//        $imeis = [];
//        $sheetData = $spreadsheet->getSheet(0)->toArray(null, true, true, true);
//
//
//        foreach ($sheetData as $key => $value) {
//            $value = array_values($value);
//            $imeis[] = $value[0];
////            if($data){
////                SmokeDetector::query()->where(['smde_imei' => $value[0]])->delete();
////                ToolsLogic::writeLog('删除数据库imei:' . $value[0],'deviceDelete');
////            }else{
////                ToolsLogic::writeLog('数据库imei不存在:' . $value[0],'deviceDelete');
////            }
//        }

        $imeis = [
            '865371075308162',
            '865371076034130',
            '865371076213114',
            '865371075916311',
            '865371076017291',
            '865371075904028',
            '865371075888668',
            '865371076031987',
            '865371075536176',
            '865371075976356',
            '865371075904283',
            '865371075915214',
            '865371076017291',
            '865371075916311',
            '865371075904028',
            '865371076034130',
            '865371075443553',
            '865371078221615',
            '865371075308162',
            '865371076034130',
            '865371076213114',
            '865371075916311',
            '865371076017291',
            '865371075904028',
            '865371075888668',
            '865371076031987',
            '865371075536176',
            '865371075976356',
            '865371075904283',
        ];
        $yunIds = SmokeDetector::query()->whereIn('smde_imei',$imeis)
            ->where('smde_yunchuang_id','>',0)
            ->select(['smde_yunchuang_id','smde_imei'])->get()->toArray();

        $appKey = 'O6eqmX5VOJ'; $appSecret = 'dpeI2JriLl'; $oneNetUserId = '127713';$oneNetAccessKey = '6XeJW0M/Im0DC2cmMD/pnGA/B59IEbS7LLX7M8P5D03pOjH+IaHScIIspDbZ4CI50x3M2RPvD05ba1HcwdzzNg=='; //平安穗粤

//        $appKey = 'dZxzhRw8Uw4'; $appSecret = 'cGG8G1Enjl'; $oneNetUserId = '380729';$oneNetAccessKey = 'a1Ygz82hVkzOHUIfF1c0LfLTr41prJZpKq4EfeEEGXhUfCSVMPdhlScKa23xW4/gR9VDiyX4Lzxvc4eAN6i2IA=='; //本地

//        $aep_product_id = "15599428"; $aep_master_key = "08d3fbc2c028477c8afc3e9ce60d714d"; $oneNetProductId = '407478'; //平安穗粤-海曼HM608NB      //温感

//        $aep_product_id = "16922967"; $aep_master_key = "0434f19136324920a51ba7287fffb667"; $oneNetProductId = '407478'; //平安穗粤-海曼HM608/618NB透传版

        $aep_product_id = "17085637"; $aep_master_key = "9c6f31f078024c0c8ab9d288eeaec206"; $oneNetProductId = 'kC06Yb93QB'; //平安穗粤-六瑞-SA-JTY-GD02C
//        $aep_product_id = "17102042"; $aep_master_key = "3859262b741f40d0a0c3bd8d64a5cebe"; $oneNetProductId = 'HzFl9NvY5q'; //平安穗粤-源流-YL-IOT-YW03（源流Y3_4G_烟感MQTT）
//        $aep_product_id = "17084269"; $aep_master_key = "c9636dcae10841aa859d5511589483b6"; $oneNetProductId = 'E2dMYR85jh'; //平安穗粤-海曼618-4G

        //删除AEP
        $this->deleteAep($imeis,$aep_product_id,$appKey, $appSecret, $aep_master_key);

        //删除oneNet
        $this->deleteOneNet($imeis,$oneNetProductId,$oneNetUserId,$oneNetAccessKey);

        //删除区平台
        $this->deleteYunChuang($yunIds);

        //删除数据库
        $res = SmokeDetector::query()->whereIn('smde_imei',$imeis)->delete();
        print_r($res);die;
        die;
    }

    public function deleteYunChuang($yunIds){
        $token = YunChuangUtil::getToken();
        foreach ($yunIds as $key => $value){
            $res = YunChuangUtil::removeDevice($token,$value['smde_yunchuang_id']);
            ToolsLogic::writeLog('删除区平台结果:imei:' . $value['smde_imei'],'deviceDelete',$res);
        }

    }

   public function deleteAep($imeis,$productId,$appKey,$appSecret,$masterKey)
   {
       $imei = [];
       foreach ($imeis as $key => $value){
           $body = [
               "productId" => $productId,
               "operator" => "string",
           ];


           $imei[] = $value;

           if(count($imei) >= 100){
               $body[ "imeiList" ] = $imei;

               $result = Aep_device_management::DeleteDeviceByImei( $appKey, $appSecret, $masterKey, json_encode( $body ) );
               $imei = [];
               $result = ToolsLogic::jsonDecode($result);
               ToolsLogic::writeLog('删除AEP结果:','deviceDelete',['params' => $body,'result' => $result]);
//                die;
           }

       }

       if(!empty($imei)){
           $body = [
               "productId" => $productId,
               "operator" => "string",
               'imeiList' => $imei
           ];

           $result = Aep_device_management::DeleteDeviceByImei( $appKey, $appSecret, $masterKey, json_encode( $body ) );

           $imei = [];
           $result = ToolsLogic::jsonDecode($result);
           ToolsLogic::writeLog('删除AEP结果:','deviceDelete',['params' => $body,'result' => $result]);
       }
   }

   public function deleteOneNet($imeis,$productId,$userId,$accessKey)
   {
        $deviceList = [];
        foreach ($imeis as $key => $imei){
            $req = [
                'product_id' => $productId,
//                'device_name' => '865118076566615',
                'imei' => $imei,
            ];

           $result = OneNetDeviceManagement::delete($req,$userId,$accessKey);
//           print_r($result);die;
            if(!$result){
                ToolsLogic::writeLog('删除ONENET失败:','deviceDelete',['params' => $req]);
            }else{
                $result = ToolsLogic::jsonDecode($result);
                ToolsLogic::writeLog('删除ONENET结果:','deviceDelete',['params' => $req,'result' => $result]);
            }
        }
   }
}
