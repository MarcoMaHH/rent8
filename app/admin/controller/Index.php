<?php

namespace app\admin\controller;

use think\facade\View;
use think\App;
use think\Db;
use app\admin\model\HouseProperty as PropertyModel;
use app\admin\model\HouseBilling as BillingModel;
use app\admin\model\BillSum as SumModel;
use app\admin\model\AdminUser as UserModel;

class Index extends Common
{
    protected $checkLoginExclude = ['login', 'logout'];

    public function index()
    {
        return View::fetch();
    }

    public function queryHouse()
    {
        $loginUser = $this->auth->getLoginUser();
        $property_count = PropertyModel::where('admin_user_id', $loginUser['id'])->count();
        $user = UserModel::find($loginUser['id']);
        $number_count =  $user->houseNumber->count();
        $empty_count =  $user->houseNumber->where('rent_mark', 'N')->count();
        $occupancy = $number_count == 0 ? '0%' : round((($number_count - $empty_count) / $number_count) * 100) . '%';
        $property = PropertyModel::where('admin_user_id', $loginUser['id'])->select()->toArray();
        $result = array_map(function ($item) {
            return $item['id'];
        }, $property);
        $accounting_month = date('Y-m');
        $income = SumModel::where('house_property_id', 'in', $result)
        ->where('type', TYPE_INCOME)
        ->where('accounting_date', $accounting_month)
        ->sum('amount');
        // todo
        $spending = SumModel::where('house_property_id', 'in', $result)
        ->where('type', TYPE_EXPENDITURE)
        ->where('accounting_date', $accounting_month)
        ->sum('amount');
        $house_info = [
            'property_count' => $property_count,
            'number_count' => $number_count,
            'empty_count' => $empty_count,
            'occupancy' => $occupancy,
            'profit' => $income - intval($spending),
        ];
        return $this->returnElement($house_info);
    }

    public function login()
    {
        if ($this->request->isPost()) {
            $data = [
                'username' => $this->request->post('username/s', '', 'trim'),
                'password' => $this->request->post('password/s', ''),
            ];
            if (!$this->auth->login($data['username'], $data['password'])) {
                $this->error('登陆失败：' . $this->auth->getError());
            }
            $loginUser = $this->auth->getLoginUser();
            $user = UserModel::find($loginUser['id']);
            $user->save(['login_date' => date("Y-m-d H:i:s")]);
            $this->success('登陆成功');
        }
        View::assign('thisYear', date("Y"));
        View::assign('token', $this->getToken());
        return View::fetch();
    }

    public function logout()
    {
        $this->auth->logout();
        $this->success('退出成功');
    }

    public function password()
    {
        $password = $this->request->post('password/s', '');
        $this->auth->changePassword($password);
        $this->success('密码修改成功。');
    }

    public function echar()
    {
        $loginUser = $this->auth->getLoginUser();
        $property = PropertyModel::where('admin_user_id', $loginUser['id'])->select()->toArray();
        $result = array_map(function ($item) {
            return $item['id'];
        }, $property);
        $charData = array();
        for ($i = 12; $i >= 0; $i--) {
            $setDate = date("Y-m", strtotime("-$i Months"));
            $income = SumModel::where('house_property_id', 'in', $result)
            ->where('type', TYPE_INCOME)
            ->where('accounting_date', $setDate)
            ->sum('amount');
            $spending = SumModel::where('house_property_id', 'in', $result)
            ->where('type', TYPE_EXPENDITURE)
            ->where('accounting_date', $setDate)
            ->sum('amount');
            \array_push($charData, ['month' => $setDate, 'project' => '收入', 'money' => $income]);
            \array_push($charData, ['month' => $setDate, 'project' => '支出', 'money' => intval($spending)]);
            \array_push($charData, ['month' => $setDate, 'project' => '利润', 'money' => $income - intval($spending)]);
        }
        return $this->returnElement($charData);
    }
}
