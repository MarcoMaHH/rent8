<?php

namespace app\admin\controller\tenant;

use app\admin\controller\Common;
use app\admin\model\TenantMessage as TenantModel;
use app\admin\model\HouseNumber as NumberModel;
use app\common\house\Tenant as TenantAction;
use app\admin\model\PhotoTenant as PhotoModel;
use app\admin\model\HouseReceipt as ReceiptModel;
use app\admin\library\Property;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use think\facade\View;

class Message extends Common
{
    public function index()
    {
        return View::fetch('/tenant/message/index');
    }
    // 主页table查询
    public function queryTenant()
    {
        $house_property_id = Property::getProperty();
        $conditions = array(
            ['a.house_property_id', 'in', $house_property_id]
        );
        $parameter = $this->request->param('parameter/s', '');
        if ($parameter) {
            $conditions[] = function ($query) use ($parameter) {
                $query->where('a.name', 'like', "%{$parameter}%")
                    ->whereOr('b.name', 'like', "%{$parameter}%")
                    ->whereOr('a.phone', 'like', "%{$parameter}%")
                    ->whereOr('a.id_card_number', 'like', "%{$parameter}%");
            };
        }
        $count = TenantModel::alias('a')
            ->leftjoin('HouseNumber b', 'a.house_property_id = b.house_property_id and a.house_number_id = b.id')
            ->where($conditions)->count();
        $tenants = TenantModel::alias('a')
            ->leftjoin('HouseNumber b', 'a.house_property_id = b.house_property_id and a.house_number_id = b.id')
            ->join('HouseProperty c', 'a.house_property_id = c.id')
            ->where($conditions)
            ->field("a.*, b.name as number_name, c.name as property_name")
            ->order(['checkout_mark', 'leave_date' => 'desc', 'checkin_date' => 'desc'])
            ->select();
        foreach ($tenants as $value) {
            $value['checkin_date'] = \substr($value['checkin_date'], 0, 10);
            if ($value['leave_date']) {
                $value['leave_date'] = \substr($value['leave_date'], 0, 10);
            }
            if ($value['id_card_number']) {
                $value['age'] = date("Y") - \substr($value['id_card_number'], 6, 4);
            }
            switch ($value['sex']) {
                case 'F':
                    $value['sex_name'] = '女';
                    break;
                case 'M':
                    $value['sex_name'] = '男';
                    break;
                default:
                    $value['sex_name'] = '';
                    break;
            }
        }
        return $this->returnResult($tenants, $count);
    }
    // 删除功能
    public function delete()
    {
        $id = $this->request->param('id/d', 0);
        $result = TenantAction::delete($id);
        ReceiptModel::where('tenant_message_id', $id)->delete();
        if ($result['flag']) {
            return $this->returnSuccess($result['msg']);
        } else {
            return $this->returnError($result['msg']);
        }
    }

