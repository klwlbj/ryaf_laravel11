<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OtherOrder extends BaseModel
{
    use SoftDeletes;

    protected $table = 'other_order';

    protected array $dates = ['deleted_at'];

    public $timestamps = false; // 禁用默认的 created_at 和 updated_at 字段

    const CREATED_AT = 'order_crt_time'; // 定义自定义的创建时间字段名

    const UPDATED_AT = 'order_upd_time'; // 定义自定义的修改时间字段名

    public function area()
    {
        return $this->belongsTo(Area::class, 'order_area_id');
    }
    public const PAY_WAY_BANK   = 3;
    public const PAY_WAY_MONEY  = 4;
    public const PAY_WAY_QRCODE = 5;

    public static array $formatPayWayMaps = [
        self::PAY_WAY_BANK   => '对公转账',
        self::PAY_WAY_MONEY  => '现金',
        self::PAY_WAY_QRCODE => '扫二维码',
    ];

    public const CONTRACT_TYPE_RENT              = 1;
    public const CONTRACT_TYPE_MOBILE_GIFTING    = 2;
    public const CONTRACT_TYPE_ADDITIONAL_ACCESS = 3;

    public static array $formatContractTypeMaps = [
        self::CONTRACT_TYPE_RENT              => '以租代购',
        self::CONTRACT_TYPE_MOBILE_GIFTING    => '移动赠机',
        self::CONTRACT_TYPE_ADDITIONAL_ACCESS => '异网接入',
    ];

    public const PAY_WAY_BANK_ELECTRICITY             = 1;
    public const PAY_WAY_BANK_GAS                     = 2;
    public const PRODUCT_TYPE_SUBSCRIBER_TRANSMISSION = 3;
    public const PRODUCT_TYPE_FIRE_MAINTENANCE        = 4;
    public const PAY_WAY_BANK_FIRE_ENGINEERING        = 5;
    public const PRODUCT_TYPE_FIRE_STATION            = 6;
    public const PRODUCT_TYPE_OTHER                   = 7;

    public static array $formatProductTypeMaps = [
        self::PAY_WAY_BANK_ELECTRICITY             => '智慧用电',
        self::PAY_WAY_BANK_GAS                     => '智慧燃气',
        self::PRODUCT_TYPE_SUBSCRIBER_TRANSMISSION => '用传装置',
        self::PRODUCT_TYPE_FIRE_MAINTENANCE        => '消防维保',
        self::PAY_WAY_BANK_FIRE_ENGINEERING        => '消防工程',
        self::PRODUCT_TYPE_FIRE_STATION            => '消防站建设',
        self::PRODUCT_TYPE_OTHER                   => '其他',
    ];
}
