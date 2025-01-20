<?php

namespace App\Http\Logic;

class MaterialFlowConsumeLogic extends BaseLogic
{
    public function getList($params)
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 10;
        $point = ($page - 1) * $pageSize;


    }
}
