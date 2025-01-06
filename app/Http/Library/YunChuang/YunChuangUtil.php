<?php

namespace App\Http\Library\YunChuang;


class YunChuangUtil
{
    const AppId = "dyprovider";
    const AppSecret = "d2b059b444e778bf317b75e8042bdcbf";

    const Url = "https://zhygjgpt.by.gov.cn/bfm";


    public static function getStardardAddressRooms( $dzbm ) {
        $header = "SsoToken:2bcaacd810f1dea6f17c498918ac8a00";
        $post_data = [
            "type" => "3",
            "ssmlp_dzbm" => $dzbm,
        ];
        $rooms = ZmHttpUtil::sendPost( "https://xcx.pinganbaiyun.cn/p_060_yjm/api_005_yjm/search_address_smartCity_management", $header, $post_data );
//		echo $rooms;
        return json_decode( $rooms )[0]->hits;
    }

    public static function getStandardAddressByGps( $lng, $lat ) {
        if ( $lng == "" || $lat == "" ) return [];

        $header = "SsoToken:2bcaacd810f1dea6f17c498918ac8a00";
        $post_data = [
            "type" => "0",
            "longitude" => $lng,
            "latitude" => $lat,
        ];
        $list_by_gps = ZmHttpUtil::sendPost( "https://xcx.pinganbaiyun.cn/p_060_yjm/api_005_yjm/search_address_smartCity_management", $header, $post_data );

//		return json_decode( $list_by_gps );
        return json_decode( $list_by_gps )[0]->hits;
    }
    public static function getStardardAddressByGenericAddress( $generic_address ) {
        if ( $generic_address == "" ) return [];

        $header = "SsoToken:2bcaacd810f1dea6f17c498918ac8a00";
        $post_data = [
            "type" => "2",
            "key_word" => $generic_address,
        ];
        $list_by_generic_address = ZmHttpUtil::sendPost( "https://xcx.pinganbaiyun.cn/p_060_yjm/api_005_yjm/search_address_smartCity_management", $header, $post_data );
//		return json_decode( $list_by_generic_address );
        return json_decode( $list_by_generic_address )[0]->hits;
    }


    // 发送处理报警结果
    public static function sendAlertHandle( $token, $oid, $process_result, $process_time ) {
        $url = self::Url . "/open/notify/event/handle?accessToken=" . $token;
        $data = (object) [
            "oid" => $oid, //	String		是	事件唯一流水号  同一事件，需与《3.10接受报警信息》里的oid保持一致
            "processResult" => $process_result, //	String		是	报警处理结果，请参考数据字典 //00误报;01报警已处理;02报警未处理;03测试;
            "processTime" => $process_time, //	Date		是	处理时间 格式：yyyy-MM-dd hh:mm:ss  例如：2023-06-27 11:35:05
        ];
        $resp = ZmHttpUtil::send_post_json( $url, $data );
        return $resp; //content->id
    }


    // 发送报警
    public static function sendAlert( $token, $oid, $device_id, $occurrence_time ) {
        $url = self::Url . "/open/notify/event?accessToken=" . $token;
        $data = (object) [
            "oid" => "ruyue" . $oid, //	String		是	事件唯一流水号
            "deviceId" => $device_id, //	Long		是	硬件设备id
            "eventType" => "200001", //	String		是	报警事件类型，请参考数据字典
            "occurrenceTime" => date( "Y-m-d H:i:s", strtotime( $occurrence_time ) ), //	Date		是	事件发生时间 格式：yyyy-MM-dd hh:mm:ss 例如：2023-06-27 11:35:05
        ];
        $resp = ZmHttpUtil::send_post_json( $url, $data );
        return $resp; //content->id
    }

    // 发送报警
    public static function sendAlert2( $token, $iono, $device_id ) {
        $url = self::Url . "/open/notify/event?accessToken=" . $token;

        $payload = (object) [
            "400006" => [
                "monitorType" => "400006", //模拟量类型，温度
                "monitorUnit" => "℃", //模拟量单位
                "monitorValue" => $iono->iono_temperature != "" ? round( $iono->iono_temperature / 100, 2 ) : 0, //模拟量值
            ],
            "400013" => [
                "monitorType" => "400013", //模拟量类型，烟雾浓度
                "monitorUnit" => "dB/m", //模拟量单位
                "monitorValue" => $iono->iono_smoke_scope != "" ? round( $iono->iono_smoke_scope / 100, 2 ) : 0, //模拟量值
            ],
        ];

        $data = (object) [
            "oid" => "ruyue" . $iono->iono_id, //	String		是	事件唯一流水号
            "deviceId" => $device_id, //	Long		是	硬件设备id
            "eventType" => "200001", //	String		是	报警事件类型，请参考数据字典
            "occurrenceTime" => date( "Y-m-d H:i:s", strtotime( $iono->iono_crt_time ) ), //	Date		是	事件发生时间 格式：yyyy-MM-dd hh:mm:ss 例如：2023-06-27 11:35:05
            "payload" => $payload,
        ];
        $resp = ZmHttpUtil::send_post_json( $url, $data );
        return $resp; //content->id
    }

