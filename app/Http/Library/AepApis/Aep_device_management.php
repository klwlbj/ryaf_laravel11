<?php

namespace App\Http\Library\AepApis;


use App\Http\Library\AepApis\Core\AepSdkCore;
class Aep_device_management
{
    //参数MasterKey: 类型String, 参数不可以为空
    //  描述:MasterKey在该设备所属产品的概况中可以查看
    //参数productId: 类型long, 参数不可以为空
    //  描述:
    //参数searchValue: 类型String, 参数可以为空
    //  描述:T-link协议可选填:设备名称，设备编号，设备Id
    //    MQTT协议可选填:设备名称，设备编号，设备Id
    //    LWM2M协议可选填:设备名称，设备Id ,IMEI号
    //    TUP协议可选填:设备名称，设备Id ,IMEI号
    //    TCP协议可选填:设备名称，设备编号，设备Id
    //    HTTP协议可选填:设备名称，设备编号，设备Id
    //    JT/T808协议可选填:设备名称，设备编号，设备Id
    //参数pageNow: 类型long, 参数可以为空
    //  描述:当前页数
    //参数pageSize: 类型long, 参数可以为空
    //  描述:每页记录数,最大100
    public static function QueryDeviceList($appKey, $appSecret, $MasterKey, $productId, $searchValue = "", $pageNow = "", $pageSize = "")
    {
        $path                 = "/aep_device_management/devices";
        $headers              = [];
        $headers["MasterKey"] = $MasterKey;

        $param                = [];
        $param["productId"]   = $productId;
        $param["searchValue"] = $searchValue;
        $param["pageNow"]     = $pageNow;
        $param["pageSize"]    = $pageSize;

        $version = "20190507012134";

        $application = $appKey;
        $secret      = $appSecret;

        $response = AepSdkCore::sendSDkRequest($path, $headers, $param, null, $version, $application, $secret, "GET");
        if ($response != null) {
            return $response;
        }
        return null;
    }

    //参数MasterKey: 类型String, 参数不可以为空
    //  描述:MasterKey在该设备所属产品的概况中可以查看
    //参数deviceId: 类型String, 参数不可以为空
    //  描述:
    //参数productId: 类型long, 参数不可以为空
    //  描述:
    public static function QueryDevice($appKey, $appSecret, $MasterKey, $deviceId, $productId)
    {
        $path                 = "/aep_device_management/device";
        $headers              = [];
        $headers["MasterKey"] = $MasterKey;

        $param              = [];
        $param["deviceId"]  = $deviceId;
        $param["productId"] = $productId;

        $version = "20181031202139";

        $application = $appKey;
        $secret      = $appSecret;

        $response = AepSdkCore::sendSDkRequest($path, $headers, $param, null, $version, $application, $secret, "GET");
        if ($response != null) {
            return $response;
        }
        return null;
    }

    //参数MasterKey: 类型String, 参数不可以为空
    //  描述:MasterKey在该设备所属产品的概况中可以查看
    //参数productId: 类型long, 参数不可以为空
    //  描述:
    //参数deviceIds: 类型String, 参数不可以为空
    //  描述:可以删除多个设备（最多支持200个设备）。多个设备id，中间以逗号 "," 隔开 。样例：05979394b88a45b0842de729c03d99af,06106b8e1d5a458399326e003bcf05b4
    public static function DeleteDevice($appKey, $appSecret, $MasterKey, $productId, $deviceIds)
    {
        $path                 = "/aep_device_management/device";
        $headers              = [];
        $headers["MasterKey"] = $MasterKey;

        $param              = [];
        $param["productId"] = $productId;
        $param["deviceIds"] = $deviceIds;

        $version = "20181031202131";

        $application = $appKey;
        $secret      = $appSecret;

        $response = AepSdkCore::sendSDkRequest($path, $headers, $param, null, $version, $application, $secret, "DELETE");
        if ($response != null) {
            return $response;
        }
        return null;
    }

    //参数MasterKey: 类型String, 参数不可以为空
    //  描述:
    //参数deviceId: 类型String, 参数不可以为空
    //  描述:
    //参数body: 类型json, 参数不可以为空
    //  描述:body,具体参考平台api说明
    public static function UpdateDevice($appKey, $appSecret, $MasterKey, $deviceId, $body)
    {
        $path                 = "/aep_device_management/device";
        $headers              = [];
        $headers["MasterKey"] = $MasterKey;

        $param             = [];
        $param["deviceId"] = $deviceId;

        $version = "20181031202122";

        $application = $appKey;
        $secret      = $appSecret;

        $response = AepSdkCore::sendSDkRequest($path, $headers, $param, $body, $version, $application, $secret, "PUT");
        if ($response != null) {
            return $response;
        }
        return null;
    }

    //参数MasterKey: 类型String, 参数不可以为空
    //  描述:MasterKey在该设备所属产品的概况中可以查看
    //参数body: 类型json, 参数不可以为空
    //  描述:body,具体参考平台api说明
    public static function CreateDevice($appKey, $appSecret, $MasterKey, $body)
    {
        $path                 = "/aep_device_management/device";
        $headers              = [];
        $headers["MasterKey"] = $MasterKey;

        $param   = null;
        $version = "20181031202117";

        $application = $appKey;
        $secret      = $appSecret;

        $response = AepSdkCore::sendSDkRequest($path, $headers, $param, $body, $version, $application, $secret, "POST");
        if ($response != null) {
            return $response;
        }
        return null;
    }

