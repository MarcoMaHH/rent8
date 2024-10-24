<?php

namespace app\api\library;

use app\admin\model\HouseProperty as PropertyModel;
use app\api\library\Auth;

class Property
{
    /**
     * 获取房产id
     */
    public static function getProperty()
    {
        $preferredPropertyId = null;
        $allPropertyIds = [];

        $user = Auth::getInstance()->getLoginUser()['id'];
        if ($user) {
            $properties = PropertyModel::where('admin_user_id', $user)
                                       ->field('id,firstly')
                                       ->select()
                                       ->toArray();

            $allPropertyIds = array_map(function ($property) {
                return $property['id'];
            }, $properties);

            $preferredProperty = array_filter($properties, function ($property) {
                return $property['firstly'] === 'Y';
            });

            if (!empty($preferredProperty)) {
                $preferredPropertyId = reset($preferredProperty)['id'];
            }
        }

        return $preferredPropertyId ? [$preferredPropertyId] : $allPropertyIds;

    }
}
