<?php

namespace App\Http\Middleware;

use Closure;

class CheckIpMiddleware
{
    public function handle($request, Closure $next)
    {
        if($request->get('super_user') == 1){
            return $next($request);
        }
        $allowedIps = [
            '127.0.0.1',
            '183.6.174.106',
            '120.236.178.4',
            '120.236.189.237',
            '120.234.195.243',
            '14.23.77.156',
            '116.21.228.20', // 加入泰沙路IP，暂时
        ]; // 允许访问的 IP 地址

        $allowedIpRanges = [
            '121.33.144.0/22', // 允许访问的 IP 网段
        ];

        $ip = ip2long($request->ip());
        // dd($request->ip());

        foreach ($allowedIpRanges as $allowedIpRange) {
            list($subnet, $mask) = explode('/', $allowedIpRange);

            $subnet = ip2long($subnet);
            $mask   = -1 << (32 - $mask);

            if (($ip & $mask) === ($subnet & $mask)) {
                return $next($request);
            }
        }

        if (in_array($request->ip(), $allowedIps)) {
            return $next($request);
        }
        abort(403, 'Unauthorized action.');
    }
}
