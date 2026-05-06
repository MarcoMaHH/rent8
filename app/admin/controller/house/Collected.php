<?php

namespace app\admin\controller\house;

use app\admin\controller\Common;
use app\admin\library\Property;
use app\admin\model\HouseReceipt as ReceiptModel;
use app\admin\model\HouseFee as FeeModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use think\facade\View;

class Collected extends Common
{
    public function index()
    {
        return View::fetch('/house/collected/index');
    }

    public function queryCollected()
    {
        $page = $this->request->param('page/d', LAYUI_PAGE);
        $limit = $this->request->param('limit/d', LAYUI_LIMIT);
        $house_property_id = Property::getProperty();
        $conditions = array(
            ['a.house_property_id', 'in', $house_property_id],
            ['a.accounting_date', 'not null', '']
        );
        $parameter = $this->request->param('parameter/s', '', 'trim');
        if ($parameter) {
            $conditions[] = function ($query) use ($parameter) {
                $query->where('b.name', 'like', "%{$parameter}%")
                    ->whereOr('c.name', 'like', "%{$parameter}%");
            };
        }
        $count = ReceiptModel::alias('a')
            ->join('HouseNumber b', 'a.house_property_id = b.house_property_id and a.house_number_id = b.id and  a.tenant_message_id = b.tenant_message_id')
            ->join('HouseProperty c', 'c.id = a.house_property_id')
            ->where($conditions)
            ->count();
        $billing = ReceiptModel::where($conditions)
            ->alias('a')
            ->join('HouseNumber b', 'a.house_property_id = b.house_property_id and a.house_number_id = b.id and  a.tenant_message_id = b.tenant_message_id')
            ->join('HouseProperty c', 'c.id = a.house_property_id')
            ->field('a.*,b.name as number_name, c.name as property_name, DATE_FORMAT(a.accounting_date, "%Y-%m-%d") as accounting_date')
            ->order(['a.accounting_date' => 'desc', 'number_name'])
            ->page($page, $limit)
            ->select();
        foreach ($billing as $value) {
            if ($value['end_time']) {
                $value['lease'] = \substr($value['start_time'], 0, 10)  . '至' . \substr($value['end_time'], 0, 10);
            } elseif ($value['meter_reading_time']) {
                $value['lease'] = \substr($value['start_time'], 0, 10) . ' 退房';
            } else {
                $value['lease'] = \substr($value['start_time'], 0, 10);
            }
        }
        return $this->returnResult($billing, $count);
    }

    public function getColumns()
    {
        $fees = FeeModel::where('admin_user_id', $this->auth->getLoginUser()['id'])
            ->select()->toArray();

        $feeColumns = [];
        foreach ($fees as $item) {
            if (!empty($item['column_name'])) {
                $feeColumns[] = [
                    'title' => $item['column_name'],
                    'colKey' => $item['column_id'],
                    'width' => 50
                ];
            }
        }

        $columns = [
            ['title' => '账单类型', 'colKey' => 'bill_type', 'width' => 70],
            ['title' => '房产名', 'colKey' => 'property_name', 'width' => 70],
            ['title' => '房间名', 'colKey' => 'number_name', 'width' => 80, 'sorter' => true],
            ['title' => '租期', 'colKey' => 'lease', 'width' => 95, 'ellipsis' => true],
            ['title' => '电费', 'colKey' => 'electricity', 'width' => 60],
            ['title' => '水费', 'colKey' => 'water', 'width' => 50],
            ['title' => '租金', 'colKey' => 'rental', 'width' => 50],
            ['title' => '押金', 'colKey' => 'deposit', 'width' => 50],
            ...$feeColumns,
            ['title' => '总金额', 'colKey' => 'total_money', 'width' => 50],
            ['title' => '备注', 'colKey' => 'note', 'width' => 90, 'ellipsis' => true],
            ['title' => '到账日期', 'colKey' => 'accounting_date', 'width' => 80],
        ];

        $colums2 = [
            ['title' => '账单类型', 'colKey' => 'bill_type', 'width' => 75],
            ['title' => '房产名', 'colKey' => 'property_name', 'width' => 70],
            ['title' => '房间名', 'colKey' => 'number_name', 'width' => 80, 'sorter' => true],
            ['title' => '租期', 'colKey' => 'lease', 'width' => 87, 'ellipsis' => true],
            ['title' => '电表度数', 'colKey' => 'electricity_meter_this_month', 'width' => 50],
            ['title' => '用电量', 'colKey' => 'electricity_consumption', 'width' => 50],
            ['title' => '电费', 'colKey' => 'electricity', 'width' => 50],
            ['title' => '水表度数', 'colKey' => 'water_meter_this_month', 'width' => 50],
            ['title' => '用水量', 'colKey' => 'water_consumption', 'width' => 50],
            ['title' => '水费', 'colKey' => 'water', 'width' => 50],
            ['title' => '租金', 'colKey' => 'rental', 'width' => 50],
            ['title' => '押金', 'colKey' => 'deposit', 'width' => 50],
            ...$feeColumns,
            ['title' => '总金额', 'colKey' => 'total_money', 'width' => 50],
            ['title' => '备注', 'colKey' => 'note', 'width' => 80, 'ellipsis' => true],
            ['title' => '到账日期', 'colKey' => 'accounting_date', 'width' => 80],
        ];

        return $this->returnResult([$columns, $colums2]);
    }

    public function sum()
    {
        $house_property_id = Property::getProperty();
        $sum = ReceiptModel::where('house_property_id', 'in', $house_property_id)
            ->whereTime('accounting_date', 'today')
            ->sum('total_money');
        return $this->returnResult([], 1, round($sum, 2));
    }