    // 发送故障信息
    public static function sendDeviceFault( $token, $device_id ) {
        $url = self::Url . "/open/notify/device/fault?accessToken=" . $token;

        $fault_type = "300104";
        $fault_time = date( "Y-m-d H:i:s" );

        $data = (object) [
            "deviceId" => $device_id, //	Long		是	硬件设备id
            "faultType" => $fault_type, //	String		是	报警事件类型，请参考数据字典
            "faultTime" => $fault_time, //	Date		是	事件发生时间 格式：yyyy-MM-dd hh:mm:ss 例如：2023-06-27 11:35:05
        ];
        $resp = ZmHttpUtil::send_post_json( $url, $data );
        return $resp; //content->id
    }


    // 发送故障信息处理结果
    public static function sendDeviceFaultHandle( $token, $device_id, $process_result, $process_time ) {
        $url = self::Url . "/open/notify/device/fault/handle?accessToken=" . $token;

        $data = (object) [
            "deviceId" => $device_id, //	Long		是	硬件设备id
            "processResult" => $process_result, //	String		是	报警事件类型，请参考数据字典
            "processTime" => $process_time, //	Date		是	事件发生时间 格式：yyyy-MM-dd hh:mm:ss 例如：2023-06-27 11:35:05
        ];
        $resp = ZmHttpUtil::send_post_json( $url, $data );
        return $resp; //content->id
    }


    public static function removeDevice( $token, $yunchuang_id ) {
        $url = self::Url . "/open/unregister/device?accessToken=" . $token;
        $data = (object) [
            "deviceId" => $yunchuang_id,
        ];
        $resp = ZmHttpUtil::send_post_json( $url, $data );
        return $resp; //content->id
    }


    // 获取设备列表
    public static function getDevices( $token, $unit_id, $oid, $page ) {
        $url = self::Url . "/open/query/device/page?accessToken=" . $token;
        $data = (object) [
//			"unitId" => $unit_id,
            "oid" => $oid,
//			"name" => "865371075473164"
            "pageSize" => 200,
//			"pageNo" => $page,
        ];
        $resp = ZmHttpUtil::send_post_json( $url, $data );
        return $resp; //content->id
    }



    // 更新设备的监测信息
    public static function updateDeviceExt( $token, $device_id, $battery, $signal_intensity, $temperature, $smokescope ) {
        $url = self::Url . "/open/update/device?accessToken=" . $token;

        $ext = (object) [
            "400001" => [
                "monitorType" => "400001", //模拟量类型，电量
                "monitorUnit" => "%", //模拟量单位
                "monitorValue" => $battery != "" ? $battery : 100, //模拟量值
            ],
            "400002" => [
                "monitorType" => "400002", //模拟量类型，信号
                "monitorUnit" => "dB", //模拟量单位
                "monitorValue" => $signal_intensity, //模拟量值
            ],
            "400006" => [
                "monitorType" => "400006", //模拟量类型，温度
                "monitorUnit" => "℃", //模拟量单位
                "monitorValue" => $temperature != "" ? round( $temperature / 100, 2 ) : 0, //模拟量值
            ],
            "400013" => [
                "monitorType" => "400013", //模拟量类型，烟雾浓度
                "monitorUnit" => "dB/m", //模拟量单位
                "monitorValue" => $smokescope != "" ? round( $smokescope / 100, 2 ) : 0, //模拟量值
            ],
        ];

        $data = (object) [
            "deviceId" => $device_id,
            "ext" => $ext,
        ];
        $resp = ZmHttpUtil::send_post_json( $url, $data );
        return $resp; //content->id
    }


    public static function updateOnlineStatus( $token, $device_id, $status ) {
        $url = self::Url . "/open/notify/device/status?accessToken=" . $token;
        $data = (object) [
            "deviceId" => $device_id,
            "status" => $status,
        ];
        $resp = ZmHttpUtil::send_post_json( $url, $data );
        return $resp; //content->id
    }


    public static function modifyDevice( $token, $yunchuang_id, $provider, $model_name ) {
        $url = self::Url . "/open/update/device?accessToken=" . $token;
        $data = (object) [
            "deviceId" => $yunchuang_id,
            "provider" => $provider,
        ];
        $resp = ZmHttpUtil::send_post_json( $url, $data );
        return $resp; //content->id
    }


    //注册设备
    public static function regDevice( $token, $smde_id, $imei, $device_type, $unit_id, $device_name, $provider, $net_type, $lng, $lat, $ext, $standard_address, $addr_generic_name, $addr_room, $install_location ) {
        $url = self::Url . "/open/register/device?accessToken=" . $token;
        $data = (object) [
            "uid" => $imei,
            "oid" => $imei,
            "oidType" => $device_type,
            "unitId" => $unit_id,
            "name" => $device_name,
            "provider" => $provider, //"180003"
            "type" => $net_type, //"nb-lot",
            "address" => $standard_address . $addr_room . $install_location,
            "standardAddress" => $standard_address,
//			"addrGenericName" => "",
            "addrRoom" => $addr_room,
            "installLocation" => $install_location,
            "gpsLnt" => $lng,
            "gpsLat" => $lat,
            "ext" => $ext,
        ];
        $resp = ZmHttpUtil::send_post_json( $url, $data );
        return $resp; //content->id
    }

