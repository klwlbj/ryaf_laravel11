<?php

namespace App\Http\Library\OneNetApis;

class OneNetDeviceManagement
{
    public function batchCreate($data,$userId,$accessKey)
    {
        return OneNetSdkCore::sendSDkRequest('/device/batch-create',$data,$userId,$accessKey);

    }
}
