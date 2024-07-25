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
            array_shift($array);
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
}
