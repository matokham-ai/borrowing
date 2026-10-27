<?php

namespace App\Support;

use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Parses the raw text of a Safaricom M-Pesa PDF statement into structured
 * transactions and computes monthly financial aggregates.
 *
 * M-Pesa statement PDF format (Safaricom Kenya, 2018–present):
 *
 *   Each transaction line looks like:
 *   RXXXXXXXX  DD/MM/YYYY HH:MM:SS  Description                  Paid Out  Paid In  Balance
 *
 *   Example lines (as extracted by pdfparser):
 *   RBK4X12345  01/03/2026 09:14:32  Pay Bill Online - KRA         0.00   5,000.00   12,345.67
 *   RCAB123456  02/03/2026 14:22:10  Sent to JOHN DOE 0712345678  200.00      0.00   12,145.67
 *
 * The parser:
 *  1. Extracts the full text from the PDF
 *  2. Parses each transaction row via regex
 *  3. Classifies each transaction (income/expense/transfer/loan)
 *  4. Aggregates by calendar month → monthly averages
 *  5. Computes financial scores and ratios
 */
class MpesaStatementParser
{
    // ── Transaction classification keywords ───────────────────────────────

    private const SALARY_KEYWORDS = [
        'salary', 'payroll', 'wage', 'stipend', 'employer',
    ];

    private const BUSINESS_KEYWORDS = [
        'till', 'paybill', 'pay bill', 'lipa na mpesa', 'merchant',
        'buy goods', 'business payment', 'reversal',
    ];

    private const LOAN_DISBURSEMENT_KEYWORDS = [
        'fuliza', 'mshwari loan', 'kcb mpesa loan', 'loan disbursement',
        'credit party', 'faulu', 'tala', 'branch loan', 'zenka',
    ];

    private const LOAN_REPAYMENT_KEYWORDS = [
        'fuliza repayment', 'loan repayment', 'mshwari repayment',
        'kcb mpesa repayment', 'loan recovery',
    ];

    private const UTILITY_KEYWORDS = [
        'kplc', 'kenya power', 'nairobi water', 'zuku', 'dstv',
        'gotv', 'safaricom postpaid', 'airtel', 'telkom', 'nwsc',
    ];

    private const SAVINGS_KEYWORDS = [
        'mshwari lock', 'mshwari savings', 'kcb mpesa savings',
        'mali', 'sacco',
    ];

    // ─────────────────────────────────────────────────────────────────────
    // Public API
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Parse a PDF file path and return the full analysis result.
     *
     * @throws \Exception if the PDF cannot be read or no transactions found
     */
    public static function parseFile(string $filePath): array
    {
        Log::debug('M-Pesa statement parsing started', [
            'file' => $filePath,
        ]);

        $parser = new Parser();
        $pdf    = $parser->parseFile($filePath);
        $text   = trim($pdf->getText());

        Log::debug('M-Pesa native PDF extraction', [
            'file'      => $filePath,
            'text_size' => strlen($text),
            'preview'   => substr($text, 0, 2000),
        ]);

        try {
            return self::parseText($text);

        } catch (\RuntimeException $exception) {

            Log::warning('Native M-Pesa parsing failed; attempting OCR', [
                'file'  => $filePath,
                'error' => $exception->getMessage(),
            ]);

            $ocrText = self::extractTextWithOcr($filePath);

            if ($ocrText !== '') {

                Log::debug('Attempting M-Pesa parsing from OCR text', [
                    'text_size' => strlen($ocrText),
                ]);

                return self::parseText($ocrText);
            }

            throw $exception;
        }
    }

