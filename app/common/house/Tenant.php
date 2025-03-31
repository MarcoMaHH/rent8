<?php

namespace app\common\house;

use app\admin\model\HouseTenant as TenantModel;
use app\admin\model\TenantPhoto as TenantPhotoModel;
use app\admin\validate\HouseTenant as TenantValidate;

class Tenant
{
    public static function save($id, $data)
    {
        if ($id) {
            if (!$tenant = TenantModel::find($id)) {
                return ['flag' => false, 'msg' => '修改失败，租客不存在'];
            }
            $tenant->save($data);
            return ['flag' => true, 'msg' => '修改成功'];
        }
        TenantModel::create($data);
        return ['flag' => true, 'msg' => '添加成功'];
    }

    public static function delete($id)
    {
        if (!$tenant = TenantModel::find($id)) {
            return ['flag' => false, 'msg' => '删除失败，租客不存在'];
        }
        $validate = new TenantValidate();
        if (!$validate->scene('delete')->check(['id' => $id])) {
            return ['flag' => false, 'msg' => '删除失败，' . $validate->getError()];
        }
        $photoRootPath = app()->getRootPath() . 'public';

        // 用于存储所有照片文件所在文件夹的路径
        $folders = [];

        // 删除租客证件图片
        TenantPhotoModel::where('tenant_id', '=', $id)->chunk(100, function ($photos) use ($photoRootPath, &$folders) {
            foreach ($photos as $photo) {
                $filePath = $photoRootPath . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, array_slice(explode('/', $photo['url']), 1));
                $folderPath = dirname($filePath); // 获取文件所在文件夹路径
                $folders[$folderPath] = true; // 记录文件夹路径
                if (file_exists($filePath)) {
                    try {
                        unlink($filePath);
                    } catch (\Exception $e) {
                        // 记录日志或处理异常
                        continue;
                    }
                }
            }
        });

        // 删除所有照片记录
        TenantPhotoModel::where('tenant_id', '=', $id)->delete();

        // 尝试删除所有空文件夹
        foreach (array_keys($folders) as $folderPath) {
            try {
                if (is_dir($folderPath) && count(scandir($folderPath)) === 2) {
                    rmdir($folderPath);
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        $tenant->delete();
        return ['flag' => true, 'msg' => '删除成功'];
    }
}
