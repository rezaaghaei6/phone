<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Validator;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumber;
use libphonenumber\NumberParseException;

class PhoneHelper
{
    /**
     * نرمال‌سازی شماره موبایل به فرمت استاندارد ایران
     * 
     * @param string $phone
     * @return string|null
     */
    public static function normalize($phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // حذف تمام کاراکترهای غیر عددی
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        try {
            $phoneUtil = PhoneNumberUtil::getInstance();
            
            // تبدیل فرمت‌های مختلف ایرانی
            if (str_starts_with($phone, '0098')) {
                $phone = '+98' . substr($phone, 4);
            } elseif (str_starts_with($phone, '98') && !str_starts_with($phone, '+98')) {
                $phone = '+98' . substr($phone, 2);
            } elseif (str_starts_with($phone, '0')) {
                $phone = '+98' . substr($phone, 1);
            } elseif (!str_starts_with($phone, '+')) {
                $phone = '+98' . $phone;
            }

            // تجزیه و تحلیل شماره تلفن
            $phoneNumber = $phoneUtil->parse($phone, 'IR');
            
            if ($phoneUtil->isValidNumber($phoneNumber)) {
                // بازگرداندن فرمت ایرانی
                return $phoneUtil->format($phoneNumber, \libphonenumber\PhoneNumberFormat::NATIONAL);
            }
            
        } catch (NumberParseException $e) {
            // در صورت خطا، تلاش برای فرمت ساده
            return static::fallbackNormalize($phone);
        }

        return null;
    }

    /**
     * فرمت نرمال‌سازی ساده در صورت عدم دسترسی به libphonenumber
     */
    private static function fallbackNormalize(string $phone): ?string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '0098')) {
            $phone = substr($phone, 4);
        } elseif (str_starts_with($phone, '98')) {
            $phone = substr($phone, 2);
        } elseif (str_starts_with($phone, '0')) {
            $phone = substr($phone, 1);
        }

        // بررسی طول و الگوی موبایل ایران
        if (strlen($phone) === 10 && preg_match('/^9[0-9]{9}$/', $phone)) {
            return '0' . $phone;
        }

        return null;
    }

    /**
     * متد جدید برای سازگاری با کدهای موجود
     */
    public static function normalizePhone($phone): ?string
    {
        return self::normalize($phone);
    }

    /**
     * بررسی معتبر بودن شماره موبایل ایران
     */
    public static function isValid($phone): bool
    {
        if (empty($phone)) {
            return false;
        }

        $normalizedPhone = self::normalize($phone);
        
        if (!$normalizedPhone) {
            return false;
        }

        // بررسی الگوی دقیق موبایل ایران
        return preg_match('/^09[0-9]{9}$/', $normalizedPhone) === 1;
    }

    /**
     * بررسی معتبر بودن کد کشور ایران
     */
    public static function isIranianNumber($phone): bool
    {
        try {
            $phoneUtil = PhoneNumberUtil::getInstance();
            $phoneNumber = $phoneUtil->parse($phone, null);
            return $phoneNumber->getCountryCode() === 98;
        } catch (NumberParseException $e) {
            return false;
        }
    }

    /**
     * تبدیل شماره به فرمت بین‌المللی
     */
    public static function toInternational($phone): ?string
    {
        $normalized = self::normalize($phone);
        
        if (!$normalized) {
            return null;
        }

        return '+98' . substr($normalized, 1);
    }

    /**
     * دریافت کد شبکه موبایل
     */
    public static function getCarrier($phone): ?string
    {
        $normalized = self::normalize($phone);
        
        if (!$normalized) {
            return null;
        }

        $prefix = substr($normalized, 2, 3); // 091X, 099X etc.

        $carriers = [
            '910' => 'همراه اول',
            '911' => 'همراه اول',
            '912' => 'همراه اول',
            '913' => 'همراه اول',
            '914' => 'همراه اول',
            '915' => 'همراه اول',
            '916' => 'همراه اول',
            '917' => 'همراه اول',
            '918' => 'همراه اول',
            '919' => 'همراه اول',
            '990' => 'ایرانسل',
            '991' => 'ایرانسل',
            '992' => 'ایرانسل',
            '993' => 'ایرانسل',
            '994' => 'ایرانسل',
            '995' => 'ایرانسل',
            '996' => 'ایرانسل',
            '997' => 'ایرانسل',
            '998' => 'ایرانسل',
            '999' => 'ایرانسل',
            '934' => 'رایتل',
            '935' => 'رایتل',
            '936' => 'رایتل',
            '937' => 'رایتل',
            '938' => 'رایتل',
            '939' => 'رایتل',
        ];

        return $carriers[$prefix] ?? 'نامشخص';
    }

    /**
     * اعتبارسنجی قوانین امنیتی
     */
    public static function validateSecurity($phone): array
    {
        $errors = [];
        
        if (!self::isValid($phone)) {
            $errors[] = 'شماره موبایل معتبر نیست';
        }

        // بررسی شماره‌های مشکوک
        $suspiciousPatterns = [
            '/^09(0|1){9}$/',  // شماره‌های تکراری
            '/^09(123456789|987654321)$/', // شماره‌های متوالی
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $phone)) {
                $errors[] = 'شماره موبایل مشکوک است';
                break;
            }
        }

        return $errors;
    }
}