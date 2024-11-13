<?php

namespace app\admin\controller\house;

use app\admin\controller\Common;
use app\admin\model\HouseContract as ContractModel;
use app\common\house\Contract as ContractAction;
use app\admin\model\HouseNumber as NumberModel;
use app\admin\library\Property;
use app\admin\library\Date;
use think\facade\View;

class Contract extends Common
{
    public function index()
    {
        return View::fetch();
    }

    public function queryContract()
    {
        $house_property_id = Property::getProperty();
        $conditions = array(
            ['a.house_property_id', 'in', $house_property_id],
        );
        $parameter = $this->request->param('parameter/s', '');
        if ($parameter) {
            $conditions[] = function ($query) use ($parameter) {
                $query->where('b.name', 'like', "%{$parameter}%")
                    ->whereOr('c.name', 'like', "%{$parameter}%");
            };
        }
        $count = ContractModel::alias('a')
            ->join('HouseNumber b', 'a.house_property_id = b.house_property_id and a.house_number_id = b.id')
            ->join('HouseProperty c', 'c.id = a.house_property_id')
            ->where($conditions)
            ->count();
        $contract = ContractModel::alias('a')
            ->join('HouseNumber b', 'a.house_property_id = b.house_property_id and a.house_number_id = b.id')
            ->join('HouseProperty c', 'c.id = a.house_property_id')
            ->field('a.*, b.name as number_name, c.name as property_name')
            ->where($conditions)
            ->orderRaw('CASE WHEN a.end_date IS NULL THEN 1 ELSE 0 END, a.end_date ASC, a.house_property_id')
            ->select();
        foreach ($contract as $value) {
            if ($value['start_date']) {
                $value['start_date'] = \substr($value['start_date'], 0, 10);
            }
            if ($value['end_date']) {
                $value['remaining'] = \floor((\strtotime($value['end_date']) - \strtotime('today midnight')) / 86400);
                $value['end_date'] = \substr($value['end_date'], 0, 10);
            }
        }
        return $this->returnResult($contract, $count);
    }

    // 合同管理页面-获取合同信息
    public function getContractMessage()
    {
        $house_property_id = Property::getProperty();
        $valid = ContractModel::where('house_property_id', 'in', $house_property_id)
            ->whereNotNull('end_date')
            ->count();
        $sum = ContractModel::where('house_property_id', 'in', $house_property_id)
            ->count();
        $contract_info = [
            'valid' => $valid,
            'invalid' => $sum - $valid,
        ];
        return $this->returnResult($contract_info);
    }

    public function save()
    {
        $data = [
            'id' => $this->request->post('id/d', 0),
            'house_property_id' => $this->request->post('house_property_id/s', '', 'trim'),
            'house_number_id' => $this->request->post('house_number_id/s', '', 'trim'),
            'start_date' => $this->request->post('start_date/s', '', 'trim'),
            'end_date' => $this->request->post('end_date/s', '', 'trim'),
        ];
        $result = ContractAction::save($data);
        if ($result['flag']) {
            return $this->returnSuccess($result['msg']);
        } else {
            return $this->returnError($result['msg']);
        }
    }

    // 合同
    public function contract()
    {
        $number_id = $this->request->param('id/d', 0);
        // $number_id = 0;
        $number_data = NumberModel::where('a.id', $number_id)
            ->alias('a')
            ->join('HouseProperty b', 'a.house_property_id = b.id')
            ->leftJoin('HouseTenant c', 'a.tenant_id = c.id')
            ->field('a.*,b.address, b.landlord, b.id_card as landlordId, b.name as property_name, c.name as renter, c.id_card_number')
            ->select()->toArray();
        if (!$number_data) {
            return $this->returnError('房间不存在');
        }
        // var_dump($number_data);
        $tmp = new \PhpOffice\PhpWord\TemplateProcessor('static/wordfile/contract.docx'); //打开模板
        $tmp->setValue('landlord', $number_data[0]['landlord']); //替换变量name
        $tmp->setValue('landlordId', $number_data[0]['landlordId']); //替换变量name
        $tmp->setValue('renter', $number_data[0]['renter']);
        $tmp->setValue('renterId', $number_data[0]['id_card_number']);
        $tmp->setValue('address', $number_data[0]['address'] . $number_data[0]['name']);
        $tmp->setValue('rental', Property::convert_case_number($number_data[0]['rental']));
        $tmp->setValue('rentalLower', $number_data[0]['rental']);
        $tmp->setValue('depositLower', $number_data[0]['deposit']);
        $tmp->setValue('deposit', Property::convert_case_number($number_data[0]['deposit']));
        // $tmp->setValue('management', Property::convert_case_number($number_data[0]['management']));
        // $tmp->setValue('network', Property::convert_case_number($number_data[0]['network']));
        // $tmp->setValue('garbage_fee', Property::convert_case_number($number_data[0]['garbage_fee']));
        $startDate = explode('-', Date::getLease($number_data[0]['checkin_time'], $number_data[0]['lease'] - $number_data[0]['lease_type'])[0]);
        $endDate = explode('-', Date::getLease($number_data[0]['checkin_time'], $number_data[0]['lease'] + 11 - $number_data[0]['lease_type'])[1]);
        $tmp->setValue('startDate', $startDate[0] . '年' . $startDate[1] . '月' . $startDate[2] . '日');
        $tmp->setValue('endDate', $endDate[0] . '年' . $endDate[1] . '月' . $endDate[2] . '日');
        $tmp->saveAs('../tempfile/合同.docx'); //另存为
        $file_url = '../tempfile/合同.docx';
        // $file_name = basename($file_url);
        $file_type = explode('.', $file_url);
        $file_type = $file_type[count($file_type) - 1];
        $file_type = fopen($file_url, 'r'); //打开文件
        //输入文件标签
        header("Content-type: application/octet-stream");
        header("Accept-Ranges: bytes");
        header("Accept-Length: " . filesize($file_url));
        header("Content-Disposition:attchment; filename=" . json_encode($number_data[0]['property_name'] . '-' . $number_data[0]['name'] . '合同.docx'));
        //输出文件内容
        echo fread($file_type, filesize($file_url));
        fclose($file_type);
    }
}
