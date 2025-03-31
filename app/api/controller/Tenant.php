<?php

namespace app\api\controller;

use app\admin\model\HouseProperty as PropertyModel;
use app\admin\model\HouseTenant as TenantModel;
use app\common\house\Tenant as TenantAction;
use app\admin\model\HouseNumber as NumberModel;
use app\api\library\Property;

class Tenant extends Common
{
    public function queryTenant()
    {
        $house_property_id = Property::getProperty();
        $conditions = array(
            ['a.house_property_id', 'in', $house_property_id],
            ['a.mark', '=', 'N'],
        );
        $parameter = $this->request->param('parameter/s', '');
        if ($parameter) {
            $conditions[] = function ($query) use ($parameter) {
                $query->where('b.name', 'like', "%{$parameter}%")
                    ->whereOr('c.name', 'like', "%{$parameter}%");
            };
        }
        $count = TenantModel::alias('a')
            ->join('HouseNumber b', 'a.house_property_id = b.house_property_id and a.house_number_id = b.id')
            ->join('HouseProperty c', 'a.house_property_id = c.id')
            ->where($conditions)
            ->count();
        $tenants = TenantModel::alias('a')
            ->join('HouseNumber b', 'a.house_property_id = b.house_property_id and a.house_number_id = b.id')
            ->join('HouseProperty c', 'a.house_property_id = c.id')
            ->where($conditions)
            ->field("a.*,b.name as number_name, c.name as property_name")
            ->order(['mark', 'leave_time' => 'desc', 'checkin_time' => 'desc'])
            ->select();
        foreach ($tenants as $value) {
            $value['checkin_time'] = \substr($value['checkin_time'], 0, 10);
            if ($value['leave_time']) {
                $value['leave_time'] = \substr($value['leave_time'], 0, 10);
            }
            if ($value['id_card_number']) {
                $value['age'] = date("Y") - \substr($value['id_card_number'], 6, 4);
            }
            switch ($value['sex']) {
                case 'F':
                    $value['sex_name'] = '女';
                    break;
                case 'M':
                    $value['sex_name'] = '男';
                    break;
                default:
                    $value['sex_name'] = '';
                    break;
            }
        }
        return $this->returnWechat($tenants, $count);
    }

    public function edit()
    {
        $id = $this->request->param('id/d', 0);
        if ($id) {
            if (!$data = TenantModel::find($id)) {
                return $this->returnError('租客不存在。');
            }
        }
        $property_name = PropertyModel::find($data->house_property_id);
        $data['property_name'] = $property_name->name;
        $number_name = NumberModel::find($data->house_number_id);
        $data['number_name'] = $number_name->name;
        if ($data['id_card_number']) {
            $data['age'] = date("Y") - \substr($data['id_card_number'], 6, 4);
        }
        switch ($data['sex']) {
            case 'F':
                $data['sex_name'] = '女';
                break;
            case 'M':
                $data['sex_name'] = '男';
                break;
            default:
                $data['sex_name'] = '';
                break;
        }
        $returnData = [
            "code" => 1,
            "data" => $data
        ];
        return \json($returnData);
    }

    public function save()
    {
        $id = $this->request->post('id/d', 0);
        $data = [
            'house_property_id' => $this->request->post('house_property_id/s', null),
            'house_number_id' => $this->request->post('house_number_id/d', 0),
            'name' => $this->request->post('name/s', '', 'trim'),
            'sex' => $this->request->post('sex/s', '', 'trim'),
            'phone' => $this->request->post('phone/s', null, 'trim'),
            'id_card_number' => $this->request->post('id_card_number/s', null, 'trim'),
            'native_place' => $this->request->post('native_place/s', '', 'trim'),
            'work_units' => $this->request->post('work_units/s', '', 'trim'),
            'checkin_time' => $this->request->post('checkin_time/s', '', 'trim'),
        ];
        $result = TenantAction::save($id, $data);
        if ($result['flag']) {
            return $this->returnSuccess($result['msg']);
        } else {
            return $this->returnError($result['msg']);
        }
    }

    public function delete()
    {
        $id = $this->request->param('id/d', 0);
        $result = TenantAction::delete($id);
        if ($result['flag']) {
            return $this->returnSuccess($result['msg']);
        } else {
            return $this->returnError($result['msg']);
        }
    }
}
