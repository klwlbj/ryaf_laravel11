<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin;
use App\Models\Department;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportController
{
    public function importAdmin()
    {

        $fileName = "安防人员导入.xlsx";
        $spreadsheet = IOFactory::load($fileName);

        $departmentArr = Department::query()->pluck('depa_id','depa_name')->toArray();

        $sheetData = $spreadsheet->getSheet(0)->toArray(null, true, true, true);
        $error = [];
        $insertData = [];
        foreach ($sheetData as $key => $value) {
            if($key == 1){
                continue;
            }
            $value = array_values($value);

            $departmentId = $departmentArr[$value[1]] ?? '';
//            print_r($departmentId);die;
            if(empty($departmentId)){
                $error[] = $value;
                continue;
            }
//            print_r($value);die;
            $id = Admin::query()->where(['admin_name' => $value[0]])->value('admin_id');

            if(!empty($id)){
                Admin::query()->where(['admin_id' => $id])->update(['admin_department_id' => $departmentId]);
            }else{
                $insertData[] = [
                    'admin_part_id' => 0,
                    'admin_mobile' => $value[2],
                    'admin_pwd' => '123457',
                    'admin_name' => $value[0],
                    'admin_auths' => '',
                    'admin_enabled' => 1,
                    'admin_uni_client_id' => '',
                    'admin_department_id' => $departmentId,
                    'admin_is_principal' => 0
                ];
            }

        }

        if(!empty($insertData)){
            Admin::query()->insert($insertData);
        }


        print_r($error);die;

    }
}
