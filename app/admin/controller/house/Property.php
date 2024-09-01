<?php

namespace app\admin\controller\house;

use app\admin\controller\Common;
use app\admin\model\HouseProperty as PropertyModel;
use app\admin\model\BillMeter as MeterModel;
use app\admin\validate\HouseProperty as PropertyValidate;
use think\facade\View;
use think\facade\Db;

class Property extends Common
{
    public function index()
    {
        return View::fetch();
    }

    public function query()
    {
        $loginUser = $this->auth->getLoginUser();
        $property = PropertyModel::where('admin_user_id', $loginUser['id'])
        ->order('firstly')
        ->select();
        return $this->returnElement($property);
    }

    public function save()
    {
        $id = $this->request->post('id/d', 0);
        $data = [
            'name' => $this->request->post('name/s', null, 'trim'),
            'address' => $this->request->post('address/s', '', 'trim'),
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

    public function sort()
    {
        $id = $this->request->post('id/d', 0);
        $loginUser = $this->auth->getLoginUser();
        $data = PropertyModel::where('admin_user_id', $loginUser['id'])->select()->toArray();
        $result = [];
        foreach ($data as $value) {
            $temp = array(
                'id' => $value['id']
            );
            if ($value['id'] === $id) {
                $temp['firstly'] = 'Y';
            } else {
                $temp['firstly'] = 'N';
            }
            \array_push($result, $temp);
        }
        $property = new PropertyModel();
        $property->saveAll($result);
        $this->success('切换成功');
    }
}
