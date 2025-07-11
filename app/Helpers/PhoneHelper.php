<?php

namespace App\Helpers;

class PhoneHelper
{
    /**
     * نرمال‌سازی شماره موبایل به فرمت استاندارد ایران
     */
    public static function normalize($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '0098')) {
            $phone = substr($phone, 4);
        } elseif (str_starts_with($phone, '+98')) {
            $phone = substr($phone, 3);
        } elseif (str_starts_with($phone, '98')) {
            $phone = substr($phone, 2);
        } elseif (str_starts_with($phone, '0')) {
            $phone = substr($phone, 1);
        }

        return '0' . $phone;
    }

    /**
     * بررسی معتبر بودن شماره موبایل ایران
     */
    public static function isValid($phone)
    {
        $phone = self::normalize($phone);
        return preg_match('/^09\d{9}$/', $phone);
    }
}
