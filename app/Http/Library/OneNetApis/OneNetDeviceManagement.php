<?php

namespace App\Http\Library\OneNetApis;

class OneNetDeviceManagement
{
    public function batchCreate($data,$userId,$accessKey)
    {
        return OneNetSdkCore::sendSDkRequest('/device/batch-create',$data,$userId,$accessKey);
    }

    public function create($data,$userId,$accessKey)
    {
        return OneNetSdkCore::sendSDkRequest('/device/create',$data,$userId,$accessKey);
    }


    public function delete($data,$userId,$accessKey)
    {
        return OneNetSdkCore::sendSDkRequest('/device/delete',$data,$userId,$accessKey);
    }
}
