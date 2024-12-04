<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UitdSeeder extends Seeder
{
    public array $Uitds = [
        ['1',	'1',	'000001',	'点型感烟',	'01-灵敏度0.10~0.21dB/m',	'101房间'],
        ['1',	'2',	'000002',	'点型感温',	'01-灵敏度A1S',	'102房间'],
        ['1',	'3',	'000003',	'手动按钮',	'00-未定义',	'103房间'],
        ['1',	'4',	'000004',	'声光警报',	'01-电平输出',	'104房间'],
        ['1',	'5',	'000005',	'消火栓',	'01-电平输出',	'105房间'],
        ['1',	'6',	'000006',	'点型感烟',	'01-灵敏度0.10~0.21dB/m',	'106房间'],
        ['1',	'7',	'000007',	'点型感烟',	'01-灵敏度0.10~0.21dB/m',	'107房间'],
        ['1',	'8',	'000008',	'点型感烟',	'01-灵敏度0.10~0.21dB/m',	'108房间'],
        ['1',	'9',	'000009',	'点型感烟',	'01-灵敏度0.10~0.21dB/m',	'109房间'],
        ['1',	'10',	'000010',	'点型感烟',	'01-灵敏度0.10~0.21dB/m',	'110房间'],
        ['1',	'11',	'000011',	'点型感烟',	'01-灵敏度0.10~0.21dB/m',	'111房间'],
        ['1',	'12',	'000012',	'点型感烟',	'01-灵敏度0.10~0.21dB/m',	'112房间'],
        ['1',	'13',	'000013',	'点型感烟',	'01-灵敏度0.10~0.21dB/m',	'113房间'],
        ['1',	'14',	'000014',	'点型感烟',	'01-灵敏度0.10~0.21dB/m',	'114房间'],
        ['1',	'15',	'000015',	'点型感烟',	'01-灵敏度0.10~0.21dB/m',	'115房间'],
        ['1',	'16',	'000016',	'点型感烟',	'01-灵敏度0.10~0.21dB/m',	'116房间'],
        ['1',	'17',	'000017',	'点型感烟',	'01-灵敏度0.10~0.21dB/m',	'117房间'],
        ['1',	'18',	'000018',	'点型感烟',	'01-灵敏度0.10~0.21dB/m',	'118房间'],
        ['1',	'19',	'000019',	'点型感烟',	'01-灵敏度0.10~0.21dB/m',	'119房间'],
        ['1',	'20',	'000020',	'点型感烟',	'01-灵敏度0.10~0.21dB/m',	'120房间'],
        ['1',	'21',	'000021',	'点型感烟',	'01-灵敏度0.10~0.21dB/m',	'121房间'],
        ['1',	'22',	'000022',	'点型感烟',	'01-灵敏度0.10~0.21dB/m',	'122房间'],
        ['1',	'23',	'000023',	'点型感烟',	'01-灵敏度0.10~0.21dB/m',	'123房间'],
        ['1',	'24',	'000024',	'点型感烟',	'01-灵敏度0.10~0.21dB/m',	'124房间'],
        ['1',	'25',	'000025',	'点型感烟',	'01-灵敏度0.10~0.21dB/m',	'125房间'],
        ['1',	'26',	'000026',	'点型感烟',	'01-灵敏度0.10~0.21dB/m',	'126房间'],
        ['1',	'27',	'000027',	'点型感烟',	'01-灵敏度0.10~0.21dB/m',	'127房间'],
        ['1',	'28',	'000028',	'点型感烟',	'01-灵敏度0.10~0.21dB/m',	'128房间'],
        ['1',	'29',	'000029',	'点型感烟',	'01-灵敏度0.10~0.21dB/m',	'129房间'],
        ['1',	'30',	'000030',	'点型感烟',	'01-灵敏度0.10~0.21dB/m',	'130房间'],
        ['1',	'31',	'000031',	'点型感烟',	'01-灵敏度0.10~0.21dB/m',	'131房间'],
        ['1',	'32',	'000032',	'点型感烟',	'01-灵敏度0.10~0.21dB/m',	'132房间'],
        ['1',	'33',	'000033',	'点型感烟',	'01-灵敏度0.10~0.21dB/m',	'133房间'],
        ['1',	'34',	'000034',	'点型感烟',	'01-灵敏度0.10~0.21dB/m',	'134房间'],
        ['1',	'35',	'000035',	'点型感烟',	'01-灵敏度0.10~0.21dB/m',	'135房间'],
        ['1',	'36',	'000036',	'点型感烟',	'01-灵敏度0.10~0.21dB/m',	'136房间'],
        ['1',	'37',	'000037',	'点型感温',	'01-灵敏度A1S',	'137房间'],
        ['1',	'38',	'000038',	'点型感温',	'01-灵敏度A1S',	'138房间'],
        ['1',	'39',	'000039',	'点型感温',	'01-灵敏度A1S',	'139房间'],
        ['1',	'40',	'000040',	'点型感温',	'01-灵敏度A1S',	'140房间'],
        ['1',	'41',	'000041',	'点型感温',	'01-灵敏度A1S',	'141房间'],
        ['1',	'42',	'000042',	'点型感温',	'01-灵敏度A1S',	'142房间'],
        ['1',	'43',	'000043',	'点型感温',	'01-灵敏度A1S',	'143房间'],
        ['1',	'44',	'000044',	'点型感温',	'01-灵敏度A1S',	'144房间'],
        ['1',	'45',	'000045',	'点型感温',	'01-灵敏度A1S',	'145房间'],
        ['1',	'46',	'000046',	'点型感温',	'01-灵敏度A1S',	'146房间'],
        ['1',	'47',	'000047',	'点型感温',	'01-灵敏度A1S',	'147房间'],
        ['1',	'48',	'000048',	'点型感温',	'01-灵敏度A1S',	'148房间'],
        ['1',	'49',	'000049',	'点型感温',	'01-灵敏度A1S',	'149房间'],
        ['1',	'50',	'000050',	'排烟机',	'01-电平输出',	'风机房'],
        ['1',	'51',	'000051',	'送风机',	'01-电平输出',	'风机房'],
        ['1',	'52',	'000052',	'新风机',	'01-电平输出',	'风机房'],
        ['1',	'53',	'000053',	'消火栓泵',	'01-电平输出',	'水泵房'],
        ['1',	'54',	'000054',	'喷淋泵',	'01-电平输出',	'水泵房'],
        ['1',	'55',	'000055',	'稳压泵',	'01-电平输出',	'水泵房'],
        ['1',	'56',	'000056',	'手动按钮',	'00-未定义'],
        ['1',	'57',	'000057',	'手动按钮',	'00-未定义'],
        ['1',	'58',	'000058',	'手动按钮',	'00-未定义'],
        ['1',	'59',	'000059',	'手动按钮',	'00-未定义'],
        ['1',	'60',	'000060',	'手动按钮',	'00-未定义'],
        ['1',	'61',	'000061',	'手动按钮',	'00-未定义'],
        ['1',	'62',	'000062',	'手动按钮',	'00-未定义'],
        ['1',	'63',	'000063',	'防火阀',	'01-电平输出'],
        ['1',	'64',	'000064',	'手动按钮',	'00-未定义'],
        ['1',	'65',	'000065',	'防火阀',	'01-电平输出'],
        ['1',	'66',	'000066',	'防火阀',	'01-电平输出'],
        ['1',	'67',	'000067',	'防火阀',	'01-电平输出'],
        ['1',	'68',	'000068',	'防火阀',	'01-电平输出'],
        ['1',	'69',	'000069',	'防火阀',	'01-电平输出'],
        ['1',	'70',	'000070',	'防火阀',	'01-电平输出'],
        ['1',	'71',	'000071',	'防火阀',	'01-电平输出'],
        ['1',	'72',	'000072',	'防火阀',	'01-电平输出'],
        ['1',	'73',	'000073',	'防火阀',	'01-电平输出'],
        ['1',	'74',	'000074',	'防火阀',	'01-电平输出'],
        ['1',	'75',	'000075',	'防火阀',	'01-电平输出'],
        ['1',	'76',	'000076',	'防火阀',	'01-电平输出'],
        ['1',	'77',	'000077',	'防火阀',	'01-电平输出'],
        ['1',	'78',	'000078',	'防火阀',	'01-电平输出'],
        ['1',	'79',	'000079',	'防火阀',	'01-电平输出'],
        ['1',	'80',	'000080',	'防火阀',	'01-电平输出'],
        ['1',	'81',	'000081',	'防火阀',	'01-电平输出'],
        ['1',	'82',	'000082',	'防火阀',	'01-电平输出'],
        ['1',	'83',	'000083',	'防火阀',	'01-电平输出'],
        ['1',	'84',	'000084',	'防火阀',	'01-电平输出'],
        ['1',	'85',	'000085',	'防火阀',	'01-电平输出'],
        ['1',	'86',	'000086',	'防火阀',	'01-电平输出'],
        ['1',	'87',	'000087',	'防火阀',	'01-电平输出'],
        ['1',	'88',	'000088',	'防火阀',	'01-电平输出'],
        ['1',	'89',	'000089',	'防火阀',	'01-电平输出'],
        ['1',	'90',	'000090',	'防火阀',	'01-电平输出'],
        ['1',	'91',	'000091',	'防火阀',	'01-电平输出'],
        ['1',	'92',	'000092',	'防火阀',	'01-电平输出'],
        ['1',	'93',	'000093',	'防火阀',	'01-电平输出'],
        ['1',	'94',	'000094',	'防火阀',	'01-电平输出'],
        ['1',	'95',	'000095',	'防火阀',	'01-电平输出'],
        ['1',	'96',	'000096',	'防火阀',	'01-电平输出'],
        ['1',	'97',	'000097',	'防火阀',	'01-电平输出'],
        ['1',	'98',	'000098',	'防火阀',	'01-电平输出'],
        ['1',	'99',	'000099',	'防火阀',	'01-电平输出'],
        ['1',	'100',	'000100',	'防火阀',	'01-电平输出'],
        ['1',	'101',	'000101',	'防火阀',	'01-电平输出'],
        ['1',	'102',	'000102',	'防火阀',	'01-电平输出'],
        ['1',	'103',	'000103',	'防火阀',	'01-电平输出'],
        ['1',	'104',	'000104',	'防火阀',	'01-电平输出'],
        ['1',	'105',	'000105',	'防火阀',	'01-电平输出'],
        ['1',	'106',	'000106',	'防火阀',	'01-电平输出'],
        ['1',	'107',	'000107',	'防火阀',	'01-电平输出'],
        ['1',	'108',	'000108',	'防火阀',	'01-电平输出'],
        ['1',	'109',	'000109',	'防火阀',	'01-电平输出'],
        ['1',	'110',	'000110',	'防火阀',	'01-电平输出'],
        ['1',	'111',	'000111',	'防火阀',	'01-电平输出'],
        ['1',	'112',	'000112',	'防火阀',	'01-电平输出'],
        ['1',	'113',	'000113',	'防火阀',	'01-电平输出'],
        ['1',	'114',	'000114',	'防火阀',	'01-电平输出'],
        ['1',	'115',	'000115',	'防火阀',	'01-电平输出'],
        ['1',	'116',	'000116',	'防火阀',	'01-电平输出'],
        ['1',	'117',	'000117',	'防火阀',	'01-电平输出'],
        ['1',	'118',	'000118',	'防火阀',	'01-电平输出'],
        ['1',	'119',	'000119',	'防火阀',	'01-电平输出'],
        ['1',	'120',	'000120',	'防火阀',	'01-电平输出'],
        ['1',	'121',	'000121',	'防火阀',	'01-电平输出'],
        ['1',	'122',	'000122',	'防火阀',	'01-电平输出'],
        ['1',	'123',	'000123',	'防火阀',	'01-电平输出'],
        ['1',	'124',	'000124',	'防火阀',	'01-电平输出'],
        ['1',	'125',	'000125',	'防火阀',	'01-电平输出'],
        ['1',	'126',	'000126',	'防火阀',	'01-电平输出'],
        ['1',	'127',	'000127',	'防火阀',	'01-电平输出'],
        ['1',	'128',	'000128',	'防火阀',	'01-电平输出'],
        ['1',	'129',	'000129',	'防火阀',	'01-电平输出'],
        ['1',	'130',	'000130',	'防火阀',	'01-电平输出'],
        ['1',	'131',	'000131',	'防火阀',	'01-电平输出'],
        ['1',	'132',	'000132',	'防火阀',	'01-电平输出'],
        ['1',	'133',	'000133',	'防火阀',	'01-电平输出'],
        ['1',	'134',	'000134',	'防火阀',	'01-电平输出'],
        ['1',	'135',	'000135',	'防火阀',	'01-电平输出'],
        ['1',	'136',	'000136',	'防火阀',	'01-电平输出'],
        ['1',	'137',	'000137',	'防火阀',	'01-电平输出'],
        ['1',	'138',	'000138',	'防火阀',	'01-电平输出'],
        ['1',	'139',	'000139',	'防火阀',	'01-电平输出'],
        ['1',	'140',	'000140',	'防火阀',	'01-电平输出'],
        ['1',	'141',	'000141',	'防火阀',	'01-电平输出'],
        ['1',	'142',	'000142',	'防火阀',	'01-电平输出'],
        ['1',	'143',	'000143',	'防火阀',	'01-电平输出'],
        ['1',	'144',	'000144',	'防火阀',	'01-电平输出'],
        ['1',	'145',	'000145',	'防火阀',	'01-电平输出'],
        ['1',	'146',	'000146',	'防火阀',	'01-电平输出'],
        ['1',	'147',	'000147',	'防火阀',	'01-电平输出'],
        ['1',	'148',	'000148',	'防火阀',	'01-电平输出'],
        ['1',	'149',	'000149',	'防火阀',	'01-电平输出'],
        ['1',	'150',	'000150',	'防火阀',	'01-电平输出'],
        ['1',	'151',	'000151',	'防火阀',	'01-电平输出'],
        ['1',	'152',	'000152',	'防火阀',	'01-电平输出'],
        ['1',	'153',	'000153',	'防火阀',	'01-电平输出'],
        ['1',	'154',	'000154',	'防火阀',	'01-电平输出'],
        ['1',	'155',	'000155',	'防火阀',	'01-电平输出'],
        ['1',	'156',	'000156',	'防火阀',	'01-电平输出'],
        ['1',	'157',	'000157',	'防火阀',	'01-电平输出'],
        ['1',	'158',	'000158',	'防火阀',	'01-电平输出'],
        ['1',	'159',	'000159',	'防火阀',	'01-电平输出'],
        ['1',	'160',	'000160',	'防火阀',	'01-电平输出'],
        ['1',	'161',	'000161',	'防火阀',	'01-电平输出'],
        ['1',	'162',	'000162',	'防火阀',	'01-电平输出'],
        ['1',	'163',	'000163',	'防火阀',	'01-电平输出'],
        ['1',	'164',	'000164',	'防火阀',	'01-电平输出'],
        ['1',	'165',	'000165',	'防火阀',	'01-电平输出'],
        ['1',	'166',	'000166',	'防火阀',	'01-电平输出'],
        ['1',	'167',	'000167',	'防火阀',	'01-电平输出'],
        ['1',	'168',	'000168',	'防火阀',	'01-电平输出'],
        ['1',	'169',	'000169',	'防火阀',	'01-电平输出'],
        ['1',	'170',	'000170',	'防火阀',	'01-电平输出'],
        ['1',	'171',	'000171',	'防火阀',	'01-电平输出'],
        ['1',	'172',	'000172',	'防火阀',	'01-电平输出'],
        ['1',	'173',	'000173',	'防火阀',	'01-电平输出'],
        ['1',	'174',	'000174',	'防火阀',	'01-电平输出'],
        ['1',	'175',	'000175',	'防火阀',	'01-电平输出'],
        ['1',	'176',	'000176',	'防火阀',	'01-电平输出'],
        ['1',	'177',	'000177',	'防火阀',	'01-电平输出'],
        ['1',	'178',	'000178',	'防火阀',	'01-电平输出'],
        ['1',	'179',	'000179',	'防火阀',	'01-电平输出'],
        ['1',	'180',	'000180',	'防火阀',	'01-电平输出'],
        ['1',	'181',	'000181',	'防火阀',	'01-电平输出'],
        ['1',	'182',	'000182',	'防火阀',	'01-电平输出'],
        ['1',	'183',	'000183',	'防火阀',	'01-电平输出'],
        ['1',	'184',	'000184',	'防火阀',	'01-电平输出'],
        ['1',	'185',	'000185',	'防火阀',	'01-电平输出'],
        ['1',	'186',	'000186',	'防火阀',	'01-电平输出'],
        ['1',	'187',	'000187',	'防火阀',	'01-电平输出'],
        ['1',	'188',	'000188',	'防火阀',	'01-电平输出'],
        ['1',	'189',	'000189',	'防火阀',	'01-电平输出'],
        ['1',	'190',	'000190',	'防火阀',	'01-电平输出'],
        ['1',	'191',	'000191',	'防火阀',	'01-电平输出'],
        ['1',	'192',	'000192',	'防火阀',	'01-电平输出'],
        ['1',	'193',	'000193',	'防火阀',	'01-电平输出'],
        ['1',	'194',	'000194',	'防火阀',	'01-电平输出'],
        ['1',	'195',	'000195',	'防火阀',	'01-电平输出'],
        ['1',	'196',	'000196',	'防火阀',	'01-电平输出'],
        ['1',	'197',	'000197',	'防火阀',	'01-电平输出'],
        ['1',	'198',	'000198',	'防火阀',	'01-电平输出'],
        ['1',	'199',	'000199',	'防火阀',	'01-电平输出'],
        ['1',	'200',	'000200',	'防火阀',	'01-电平输出'],
        ['1',	'201',	'000201',	'防火阀',	'01-电平输出'],
        ['1',	'202',	'000202',	'防火阀',	'01-电平输出'],
        ['1',	'203',	'000203',	'防火阀',	'01-电平输出'],
        ['1',	'204',	'000204',	'防火阀',	'01-电平输出'],
        ['1',	'205',	'000205',	'防火阀',	'01-电平输出'],
        ['1',	'206',	'000206',	'防火阀',	'01-电平输出'],
        ['1',	'207',	'000207',	'防火阀',	'01-电平输出'],
        ['1',	'208',	'000208',	'防火阀',	'01-电平输出'],
        ['1',	'209',	'000209',	'防火阀',	'01-电平输出'],
        ['1',	'210',	'000210',	'防火阀',	'01-电平输出'],
        ['1',	'211',	'000211',	'防火阀',	'01-电平输出'],
        ['1',	'212',	'000212',	'防火阀',	'01-电平输出'],
        ['1',	'213',	'000213',	'防火阀',	'01-电平输出'],
        ['1',	'214',	'000214',	'防火阀',	'01-电平输出'],
        ['1',	'215',	'000215',	'防火阀',	'01-电平输出'],
        ['1',	'216',	'000216',	'防火阀',	'01-电平输出'],
        ['1',	'217',	'000217',	'防火阀',	'01-电平输出'],
        ['1',	'218',	'000218',	'防火阀',	'01-电平输出'],
        ['1',	'219',	'000219',	'防火阀',	'01-电平输出'],
        ['1',	'220',	'000220',	'防火阀',	'01-电平输出'],
        ['1',	'221',	'000221',	'防火阀',	'01-电平输出'],
        ['1',	'222',	'000222',	'防火阀',	'01-电平输出'],
        ['1',	'223',	'000223',	'防火阀',	'01-电平输出'],
        ['1',	'224',	'000224',	'防火阀',	'01-电平输出'],
        ['1',	'225',	'000225',	'防火阀',	'01-电平输出'],
        ['1',	'226',	'000226',	'防火阀',	'01-电平输出'],
        ['1',	'227',	'000227',	'防火阀',	'01-电平输出'],
        ['1',	'228',	'000228',	'防火阀',	'01-电平输出'],
        ['1',	'229',	'000229',	'防火阀',	'01-电平输出'],
        ['1',	'230',	'000230',	'防火阀',	'01-电平输出'],
        ['1',	'231',	'000231',	'防火阀',	'01-电平输出'],
        ['1',	'232',	'000232',	'防火阀',	'01-电平输出'],
        ['1',	'233',	'000233',	'防火阀',	'01-电平输出'],
        ['1',	'234',	'000234',	'防火阀',	'01-电平输出'],
        ['1',	'235',	'000235',	'防火阀',	'01-电平输出'],
        ['1',	'236',	'000236',	'防火阀',	'01-电平输出'],
        ['1',	'237',	'000237',	'防火阀',	'01-电平输出'],
        ['1',	'238',	'000238',	'防火阀',	'01-电平输出'],
        ['1',	'239',	'000239',	'防火阀',	'01-电平输出'],
        ['1',	'240',	'000240',	'防火阀',	'01-电平输出'],
        ['1',	'241',	'000241',	'防火阀',	'01-电平输出'],
        ['1',	'242',	'000242',	'防火阀',	'01-电平'],

        [1,	1,	'100001',	'排烟机',	'01-电平启',	'风机房'],
        [1,	1,	'100002',	'送风机',	'01-电平启',	'风机房'],
        [1,	1,	'100003',	'新风机',	'01-电平启',	'风机房'],
        [1,	1,	'100004',	'消火栓泵',   '01-电平启',	'水泵房'],
        [1,	1,	'100005',	'喷淋泵',	'01-电平启',	'水泵房'],
        [1,	1,	'100006',	'稳压泵',	'01-电平启',	'水泵房'],
        [1,	1,	'000007',	'喷淋泵',	'01-电平启'],
        [1,	1,	'000008',	'喷淋泵',	'01-电平启'],
        [1,	1,	'000009',	'喷淋泵',	'01-电平启'],
        [1,	1,  '000010',	'喷淋泵',	'01-电平启'],
        [1,	1,  '000011',	'喷淋泵',	'01-电平启'],
        [1,	1,  '000012',	'喷淋泵',	'01-电平启'],
        [1,	1,  '000013',	'喷淋泵',	'01-电平启'],
        [1,	1,  '000014',	'喷淋泵',	'01-电平启'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mainframeId = 61;
        // smde_circuit => 回路
        // smde_point_position => 点位
        // smde_imei=> 用户编码
        // smde_type 设备类型
        // smde_building_no 楼号
        // smde_extra_remark => 设备注释
        // smde_mafr_id 所属主机

        foreach ($this->Uitds as $uitd) {
            $data[] = [
                'smde_circuit'        => $uitd[0],
                "smde_point_position" => $uitd[1],
                'smde_imei'           => $uitd[2],
                'smde_type'           => $uitd[3],
                'smde_building_no'    => $uitd[5] ?? '',
                'smde_extra_remark'   => $uitd[4],
                'smde_mafr_id'        => $mainframeId,
            ];
        }
        DB::connection('mysql2')->table('smoke_detector')->insert($data);
    }
}
