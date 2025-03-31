<?php

namespace app\common\house;

use app\admin\model\HouseProperty as PropertyModel;
use app\admin\model\PayPhoto as PayPhotoModel;
use app\admin\validate\HouseProperty as PropertyValidate;

class Property
{
    public static function save($id, $data, $admin_user_id)
    {
        if ($id) {
            if (!$property = PropertyModel::find($id)) {
                return ['flag' => false, 'msg' => '房产不存在'];
            }
            if (PropertyModel::where('name', $data['name'])
                ->where('id', '<>', $id)
                ->where('admin_user_id', $admin_user_id)
                ->find()
            ) {
                return ['flag' => false, 'msg' => '房间名已存在'];
            }
            $property->save($data);
            return ['flag' => true, 'msg' => '修改成功'];
        }
        if (PropertyModel::where('name', $data['name'])->where('admin_user_id', $admin_user_id)->find()) {
            return ['flag' => false, 'msg' => '房间名已存在'];
        }
        $data['admin_user_id'] = $admin_user_id;
        $data['firstly'] = 'Y';
        PropertyModel::where('admin_user_id', $admin_user_id)->update(['firstly' => 'N']);
        PropertyModel::create($data);
        return ['flag' => true, 'msg' => '添加成功'];
    }

    public static function sort($id, $admin_user_id)
    {
        $data = PropertyModel::where('admin_user_id', $admin_user_id)->select()->toArray();
        $result = [];
        foreach ($data as $value) {
            $temp = array(
                'id' => $value['id']
            );
            if ($value['id'] === $id) {
                $temp['firstly'] = 'Y';
            } else {
                $temp['firstly'] = 'N';
            }
            \array_push($result, $temp);
        }
        $property = new PropertyModel();
        $property->saveAll($result);
        return ['flag' => true, 'msg' => '切换成功'];
    }

    public static function delete($id)
    {
        if (!$property = PropertyModel::find($id)) {
            return ['flag' => false, 'msg' => '删除失败，房产不存在'];
        }
        $validate = new PropertyValidate();
        if (!$validate->scene('delete')->check(['id' => $id])) {
            return ['flag' => false, 'msg' => '删除失败，' . $validate->getError()];
        }
        $photoRootPath = app()->getRootPath() . 'public';
        $folders = [];
        PayPhotoModel::where('house_property_id', '=', $id)->chunk(100, function ($photos) use ($photoRootPath, &$folders) {
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
        PayPhotoModel::where('house_property_id', '=', $id)->delete();
        $property->delete();
        return ['flag' => true, 'msg' => '删除成功'];
    }
}