    // 新增-修改页面
    public function save()
    {
        View::assign('id', $this->request->get('id/d', 0));
        View::assign('tenant_message_id', $this->request->get('tenant_message_id/d', 0));
        return View::fetch('/tenant/message/save');
    }
    public function queryTenantEdit()
    {
        $id = $this->request->param('id/d', 0);
        $tenant_message_id = $this->request->param('tenant_message_id/d', 0);
        if ($id) {
            $data = TenantModel::where('a.id', $id)
                ->alias('a')
                ->join('HouseProperty b', 'a.house_property_id = b.id')
                ->join('HouseNumber c', 'a.house_number_id = c.id')
                ->field('a.*, b.name as property_name, c.name as number_name')
                ->select()->toArray();
            if (count($data) == 0) {
                return $this->returnError('租客不存在');
            } else {
                return $this->returnResult($data[0], 1, $data[0]['property_name'] . '-' . $data[0]['number_name'] . ' - 编辑租客信息');
            }
        } else {
            $tenant = TenantModel::where('a.id', $tenant_message_id)
                ->alias('a')
                ->join('HouseProperty b', 'a.house_property_id = b.id')
                ->join('HouseNumber c', 'a.house_number_id = c.id')
                ->field('a.*, b.name as property_name, c.name as number_name')
                ->select()->toArray();
            if (count($tenant) === 0) {
                return $this->returnError('租客不存在');
            }
            $data = [
                'house_property_id' => $tenant[0]['house_property_id'],
                'house_number_id' => $tenant[0]['house_number_id'],
                'pid' => $tenant[0]['id'],
            ];
            return $this->returnResult($data, 1, $tenant[0]['property_name']  . '-' . $tenant[0]['number_name'] . '-' . $tenant[0]['name'] . ' - 新增同住人员');
        }
    }
    public function runSave()
    {
        $id = $this->request->post('id/d', 0);
        $data = [
            'house_property_id' => $this->request->post('house_property_id/d', 0),
            'house_number_id' => $this->request->post('house_number_id/d', 0),
            'pid' => $this->request->post('pid/d', 0),
            'name' => $this->request->post('name/s', '', 'trim'),
            'sex' => $this->request->post('sex/s', '', 'trim'),
            'phone' => $this->request->post('phone/s', '', 'trim'),
            'id_card_number' => $this->request->post('id_card_number/s', '', 'trim'),
            'native_place' => $this->request->post('native_place/s', '', 'trim'),
            'work_units' => $this->request->post('work_units/s', '', 'trim'),
            'note' => $this->request->post('note/s', '', 'trim'),
            'checkin_date' => $this->request->post('checkin_date/s', '', 'trim'),
        ];
        $result = TenantAction::save($id, $data);
        if ($result['flag']) {
            return $this->returnSuccess($result['msg']);
        } else {
            return $this->returnError($result['msg']);
        }
    }

    // 上传照片信息
    public function upload()
    {
        $way = $this->request->post('way/s', '', 'trim');
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('file');
        $originalName = $file->getOriginalName();

        // 提取文件名（不含扩展名）和扩展名
        $fileNameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);

        // 生成随机数
        $randomNum = mt_rand(1000, 9999); // 生成一个4位的随机数

        // 组合新的文件名
        $newFileName = $fileNameWithoutExt . '_' . $randomNum . '.' . $extension;

