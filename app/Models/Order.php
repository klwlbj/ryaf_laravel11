<?php

namespace App\Models;

class Order extends BaseModel
{
    protected $table   = 'order';
    public $timestamps = null;
    public $primaryKey = 'order_id';

    public function smokeDetectors()
    {
        return $this->hasMany(smokeDetector::class, 'smde_order_id', 'order_id');
    }
}
