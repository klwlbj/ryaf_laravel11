<?php

namespace App\Models;

class SmokeDetector extends BaseModel
{
    protected $table   = 'smoke_detector';
    public $timestamps = null;

    public $primaryKey = 'smde_id';

    // protected $fillable = ['smde_order_id'];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'smde_order_id');
    }
}
