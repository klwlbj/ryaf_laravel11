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

        if(AuthLogic::$userId){
            $data['user_id'] = AuthLogic::$userId;
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
    public static function toTree($arr, $pid = 0,$idKey = 'id',$parentIdKey = 'parent_id')
    {
        $tree = [];

        foreach ($arr as $k => $v) {
            if ($v[$parentIdKey] == $pid) {
                $childData = self::toTree($arr, $v[$idKey],$idKey,$parentIdKey);
                // 这里根据前端，以及业务的需要来处理，加上这个判断，如果该菜单没有下级，就没有chlidren字段，不加判断，则返回[]
                if (count($childData) > 0) {
                    $v['children'] = $childData;
                }
                $tree[] = $v;
            }
        }

        return $tree;
    }

    public static function convertExcelTime($excelValue)
    {
        if(empty($excelValue) || !is_numeric($excelValue)){
            return $excelValue;
        }
        try {
            $fixation = 25569;
            $fixationT = 24 * 60 * 60;
            $date = gmdate('Y-m-d H:i:s', ($excelValue- $fixation) * $fixationT);
        } catch (\Exception $e) {
            $date = '1970-01-01';
        }
        return $date;
    }

    public static function createHeartBeat($heartbeat)
    {
        if((time() - strtotime($heartbeat)) > (60 * 60 *24 *3)){
            $hour = str_pad(rand(0,date('H')), 2, '0', STR_PAD_LEFT);
            $minute = str_pad(rand(0,date('i')), 2, '0', STR_PAD_LEFT);
            $second = str_pad(rand(0,date('s')), 2, '0', STR_PAD_LEFT);
            $heartbeat = date('Y-m-d') . ' ' .$hour . ':' . $minute . ':' . $second;
        }

        return $heartbeat;
    }
}
