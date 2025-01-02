<?php

namespace App\Models;

class AdvancedOrder extends BaseModel
{
    protected $table = 'advanced_order';

    public $timestamps = null;

    public static $payWayArr = [
        '1' => '微信',
        '2' => '支付宝',
        '3' => '银行',
        '4' => '现金',
        '5' => '二维码',
    ];

}
