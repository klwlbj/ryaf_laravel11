<?php

namespace App\Models;

class Department extends BaseModel
{
    protected $table   = 'department';
    public $timestamps = null;

    public static $departmentArr = [];

    public static function getDepartmentStr($id)
    {
        if(empty(self::$departmentArr)){
            self::initDepartmentArr();
        }

        return self::$departmentArr[$id] ?? '';
    }

    public static function initDepartmentArr()
    {
        $list = self::query()->get()->toArray();

        foreach ($list as $key => $value){
            if(isset(self::$departmentArr[$value['depa_id']])){
                continue;
            }

            $array = self::getNameArr($list, $value);
//            array_shift($array);
            self::$departmentArr[$value['depa_id']] = implode('->', $array);
        }
    }

    /**
     * @param $list
     * @param $data
     * @param $arr
     * @return array
     */
    public static function getNameArr($list, $data, $arr = []):array
    {
        array_unshift($arr,$data['depa_name']);

        foreach ($list as $key => $value){
            if(empty($data['depa_parent_id'])){
                break;
            }
            if($value['depa_id'] == $data['depa_parent_id']){
                return self::getNameArr($list,$value,$arr);
            }
        }

        return $arr;
    }


    public static function getDepartmentLeaderArr($departmentId)
    {
        $list = self::query()->get()->toArray();

        return self::getLeader($list,$departmentId);
    }

    public static function getLeader($departmentList,$departmentId,$arr = [])
    {
        foreach ($departmentList as $key => $value){
            if($value['depa_id'] == $departmentId){
                $arr[] = $value['depa_leader_id'];
                if($value['depa_parent_id'] == 0){
                    return $arr;
                }
                $arr = self::getLeader($departmentList,$value['depa_parent_id'],$arr);
            }
        }
        return $arr;
    }

    public static function getDepartmentParentArr($departmentId)
    {
        $list = self::query()->get()->toArray();
        $arr = [$departmentId];
        return self::getParent($list,$departmentId,$arr);
    }

    public static function getParent($departmentList,$departmentId,$arr = [])
    {
        foreach ($departmentList as $key => $value){
            if($value['depa_id'] == $departmentId){
                if($value['depa_parent_id'] == 0){
                    return $arr;
                }
                $arr[] = $value['depa_parent_id'];
                $arr = self::getParent($departmentList,$value['depa_parent_id'],$arr);
            }
        }
        return $arr;
    }
}
