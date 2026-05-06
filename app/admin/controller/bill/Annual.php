<?php

namespace app\admin\controller\bill;

use app\admin\controller\Common;
use app\admin\model\HouseProperty as PropertyModel;
use app\admin\model\BillSum as SumModel;
use app\admin\library\Property;
use think\facade\Db;
use think\facade\View;

class Annual extends Common
{
    public function index()
    {
        return View::fetch('/bill/annual/index');
    }

    public function query()
    {
        $loginUser = $this->auth->getLoginUser();
        $result = Property::getProperty();
        $years = SumModel::where('admin_user_id', $loginUser['id'])->field('annual')->group('annual')->order('annual')->select()->toArray();
        $annual = [];
        foreach ($years as $year) {
            $lasyYear = $year['annual'];
            $propertys = PropertyModel::where('admin_user_id', $loginUser['id'])->select()->toArray();
            $allIncomes = SumModel::where('house_property_id', 'in', $result)
                ->where('type', 'I')
                ->where('annual', $lasyYear)
                ->field('annual, house_property_id, sum(amount) as amount')
                ->group('annual, house_property_id')
                ->select()->toArray();
            $allExpenditures = SumModel::where('house_property_id', 'in', $result)
                ->where('type', 'E')
                ->where('annual', $lasyYear)
                ->field('annual, house_property_id, sum(amount) as amount')
                ->group('annual, house_property_id')
                ->select()->toArray();
            foreach ($propertys as $property) {
                $propertyId = $property['id'];
                $income = 0;
                $expenditure = 0;

                // 从预先查询的数据中筛选当前年份和房产的收入和支出
                foreach ($allIncomes as $in) {
                    if ($in['house_property_id'] == $propertyId) {
                        $income += $in['amount'];
                    }
                }

                foreach ($allExpenditures as $ex) {
                    if ($ex['house_property_id'] == $propertyId) {
                        $expenditure += $ex['amount'];
                    }
                }

                if ($income > 0 || $expenditure > 0) {
                    array_unshift($annual, [
                        'annual' => $lasyYear,
                        'admin_user_id' => $loginUser['id'],
                        'property_name' => $property['name'],
                        'income' => $income,
                        'expenditure' => round($expenditure, 2),
                        'profit' => round($income - $expenditure, 2),
                    ]);
                }
            }
        }
        return $this->returnResult($annual);
    }
}
