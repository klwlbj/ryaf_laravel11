<?php

namespace App\Http\Controllers\Admin;

use App\Models\Area;
use App\Models\Node;
use App\Http\Logic\ResponseLogic;
use Illuminate\Support\Facades\Storage;

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

    public function getList2()
    {
        // 从文件中读取JSON数据
        $jsonData = Storage::disk('local')->get('area2.json');

        if ($jsonData === false) {
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }
        return ResponseLogic::apiResult(0, 'ok', [
            'areaList' => json_decode($jsonData),
        ]);
    }

/*    public function getBaiyunAreaList()
    {
        // 从文件中读取JSON数据
        $jsonData = Storage::disk('local')->get('area2.json');

        if ($jsonData === false) {
            return ResponseLogic::apiErrorResult(ResponseLogic::getMsg());
        }

        $collection = collect(json_decode($jsonData));

        $filteredCollection = $collection->where('value', '=', 5);// 筛选白云区下的

        // 如果需要将结果转换为数组，可以使用如下方式
        $filteredArray = $filteredCollection->values()->all();
        return ResponseLogic::apiResult(0, 'ok', [
            'areaList' => $filteredArray,
        ]);
    }*/

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

    public function generateJson2()
    {
        // 获取所有区级数据
        $district = Node::where('node_level', 2)
            ->where('node_parent_id', 4)
            ->with('children.children.children.children.children') // 预加载
            ->get();

        // 转换为数组并组装成 JSON 格式
        $jsonData = $district->map(function ($district) {
            return [
                'value'    => $district->node_id,
                'label'    => $district->node_name,
                'children' => $district->children->map(function ($street) {
                    return [
                        'value'    => $street->node_id,
                        'label'    => $street->node_name,
                        'children' => $street->children->map(function ($community) {
                            return [
                                'value'    => $community->node_id,
                                'label'    => $community->node_name,
                                'children' => $community->children->map(function ($community2) {
                                    return [
                                        'value'    => $community2->node_id,
                                        'label'    => $community2->node_name,
                                        'children' => $community2->children->map(function ($community3) {
                                            return [
                                                'value' => $community3->node_id,
                                                'label' => $community3->node_name,
                                            ];
                                        }),
                                    ];
                                }),
                            ];
                        }),
                    ];
                }),
            ];
        })->toJson();
        // 将JSON数据写入到文件中
        Storage::disk('local')->put('area2.json', $jsonData);
        // 输出生成的 JSON 数据
        echo $jsonData;
    }
}
