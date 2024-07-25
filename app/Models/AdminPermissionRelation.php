<?php

namespace App\Models;

use App\Http\Logic\AuthLogic;
use App\Http\Logic\ToolsLogic;

class AdminPermissionRelation extends BaseModel
{
    protected $table   = 'admin_permission_relation';
    public $timestamps = null;

    public static function getMenu($adminId)
    {
        #获取父级菜单
        $ids = self::query()->where(['admin_permission_relation.adpe_admin_id' => $adminId])
            ->select(['adpe_permission_id'])->pluck('adpe_permission_id')->toArray();

        $permissionList = AdminPermission::query()
            ->where(['adpe_status' => 1])
            ->orderBy('adpe_sort','desc')
            ->select([
                'adpe_id',
                'adpe_parent_id',
                'adpe_name',
                'adpe_route'
            ])->get()->toArray();

        $list = [];
        foreach ($ids as $id){
            $list = self::getPermissionList($permissionList,$id,$list);
        }

        return ToolsLogic::toTree(array_values($list));
    }

    public static function getPermissionList($permissionList,$id,$arr = [])
    {
        if(isset($arr[$id])){
            return $arr;
        }
        foreach ($permissionList as $value){
            if($value['adpe_id'] == $id){
                $arr[$id] = [
                    'label' => $value['adpe_name'],
                    'url' => $value['adpe_route'],
                    'id'   => $value['adpe_id'],
                    'parent_id'   => $value['adpe_parent_id'],
                    'spread' => true
                ];

                if(empty($value['adpe_parent_id'])){
                    break;
                }

                return self::getPermissionList($permissionList,$value['adpe_parent_id'],$arr);
            }
        }
        return $arr;
    }
}
