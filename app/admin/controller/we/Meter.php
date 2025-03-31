<?php

namespace app\admin\controller\we;

use app\admin\controller\Common;
use app\admin\model\HouseNumber as NumberModel;
use app\admin\model\WeMeter as MeterModel;
use app\admin\model\WeDetail as WeDetailModel;
use app\admin\model\WeBill as WeBillModel;
use app\admin\library\Property;
use think\facade\View;
use think\facade\Db;

class Meter extends Common
{
    public function index()
    {
        return View::fetch('/we/meter/index');
    }

    public function queryMeter()
    {
        $house_property_id = Property::getProperty();
        $conditions = array(
            ['a.house_property_id', 'in', $house_property_id]
        );
        $count = MeterModel::where($conditions)->alias('a')->count();
        $meters = MeterModel::where($conditions)->alias('a')
        ->join('HouseProperty c', 'a.house_property_id = c.id')
        ->field("a.*, c.name as property_name")
        ->order(['house_property_id'])
        ->select();
        foreach ($meters as $value) {
            if ($value['house_number_id']) {
                $value['number_name'] = '';
                $array = explode(',', $value['house_number_id']);
                foreach ($array as $value1) {
                    $value['number_name'] .= NumberModel::find($value1)['name'] . ',';
                }
                if (strlen($value['number_name']) > 0) {
                    $value['number_name'] = substr($value['number_name'], 0, -1);
                }
            }
        }
        return $this->returnResult($meters);
    }

    public function save()
    {
        $id = $this->request->post('id/d', 0);
        $data = [
            'house_property_id' => $this->request->post('house_property_id/s', null, 'trim'),
            'property_name' => $this->request->post('property_name/s', null, 'trim'),
            'type' => $this->request->post('type/s', null, 'trim'),
            'name' => $this->request->post('name/s', null, 'trim'),
            'house_number_id' => $this->request->post('house_number_id/s', null, 'trim'),
        ];
        if ($id) {
            if (!$meter = MeterModel::find($id)) {
                return $this->returnError('修改失败，记录不存在。');
            }
            $meter->save($data);
            return $this->returnSuccess('修改成功');
        }
        MeterModel::create($data);
        return $this->returnSuccess('添加成功');
    }

    public function delete()
    {
        $id = $this->request->post('id/d', 0);
        if (!$meter = MeterModel::find($id)) {
            return $this->returnError('删除失败，记录不存在。');
        }
        $transFlag = true;
        Db::startTrans();
        try {
            $meter->delete();
            WeDetailModel::where('meter_id', $id)->delete();
            WeBillModel::where('meter_id', $id)->delete();
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            $transFlag = false;
            // 回滚事务
            Db::rollback();
            return $this->returnError($e->getMessage());
        }
        if ($transFlag) {
            return $this->returnSuccess('删除成功。');
        }
    }

    //其他页面查询-全部房产时，不可用
    public function queryMeterId()
    {
        $house_property_id = Property::getProperty();
        if (count($house_property_id) > 1) {
            return $this->returnResult();
        } else {
            $type = $this->request->param('type/s',  null, 'trim');
            $meter = MeterModel::where([
                'house_property_id' => $house_property_id,
                'type' => $type
            ])->select();
            return $this->returnResult($meter);
        }
    }
}
