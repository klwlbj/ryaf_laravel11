<?php

namespace App\Http\Controllers\Admin;

use App\Models\Area;
use Illuminate\Http\Request;
use App\Http\Logic\ResponseLogic;
use App\Http\Logic\AdvancedOrderLogic;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Logic\MaterialManufacturerLogic;

class AreaController
{
    public function getList()
    {
        // 从文件中读取JSON数据
        $jsonData = Storage::disk('local')->get('area.json');

        if ($jsonData === false) {
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0, 'ok', [
            'areaList' => json_decode($jsonData),
        ]);
    }

    public function generateJson()
    {
        // 获取所有区级数据
        $district = Area::where('level', 2)
            ->where('status', 1)
            ->with('children.children') // 预加载
            ->get();

        // 转换为数组并组装成 JSON 格式
        $jsonData = $district->map(function ($district) {
            return [
                'value'    => $district->id,
                'label'    => $district->name,
                'children' => $district->children->map(function ($street) {
                    return [
                        'value'    => $street->id,
                        'label'    => $street->name,
                        'children' => $street->children->map(function ($community) {
                            return [
                                'value' => $community->id,
                                'label' => $community->name,
                            ];
                        }),
                    ];
                }),
            ];
        })->toJson();
        // 将JSON数据写入到文件中
        Storage::disk('local')->put('area.json', $jsonData);
        // 输出生成的 JSON 数据
        echo $jsonData;
    }
}
