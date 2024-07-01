<?php

namespace App\Http\Logic;

use DateTimeZone;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

class ToolsLogic
{
    /**解析json字符串
     * @param $data
     * @return mixed
     */
    public static function jsonDecode($data){
        if(is_array($data)){
            return $data;
        }

        $newData = json_decode($data,true);
        if(is_array($newData)){
            return $newData;
        }

        return $data;
    }

    /**
     * @param $data
     * @param int $option
     * @return string
     */
    public static function jsonEncode($data, $option = 256){
        if(!is_array($data)){
            return $data;
        }

        return json_encode($data,$option);
    }

    /**打印日志
     * @param $msg
     * @param null $path
     * @param array $data
     */
    public static function writeLog($msg, $path = null, $data = []){
        if(empty($path)){
            $actions=explode('\\', \Route::current()->getActionName());
            $func=explode('@', $actions[count($actions)-1]);
            $path=$func[0].'-'.$func[1];
        }
        $path = 'logs/'.$path.'/'.$path.'.log';

        if(!is_array($data) && !empty($data)){
            $data = ['data' => $data];
        }

        if(empty($data)){
            $data = array_merge($_GET,$_POST);
        }


        if(!is_array($data)){
            $data = ToolsLogic::jsonDecode($data);
        }



        (new Logger('daily',[],[],new DateTimeZone('Asia/Shanghai')))
            ->pushHandler(new RotatingFileHandler(storage_path($path),14))
            ->debug($msg,$data);
    }

    /**新建url
     * @param $dir
     * @return bool
     */
    public static function createDir($dir){
        if(!file_exists($dir)){
            return mkdir($dir,0777,true);
        }
        return true;
    }

    /**
     * 菜单树形获取
     * @param array   $arr 所有菜单
     * @param integer $pid   菜单父级id
     * @return array
     */
    public static function toTree($arr, $pid = 0)
    {
        $tree = [];

        foreach ($arr as $k => $v) {
            if ($v['parent_id'] == $pid) {
                $childData = self::toTree($arr, $v['id']);
                // 这里根据前端，以及业务的需要来处理，加上这个判断，如果该菜单没有下级，就没有chlidren字段，不加判断，则返回[]
                if (count($childData) > 0) {
                    $v['children'] = self::toTree($arr, $v['id']);
                }
                $tree[] = $v;
            }
        }

        return $tree;
    }
}