    private static function extractTextWithOcr(string $filePath): string
    {
        try {
            if (!is_file($filePath)) {
                throw new \RuntimeException(
                    'M-Pesa statement file does not exist: ' . $filePath
                );
            }

            $fileSize = filesize($filePath);

            Log::debug('M-Pesa OCR starting', [
                'file'      => $filePath,
                'file_size' => $fileSize,
                'mime'      => mime_content_type($filePath),
            ]);

            $apiKey = config('services.ocrspace.api_key');

            if (empty($apiKey)) {
                throw new \RuntimeException(
                    'OCR.space API key is not configured.'
                );
            }

            $endpoint = config(
                'services.ocrspace.endpoint',
                'https://api.ocr.space/parse/image'
            );

            $response = Http::timeout(120)
                ->withHeaders([
                    'apikey' => $apiKey,
                ])
                ->attach(
                    'file',
                    file_get_contents($filePath),
                    basename($filePath)
                )
                ->post($endpoint, [
                    'language'          => 'eng',
                    'isOverlayRequired' => 'false',
                    'detectOrientation' => 'true',
                    'scale'             => 'true',
                    'isTable'           => 'true',
                    'OCREngine'         => '2',
                ]);

            Log::debug('M-Pesa OCR HTTP response', [
                'status'     => $response->status(),
                'successful' => $response->successful(),
            ]);

            if ($response->failed()) {
                Log::error('M-Pesa OCR HTTP failure', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return '';
            }

            $json = $response->json();

            Log::debug('M-Pesa OCR.space response', [
                'response' => $json,
            ]);

            if (!empty($json['IsErroredOnProcessing'])) {
                Log::error('M-Pesa OCR processing error', [
                    'error_message' => $json['ErrorMessage'] ?? null,
                    'error_details' => $json['ErrorDetails'] ?? null,
                ]);

                return '';
            }

            $rawText = collect($json['ParsedResults'] ?? [])
                ->pluck('ParsedText')
                ->filter()
                ->implode("\n");

            $rawText = trim($rawText);

            Log::debug('M-Pesa OCR raw text', [
                'length' => strlen($rawText),
                'text'   => $rawText,
            ]);

            return $rawText;

        } catch (\Throwable $e) {

            Log::error('M-Pesa OCR exception', [
                'file'  => $filePath,
                'error' => $e->getMessage(),
                'type'  => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            return '';
        }
    }

    /**
     * Parse raw PDF text (useful for testing without a real file).
     */
    public static function parseText(string $text): array
    {
        $transactions = self::extractTransactions($text);

        if (empty($transactions)) {
            throw new \RuntimeException(
                'No M-Pesa transactions found in the document. '
                . 'Please ensure this is a valid Safaricom M-Pesa statement PDF.'
            );
        }

        $classified = self::classifyTransactions($transactions);
        $monthly    = self::aggregateByMonth($classified);
        $profile    = self::computeProfile($classified, $monthly);
        $period     = self::detectPeriod($classified);

        return [
            'transactions'   => $classified,      // all parsed & classified rows
            'monthly'        => $monthly,          // per-month aggregates
            'profile'        => $profile,          // averaged financial profile
            'period_start'   => $period['start'],  // YYYY-MM-DD
            'period_end'     => $period['end'],     // YYYY-MM-DD
            'transaction_count' => count($classified),
            'raw_text'       => $text,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Step 1 — Extract raw transaction rows from PDF text
    // ─────────────────────────────────────────────────────────────────────

    private static function extractTransactions(string $text): array
    {
        $transactions = [];

        // Normalise line endings and clean up PDF artefacts
        $text = preg_replace('/\r\n?/', "\n", $text);

        // Strip "No Records Found" sections so empty account-type blocks don't
        // confuse later parsers. Also strip common header/footer noise lines.
        $text = preg_replace(
            '/^(Account Type\s*[-–]\s*.+|No Records Found\.?|Receipt No\.?|Completion.*|Details.*|Transaction.*Type|Paid In.*Balance|LOCALE.*|HOP)\s*$/mi',
            '',
            $text
        );
        $text = preg_replace('/\n{3,}/', "\n\n", $text); // collapse blank lines

        $lines = explode("\n", $text);

        // ── Primary regex: all columns on one line ─────────────────────────
        // M-Pesa transaction regex — matches the canonical statement layout:
        // TxCode  DD/MM/YYYY HH:MM:SS  Description  PaidOut  PaidIn  Balance
        //
        // Safaricom uses 10-character receipt codes.
        // The receipt prefix is typically 1–3 uppercase letters; however some
        // older and mini-statements start with a digit, so we broaden to
        // [A-Z0-9]{1,3} for the prefix.
        $txPattern = '/^([A-Z]{1,3}[A-Z0-9]{7,12})\s+' .  // receipt code
                     '(\d{1,2}\/\d{1,2}\/\d{4})\s+' .          // date DD/MM/YYYY
                     '(\d{2}:\d{2}:\d{2})\s+' .                  // time HH:MM:SS
                     '(.+?)\s+' .                                 // description (lazy)
                     '([\d,]+\.?\d*)\s+' .                        // paid out
                     '([\d,]+\.?\d*)\s+' .                        // paid in
                     '([\d,]+\.?\d*)\s*$/';                       // balance

        foreach ($lines as $line) {
            $line = trim($line);
            if (strlen($line) < 30) continue;

            if (preg_match($txPattern, $line, $m)) {
                $paidOut = self::parseCents($m[5]);
                $paidIn  = self::parseCents($m[6]);
                $balance = self::parseCents($m[7]);

                // Skip rows where both in and out are zero (header artefacts)
                if ($paidOut === 0 && $paidIn === 0) continue;

                $transactions[] = [
                    'receipt_no'    => $m[1],
                    'date'          => self::parseDate($m[2]),
                    'time'          => $m[3],
                    'description'   => trim($m[4]),
                    'paid_out_cents'=> $paidOut,
                    'paid_in_cents' => $paidIn,
                    // Signed amount: positive = money in, negative = money out
                    'amount_cents'  => $paidIn > 0 ? $paidIn : -$paidOut,
                    'balance_cents' => $balance,
                ];
            }
        }

        // If strict regex matched nothing, try a looser line-based approach
        if (empty($transactions)) {
            $transactions = self::extractTransactionsLoose($lines);
        }

        // Some Safaricom PDFs extract table cells on separate lines. Rebuild
        // those rows from receipt/date anchors before declaring the document invalid.
        if (empty($transactions)) {
            $transactions = self::extractTransactionsFromBlocks($text);
        }

        // Try to stitch multi-line rows by looking for receipt codes followed
        // by a date on the next line(s).
        if (empty($transactions)) {
            $transactions = self::extractTransactionsMultiLine($lines);
        }

        // Last resort: OCR-mode parser for heavily corrupted output from
        // Safaricom merchant PDFs scanned via OCR.space. Anchors on receipt
        // codes and harvests amounts from surrounding token windows.
        if (empty($transactions)) {
            $transactions = self::extractTransactionsFromOcr($text);
        }

        return $transactions;
    }

    /**
     * Parse rows whose PDF text extraction has inserted line breaks between
     * the receipt, date, description and amount columns.
     */
    private static function extractTransactionsFromBlocks(string $text): array
    {
        $transactions = [];

        // Collapse all whitespace to single spaces so column splits disappear
        $normalised = preg_replace('/\s+/', ' ', trim($text));

        if (!$normalised) {
            return $transactions;
        }

        // Find every position where a receipt code+date or bare date appears.
        // Receipt pattern broadened: optional prefix of 1-3 uppercase or digits.
        preg_match_all(
            '/(?=(?:(?:\b[A-Z0-9]{1,3}[A-Z0-9]{7,12})\s+)?\d{1,2}[\/-]\d{1,2}[\/-]\d{4}\b)/',
            $normalised,
            $anchors,
            PREG_OFFSET_CAPTURE
        );

        foreach ($anchors[0] as $index => $anchor) {
            $start = $anchor[1];
            $end   = $anchors[0][$index + 1][1] ?? strlen($normalised);
            $block = trim(substr($normalised, $start, $end - $start));

            // Match: [receipt_code] date [time] description amounts…
            // Amounts are one to three numeric tokens at the tail of the block.
            // We accept "-" and "—" as zero-value placeholders.
            if (!preg_match(
                '/^(?:([A-Z0-9]{1,3}[A-Z0-9]{7,12})\s+)?'
                . '(\d{1,2}[\/-]\d{1,2}[\/-]\d{4})(?:\s+(\d{1,2}:\d{2}(?::\d{2})?))?\s+'
                . '(.+?)\s+'
                . '((?:[\d,]+\.?\d*|-|—)\s+(?:[\d,]+\.?\d*|-|—)\s+(?:[\d,]+\.?\d*|-|—)'  // 3 amounts
                . '|(?:[\d,]+\.?\d*|-|—)\s+(?:[\d,]+\.?\d*|-|—)'                          // 2 amounts
                . '|(?:[\d,]+\.?\d*))$/u',                                                  // 1 amount
                $block,
                $match
            )) {
                continue;
            }

            $amountStr  = trim($match[5]);
            $amounts    = preg_split('/\s+/', $amountStr);
            $parsedAmounts = array_map(
                fn (string $amount): int => in_array($amount, ['-', '—'], true) ? 0 : self::parseCents($amount),
                $amounts
            );

            $description = trim($match[4]);

            if (count($parsedAmounts) === 3) {
                $paidOut = $parsedAmounts[0];
                $paidIn  = $parsedAmounts[1];
                $balance = $parsedAmounts[2];
            } elseif (count($parsedAmounts) === 2) {
                // Two amounts — decide which is in/out using description keywords
                $isIncoming = (bool) preg_match('/\b(received|deposit|salary|from|income|reversal)\b/i', $description);
                $paidOut    = $isIncoming ? 0 : $parsedAmounts[0];
                $paidIn     = $isIncoming ? $parsedAmounts[0] : 0;
                $balance    = $parsedAmounts[1];
            } else {
                // Only balance available — skip (can't determine direction)
                continue;
            }

            if ($paidOut === 0 && $paidIn === 0) {
                continue;
            }

            $transactions[] = [
                'receipt_no'     => $match[1] ?: null,
                'date'           => self::parseDate($match[2]),
                'time'           => $match[3] ? (strlen($match[3]) === 5 ? $match[3] . ':00' : $match[3]) : '00:00:00',
                'description'    => $description,
                'paid_out_cents' => $paidOut,
                'paid_in_cents'  => $paidIn,
                'amount_cents'   => $paidIn > 0 ? $paidIn : -$paidOut,
                'balance_cents'  => $balance,
            ];
        }

        return $transactions;
    }

    /**
     * Stitches multi-line transaction rows.
     *
     * Some Safaricom PDF variants (especially encrypted/compressed ones) cause
     * smalot/pdfparser to place each table cell on its own line:
     *
     *   RBK4X12345
     *   01/03/2026
     *   09:14:32
     *   Pay Bill Online - KRA
     *   0.00
     *   5,000.00
     *   12,345.67
     *
     * This method scans for lines that look like receipt codes, then gathers
     * the next several non-blank lines and attempts to assemble a transaction.
     */
    private static function extractTransactionsMultiLine(array $lines): array
    {
        $transactions  = [];
        $receiptPattern = '/^([A-Z]{1,3}[A-Z0-9]{7,12})$/';
        $datePattern    = '/^(\d{1,2}[\/-]\d{1,2}[\/-]\d{4})$/';
        $timePattern    = '/^(\d{2}:\d{2}(?::\d{2})?)$/';
        $amountPattern  = '/^([\d,]+\.?\d*)$|^(-|—)$/';
        $total          = count($lines);

        $i = 0;
        while ($i < $total) {
            $line = trim($lines[$i]);

            // Look for a standalone receipt code OR a line starting with one
            $receiptCode = null;
            if (preg_match($receiptPattern, $line, $rm)) {
                $receiptCode = $rm[1];
                $i++;
            } elseif (preg_match('/^([A-Z]{1,3}[A-Z0-9]{7,12})\s+(\d{1,2}[\/-]\d{1,2}[\/-]\d{4})/', $line, $rm)) {
                // receipt + date on the same line — partial merge
                $receiptCode = $rm[1];
                // Re-process this line as subsequent tokens
            } else {
                $i++;
                continue;
            }

            // Gather up to 8 non-blank lines following the receipt code
            $window = [];
            $j = $i;
            while ($j < $total && count($window) < 8) {
                $wl = trim($lines[$j]);
                if ($wl !== '') $window[] = $wl;
                $j++;
            }

            if (count($window) < 3) { $i++; continue; }

            // Expect: date, [time], description..., paidOut, paidIn, balance
            $wi = 0;
            if (!preg_match('/^(\d{1,2}[\/-]\d{1,2}[\/-]\d{4})$/', $window[$wi], $dm)) { $i++; continue; }
            $date = self::parseDate($dm[1]);
            $wi++;

            $time = '00:00:00';
            if (isset($window[$wi]) && preg_match('/^(\d{2}:\d{2}(?::\d{2})?)$/', $window[$wi], $tm)) {
                $time = strlen($tm[1]) === 5 ? $tm[1] . ':00' : $tm[1];
                $wi++;
            }

            // Collect description lines until we hit amount-looking lines
            $descParts  = [];
            $amountParts = [];
            while ($wi < count($window)) {
                $wl = $window[$wi];
                if (preg_match('/^[\d,]+\.?\d*$/', $wl) || in_array($wl, ['-', '—'])) {
                    $amountParts[] = $wl;
                } else {
                    if (!empty($amountParts)) break; // description after amounts — abort
                    $descParts[] = $wl;
                }
                $wi++;
            }

            if (count($amountParts) < 2) { $i++; continue; }

            $description = implode(' ', $descParts);
            $parsed = array_map(
                fn ($v) => in_array($v, ['-', '—'], true) ? 0 : self::parseCents($v),
                $amountParts
            );

            if (count($parsed) >= 3) {
                $paidOut = $parsed[0];
                $paidIn  = $parsed[1];
                $balance = $parsed[2];
            } else {
                $isIncoming = (bool) preg_match('/\b(received|deposit|salary|from|income|reversal)\b/i', $description);
                $paidOut    = $isIncoming ? 0 : $parsed[0];
                $paidIn     = $isIncoming ? $parsed[0] : 0;
                $balance    = $parsed[1];
            }

            if ($paidOut === 0 && $paidIn === 0) { $i++; continue; }

            $transactions[] = [
                'receipt_no'     => $receiptCode,
                'date'           => $date,
                'time'           => $time,
                'description'    => $description,
                'paid_out_cents' => $paidOut,
                'paid_in_cents'  => $paidIn,
                'amount_cents'   => $paidIn > 0 ? $paidIn : -$paidOut,
                'balance_cents'  => $balance,
            ];

            // Advance past the lines we consumed
            $i = $j;
        }

        return $transactions;
    }

    /**
     * Looser fallback parser — handles PDFs where columns are separated by
     * varying whitespace or the receipt code is on its own line.
     */
    private static function extractTransactionsLoose(array $lines): array
    {
        $transactions = [];
        $datePattern  = '/\b(\d{1,2})\/(\d{1,2})\/(\d{4})\b/';
        $amountPattern = '/([\d,]+\.?\d+)/';  // allow amounts without decimal part

        foreach ($lines as $line) {
            $line = trim($line);
            if (!preg_match($datePattern, $line, $dm)) continue;
            if (!preg_match_all($amountPattern, $line, $am)) continue;

            $amounts = $am[1];
            if (count($amounts) < 2) continue;

            // Last amount is the balance; second-to-last could be in or out
            $balance = self::parseCents(end($amounts));
            $second  = self::parseCents($amounts[count($amounts) - 2]);
            $first   = count($amounts) >= 3 ? self::parseCents($amounts[count($amounts) - 3]) : 0;

            // Heuristic: if there are 3 amounts (out, in, balance), use them
            $paidOut = $first;
            $paidIn  = $second;

            if ($paidOut === 0 && $paidIn === 0) continue;

            // Extract receipt code
            preg_match('/\b([A-Z]{1,3}[A-Z0-9]{7,12})\b/', $line, $rc);

            $transactions[] = [
                'receipt_no'     => $rc[1] ?? null,
                'date'           => sprintf('%04d-%02d-%02d', (int)$dm[3], (int)$dm[2], (int)$dm[1]),
                'time'           => '00:00:00',
                'description'    => preg_replace('/[A-Z0-9]{8,}\s+\d{1,2}\/\d{1,2}\/\d{4}/', '', $line),
                'paid_out_cents' => $paidOut,
                'paid_in_cents'  => $paidIn,
                'amount_cents'   => $paidIn > 0 ? $paidIn : -$paidOut,
                'balance_cents'  => $balance,
            ];
        }

        return $transactions;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Step 2 — Classify each transaction
    // ─────────────────────────────────────────────────────────────────────

    private static function classifyTransactions(array $transactions): array
    {
        return array_map(function (array $tx) {
            $desc   = strtolower($tx['description'] ?? '');
            $isIn   = $tx['amount_cents'] > 0;
            $isOut  = $tx['amount_cents'] < 0;
            $abs    = abs($tx['amount_cents']);

            // ── Classification logic ──────────────────────────────────
            $classification = 'other';
            $isIncome       = false;
            $isExpense      = false;
            $isTransfer     = false;
            $isLoanRelated  = false;
            $isRepayment    = false;

            if ($isIn) {
                if (self::matchesKeywords($desc, self::SALARY_KEYWORDS)) {
                    $classification = 'salary_income';
                    $isIncome       = true;
                } elseif (self::matchesKeywords($desc, self::LOAN_DISBURSEMENT_KEYWORDS)) {
                    $classification = 'loan_disbursement';
                    $isLoanRelated  = true;
                } elseif (self::matchesKeywords($desc, self::BUSINESS_KEYWORDS)) {
                    $classification = 'business_income';
                    $isIncome       = true;
                } elseif (str_contains($desc, 'received') || str_contains($desc, 'from')) {
                    // Person-to-person receive
                    $classification = 'p2p_received';
                    $isIncome       = true; // treat P2P as income (remittances)
                } else {
                    $classification = 'other_income';
                    $isIncome       = true;
                }
            } elseif ($isOut) {
                if (self::matchesKeywords($desc, self::LOAN_REPAYMENT_KEYWORDS)) {
                    $classification = 'loan_repayment';
                    $isLoanRelated  = true;
                    $isRepayment    = true;
                } elseif (self::matchesKeywords($desc, self::SAVINGS_KEYWORDS)) {
                    $classification = 'savings_transfer';
                    $isTransfer     = true;
                } elseif (self::matchesKeywords($desc, self::UTILITY_KEYWORDS)) {
                    $classification = 'utility_payment';
                    $isExpense      = true;
                } elseif (self::matchesKeywords($desc, self::BUSINESS_KEYWORDS)) {
                    $classification = 'business_payment';
                    $isExpense      = true;
                } elseif (str_contains($desc, 'sent to') || str_contains($desc, 'transfer to')) {
                    $classification = 'p2p_sent';
                    $isTransfer     = true;
                } elseif (str_contains($desc, 'withdraw') || str_contains($desc, 'agent')) {
                    $classification = 'cash_withdrawal';
                    $isExpense      = true;
                } elseif (str_contains($desc, 'airtime') || str_contains($desc, 'bundles')) {
                    $classification = 'airtime_data';
                    $isExpense      = true;
                } else {
                    $classification = 'other_expense';
                    $isExpense      = true;
                }
            }

            return array_merge($tx, [
                'classification' => $classification,
                'is_income'      => $isIncome,
                'is_expense'     => $isExpense,
                'is_transfer'    => $isTransfer,
                'is_loan_related'=> $isLoanRelated,
                'is_repayment'   => $isRepayment,
            ]);
        }, $transactions);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Step 3 — Aggregate by calendar month
    // ─────────────────────────────────────────────────────────────────────

    private static function aggregateByMonth(array $transactions): array
    {
        $months = [];

        foreach ($transactions as $tx) {
            $month = substr($tx['date'], 0, 7); // YYYY-MM

            if (!isset($months[$month])) {
                $months[$month] = [
                    'month'              => $month,
                    'income_cents'       => 0,
                    'expense_cents'      => 0,
                    'salary_cents'       => 0,
                    'business_income_cents' => 0,
                    'other_income_cents' => 0,
                    'debt_service_cents' => 0,
                    'transfer_out_cents' => 0,
                    'transaction_count'  => 0,
                ];
            }

            $m = &$months[$month];
            $m['transaction_count']++;

            if ($tx['is_income']) {
                $m['income_cents'] += $tx['amount_cents'];
                if ($tx['classification'] === 'salary_income') {
                    $m['salary_cents'] += $tx['amount_cents'];
                } elseif ($tx['classification'] === 'business_income') {
                    $m['business_income_cents'] += $tx['amount_cents'];
                } else {
                    $m['other_income_cents'] += $tx['amount_cents'];
                }
            }

            if ($tx['is_expense']) {
                $m['expense_cents'] += abs($tx['amount_cents']);
            }

            if ($tx['is_repayment']) {
                $m['debt_service_cents'] += abs($tx['amount_cents']);
            }

            if ($tx['is_transfer'] && $tx['amount_cents'] < 0) {
                $m['transfer_out_cents'] += abs($tx['amount_cents']);
            }
        }

        ksort($months);
        return array_values($months);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Step 4 — Compute the financial profile
    // ─────────────────────────────────────────────────────────────────────

    private static function computeProfile(array $transactions, array $monthly): array
    {
        $n = count($monthly);
        if ($n === 0) {
            return self::emptyProfile();
        }

        // Monthly averages
        $avgIncome       = (int) round(array_sum(array_column($monthly, 'income_cents')) / $n);
        $avgExpense      = (int) round(array_sum(array_column($monthly, 'expense_cents')) / $n);
        $avgSalary       = (int) round(array_sum(array_column($monthly, 'salary_cents')) / $n);
        $avgBusiness     = (int) round(array_sum(array_column($monthly, 'business_income_cents')) / $n);
        $avgOtherIncome  = (int) round(array_sum(array_column($monthly, 'other_income_cents')) / $n);
        $avgDebtService  = (int) round(array_sum(array_column($monthly, 'debt_service_cents')) / $n);
        $avgNetCashflow  = $avgIncome - $avgExpense;
        $avgDiscretionary = max(0, $avgNetCashflow - $avgDebtService);

        // Volatility (coefficient of variation) for income and expense
        $incomeVol  = self::coefficientOfVariation(array_column($monthly, 'income_cents'));
        $expenseVol = self::coefficientOfVariation(array_column($monthly, 'expense_cents'));

        // Debt service ratio
        $debtServiceRatio = $avgIncome > 0
            ? round($avgDebtService / $avgIncome, 6)
            : 0.0;

        // Scores (0–1 scale)
        // Income stability: lower volatility = better (1 - bounded_vol)
        $incomeStabilityScore = round(max(0, 1 - min($incomeVol, 1.0)), 4);

        // Cashflow score: based on net cashflow as % of income
        $cashflowRatio = $avgIncome > 0
            ? max(0, min(1, $avgNetCashflow / $avgIncome))
            : 0.0;
        $cashflowScore = round($cashflowRatio, 4);

        // Financial score: weighted composite
        $financialScore = round(
            ($incomeStabilityScore * 0.35) +
            ($cashflowScore * 0.35) +
            (max(0, 1 - $debtServiceRatio) * 0.30),
            4
        );

        return [
            'average_monthly_income_cents'              => $avgIncome,
            'average_monthly_expense_cents'             => $avgExpense,
            'average_monthly_net_cashflow_cents'        => $avgNetCashflow,
            'average_monthly_salary_income_cents'       => $avgSalary,
            'average_monthly_business_income_cents'     => $avgBusiness,
            'average_monthly_other_income_cents'        => $avgOtherIncome,
            'average_monthly_debt_service_cents'        => $avgDebtService,
            'average_monthly_discretionary_cashflow_cents' => $avgDiscretionary,
            'income_stability_score'                    => $incomeStabilityScore,
            'cashflow_score'                            => $cashflowScore,
            'financial_score'                           => $financialScore,
            'income_volatility'                         => round($incomeVol, 6),
            'expense_volatility'                        => round($expenseVol, 6),
            'debt_service_ratio'                        => $debtServiceRatio,
            'analysis_months'                           => $n,
            'transaction_count'                         => count($transactions),
            'currency'                                  => 'KES',
        ];
    }

    private static function emptyProfile(): array
    {
        return [
            'average_monthly_income_cents'              => 0,
            'average_monthly_expense_cents'             => 0,
            'average_monthly_net_cashflow_cents'        => 0,
            'average_monthly_salary_income_cents'       => 0,
            'average_monthly_business_income_cents'     => 0,
            'average_monthly_other_income_cents'        => 0,
            'average_monthly_debt_service_cents'        => 0,
            'average_monthly_discretionary_cashflow_cents' => 0,
            'income_stability_score'                    => 0.0,
            'cashflow_score'                            => 0.0,
            'financial_score'                           => 0.0,
            'income_volatility'                         => 0.0,
            'expense_volatility'                        => 0.0,
            'debt_service_ratio'                        => 0.0,
            'analysis_months'                           => 0,
            'transaction_count'                         => 0,
            'currency'                                  => 'KES',
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // OCR fallback — anchored on receipt codes in tab-delimited OCR output
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Parse heavily-corrupted OCR text from Safaricom M-Pesa merchant PDFs.
     *
     * OCR.space returns tab-separated tokens whose column alignment is broken.
     * However, receipt codes (10-char uppercase alphanumeric like "UIA6Q50EK6")
     * survive OCR reliably and can anchor each transaction block.
     *
     * Strategy:
     *  1. Flatten the text into tab/newline-separated tokens.
     *  2. Walk the token stream; when we encounter a receipt code, collect
     *     all tokens until the next receipt code (or end of relevant section).
     *  3. Within that block, harvest:
     *     - The first well-formed date (DD/MM/YYYY or YYYY-MM-DD variant)
     *     - All signed/unsigned currency amounts (digits with optional commas and decimals)
     *     - A rough description from non-numeric, non-receipt, non-date tokens
     *  4. Determine paid_in / paid_out / balance from the harvested amounts
     *     using the merchant-account sign convention:
     *       • explicit negative amounts → withdrawal / charge
     *       • three positive amounts → out, in, balance (merchant layout)
     *       • two positive amounts   → amount, balance
     *
     * Noise tokens (page headers, disclaimers, "No Records Found", account
     * type labels, etc.) are stripped before and during processing.
     */
    private static function extractTransactionsFromOcr(string $text): array
    {
        $transactions = [];

        // ── Normalise line endings ────────────────────────────────────────
        $text = preg_replace('/\r\n?/', "\n", $text);

        // ── Pre-process: split compound tokens that OCR jams together ────
        // e.g. "6,966.47 PaymeMerchant" → ["6,966.47", "PaymeMerchant"]
        // e.g. "-38.50" as a standalone token is fine; we also want to split
        // tokens like "1,966.47 Sea Ae to owner" into the amount + text.
        // We do this by inserting a tab before each currency-amount pattern
        // that is followed by non-numeric text within the same token.
        $text = preg_replace_callback(
            '/\t([+-]?[\d,]+\.\d{2})\s+([A-Za-z].*)/',
            fn ($m) => "\t" . $m[1] . "\t" . $m[2],
            $text
        );

        // Split on tabs AND newlines
        $rawTokens = preg_split('/[\t\n]+/', $text);
        $rawTokens = array_map('trim', $rawTokens);
        $rawTokens = array_filter($rawTokens, fn ($t) => $t !== '');
        $rawTokens = array_values($rawTokens);

        // ── Noise patterns — tokens to discard entirely ──────────────────
        $noisePatterns = [
            '/^(No Records Found\.?|Account Type|Receipt No\.?|Completion|Details|Transaction|Paid In|Withdrawn|Balance|Other Party|Status|Type|time|LOCALE|HOP|Page \d+ of \d+)$/i',
            '/^(Disclaimer|Statement Verification Code|Safaricom|www\.|P\.O\.|For self-help|Simple|Transparent|Honest|Approved|Statement Period|Request Date|Shortcode|M-PESA|Merchant Account|Utility Account|Working Account|Charges Paid Account)/i',
            '/^ZJA[0-9A-Z]{4,}$/',       // verification codes
            '/^\*334#/',                  // USSD codes
            '/^â€/',                     // encoding artefacts
            '/^[|*•]+$/',               // bullet/separator chars
            '/^-{3,}$/',                 // separator lines
            '/^completior$/i',           // OCR artefact for "Completion"
            '/^-ompletior$/i',
        ];

        $tokens = [];
        foreach ($rawTokens as $t) {
            $skip = false;
            foreach ($noisePatterns as $pattern) {
                if (preg_match($pattern, $t)) { $skip = true; break; }
            }
            if (!$skip) {
                $tokens[] = $t;
            }
        }

        // ── Receipt code pattern ──────────────────────────────────────────
        // Safaricom receipt codes: 10 chars, start with 2-4 uppercase letters
        // Allow the last char to be a digit or uppercase letter (OCR may
        // misread the final character as a digit or pipe).
        $receiptPattern = '/^[A-Z]{2,4}[A-Z0-9]{5,8}[A-Z0-9|]$/';

        // ── Locate all receipt-code anchors ───────────────────────────────
        $anchors = [];
        foreach ($tokens as $idx => $tok) {
            if (preg_match($receiptPattern, $tok)) {
                $anchors[] = $idx;
            }
        }

        if (empty($anchors)) {
            return [];
        }

        // ── Helper: extract all currency amounts from a single string ─────
        // Returns array of signed ints in cents; negatives for withdrawals.
        // Tokens that look like garbled date-times (contain `:` or are mostly
        // digits with punctuation) are skipped to avoid false amount extraction.
        $harvestAmounts = function (string $tok): array {
            $found = [];

            // Skip tokens that look like garbled date/time strings to avoid
            // extracting e.g. "509.02" from "30:0509.02"
            if (preg_match('/\d{2}:\d{2}/', $tok)) {
                return $found;
            }

            // Negative amount: "-1,180.00" or "- 200.00"
            if (preg_match('/^-\s*([\d,]+\.\d{1,2})$/', $tok, $m)) {
                $found[] = -self::parseCents($m[1]);
                return $found;
            }
            // Positive amount: standalone "7,000.00"
            if (preg_match('/^([\d,]+\.\d{1,2})$/', $tok, $m)) {
                $cents = self::parseCents($m[1]);
                if ($cents > 0) $found[] = $cents;
                return $found;
            }
            // Mixed token: extract all embedded amounts (positive and negative)
            // e.g. "6,966.47 PaymeMerchant" → 696647
            // Only extract if the token starts with the amount (not buried mid-string)
            if (preg_match('/^(-?)([\d,]{1,10}\.\d{2})\s+[A-Za-z]/', $tok, $im)) {
                $cents = self::parseCents($im[2]);
                if ($cents > 0) {
                    $found[] = ($im[1] === '-') ? -$cents : $cents;
                }
                return $found;
            }
            return $found;
        };

        // ── Validate date: must be a plausible M-Pesa statement date ─────
        $parseOcrDate = function (string $tok): ?string {
            // ISO: YYYY-MM-DD  (possibly with : or . instead of -)
            if (preg_match('/^(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})/', $tok, $m)) {
                $y = (int)$m[1]; $mo = (int)$m[2]; $d = (int)$m[3];
                if ($y >= 2015 && $y <= 2035 && $mo >= 1 && $mo <= 12 && $d >= 1 && $d <= 31) {
                    return sprintf('%04d-%02d-%02d', $y, $mo, $d);
                }
            }
            // DD/MM/YYYY
            if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $tok, $m)) {
                $d = (int)$m[1]; $mo = (int)$m[2]; $y = (int)$m[3];
                if ($y >= 2015 && $y <= 2035 && $mo >= 1 && $mo <= 12 && $d >= 1 && $d <= 31) {
                    return sprintf('%04d-%02d-%02d', $y, $mo, $d);
                }
            }
            return null;
        };

        // ── Process each receipt-code block ───────────────────────────────
        // Pre-scan: build a date map keyed by anchor index so that blocks
        // with garbled dates can borrow from their nearest neighbour.
        $anchorDates = [];
        foreach ($anchors as $ai => $anchorIdx) {
            $nextAnchor = $anchors[$ai + 1] ?? count($tokens);
            $blockEnd   = min($nextAnchor, $anchorIdx + 25);
            $block      = array_slice($tokens, $anchorIdx + 1, $blockEnd - $anchorIdx - 1);
            foreach ($block as $tok) {
                $d = $parseOcrDate($tok);
                if ($d !== null) { $anchorDates[$ai] = $d; break; }
            }
        }

        // Fill gaps: if an anchor has no date, use the nearest anchor that does
        $resolvedDates = [];
        foreach ($anchors as $ai => $anchorIdx) {
            if (isset($anchorDates[$ai])) {
                $resolvedDates[$ai] = $anchorDates[$ai];
                continue;
            }
            // Search forward then backward for the closest dated anchor
            $best = null;
            $bestDist = PHP_INT_MAX;
            foreach ($anchorDates as $dai => $dd) {
                $dist = abs($dai - $ai);
                if ($dist < $bestDist) { $bestDist = $dist; $best = $dd; }
            }
            // Only inherit if the neighbour is within 3 blocks (same page area)
            $resolvedDates[$ai] = ($bestDist <= 3 && $best !== null) ? $best : null;
        }

        $lastValidDate = null; // tracks the most recent clean date seen
        foreach ($anchors as $ai => $anchorIdx) {
            $receiptCode = $tokens[$anchorIdx];
            // Normalise OCR misread of trailing '|' as digit '1'
            $receiptCode = rtrim($receiptCode, '|');

            $nextAnchor = $anchors[$ai + 1] ?? count($tokens);
            // Cap lookahead at 25 tokens to avoid bleed from next section
            $blockEnd = min($nextAnchor, $anchorIdx + 25);
            $block    = array_slice($tokens, $anchorIdx + 1, $blockEnd - $anchorIdx - 1);

            if (count($block) < 2) {
                continue;
            }

            // ── 1. Extract best date ──────────────────────────────────────
            $date = null;
            foreach ($block as $tok) {
                $d = $parseOcrDate($tok);
                if ($d !== null) { $date = $d; break; }
            }

            // Fallback: scan for any 4-digit year embedded in a token
            if ($date === null) {
                foreach ($block as $tok) {
                    if (preg_match('/\b(20[12]\d)\b/', $tok, $ym)) {
                        $date = $ym[1] . '-01-01';
                        break;
                    }
                }
            }

            // Last resort: use the nearest-neighbour date from the pre-scan,
            // or the running lastValidDate (handles same-receipt charge+principal
            // pairs where one row has a fully garbled date).
            if ($date === null) {
                $date = $resolvedDates[$ai] ?? $lastValidDate;
            }

            if ($date === null) {
                continue;
            }

            // Track the most recent clean date for the running-fallback path
            if (!str_ends_with($date, '-01-01')) {
                $lastValidDate = $date;
            }

            // ── 2. Harvest all currency amounts from the block ─────────────
            $allAmounts = []; // signed cents; negative = outflow
            foreach ($block as $tok) {
                foreach ($harvestAmounts($tok) as $amt) {
                    $allAmounts[] = $amt;
                }
            }

            if (empty($allAmounts)) {
                continue;
            }

            $negatives = array_values(array_filter($allAmounts, fn ($a) => $a < 0));
            $positives = array_values(array_filter($allAmounts, fn ($a) => $a > 0));

            // ── 3. Determine paid_in / paid_out / balance ──────────────────
            //
            // Merchant statement column order: Paid In | Withdrawn | Balance
            //
            // Case A: explicit negative amounts present
            //   → largest absolute negative = principal withdrawal / charge
            //   → last positive = running balance
            //   → any earlier positives are paid_in on the same row (uncommon)
            //
            // Case B: all positive — three amounts → [paid_in, withdrawn, balance]
            // Case C: all positive — two amounts
            //   → use description keywords to decide in vs out
            // Case D: one positive — skip (only a balance, direction unknown)

            $paidIn  = 0;
            $paidOut = 0;
            $balance = 0;

            if (!empty($negatives)) {
                // Case A — take the single largest outflow as paidOut
                $paidOut = abs(min($negatives));

                if (!empty($positives)) {
                    // Last positive in the block = running balance
                    $balance = end($positives);
                    // Do NOT set paid_in from positives when we already have an
                    // explicit negative withdrawal — on merchant accounts, a
                    // negative row is a charge/withdrawal and is never also a
                    // receipt. Positives in this block are balance values only.
                }
            } else {
                $cnt = count($positives);

                if ($cnt >= 3) {
                    // Case B — use the last three positives
                    $tail    = array_slice($positives, -3);
                    $paidIn  = $tail[0];
                    $paidOut = $tail[1];
                    $balance = $tail[2];
                    // Sanity: if withdrawn > paid_in significantly and balance
                    // doesn't follow, try treating as [paid_in, 0, balance]
                    if ($paidOut > $paidIn && $paidOut > $balance) {
                        // Probably a large paid_in with a small charge somewhere;
                        // keep as-is, let the caller sort out classification.
                    }
                } elseif ($cnt === 2) {
                    // Case C
                    $descBlob   = strtolower(implode(' ', $block));
                    $isOutgoing = (bool) preg_match(
                        '/\b(charge|pay merchant|withdrawn|merchant to|payment charge|utility|pay bill)\b/',
                        $descBlob
                    );
                    if ($isOutgoing) {
                        $paidOut = $positives[0];
                        $balance = $positives[1];
                    } else {
                        $paidIn  = $positives[0];
                        $balance = $positives[1];
                    }
                } else {
                    // Case D — single amount, direction unknown
                    continue;
                }
            }

            if ($paidIn === 0 && $paidOut === 0) {
                continue;
            }

            // ── 4. Build description ──────────────────────────────────────
            $descTokens = [];
            foreach ($block as $tok) {
                if (preg_match('/^-?\s*[\d,]+\.?\d*$/', $tok)) continue;          // pure numbers
                if (preg_match($receiptPattern, $tok)) continue;                   // receipt codes
                if ($parseOcrDate($tok) !== null) continue;                        // dates
                if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $tok)) continue;    // times
                if (preg_match('/^\d{9,}$/', $tok)) continue;                     // phone nums
                // Strip leading amount from mixed tokens like "6,966.47 PayMe..."
                $clean = preg_replace('/^-?\s*[\d,]+\.\d{2}\s*/', '', $tok);
                $clean = preg_replace('/â€[^\s]*/u', '', $clean);
                $clean = trim($clean);
                if (strlen($clean) >= 3) {
                    $descTokens[] = $clean;
                }
            }

            $description = trim(preg_replace('/\s{2,}/', ' ', implode(' ', array_unique($descTokens))));
            if ($description === '') {
                $description = 'M-Pesa Transaction';
            }

            $transactions[] = [
                'receipt_no'     => $receiptCode,
                'date'           => $date,
                'time'           => '00:00:00',
                'description'    => $description,
                'paid_out_cents' => $paidOut,
                'paid_in_cents'  => $paidIn,
                'amount_cents'   => $paidIn > 0 ? $paidIn : -$paidOut,
                'balance_cents'  => $balance,
            ];
        }

        // ── Merge charge + principal rows for the same receipt code ──────
        //
        // Safaricom merchant statements emit TWO rows per transaction:
        //   1. The principal row  (paid_in or large withdrawal + running balance)
        //   2. The charge row     (small withdrawal, the Safaricom service fee)
        //
        // Both rows share the same receipt code. We merge them: take the date
        // and paid_in from the principal row, add paid_out from both rows, and
        // use the balance from the principal row (the higher of the two).

        $byReceipt = [];
        foreach ($transactions as $tx) {
            $key = $tx['receipt_no'];
            if (!isset($byReceipt[$key])) {
                $byReceipt[$key] = $tx;
            } else {
                $existing = $byReceipt[$key];

                // Identify which is the principal (higher monetary movement)
                $txMov  = max($tx['paid_in_cents'],  $tx['paid_out_cents']);
                $exMov  = max($existing['paid_in_cents'], $existing['paid_out_cents']);

                $principal = ($txMov >= $exMov) ? $tx       : $existing;
                $charge    = ($txMov >= $exMov) ? $existing : $tx;

                // Merge: principal values + accumulated charge paid_out
                $merged = $principal;
                $merged['paid_out_cents'] += $charge['paid_out_cents'];
                // Recalculate signed amount
                $merged['amount_cents'] = $merged['paid_in_cents'] > 0
                    ? $merged['paid_in_cents']
                    : -$merged['paid_out_cents'];
                // Use the highest balance seen (principal row has the post-payment balance)
                $merged['balance_cents'] = max($principal['balance_cents'], $charge['balance_cents']);

                $byReceipt[$key] = $merged;
            }
        }

        return array_values($byReceipt);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────

    private static function detectPeriod(array $transactions): array
    {
        $dates = array_column($transactions, 'date');
        if (empty($dates)) {
            return ['start' => null, 'end' => null];
        }
        sort($dates);
        return ['start' => $dates[0], 'end' => end($dates)];
    }

    private static function parseCents(string $value): int
    {
        return (int) round((float) str_replace(',', '', $value) * 100);
    }

    private static function parseDate(string $dmy): string
    {
        // Input: DD/MM/YYYY  → Output: YYYY-MM-DD
        $parts = preg_split('/[\/-]/', $dmy);
        return sprintf('%04d-%02d-%02d', (int)$parts[2], (int)$parts[1], (int)$parts[0]);
    }

    private static function matchesKeywords(string $haystack, array $keywords): bool
    {
        foreach ($keywords as $kw) {
            if (str_contains($haystack, $kw)) return true;
        }
        return false;
    }

    private static function coefficientOfVariation(array $values): float
    {
        $n = count($values);
        if ($n < 2) return 0.0;

        $mean = array_sum($values) / $n;
        if ($mean == 0) return 0.0;

        $variance = array_sum(array_map(fn ($v) => ($v - $mean) ** 2, $values)) / $n;
        return sqrt($variance) / $mean;
    }
}
