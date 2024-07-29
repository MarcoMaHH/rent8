<?php

namespace app\admin\controller\house;

use app\admin\controller\Common;
use app\admin\model\HouseProperty as PropertyModel;
use app\admin\model\HouseNumber as NumberModel;
use app\admin\model\HouseTenant as TenantModel;
use app\admin\model\HouseBilling as BillingModel;
use app\admin\model\BillMeter as MeterModel;
use app\admin\validate\HouseNumber as NumberValidate;
use app\admin\library\Property;
use app\admin\library\Date;
use think\facade\Db;
use think\facade\View;

class Number extends Common
{
    public function index()
    {
        return View::fetch();
    }

    public function query()
    {
        $loginUser = $this->auth->getLoginUser();
        $house_property_id = $this->request->param('house_property_id/d', Property::getProperty($loginUser['id']));
        $name = $this->request->param('name/s', '', 'trim');
        $conditions = array(
            ['a.house_property_id', '=', $house_property_id],
        );
        if ($name != '') {
            \array_push($conditions, ['a.name', 'like', '%' . $name . '%']);
        }
        $numbers = NumberModel::where($conditions)
        ->alias('a')
        ->join('HouseProperty b', 'a.house_property_id = b.id')
        ->field('a.*,b.name as property_name')
        ->order('a.name')
        ->select();
        foreach ($numbers as  $value) {
            if ($value['lease']) {
                $value['rent_date'] = Date::getLease($value['checkin_time'], $value['lease'] - $value['lease_type'])[0];
            }
        }
        return $this->returnElement($numbers);
    }

