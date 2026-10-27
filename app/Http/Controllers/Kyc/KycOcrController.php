<?php

namespace App\Http\Controllers\Kyc;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessKycOcr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class KycOcrController extends Controller
{
    /**
     * POST /kyc/ocr
     * Store the image and dispatch OCR for background processing.
     */
    public function extract(Request $request): JsonResponse
    {
        $request->validate([
            'id_image' => ['required', 'file', 'mimes:jpeg,jpg,png,webp', 'max:6144'],
        ], [
            'id_image.required' => 'Please upload an image of the ID document.',
            'id_image.mimes'    => 'Only JPEG, PNG and WebP images are supported.',
            'id_image.max'      => 'The image must be smaller than 6 MB.',
        ]);

        $file    = $request->file('id_image');
        $orgId   = (int) auth()->user()->organisation_id;
        $ext     = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $fileKey = "kyc/{$orgId}/" . Str::uuid() . ".{$ext}";

        Storage::disk('local')->put($fileKey, file_get_contents($file->getRealPath()));
        $absPath = Storage::disk('local')->path($fileKey);

        $jobId = (string) Str::uuid();
        ProcessKycOcr::dispatch($jobId, $absPath, $fileKey, $ext);

        return response()->json([
            'success'  => true,
            'queued'   => true,
            'job_id'   => $jobId,
            'file_key' => $fileKey,
        ]);
    }

    /**
     * GET /kyc/ocr/{jobId}
     * Returns { ready: false } while running, or the full result when done.
     */
    public function poll(string $jobId): JsonResponse
    {
        if (!preg_match('/^[0-9a-f\-]{36}$/', $jobId)) {
            return response()->json(['ready' => false]);
        }

        $result = Cache::get('kyc_ocr_' . $jobId);

        if ($result === null) {
            return response()->json(['ready' => false]);
        }

        Cache::forget('kyc_ocr_' . $jobId);
        return response()->json(array_merge(['ready' => true], $result));
    }
}
