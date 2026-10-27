<?php

namespace App\Support;

/**
 * Parses raw OCR text from a government-issued ID into structured fields.
 * Used by both ProcessKycOcr (job) and KycOcrController (controller).
 */
class IdOcrParser
{
    public static function parse(string $text): array
    {
        $lines = array_values(
            array_filter(array_map('trim', explode("\n", $text)), fn ($l) => $l !== '')
        );
        $flat = preg_replace('/\s+/', ' ', implode(' ', $lines));

        return [
            'full_name'     => self::extractName($lines, $flat),
            'doc_number'    => self::extractDocNumber($lines, $flat),
            'date_of_birth' => self::extractDate($flat, [
                'date of birth', 'birth date', 'dob', 'born',
                'tarehe ya kuzaliwa', 'tarehe',
            ]),
            'expiry_date'   => self::extractDate($flat, [
                'expiry', 'expiration', 'expires',
                'date of expiry', 'valid until', 'exp',
            ]),
            'doc_type'      => self::detectDocType($flat),
            'doc_country'   => self::detectCountry($flat),
            'gender'        => self::extractGender($flat),
            'nationality'   => self::detectNationality($flat),
        ];
    }

    // ── Name ─────────────────────────────────────────────────────────────────

    private static function extractName(array $lines, string $flat): ?string
    {
        $keywords = [
            'full names', 'full name', 'given names', 'given name',
            'surname', 'first name', 'last name', 'name', 'jina', 'names',
        ];

        foreach ($lines as $i => $line) {
            $lower = strtolower($line);
            foreach ($keywords as $kw) {
                if (!str_contains($lower, $kw)) continue;

                // Value after colon/slash on same line: "FULL NAMES: JANE WANJIKU"
                if (preg_match('/[:\\/]\s*(.{3,})$/', $line, $m)) {
                    return self::cleanName($m[1]);
                }
                // Rest of line after keyword: "NAME JANE WANJIKU"
                $after = trim(preg_replace('/^' . preg_quote($kw, '/') . '\s*/i', '', $line));
                if (strlen($after) > 2 && strtolower($after) !== $kw) {
                    return self::cleanName($after);
                }
                // Value on the next non-empty line
                if (!empty($lines[$i + 1]) && strlen($lines[$i + 1]) > 2) {
                    return self::cleanName($lines[$i + 1]);
                }
            }
        }

        // MRZ: SURNAME<<GIVENNAME (line of 20+ chars, only A-Z and <)
        foreach ($lines as $line) {
            if (strlen($line) >= 20
                && preg_match('/^[A-Z<]{20,}$/', $line)
                && str_contains($line, '<')
            ) {
                $parts   = explode('<<', $line, 2);
                $surname = trim(str_replace('<', ' ', $parts[0]));
                $given   = trim(str_replace('<', ' ', $parts[1] ?? ''));
                $name    = trim($given . ' ' . $surname);
                if (strlen($name) > 3) return self::cleanName($name);
            }
        }

        // Longest consecutive all-caps line — Kenyan national IDs print name in caps
        $best = '';
        foreach ($lines as $line) {
            if (preg_match('/^[A-Z][A-Z\s\'\-]{5,}$/', $line) && strlen($line) > strlen($best)) {
                $best = $line;
            }
        }
        return $best ? self::cleanName($best) : null;
    }

    private static function cleanName(string $raw): string
    {
        $name = trim(preg_replace('/\s{2,}/', ' ', preg_replace('/[^A-Za-z\s\'\-]/', '', $raw)));
        return ($name === strtoupper($name) && strlen($name) > 0)
            ? mb_convert_case($name, MB_CASE_TITLE)
            : $name;
    }

    // ── Document number ──────────────────────────────────────────────────────

    private static function extractDocNumber(array $lines, string $flat): ?string
    {
        // Labelled: "ID No: 12345678", "Document No: A1234567"
        if (preg_match(
            '/(?:id\.?\s*no|serial\s*no|document\s*no|id\s*number|doc\.?\s*no|nambari)\s*[:\-]?\s*([A-Z0-9]{5,15})/i',
            $flat, $m
        )) {
            return strtoupper(preg_replace('/[^A-Z0-9]/', '', $m[1]));
        }

        // Kenyan national ID — exactly 8 consecutive digits, standalone
        if (preg_match('/\b(\d{8})\b/', $flat, $m)) return $m[1];

        // Passport — uppercase letter + 7-8 digits
        if (preg_match('/\b([A-Z]\d{7,8})\b/', $flat, $m)) return $m[1];

        // MRZ line 1 — doc number at positions 5–13
        foreach ($lines as $line) {
            if (preg_match('/^[A-Z]{1,2}[A-Z0-9<]([A-Z0-9]{9})[0-9<]/', $line, $m)) {
                return str_replace('<', '', $m[1]);
            }
        }

        return null;
    }

