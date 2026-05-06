<?php

namespace app\common\house;

use app\admin\model\HouseProperty as PropertyModel;
use app\admin\model\HouseNumber as NumberModel;
use app\admin\model\HouseFee as FeeModel;
use app\admin\model\HouseReceipt as ReceiptModel;
use app\admin\model\TenantMessage as TenantModel;
use app\admin\model\TenantContract as ContractModel;
use app\admin\model\PhotoContract as ContractPhotoModel;
use think\facade\Db;

class Number
{
    public static function save($id, $data)
    {
        if (!PropertyModel::find($data['house_property_id'])) {
            return ['flag' => false, 'msg' => '房产不存在'];
        }
        if ($id) {
            if (!$number = NumberModel::find($id)) {
                return ['flag' => false, 'msg' => '房间不存在'];
            }
            if (NumberModel::where('name', $data['name'])->where('id', '<>', $id)->where('house_property_id', $data['house_property_id'])->find()) {
                return ['flag' => false, 'msg' => '房间名已存在'];
            }
            $number->save($data);
            return ['flag' => true, 'msg' => '保存成功'];
        } else {
            if (NumberModel::where('name', $data['name'])->where('house_property_id', $data['house_property_id'])->find()) {
                return ['flag' => false, 'msg' => '房间名已存在'];
            }
            $data['calculate_date'] = date('Y-m-d');
            NumberModel::create($data);
            return ['flag' => true, 'msg' => '保存成功'];
        }
    }

    public static function checkin($data, $admin_user_id)
    {
        if (!$number_data = NumberModel::find($data['house_number_id'])) {
            return ['flag' => false, 'msg' => '入住失败，房间不存在'];
        }
        $free = FeeModel::where('admin_user_id', $admin_user_id)->select()->toArray();
        $checkin_time = $data['calculate_date'];
        $data['checkin_date'] = $checkin_time;
        $cycle = $number_data['cycle'];
        $cycle_type = $number_data['cycle_type'];

        $note = "单据开出中途退房，一律不退房租。 \n" .
            "到期如果不续租，超期将按每天" . $number_data['daily_rent'] . "元计算。";
        // 账单资料
        $transFlag = true;
        Db::startTrans();
        try {
            //insert租客资料
            $tenant = TenantModel::create($data);
            // insert收据资料
            $receipt_data = [
                'bill_type' => config('params.bill_types')['房租账单'],
                'house_property_id' => $data['house_property_id'],
                'house_number_id' => $data['house_number_id'],
                'start_time' => $data['calculate_date'],
                'end_time' => date('Y-m-d', strtotime("$checkin_time +$cycle $cycle_type -1 day")),
                'tenant_message_id' => $tenant->id,
                'meter_reading_time' => $data['calculate_date'],
                'electricity_meter_this_month' => $data['electricity_meter'],
                'electricity_meter_last_month' => $data['electricity_meter'],
                'electricity_consumption' => 0,
                'electricity' => 0,
                'water_meter_this_month' => $data['water_meter'],
                'water_meter_last_month' => $data['water_meter'],
                'water_consumption' => 0,
                'water' => 0,
                'rental' => $number_data['rental'],
                'deposit' => $number_data['deposit'],
                'total_money' => $number_data['rental'] + $number_data['deposit'],
                'note' => $note,
            ];
            foreach ($free as $value) {
                if ($value['column_name']) {
                    $receipt_data[$value['column_id']] = $number_data[$value['column_id']];
                    $receipt_data['total_money'] += $number_data[$value['column_id']];
                }
            }
            $receipt_id = ReceiptModel::create($receipt_data);

            //update房间资料
            $update_data = [
                'tenant_message_id' => $tenant->id,
                'house_receipt_id' => $receipt_id->id,
                'checkin_date' => $data['checkin_date'],
                'payment_date' => $data['checkin_date'],
            ];
            $number_data->save($update_data);
            //insert合同资料
            $contract_data = [
                'house_property_id' => $data['house_property_id'],
                'house_number_id' => $data['house_number_id'],
                'tenant_message_id' => $tenant->id,
                'start_date' => $data['calculate_date'],
            ];
            ContractModel::create($contract_data);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            $transFlag = false;
            // 回滚事务
            Db::rollback();
            return ['flag' => false, 'msg' => $e->getMessage()];
        }
        if ($transFlag) {
            return ['flag' => true, 'msg' => '入住成功'];
        }
    }

