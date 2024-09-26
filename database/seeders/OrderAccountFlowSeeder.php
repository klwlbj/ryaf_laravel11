<?php

namespace Database\Seeders;

use App\Models\Order;
use Illuminate\Support\Carbon;
use Illuminate\Database\Seeder;
use App\Models\OrderAccountFlow;
use Illuminate\Support\Facades\DB;

class OrderAccountFlowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $list        = Order::query()->where('order_id', '>', 34550)->get();
            $stageList   = [1, 1, 1, 1, 6, 36];
            $transaction = [];
            foreach ($list as $item) {
                $time  = strtotime($item->order_actual_delivery_date);
                $count = $item->smokeDetectors->count();

                $item->order_pay_cycle = $stageList[array_rand($stageList)];
                if ($count !== 0) {
                    $item->order_account_receivable = ($count) * 240;
                    // 随机流水数
                    $orderAccountFlow           = random_int(1, 6);
                    $item->order_funds_received = random_int($orderAccountFlow, $item->order_account_receivable);
                } else {
                    $item->order_account_receivable = $item->order_amount_given = $item->order_funds_received = 0;
                    $orderAccountFlow               = 0;
                }
                $item->save();

                for ($i = 1; $i <= $orderAccountFlow; $i++) {
                    if ($i === 1) {
                        $balance = $item->order_funds_received;
                    }
                    if (1 > $balance - ($orderAccountFlow - $i)) {
                        var_dump($balance, $orderAccountFlow, $i, $item->order_account_receivable);
                    }
                    // 随机流水，欠款or还款
                    $randomAmount = random_int(-10, $balance - ($orderAccountFlow - $i)); // 生成随机金额
                    if ($i === $orderAccountFlow) {
                        $randomAmount = $balance;
                    }
                    $balance       = $balance - $randomAmount;
                    $payTime       = Carbon::createFromTimestamp(mt_rand($time, time()));// 生成随机日期
                    $transaction[] = [
                        'orac_funds_received' => $randomAmount,
                        'orac_remark'         => 'Payment for transaction ' . $i,
                        'orac_datetime'       => $payTime->format('Y-m-d H:i:s'),
                        'orac_status'         => 2,
                        'orac_type'           => ($payTime->format('Y-m') == date('Y-m', $time)) ? 1 : 2,
                        'orac_pay_way'        => random_int(1, 5),
                        'orac_approve_time'   => date('Y-m-d H:i:s', time()),
                        'orac_approve_id'     => 1,
                        'orac_operator_id'    => 1,
                        'orac_order_id'       => $item->order_id,
                    ];
                }
            }
            // 清空表
            OrderAccountFlow::query()->delete();
            // 将流水记录保存到数据库
            OrderAccountFlow::query()->insert($transaction);
        });
    }
}
