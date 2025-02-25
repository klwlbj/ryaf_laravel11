<?php

namespace App\Http\Logic;

use App\Http\Logic\Excel\ExportLogic;
use App\Models\Node;
use App\Models\SmokeDetector;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ReportLogic extends BaseLogic
{
    public function online($params)
    {
//        $nodeId = $params['node_id'] ?? 26;
//
//        $nodeList = Node::query()
//            ->where(function (Builder $q) use ($params){
//
//            })->select([''])
        $resultList = [
            [
                'node_name' => '石门街道',
                'node_ids' => ",5,26,",
                'count' => 0,
                '24_count' => 0,
                '24_rate' => 0,
                '48_count' => 0,
                '48_rate' => 0,
            ],
            [
                'node_name' => '红星联社',
                'node_ids' => ",5,26,352,",
                'count' => 0,
                '24_count' => 0,
                '24_rate' => 0,
                '48_count' => 0,
                '48_rate' => 0,
            ],
            [
                'node_name' => '滘心联社',
                'node_ids' => ",5,26,353,",
                'count' => 0,
                '24_count' => 0,
                '24_rate' => 0,
                '48_count' => 0,
                '48_rate' => 0,
            ],
            [
                'node_name' => '鸦岗联社',
                'node_ids' => ",5,26,350,",
                'count' => 0,
                '24_count' => 0,
                '24_rate' => 0,
                '48_count' => 0,
                '48_rate' => 0,
            ],
            [
                'node_name' => '朝阳联社',
                'node_ids' => ",5,26,351,",
                'count' => 0,
                '24_count' => 0,
                '24_rate' => 0,
                '48_count' => 0,
                '48_rate' => 0,
            ],
        ];

        $totalList = SmokeDetector::query()
            ->leftJoin('order','order_id','=','smde_order_id')
            ->whereIn('smde_type',["烟感","温感"])
            ->where('smde_order_id','>',0)
            ->where('smde_place_id','>',0)
            ->where('order_status','=','交付完成')
            ->where('smde_node_ids','like',"%,5,26,%")
            ->select([
                DB::raw("count(1) as count"),
                'smde_node_ids'
            ])->groupBy('smde_node_ids')->get()->toArray();

        $onlineList1 = SmokeDetector::query()
            ->leftJoin('order','order_id','=','smde_order_id')
            ->whereIn('smde_type',["烟感","温感"])
            ->where('smde_order_id','>',0)
            ->where('smde_place_id','>',0)
            ->where('order_status','=','交付完成')
            ->where('smde_node_ids','like',"%,5,26,%")
            ->where('smde_yunchuang_id','>',0)
            ->whereRaw("(COALESCE(smde_fake_heart_beat,smde_last_heart_beat) >= (NOW() - INTERVAL 1 DAY))")
            ->select([
                DB::raw("count(1) as count"),
                'smde_node_ids'
            ])->groupBy('smde_node_ids')->get()->toArray();

        $onlineList2 = SmokeDetector::query()
            ->leftJoin('order','order_id','=','smde_order_id')
            ->whereIn('smde_type',["烟感","温感"])
            ->where('smde_order_id','>',0)
            ->where('smde_place_id','>',0)
            ->where('order_status','=','交付完成')
            ->where('smde_node_ids','like',"%,5,26,%")
            ->where('smde_yunchuang_id','>',0)
            ->whereRaw("(COALESCE(smde_fake_heart_beat,smde_last_heart_beat) >= (NOW() - INTERVAL 2 DAY))")
            ->select([
                DB::raw("count(1) as count"),
                'smde_node_ids'
            ])->groupBy('smde_node_ids')->get()->toArray();


        foreach ($resultList as $key => &$value){
            foreach ($totalList as $k => $v){
                if (strpos($v['smde_node_ids'], $value['node_ids']) !== false) {
                    $value['count'] += $v['count'];
                }
            }

            foreach ($onlineList1 as $k => $v){
                if (strpos($v['smde_node_ids'], $value['node_ids']) !== false) {
                    $value['24_count'] += $v['count'];
                }
            }

            foreach ($onlineList2 as $k => $v){
                if (strpos($v['smde_node_ids'], $value['node_ids']) !== false) {
                    $value['48_count'] += $v['count'];
                }
            }


            $value['24_rate'] = round($value['24_count']/$value['count'] * 100,1) . '%';
            $value['48_rate'] = round($value['48_count']/$value['count'] * 100,1) . '%';
        }

        if(!empty($params['export'])){
            $title = ['街道','设备总数','24小时在线数','48小时在线数','24小时在线率','48小时在线率'];

            $exportData = [];
            $config = [
                'bold' => [ExportLogic::getColumnName(1) . '1:' . ExportLogic::getColumnName(count($title)) . '1' => true],
                'width' => ['A'=>20,'B'=>20,'C'=>20,'D'=>20,'E'=>20,'F'=>20]
            ];

            $row = 2;
            foreach ($resultList as $key => $value){
                $exportData[] = [
                    $value['node_name'],
                    $value['count'],
                    $value['24_count'],
                    $value['48_count'],
                    $value['24_rate'],
                    $value['48_rate'],
                ];

                $row++;
            }

            $config['horizontal_center'] = [ExportLogic::getColumnName(1) . '1:' . ExportLogic::getColumnName(count($title)) . $row => true];

            $config['wrap_text'] = [ExportLogic::getColumnName(1) . '1:' . ExportLogic::getColumnName(count($title)) . $row => true];

            return ExportLogic::getInstance()->export($title,$exportData,'烟感在线率',$config);
        }

        unset($value);
        return ['list' => $resultList];
    }
}
