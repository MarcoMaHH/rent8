<?php

namespace app\admin\controller\bill;

use app\admin\controller\Common;
use app\admin\model\HouseProperty as PropertyModel;
use app\admin\model\BillAnnual as AnnualModel;
use app\admin\model\BillSum as SumModel;
use think\facade\Db;
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

    public function arrange()
    {
        $loginUser = $this->auth->getLoginUser();
        $sumData = SumModel::where('admin_user_id', $loginUser['id'])->select();

        // 开始事务
        $transFlag = true;
        Db::startTrans();
        try {

            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            $transFlag = false;
            // 回滚事务
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($transFlag) {
            $this->success('整理成功');
        }
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
