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

    public function node()
    {
        return $this->belongsTo(Node::class, 'node_id', 'order_node_ids')
            ->whereRaw("FIND_IN_SET(node.node_id, order.order_node_ids)");
    }
}
