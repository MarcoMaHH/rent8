<?php

namespace app\admin\library;

class Date
{
    public static function getLease($checkin, $i = 1, $lease_type = 1)
    {
        $j = $i + $lease_type;
        $star = date('Y-m-d', strtotime("$checkin +$i month"));
        $end = date('Y-m-d', strtotime("$checkin +$j month - 1 day"));
        return array($star,$end);
    }
}
