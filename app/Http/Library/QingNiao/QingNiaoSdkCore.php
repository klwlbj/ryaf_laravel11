<?php

namespace App\Http\Library\QingNiao;

use App\Http\Logic\ResponseLogic;
use App\Http\Logic\ToolsLogic;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;

class QingNiaoSdkCore
{
    public const HOST = 'http://push.jbufacloud.com/jbfsys';

    public static $monitorId = '';
    public static $unitId = '64986';
    public static $signKey = 'nht6x4tY54W6zKfk';

    public static function getSign($data,$timestamp)
    {
        $dataStr = ToolsLogic::jsonEncode($data);

        $str = $dataStr . $timestamp . self::$signKey;

        return sha1($str);
    }

    public static function getToken()
    {

        $token = Cache::get('qing_niao_token');

        if(!empty($token)){
            return $token;
        }
        $timestamp               = time() . '000';

        $client = new Client([
            'verify' => false, // 关闭 SSL 验证
        ]);

        try {
            $response = $client->request('GET', self::HOST . '/api/token', [
                'query'   => [

                ],
//            'json'    => [],
                'headers' => [
                    'Fire-Unit-Id' => self::$unitId,
                    'X-Timestamp' => $timestamp,
                    'X-Signature' => self::getSign('',$timestamp),
                ],
            ]);
        } catch (RequestException $e) {
            $res = ToolsLogic::jsonDecode($e->getResponse()->getBody()->getContents());//5xx 4xx
            ResponseLogic::setMsg($res['message']);
            return false;
        }


//        print_r($response->getStatusCode());die;

        if ($response->getStatusCode() === 200) {
            $tokenRes = ToolsLogic::jsonDecode($response->getBody()->getContents());
        }else{
            return false;
        }

        if(isset($tokenRes['data']['token'])){
            Cache::set('qing_niao_token',$tokenRes['data']['token'],60*50);
            return $tokenRes['data']['token'];
        }

        ResponseLogic::setMsg('获取token失败');
        return false;
    }

    public static function sendSDkRequest($path,$data,$method = "GET",$header = [])
    {
        $client = new Client([
            'verify' => false, // 关闭 SSL 验证
        ]);

        $token = self::getToken();
        if(!$token){
            return false;
        }

        $header['Token'] = $token;
        if(!empty(self::$unitId)){
            $header['Fire-Unit-Id'] = self::$unitId;
        }

        if(!empty(self::$monitorId)){
            $header['Monitor-Center-Id'] = self::$monitorId;
        }

        try {
            $response = $client->request($method, self::HOST . $path, [
                'query'   => $data,
//            'json'    => $data,
                'headers' => $header,
            ]);
        } catch (RequestException $e) {
            $res = ToolsLogic::jsonDecode($e->getResponse()->getBody()->getContents());//5xx 4xx
            ResponseLogic::setMsg($res['message']);
            return false;
        }


        if ($response->getStatusCode() === 200) {
            return ToolsLogic::jsonDecode($response->getBody()->getContents());
        }else{
            return false;
        }
    }
}
