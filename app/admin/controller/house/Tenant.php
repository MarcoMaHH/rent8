<?php

namespace app\admin\controller\house;

use app\admin\controller\Common;
use app\admin\model\HouseProperty as PropertyModel;
use app\admin\model\HouseTenant as TenantModel;
use app\admin\model\HouseNumber as NumberModel;
use app\admin\model\TenantPhoto as PhotoModel;
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
        $house_property_id = Property::getProperty();
        $conditions = array(
            ['a.house_property_id', 'in', $house_property_id]
        );
        $parameter = $this->request->param('parameter/s', '');
        if ($parameter) {
            $conditions[] = function ($query) use ($parameter) {
                $query->where('a.name', 'like', "%{$parameter}%")
                        ->whereOr('b.name', 'like', "%{$parameter}%")
                        ->whereOr('a.phone', 'like', "%{$parameter}%")
                        ->whereOr('a.id_card_number', 'like', "%{$parameter}%");
            };
        }
        $count = TenantModel::alias('a')
        ->leftjoin('HouseNumber b', 'a.house_property_id = b.house_property_id and a.house_number_id = b.id')
        ->where($conditions)->count();
        $tenants = TenantModel::alias('a')
        ->leftjoin('HouseNumber b', 'a.house_property_id = b.house_property_id and a.house_number_id = b.id')
        ->join('HouseProperty c', 'a.house_property_id = c.id')
        ->where($conditions)
        ->field("a.*,b.name as number_name, c.name as property_name")
        ->order(['mark','leave_time' => 'desc','checkin_time' => 'desc'])
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
        return $this->returnResult($tenants, $count);
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
            'note' => $this->request->post('note/s', '', 'trim'),
            'checkin_time' => $this->request->post('checkin_time/s', '', 'trim'),
        ];
        if ($id) {
            if (!$tenant = TenantModel::find($id)) {
                return $this->returnError('修改失败，记录不存在');
            }
            $tenant->save($data);
            return $this->returnSuccess('修改成功');
        }
        TenantModel::create($data);
        return $this->returnSuccess('添加成功');
    }

    public function delete()
    {
        $id = $this->request->param('id/d', 0);
        $validate = new TenantValidate();
        if (!$validate->scene('delete')->check(['id' => $id])) {
            return $this->returnError('删除失败，' . $validate->getError() . '。');
        }
        if (!$property = TenantModel::find($id)) {
            return $this->returnError('删除失败，记录不存在。');
        }
        $property->delete();
        return $this->returnSuccess('删除成功。');
    }

    // 上传照片信息
    public function upload()
    {
        $way = $this->request->post('way/s', '', 'trim');
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('file');
        $name = $file->getOriginalName();
        // 上传到本地服务器
        $savename = \think\facade\Filesystem::disk('public')->putFileAs($way, $file, time().substr(strrchr($name, '.'), 0));
        $house_property_id = $this->request->post('house_property_id/s', null, 'trim');
        $data = [
            'house_property_id' => $house_property_id,
            'tenant_id' => $way,
            'url' => '/storage/' . $savename
        ];
        PhotoModel::create($data);
        return json(['code' => 1, 'msg' => '上传成功']);
    }

    // 查询照片信息
    public function queryPhoto()
    {
        $id = $this->request->param('id/d', 0);
        $photo = PhotoModel::where('tenant_id', $id)->select();
        foreach ($photo as $value) {
            $value['name'] = $value['url'];
        }
        return $this->returnResult($photo);
    }

    // 删除照片
    public function deletePhoto()
    {
        $id = $this->request->post('id/d', 0);
        if (!$photo = PhotoModel::find($id)) {
            return $this->returnError('删除失败，记录不存在。');
        }
        $photo->delete();
        $photoName = explode('/', $photo['url']);
        $photoUrl =  app()->getRootPath();
        unlink($photoUrl . '\public\storage\\' . $photoName[2] . '\\' . $photoName[3]);
        return $this->returnSuccess('删除成功。');
    }
}
