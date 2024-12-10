<?php

namespace App\Http\Logic\Device;

use App\Http\Logic\BaseLogic;
use App\Http\Logic\ToolsLogic;
use App\Models\IotNotification;
use App\Models\SmokeDetector;
use Illuminate\Support\Facades\DB;

class SmokeDetectorLogic extends BaseLogic
{
    public function handleSignal($model = 'YL-IOT-YW03')
    {
        DB::setDefaultConnection('mysql2');
        $imeiArr = SmokeDetector::query()
            ->where(['smde_model_name' => $model])
            ->where('smde_last_heart_beat','<>','')
            ->select(['smde_imei'])->orderBy('smde_id','desc')->limit(100)->pluck('smde_imei')->toArray();


        foreach ($imeiArr as $imei){
            if(in_array($model,['YL-IOT-YW03'])){
                $heartList = IotNotification::query()->where(['iono_imei' => $imei,'iono_type' => 21])->select([
                    'iono_body'
                ])->orderBy('iono_id','asc')->limit(3)->get()->toArray();

                if(count($heartList) < 3){
                    continue;
                }

                $pass = 1;
                foreach ($heartList as $value){
                    $body = ToolsLogic::jsonDecode($value['iono_body']);
//                    print_r($body);die;
                    $signalScore = $this->calculateSignalScore($body['analyze_data']['RSSI'] ?? null,$body['analyze_data']['CSQ'] ?? null,$body['analyze_data']['RSRQ'] ?? null,$body['analyze_data']['rsrp'] ?? null);
//                    if($signalScore == 0){
//                       print_r($body);
//                    }
//                    print_r($imei . '设备信号分数：' . $signalScore . "\n");
                    if($signalScore < 0.5){
                        $pass = 0;
                        break;
                    }
                }

//                print_r($pass);
//                die;

            }
        }
        die;
    }

    public function calculateSignalScore($rssi = null, $csq = null, $rsrq = null, $rsrp = null)
    {
        $weightList = [];
        $weightTotal = 0;

        if(!empty($rssi)){
            if($rssi <= -90){
                return 0;
            }
            $weightList[] = [
                'weight' => 25,
                'data' => ($rssi - (-120)) / ((-30) - (-120))
            ];

            $weightTotal+=25;
        }

        if(!empty($csq)){
            if($csq <= 22){
                return 0;
            }
            $weightList[] = [
                'weight' => 30,
                'data' => $csq / 31
            ];

            $weightTotal+=30;
        }

        if(!empty($rsrq)){
            if($rsrq <= -20){
                return 0;
            }

            $weightList[] = [
                'weight' => 20,
                'data' => ($rsrq - (-20)) / ((-3) - (-20))
            ];

            $weightTotal+=20;
        }

        if(!empty($rsrp)){
            if($rsrp < -400){
                $rsrp = $rsrp/10;
            }

            if($rsrp <= -100){
                return 0;
            }

            $weightList[] = [
                'weight' => 25,
                'data' => ($rsrp - (-140)) / ((-44) - (-140))
            ];

            $weightTotal+=25;
        }
//        print_r($weightList);
        $score = 0;
        foreach ($weightList as $key => $value){
            $score += ($value['weight'] / $weightTotal) * $value['data'];
        }

        return $score;
    }
}