        // 上传到本地服务器
        $filePath = \think\facade\Filesystem::disk('public')->putFileAs('tenant/' . $way, $file, $newFileName);
        $house_property_id = $this->request->post('house_property_id/s', null, 'trim');
        $data = [
            'house_property_id' => $house_property_id,
            'tenant_message_id' => $way,
            'url' => '/storage/' . $filePath
        ];
        PhotoModel::create($data);
        return json(['code' => 1, 'msg' => '上传成功']);
    }
    // 查询照片信息
    public function queryPhoto()
    {
        $id = $this->request->param('id/d', 0);
        $photo = PhotoModel::where('tenant_message_id', $id)->select();
        foreach ($photo as $value) {
            $value['name'] = $value['url'];
        }
        return $this->returnResult($photo);
    }
    // 删除照片
    public function deletePhoto()
    {
        $id = $this->request->post('id/d', 0);
        if (!$photo = PhotoModel::find($id)) {
            return $this->returnError('删除失败，记录不存在。');
        }
        $photo->delete();
        $photoPath = app()->getRootPath() . 'public' . $photo['url'];
        if (file_exists($photoPath) && !unlink($photoPath)) {
            return $this->returnError('删除失败，文件无法删除。');
        }
        return $this->returnSuccess('删除成功');
    }

    // 导出excel
    public function export()
    {
        // 查询数据
        $house_property_id = Property::getProperty();
        $conditions = array(
            ['a.house_property_id', 'in', $house_property_id]
        );
        $parameter = $this->request->param('parameter/s', '');
        if ($parameter) {
            $conditions[] = function ($query) use ($parameter) {
                $query->where('a.name', 'like', "%{$parameter}%")
                    ->whereOr('b.name', 'like', "%{$parameter}%")
                    ->whereOr('a.phone', 'like', "%{$parameter}%")
                    ->whereOr('a.id_card_number', 'like', "%{$parameter}%");
            };
        }
        $tenants = TenantModel::alias('a')
            ->leftjoin('HouseNumber b', 'a.house_property_id = b.house_property_id and a.house_number_id = b.id')
            ->join('HouseProperty c', 'a.house_property_id = c.id')
            ->where($conditions)
            ->field("a.*,b.name as number_name, c.name as property_name")
            ->order(['checkout_mark', 'leave_date' => 'desc', 'checkin_date' => 'desc'])
            ->select();
        foreach ($tenants as $value) {
            $value['checkin_date'] = \substr($value['checkin_date'], 0, 10);
            if ($value['leave_date']) {
                $value['leave_date'] = \substr($value['leave_date'], 0, 10);
            }
            if ($value['id_card_number']) {
                $value['age'] = date("Y") - \substr($value['id_card_number'], 6, 4);
            }
            switch ($value['sex']) {
                case 'F':
                    $value['sex_name'] = '女';
                    break;
                case 'M':
                    $value['sex_name'] = '男';
                    break;
                default:
                    $value['sex_name'] = '';
                    break;
            }
        }

        // 创建一个新的Spreadsheet对象
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // 填充数据
        $sheet->setCellValue('A1', '房产名');
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->setCellValue('B1', '房间名');
        $sheet->getColumnDimension('B')->setWidth(13);
        $sheet->setCellValue('C1', '姓名');
        $sheet->setCellValue('D1', '性别');
        $sheet->setCellValue('E1', '年龄');
        $sheet->setCellValue('F1', '身份证号码');
        $sheet->getColumnDimension('F')->setWidth(23);
        $sheet->getStyle('F:F')->getNumberFormat()->setFormatCode('0');
        $sheet->setCellValue('G1', '户口所在地');
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->setCellValue('H1', '现工作单位');
        $sheet->getColumnDimension('H')->setWidth(20);
        $sheet->setCellValue('I1', '联系电话');
        $sheet->getColumnDimension('I')->setWidth(15);
        $sheet->setCellValue('J1', '备注');
        $sheet->setCellValue('K1', '入住日期');
        $sheet->getColumnDimension('K')->setWidth(13);
        $sheet->setCellValue('L1', '离开日期');
        $sheet->getColumnDimension('L')->setWidth(13);
        $row = 2;
        foreach ($tenants as $value) {
            $sheet->setCellValue('A' . $row, $value['property_name']);
            $sheet->setCellValue('B' . $row, $value['number_name']);
            $sheet->setCellValue('C' . $row, $value['name']);
            $sheet->setCellValue('D' . $row, $value['sex_name']);
            $sheet->setCellValue('E' . $row, $value['age']);
            $sheet->setCellValue('F' . $row, $value['id_card_number']);
            $sheet->setCellValue('G' . $row, $value['native_place']);
            $sheet->setCellValue('H' . $row, $value['work_units']);
            $sheet->setCellValue('I' . $row, $value['phone']);
            $sheet->setCellValue('J' . $row, $value['note']);
            $sheet->setCellValue('K' . $row, $value['checkin_date']);
            $sheet->setCellValue('L' . $row, $value['leave_date']);
            $row++;
        }

        // 设置导出文件名
        $fileName = 'tenant.xlsx';

        // 创建Xlsx文件写入器
        $writer = new Xlsx($spreadsheet);

        // 设置HTTP头信息
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        // 发送文件到浏览器下载
        $writer->save('php://output');

        // 结束脚本
        exit;
    }

    // 房间管理页面-入住-选择租客
    public function selectTenant()
    {
        $id = $this->request->param('id/d', 0);
        $number = NumberModel::find($id);
        $tenant = TenantModel::alias('a')
            ->leftjoin('HouseNumber b', 'a.house_property_id = b.house_property_id and a.house_number_id = b.id')
            ->join('HouseProperty c', 'a.house_property_id = c.id')
            ->where('a.house_property_id', $number->house_property_id)
            ->field("a.id, a.sex, a.phone, a.id_card_number, a.native_place, a.work_units, a.note, a.name as tenant_name, b.name as number_name, c.name as property_name")
            ->order(['checkout_mark', 'leave_date' => 'desc', 'a.checkin_date' => 'desc'])
            ->select();
        return $this->returnResult($tenant);
    }
}
