<?php

namespace App\Http\Logic\Device;

use App\Http\Logic\BaseLogic;
use App\Http\Logic\ToolsLogic;
use App\Models\DetectorSignal;
use App\Models\IotNotification;
use App\Models\SmokeDetector;
use Illuminate\Support\Facades\DB;

class SmokeDetectorLogic extends BaseLogic
{
    /**处理设备信号
     * @param $model
     * @return void
     */
    public function handleSignal($model = 'YL-IOT-YW03')
    {
        ini_set( 'max_execution_time', 72000 );
        ini_set( 'memory_limit', '2048M' );
//        DB::setDefaultConnection('mysql2');
        $imeiArr = DB::connection('mysql2')->table('smoke_detector')
            ->where(['smde_model_name' => $model])
            ->where('smde_last_heart_beat','<>','')
//            ->where(['smde_imei' => 867708075838680])
            ->select(['smde_imei'])->orderBy('smde_id','desc')
//            ->limit(100)
            ->pluck('smde_imei')->toArray();

//        print_r($imeiArr);die;

        $insertData = [];
        foreach ($imeiArr as $imei){
            if(in_array($model,['YL-IOT-YW03'])){
                $heartList = DB::connection('mysql2')->table('iot_notification')->where(['iono_imei' => $imei,'iono_type' => 21])->select([
                    'iono_platform',
                    'iono_body'
                ])->orderBy('iono_id','asc')->limit(3)->get()->toArray();

                if(empty($heartList)){
                    continue;
                }

                $datas = [];
                $scores = [];

                $pass = 1;
                foreach ($heartList as $value){
                    $value = (array)$value;
                    $body = ToolsLogic::jsonDecode($value['iono_body']);
                    $analyzeData = $body['analyze_data'];
//                    print_r($body);die;
                    $signalScore = $this->calculateSignalScore($analyzeData['RSSI'] ?? null,$analyzeData['CSQ'] ?? null,$analyzeData['RSRQ'] ?? null,$analyzeData['rsrp'] ?? null);

                    $datas[] = [
                        'rssi' => $analyzeData['RSSI'] ?? null,
                        'csq' => $analyzeData['CSQ'] ?? null,
                        'rsrq' => $analyzeData['RSRQ'] ?? null,
                        'rsrp' => $analyzeData['rsrp'] ?? null,
                    ];

                    $scores[] = $signalScore;
                    if($signalScore < 0.5){
                        $pass = 0;
//                        break;
                    }
                }

                $insertData[] = [
                    'desi_imei' => $imei,
                    'desi_datas' => ToolsLogic::jsonEncode($datas),
                    'desi_scores' => ToolsLogic::jsonEncode($scores),
                    'desi_pass' => $pass,
                ];

                if(count($insertData) >= 500){
                    DetectorSignal::query()->insert($insertData);
                    $insertData = [];
                }

            }elseif(in_array($model,['HM-618PH-NB'])){
                $heartList = DB::connection('mysql2')->table('iot_notification')->where(['iono_imei' => $imei,'iono_type' => 0])->select([
                    'iono_platform',
                    'iono_body',
                    'iono_rsrp',
                    'iono_rsrq',
                    'iono_snr'
                ])->orderBy('iono_id','asc')->limit(3)->get()->toArray();

                if(empty($heartList)){
                    continue;
                }
                $pass = 1;
                foreach ($heartList as $value){
                    $value = (array)$value;
                    $signalScore = $this->calculateSignalScore(null,null,$value['iono_rsrq'],$value['iono_rsrp']);

                    if($signalScore < 0.5){
                        $pass = 0;
                        break;
                    }

                    $datas[] = [
                        'rssi' => null,
                        'csq' => null,
                        'rsrq' => $value['iono_rsrq'] ?? null,
                        'rsrp' => $value['iono_rsrp'] ?? null,
                    ];

                    $scores[] = $signalScore;
                }

                $insertData[] = [
                    'desi_imei' => $imei,
                    'desi_datas' => ToolsLogic::jsonEncode($datas),
                    'desi_scores' => ToolsLogic::jsonEncode($scores),
                    'desi_pass' => $pass,
                ];

                if(count($insertData) >= 500){
                    DetectorSignal::query()->insert($insertData);
                    $insertData = [];
                }

            }
        }

        if(!empty($insertData)){
            DetectorSignal::query()->insert($insertData);
            $insertData = [];
        }

        die;
    }

    public function calculateSignalScore($rssi = null, $csq = null, $rsrq = null, $rsrp = null)
    {
        $weightList = [];
        $weightTotal = 0;

        if(!empty($rssi)){
            if($rssi < -110){
                return 0;
            }
            $weightList[] = [
                'weight' => 25,
                'data' => ($rssi - (-120)) / ((-30) - (-120))
            ];

            $weightTotal+=25;
        }

        if(!empty($csq)){
            if($csq < 25){
                return 0;
            }
            $weightList[] = [
                'weight' => 30,
                'data' => $csq / 31
            ];

            $weightTotal+=30;
        }

        if(!empty($rsrq)){
            if($rsrq < -25){
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

            if($rsrp < -110){
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

        return round($score,2);
    }

    public function getOneNetCommand($params)
    {
        return [
            [
                'identifier' => 'cmd',
                'params' => [
                    'muffling' => 1
                ]
            ]
        ];
    }
}
