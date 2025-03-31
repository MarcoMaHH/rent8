<?php

namespace app\admin\controller\house;

use app\admin\controller\Common;
use app\admin\library\Property;
use app\admin\model\HouseBilling as BillingModel;
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
        $parameter = $this->request->param('parameter/s', '');
        if ($parameter) {
            $conditions[] = function ($query) use ($parameter) {
                $query->where('b.name', 'like', "%{$parameter}%")
                    ->whereOr('c.name', 'like', "%{$parameter}%")
                    ->whereOr('a.accounting_date', 'like', "%{$parameter}%");
            };
        }
        $count = BillingModel::alias('a')
            ->join('HouseNumber b', 'a.house_property_id = b.house_property_id and a.house_number_id = b.id')
            ->join('HouseProperty c', 'c.id = a.house_property_id')
            ->where($conditions)
            ->count();
        $billing = BillingModel::where($conditions)
            ->alias('a')
            ->join('HouseNumber b', 'a.house_property_id = b.house_property_id and a.house_number_id = b.id')
            ->join('HouseProperty c', 'c.id = a.house_property_id')
            ->field('a.*,b.name as number_name, c.name as property_name')
            ->order(['a.accounting_date' => 'desc', 'number_name'])
            ->page($page, $limit)
            ->select();
        foreach ($billing as $value) {
            $value['accounting_date'] = \substr($value['accounting_date'], 0, 10);
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

    public function sum()
    {
        $house_property_id = Property::getProperty();
        $sum = BillingModel::where('house_property_id', 'in', $house_property_id)
            ->whereTime('accounting_date', 'today')
            ->sum('total_money');
        return $this->returnResult([], 1, round($sum, 2));
    }

    public function export()
    {
        // 查询数据
        $page = $this->request->param('page/d', LAYUI_PAGE);
        $limit = $this->request->param('limit/d', LAYUI_LIMIT);
        $house_property_id = Property::getProperty();
        $conditions = array(
            ['a.house_property_id', 'in', $house_property_id],
            ['a.accounting_date', 'not null', '']
        );
        $parameter = $this->request->param('parameter/s', '');
        if ($parameter) {
            $conditions[] = function ($query) use ($parameter) {
                $query->where('b.name', 'like', "%{$parameter}%")
                    ->whereOr('c.name', 'like', "%{$parameter}%")
                    ->whereOr('a.accounting_date', 'like', "%{$parameter}%");
            };
        }
        $billing = BillingModel::where($conditions)
            ->alias('a')
            ->join('HouseNumber b', 'a.house_property_id = b.house_property_id and a.house_number_id = b.id')
            ->join('HouseProperty c', 'c.id = a.house_property_id')
            ->field('a.*,b.name as number_name, c.name as property_name')
            ->order(['a.accounting_date' => 'desc', 'number_name'])
            ->page($page, $limit)
            ->select();
        foreach ($billing as $value) {
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
        $sheet->setCellValue('A1', '房产名');
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->setCellValue('B1', '房间名');
        $sheet->getColumnDimension('B')->setWidth(13);
        $sheet->setCellValue('C1', '租期');
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->setCellValue('D1', '电表度数');
        $sheet->setCellValue('E1', '用电量');
        $sheet->setCellValue('F1', '电费');
        $sheet->setCellValue('G1', '水表度数');
        $sheet->setCellValue('H1', '用水量');
        $sheet->setCellValue('I1', '水费');
        $sheet->setCellValue('J1', '租金');
        $sheet->setCellValue('K1', '押金');
        $sheet->setCellValue('L1', '管理费');
        $sheet->setCellValue('M1', '网络费');
        $sheet->setCellValue('N1', '卫生费');
        $sheet->setCellValue('O1', '其他费用');
        $sheet->setCellValue('P1', '总金额');
        $sheet->setCellValue('Q1', '到账日期');
        $sheet->getColumnDimension('Q')->setWidth(13);
        $sheet->setCellValue('R1', '备注');
        $row = 2;
        foreach ($billing as $value) {
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
            $sheet->setCellValue('L' . $row, $value['management']);
            $sheet->setCellValue('M' . $row, $value['network']);
            $sheet->setCellValue('N' . $row, $value['garbage_fee']);
            $sheet->setCellValue('O' . $row, $value['other_charges']);
            $sheet->setCellValue('P' . $row, $value['total_money']);
            $sheet->setCellValue('Q' . $row, $value['accounting_date']);
            $sheet->setCellValue('R' . $row, $value['note']);
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
