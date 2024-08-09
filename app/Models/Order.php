<?php

namespace App\Models;

class Order extends BaseModel
{
    protected $table   = 'order';
    public $timestamps = null;
    public $primaryKey = 'order_id';

    public function smokeDetectors()
    {
        return $this->hasMany(SmokeDetector::class, 'smde_order_id', 'order_id');
    }

    public function places()
    {
        return $this->hasMany(Place::class, 'plac_order_id', 'order_id');
    }

    public function orderAccountFlows()
    {
        return $this->hasMany(OrderAccountFlow::class, 'orac_order_id', 'order_id');
    }
}
