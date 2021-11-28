<?php

namespace App\Traits;

trait DateTimeUtil
{
    public static function Interval2HumanStr($interval)
    {
        if (isset($interval)) {
            $str = '';
            if ($interval->m > 0)
                $str .= $interval->m . 'мес ';
            if ($interval->d > 0)
                $str .= $interval->d . 'дн ';
            if ($interval->h > 0)
                $str .= $interval->h . 'ч ';
            if ($interval->i > 0)
                $str .= $interval->i . 'м ';
            return $str;
        }
    }

    public static function sec2dhm($sec)
    {
        $sec = floor($sec);
        $dhms = '';
        $t = floor($sec / 86400);
        if ($t > 0)
            $dhms .= $t . 'дн ';
        $sec = $sec - $t * 86400;

        $t = floor($sec / 3600);
        if ($t > 0)
            $dhms .= $t . 'ч ';
        $sec = $sec - $t * 3600;
        $t = floor($sec / 60);
        if ($t > 0)
            $dhms .= $t . 'м ';

        return $dhms;
    }

}
