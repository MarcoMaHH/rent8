<?php

namespace app\admin\controller\house;

use app\admin\controller\Common;
use app\admin\library\Property;
use app\admin\model\HouseBilling as BillingModel;
use app\admin\model\HouseNumber as NumberModel;
use app\admin\model\HouseProperty as PropertyModel;
use think\facade\View;

class Collected extends Common
{
    public function index()
    {
        return View::fetch();
    }

    public function query()
    {
        $page = $this->request->param('page/d', LAYUI_PAGE);
        $limit = $this->request->param('limit/d', LAYUI_LIMIT);
        $loginUser = $this->auth->getLoginUser();
        $house_property_id = $this->request->param('house_property_id/d', Property::getProperty($loginUser['id']));
        $house_number_id = $this->request->param('house_number_id/d', 0);
        $conditions = array(
            ['a.house_property_id', '=', $house_property_id],
            ['a.accounting_date', 'not null', '']
        );
        if ($house_number_id) {
            \array_push($conditions, ['a.house_number_id', '=', $house_number_id]);
        }
        $count = BillingModel::where($conditions)
        ->alias('a')->count();
        $billing = BillingModel::where($conditions)
        ->alias('a')
        ->join('HouseNumber b', 'a.house_property_id = b.house_property_id and a.house_number_id = b.id')
        ->join('HouseProperty c', 'c.id = a.house_property_id')
        ->field('a.*,b.name as number_name, c.name as property_name')
        ->order(['a.accounting_date' => 'desc','number_name'])
        ->page($page, $limit)
        ->select();
        foreach ($billing as  $value) {
            $value['accounting_date'] = \substr($value['accounting_date'], 0, 10);
            if ($value['end_time']) {
                $value['lease'] = \substr($value['start_time'], 0, 10)  . '至' . \substr($value['end_time'], 0, 10);
            } elseif ($value['meter_reading_time']) {
                $value['lease'] = \substr($value['start_time'], 0, 10) . ' 退房';
            } else {
                $value['lease'] = \substr($value['start_time'], 0, 10);
            }
        }
        return $this->returnResult($billing, $count);
    }

    public function sum()
    {
        $loginUser = $this->auth->getLoginUser();
        $house_property_id = $this->request->param('house_property_id/d', Property::getProperty($loginUser['id']));
        $sum = BillingModel::where('house_property_id', $house_property_id)
        ->whereTime('accounting_date', 'today')
        ->sum('total_money');
        return $this->returnResult([], 1, $sum);
    }
}
