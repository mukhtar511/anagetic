<?php

namespace App\Services;

/**
 * Blocks off-platform dealing in chat — SPEC §4.10 / D-10.
 * Detects phone numbers, links, and external-payment keywords; the message is
 * flagged, a warning is returned, and the admin is notified (a report is raised).
 */
class ExternalDealingFilter
{
    private const SYSTEM_WARNING = '⚠ ممنوع تبادل الأرقام أو روابط الدفع خارج أناقتك — التعامل كله عبر المنصة، ومبلغك محمي بالحجز الآمن';

    /** External-payment / contact keywords (Arabic + Latin). */
    private array $keywords = [
        'واتساب', 'واتس', 'whatsapp', 'stc pay', 'stcpay', 'اس تي سي', 'تحويل بنكي',
        'حسابي البنكي', 'ايبان', 'iban', 'باي بال', 'paypal', 'تليجرام', 'telegram',
        'رقمي', 'اتصلي علي', 'حولي على',
    ];

    public function inspect(string $body): array
    {
        $flagged = $this->hasPhone($body) || $this->hasLink($body) || $this->hasKeyword($body);

        return [
            'flagged' => $flagged,
            'warning' => $flagged ? self::SYSTEM_WARNING : null,
        ];
    }

    private function hasPhone(string $body): bool
    {
        // 9+ consecutive digits (allowing spaces/dashes) → likely a phone/account.
        $normalized = preg_replace('/[\s\-]/u', '', $this->toLatinDigits($body));

        return (bool) preg_match('/\d{9,}/', (string) $normalized);
    }

    private function hasLink(string $body): bool
    {
        return (bool) preg_match('#(https?://|www\.|\b\w+\.(com|net|sa|me|link)\b)#iu', $body);
    }

    private function hasKeyword(string $body): bool
    {
        $lower = mb_strtolower($body);
        foreach ($this->keywords as $kw) {
            if (mb_strpos($lower, mb_strtolower($kw)) !== false) {
                return true;
            }
        }

        return false;
    }

    private function toLatinDigits(string $s): string
    {
        return strtr($s, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
    }
}
