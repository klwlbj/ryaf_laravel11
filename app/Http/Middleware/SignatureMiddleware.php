<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SignatureMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $partnerCode = $request->input('partnerCode');
        $secretList  = ['gjjtznaqgj' => 'h5iWu2tJyQDYQdmCLfSvUfzi92YCsvgA'];

        $secret = $secretList[$partnerCode] ?? '';
        if(empty($secret)){
            return response()->json(['error' => 'secret not found'], 401);
        }

        $expectedSignature = md5($partnerCode . date('Y-m-d') . $secret);
        // dd($expectedSignature);
        $requestSignature = $request->input('signature');

        if ($expectedSignature != $requestSignature && env('APP_ENV') == 'production') {
            // 验证失败，可根据需要进行日志记录或返回错误响应
            Log::error('Signature mismatch for partnerCode: ' . $partnerCode);
            return response()->json(['error' => 'Invalid Signature'], 401);
        }

        return $next($request);
    }
}
