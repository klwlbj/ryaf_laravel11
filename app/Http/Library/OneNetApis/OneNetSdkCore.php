<?php

namespace App\Http\Library\OneNetApis;

use App\Http\Logic\ToolsLogic;
use GuzzleHttp\Client;

class OneNetSdkCore
{
    public const HOST = 'https://iot-api.heclouds.com';

    public static function getSign($userId,$accessKey)
    {
        $time               = time() + 30;
        $method             = 'md5';
        $res                = 'userid/' . $userId;
        $version            = '2022-05-01';
        $stringForSignature = $time . "\n" . $method . "\n" . $res . "\n" . $version;

        $decodedAccessKey = base64_decode($accessKey);
        $hmac             = hash_hmac($method, utf8_encode($stringForSignature), $decodedAccessKey, true);
        $signature        = base64_encode($hmac);

        return 'version=' . $version . '&res=' . urlencode($res) . '&et=' . $time . '&method=' . $method . '&sign=' . urlencode($signature);
    }

    public static function sendSDkRequest($path,$data,$userId,$accessKey)
    {
        $client = new Client([
            'verify' => false, // 关闭 SSL 验证
        ]);

        $response = $client->request('POST', self::HOST . $path, [
            'query'   => [

            ],
            'json'    => $data,
            'headers' => ['authorization' => self::getSign($userId,$accessKey)],
        ]);

        if ($response->getStatusCode() === 200) {
            return ToolsLogic::jsonDecode($response->getBody()->getContents());
        }else{
            return false;
        }
    }
}