    // ── Dates ────────────────────────────────────────────────────────────────

    private static function extractDate(string $flat, array $keywords): ?string
    {
        foreach ($keywords as $kw) {
            $pos = stripos($flat, $kw);
            if ($pos === false) continue;
            $window = substr($flat, $pos, 80);

            // DD/MM/YYYY  DD-MM-YYYY  DD.MM.YYYY
            if (preg_match('/(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})/', $window, $m)) {
                [$d, $mo, $y] = [(int)$m[1], (int)$m[2], (int)$m[3]];
                if ($mo > 12) [$d, $mo] = [$mo, $d];
                return sprintf('%04d-%02d-%02d', $y, $mo, $d);
            }
            // YYYY-MM-DD  YYYY/MM/DD
            if (preg_match('/(\d{4})[\/\-\.](\d{1,2})[\/\-\.](\d{1,2})/', $window, $m)) {
                return sprintf('%04d-%02d-%02d', (int)$m[1], (int)$m[2], (int)$m[3]);
            }
            // MRZ 6-digit YYMMDD
            if (preg_match('/\b(\d{2})(\d{2})(\d{2})\b/', $window, $m)) {
                $yy = (int)$m[1];
                $y  = $yy > 30 ? 1900 + $yy : 2000 + $yy;
                return sprintf('%04d-%02d-%02d', $y, (int)$m[2], (int)$m[3]);
            }
        }

        // Broad fallback — first plausible date anywhere
        if (preg_match('/(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})/', $flat, $m)) {
            [$d, $mo, $y] = [(int)$m[1], (int)$m[2], (int)$m[3]];
            if ($mo > 12) [$d, $mo] = [$mo, $d];
            return sprintf('%04d-%02d-%02d', $y, $mo, $d);
        }

        return null;
    }

    // ── Doc type ─────────────────────────────────────────────────────────────

    private static function detectDocType(string $flat): string
    {
        $l = strtolower($flat);
        if (str_contains($l, 'passport'))                                return 'passport';
        if (str_contains($l, 'alien'))                                   return 'alien_id';
        if (str_contains($l, 'driving') || str_contains($l, 'driver'))  return 'driving_licence';
        return 'national_id';
    }

    // ── Country ──────────────────────────────────────────────────────────────

    private static function detectCountry(string $flat): string
    {
        $l = strtolower($flat);
        if (str_contains($l, 'kenya')    || str_contains($l, 'jamhuri') || str_contains($l, 'kenyan')) return 'KE';
        if (str_contains($l, 'uganda')   || str_contains($l, 'ugandan'))   return 'UG';
        if (str_contains($l, 'tanzania') || str_contains($l, 'tanzanian')) return 'TZ';
        if (str_contains($l, 'rwanda')   || str_contains($l, 'rwandan'))   return 'RW';
        if (str_contains($l, 'ethiopia') || str_contains($l, 'ethiopian')) return 'ET';
        if (str_contains($l, 'somalia')  || str_contains($l, 'somali'))    return 'SO';

        // ISO-3 from MRZ
        if (preg_match('/\b(KEN|UGA|TZA|RWA|ETH|SOM)\b/', $flat, $m)) {
            return match ($m[1]) {
                'KEN' => 'KE', 'UGA' => 'UG', 'TZA' => 'TZ',
                'RWA' => 'RW', 'ETH' => 'ET', 'SOM' => 'SO',
            };
        }

        return 'KE'; // default for this market
    }

    private static function detectNationality(string $flat): string
    {
        if (preg_match('/nationality\s*[:\-]?\s*([A-Za-z]+)/i', $flat, $m)) {
            return match (true) {
                str_starts_with(strtolower($m[1]), 'ken') => 'KE',
                str_starts_with(strtolower($m[1]), 'uga') => 'UG',
                str_starts_with(strtolower($m[1]), 'tan') => 'TZ',
                str_starts_with(strtolower($m[1]), 'rwa') => 'RW',
                str_starts_with(strtolower($m[1]), 'eth') => 'ET',
                str_starts_with(strtolower($m[1]), 'som') => 'SO',
                default                                    => 'KE',
            };
        }
        return self::detectCountry($flat);
    }

    // ── Gender ───────────────────────────────────────────────────────────────

    private static function extractGender(string $flat): ?string
    {
        // Labelled: "Sex: M", "Gender: Female", "Jinsia: ME"
        if (preg_match('/(?:sex|gender|jinsia)\s*[:\-]?\s*(male|female|m|f)\b/i', $flat, $m)) {
            $v = strtolower($m[1]);
            return ($v === 'f' || $v === 'female') ? 'female' : 'male';
        }
        if (preg_match('/\bfemale\b/i', $flat)) return 'female';
        if (preg_match('/\bmale\b/i',   $flat)) return 'male';
        return null;
    }
}
