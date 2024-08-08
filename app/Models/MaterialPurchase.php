<?php

namespace App\Models;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MaterialPurchase extends BaseModel
{
    protected $table   = 'material_purchase';
    public $timestamps = null;

    public static function getDataById($id)
    {
//        $data = Cache::get(self::class.'_'.$id);
//        if(!empty($data)){
//            return $data;
//        }

        $data = self::query()->where(['mapu_id' => $id])->first();
        if(!$data){
            return null;
        }

        $data = $data->toArray();

        Cache::set(self::class.'_'.$id,$data,60*60);
        return $data;
    }

    public static function delCacheById($id)
    {
        Cache::delete(self::class.'_'.$id);
    }

    public static function getSn($categoryId)
    {
        if($categoryId == 1){
            $typeStr = '01';
        }else{
            $typeStr = '02';
        }

        $number = self::query()->whereRaw("YEAR(mapu_crt_time) = YEAR(CURDATE()) AND MONTH(mapu_crt_time) = MONTH(CURDATE())")->count() ?: 0;

        $number = str_pad(($number + 1), 2, '0', STR_PAD_LEFT);

        return date('Ym') . $typeStr . '-' . $number;
    }
}
