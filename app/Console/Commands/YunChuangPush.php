<?php

namespace App\Console\Commands;

use App\Http\Library\YunChuang\YunChuangUtil;
use App\Http\Logic\ToolsLogic;
use App\Models\Node;
use App\Models\Place;
use App\Models\SmokeDetector;
use App\Models\Token;
use App\Models\User;
use Illuminate\Console\Command;
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

        $token = Token::query()->where(['token_name' => 'yunchuang'])->value('token_value') ?: '';

//        $deviceList = SmokeDetector::query()
//            ->leftJoin('place','place.plac_id','=','smoke_detector.smde_place_id')
//            ->where(['smde_yunchuang_id' => 0])
//            ->where('smde_node_ids','like',"%,5,26,%")
//            ->where('plac_yunchuang_id','>',0)
//            ->where('smde_place_id','>',0)
//            ->select([
//                'smde_id',
//                'smde_imei',
//                'plac_yunchuang_id',
//                'smde_yunchuang_id',
//                'smde_model_name',
//                'smde_position',
//                'smde_brand_name',
//                'plac_address',
//                'plac_node_ids',
//                'plac_standard_address',
//                'plac_standard_address_not_exist',
//                'plac_standard_address_room',
//                'smde_type',
//                'plac_id',
//                'plac_name',
//                'plac_lng',
//                'plac_lat',
//                'plac_type',
//                'smde_last_nb_module_battery',
//                'smde_last_signal_intensity',
//                'smde_last_temperature',
//                'smde_last_smokescope',
//                'plac_generic_address',
//                'plac_user_id',
//            ])->get()->toArray();

        $placeList = Place::query()
            ->where(['plac_yunchuang_id' => 0])
