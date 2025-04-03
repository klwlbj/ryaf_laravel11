<?php

namespace App\Console\Commands;

use App\Http\Library\YunChuang\YunChuangUtil;
use App\Http\Logic\ResponseLogic;
use App\Http\Logic\ToolsLogic;
use App\Models\SimulateTask;
use App\Models\SmokeDetector;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HandleFakeHeartBeat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:handle-fake-heartbeat';

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

        #如果处理中超过100个  直接返回
        if(SimulateTask::query()->where(['sita_status' => 1])->count() >= 70){
            return true;
        }

        #获取要处理的数据
        $list = SimulateTask::query()
            ->where('sita_status','=',0)
            ->where(function (Builder $q){
                $q->orWhereRaw("DATE_FORMAT(sita_fake_heart_beat, '%Y-%m-%d %H:%i') = DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i')")
                    ->orWhere('sita_fake_heart_beat','<',date('Y-m-d H:i:s'));
            })->limit(25)->get()->toArray();

//        echo count($list);

        $ids = array_column($list,'sita_id');

        #置为执行中状态
        SimulateTask::query()->whereIn('sita_id',$ids)->update(['sita_status' => 1]);
//        print_r($list);die;



        $sucIds = [];
        $failIds = [];
        $notHandle = [];

        $isPushYunChuang = $this->isPushYunChuang();

        if($isPushYunChuang){
            $token = Cache::get('yun_chuang_token');
            if(empty($token)){
                try {
                    $token = YunChuangUtil::getToken();
                    Cache::set('yun_chuang_token',$token,60*60);
                }catch (\Exception $e) {
                    $this->setErrorCount();
                    SimulateTask::query()->whereIn('sita_id',$ids)->update(['sita_status' => 0]);
                    ToolsLogic::writeLog('token获取失败' . $e->getMessage() .$e->getLine(),'handleFakeHeartbeat');
                    return false;
                }

            }
        }
//        print_r($token);die;
        foreach ($list as $key => $value){
            try {
                $data = ToolsLogic::jsonDecode($value['sita_data']);

//            ToolsLogic::writeLog( $value['sita_imei'] .' 更新心跳:' . $data['heartbeat'],'handleFakeHeartbeat');
                if(SmokeDetector::query()->where(['smde_imei' => $value['sita_imei']])->update(['smde_fake_heart_beat' => $value['sita_fake_heart_beat']]) === false){
                    $failIds[] = $value['sita_id'];
                    continue;
                }

                if($isPushYunChuang){
                    if (!empty($value['sita_yunchuang_id'])) {

                        #如果是新增  推送巡检状态
                        if (isset($data['type']) && $data['type'] == 'add') {
                            $onlineResp = YunChuangUtil::updateOnlineStatus($token, $value['sita_yunchuang_id'], 1);
                        }

                        $res = YunChuangUtil::updateDeviceExt($token, $value['sita_yunchuang_id'], $data['battery'], $data['signal'], $data['temperature'], $data['smokescope']);

                        $res = ToolsLogic::jsonDecode($res);
                        if ($res['success'] != 1) {
                            $failIds[] = $value['sita_id'];
                            continue;
                        }
                        ToolsLogic::writeLog('推送imei：' . $value['sita_imei'], 'handleFakeHeartbeat', $res);
                    }
                }

                $sucIds[] = $value['sita_id'];
            }catch (\Exception $e) {
                $notHandle[] = $value['sita_id'];
                $this->setErrorCount();
                ToolsLogic::writeLog('exception' . $e->getMessage() .$e->getLine() . ' imei:' . $value['sita_imei'],'handleFakeHeartbeat');
            }
        }

        if(!empty($sucIds)){
            SimulateTask::query()->whereIn('sita_id',$sucIds)->update(['sita_status' => 2]);
        }

        if(!empty($failIds)){
            SimulateTask::query()->whereIn('sita_id',$failIds)->update(['sita_status' => 3]);
        }

        if(!empty($notHandle)){
            SimulateTask::query()->whereIn('sita_id',$notHandle)->update(['sita_status' => 0]);
        }


        echo '运行结束';
        return true;
    }

    public function setErrorCount(){
        $count = Cache::get('yun_chuang_err_count') ?: 0;
        Cache::set('yun_chuang_err_count',$count + 1,60*10);
    }

    public function isPushYunChuang()
    {
        $count = Cache::get('yun_chuang_err_count') ?: 0;
        if(empty($count)){
            return true;
        }

        if($count > 100){
            return false;
        }

        return true;
    }
}
