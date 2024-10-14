<?php

namespace App\Models;

class ReceivableAccountFlow extends BaseModel
{
    protected $table   = 'receivable_account_flow';
    public $timestamps = null;

    public static $payWayArr = [
        '1' => '微信',
        '2' => '支付宝',
        '3' => '银行',
        '4' => '现金',
        '5' => '二维码',
    ];

    public static $typeArr = [
        '1' => '收回当期',
        '2' => '收回前期'
    ];

    public static function payWayMsg($payWay)
    {
        return self::$payWayArr[$payWay] ?? '未知';
    }

    public static function typeMsg($type)
    {
        return self::$typeArr[$type] ?? '未知';
    }
}
