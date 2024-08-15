<?php

namespace App\Http\Logic;

use App\Http\Library\StandardAddress;

class AddressLogic extends BaseLogic
{
    public function getStandardAddress($params)
    {
        $res = (new StandardAddress())->getStandardAddress($params['keyword']);

        $res = [
            'resp_code' => '00000',
            'resp_msg' => 'success',
            'hits' => [
                [
                    'MPDM' => '440111003021900000000000000113',
                    'XZQMC' => '白云区',
                    'JZMC' => '鹤龙街',
                    'SQMC' => '黄边社区',
                    'JLXMC' => '黄边北路',
                    'MPMC' => '182号',
                    'ADDRESS' => '白云区鹤龙街黄边社区黄边北路182号',
                    'MLP_DZBM' => '5e8a46ca-0009-a254-e053-0a29005ea254',
                    'MPDZMC' => '广州市白云区黄边北路182号'
                ],
                [
                    'MPDM' => '440111003021900000000000000114',
                    'XZQMC' => '白云区',
                    'JZMC' => '鹤龙街',
                    'SQMC' => '黄边社区',
                    'JLXMC' => '黄边北路',
                    'MPMC' => '168号',
                    'ADDRESS' => '白云区鹤龙街黄边社区黄边北路182号',
                    'MLP_DZBM' => '5e8a46ca-0009-a254-e053-0a29005ea254',
                    'MPDZMC' => '广州市白云区黄边北路168号'
                ],
                [
                    'MPDM' => '440111003021900000000000000115',
                    'XZQMC' => '白云区',
                    'JZMC' => '鹤龙街',
                    'SQMC' => '黄边社区',
                    'JLXMC' => '黄边北路',
                    'MPMC' => '168号',
                    'ADDRESS' => '白云区鹤龙街黄边社区黄边北路185号',
                    'MLP_DZBM' => '5e8a46ca-0009-a254-e053-0a29005ea254',
                    'MPDZMC' => '广州市白云区黄边北路168号'
                ],
            ]
        ];

        if($res['resp_msg'] != 'success'){
            ResponseLogic::setMsg($res['resp_msg']);
            return false;
        }

        return $res['hits'];
    }
}
