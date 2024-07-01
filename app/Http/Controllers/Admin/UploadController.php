<?php

namespace App\Http\Controllers\Admin;

use App\Http\Logic\ResponseLogic;
use App\Http\Logic\ToolsLogic;
use App\Http\Logic\UploadLogic;
use Illuminate\Http\Request;

class UploadController
{
    public function upload(Request $request)
    {
        $params = $request->all();
        $params['file'] = $request->file('file');

        $res = UploadLogic::getInstance()->upload($params);
        if($res === false){
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0,'ok',['url' => $res]);
    }
}
