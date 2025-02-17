<?php

namespace App\Console\Commands;

use App\Http\Logic\ReceivableAccountLogic;
use App\Http\Logic\ToolsLogic;
use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:sync-order';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = date('Y-m-d', strtotime('-1 day'));

        $res = ReceivableAccountLogic::getInstance()->syncOrder([
            'start_date' => $date,
            'end_date' => $date,
        ]);

        ToolsLogic::writeLog('同步' . $date . '订单 res：','syncOrder',$res);

        return true;
    }
}
