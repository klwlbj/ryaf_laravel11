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

        if(!empty($params['parent_id'])){
            $ids = Node::getNodeChild($params['parent_id'],false);
            $query->whereIn('node_id',$ids);
        }

        return $query->select(['node_id','node_name'])->get()->toArray();
    }

    public function getTreeList($params)
    {
        $query = Node::query()->where(['node_enabled' => 1]);

        if(!empty($params['type'])){
            $query->where(['node_type' => $params['type']]);
        }

        if(!empty($params['parent_id'])){
            $ids = Node::getNodeChild($params['parent_id'],false);
            $query->whereIn('node_id',$ids);
        }

        $list = $query->select(['node_id','node_parent_id','node_name'])->get()->toArray();


        return ToolsLogic::toTree($list,4,'node_id','node_parent_id');
    }
}
