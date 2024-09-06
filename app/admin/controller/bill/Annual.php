<?php

namespace app\admin\controller\bill;

use app\admin\controller\Common;
use app\admin\model\HouseProperty as PropertyModel;
use app\admin\model\BillAnnual as AnnualModel;
use app\admin\model\BillSum as SumModel;

use think\facade\View;

class Annual extends Common
{
    public function index()
    {
        return View::fetch();
    }

    public function query()
    {
        $loginUser = $this->auth->getLoginUser();
        $annual = AnnualModel::where('a.admin_user_id', $loginUser['id'])
        ->alias('a')
        ->join('HouseProperty c', 'c.id = a.house_property_id')
        ->field('a.*, c.name as property_name')
        ->select();
        foreach ($annual as $key => $value) {
            $value['profit'] = $value['price'] - $value['deposit'];
        }
        // $lasyYear = SumModel::where('admin_user_id', $loginUser['id'])->order('year desc')->find();
        return $this->returnElement($annual);
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
            $role->save($data);
            $this->success('修改成功。');
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
        if (!$property = PropertyModel::find($id)) {
            $this->error('删除失败，记录不存在。');
        }
        $validate = new PropertyValidate();
        if (!$validate->scene('delete')->check(['id' => $id])) {
            $this->error('删除失败，' . $validate->getError() . '。');
        }
        $property->delete();
        $this->success('删除成功。');
    }
}
