<?php

namespace App\Http\Logic;

use App\Models\AdminPermission;
use App\Models\AdminPermissionRelation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

class AuthLogic extends BaseLogic
{
    public static $userId = null;

    public static function getToken($data)
    {
        $token = md5(time().'_'.$data['admin_id']);
        Cache::set($token,$data,60*60*24);
        return $token;
    }

    public static function checkPermission($adminId)
    {
        $currentRoute = '/' . Route::current()->uri();

        #获取全部权限
        $permissionArr = AdminPermissionRelation::getPermissionArr($adminId);
//        print_r($permissionArr);die;
        if(isset($permissionArr[$currentRoute]) && empty($permissionArr[$currentRoute])){
            return false;
        }

        return true;
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