    public static function getUnits( $token, $oid, $name, $page ) {
        $url = self::Url . "/open/query/unit/page?accessToken=" . $token;
        $data = (object)[
            "oid" => $oid,
//			"name" => $name,
//			"pageSize" => 200,
//			"pageNo" => $page
        ];
        $resp = ZmHttpUtil::send_post_json( $url, $data );
        return $resp; //->content->id;
    }

    public static function deleteUnit( $token, $unit_id ) {
        $url = self::Url . "/open/unregister/unit?accessToken=" . $token;
        $data = (object)[
            "unitId" => $unit_id,
        ];
        $resp = ZmHttpUtil::send_post_json( $url, $data );
        return $resp; //->content->id;
    }

    public static function modifyUnit( $token, $id, $town_id ) {
        $url = self::Url . "/open/update/unit?accessToken=" . $token;
        $data = (object)[
            "townId" => $town_id,
            "unitId" => $id,
        ];
        $resp = ZmHttpUtil::send_post_json( $url, $data );
        return $resp; //->content->id;
    }

    public static function modifyUnitRustId( $token, $id, $rust_id ) {
        $url = self::Url . "/open/update/unit?accessToken=" . $token;
        $data = (object)[
            "rustId" => $rust_id,
            "unitId" => $id,
        ];
        $resp = ZmHttpUtil::send_post_json( $url, $data );
        return $resp; //->content->id;
    }

    // 注册点位
    public static function regUnit( $token, $town_id, $rust_id, $plac_id, $name, $type, $address, $lng, $lat, $chargers ) {
        $url = self::Url . "/open/register/unit?accessToken=" . $token;
        $data = (object)[
            "oid" => $plac_id,
            "townId" => $town_id,
            "rustId" => $rust_id,
            "name" => $name,
            "type" => $type,
            "address" => $address,
            "gpsLnt" => $lng,
            "gpsLat" => $lat,
            "chargeUsers" => $chargers
        ];
//		echo json_encode( $data );
        $resp = ZmHttpUtil::send_post_json( $url, $data );
        return $resp; //->content->id;
    }



    public static function getTowns( $token ) {
        $url = self::Url . "/open/query/town?accessToken=" . $token;
        $resp = ZmHttpUtil::send_post_json( $url, (object)[] );
        return $resp;
    }


    // 注册点位
    public static function getRusts( $token, $town_id ) {
        $url = self::Url . "/open/query/rust?accessToken=" . $token;
        $data = (object)[
            "townId" => $town_id,
        ];
//		echo json_encode( $data );
        $resp = ZmHttpUtil::send_post_json( $url, $data );
        return $resp; //->content->id;
    }

    public static function getToken() {
        $url = self::Url . "/open/token?appId=" . self::AppId . "&appSecret=" . self::AppSecret;
        $resp = ZmHttpUtil::get( $url );
        return json_decode( $resp )->content;
    }

    public static function getYunChuangUnitType( $plac_type ) {
        if ( $plac_type == "牙医店" || $plac_type == "诊所" ) {
            return 120001; //医院
        } else if ( $plac_type == "养老院" ) {
            return 120002; //	养老院
        } else if ( $plac_type == "党群" ) {
            return 120003; //	政府机构
        } else if ( $plac_type == "" ) {
            return 120004; //	车站
        } else if ( $plac_type == "" ) {
            return 120005; //	码头
        } else if ( $plac_type == "办公室" ) {
            return 120006; //	企业
        } else if ( $plac_type == "商铺" || $plac_type == "彩票店" || $plac_type == "药店" || $plac_type == "超市" || $plac_type == "酒吧" || $plac_type == "餐饮" ) {
            return 120007; //	商店
        } else if ( $plac_type == "客栈" || $plac_type == "酒店宾馆" ) {
            return 120008; //	宾馆
        } else if ( $plac_type == "" ) {
            return 120009; //	非盈利性机构
        } else if ( $plac_type == "" ) {
            return 120010; //	科研单位
        } else if ( $plac_type == "公寓" || $plac_type == "出租屋" || $plac_type == "出租屋/民宿" || $plac_type == "家庭" || $plac_type == "民宿" ) {
            return 120011; //	住宅
        } else if ( $plac_type == "" ) {
            return 120012; //	体育场
        } else if ( $plac_type == "厂房" ) {
            return 120013; //	工厂
        } else if ( $plac_type == "仓库" || $plac_type == "小学幼儿园" || $plac_type == "景区" || $plac_type == "汗蒸桑拿" || $plac_type == "网吧" || $plac_type == "美发店" || $plac_type == "聚类市场" ) {
            return 120014; //	其他
        } else {
            return 120014; //	其他
        }
    }
}
