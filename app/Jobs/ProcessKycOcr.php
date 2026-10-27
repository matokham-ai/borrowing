<?php

namespace App\Jobs;

use App\Support\IdOcrParser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessKycOcr implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 90;
    public int $tries   = 2;

    public function __construct(
        private readonly string $jobId,
        private readonly string $filePath,
        private readonly string $fileKey,
        private readonly string $ext,
    ) {}

    public function handle(): void
    {
        $apiKey   = config('services.ocrspace.api_key', 'helloworld');
        $endpoint = config('services.ocrspace.endpoint', 'https://api.ocr.space/parse/image');
        $startedAt = microtime(true);
        $imageBytes = filesize($this->filePath);

        try {
            $response = Http::timeout(30)
                ->withHeaders(['apikey' => $apiKey])
                ->attach('file', file_get_contents($this->filePath), 'id.' . $this->ext)
                ->post($endpoint, [
                    'language'          => 'eng',
                    'isOverlayRequired' => 'false',
                    'detectOrientation' => 'true',
                    'scale'             => 'true',
                    'isTable'           => 'false',
                    'OCREngine'         => '2',
                ]);

            Log::info('KYC OCR provider request completed', [
                'job' => $this->jobId,
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'image_bytes' => $imageBytes,
                'status' => $response->status(),
            ]);

            if ($response->status() === 503) {
                $this->storeResult(['success' => false, 'error' => 'OCR service temporarily busy. Please try scanning again.']);
                return;
            }

            if ($response->failed()) {
                $this->storeResult(['success' => false, 'error' => 'OCR request failed (HTTP ' . $response->status() . ').']);
                return;
            }

            $json = $response->json();

            if (!empty($json['IsErroredOnProcessing'])) {
                $errors = $json['ErrorMessage'] ?? [];
                $msg    = is_array($errors) ? implode(' ', array_filter($errors)) : (string) $errors;
                $this->storeResult(['success' => false, 'error' => $msg ?: 'OCR processing failed.']);
                return;
            }

            // Collect all parsed text from all pages
            $rawText = collect($json['ParsedResults'] ?? [])
                ->pluck('ParsedText')
                ->filter()
                ->implode("\n");

            $rawText = trim($rawText);

            if ($rawText === '') {
                $this->storeResult([
                    'success' => false,
                    'error'   => 'No text found in the image. Ensure the document is well-lit and fully visible.',
                ]);
                return;
            }

            // Parse into structured fields using the shared parser
            $fields             = IdOcrParser::parse($rawText);
            $fields['raw_text'] = $rawText;

            $this->storeResult([
                'success'  => true,
                'file_key' => $this->fileKey,
                'fields'   => $fields,
            ]);

        } catch (\Exception $e) {
            Log::error('OCR job exception', ['job' => $this->jobId, 'error' => $e->getMessage()]);
            $this->storeResult(['success' => false, 'error' => 'OCR failed: ' . $e->getMessage()]);
        }
    }

    private function storeResult(array $result): void
    {
        Cache::put('kyc_ocr_' . $this->jobId, $result, now()->addMinutes(10));
    }

    public function failed(\Throwable $e): void
    {
        Log::error('OCR job failed', ['job' => $this->jobId, 'error' => $e->getMessage()]);
        Cache::put('kyc_ocr_' . $this->jobId, [
            'success' => false,
            'error'   => 'OCR job failed: ' . $e->getMessage(),
        ], now()->addMinutes(10));
    }
}
