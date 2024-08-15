<?php

namespace App\Http\Library;

use GuzzleHttp\Client;

class StandardAddress
{
    public $contentType  = "application/json";
    public $accept        = "*/*";

    public $tokenAuth = '2bcaacd810f1dea6f17c498918ac8a00';

    public $host = 'https://xcx.pinganbaiyun.cn/p_060_yjm/api_005_yjm/';

    public function getStandardAddress($keyword,$type = 2)
    {
        $params = [
            'type' => $type,
            'key_word' => $keyword
        ];
        $res = $this->doRequest('search_address_smartCity_management',$params);
        return $res[0] ?? $res;
    }

    public function doRequest($path, $params = [])
    {
        $fullPath = $this->host . $path;

        $client   = new Client(['verify' => false]);
        $response = $client->post($fullPath, [
            'headers' => [
                "Accept"                 => $this->accept,
                "Content-Type"           => $this->contentType,
                "SsoToken"               => $this->tokenAuth,
            ],
            'json'    => (object) $params, // 将关联数组转换为 JSON 对象,PHP空数组转空对象
        ]);

        return json_decode($response->getBody(), true);
    }
}