    public static function checkout($data)
    {
        if (!$number_data = NumberModel::find($data['house_number_id'])) {
            return ['flag' => false, 'msg' => '退房失败，房间不存在'];
        }
        $transFlag = true;
        Db::startTrans();
        try {
            // 更新房间资料
            $number_update = [
                'checkin_date' => null,
                'payment_date' => null,
                'calculate_date' => $data['calculate_date'],
                'lease' => 0,
            ];
            $number_data->save($number_update);
            // 更新租客资料
            TenantModel::where([
                'house_property_id' => $number_data->house_property_id,
                'house_number_id' => $number_data->id
            ])->update([
                'leave_date' => $data['calculate_date'] ?? date('Y-m-d'),
                'checkout_mark' => 'Y'
            ]);
            // 更新收据资料
            $receipt_data = ReceiptModel::find($data['house_receipt_id']);
            $datediff = intval((strtotime($data['calculate_date']) - strtotime($receipt_data->start_time)) / (60 * 60 * 24));
            $note = '';
            $rental = 0;
            if ($datediff > 0) {
                $rental = $datediff * $number_data->daily_rent;
                $note = '租金为' . $datediff . '*' . $number_data->daily_rent . '=' . $rental . '。';
            }
            $receipt_update = [
                'bill_type' => config('params.bill_types')['退房账单'],
                'start_time' => $data['calculate_date'],
                'meter_reading_time' => $data['calculate_date'],
                'end_time' => null,
                'electricity_meter_this_month' => $data['electricity_meter'],
                'water_meter_this_month' => $data['water_meter'],
                'water_consumption' => $data['water_meter'] - $receipt_data->water_meter_last_month,
                'fees1' => 0,
                'fees2' => 0,
                'fees3' => 0,
                'fees4' => 0,
                'rental' => $rental,
                'deposit' => 0 - $number_data->deposit,
                'total_money' => $rental - $number_data->deposit,
                'note' => $note,
            ];
            $receipt_update['electricity_consumption'] = $data['electricity_meter'] - $receipt_data->electricity_meter_last_month;
            $receipt_update['electricity'] = $receipt_update['electricity_consumption'] * $number_data->electricity_price;
            $receipt_update['water_consumption'] = $data['water_meter'] - $receipt_data->water_meter_last_month;
            $receipt_update['water'] = $receipt_update['water_consumption'] * $number_data->water_price;
            $receipt_update['total_money'] = $receipt_update['rental'] + $receipt_update['deposit'] + $receipt_update['electricity'] + $receipt_update['water'];
            $receipt_data->save($receipt_update);

            // 移除合同
            $contract = ContractModel::where([
                'house_number_id' => $data['house_number_id'],
                'end_date' => null
            ])->find();

            if ($contract) {
                // 移除合同图片
                $photoRootPath = app()->getRootPath() . 'public';
                $folders = [];
                ContractPhotoModel::where('tenant_contract_id', $contract->id)
                    ->chunk(100, function ($photos) use ($photoRootPath, &$folders) {
                        foreach ($photos as $photo) {
                            $filePath = $photoRootPath . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, array_slice(explode('/', $photo['url']), 1));
                            $folderPath = dirname($filePath);
                            $folders[$folderPath] = true;
                            if (file_exists($filePath)) {
                                try {
                                    unlink($filePath);
                                } catch (\Exception $e) {
                                    continue;
                                }
                            }
                        }
                    });
                foreach (array_keys($folders) as $folderPath) {
                    try {
                        if (is_dir($folderPath) && count(scandir($folderPath)) === 2) {
                            rmdir($folderPath);
                        }
                    } catch (\Exception $e) {
                        continue;
                    }
                }
                ContractPhotoModel::where('tenant_contract_id', $contract->id)->delete();
                $contract->delete();
            } else {
                ContractModel::where(['house_number_id' => $data['house_number_id']])->save(['type' => 'N']);
            }

            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            $transFlag = false;
            // 回滚事务
            Db::rollback();
            return ['flag' => false, 'msg' => $e->getMessage()];
        }
        if ($transFlag) {
            return ['flag' => true, 'msg' => '退房成功'];
        }
    }

    public static function delete($id)
    {
        if (!$number = NumberModel::find($id)) {
            return ['flag' => false, 'msg' => '删除失败,房间不存在。'];
        }
        if ($number->checkin_date != null) {
            return ['flag' => false, 'msg' => '删除失败，房间已有租客入住。'];
        }
        $number->delete();
        return ['flag' => true, 'msg' => '删除成功'];
    }
}