    //参数MasterKey: 类型String, 参数不可以为空
    //  描述:
    //参数body: 类型json, 参数不可以为空
    //  描述:body,具体参考平台api说明
    public static function BindDevice($appKey, $appSecret, $MasterKey, $body)
    {
        $path                 = "/aep_device_management/bindDevice";
        $headers              = [];
        $headers["MasterKey"] = $MasterKey;

        $param   = null;
        $version = "20191024140057";

        $application = $appKey;
        $secret      = $appSecret;

        $response = AepSdkCore::sendSDkRequest($path, $headers, $param, $body, $version, $application, $secret, "POST");
        if ($response != null) {
            return $response;
        }
        return null;
    }

    //参数MasterKey: 类型String, 参数不可以为空
    //  描述:
    //参数body: 类型json, 参数不可以为空
    //  描述:body,具体参考平台api说明
    public static function UnbindDevice($appKey, $appSecret, $MasterKey, $body)
    {
        $path                 = "/aep_device_management/unbindDevice";
        $headers              = [];
        $headers["MasterKey"] = $MasterKey;

        $param   = null;
        $version = "20191024140103";

        $application = $appKey;
        $secret      = $appSecret;

        $response = AepSdkCore::sendSDkRequest($path, $headers, $param, $body, $version, $application, $secret, "POST");
        if ($response != null) {
            return $response;
        }
        return null;
    }

    //参数imei: 类型String, 参数不可以为空
    //  描述:
    public static function QueryProductInfoByImei($appKey, $appSecret, $imei)
    {
        $path          = "/aep_device_management/device/getProductInfoFormApiByImei";
        $headers       = null;
        $param         = [];
        $param["imei"] = $imei;

        $version = "20191213161859";

        $application = $appKey;
        $secret      = $appSecret;

        $response = AepSdkCore::sendSDkRequest($path, $headers, $param, null, $version, $application, $secret, "GET");
        if ($response != null) {
            return $response;
        }
        return null;
    }

    //参数MasterKey: 类型String, 参数不可以为空
    //  描述:
    //参数body: 类型json, 参数不可以为空
    //  描述:body,具体参考平台api说明
    public static function ListDeviceInfo($appKey, $appSecret, $MasterKey, $body)
    {
        $path                 = "/aep_device_management/listByDeviceIds";
        $headers              = [];
        $headers["MasterKey"] = $MasterKey;

        $param   = null;
        $version = "20210828062945";

        $application = $appKey;
        $secret      = $appSecret;

        $response = AepSdkCore::sendSDkRequest($path, $headers, $param, $body, $version, $application, $secret, "POST");
        if ($response != null) {
            return $response;
        }
        return null;
    }

    //参数MasterKey: 类型String, 参数不可以为空
    //  描述:
    //参数body: 类型json, 参数不可以为空
    //  描述:body,具体参考平台api说明
    public static function DeleteDeviceByPost($appKey, $appSecret, $MasterKey, $body)
    {
        $path                 = "/aep_device_management/deleteDevice";
        $headers              = [];
        $headers["MasterKey"] = $MasterKey;

        $param   = null;
        $version = "20211009132842";

        $application = $appKey;
        $secret      = $appSecret;

        $response = AepSdkCore::sendSDkRequest($path, $headers, $param, $body, $version, $application, $secret, "POST");
        if ($response != null) {
            return $response;
        }
        return null;
    }

    //参数MasterKey: 类型String, 参数不可以为空
    //  描述:
    //参数body: 类型json, 参数不可以为空
    //  描述:body,具体参考平台api说明
    public static function DeleteDeviceByImei($appKey, $appSecret, $MasterKey, $body)
    {
        $path                 = "/aep_nb_device_management/deleteDeviceByImei";
        $headers              = [];
        $headers["MasterKey"] = $MasterKey;

        $param   = null;
        $version = "20220226071405";

        $application = $appKey;
        $secret      = $appSecret;

        $response = AepSdkCore::sendSDkRequest($path, $headers, $param, $body, $version, $application, $secret, "POST");
        if ($response != null) {
            return $response;
        }
        return null;
    }


    //参数MasterKey: 类型String, 参数不可以为空
    //  描述:
    //参数body: 类型json, 参数不可以为空
    //  描述:body,具体参考平台api说明
    public static function ListDeviceActiveStatus($appKey, $appSecret, $MasterKey, $body)
    {
        $path                 = "/aep_device_management/listActiveStatus";
        $headers              = [];
        $headers["MasterKey"] = $MasterKey;

        $param   = null;
        $version = "20211010063104";

        $application = $appKey;
        $secret      = $appSecret;

        $response = AepSdkCore::sendSDkRequest($path, $headers, $param, $body, $version, $application, $secret, "POST");
        if ($response != null) {
            return $response;
        }
        return null;
    }

    //参数MasterKey: 类型String, 参数不可以为空
    //  描述:
    //参数body: 类型json, 参数不可以为空
    //  描述:body,具体参考平台api说明
    public static function BatchCreateDevice($appKey, $appSecret, $MasterKey, $body)
    {
        $path                 = "/aep_device_management/batchDevice";
        $headers              = [];
        $headers["MasterKey"] = $MasterKey;

        $param   = null;
        $version = "20230330043852";

        $application = $appKey;
        $secret      = $appSecret;

        $response = AepSdkCore::sendSDkRequest($path, $headers, $param, $body, $version, $application, $secret, "POST");
        if ($response != null) {
            return $response;
        }
        return null;
    }
}
