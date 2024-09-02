<?php

namespace app\admin\controller\house;

use app\admin\controller\Common;
use app\admin\model\HouseProperty as PropertyModel;
use app\admin\model\HouseNumber as NumberModel;
use app\admin\model\BillMeter as MeterModel;
use app\admin\library\Property;
use think\facade\View;
use think\facade\Db;

class Hydroelectricity extends Common
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
        $count = MeterModel::where($conditions)->alias('a')->count();
        $meters = MeterModel::where($conditions)->alias('a')
        ->join('HouseProperty c', 'a.house_property_id = c.id')
        ->field("a.*, c.name as property_name")
        ->order(['house_property_id'])
        ->select();
        foreach ($meters as $value) {
            if ($value['house_number_id']) {
                $value['number_name'] = '';
                $array = explode(',', $value['house_number_id']);
                foreach ($array as $value1) {
                    $value['number_name'] .= NumberModel::find($value1)['name'] . ',';
                }
                if (strlen($value['number_name']) > 0) {
                    $value['number_name'] = substr($value['number_name'], 0, -1);
                }
            }
        }
        return $this->returnElement($meters);
    }

    public function save()
    {
        $id = $this->request->post('id/d', 0);
        $data = [
            'name' => $this->request->post('name/s', null, 'trim'),
            'address' => $this->request->post('address/s', null, 'trim'),
            'landlord' => $this->request->post('landlord/s', null, 'trim'),
            'phone' => $this->request->post('phone/s', null, 'trim'),
            'id_card' => $this->request->post('id_card/s', null, 'trim'),
        ];
        if ($id) {
            if (!$role = PropertyModel::find($id)) {
                $this->error('修改失败，记录不存在。');
            }
            $transFlag = true;
            Db::startTrans();
            try {
                $role->save($data);
                // 修改电表名称
                MeterModel::where(
                    ['house_property_id' => $id,
                    'type' => TYPE_ELECTRICITY]
                )->update(['name' => $data['name'] . '-电表']);
                // 修改水表名称
                $water = MeterModel::where(
                    ['house_property_id' => $id,
                    'type' => TYPE_WATER]
                )->update(['name' => $data['name'] . '-水表']);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                $transFlag = false;
                // 回滚事务
                Db::rollback();
                $this->error($e->getMessage());
            }
            if ($transFlag) {
                $this->success('修改成功。');
            }
        }
        $loginUser = $this->auth->getLoginUser();
        $data['admin_user_id'] = $loginUser['id'];
        $data['firstly'] = 'Y';
        PropertyModel::where('admin_user_id', $loginUser['id'])->update(['firstly' => 'N']);
        PropertyModel::create($data);
        $this->success('添加成功。');
    }

    public function delete()
    {
        $id = $this->request->param('id/d', 0);
        $validate = new PropertyValidate();
        if (!$validate->scene('delete')->check(['id' => $id])) {
            $this->error('删除失败，' . $validate->getError() . '。');
        }
        if (!$property = PropertyModel::find($id)) {
            $this->error('删除失败，记录不存在。');
        }
        $property->delete();
        $this->success('删除成功。');
    }
}
