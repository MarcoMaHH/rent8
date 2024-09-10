<?php

namespace app\admin\controller\admin;

use app\admin\controller\Common;
use app\admin\model\AdminUser as UserModel;
use app\admin\model\AdminRole as RoleModel;
use app\admin\model\HouseProperty as PropertyModel;
use app\admin\model\WeMeter as MeterModel;
use app\admin\model\WeBill as WeBillModel;
use app\admin\model\WeDetail as WeDetailModel;
use app\admin\model\BillSum as SumModel;
use app\admin\model\BillAnnual as AnnualModel;
use app\admin\model\HouseTenant as TenantModel;
use app\admin\model\HouseBilling as BillingModel;
use app\admin\model\HouseNumber as NumberModel;
use app\admin\model\HouseOther as OtherModel;
use app\admin\model\TenantPhoto as PhotoModel;
use app\admin\validate\AdminUser as UserValidate;
use think\facade\View;
use think\facade\Db;

class User extends Common
{
    public function index()
    {
        return View::fetch();
    }

    public function query()
    {
        $user = UserModel::with('adminRole')->withoutField(['password', 'salt'], true)->select()->toArray();
        $user = \array_map(function ($item) {
            return array(
                'id' => $item["id"],
                'admin_role_id' => $item["admin_role_id"],
                'username' => $item["username"],
                'role_name' => $item["adminRole"]["name"],
                'login_date' => $item["login_date"],
                'expiration_date' => $item["expiration_date"] ? \substr($item["expiration_date"], 0, 10) : '',
                'create_time' =>  \substr($item["create_time"], 0, 10),
                'update_time' => $item["update_time"] ? \substr($item["update_time"], 0, 10) : '',
                'state' => strtotime($item["expiration_date"]) > time() ? 'Y' : 'N',
            );
        }, $user);
        return $this->returnElement($user);
    }

    public function save()
    {
        $id = $this->request->post('id/d', 0);
        $data = [
            'username' => $this->request->post('username/s', '', 'trim'),
            'admin_role_id' => $this->request->post('admin_role_id/d', 0),
            'expiration_date' => $this->request->post('expiration_date/s', '', 'trim'),
            'password' => $this->request->post('password/s', '', 'trim')
        ];
        if ($id && $data['password'] === '') {
            unset($data['password']);
        }
        $validate = new UserValidate();
        if ($id) {
            if (!$validate->scene('update')->check(\array_merge($data, ['id' => $id]))) {
                $this->error('修改失败，' . $validate->getError() . '。');
            }
            if (!$user = UserModel::find($id)) {
                $this->error('修改失败，记录不存在。');
            }
            $user->save($data);
            $this->success('修改成功。');
        }
        if (!$validate->scene('insert')->check($data)) {
            $this->error('添加失败,' . $validate->getError() . '。');
        }
        UserModel::create($data);
        $this->success('添加成功。');
    }

    public function delete()
    {
        $id = $this->request->param('id/d', 0);
        if (!$user = UserModel::find($id)) {
            $this->error('删除失败，记录不存在。');
        }
        $transFlag = true;
        Db::startTrans();
        try {
            $user->delete();
            
            $propertyIds = PropertyModel::where('admin_user_id', $id)->column('id');
            // 删除关联表数据
            WeDetailModel::where('house_property_id', 'in', $propertyIds)->delete();
            WeBillModel::where('house_property_id', 'in', $propertyIds)->delete();
            MeterModel::where('house_property_id', 'in', $propertyIds)->delete();
            SumModel::where('house_property_id', 'in', $propertyIds)->delete();
            TenantModel::where('house_property_id', 'in', $propertyIds)->delete();
            BillingModel::where('house_property_id', 'in', $propertyIds)->delete();
            AnnualModel::where('house_property_id', 'in', $propertyIds)->delete();
            OtherModel::where('house_property_id', 'in', $propertyIds)->delete();
            NumberModel::where('house_property_id', 'in', $propertyIds)->delete();
            // 删除图片文件
            $photoRootPath = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'storage';
            PhotoModel::where('house_property_id', 'in', $propertyIds)->chunk(100, function ($photos) use ($photoRootPath) {
                foreach ($photos as $photo) {
                    $photoName = explode('/', $photo['url']);
                    $filePath = $photoRootPath . DIRECTORY_SEPARATOR . $photoName[2] . DIRECTORY_SEPARATOR . $photoName[3];
                    if (file_exists($filePath)) {
                        unlink($filePath); // 移除@，让错误自然抛出
                    }
                }
            });
            PhotoModel::where('house_property_id', 'in', $propertyIds)->delete();
            PropertyModel::where('admin_user_id', $id)->delete();

            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            $transFlag = false;
            // 回滚事务
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($transFlag) {
            $this->success('删除成功。');
        }
    }
}
