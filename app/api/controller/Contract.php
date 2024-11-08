<?php

namespace app\api\controller;

use app\api\library\Property;
use app\admin\model\HouseContract as ContractModel;
use app\admin\model\HouseProperty as PropertyModel;
use app\admin\model\HouseNumber as NumberModel;
use app\common\house\Contract as ContractAction;

class Contract extends Common
{
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
                $value['end_date'] = \substr($value['end_date'], 0, 10);
                $value['remaining'] = \floor((\strtotime($value['end_date']) - \time()) / 86400);
            }
        }
        return $this->returnWechat($contract, $count);
    }

    public function edit()
    {
        $id = $this->request->param('id/d', 0);
        $data = null; // 初始化$data变量
        if ($id) {
            $data = ContractModel::find($id);
            if (!$data) {
                return $this->returnError('合同不存在');
            }
            if ($data->start_date) {
                $data->start_date = \substr($data->start_date, 0, 10);
            }
            if ($data->end_date) {
                $data->end_date = \substr($data->end_date, 0, 10);
            }
        } else {
            return $this->returnError('缺少合同ID');
        }

        if ($data) {
            $property_name = PropertyModel::find($data->house_property_id);
            $data['property_name'] = $property_name->name;
        }

        if ($data) {
            $number_name = NumberModel::find($data->house_number_id);
            $data['number_name'] = $number_name->name;
        }

        $returnData = [
            "code" => 1,
            "data" => $data
        ];
        return \json($returnData);
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
}
