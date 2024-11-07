<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\SmokeDetector;
use App\Http\Logic\ResponseLogic;
use Illuminate\Support\Facades\DB;
use App\Models\IotNotificationAlert;
use Illuminate\Support\Facades\Validator;

class SecurityController
{
    public function total()
    {
        $count        = SmokeDetector::query()->count();
        $onlineCount  = SmokeDetector::query()->where('smde_online_real', 1)->count();
        $offlineCount = SmokeDetector::query()->where('smde_online_real', 0)->count();

        $res = [
            'total_count'   => $count,
            'online_count'  => $onlineCount,
            'offline_count' => $offlineCount,
        ];

        return ResponseLogic::apiResult(0, 'ok', $res);
    }

    public function unitTotal(Request $request)
    {
        // 设置脚本最大执行时间为 360 秒
        set_time_limit(100);

        $params = $request->all();

        // 进行验证
        $validator = Validator::make($params, [
            'page'      => 'nullable|int',
            'page_size' => 'nullable|int',
        ]);

        if ($validator->fails()) {
            return ResponseLogic::apiErrorResult($validator->errors()->first());
        }
        // 获取单个参数
        $page     = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 10;
        $point    = ($page - 1) * $pageSize;

        $query = SmokeDetector::select('smde_place_id', 'plac_name', DB::raw('count(*) as count, sum(smde_online_real = "1") as online_count, sum(smde_online_real = "0") as offline_count'))
            ->where('smde_place_id', "!=", 0)
            ->orderBy('count', 'desc')
            ->groupBy('smde_place_id')
            ->leftJoin('place', 'plac_id', '=', 'smde_place_id');

        $count = DB::selectOne("
            select count('sub.smde_place_id') as count from ({$query->toSql()}) as sub
            ", $query->getQuery()->getBindings());

        $list = $query
            ->offset($point)
            ->limit($pageSize)
            ->get();
        return ResponseLogic::apiResult(0, 'ok', ['list' => $list, 'count' => $count->count]);
    }

    public function list(Request $request)
    {
        $params = $request->all();
        // 进行验证
        $validator = Validator::make($params, [
            'page'      => 'nullable|int',
            'page_size' => 'nullable|int',
            'unit_name' => "nullable",
            'status'    => "nullable|in:0,1",
        ]);

        if ($validator->fails()) {
            return ResponseLogic::apiErrorResult($validator->errors()->first());
        }
        // 获取单个参数
        $page     = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 10;
        $point    = ($page - 1) * $pageSize;

        $query = SmokeDetector::query()
            ->where('smde_place_id', "!=", 0)
            ->when(isset($params['unit_name']), function ($query) use ($params) {
                return $query->where('plac_name', 'like', '%' . $params['unit_name'] . '%')
                    ->leftJoin('place', 'plac_id', '=', 'smde_place_id');
            })
            ->when(isset($params['status']), function ($query) use ($params) {
                return $query->where('smde_online_real', $params['status']);
            });
        $count  = $query->count();
        $select = ['smde_id', 'smde_type', 'smde_position', 'smde_lng', 'smde_lat', 'smde_threshold_temperature', 'smde_last_heart_beat', 'smde_online_real'];

        if (isset($params['unit_name'])) {
            $select[] = 'plac_name';
            $select[] = 'plac_address';
        }

        $list = $query->select($select)
        ->orderBy('smde_id', 'desc')
        ->offset($point)
        ->limit($pageSize)
        ->get();

        return ResponseLogic::apiResult(0, 'ok', ['list' => $list, 'count' => $count]);
    }

    public function alertTotal(Request $request)
    {
        $params = $request->all();
        // 进行验证
        $validator = Validator::make($params, [
            'start_time' => "required|date_format:Y-m-d H:i:s",
            'end_time'   => "required|date_format:Y-m-d H:i:s",
        ]);

        if ($validator->fails()) {
            return ResponseLogic::apiErrorResult($validator->errors()->first());
        }
        // 获取单个参数
        $startTime = $params['start_time'] ?? '';
        $endTime   = $params['end_time'] ?? '';

        $count = IotNotificationAlert::query()
            ->where('iono_crt_time', '>=', $startTime)
            ->where('iono_crt_time', '<=', $endTime)
            ->whereIn('iono_type', [1, 3])
            ->count();

        return ResponseLogic::apiResult(0, 'ok', ['count' => $count]);
    }

        public function unitAlertTotal(Request $request)
        {
            $params = $request->all();

            // 进行验证
            $validator = Validator::make($params, [
                'page'       => 'nullable|int',
                'page_size'  => 'nullable|int',
                'start_time' => "required|date_format:Y-m-d H:i:s",
                'end_time'   => "required|date_format:Y-m-d H:i:s",
            ]);

            if ($validator->fails()) {
                return ResponseLogic::apiErrorResult($validator->errors()->first());
            }
            // 获取单个参数
            $page      = $params['page'] ?? 1;
            $pageSize  = $params['page_size'] ?? 10;
            $point     = ($page - 1) * $pageSize;
            $startTime = $params['start_time'] ?? '';
            $endTime   = $params['end_time'] ?? '';

            $query = IotNotificationAlert::query()
                ->select('smde_place_id', 'plac_name', DB::raw('count(*) as count'))
                ->where('smde_place_id', "!=", 0)
                ->whereNotNull('smde_place_id')
                ->where('iono_crt_time', '>=', $startTime)
                ->where('iono_crt_time', '<=', $endTime)
                ->whereIn('iono_type', [1, 3])
                ->leftJoin('smoke_detector', 'smde_id', '=', 'iono_smde_id')
                ->leftJoin('place', 'plac_id', '=', 'smde_place_id')
                ->orderBy('count', 'desc')
                ->groupBy('smde_place_id');

            // todo 多种方式子查询
            $count = DB::selectOne("
            select count('sub.smde_place_id') as count from ({$query->toSql()}) as sub
            ", $query->getQuery()->getBindings());

            $list = $query
                ->offset($point)
                ->limit($pageSize)
                ->get();

            return ResponseLogic::apiResult(0, 'ok', ['list' => $list, 'count' => $count->count]);
        }

    public function alertList(Request $request)
    {
        $params = $request->all();
        // 进行验证
        $validator = Validator::make($params, [
            'page'        => 'nullable|int',
            'page_size'   => 'nullable|int',
            'unit_name'   => "nullable",
            'iono_status' => "nullable|in:1,2,3,4",
        ]);

        if ($validator->fails()) {
            return ResponseLogic::apiErrorResult($validator->errors()->first());
        }
        // 获取单个参数
        $page     = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 10;
        $point    = ($page - 1) * $pageSize;

        $ionoStatusList = [
            0 => "处理中", 1 => "已处理", 2 => "已忽略", 3 => "待处理", 4 => "自动恢复",
        ];

        $query = IotNotificationAlert::query()
            ->whereIn('iono_type', [1, 3])
            ->when(isset($params['unit_name']), function ($query) use ($params) {
                return $query->where('plac_name', 'like', '%' . $params['unit_name'] . '%')
                    ->leftJoin('smoke_detector', 'smde_id', '=', 'iono_smde_id')
                    ->leftJoin('place', 'plac_id', '=', 'smde_place_id');
            })
            ->when(isset($params['iono_status']), function ($query) use ($params, $ionoStatusList) {
                return $query->where('iono_status', $ionoStatusList[$params['iono_status']] ?? '');
            });
        $count  = $query->count();
        $select = ['iono_id', 'iono_msg_imei', 'iono_type', 'iono_crt_time', 'iono_alert_status', 'iono_status', 'iono_remark', 'iono_conclusion'];

        if (isset($params['unit_name'])) {
            $select[] = 'plac_name';
        }
        $list = $query->select($select)
            ->offset($point)
            ->limit($pageSize)
            ->get();

        return ResponseLogic::apiResult(0, 'ok', ['list' => $list, 'count' => $count]);
    }
}
