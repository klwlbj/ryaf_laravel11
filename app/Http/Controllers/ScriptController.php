<?php

namespace App\Http\Controllers;

use App\Http\Logic\ToolsLogic;
use App\Models\Place;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ScriptController
{
    public function pushUnits(Request $request)
    {
        ini_set( 'max_execution_time', 7200 );


        $client   = new Client(['verify' => false]);

        $list = place::query()
            ->leftJoin('user','user.user_id','=','place.plac_user_id')
            ->leftJoin('node','node.node_id','=','place.plac_node_id')
            ->whereIn('plac_id',[113662])
            ->whereIn('plac_node_id',[
            23
            ,58
            ,83
            ,117
            ,119
            ,120
            ,121
            ,122
            ,123
            ,124
            ,125
            ,126
            ,127
            ,128
            ,129
            ,196
            ,222
            ,223
            ,319
            ,339
            ,346
            ,84
            ,320
            ,321
            ,322
            ,323
            ,324
            ,325
            ,326
            ,327
            ,328
            ,329
            ,330
        ])->select(['plac_id','plac_type','plac_name','plac_address','plac_lng','plac_lat','user_name','user_mobile','node_name'])->get()->toArray();

//        print_r($list);die;
        foreach ($list as $key => $value){
            $params = [
                'id' => $value['plac_id'],
                'unitName' => $value['plac_name'],
                'address' => $value['plac_address'],
                'unitType' => $this->getUnitType($value['plac_type']),
                'unitNature' => $this->getUnitNature($value['plac_type']),
                'mapType' => 1,
                'phoneNum' => $value['user_mobile'],
                'pointX' => $value['plac_lng'],
                'pointY' => $value['plac_lat'],
                'regionCode' => $this->getRegionCode($value['node_name']),
            ];
//            print_r($params);die;
            $response = $client->post(
//                "http://test.crzfxjzn.com/hikvision/units/update",
                "http://test.crzfxjzn.com/hikvision/units/add",
                [
                'headers' => [

                ],
                'json'    => (object)$params, // 将关联数组转换为 JSON 对象,PHP空数组转空对象
            ]);
            print_r(ToolsLogic::jsonDecode($response->getBody()));die;
            ToolsLogic::writeLog($value['plac_id'] . ' res:','jg',ToolsLogic::jsonDecode($response->getBody()));
        }
        echo '推送成功';
//        $params = [
//            "id" => "114529",
//            "unitName" => "如约测试",
//            "address" => "钱大妈江高配送中心一楼C区",
//            "unitType" => "5",
//            "unitNature" => "99",
//            "mapType" => "1",
//            "phoneNum" => "18680441997",
//            "pointX" => "113.22596",
//            "pointY" => "23.258282",
//            "regionCode" => "431",
//        ];



//        $response = $client->post("http://test.crzfxjzn.com/hikvision/units/delete", [
//            'headers' => [
//
//            ],
//            'json'    => (object)[
//                'id' => '114529',
////                'imei' => '865118076532179',
//            ], // 将关联数组转换为 JSON 对象,PHP空数组转空对象
//        ]);
//
//        print_r(ToolsLogic::jsonDecode($response->getBody()));die;
    }

    public function getUnitType($value)
    {
        return match ($value) {
            '出租屋/民宿', '出租屋', '出租房' => 5,
            '商铺', '厂房', '办公室' => 1,
            default => 99,
        };
    }

    public function getUnitNature($value)
    {
        return match ($value) {
            '出租屋/民宿', '出租屋', '出租房' => 15,
            '厂房' => 13,
            '商铺' => 7,
            '办公室' => 5,
            default => 99,
        };
    }

    public static $cityCode = [
        '江高镇'   => 1000005,
        '江兴社区'  => 410,
        '高塘社区'  => 411,
        '松岗社区'  => 412,
        '河心洲社区' => 413,
        '江华社区'  => 414,
        '广北社区'  => 415,
        '金沙社区'  => 416,
        '神山社区'  => 417,
        '石龙社区'  => 418,
        '南山社区'  => 419,
        '塘贝村'   => 420,
        '水沥村'   => 421,
        '长岗村'   => 422,
        '新楼村'   => 423,
        '双岗村'   => 424,
        '茅山村'   => 425,
        '蓼江村'   => 426,
        '小塘村'   => 427,
        '何布村'   => 428,
        '大田村'   => 429,
        '南岗村'   => 430,
        '珠江村'   => 431,
        '江村村'   => 432,
        '大龙头村'  => 433,
        '泉溪村'   => 434,
        '叶边村'   => 435,
        '沙溪村'   => 436,
        '勤星村'   => 437,
        '大岭村'   => 438,
        '郭塘村'   => 439,
        '沙龙村'   => 440,
        '聚龙村'   => 441,
        '大石岗村'  => 442,
        '杨山村'   => 443,
        '鹤岗村'   => 444,
        '硖石村'   => 445,
        '朗头村'   => 446,
        '两上村'   => 447,
        '两下村'   => 448,
        '五丰村'   => 449,
        '中八村'   => 450,
        '雄丰村'   => 451,
        '南浦村'   => 452,
        '罗溪村'   => 453,
        '井岗村'   => 454,
    ];

    public function getRegionCode($nodeName)
    {
        $nodeName = match ($nodeName) {
            '神山消防救援专职队' => '神山社区',
            '五丰村委' => '五丰村',
            '朱江村委' => '珠江村',
            '茅山村委' => '茅山村',
            '何㘵村委','何㘵村新庄经济合作社','何㘵村上庄经济合作社','何㘵村南一经济合作社','何㘵村南二经济合作社','何㘵村叶家经济合作社','何㘵村大巷经济合作社','何㘵村细巷经济合作社','何㘵村蔡南经济合作社','何㘵村蔡北经济合作社','何㘵村东头经济合作社','何㘵村西边经济合作社' => '何布村',
            default => $nodeName,
        };
//        print_r($nodeName);
        return self::$cityCode[$nodeName] ?? '1000005';
    }
}
