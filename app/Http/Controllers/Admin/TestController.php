<?php

namespace App\Http\Controllers\Admin;

use DateInterval;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestController
{
    public function getList(Request $request)
    {
        set_time_limit(300);
        $isBaiyun = false;
        $recentMonths = 5;
        $startDate = new DateTime();
        $endDate   = new DateTime('Monday this week');
        $startDate->modify('-5 weeks');

        $weekdays = [];

        $weeks = 5;
        for ($i = 1;$i <= $weeks; $i++) {
            $weekdays[] = [$endDate->modify("-1 days")->format('Y-m-d 00:00:00'), $endDate->modify("-6 days")->format('Y-m-d 23:59:59')];
        }

        $weekdays= [
/*            [
                '2024-09-30 23:59:59',
                '2024-09-01 00:00:00',
            ],*/
            [
                '2024-10-31 23:59:59',
                '2024-10-01 00:00:00',
            ],
        ];
        // dd($weekdays);
        $list = [];

        foreach ($weekdays as $weekday) {
            $list[$weekday[0]] = DB::connection('mysql2')->select('
            SELECT
                a.smde_model_name as "型号",
                CONCAT(ROUND((alert_smoke_num / smde_num) * 100, 2), "%") AS "设备报警率",
                CONCAT(ROUND((alert_num / smde_num) * 100, 2), "%") AS "报警率",
                alert_smoke_num as "报警总设备数",
                alert_num as "报警总数",
                smde_num as "总设备数"
            FROM
                (
                SELECT
                    smde_model_name,
                    count( iot_notification_alert.iono_id ) AS alert_num,
                    count( DISTINCT smde_id ) alert_smoke_num 
                FROM
                    smoke_detector
                    LEFT JOIN iot_notification_alert ON iono_smde_id = smde_id 
                WHERE
                    smde_node_ids like "%,3,4,5%" ##仅限白云区
                    and
                    smde_crt_time <= ?
                    AND iono_crt_time <= ?
                    AND iono_crt_time >= ?
                    AND smde_model_name IN ( "HM-618PH-NB", "SA-JTY-GD02C", "YL-IOT-YW03", "HM-5HA-NB", "HM-608PH-NB金属防尘网", "HM-608PH-NB" ) 
                    AND iot_notification_alert.iono_type = 1 
                GROUP BY
                    smde_model_name 
                ) a
                LEFT JOIN (
                SELECT
                    count( 1 ) AS smde_num,
                    smde_model_name 
                FROM
                    smoke_detector 
                WHERE
                    smde_crt_time <= ? AND
                    smde_node_ids like "%,3,4,5%" and
                    smde_model_name IN ( "HM-618PH-NB", "SA-JTY-GD02C", "YL-IOT-YW03", "HM-5HA-NB", "HM-608PH-NB金属防尘网", "HM-608PH-NB", "HS2SA" ) 
                GROUP BY
                smde_model_name 
                ) b ON b.smde_model_name = a.smde_model_name
                ', [$weekday[1], $weekday[0], $weekday[1], $weekday[1]]);
        }

        return $list;
    }
}
