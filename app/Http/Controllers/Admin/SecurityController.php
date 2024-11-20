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
    //smde_user_ids like ',1345,%'
    // plac_user_ids like ',1345,%'
    public function total()
    {
        $query = SmokeDetector::select(DB::raw('count(*) as count, sum(smde_online = "1") as online_count, sum(smde_online = "0") as offline_count'))
            ->where('smde_place_id', "!=", 0)
            ->where('smde_user_ids', 'like', ',1345,%')
            ->whereNotNull('smde_place_id')
            // ->leftJoin('place', 'plac_id', '=', 'smde_place_id')
            ->first();

        $res = [
            'total_count'   => (int) $query->count,
            'online_count'  => (int) $query->online_count,
            'offline_count' => (int) $query->offline_count,
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

        $query = SmokeDetector::query()
            ->select('plac_node_id as node_id', 'node_name', 'node_level', DB::raw('count(*) as count, sum(smde_online = "1") as online_count, sum(smde_online = "0") as offline_count'))
            ->where('smde_place_id', "!=", 0)
            ->whereNotNull('plac_node_id')
            ->where('plac_user_ids', 'like', ',1345,%')
            ->leftJoin('place', 'plac_id', '=', 'smde_place_id')
            ->leftJoin('node', 'plac_node_id', '=', 'node_id')
            ->groupBy('plac_node_id');

        $count = DB::selectOne("
            select count('sub.plac_node_id') as count from ({$query->clone()->select('plac_node_id')->toSql()}) as sub
            ", $query->getQuery()->getBindings());

        $list = $query
            ->orderBy('count', 'desc')
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
            'node_id'   => 'nullable|int',
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
            ->where('plac_user_ids', 'like', ',1345,%')
            ->when(isset($params['node_id']), function ($query) use ($params) {
                return $query->where('plac_node_id', $params['node_id']);
            })
            ->when(isset($params['status']), function ($query) use ($params) {
                return $query->where('smde_online', $params['status']);
            })
            ->leftJoin('place', 'plac_id', '=', 'smde_place_id')
            ->leftJoin('node', 'plac_node_id', '=', 'node_id')
        ;
        $count  = $query->count();
        $select = ['node_id', 'node_name', 'smde_id', 'smde_type', 'smde_position', 'smde_lng', 'smde_lat',
            'smde_last_temperature', 'smde_last_smokescope', 'smde_online',
            'plac_name', 'plac_address',
        ];

        $list = $query->select($select)
        ->orderBy('smde_id', 'desc')
        ->offset($point)
        ->limit($pageSize)
        ->get()
        ->transform(function ($item) {
            $item->smde_last_temperature = $item->smde_last_temperature / 100;
            $item->smde_last_smokescope  = $item->smde_last_smokescope / 100;
            return $item;
        });

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

        $smokeDetectorIMEIs = SmokeDetector::query()->where('smde_user_ids', 'like', ',1345,%')->pluck('smde_imei');

        $count = IotNotificationAlert::query()
            ->where('iono_crt_time', '>=', $startTime)
            ->where('iono_crt_time', '<=', $endTime)
            ->whereIn('iono_msg_imei', $smokeDetectorIMEIs)
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

            $smokeDetectorIMEIs = SmokeDetector::query()->where('smde_user_ids', 'like', ',1345,%')->pluck('smde_imei');

            $query = IotNotificationAlert::query()
                ->select('plac_node_id as node_id', 'node_name', DB::raw('count(*) as count'))
                ->where('smde_place_id', "!=", 0)
                ->whereNotNull('smde_place_id')
                ->where('iono_crt_time', '>=', $startTime)
                ->where('iono_crt_time', '<=', $endTime)
                ->whereIn('iono_msg_imei', $smokeDetectorIMEIs)
                ->whereIn('iono_type', [1, 3])
                ->leftJoin('smoke_detector', 'smde_id', '=', 'iono_smde_id')
                ->leftJoin('place', 'plac_id', '=', 'smde_place_id')
                ->leftJoin('node', 'plac_node_id', '=', 'node_id')
                ->groupBy('plac_node_id');

            $count = DB::selectOne("
            select count('sub.plac_node_id') as count from ({$query->clone()->select('plac_node_id')->toSql()}) as sub
            ", $query->getQuery()->getBindings());

            $list = $query
                ->orderBy('count', 'desc')
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
            'node_id'     => 'nullable|int',
            'iono_status' => "nullable|in:1,2,3,4",
            'start_time'  => "required|date_format:Y-m-d H:i:s",
            'end_time'    => "required|date_format:Y-m-d H:i:s",
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

        $ionoStatusList = [
            0 => "处理中", 1 => "已处理", 2 => "已忽略", 3 => "待处理", 4 => "自动恢复",
        ];

        $query = IotNotificationAlert::query()
            ->whereIn('iono_type', [1, 3])
            ->where('iono_crt_time', '>=', $startTime)
            ->where('iono_crt_time', '<=', $endTime)
            ->where('plac_user_ids', 'like', ',1345,%')
            ->when(isset($params['node_id']), function ($query) use ($params) {
                return $query->where('plac_id', $params['node_id']);
            })
            ->when(isset($params['iono_status']), function ($query) use ($params, $ionoStatusList) {
                return $query->where('iono_status', $ionoStatusList[$params['iono_status']] ?? '');
            })
            ->leftJoin('smoke_detector', 'smde_id', '=', 'iono_smde_id')
            ->leftJoin('place', 'plac_id', '=', 'smde_place_id')
            ->leftJoin('node', 'plac_node_id', '=', 'node_id')
            ->leftJoin('user', 'user_id', '=', 'plac_user_id');
        $count  = $query->count();
        $select = ['node_name', 'iono_id', 'plac_name', 'user_mobile', 'user_name', 'iono_msg_imei', 'plac_address', 'iono_type', 'iono_crt_time', 'iono_status', 'iono_remark', 'iono_conclusion'];

        $list = $query->select($select)
            ->offset($point)
            ->orderBy('iono_crt_time', 'desc')
            ->limit($pageSize)
            ->get();

        return ResponseLogic::apiResult(0, 'ok', ['list' => $list, 'count' => $count]);
    }
}
