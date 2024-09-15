<?php

namespace app\admin\controller\bill;

use app\admin\controller\Common;
use app\admin\model\BillSum as SumModel;
use app\admin\model\AdminUser as UserModel;
use app\admin\library\Property;
use app\admin\model\HouseProperty as PropertyModel;
use app\admin\model\HouseOther as OtherModel;
use think\facade\View;

class Report extends Common
{
    public function index()
    {
        return View::fetch();
    }

    public function query()
    {
        $loginUser = $this->auth->getLoginUser();
        $house_property_id = $this->request->param('house_property_id/d', Property::getProperty($loginUser['id']));
        $accounting_month = date('Y-m');
        $income = SumModel::where('house_property_id', $house_property_id)
        ->where('type', TYPE_INCOME)
        ->where('accounting_date', $accounting_month)
        ->sum('amount');
        // todo
        $spending = SumModel::where('house_property_id', $house_property_id)
        ->where('type', TYPE_EXPENDITURE)
        ->where('accounting_date', $accounting_month)
        ->sum('amount');
        $user = UserModel::find($loginUser['id']);
        $number_count =  $user->houseNumber->where('house_property_id', $house_property_id)->count();
        $empty_count =  $user->houseNumber->where('rent_mark', 'N')->where('house_property_id', $house_property_id)->count();
        $occupancy = $number_count == 0 ? '0%' : round((($number_count - $empty_count) / $number_count) * 100).'%';
        $house_info = [
            'income' => $income,
            'spending' => round($spending, 2),
            'profit' => round($income - $spending, 2),
            'occupancy' => $occupancy,
            'number_count' => $number_count,
            'empty_count' => $empty_count,
        ];
        return $this->returnElement($house_info);
    }

    public function echar()
    {
        $loginUser = $this->auth->getLoginUser();
        $house_property_id = $this->request->param('house_property_id/d', Property::getProperty($loginUser['id']));
        $currentDate = new \DateTime();
        $currentDate->modify('first day of this month');
        $charData = array();
        for ($i = 12; $i >= 0; $i--) {
            $month = clone $currentDate;
            $accounting_month = $month->modify("-{$i} month")->format('Y-m');
            $income = SumModel::where('house_property_id', $house_property_id)
            ->where('accounting_date', $accounting_month)
            ->where('type', TYPE_INCOME)
            ->sum('amount');
            $spending = SumModel::where('house_property_id', $house_property_id)
            ->where('accounting_date', $accounting_month)
            ->where('type', TYPE_EXPENDITURE)
            ->sum('amount');
            \array_push($charData, ['month' => $accounting_month, 'project' => '收入', 'money' => $income]);
            \array_push($charData, ['month' => $accounting_month, 'project' => '支出', 'money' => intval($spending)]);
            \array_push($charData, ['month' => $accounting_month, 'project' => '利润', 'money' => $income - intval($spending)]);
        }
        return $this->returnElement($charData);
    }

    public function expenditure()
    {
        $loginUser = $this->auth->getLoginUser();
        $house_property_id = $this->request->param('house_property_id/d', Property::getProperty($loginUser['id']));
        $accounting_month = date('Y-m');
        $other = OtherModel::where('house_property_id', $house_property_id)
        ->where('accounting_date', $accounting_month)
        ->where('accout_mark', 'Y')
        ->sum('total_money');
        return $this->returnElement($other);
    }
}
