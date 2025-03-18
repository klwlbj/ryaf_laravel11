<?php

namespace App\Console\Commands;

use App\Http\Library\YunChuang\YunChuangUtil;
use App\Http\Logic\ToolsLogic;
use App\Models\Node;
use App\Models\Order;
use App\Models\Place;
use App\Models\SmokeDetector;
use App\Models\Token;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class YunChuangPush extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:yunchuang-push';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    public $exitsUnitArr = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        DB::setDefaultConnection('mysql2');

        ini_set( 'max_execution_time', 72000 );
        ini_set( 'memory_limit', '2048M' );

//        $token = Token::query()->where(['token_name' => 'yunchuang'])->value('token_value') ?: '';

        $token = Cache::get('yun_chuang_token');
        if(empty($token)){
            $token = YunChuangUtil::getToken();
            Cache::set('yun_chuang_token',$token,60*60);
        }

        $deviceList = SmokeDetector::query()
            ->leftJoin('place','place.plac_id','=','smoke_detector.smde_place_id')
            ->where(['smde_yunchuang_id' => 0])
            ->where('smde_node_ids','like',"%,5,40,%")
            ->where('plac_yunchuang_id','>',0)
            ->where('smde_place_id','>',0)
//            ->where('smde_imei','=','861050049858742')
            ->select([
                'smde_id',
                'smde_imei',
                'plac_yunchuang_id',
                'smde_yunchuang_id',
                'smde_model_name',
                'smde_position',
                'smde_brand_name',
                'plac_address',
                'plac_node_ids',
                'plac_standard_address',
                'plac_standard_address_not_exist',
                'plac_standard_address_room',
                'smde_type',
                'plac_id',
                'plac_name',
                'plac_lng',
                'plac_lat',
                'plac_type',
                'smde_last_nb_module_battery',
                'smde_last_signal_intensity',
                'smde_last_temperature',
                'smde_last_smokescope',
                'plac_generic_address',
                'plac_user_id',
            ])->get()->toArray();

//        $placeList = Place::query()
//            ->leftJoin('user','user_id','=','plac_user_id')
//            ->leftJoin('order','order_id','=','plac_order_id')
//            ->where(['plac_yunchuang_id' => 0])
//            ->where('order_status','=','交付完成')
//            ->where('plac_node_ids','like','%,5,40,%')
//            ->whereRaw("COALESCE(user_name,'') <> '' and COALESCE(user_mobile,'') <> ''")
//            ->select(['place.*'])
//            ->get()->toArray();

        $success = 0;

