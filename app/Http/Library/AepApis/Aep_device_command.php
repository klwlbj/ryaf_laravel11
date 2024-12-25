<?php

namespace App\Http\Library\AepApis;


use App\Http\Library\AepApis\Core\AepSdkCore;

class Aep_device_command
{
    //参数MasterKey: 类型String, 参数不可以为空
    //  描述:MasterKey在该设备所属产品的概况中可以查看
    //参数body: 类型json, 参数不可以为空
    //  描述:body,具体参考平台api说明
    public static function CreateCommand($appKey, $appSecret, $MasterKey, $body)
    {
        $path                 = "/aep_device_command/command";
        $headers              = [];
        $headers["MasterKey"] = $MasterKey;

        $param   = null;
        $version = "20190712225145";

        $application = $appKey;
        $secret      = $appSecret;

        $response = AepSdkCore::sendSDkRequest($path, $headers, $param, $body, $version, $application, $secret, "POST");
        if ($response != null) {
            return $response;
        }
        return null;
    }

    //参数MasterKey: 类型String, 参数不可以为空
    //  描述:MasterKey在该设备所属产品的概况中可以查看
    //参数productId: 类型long, 参数不可以为空
    //  描述:产品ID，必填
    //参数deviceId: 类型String, 参数不可以为空
    //  描述:设备ID，必填
    //参数startTime: 类型String, 参数可以为空
    //  描述:日期格式，年月日时分秒，例如：20200801120130
    //参数endTime: 类型String, 参数可以为空
    //  描述:日期格式，年月日时分秒，例如：20200801120130
    //参数pageNow: 类型long, 参数可以为空
    //  描述:当前页数
    //参数pageSize: 类型long, 参数可以为空
    //  描述:每页记录数，最大40
    public static function QueryCommandList($appKey, $appSecret, $MasterKey, $productId, $deviceId, $startTime = "", $endTime = "", $pageNow = "", $pageSize = "")
    {
        $path                 = "/aep_device_command/commands";
        $headers              = [];
        $headers["MasterKey"] = $MasterKey;

        $param              = [];
        $param["productId"] = $productId;
        $param["deviceId"]  = $deviceId;
        $param["startTime"] = $startTime;
        $param["endTime"]   = $endTime;
        $param["pageNow"]   = $pageNow;
        $param["pageSize"]  = $pageSize;

        $version = "20200814163736";

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
    //参数commandId: 类型String, 参数不可以为空
    //  描述:创建指令成功响应中返回的id，
    //参数productId: 类型long, 参数不可以为空
    //  描述:
    //参数deviceId: 类型String, 参数不可以为空
    //  描述:设备ID
    public static function QueryCommand($appKey, $appSecret, $MasterKey, $commandId, $productId, $deviceId)
    {
        $path                 = "/aep_device_command/command";
        $headers              = [];
        $headers["MasterKey"] = $MasterKey;

        $param              = [];
        $param["commandId"] = $commandId;
        $param["productId"] = $productId;
        $param["deviceId"]  = $deviceId;

        $version = "20190712225241";

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
    public static function CancelCommand($appKey, $appSecret, $MasterKey, $body)
    {
        $path                 = "/aep_device_command/cancelCommand";
        $headers              = [];
        $headers["MasterKey"] = $MasterKey;

        $param   = null;
        $version = "20190615023142";

        $application = $appKey;
        $secret      = $appSecret;

        $response = AepSdkCore::sendSDkRequest($path, $headers, $param, $body, $version, $application, $secret, "PUT");
        if ($response != null) {
            return $response;
        }
        return null;
    }
}
