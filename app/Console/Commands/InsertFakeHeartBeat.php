<?php

namespace App\Console\Commands;

use App\Http\Library\YunChuang\YunChuangUtil;
use App\Http\Logic\ToolsLogic;
use App\Models\SimulateTask;
use App\Models\SmokeDetector;
use App\Models\Token;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;

class InsertFakeHeartBeat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:insert-fake-heartbeat';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected $host = 'http://ryaf-laravel11.com';

    protected $concurrency = 4;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ini_set('max_execution_time', 72000);
        ini_set('memory_limit', '2048M');

        DB::setDefaultConnection('mysql2');

        echo '运行开始' . "\n";
        # 总离线数
        $outlineCount = SmokeDetector::query()
            ->whereIn('smde_type', ["烟感", "温感"])
            ->where('smde_place_id', '>', 0)
            ->where('smde_order_id','>',0)
            ->where('smde_fake','=',0)
            ->where('smde_node_ids', 'like', "%,5,%")
            ->whereRaw('smde_last_heart_beat < (NOW() - INTERVAL 2 DAY)')
            ->count();

        # 白云区总数
        $total = SmokeDetector::query()
            ->whereIn('smde_type', ["烟感", "温感"])
            ->where('smde_place_id', '>', 0)
            ->where('smde_order_id','>',0)
            ->where('smde_fake','=',0)
            ->where('smde_node_ids', 'like', "%,5,%")
            ->count();

//        $token = Token::query()->where(['token_name' => 'yunchuang'])->value('token_value') ?: '';

        #根据总数和离线率计算出需要处理的数量
        $needHandleCount = $outlineCount - bcmul($total, rand(60,70) / 1000);
//        print_r($total);die;
        #如果真实心跳包恢复了  则清楚造心跳包
        SmokeDetector::query()->whereRaw("smde_last_heart_beat > smde_fake_heart_beat")->update(['smde_fake_heart_beat' => null]);

        //造心跳包烟感
        $fakeList = SmokeDetector::query()
            ->whereIn('smde_type', ["烟感", "温感"])
            ->where('smde_place_id', '>', 0)
            ->where('smde_order_id','>',0)
            ->where('smde_fake','=',0)
//            ->whereRaw("smde_last_heart_beat < smde_fake_heart_beat")
            ->where('smde_node_ids', 'like', "%,5,%")
            ->whereNotNull('smde_fake_heart_beat')
            ->select(['smde_last_heart_beat', 'smde_imei','smde_fake_heart_beat','smde_yunchuang_id'])
            ->limit($needHandleCount)
//            ->orderBy('smde_id','desc')
            ->get()->toArray();

        # 已经伪造的数量
        $fakeCount = count($fakeList);

        #获取一条真实数据参照
        $realData = SmokeDetector::query()->where(['smde_model_name' => 'HM-618PH-NB'])
            ->whereRaw("smde_last_heart_beat > (NOW() - INTERVAL 1 DAY)")
            ->orderBy('smde_last_heart_beat','desc')
            ->select([
                'smde_last_temperature',
                'smde_last_nb_module_battery',
                'smde_last_signal_intensity'
            ])->first();

        if($realData){
            $realData = $realData->toArray();
        }else{
            $realData = [];
        }


