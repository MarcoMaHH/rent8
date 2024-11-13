<?php

namespace app\admin\controller\bill;

use app\admin\controller\Common;
use app\admin\model\BillSum as SumModel;
use app\admin\model\AdminUser as UserModel;
use app\admin\library\Property;
use app\admin\model\HouseOther as OtherModel;
use app\admin\model\WeBill as WeBillModel;
use app\admin\model\HouseContract as ContractModel;
use think\facade\View;

class Report extends Common
{
    public function index()
    {
        return View::fetch();
    }

    public function queryReport()
    {
        $house_property_id = Property::getProperty();
        $loginUser = $this->auth->getLoginUser();
        $accounting_month = date('Y-m');
        $income = SumModel::where('house_property_id', 'in', $house_property_id)
            ->where('type', TYPE_INCOME)
            ->where('accounting_date', $accounting_month)
            ->sum('amount');
        // todo
        $spending = SumModel::where('house_property_id', 'in', $house_property_id)
            ->where('type', TYPE_EXPENDITURE)
            ->where('accounting_date', $accounting_month)
            ->sum('amount');
        $user = UserModel::find($loginUser['id']);
        $number_count =  $user->houseNumber->where('house_property_id', 'in', $house_property_id)->count();
        $empty_count =  $user->houseNumber->where('rent_mark', 'N')->where('house_property_id', 'in', $house_property_id)->count();
        $occupancy = $number_count == 0 ? '0%' : round((($number_count - $empty_count) / $number_count) * 100) . '%';
        $contract = ContractModel::where('house_property_id', 'in', $house_property_id)
            ->whereNotNull('end_date')
            ->count();
        $house_info = [
            'income' => $income,
            'spending' => round($spending, 2),
            'profit' => round($income - $spending, 2),
            'occupancy' => $occupancy,
            'number_count' => $number_count,
            'empty_count' => $empty_count,
            'contract_count' => $contract,
        ];
        return $this->returnResult([$house_info]);
    }

    public function echar()
    {
        $house_property_id = Property::getProperty();
        $currentDate = new \DateTime();
        $currentDate->modify('first day of this month');
        $charData = array();
        for ($i = 12; $i >= 0; $i--) {
            $month = clone $currentDate;
            $accounting_month = $month->modify("-{$i} month")->format('Y-m');
            $income = SumModel::where('house_property_id', 'in', $house_property_id)
                ->where('accounting_date', $accounting_month)
                ->where('type', TYPE_INCOME)
                ->sum('amount');
            $spending = SumModel::where('house_property_id', 'in', $house_property_id)
                ->where('accounting_date', $accounting_month)
                ->where('type', TYPE_EXPENDITURE)
                ->sum('amount');
            \array_push($charData, ['month' => $accounting_month, 'project' => '收入', 'money' => $income]);
            \array_push($charData, ['month' => $accounting_month, 'project' => '支出', 'money' => intval($spending)]);
            \array_push($charData, ['month' => $accounting_month, 'project' => '利润', 'money' => $income - intval($spending)]);
        }
        return $this->returnResult($charData);
    }

    public function expenditure()
    {
        $house_property_id = Property::getProperty();
        if (count($house_property_id) > 1) {
            return $this->returnResult();
        } else {
            $first_day_of_month = date('Y-m-01');
            $last_day_of_month = date('Y-m-t');

            $other_total = OtherModel::where('house_property_id', 'in', $house_property_id)
                ->whereTime('accounting_date', 'between', [$first_day_of_month, $last_day_of_month])
                ->where('accout_mark', 'Y')
                ->sum('total_money');

            $water_total = WeBillModel::alias('a')
                ->join('we_meter b', 'a.meter_id = b.id and a.house_property_id=b.house_property_id')
                ->where('a.house_property_id', 'in', $house_property_id)
                ->whereTime('a.accounting_date', 'between', [$first_day_of_month, $last_day_of_month])
                ->where('b.type', TYPE_WATER)
                ->sum('a.master_sum');

            $electricity_total = WeBillModel::alias('a')
                ->join('we_meter b', 'a.meter_id = b.id and a.house_property_id=b.house_property_id')
                ->where('a.house_property_id', 'in', $house_property_id)
                ->whereTime('a.accounting_date', 'between', [$first_day_of_month, $last_day_of_month])
                ->where('b.type', TYPE_ELECTRICITY)
                ->sum('a.master_sum');
            // var_dump($other_total, $water_total, $electricity_total);
            $sum = $other_total + $water_total + $electricity_total;

            $result = [
                ['item' => '其他费用', 'percent' => $sum ? round(($other_total / $sum), 2) : 0],
                ['item' => '水费', 'percent' => $sum ? round(($water_total / $sum), 2) : 0],
                ['item' => '电费', 'percent' => $sum ? round(($electricity_total / $sum), 2) : 0]
            ];

            return $this->returnResult($result);
        }
    }
}
