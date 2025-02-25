<?php

namespace App\Console\Commands;

use App\Http\Library\YunChuang\YunChuangUtil;
use App\Http\Logic\ToolsLogic;
use App\Models\SmokeDetector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PushHeartBeat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:push-heartbeat';

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

        $time = date('H:i');

        if(!$this->isRun()){
            return false;
        }

        $deviceList = SmokeDetector::query()
            ->where('smde_yunchuang_id','>',0)
            ->where('smde_place_id', '>', 0)
            ->where('smde_order_id','>',0)
            ->where('smde_fake','=',0)
            ->whereIn('smde_type',['烟感','温感'])
            ->where('smde_node_ids', 'like', "%,5,%")
            ->whereRaw("DATE_FORMAT(smde_last_heart_beat, '%Y-%m-%d %H:%i') = DATE_FORMAT((NOW() - INTERVAL 1 MINUTE), '%Y-%m-%d %H:%i')")
            ->select([
                'smde_yunchuang_id',
                'smde_imei',
                'smde_last_smokescope',
                'smde_last_temperature',
                'smde_last_nb_module_battery',
                'smde_last_signal_intensity',
                'smde_last_heart_beat'
            ])->get()->toArray();


        if($this->isPushYunChuang()){
            $token = Cache::get('yun_chuang_token');
            if(empty($token)){
                try {
                    $token = YunChuangUtil::getToken();
                    Cache::set('yun_chuang_token',$token,60*60);
                }catch (\Exception $e) {
                    $this->setErrorCount();
                    $this->clearProgress();
                    ToolsLogic::writeLog('token获取失败' . $e->getMessage() .$e->getLine(),'pushHeartbeat');
                    return false;
                }

            }
        }else{
            $this->clearProgress();
            return true;
        }

        foreach ($deviceList as $key => $value){
            try {
                $res = YunChuangUtil::updateDeviceExt($token, $value['smde_yunchuang_id'], $value['smde_last_nb_module_battery'], $value['smde_last_signal_intensity'], $value['smde_last_temperature'], $value['smde_last_smokescope']);

                $res = ToolsLogic::jsonDecode($res);

                ToolsLogic::writeLog($time . ' 推送imei：' . $value['smde_imei'], 'pushHeartbeat', $res);
            }catch (\Exception $e) {
                $this->setErrorCount();
                ToolsLogic::writeLog('exception' . $e->getMessage() .$e->getLine() . ' imei:' . $value['smde_imei'],'pushHeartbeat');
            }
        }
        $this->clearProgress();
        return true;
    }

    public function setErrorCount(){
        $count = Cache::get('yun_chuang_err_count') ?: 0;
        Cache::set('yun_chuang_err_count',$count + 1,60*10);
    }

    public function isPushYunChuang()
    {
//        return true;
        $count = Cache::get('yun_chuang_err_count') ?: 0;
        if(empty($count)){
            return true;
        }

        if($count > 10){
            return false;
        }

        return true;
    }

    public function isRun()
    {
//        return true;
        $progressCount = Cache::get('yun_progress_count') ?: 0;
        if($progressCount >= 5){
            return false;
        }

        $progressCount += 1;
        ToolsLogic::writeLog("添加进程；进程数：" . $progressCount, 'pushHeartbeat');
        Cache::set('yun_progress_count',$progressCount,60*60);

        return true;
    }

    public function clearProgress()
    {
        $progressCount = Cache::get('yun_progress_count') ?: 0;
        if(empty($progressCount)){
            return true;
        }

        $progressCount -= 1;
        ToolsLogic::writeLog("消耗进程；进程数：" . $progressCount, 'pushHeartbeat');
        Cache::set('yun_progress_count',$progressCount,60*60);

        return true;
    }

}
