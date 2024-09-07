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
        $years = SumModel::where('admin_user_id', $loginUser['id'])->field('annual')->group('annual')->select()->toArray();
        $result = [];
        $currentYear = date('Y');

        foreach ($years as $value) {
            if ($value['annual'] < $currentYear - 1) {
                $data = [
                    'annual' => $value['annual'],
                    'admin_user_id' => $loginUser['id'],
                    'income' => SumModel::where('admin_user_id', $loginUser['id'])
                        ->where('type', 'I')
                        ->where('annual', $value['annual'])
                        ->sum('amount'),
                    'expenditure' => SumModel::where('admin_user_id', $loginUser['id'])
                        ->where('type', 'E')
                        ->where('annual', $value['annual'])
                        ->sum('amount'),
                ];

                $result[] = $data;
            }
        }
        return $this->returnElement($result);

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
