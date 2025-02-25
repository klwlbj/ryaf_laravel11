<?php

namespace App\Console\Commands;

use App\Http\Library\AepApis\Aep_device_management;
use App\Http\Library\OneNetApis\OneNetDeviceManagement;
use App\Http\Logic\Device\SmokeDetectorLogic;
use App\Http\Logic\ToolsLogic;
use App\Models\DetectorImportTask;
use App\Models\SmokeDetector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DeviceImportTask extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:device-import-task';

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
//        DB::setDefaultConnection('mysql2');

        ini_set( 'max_execution_time', 72000 );
        ini_set( 'memory_limit', '2048M');

        $list = DetectorImportTask::query()->where(['deim_status' => 0])->limit(100)->select([
            'deim_id',
            'deim_imei',
            'deim_brand_name',
            'deim_model_name',
            'deim_database_status',
            'deim_aep_status',
            'deim_onenet_status'
        ])->get()->toArray();

        if(empty($list)){
            return true;
        }

        $ids = array_column($list,'deim_id');

        #置为可执行状态
        DetectorImportTask::query()->where(['deim_status' => 0])->whereIn('deim_id',$ids)->update(['deim_status' => 1]);


        foreach ($list as $key => $value){
            SmokeDetectorLogic::getInstance()->importDevice($value);
        }

        return true;
    }
}
