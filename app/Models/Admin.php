<?php

namespace App\Models;

use Illuminate\Support\Facades\Cache;

class Admin extends BaseModel
{
    protected $table   = 'admin';
    public $timestamps = null;

    public static function getDataById($id)
    {
        $data = Cache::get(self::class.'_'.$id);
        if(!empty($data)){
            return $data;
        }

        $data = self::query()->where(['admin_id' => $id])->first();
        if(!$data){
            return null;
        }

        $data = $data->toArray();

        Cache::set(self::class.'_'.$id,$data,60*60);
        return $data;
    }
}
