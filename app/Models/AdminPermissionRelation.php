<?php

namespace App\Models;

use App\Http\Logic\AuthLogic;
use App\Http\Logic\ToolsLogic;
use Illuminate\Support\Facades\DB;

class AdminPermissionRelation extends BaseModel
{
    protected $table   = 'admin_permission_relation';
    public $timestamps = null;

    public static function getMenu($adminId)
    {
        #获取父级菜单
        $ids = self::query()->where(['admin_permission_relation.adpe_admin_id' => $adminId])
            ->leftJoin('admin_permission', 'admin_permission.adpe_id', '=', 'admin_permission_relation.adpe_permission_id')
            ->select(['adpe_permission_id'])
            ->orderBy('admin_permission.adpe_sort','desc')
            ->pluck('adpe_permission_id')->toArray();

        $permissionList = AdminPermission::query()
            ->where(['adpe_status' => 1,'adpe_type' => 1])
            ->orderBy('adpe_sort','desc')
            ->select([
                'adpe_id',
                'adpe_parent_id',
                'adpe_name',
                'adpe_route'
            ])->get()->toArray();

        $list = [];
        foreach ($ids as $id){
            $list = self::getMenuList($permissionList,$id,$list);
        }

        return ToolsLogic::toTree(array_values($list));
    }

    public static function getMenuList($permissionList,$id,$arr = [])
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

                return self::getMenuList($permissionList,$value['adpe_parent_id'],$arr);
            }
        }
        return $arr;
    }

    public static function getPermissionArr($adminId){
        return AdminPermission::query()
            ->leftJoin(
                'admin_permission_relation',
                'admin_permission_relation.adpe_permission_id',
                '=',
                DB::raw('admin_permission.adpe_id and admin_permission_relation.adpe_admin_id = '.$adminId)
            )
            ->where([
                'admin_permission.adpe_status' => 1,
                'admin_permission.adpe_type' => 2
            ])
            ->select([
                DB::raw("case when admin_permission_relation.adpe_permission_id is null then 0 else 1 end as value"),
                'admin_permission.adpe_route',
            ])
            ->pluck('value','adpe_route')->toArray();
    }
}
