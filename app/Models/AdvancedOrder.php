<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdvancedOrder extends Model
{
    use SoftDeletes;

    protected $table = 'advanced_order';

    protected array $dates = ['deleted_at'];

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public const PAY_WAY_WECHAT = 1;
    public const PAY_WAY_ALIPAY = 2;
    public const PAY_WAY_BANK   = 3;
    public const PAY_WAY_MONEY  = 4;
    public const PAY_WAY_QRCODE = 5;

    public static array $formatPayWayMaps = [
        self::PAY_WAY_WECHAT => '微信',
        self::PAY_WAY_ALIPAY => '支付宝',
        self::PAY_WAY_BANK   => '银行',
        self::PAY_WAY_MONEY  => '现金',
        self::PAY_WAY_QRCODE => '扫二维码',
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
