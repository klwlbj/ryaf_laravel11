<?php

namespace App\Models;

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

    public static function getNodeParent($id,$pid = 4)
    {
        $list = self::query()->where(['node_enabled' => 1])
            ->select(['node_parent_id','node_id'])->get()->pluck('node_parent_id','node_id')->toArray();

        $arr = [$id];

        $arr = self::getParents($list,$id,$arr,$pid);

        return array_reverse($arr);
    }

    public static function getParents($list,$id,$arr,$pid = 4){
        $parentId = $list[$id] ?? 0;
        if($parentId <= $pid){
            return $arr;
        }
        $arr[] = $parentId;
        $arr = self::getParents($list,$parentId,$arr,$pid);

        return $arr;
    }


    public function children()
    {
        return $this->hasMany(self::class, 'node_parent_id')->orderBy('node_id');
    }
}
