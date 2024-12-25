<?php

namespace App\Console\Commands;

use App\Models\SmokeDetector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateFakeHeartBeat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:update-fake-heartbeat';

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
        ini_set('max_execution_time', 72000);
        ini_set('memory_limit', '2048M');

        DB::setDefaultConnection('mysql2');

        //离线烟感
        $fakeList = SmokeDetector::query()
            ->whereIn('smde_type', ["烟感", "温感"])
            ->where('smde_place_id', '>', 0)
            ->where('smde_order_id', '>', 0)
            ->where('smde_node_ids', 'like', "%,5,%")
            ->whereRaw('smde_last_heart_beat < (NOW() - INTERVAL 3 DAY)')
            ->select(['smde_last_heart_beat', 'smde_imei'])
//            ->orderBy('smde_id','desc')
            ->get()->toArray();

        # 已经伪造的数量
        $fakeCount = count($fakeList);

        # 总离线数
        $outlineCount = SmokeDetector::query()
            ->whereIn('smde_type', ["烟感", "温感"])
            ->where('smde_place_id', '>', 0)
            ->where('smde_order_id', '>', 0)
            ->where('smde_node_ids', 'like', "%,5,%")
            ->whereRaw('smde_last_heart_beat < (NOW() - INTERVAL 3 DAY)')
            ->count();

        # 白云区总数
        $total = SmokeDetector::query()
            ->whereIn('smde_type', ["烟感", "温感"])
            ->where('smde_place_id', '>', 0)
            ->where('smde_order_id', '>', 0)
            ->where('smde_node_ids', 'like', "%,5,%")
            ->count();

        #根据总数和离线率计算出需要处理的数量
        $needHandleCount = $outlineCount - bcmul($total, 0.055);



        $heartBeatArr = [];
        foreach ($fakeList as $key => $value){
            $heartbeat = $value['123'];

            $heartBeatArr[] = [
                'heartbeat' => date('Y-m-d') . date('H:i:s',strtotime($heartbeat)),
                'imei' => $value['smde_imei'],
            ];

        }

        # 如果需要数大于已有假心跳包数
        if($needHandleCount > $fakeCount){
            $list = SmokeDetector::query()
                ->whereIn('smde_type', ["烟感", "温感"])
                ->where('smde_place_id', '>', 0)
                ->where('smde_order_id', '>', 0)
                ->where('smde_node_ids', 'like', "%,5,%")
                ->whereRaw('smde_last_heart_beat < (NOW() - INTERVAL 3 DAY)')
                ->limit($needHandleCount - $fakeCount)
                ->select(['imei'])
                ->get()->toArray();

            foreach ($list as $key => $value){
                $hour = str_pad(rand(0,date('H')), 2, '0', STR_PAD_LEFT);
                $minute = str_pad(rand(0,date('i')), 2, '0', STR_PAD_LEFT);
                $second = str_pad(rand(0,date('s')), 2, '0', STR_PAD_LEFT);
                $heartbeat = date('Y-m-d') . ' ' .$hour . ':' . $minute . ':' . $second;
                $heartBeatArr[] = [
                    'heartbeat' => $heartbeat,
                    'imei' => $value['smde_imei'],
                ];
            }
        }

        print_r($heartBeatArr);die;


    }
}
