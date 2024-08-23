<?php

namespace App\Models;

class InstallationRegister extends BaseModel
{
    protected $table   = 'installation_register';
    public $timestamps = null;

    public static $payWayArr = [
        '1' => '微信',
        '2' => '支付宝',
        '3' => '银行',
        '4' => '现金',
        '5' => '二维码',
    ];
}
