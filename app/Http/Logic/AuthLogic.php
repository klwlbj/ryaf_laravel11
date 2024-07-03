<?php

namespace App\Http\Logic;

class AuthLogic extends BaseLogic
{
    public static $userId = null;

    /**物品申购审批权限
     * @param $userId
     * @return boolean
     */
    public static function materialPurchaseAuth()
    {
        return true;
    }
}
