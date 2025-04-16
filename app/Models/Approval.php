<?php

namespace App\Models;

class Approval extends BaseModel
{
    protected $table   = 'approval';
    public $timestamps = null;

    public static function getSn()
    {
        #查询当月申请单数量
        $count = Approval::query()
            ->where('appr_crt_time','>=',date('Y-m-d 00:00:00'))
            ->where('appr_crt_time','<=',date('Y-m-d 23:59:59'))
            ->count() ?: 0;

        return 'RYAF' . date('Ymd') . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    }
}
