<?php

namespace app\admin\controller\house;

use app\admin\controller\Common;
use app\admin\model\HouseProperty as PropertyModel;
use app\admin\model\PayPhoto as PhotoModel;
use app\common\house\Property as PropertyAction;
use think\facade\View;

class Property extends Common
{
    public function index()
    {
        return View::fetch('/house/property/index');
    }

    public function queryProperty()
    {
        $loginUser = $this->auth->getLoginUser();
        $property = PropertyModel::where('admin_user_id', $loginUser['id'])
            ->order('id')
            ->select();
        return $this->returnResult($property);
    }

    // hearder查询全部房产
    public function queryPropertyAll()
    {
        $loginUser = $this->auth->getLoginUser();
        $property = PropertyModel::where('admin_user_id', $loginUser['id'])
            ->field('id,name,firstly')
            ->order('firstly, id')
            ->select()
            ->toArray();
        array_unshift($property, ['id' => 0, 'name' => '全部', 'firstly' => 'N']);
        return $this->returnResult($property);
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
            'subscribe_mark' => $this->request->post('subscribe_mark/s', 'Y', 'trim'),
        ];
        $loginUser = $this->auth->getLoginUser();
        $result = PropertyAction::save($id, $data, $loginUser['id']);
        if ($result['flag']) {
            return $this->returnSuccess($result['msg']);
        } else {
            return $this->returnError($result['msg']);
        }
    }

    public function delete()
    {
        $id = $this->request->param('id/d', 0);
        $result = PropertyAction::delete($id);
        if ($result['flag']) {
            return $this->returnSuccess($result['msg']);
        } else {
            return $this->returnError($result['msg']);
        }
    }

    public function sort()
    {
        $id = $this->request->param('id/d', 0);
        $result = PropertyAction::sort($id, $this->auth->getLoginUser()['id']);
        if ($result['flag']) {
            return $this->returnSuccess($result['msg']);
        } else {
            return $this->returnError($result['msg']);
        }
    }

    // 支付照片
    public function upload()
    {
        $way = $this->request->post('way/s', '', 'trim');
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('file');
        $originalName = $file->getOriginalName();

        // 提取文件名（不含扩展名）和扩展名
        $fileNameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);

        // 生成随机数
        $randomNum = mt_rand(1000, 9999); // 生成一个4位的随机数

        // 组合新的文件名
        $newFileName = $fileNameWithoutExt . '_' . $randomNum . '.' . $extension;

        // 上传到本地服务器
        $filePath = \think\facade\Filesystem::disk('public')->putFileAs('pay/' . $way, $file, $newFileName);
        $data = [
            'house_property_id' => $way,
            'url' => '/storage/' . $filePath
        ];
        PhotoModel::create($data);
        return json(['code' => 1, 'msg' => '上传成功']);
    }

    // 查询照片信息
    public function queryPhoto()
    {
        $id = $this->request->param('id/d', 0);
        $photo = PhotoModel::where('house_property_id', $id)->select();
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
        $photoPath = app()->getRootPath() . 'public' . $photo['url'];
        if (file_exists($photoPath) && !unlink($photoPath)) {
            return $this->returnError('删除失败，文件无法删除。');
        }
        return $this->returnSuccess('删除成功');
    }
}