    public function save()
    {
        $id = $this->request->post('id/d', 0);
        $data = [
            'house_property_id' => $this->request->post('house_property_id/d', 0),
            'name' => $this->request->post('name/s', '', 'trim'),
            'rental' => $this->request->post('rental/d', 0),
            'deposit' => $this->request->post('deposit/d', 0),
            'lease_type' => $this->request->post('lease_type/d', 0),
            'management' => $this->request->post('management/d', 0),
            'garbage_fee' => $this->request->post('garbage_fee/d', 0),
            'daily_rent' => $this->request->post('daily_rent/d', 0),
            'water_price' => $this->request->post('water_price/f', 0.0),
            'electricity_price' => $this->request->post('electricity_price/f', 0.0),
        ];
        $validate = new NumberValidate();
        if ($id) {
            if (!$validate->scene('update')->check($data)) {
                $this->error('修改失败，' . $validate->getError() . '。');
            }
            if (!$permission = NumberModel::find($id)) {
                $this->error('修改失败，记录不存在');
            }
            $permission->save($data);
            $this->success('修改成功');
        }
        if (!$validate->scene('insert')->check($data)) {
            $this->error('添加失败，' . $validate->getError() . '。');
        }
        $transFlag = true;
        Db::startTrans();
        try {
            $result = NumberModel::create($data);
            $propertyArr = PropertyModel::find($data['house_property_id']);
            $electricity = MeterModel::where(
                ['house_property_id' => $data['house_property_id'],
                'type' => TYPE_ELECTRICITY]
            )->find();
            if($electricity) {
                $meter = ['house_number_id' => $electricity->house_number_id . ',' . $result->id];
                $electricity->save($meter);
            } else {
                $meter = [
                    'name' => $propertyArr['name'] . '-电表',
                    'house_property_id' => $data['house_property_id'],
                    'house_number_id' => $result->id,
                    'type' => TYPE_ELECTRICITY
                ];
                MeterModel::create($meter);
            }

            $water = MeterModel::where(
                ['house_property_id' => $data['house_property_id'],
                'type' => TYPE_WATER]
            )->find();
            if($water) {
                $meter = ['house_number_id' => $water->house_number_id . ',' . $result->id];
                $water->save($meter);
            } else {
                $meter = [
                    'name' => $propertyArr['name'] . '-水表',
                    'house_property_id' => $data['house_property_id'],
                    'house_number_id' => $result->id,
                    'type' => TYPE_WATER
                ];
                MeterModel::create($meter);
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            $transFlag = false;
            // 回滚事务
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($transFlag) {
            $this->success('添加成功');
        }
    }

    public function delete()
    {
        $id = $this->request->param('id/d', 0);
        if (!$permission = NumberModel::find($id)) {
            $this->error('删除失败,记录不存在。');
        }
        $permission->delete();
        $this->success('删除成功');
    }

    //新租
    public function checkin()
    {
        // 租客资料
        $house_number_id = $this->request->post('house_number_id/d', 0);
        $checkin_time = $this->request->post('checkin_time/s', '', 'trim');
        $data = [
            'house_property_id' => $this->request->post('house_property_id/d', 0),
            'house_number_id' => $house_number_id,
            'name' => $this->request->post('name/s', '', 'trim'),
            'sex' => $this->request->post('sex/s', '', 'trim'),
            'checkin_time' => $checkin_time,
            'phone' => $this->request->post('phone/d', ''),
            'id_card_number' => $this->request->post('id_card_number/d', ''),
            'native_place' => $this->request->post('native_place/s', '', 'trim'),
            'work_units' => $this->request->post('work_units/s', '', 'trim'),
        ];
        if (!$number_data = NumberModel::find($house_number_id)) {
            $this->error('修改失败，记录不存在');
        }
        // 账单资料
        $note = "单据开出中途退房，一律不退房租。 \n" .
                "到期如果不续租，超期将按每天" . $number_data['daily_rent'] . "元计算。" ;
        $lease_type = $number_data['lease_type'];
        $transFlag = true;
        Db::startTrans();
        try {
            //insert租客资料
            $tenant = TenantModel::create($data);
            // 删除上位租客的账单
            BillingModel::where('house_property_id', $this->request->post('house_property_id/d', 0))
            ->where('house_number_id', $house_number_id)
            ->delete();
            //insert账单资料
            $billing_data = [
                'house_property_id' => $data['house_property_id'],
                'house_number_id' => $data['house_number_id'],
                'start_time' => $checkin_time,
                'end_time' => date('Y-m-d', strtotime("$checkin_time +$lease_type month -1 day")),
                'tenant_id' => $tenant->id,
                'rental' => $number_data['rental'] * $lease_type,
                'deposit' => $number_data['deposit'],
                'management' => $number_data['management'] * $lease_type,
                'garbage_fee' => $number_data['garbage_fee'] * $lease_type,
                'total_money' => $number_data['deposit'] + $number_data['rental'] * $lease_type + $number_data['management'] * $lease_type + $number_data['garbage_fee'] * $lease_type,
                'note' => $note
            ];
            $billing = BillingModel::create($billing_data);
            //update房号资料
            $update_data = [
                'tenant_id' => $tenant->id,
                'receipt_number' => $billing->id,
                'payment_time' => $checkin_time,
                'checkin_time' => $checkin_time,
                'rent_mark' => 'Y',
                'lease' => $lease_type,
            ];
            $number_data->save($update_data);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            $transFlag = false;
            // 回滚事务
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($transFlag) {
            $this->success('添加租客成功');
        } else {
            $this->error('系统出错了');
        }
    }

    //退房
    public function checkout()
    {
        $number_id = $this->request->param('id/d', 0);
        $leave_time = $this->request->param('leave_time/s', date('Y-m-d'), 'trim');
        if (!$number_data = NumberModel::find($number_id)) {
            $this->error('修改失败，记录不存在');
        }
        $number_update = [
            'rent_mark' => 'N',
            'tenant_id' => '',
            'checkin_time' => null,
            // 'payment_time' => date('Y-m-d'),
            'lease' => 0,
        ];
        $number_data->save($number_update);
        TenantModel::where('house_property_id', $number_data->house_property_id)
        ->where('house_number_id', $number_id)
        ->where('leave_time', 'null')
        ->data(['leave_time' => $leave_time, 'mark' => 'Y'])
        ->update();
        $billing_data = BillingModel::find($number_data->receipt_number);
        $datediff = intval((strtotime($leave_time) - strtotime($billing_data->start_time)) / (60 * 60 * 24));
        $note = '';
        $rental = 0;
        if ($datediff > 0) {
            $rental = $datediff * $number_data->daily_rent;
            $note = '租金为' . $datediff . '*' . $number_data->daily_rent . '=' . $rental . '。';
        }
        $billing_update = [
            'start_time' => $leave_time,
            'meter_reading_time' => $leave_time,
            'end_time' => null,
            'rental' => $rental,
            'deposit' => 0 - $number_data->deposit,
            'management' => 0,
            'garbage_fee' => 0,
            'note' => $note,
        ];
        $billing_data->save($billing_update);
        $this->success('退房成功');
    }

    //其他页面查询numberId
    public function queryNumberId()
    {
        $loginUser = $this->auth->getLoginUser();
        $house_property_id = $this->request->param('house_property_id/d', Property::getProperty($loginUser['id']));
        $number = NumberModel::where('house_property_id', $house_property_id)
        ->order('name')
        ->select()
        ->toArray();
        return $this->returnElement($number);
    }

    public function contract()
    {
        $number_id = $this->request->param('id/d', 0);
        // $number_id = 0;
        $number_data = NumberModel::where('a.id', $number_id)
        ->alias('a')
        ->join('HouseProperty b', 'a.house_property_id = b.id')
        ->leftJoin('HouseTenant c', 'a.tenant_id = c.id')
        ->field('a.*,b.address, c.name as renter, c.id_card_number')
        ->select()->toArray();
        if (!$number_data) {
            $this->error('修改失败，记录不存在');
        }
        // var_dump($number_data);
        $tmp = new \PhpOffice\PhpWord\TemplateProcessor('static/wordfile/contract.docx');//打开模板
        $tmp->setValue('landlord', '            ');//替换变量name
        $tmp->setValue('landlordId', '          ');//替换变量name
        $tmp->setValue('renter', $number_data[0]['renter']);
        $tmp->setValue('renterId', $number_data[0]['id_card_number']);
        $tmp->setValue('address', $number_data[0]['address'] . $number_data[0]['name']);
        $tmp->setValue('rental', Property::convert_case_number($number_data[0]['rental']));
        $tmp->setValue('rentalLower', $number_data[0]['rental']);
        $tmp->setValue('depositLower', $number_data[0]['deposit']);
        $tmp->setValue('deposit', Property::convert_case_number($number_data[0]['deposit']));
        $tmp->setValue('management', Property::convert_case_number($number_data[0]['management']));
        $tmp->setValue('garbage_fee', Property::convert_case_number($number_data[0]['garbage_fee']));
        $startDate = explode('-', Date::getLease($number_data[0]['checkin_time'], $number_data[0]['lease'] - $number_data[0]['lease_type'])[0]);
        $endDate = explode('-', Date::getLease($number_data[0]['checkin_time'], $number_data[0]['lease'] + 11 - $number_data[0]['lease_type'])[1]);
        $tmp->setValue('startDate', $startDate[0] . '年' . $startDate[1] . '月' . $startDate[2] . '日');
        $tmp->setValue('endDate', $endDate[0] . '年' . $endDate[1] . '月' . $endDate[2] . '日');
        $tmp->saveAs('../tempfile/合同.docx');//另存为
        $file_url = '../tempfile/合同.docx';
        $file_name = basename($file_url);
        $file_type = explode('.', $file_url);
        $file_type = $file_type[count($file_type) - 1];
        $file_type = fopen($file_url, 'r'); //打开文件
        //输入文件标签
        header("Content-type: application/octet-stream");
        header("Accept-Ranges: bytes");
        header("Accept-Length: " . filesize($file_url));
        header("Content-Disposition:attchment; filename=" . json_encode('合同.docx'));
        //输出文件内容
        echo fread($file_type, filesize($file_url));
        fclose($file_type);
    }
}
