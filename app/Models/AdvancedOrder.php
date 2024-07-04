<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdvancedOrder extends Model
{
    use SoftDeletes;

    protected array $dates = ['deleted_at'];

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public const INCOME_TYPE_WECHAT = 1;
    public const INCOME_TYPE_ALIPAY = 2;
    public const INCOME_TYPE_BANK   = 3;
    public const INCOME_TYPE_MONEY  = 4;
    public const INCOME_TYPE_QRCODE = 5;

    public static array $formatIncomeTypeMaps = [
        self::INCOME_TYPE_WECHAT => '微信',
        self::INCOME_TYPE_ALIPAY => '支付宝',
        self::INCOME_TYPE_BANK   => '银行',
        self::INCOME_TYPE_MONEY  => '现金',
        self::INCOME_TYPE_QRCODE => '扫二维码',
    ];

    public const  CUSTOMER_TYPE_TO_B = 1;
    public const CUSTOMER_TYPE_TO_C  = 2;

    public static array $formatCustomerTypeMaps = [
        self::CUSTOMER_TYPE_TO_B => 'TO B',
        self::CUSTOMER_TYPE_TO_C => 'TO C',
    ];

    public const  PAYMENT_TYPE_PREPAYMENT = 1;

    public static array $formatPaymentTypeMaps = [
        self::PAYMENT_TYPE_PREPAYMENT => '预付',
    ];
}
