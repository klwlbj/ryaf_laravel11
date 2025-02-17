<?php

namespace App\Http\Library\QingNiao;

use App\Http\Logic\ResponseLogic;

class QingNiaoApi
{
    public function getUnitList($page = 1, $pageSize = 100)
    {
        $res = QingNiaoSdkCore::sendSDkRequest('/api/fireUnit/list',[
            'pageSize' => $pageSize,
            'pageNum' => $page,
        ]);

        if(!$res){
            return false;
        }

        return $res['data'];
    }

    public function getDeviceList($unitId ,$page = 1, $pageSize = 100)
    {
        $res = QingNiaoSdkCore::sendSDkRequest('/api/facilities/list',[
            'pageSize' => $pageSize,
            'pageNum' => $page,
            'fireUnitId' => $unitId
        ]);

        if(!$res){
            return false;
        }

        return $res['data'];
    }

    public function getLinkman($unitId ,$page = 1, $pageSize = 100)
    {
        $res = QingNiaoSdkCore::sendSDkRequest('/api/linkman/list',[
            'pageSize' => $pageSize,
            'pageNum' => $page,
            'type' => 1,
            'unitId' => $unitId
        ]);

        if(!$res){
            return false;
        }

        return $res['data'];
    }
}
