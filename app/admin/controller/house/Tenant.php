<?php

namespace app\admin\controller\house;

use app\admin\controller\Common;
use app\admin\model\HouseProperty as PropertyModel;
use app\admin\model\HouseTenant as TenantModel;
use app\admin\model\HouseNumber as NumberModel;
use app\admin\validate\HouseTenant as TenantValidate;
use app\admin\library\Property;
use think\facade\View;

class Tenant extends Common
{
    public function index()
    {
        return View::fetch();
    }

    public function query()
    {
        $loginUser = $this->auth->getLoginUser();
        $house_property_id = $this->request->param('house_property_id/d', Property::getProperty($loginUser['id']));
        $conditions = array(
            ['a.house_property_id', '=', $house_property_id]
        );
        // $house_number_id = $this->request->param('house_number_id/d', 0);
        // if ($house_number_id) {
        //     \array_push($conditions, ['a.house_number_id', '=', $house_number_id]);
        // }
        $count = TenantModel::where($conditions)->alias('a')->count();
        $tenants = TenantModel::where($conditions)->alias('a')
        ->join('HouseNumber b', 'a.house_property_id = b.house_property_id and a.house_number_id = b.id')
        ->join('HouseProperty c', 'a.house_property_id = c.id')
        ->field("a.*,b.name as number_name, c.name as property_name")
        ->order(['mark','leave_time' => 'desc','number_name'])
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
        return $this->returnElement($tenants, $count);
    }

    public function save()
    {
        $id = $this->request->post('id/d', 0);
        $data = [
            'house_property_id' => $this->request->post('house_property_id/d', 0),
            'house_number_id' => $this->request->post('house_number_id/d', 0),
            'name' => $this->request->post('name/s', '', 'trim'),
            'sex' => $this->request->post('sex/s', '', 'trim'),
            'phone' => $this->request->post('phone/s', '', 'trim'),
            'id_card_number' => $this->request->post('id_card_number/s', '', 'trim'),
            'native_place' => $this->request->post('native_place/s', '', 'trim'),
            'work_units' => $this->request->post('work_units/s', '', 'trim'),
            'checkin_time' => $this->request->post('checkin_time/s', \date('Ymd'), 'trim'),
        ];
        if ($id) {
            if (!$tenant = TenantModel::find($id)) {
                $this->error('修改失败，记录不存在');
            }
            $tenant->save($data);
            $this->success('修改成功');
        }
        TenantModel::create($data);
        $this->success('添加成功');
    }

    public function delete()
    {
        $id = $this->request->param('id/d', 0);
        $validate = new TenantValidate();
        if (!$validate->scene('delete')->check(['id' => $id])) {
            $this->error('删除失败，' . $validate->getError() . '。');
        }
        if (!$property = TenantModel::find($id)) {
            $this->error('删除失败，记录不存在。');
        }
        $property->delete();
        $this->success('删除成功。');
    }
}
