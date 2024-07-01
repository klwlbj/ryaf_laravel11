<?php

namespace App\Http\Logic;

use Illuminate\Support\Facades\Storage;

class UploadLogic extends BaseLogic
{
    protected $type = [
        'material'  => 'material',
        'material_flow' => 'material_flow'
    ];

    /**
     * @param $params
     * @return array|bool|string
     */
    public function upload($params)
    {
        if(!isset($params['file']) || empty($params['file'])){
            ResponseLogic::setMsg('请选择文件');
            return false;
        }

        $path = $this->getPathName($params['type'] ?? '');
        $file = $params['file'];
        if (!$file->isValid()) {
            ResponseLogic::setMsg('上传文件不成功');
            return false;
        }

        // 2.是否符合文件类型 getClientOriginalExtension 获得文件后缀名
        $fileExtension = $file->getClientOriginalExtension();
//        if(!in_array($fileExtension, ['png', 'jpg', 'gif'])) {
//            Response::setMsg('文件格式有误');
//            return false;
//        }

        // 3.判断大小是否符合 2M
        $tmpFile = $file->getRealPath();
//        if (filesize($tmpFile) >= 2048000) {
//            return false;
//        }

        // 4.是否是通过http请求表单提交的文件
//        if (! is_uploaded_file($tmpFile)) {
//            return false;
//        }

        // 5.获取源文件名称
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        $fileName = $this->getFileName($path,$fileExtension,$originalName);
        if (Storage::disk('public')->put($fileName, file_get_contents($tmpFile))) {
            return Storage::url($fileName);
//            return 'storage/app/public/' . $fileName;
        }
        ResponseLogic::setMsg('上传失败');
        return false;
    }

    private function getFileName($path,$fileExtension, $originalName){
        $index = 1;
        $path = $path . '/' . date('Y-m-d');
        $originalName = md5($originalName);
        $fileName = $path . '/' . $originalName . '.' . $fileExtension;
        while (file_exists(storage_path('app/public/' . $fileName))) {
            $fileName = $path . '/' . $originalName . "({$index})" . '.' . $fileExtension;
            $index++;
        }

        return $fileName;
    }

    public function getPathName($type = null){
        return $this->type[$type] ?? 'other' ;
    }
}
