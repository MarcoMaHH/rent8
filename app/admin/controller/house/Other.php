<?php

namespace app\admin\controller\house;

use app\admin\controller\Common;
use app\admin\model\HouseProperty as PropertyModel;
use app\admin\model\HouseOther as OtherModel;
use app\admin\model\BillSum as SumModel;
use app\admin\library\Property;
use think\facade\View;

class Other extends Common
{
    public function index()
    {
        return View::fetch('/house/other/index');
    }

    public function queryOther()
    {
        $house_property_id = Property::getProperty();
        $conditions = array(
            ['a.house_property_id', 'in', $house_property_id],
        );
        $type = $this->request->param('type/s', '', 'trim');
        if ($type) {
            \array_push($conditions, ['a.type', '=', $type]);
        }
        $count = OtherModel::alias('a')->where($conditions)->count();
        $numbers = OtherModel::alias('a')
            ->leftJoin('HouseProperty b', 'a.house_property_id = b.id')
            ->field('a.*,b.name as property_name')
            ->where($conditions)
            ->order(['a.accout_mark', 'a.accounting_date' => 'desc'])
            ->select();
        foreach ($numbers as $value) {
            if ($value['accounting_date']) {
                $value['accounting_date'] = \substr($value['accounting_date'], 0, 10);
            }
            switch ($value['type']) {
                case 'D':
                    $value['type_name'] = '维修费 ';
                    break;
                case 'E':
                    $value['type_name'] = '工资 ';
                    break;
                case 'F':
                    $value['type_name'] = '其他 ';
                    break;
                default:
                    break;
            }
        }
        return $this->returnResult($numbers, $count);
    }

    public function save()
    {
        $id = $this->request->post('id/d', 0);
        $house_property_id = $this->request->post('house_property_id/s', '', 'trim');
        if (!$propertyArr = PropertyModel::find($house_property_id)) {
            return $this->returnError('房产数据异常');
        }
        $data = [
            'house_property_id' => $this->request->post('house_property_id/s', '', 'trim'),
            'type' => $this->request->post('type/s', '', 'trim'),
            'total_money' => $this->request->post('total_money/f', 0.0),
            'note' => $this->request->post('note/s', '', 'trim'),
            'circulate_mark' => $this->request->post('circulate_mark/s', '', 'trim'),
            'accounting_date' => $this->request->post('accounting_date/s', '', 'trim'),
            'accout_mark' => 'N'
        ];
        if ($id) {
            if (!$electricity = OtherModel::find($id)) {
                return $this->returnError('修改失败，记录不存在。');
            }
            $electricity->save($data);
            return $this->returnSuccess('修改成功');
        }
        OtherModel::create($data);
        return $this->returnSuccess('添加成功');
    }

    public function delete()
    {
        $id = $this->request->param('id/d', 0);
        if (!$permission = OtherModel::find($id)) {
            return $this->returnError('删除失败,记录不存在。');
        }
        $permission->delete();
        return $this->returnSuccess('删除成功');
    }

    //到账
    public function account()
    {
        $id = $this->request->param('id/d', 0);
        if (!$other = OtherModel::find($id)) {
            return $this->returnError('到账失败,记录不存在。');
        }
        if ($other['circulate_mark'] == 'Y') {
            $accounting_date = $other['accounting_date'];
            $new = [
                'house_property_id' => $other['house_property_id'],
                'type' => $other['type'],
                'total_money' => $other['total_money'],
                'accout_mark' => 'N',
                'circulate_mark' => 'Y',
                'accounting_date' => date('Y-m-d', strtotime("$accounting_date +1 month"))
            ];
            OtherModel::create($new);
        }
        $accounting_month = date('Y-m');
        $sum_data = SumModel::where([
            'house_property_id' => $other['house_property_id'],
            'type' => TYPE_EXPENDITURE,
            'accounting_date' => $accounting_month,
        ])->find();
        if ($sum_data) {
            $sum_data->save([
                'amount' => $sum_data->amount + $other['total_money'],
            ]);
        } else {
            SumModel::create([
                'admin_user_id' => $this->auth->getLoginUser()['id'],
                'house_property_id' => $other['house_property_id'],
                'amount' => $other['total_money'],
                'type' => TYPE_EXPENDITURE,
                'accounting_date' => $accounting_month,
                'annual' => date('Y'),
            ]);
        }
        $other->save(['accout_mark' => 'Y']);
        return $this->returnSuccess('操作成功');
    }
}