//            ->where('plac_node_ids','like','%,5,26,%')
                ->whereIn('plac_id',[
                187003
                ,176073
                ,176062
                ,167124
                ,166991
                ,167321
                ,167372
                ,167283
                ,167140
                ,167177
                ,167375
                ,167165
                ,167256
                ,167161
                ,167363
                ,167368
                ,167360
                ,167202
                ,167225
                ,162215
                ,162594
                ,163942
                ,163946
                ,211144
                ,163945
                ,162076
                ,169284
                ,169305
                ,169303
                ,169030
                ,169280
                ,169292
                ,169225
                ,169259
                ,169231
                ,169261
                ,168081
                ,169028
                ,169263
                ,169021
                ,169233
                ,169257
                ,169236
                ,168973
                ,172076
                ,172080
                ,169439
                ,172058
                ,169472
                ,169387
                ,169480
                ,169487
                ,172074
                ,169452
                ,172094
                ,169467
                ,169413
                ,169406
                ,169397
                ,171730
                ,171795
                ,171679
                ,171716
                ,177182
                ,170919
                ,173177
                ,169235
                ,170919
                ,177189
                ,173233
                ,171676
                ,171674
                ,171752
                ,171791
                ,171199
                ,171722
                ,171748
                ,171793
                ,171801
                ,171798
                ,173754
                ,171742
                ,177173
                ,171755
                ,171209
                ,171670
                ,171749
                ,169483
                ,169484
                ,171191
                ,171204
                ,171720
                ,171788
                ,176155
                ,174780
                ,172697
                ,172686
                ,172694
                ,173187
                ,174774
                ,174775
                ,176422
                ,173183
                ,174781
                ,172677
                ,174773
                ,172672
                ,176422
                ,174778
                ,172678
                ,174766
                ,174563
                ,174784
                ,172647
                ,174347
                ,174347
                ,174770
                ,174764
                ,176422
                ,172700
                ,175552
                ,175565
                ,175553
                ,176168
                ,175803
                ,175562
                ,175567
                ,175560
                ,175556
                ,175569
                ,174644
                ,180267
                ,178823
                ,180272
                ,188126
                ,180284
                ,188138
                ,180276
                ,188121
                ,180254
                ,180235
                ,202085
                ,180252
                ,188137
                ,180287
                ,211168
                ,180241
                ,180300
                ,178813
                ,180248
                ,187106
                ,188142
                ,188145
                ,188133
                ,188147
                ,188140
                ,178834
                ,188148
                ,211174
                ,180295
                ,180282
                ,180233
                ,178862
                ,181133
                ,187108
                ,186255
                ,188150
                ,187116
                ,187009
                ,185135
                ,187008
                ,187112
                ,187124
                ,185126
                ,187116
                ,187128
                ,188129
                ,187006
                ,187004
                ,186251
                ,188820
                ,188822
                ,188818
                ,187997
                ,187999
                ,187996
                ,187740
                ,187762
                ,187742
                ,187985
                ,187753
                ,187984
                ,187763
                ,187981
                ,187982
                ,187760
                ,187744
                ,187801
                ,187749
                ,187998
                ,187986
                ,187983
                ,187987
                ,188733
                ,188754
                ,188742
                ,188751
                ,188759
                ,188746
                ,190926
                ,191689
                ,191709
                ,191727
                ,191677
                ,191643
                ,191730
                ,191673
                ,191694
                ,191647
                ,190926
                ,197345
                ,199538
                ,199061
                ,199574
                ,199757
                ,199482
                ,199520
                ,199403
                ,199068
                ,201251
                ,199528
                ,199052
                ,199448
                ,197278
                ,199438
                ,199429
                ,199512
                ,199553
                ,199365
                ,199466
                ,199503
                ,199458
                ,199500
                ,197263
                ,199492
                ,201250
                ,199848
                ,197287
                ,199796
                ,199790
                ,199836
                ,199791
                ,197250
                ,197222
                ,199425
                ,199820
                ,199811
                ,202061
                ,202091
                ,202069
                ,202064
                ,202073
                ,202052
                ,202086
                ,202056
                ,202047
                ,202067
                ,207853
                ,206912
                ,206924
                ,206918
                ,206904
                ,202332
                ,201815
                ,202284
                ,201847
                ,202261
                ,201893
                ,202359
                ,201871
                ,201835
                ,204417
                ,204429
                ,204438
                ,204452
                ,204449
                ,206943
                ,206891
                ,206914
                ,206850
                ,206822
                ,206926
                ,206955
                ,206906
                ,206899
                ,206950
                ,206863
                ,206919
                ,206836
                ,204228
                ,204221
                ,204233
                ,211058
                ,211101
                ,211067
                ,211094
                ,211142
                ,211079
                ,211108
                ,211046
                ,211160
                ,211127
                ,211123
                ,211190
                ,211038
                ,211052
                ,211136
                ,211139
                ,211148
                ,211113
                ,211116
                ,224042
                ,224043
                ,219349
                ,220812
                ,221301
                ,211835
                ,211796
                ,211789
                ,211827
                ,211765
                ,211786
                ,211757
                ,211758
                ,211830
                ,211853
                ,211794
                ,211791
                ,211784
                ,211782
                ,211831
                ,211819
                ,211814
                ,211847
                ,211810
                ,211849
                ,211850
                ,211780
                ,211772
                ,211769
                ,211843
                ,211771
                ,211821
                ,211838
                ,211839
                ,211834
                ,211833
                ,211913
                ,211826
                ,211889
                ,211854
                ,211812
                ,211893
                ,211902
                ,211858
                ,211867
                ,211848
                ,211890
                ,211898
                ,211885
                ,211917
                ,211864
                ,211907
                ,211825
                ,211872
                ,211877
                ,211880
                ,211904
                ,211842
                ,211861
                ,220812
                ,214213
                ,219371
                ,222678
                ,222798
                ,222643
                ,222638
                ,222662
                ,222889
                ,222652
                ,222695
                ,222682
                ,222667
                ,223762
                ,223659
                ,223770
                ,223385
                ,223759
                ,223563
                ,223328
                ,222921
                ,222913
                ,222927
                ,223676
                ,223421
                ,223667
                ,222445
                ,222466
                ,222584
                ,222610
                ,222486
                ,222514
                ,222426
                ,222536
                ,222529
                ,222577
                ,222616
                ,222602
                ,222645
                ,222764
                ,222895
                ,222862
                ,222533
                ,223270
                ,222770
                ,222679
                ,222739
                ,222633
                ,222567
                ,222478
                ,222779
                ,222808
                ,222611
                ,222701
                ,222737
                ,222478
                ,222753
                ,222828
                ,222790
                ,222656
                ,223164
                ,223123
                ,222676
                ,222757
                ,223254
                ,223307
                ,222548
                ,223208
                ,222583
                ,222708
                ,223133
                ,223189
                ,223093
                ,223221
                ,223060
                ,222603
                ,222158
                ,222091
                ,222081
                ,222084
                ,222093
                ,222147
                ,222163
                ,222124
                ,222032
                ,221581
                ,221592
                ,221599
                ,222136
            ])
            ->get()->toArray();

        $success = 0;

//        print_r(count($deviceList));die;

        foreach ($placeList as $key => $value){
//            $this->updateOnlineStatus($token,$value['smde_yunchuang_id']);
//            $resp = $this->regDevice($token,$value);
            $resp = $this->regUnit($token,$value);
            if(empty($resp)){
                continue;
            }

            $success++;
        }
        print_r($success);die;
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

        return $resp;
    }

    public function updateOnlineStatus($token,$deviceId)
    {
        $onlineResp = YunChuangUtil::updateOnlineStatus( $token, $deviceId, 1 );
        ToolsLogic::writeLog('更新在线状态res：','yunchuang',$onlineResp);
        return ToolsLogic::jsonDecode($onlineResp);
    }
}
