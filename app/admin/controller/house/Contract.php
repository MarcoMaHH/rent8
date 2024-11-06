<?php

namespace app\admin\controller\house;

use app\admin\controller\Common;
use app\admin\model\HouseContract as ContractModel;
use app\common\house\Contract as ContractAction;
use app\admin\model\HouseOther as OtherModel;
use app\admin\model\BillSum as SumModel;
use app\admin\library\Property;
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
            ->field('a.*,b.name as number_name, c.name as property_name')
            ->where($conditions)
            ->order(['a.end_date'])
            ->select();
        foreach ($contract as $value) {
            if ($value['start_date']) {
                $value['start_date'] = \substr($value['start_date'], 0, 10);
            }
            if ($value['end_date']) {
                $value['end_date'] = \substr($value['end_date'], 0, 10);
            }
        }
        return $this->returnResult($contract, $count);
    }

    public function save()
    {
        $id = $this->request->post('id/d', 0);
        $data = [
            'house_property_id' => $this->request->post('house_property_id/s', '', 'trim'),
            'house_number_id' => $this->request->post('house_number_id/s', '', 'trim'),
            'start_date' => $this->request->post('start_date/s', '', 'trim'),
            'end_date' => $this->request->post('end_date/s', '', 'trim'),
        ];
        $result = ContractAction::save($id, $data);
        if ($result['flag']) {
            return $this->returnSuccess($result['msg']);
        } else {
            return $this->returnError($result['msg']);
        }
    }
}
