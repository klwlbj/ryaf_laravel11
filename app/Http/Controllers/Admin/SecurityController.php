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
    //smde_user_ids like '%,1345,%'
    // plac_user_ids like '%,1345,%'
    public function total()
    {
        $query = SmokeDetector::select(DB::raw('count(*) as count, sum(smde_online = "1") as online_count, sum(smde_online = "0") as offline_count'))
            ->where('smde_place_id', "!=", 0)
            ->where('smde_user_ids', 'like', '%,1345,%')
            ->whereNotNull('smde_place_id')
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
            ->select('smde_place_id as plac_id', 'plac_name', DB::raw('count(*) as count, sum(smde_online = "1") as online_count, sum(smde_online = "0") as offline_count'))
            ->where('smde_place_id', "!=", 0)
            ->where('smde_user_ids', 'like', '%,1345,%')
            ->groupBy('smde_place_id')
            ->leftJoin('place', 'plac_id', '=', 'smde_place_id');

        $count = DB::selectOne("
            select count('sub.smde_place_id') as count from ({$query->clone()->select('smde_place_id')->toSql()}) as sub
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
            'plac_id'   => 'required_without:plac_name|int',
            'plac_name' => "required_without:plac_id|nullable",
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
            ->where('smde_user_ids', 'like', '%,1345,%')
            ->when(isset($params['plac_name']), function ($query) use ($params) {
                return $query->where('plac_name', $params['plac_name']);
            })
            ->when(isset($params['plac_id']), function ($query) use ($params) {
                return $query->where('plac_id', $params['plac_id']);
            })
            ->when(isset($params['status']), function ($query) use ($params) {
                return $query->where('smde_online', $params['status']);
            })
            //     ->where('plac_name', 'like', '%' . $params['plac_name'] . '%')
            ->leftJoin('place', 'plac_id', '=', 'smde_place_id');
        $count  = $query->count();
        $select = ['smde_id', 'smde_type', 'smde_position', 'smde_lng', 'smde_lat', 'smde_threshold_temperature', 'smde_last_heart_beat', 'smde_online'];

        if (isset($params['plac_name'])) {
            $select[] = 'plac_name';
            $select[] = 'plac_address';
            $select[] = 'plac_id';
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

        $smokeDetectorIMEIs = SmokeDetector::query()->where('smde_user_ids', 'like', '%,1345,%')->pluck('smde_imei');

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

            $smokeDetectorIMEIs = SmokeDetector::query()->where('smde_user_ids', 'like', '%,1345,%')->pluck('smde_imei');

            $query = IotNotificationAlert::query()
                ->select('smde_place_id as plac_id', 'plac_name', DB::raw('count(*) as count'))
                ->where('smde_place_id', "!=", 0)
                ->whereNotNull('smde_place_id')
                ->where('iono_crt_time', '>=', $startTime)
                ->where('iono_crt_time', '<=', $endTime)
                ->whereIn('iono_msg_imei', $smokeDetectorIMEIs)
                ->whereIn('iono_type', [1, 3])
                ->leftJoin('smoke_detector', 'smde_id', '=', 'iono_smde_id')
                ->leftJoin('place', 'plac_id', '=', 'smde_place_id')
                ->groupBy('smde_place_id');

            // todo 多种方式子查询
            $count = DB::selectOne("
            select count('sub.smde_place_id') as count from ({$query->clone()->select('smde_place_id')->toSql()}) as sub
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
            'plac_id'     => 'required_without:plac_name|int',
            'plac_name'   => "required_without:plac_id|nullable",
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
        $smokeDetectorIMEIs = SmokeDetector::query()->where('smde_user_ids', 'like', '%,1345,%')->pluck('smde_imei');

        $query = IotNotificationAlert::query()
            ->whereIn('iono_type', [1, 3])
            ->whereIn('iono_msg_imei', $smokeDetectorIMEIs)
            ->when(isset($params['plac_name']), function ($query) use ($params) {
                return $query->where('plac_name', $params['plac_name']);
            })
            ->when(isset($params['plac_id']), function ($query) use ($params) {
                return $query->where('plac_id', $params['plac_id']);
            })
            ->when(isset($params['iono_status']), function ($query) use ($params, $ionoStatusList) {
                return $query->where('iono_status', $ionoStatusList[$params['iono_status']] ?? '');
            })
            ->leftJoin('smoke_detector', 'smde_id', '=', 'iono_smde_id')
            ->leftJoin('place', 'plac_id', '=', 'smde_place_id');
        $count  = $query->count();
        $select = ['iono_id', 'iono_msg_imei', 'iono_type', 'iono_crt_time', 'iono_status', 'iono_remark', 'iono_conclusion'];

        if (isset($params['plac_name'])) {
            $select[] = 'plac_name';
        }
        $list = $query->select($select)
            ->offset($point)
            ->limit($pageSize)
            ->get();

        return ResponseLogic::apiResult(0, 'ok', ['list' => $list, 'count' => $count]);
    }
}
