<?php

namespace App\Console\Commands;

use App\Http\Library\QingNiao\QingNiaoApi;
use App\Http\Logic\ReceivableAccountLogic;
use App\Http\Logic\ToolsLogic;
use App\Models\Admin;
use App\Models\AlertReceiver;
use App\Models\Place;
use App\Models\SmokeDetector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncQingNiao extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:sync-qing-niao';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    protected $apiUnit = null;

    public function __construct(){
        parent::__construct();
        $this->apiUnit = new QingNiaoApi();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $unitList = $this->getUnitList();
        print_r($unitList);die;
        #插入单位信息
        $placeInsert = [];
        $placeInertIds = [];
//        print_r($unitList);die;
        foreach ($unitList as $key => $unitItem){
            if(Place::query()->where(['plac_thpl_pk' => $unitItem['id'],'plac_thpl_id' => 7])->exists()){
                $placeInertIds[] = $unitItem['id'];
                continue;
            }

            $placeInsert[] = [
                'plac_node_id' => 115,
                'plac_node_ids' => ',3,4,61,115,',
                'plac_name' => $unitItem['name'],
                'plac_address' => $unitItem['address'],
                'plac_lng' => $unitItem['longitude'],
                'plac_lat' => $unitItem['latitude'],
                'plac_thpl_id' => 7,
                'plac_thpl_raw' => ToolsLogic::jsonEncode($unitItem),
                'plac_thpl_pk' => $unitItem['id'],
            ];
            $placeInertIds[] = $unitItem['id'];
            if(count($placeInsert) >= 100){
                Place::query()->insert($placeInsert);
                $placeInsert = [];
            }
        }

        if(!empty($placeInsert)){
            Place::query()->insert($placeInsert);
            $placeInsert = [];
        }

        $deviceInsert = [];

        #同步设备信息
        $placeList = Place::query()->where(['plac_thpl_id' => 7])
            ->whereIn('plac_thpl_pk',$placeInertIds)
            ->select(['plac_id','plac_thpl_pk'])
            ->get()->toArray();

        foreach ($placeList as $key => $placeItem){
            $deviceList = $this->getDeviceList($placeItem['plac_thpl_pk']);
//            print_r($deviceList);die;
            foreach ($deviceList as $k => $deviceItem){
                if(SmokeDetector::query()->where(['smde_imei' => $deviceItem['imei']])->exists()){
                    continue;
                }

                $deviceInsert[] = [
                    'smde_type' => '烟感',
                    'smde_imei' => $deviceItem['imei'],
                    'smde_status' => '已入库',
                    'smde_node_ids' => ',3,4,61,115,',
                    'smde_thpl_id' => 7,
                    'smde_place_id' => $placeItem['plac_id'],
                    'smde_thpl_raw' => ToolsLogic::jsonEncode($deviceItem),
                    'smde_thpl_plac_pk' => $placeItem['plac_thpl_pk']
                ];

                if(count($deviceInsert) > 100){
                    SmokeDetector::query()->insert($deviceInsert);
                    $deviceInsert = [];
                }
            }
        }

        if(!empty($deviceInsert)){
            Place::query()->insert($deviceInsert);
            $deviceInsert = [];
        }

        #同步联系人信息
        $receiverInsert = [];
        foreach ($placeList as $key => $placeItem){
            if(AlertReceiver::query()->where(['alre_place_id' => $placeItem['plac_id']])->exists()){
                continue;
            }
            $linkmanList = $this->getLinkman($placeItem['plac_thpl_pk']);

            foreach ($linkmanList as $k =>$linkmanItem){
                $receiverInsert[] = [
                    'alre_place_id' => $placeItem['plac_id'],
                    'alre_name' => $linkmanItem['name'],
                    'alre_mobile' => $linkmanItem['phone'],
                    'alre_remark' => '北大青鸟华洲街道接警人',
                ];

                if(count($receiverInsert) >= 100){
                    AlertReceiver::query()->insert($receiverInsert);
                    $receiverInsert = [];
                }
            }
        }

        if(!empty($receiverInsert)){
            AlertReceiver::query()->insert($receiverInsert);
            $receiverInsert = [];
        }
    }

    public function getUnitList()
    {
        $page = 1;
        $pageSize = 100;
        #获取单位信息
        $unitList = [];
        while (1) {
            $list = $this->apiUnit->getUnitList($page,$pageSize);
            if(empty($list)){
                break;
            }

            $unitList = array_merge($unitList,$list);

            if($pageSize > count($list)){
                break;
            }
            $page++;
        }

        return $unitList;
    }

    public function getDeviceList($unitId)
    {
        $page = 1;
        $pageSize = 100;
        #获取单位信息
        $deviceList = [];
        while (1) {
            $list = $this->apiUnit->getDeviceList($unitId,$page,$pageSize);
            if(empty($list)){
                break;
            }

            $deviceList = array_merge($deviceList,$list);

            if($pageSize > count($list)){
                break;
            }
            $page++;
        }

        return $deviceList;
    }

    public function getLinkman($unitId)
    {
        $page = 1;
        $pageSize = 100;
        #获取单位信息
        $linkList = [];
        while (1) {
            $list = $this->apiUnit->getLinkman($unitId,$page,$pageSize);

            if(empty($list)){
                break;
            }

            $linkList = array_merge($linkList,$list);

            if($pageSize > count($list)){
                break;
            }
            $page++;
        }

        return $linkList;
    }
}