//        print_r(count($deviceList));die;

        foreach ($deviceList as $key => $value){
//            $this->updateOnlineStatus($token,$value['smde_yunchuang_id']);
            $resp = $this->regDevice($token,$value);
//                $resp = $this->regUnit($token,$value);
//            $resp = $this->pushMonitoring($token,$value);
//            if(empty($resp)){
//                continue;
//            }

            $success++;
        }
        print_r($success);die;
    }

    public function fillUserData()
    {
        $list = Order::query()
            ->leftJoin('place','plac_order_id','=','order_id')
            ->leftJoin('user','user_id','=','order_user_id')
            ->where('order_node_ids','like',"%,5,40,%")
            ->where('plac_yunchuang_id','=',0)
            ->where('order_status','=','交付完成')
            ->whereRaw("COALESCE(user_name,'') = '' and COALESCE(user_mobile,'') <> ''")
            ->select(['plac_id','order_user_name','order_user_mobile','user_name','user_mobile'])
            ->get()->toArray();

        foreach ($list as $key => $value){
            User::query()->where(['user_mobile' => $value['order_user_mobile']])->update(['user_name' => $value['order_user_name']]);
        }
    }

    public function pushMonitoring($token,$data)
    {
        $res = YunChuangUtil::updateDeviceExt($token,$data['smde_yunchuang_id'],$data['smde_last_nb_module_battery'] ?? '',$data['smde_last_signal_intensity'] ?? '',$data['smde_last_temperature'] ?? '',$data['smde_last_smokescope'] ?? '');

        return ToolsLogic::jsonDecode($res);
    }


    public function regUnit($token,$value)
    {
        if ($value['plac_yunchuang_id'] > 0){
            return false;
        }

        $type = YunChuangUtil::getYunChuangUnitType( $value['plac_type'] );
        $townId = Node::getYunchuangTownId($value['plac_node_ids']);

        if(empty($value['plac_user_id'])){
            return false;
        }

        if(empty($value['plac_lng'])){
            return false;
        }
        $user = User::query()->where(['user_id' => $value['plac_user_id']])->first();

        if ( $user->user_mobile == "" || $user->user_name == "" ){
            return false;
        }
//        print_r(123);die;
        $chargers = [ ( object ) [ "name" => $user->user_name, "mobile" => $user->user_mobile ] ];
//	echo json_encode( $chargers );
//	return;

        $rustId = Node::getYunchuangRustId2($value['plac_node_ids']);

        $resp = YunChuangUtil::regUnit( $token, $townId, $rustId, $value['plac_id'], $value['plac_name'], $type, $value['plac_address'], $value['plac_lng'], $value['plac_lat'], $chargers );
        $resp = ToolsLogic::jsonDecode($resp);
        ToolsLogic::writeLog('注册单位res：','yunchuang',$resp);
        if(empty($resp)){
            return false;
        }

        $unitId = null;

        if(isset($resp['success']) && !$resp['success']){
            return false;
        }

        if(isset($resp['code']) && $resp['code'] == 10001){
            $unitId = $resp['ext']['unitId'];
        }

        if(isset($resp['content']['id']) && !empty($resp['content']['id'])){
            $unitId = $resp['content']['id'];
        }

        if(empty($unitId)){
            return false;
        }

        Place::query()->where(['plac_id' => $value['plac_id']])->update(['plac_yunchuang_reg_resp' => ToolsLogic::jsonEncode($resp),'plac_yunchuang_id' => $unitId]);

        $resp['unit_id'] = $unitId;
        return $resp;
    }


    public function regDevice($token,$value)
    {
        if ($value['smde_yunchuang_id']){
            return false;
        }

        if(empty($value['smde_position'])){
            $value['smde_position'] = '天花板';
        }

        $ext =  (object) [
            "400001" => [
                "monitorType" => "400001", //模拟量类型，电量
                "monitorUnit" => "%", //模拟量单位
                "monitorValue" => $value['smde_last_nb_module_battery'] != "" ? $value['smde_last_nb_module_battery'] : 100, //模拟量值
            ],
            "400002" => [
                "monitorType" => "400002", //模拟量类型，信号
                "monitorUnit" => "dB", //模拟量单位
                "monitorValue" => $value['smde_last_signal_intensity'], //模拟量值
            ],
            "400006" => [
                "monitorType" => "400006", //模拟量类型，温度
                "monitorUnit" => "℃", //模拟量单位
                "monitorValue" => $value['smde_last_temperature'] != "" ? round( $value['smde_last_temperature'] / 100, 2 ) : 0, //模拟量值
            ],
            "400013" => [
                "monitorType" => "400013", //模拟量类型，烟雾浓度
                "monitorUnit" => "dB/m", //模拟量单位
                "monitorValue" => $value['smde_last_smokescope'] != "" ? round( $value['smde_last_smokescope'] / 100, 2 ) : 0, //模拟量值
            ],
        ];

        $provider = 180003;
        if ( $value['smde_brand_name'] == "六瑞" ) {
            $provider = 180006;
        }
        $net_type = "nb-lot";
        if ( $value['smde_brand_name'] == "六瑞" ) {
            $net_type = "4g";
        }


        $standardAddress = $value['plac_standard_address'];

        $standardAddressRoom = $value['plac_standard_address_room'];

        if ( $value['plac_standard_address_not_exist'] == 1 ) {
            $standardAddress = $value['plac_address'];
            if ( $standardAddressRoom == "" ) {
                $standardAddressRoom = $value['plac_address'];
            }
        }

        #如果单位云创id等于0  先注册单位
        if($value['plac_yunchuang_id'] == 0){
            if(isset($this->exitsUnitArr[$value['plac_id']])){
                $value['plac_yunchuang_id'] = $this->exitsUnitArr[$value['plac_id']];
            }else{
                $unitRes = $this->regUnit($token,$value);
                if(!$unitRes){
                    return false;
                }

                $value['plac_yunchuang_id'] = $unitRes['unit_id'] ?? 0;
            }
        }

        if(empty($value['plac_yunchuang_id'])){
            return false;
        }

        $this->exitsUnitArr[$value['plac_id']] = $value['plac_yunchuang_id'];

        $resp = YunChuangUtil::regDevice( $token, $value['smde_id'], $value['smde_imei'], $value['smde_model_name'], $value['plac_yunchuang_id'], $value['smde_type'] . $value['smde_imei'], $provider, $net_type, $value['plac_lng'], $value['plac_lat'], $ext, $standardAddress, $value['plac_generic_address'], $standardAddressRoom, $value['smde_position'] );

        $resp = ToolsLogic::jsonDecode($resp);

        ToolsLogic::writeLog('注册设备res：','yunchuang',$resp);

        if(empty($resp)){
            return false;
        }

        $deviceId = null;
//        print_r($resp);die;
        if(isset($resp['code']) && $resp['code'] == 10002){
            $deviceId = $resp['ext']['deviceId'];
        }


        if(isset($resp['content']['id']) && !empty($resp['content']['id'])){
            $deviceId = $resp['content']['id'];
        }

        if(empty($deviceId)){
            return false;
        }

        SmokeDetector::query()->where(['smde_id' => $value['smde_id']])->update(['smde_yunchuang_reg_resp' => ToolsLogic::jsonEncode($resp),'smde_yunchuang_id' => $deviceId]);

        $onlineResp = $this->updateOnlineStatus($token, $deviceId, 1 );
        YunChuangUtil::updateDeviceExt($token, $deviceId, $value['smde_last_nb_module_battery'], $value['smde_last_signal_intensity'], $value['smde_last_temperature'], $value['smde_last_smokescope']);
        return $resp;
    }

    public function updateOnlineStatus($token,$deviceId)
    {
        $onlineResp = YunChuangUtil::updateOnlineStatus( $token, $deviceId, 1 );
        ToolsLogic::writeLog('更新在线状态res：','yunchuang',$onlineResp);
        return ToolsLogic::jsonDecode($onlineResp);
    }
}
