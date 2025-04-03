<?php

namespace App\Http\Logic;

use Illuminate\Http\JsonResponse;

class ResponseLogic
{
    protected static $code = 0;
    protected static $message = '';
    protected static $data = [];

    public static function setMsg($message){
        self::$message = $message;
    }

    public static function getMsg(): string
    {
        return self::$message;
    }

    public static function apiResult($code = 0,$message = '',$data = []): JsonResponse
    {
        $result = [
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ];
        return response()->json($result, 200);
    }

    public static function apiErrorResult($message = ''): JsonResponse
    {
        $result = [
            'code' => -1,
            'message' => $message,
            'data' => [],
        ];
        return response()->json($result, 200);
    }

    public static function apiNoLoginResult(): JsonResponse
    {
        $result = [
            'code' => 401,
            'message' => '暂未登录',
            'data' => [],
        ];
        return response()->json($result, 200);
    }

    public static function apiOtherLogin(): JsonResponse
    {
        $result = [
            'code' => 402,
            'message' => '账号已被其他地方登录',
            'data' => [],
        ];
        return response()->json($result, 200);
    }
}
