<?php

namespace app\admin\library;

class Date
{
    public static function getLease($calculate, $cycle = 1, $cycle_type = 'month', $lease = 1)
    {
        $j = $cycle + $lease;
        $star = date('Y-m-d', strtotime("$calculate +$lease $cycle_type"));
        $end = date('Y-m-d', strtotime("$calculate +$j $cycle_type - 1 day"));
        return array($star, $end);
    }

    public static function formatDays($idleDays)
    {
        if ($idleDays > 365) {
            $years = floor($idleDays / 365);
            $remainder = $idleDays % 365;
            $months = floor($remainder / 30);
            $days = $remainder % 30;

            $output = $years . '年';
            if ($months > 0 || $days > 0) {
                $output .= $months . '个月';
                if ($days > 0) {
                    $output .= $days . '日';
                }
            }
            return $output;
        } elseif ($idleDays > 30) {
            $months = floor($idleDays / 30);
            $days = $idleDays % 30;

            $output = $months . '个月';
            if ($days > 0) {
                $output .= $days . '日';
            }
            return $output;
        } else {
            return $idleDays . '日';
        }
    }
}
