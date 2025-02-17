<?php

namespace App\Console\Commands;

use App\Http\Library\AepApis\Aep_device_management;
use App\Http\Library\OneNetApis\OneNetDeviceManagement;
use App\Http\Logic\ToolsLogic;
use App\Models\SmokeDetector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class IccidUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:iccid-update';

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
        DB::setDefaultConnection('mysql2');

        ini_set( 'max_execution_time', 72000 );
        ini_set( 'memory_limit', '2048M' );

        $list = SmokeDetector::query()
            ->leftJoin('iot_notification_self_check','smde_imei','=','iono_imei')
            ->whereRaw("(smde_nb_iid2 = '' or smde_nb_iid2 is null) and smde_order_id > 0
and iono_id is not null and (iono_nb_iccid != '' and iono_nb_iccid is not null)
GROUP BY smde_imei,iono_nb_iccid")
            ->select([
                'smde_imei',
                'iono_nb_iccid'
            ])->get()->toArray();


        foreach ($list as $key => $value) {
            SmokeDetector::query()->where(['smde_imei' => $value['smde_imei']])->update(['smde_nb_iid2' => $value['iono_nb_iccid']]);
        }
    }

}
