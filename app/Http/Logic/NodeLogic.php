<?php

namespace App\Http\Logic;

use App\Models\Node;

class NodeLogic extends BaseLogic
{
    public function getAllList($params)
    {
        $query = Node::query()->where(['node_enabled' => 1]);

        if(!empty($params['type'])){
            $query->where(['node_type' => $params['type']]);
        }

        return $query->select(['node_id','node_name'])->get()->toArray();
    }
}
