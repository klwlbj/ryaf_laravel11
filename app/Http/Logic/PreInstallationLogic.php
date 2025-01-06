<?php

namespace App\Http\Logic;

use App\Models\Order;
use App\Models\PreInstallation;
use App\Http\Logic\Excel\ExcelGenerator;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class PreInstallationLogic extends ExcelGenerator
{
    public string $exportTitle = '如约安防-预安装报表导出excel';

    public bool $openLastRowTotal = false;

    public bool $lockFirstRow = true;

    /**
     * 获取可导出字段
     *
     * @return array
     */
    public function getExportColumns(): array
    {
        return [
            [
                "name"  => 'Id',
                "index" => 'id',
                "type"  => DataType::TYPE_STRING,
                "width" => 20,
            ],
            [
                "name"  => '姓名',
                "index" => 'name',
                "width" => 30,
            ],
            [
                "name"  => '电话',
                "index" => 'phone',
                "width" => 30,
            ],
            [
                "name"      => '地址',
                "index"     => 'address',
                "width"     => 60,
                "wrap_text" => true,
            ],
            [
                "name"      => '手写地址',
                "index"     => 'handwritten_address',
                "width"     => 60,
                "wrap_text" => true,
            ],
            [
                "name"  => '数量',
                "index" => 'installation_count',
                "width" => 20,
            ],
            [
                "name"  => '日期',
                "index" => 'registration_date',
                "width" => 20,
            ],
        ];
    }

    public function getList($params)
    {
        $page     = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 10;
        $offset   = ($page - 1) * $pageSize;

        $query = PreInstallation::query();

        $queryConditions = ['name', 'phone', 'address'];

        foreach ($queryConditions as $queryCondition) {
            switch ($queryCondition) {
                case 'phone':
                case 'name':
                    if (!empty($params[$queryCondition])) {
                        $query->where($queryCondition, 'like', '%' . $params[$queryCondition] . '%');
                    }
                    break;
                case 'address':
                    if (!empty($params[$queryCondition])) {
                        $query->where(function ($query) use ($params) {
                            $query->orWhere('address', 'like', '%' . $params['address'] . '%')->orWhere('handwritten_address', 'like', '%' . $params['address'] . '%');
                        });
                    }
                    break;
                default:break;
            }
        }

        if (!empty($params['start_date'])) {
            $query->where('registration_date', '>=', $params['start_date']);
        }

        if (!empty($params['end_date'])) {
            $query->where('registration_date', '<=', $params['end_date']);
        }

        $total = $query->count();

        $list = $query->when(!isset($params['export']), function ($query) use ($offset, $pageSize) {
            return $query->orderBy('created_at', 'desc')
                ->offset($offset)->limit($pageSize)
                ->get()
                ->map(function ($item) {
                    return $this->handleRow($item);
                });
        });

        if (isset($params['export'])) {
            $list = Order::getCursorSortById($list);
            return $this->export($list, $params, $total);
        }

        return [
            'total' => $total,
            'list'  => $list,
        ];
    }

    public function handleRow($item, $params = [])
    {
        return $item;
    }

    protected function handleLastRow($sheet, int $lastRow, array $lastRowTotal = [])
    {
    }

    public function delete($params)
    {
        $id    = $params['id'] ?? 0;
        $model = PreInstallation::find($id);
        $model->delete();
        return [];
    }
}
