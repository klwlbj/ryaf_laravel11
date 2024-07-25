<?php

namespace App\Http\Logic;

use Illuminate\Support\Facades\Cache;

class AuthLogic extends BaseLogic
{
    public static $userId = null;

    public static function getToken($data)
    {
        $token = md5(time().'_'.$data['admin_id']);
        Cache::set($token,$data,60*60*24);
        return $token;
    }

    /**物品申购审批权限
     * @param $userId
     * @return boolean
     */

    public static function orderAccountApproveAuth()
    {
        return true;
    }
}
