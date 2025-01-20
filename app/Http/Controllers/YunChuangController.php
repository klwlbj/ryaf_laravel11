<?php

namespace App\Http\Controllers;

use App\Http\Library\YunChuang\YunChuangUtil;
use App\Http\Logic\ResponseLogic;
use App\Http\Logic\ToolsLogic;
use App\Models\SmokeDetector;
use App\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class YunChuangController
{
    public function updateDevice(Request $request)
    {
        $params = $request->all();

        $validate = Validator::make($params, [
            'deviceId' => 'required',
            'data' => 'required',
        ],[
            'deviceId.required' => '云创id为空',
            'data.required' => '数据不得为空',
        ]);

        if($validate->fails())
        {
            return ResponseLogic::apiErrorResult($validate->errors()->first());
        }
//        ToolsLogic::writeLog('device:' . $params['deviceId'] . '开始' . date('Y-m-d H:i:s'),'updateFakeHeartbeat');
//        sleep(2);
//        ToolsLogic::writeLog('device:' . $params['deviceId'] . '结束' . date('Y-m-d H:i:s'),'updateFakeHeartbeat');

        $data = ToolsLogic::jsonDecode($params['data']);
        DB::setDefaultConnection('mysql2');
        if($data['update']){
            ToolsLogic::writeLog($data['imei'] .' 更新心跳:' . $data['heartbeat'],'updateFakeHeartbeat');
            SmokeDetector::query()->where(['smde_imei' => $data['imei']])->update(['smde_fake_heart_beat' => $data['heartbeat']]);
        }


        if(!empty($data['yunchuang_id'])){
//            $token = Token::query()->where(['token_name' => 'yunchuang'])->value('token_value') ?: '';
            $token = Cache::get('yun_chuang_token');
            if(empty($token)){
                $token = YunChuangUtil::getToken();
                Cache::set('yun_chuang_token',$token,60*60);
            }

            #如果是新增  推送巡检状态
            if(isset($data['type']) && $data['type'] == 'add'){
                $onlineResp = YunChuangUtil::updateOnlineStatus( $token, $data['yunchuang_id'], 1 );
            }

            ToolsLogic::writeLog('开始推送监控数据 imei:' . $data['imei'],'updateFakeHeartbeat');
                $res = YunChuangUtil::updateDeviceExt($token,$data['yunchuang_id'],rand(60,80),rand(-80,-100),rand(1500,1800),'');

            $res = ToolsLogic::jsonDecode($res);

            ToolsLogic::writeLog('推送imei：' . $data['imei'],'updateFakeHeartbeat',$res);
        }


        return ResponseLogic::apiResult(0,'ok',[]);
    }
}
