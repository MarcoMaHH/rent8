<?php

namespace app\admin\controller;

use app\admin\model\HouseBilling as BillingModel;
use app\admin\model\HouseProperty as PropertyModel;
use app\admin\model\HouseContract as ContractModel;
use app\admin\model\BillSum as SumModel;
use app\admin\model\AdminUser as UserModel;
use think\facade\View;

class Index extends Common
{
    protected $checkLoginExclude = ['login', 'logout'];

    public function index()
    {
        return View::fetch();
    }

    public function queryHouseInfo()
    {
        $loginUser = $this->auth->getLoginUser();
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
        $spending = SumModel::where('house_property_id', 'in', $result)
            ->where('type', TYPE_EXPENDITURE)
            ->where('accounting_date', $accounting_month)
            ->sum('amount');
        $house_info = [
            'number_count' => $number_count,
            'empty_count' => $empty_count,
            'occupancy' => $occupancy,
            'profit' =>  round($income - $spending, 2),
        ];
        return $this->returnResult($house_info);
    }

    public function queryBill()
    {
        $loginUser = $this->auth->getLoginUser();
        $properties = PropertyModel::where('admin_user_id', $loginUser['id'])
            ->select()
            ->toArray();
        $result = array_map(function ($property) {
            return $property['id'];
        }, $properties);
        $conditions = array(
            ['a.house_property_id', 'in', $result],
            ['a.start_time', '< time', 'today+7 days'],
            ['a.accounting_date', 'null', ''],
        );
        $bill = BillingModel::where($conditions)
            ->alias('a')
            ->join('HouseNumber b', 'b.house_property_id = a.house_property_id and b.id = a.house_number_id')
            ->join('HouseProperty c', 'c.id = a.house_property_id')
            ->field('a.id, a.total_money, a.house_property_id, a.start_time, b.name as number_name, c.name as property_name')
            ->order(['a.start_time' => 'asc'])
            ->select();
        $sum = 0;
        foreach ($bill as $value) {
            if ($value['start_time']) {
                $value['start_time'] = \substr($value['start_time'], 0, 10);
            }
            $sum += $value['total_money'] ? $value['total_money'] : 0;
        }
        return $this->returnResult($bill, 0, round($sum, 2));
    }

    public function queryContract()
    {
        $loginUser = $this->auth->getLoginUser();
        $properties = PropertyModel::where('admin_user_id', $loginUser['id'])
            ->select()
            ->toArray();
        $result = array_map(function ($property) {
            return $property['id'];
        }, $properties);
        $conditions = array(
            ['a.house_property_id', 'in', $result],
            ['a.end_date', '< time', 'today+7 days'],
        );
        $contract = ContractModel::where($conditions)
            ->alias('a')
            ->join('HouseNumber b', 'b.house_property_id = a.house_property_id and b.id = a.house_number_id')
            ->join('HouseProperty c', 'c.id = a.house_property_id')
            ->field('a.id, a.house_property_id, a.end_date, b.name as number_name, c.name as property_name')
            ->order(['a.end_date' => 'asc'])
            ->select();
        foreach ($contract as $value) {
            if ($value['end_date']) {
                $value['end_date'] = \substr($value['end_date'], 0, 10);
            }
        }
        return $this->returnResult($contract);
    }

    public function login()
    {
        if ($this->request->isPost()) {
            $data = [
                'username' => $this->request->post('username/s', '', 'trim'),
                'password' => $this->request->post('password/s', ''),
            ];
            if (!$this->auth->login($data['username'], $data['password'])) {
                return $this->returnError('登陆失败：' . $this->auth->getError());
            }
            $loginUser = $this->auth->getLoginUser();
            $user = UserModel::find($loginUser['id']);
            $user->save(['login_date' => date("Y-m-d H:i:s")]);
            return $this->returnSuccess('登陆成功');
        }
        View::assign('thisYear', date("Y"));
        View::assign('token', $this->getToken());
        return View::fetch();
    }

    public function logout()
    {
        $this->auth->logout();
        return $this->returnSuccess('退出成功');
    }

    public function password()
    {
        $password = $this->request->post('password/s', '');
        $this->auth->changePassword($password);
        return $this->returnSuccess('密码修改成功。');
    }

    public function echar()
    {
        $loginUser = $this->auth->getLoginUser();
        $property = PropertyModel::where('admin_user_id', $loginUser['id'])->select()->toArray();
        $result = array_map(function ($item) {
            return $item['id'];
        }, $property);
        $currentDate = new \DateTime();
        $currentDate->modify('first day of this month');
        $charData = array();
        for ($i = 12; $i >= 0; $i--) {
            $month = clone $currentDate;
            $setDate = $month->modify("-{$i} month")->format('Y-m');
            $income = SumModel::where('house_property_id', 'in', $result)
                ->where('type', TYPE_INCOME)
                ->where('accounting_date', $setDate)
                ->sum('amount');
            $spending = SumModel::where('house_property_id', 'in', $result)
                ->where('type', TYPE_EXPENDITURE)
                ->where('accounting_date', $setDate)
                ->sum('amount');
            \array_push($charData, ['month' => $setDate, 'project' => '收入', 'money' => round($income, 2)]);
            \array_push($charData, ['month' => $setDate, 'project' => '支出', 'money' => round($spending, 2)]);
            \array_push($charData, ['month' => $setDate, 'project' => '利润', 'money' => round($income - $spending, 2)]);
        }
        return $this->returnResult($charData);
    }
}
