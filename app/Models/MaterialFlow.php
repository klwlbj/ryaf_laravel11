<?php

namespace App\Models;

class MaterialFlow extends BaseModel
{
    protected $table   = 'material_flow';
    public $timestamps = null;

    public static $invoiceTypeArr = [
        '0' => '未确认',
        '1' => '专票',
        '2' => '普票',
    ];
}