    public function export()
    {
        // 查询数据
        $house_property_id = Property::getProperty();
        $conditions = array(
            ['a.house_property_id', 'in', $house_property_id],
            ['a.accounting_date', 'not null', '']
        );
        // 获取月份参数
        $monthParam = $this->request->post('export_time/s', '', 'trim');
        $startDate = date('Y-m-01', strtotime($monthParam));
        $endDate = date('Y-m-t', strtotime($monthParam));
        $type = $this->request->post('type/s', '', 'trim');
        if ($type == 'A') {
            // 生成日期范围
            $conditions[] = ['a.accounting_date', 'between', [$startDate, $endDate]];
        } else if ($type == 'S') {
            $conditions[] = ['a.start_time', 'between', [$startDate, $endDate]];
        } else {
            return $this->returnResult([], 0, '请选择导出类型');
        }
        $receipt = ReceiptModel::where($conditions)
            ->alias('a')
            ->join('HouseNumber b', 'a.house_property_id = b.house_property_id and a.house_number_id = b.id')
            ->join('HouseProperty c', 'c.id = a.house_property_id')
            ->field('a.*,b.name as number_name, c.name as property_name')
            ->order(['a.accounting_date' => 'desc', 'number_name'])
            ->select();
        $fees = FeeModel::where('admin_user_id', $this->auth->getLoginUser()['id'])
            ->column('column_name', 'column_id');

        $feesArr = [];
        for ($i = 1; $i <= 4; $i++) {
            $key = 'fees' . $i;
            if (isset($fees[$key])) {
                $feesArr[$key] = $fees[$key];
            }
        }
        foreach ($receipt as $value) {
            $value['accounting_date'] = \substr($value['accounting_date'], 0, 10);
            if ($value['end_time']) {
                $value['lease'] = \substr($value['start_time'], 0, 10)  . '至' . \substr($value['end_time'], 0, 10);
            } elseif ($value['meter_reading_time']) {
                $value['lease'] = \substr($value['start_time'], 0, 10) . ' 退房';
            } else {
                $value['lease'] = \substr($value['start_time'], 0, 10);
            }
        }

        // 创建一个新的Spreadsheet对象
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // 填充数据
        $headers = [
            'A' => ['name' => '房产名', 'width' => 15],
            'B' => ['name' => '房间名', 'width' => 13],
            'C' => ['name' => '租期', 'width' => 25],
            'D' => ['name' => '电表度数'],
            'E' => ['name' => '用电量'],
            'F' => ['name' => '电费'],
            'G' => ['name' => '水表度数'],
            'H' => ['name' => '用水量'],
            'I' => ['name' => '水费'],
            'J' => ['name' => '租金'],
            'K' => ['name' => '押金']
        ];

        foreach ($headers as $col => $config) {
            $sheet->setCellValue($col . '1', $config['name']);
            if (isset($config['width'])) {
                $sheet->getColumnDimension($col)->setWidth($config['width']);
            }
        }

        $columns = ['L', 'M', 'N', 'O', 'P', 'Q', 'R'];
        $currentCol = 0;

        foreach (['fees1', 'fees2', 'fees3', 'fees4'] as $feeKey) {
            if (!empty($feesArr[$feeKey])) {
                $sheet->setCellValue($columns[$currentCol] . '1', $feesArr[$feeKey]);
                $currentCol++;
            }
        }

        $footer = [
            ['name' => '总金额'],
            ['name' => '到账日期', 'width' => 13],
            ['name' => '备注']
        ];

        foreach ($footer as $item) {
            $sheet->setCellValue($columns[$currentCol] . '1', $item['name']);
            if (isset($item['width'])) {
                $sheet->getColumnDimension($columns[$currentCol])->setWidth($item['width']);
            }
            $currentCol++;
        }
        $row = 2;
        foreach ($receipt as $value) {
            $sheet->setCellValue('A' . $row, $value['property_name']);
            $sheet->setCellValue('B' . $row, $value['number_name']);
            $sheet->setCellValue('C' . $row, $value['lease']);
            $sheet->setCellValue('D' . $row, $value['electricity_meter_this_month']);
            $sheet->setCellValue('E' . $row, $value['electricity_consumption']);
            $sheet->setCellValue('F' . $row, $value['electricity']);
            $sheet->setCellValue('G' . $row, $value['water_meter_this_month']);
            $sheet->setCellValue('H' . $row, $value['water_consumption']);
            $sheet->setCellValue('I' . $row, $value['water']);
            $sheet->setCellValue('J' . $row, $value['rental']);
            $sheet->setCellValue('K' . $row, $value['deposit']);

            // todo 费用字段处理
            $columns = ['L', 'M', 'N', 'O', 'P', 'Q', 'R'];
            $currentCol = 0;
            foreach (['fees1', 'fees2', 'fees3', 'fees4'] as $feeKey) {
                if (!empty($feesArr[$feeKey])) {
                    $sheet->setCellValue($columns[$currentCol] . $row, $value[$feeKey]);
                    $currentCol++;
                }
            }

            $footer = [
                ['name' => 'total_money'],
                ['name' => 'accounting_date'],
                ['name' => 'note']
            ];
            foreach ($footer as $item) {
                $sheet->setCellValue($columns[$currentCol] . $row, $value[$item['name']]);
                $currentCol++;
            }
            $row++;
        }

        // 设置导出文件名
        $fileName = 'export.xlsx';

        // 创建Xlsx文件写入器
        $writer = new Xlsx($spreadsheet);

        // 设置HTTP头信息
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        // 发送文件到浏览器下载
        $writer->save('php://output');

        // 结束脚本
        exit;
    }
}
