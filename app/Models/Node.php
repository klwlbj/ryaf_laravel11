<?php

namespace App\Models;

use Illuminate\Support\Facades\Cache;

class Node extends BaseModel
{
    protected $table   = 'node';
    public $timestamps = null;
    public $primaryKey = 'node_id';

    public static function getNodeChild($id,$getSelf = true)
    {
        $list = self::query()->where(['node_enabled' => 1])->select(['node_parent_id','node_id'])->get()->toArray();
        $arr = [];
        if($getSelf){
            $arr[] = $id;
        }

        return self::getChildIds($list,$id,$arr);
    }

    public static function getChildIds($list,$parentId,$arr = [])
    {
        foreach ($list as $key => $value) {
            if($value['node_parent_id'] == $parentId){
                $arr[] = $value['node_id'];
                $arr = self::getChildIds($list,$value['node_id'], $arr);
            }
        }

        return $arr;
    }

}