//        print_r($needHandleCount);die;
        $heartBeatArr = [];

        foreach ($fakeList as $key => $value){
            $heartbeat = $value['smde_fake_heart_beat'];

            #如果时间大于目前的  跳过
            if(strtotime($heartbeat) > time()){
//                $heartBeatArr[] = [
//                    'update' => false,
//                    'heartbeat' => $heartbeat,
//                    'imei' => $value['smde_imei'],
//                    'yunchuang_id' => $value['smde_yunchuang_id']
//                ];
                continue;
            }

            $newHeartbeat = date('Y-m-d') . ' ' . date('H:i:s',strtotime($heartbeat) + rand(2,10));
            if(strtotime($newHeartbeat) < time()){
                #如果时间少于今天当前时间 取数明天
                $newHeartbeat = date('Y-m-d', strtotime('+1 days')) . ' ' . date('H:i:s',strtotime($heartbeat));
            }

            $heartBeatArr[] = [
                'type' => 'update',
                'heartbeat' => $newHeartbeat . '.' . date('Y'),
                'imei' => $value['smde_imei'],
                'yunchuang_id' => $value['smde_yunchuang_id'],
                'battery' => rand(70,90),
                'signal' => rand(-80,-99),
                'temperature' => ($realData['smde_last_temperature'] ?? 2200) + (rand(-2,2) * 100),
                'smokescope' => 0,
            ];

        }

        # 如果需要数大于已有假心跳包数
        if($needHandleCount > $fakeCount){
            $list = SmokeDetector::query()
                ->whereIn('smde_type', ["烟感", "温感"])
                ->where('smde_place_id', '>', 0)
                ->where('smde_order_id', '>', 0)
                ->where('smde_node_ids', 'like', "%,5,%")
                ->whereRaw('smde_last_heart_beat < (NOW() - INTERVAL 2 DAY)')
                ->whereNull('smde_fake_heart_beat')
                ->limit($needHandleCount - $fakeCount)
                ->select(['smde_imei','smde_yunchuang_id'])
                ->orderBy(DB::raw("RAND()"))
                ->get()->toArray();

            foreach ($list as $key => $value){
                $hour = str_pad(rand(0,date('H')), 2, '0', STR_PAD_LEFT);
                $minute = str_pad(rand(0,date('i')), 2, '0', STR_PAD_LEFT);
                $second = str_pad(rand(0,date('s')), 2, '0', STR_PAD_LEFT);
                $heartbeat = date('Y-m-d', strtotime('+1 days')) . ' ' .$hour . ':' . $minute . ':' . $second;
                $heartBeatArr[] = [
                    'type' => 'add',
                    'heartbeat' => $heartbeat . '.' . date('Y'),
                    'imei' => $value['smde_imei'],
                    'yunchuang_id' => $value['smde_yunchuang_id'],
                    'battery' => rand(70,90),
                    'signal' => rand(-80,-99),
                    'temperature' => ($realData['smde_last_temperature'] ?? 2200) + (rand(-2,2) * 100),
                    'smokescope' => 0,
                ];
            }
        }
        echo '需插入: ' . count($heartBeatArr) . '个数据' . "\n";
//        print_r(count($heartBeatArr));die;
        $pushData = [];
        foreach ($heartBeatArr as $key => $value) {
            $pushData[] = [
                'sita_imei' => $value['imei'],
                'sita_fake_heart_beat' => $value['heartbeat'],
                'sita_yunchuang_id' => $value['yunchuang_id'],
                'sita_data' => ToolsLogic::jsonEncode([
                    'battery' => $value['battery'],
                    'signal' => $value['signal'],
                    'temperature' => $value['temperature'],
                    'smokescope' => $value['smokescope'],
                    'type' => $value['type'],
                ]),
                'sita_status' => 0
            ];

            if (count($pushData) >= 500){
                SimulateTask::query()->insert($pushData);
                $pushData = [];
            }

//            if (count($pushData) >= $this->concurrency) {
//                Http::pool(function (Pool $pool) use ($pushData) {
//                    foreach ($pushData as $key => $item) {
//                        $pool->post($this->host . '/api/yunChuang/updateDevice', [
//                            'deviceId' => $item['yunchuang_id'],
//                            'data' => ToolsLogic::jsonEncode($item)
//                        ]);
//                    }
//                });
//
//                $pushData = [];
//            }
        }

        if(!empty($pushData)){
            SimulateTask::query()->insert($pushData);
            $pushData = [];
        }

        echo '运行结束' . "\n";
    }
}
