<?php

namespace App\Http\Controllers\Mpesa;

use App\Http\Controllers\Controller;
use App\Models\LoanPayment;
use App\Models\MpesaApiOperation;
use App\Models\Organisation;
use App\Services\Mpesa\MpesaDarajaOperationsService;
use App\Services\Payments\PaymentReversalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MpesaOperationsController extends Controller
{
    public function index(Request $request, MpesaDarajaOperationsService $daraja): View
    {
        $organisationId = (int) $request->user()->organisation_id;
        $operations = MpesaApiOperation::where('organisation_id', $organisationId)
            ->latest('id')
            ->paginate(15);

        return view('mpesa.operations.index', [
            'catalog' => $daraja->catalog(),
            'operations' => $operations,
            'environment' => config('services.mpesa.env', 'sandbox'),
        ]);
    }

    public function store(Request $request, MpesaDarajaOperationsService $daraja): RedirectResponse
    {
        $data = $request->validate([
            'product' => ['required', 'string', 'in:'.implode(',', array_keys($daraja->catalog()))],
            'payload' => ['required', 'string', 'max:65535'],
        ]);

        try {
            $payload = json_decode($data['payload'], true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return back()->withInput()->withErrors(['payload' => 'Enter a valid JSON object.']);
        }

        if (! is_array($payload) || array_is_list($payload)) {
            return back()->withInput()->withErrors(['payload' => 'The request payload must be a JSON object.']);
        }

        $definition = $daraja->catalog()[$data['product']];
        $missing = collect($definition['required'])
            ->filter(fn ($key) => ! array_key_exists($key, $payload) || $payload[$key] === '' || $payload[$key] === null)
            ->values();

        if ($missing->isNotEmpty()) {
            return back()->withInput()->withErrors([
                'payload' => 'Missing required fields: '.$missing->implode(', ').'.',
            ]);
        }

        $forbidden = array_intersect(array_keys($payload), $definition['system_fields']);
        if ($forbidden !== []) {
            return back()->withInput()->withErrors([
                'payload' => 'Remove fields supplied automatically by the integration: '.implode(', ', $forbidden).'.',
            ]);
        }

        if (isset($payload['Amount']) && (! is_numeric($payload['Amount']) || (float) $payload['Amount'] <= 0)) {
            return back()->withInput()->withErrors(['payload' => 'Amount must be a positive number.']);
        }

        if ($data['product'] === 'reversal' && (float) $payload['Amount'] !== (float) (int) $payload['Amount']) {
            return back()->withInput()->withErrors(['payload' => 'Reversal Amount must be a whole number of Kenya shillings.']);
        }

        if (str_starts_with($data['product'], 'c2b_') && ! in_array($payload['ResponseType'], ['Completed', 'Cancelled'], true)) {
            return back()->withInput()->withErrors(['payload' => 'ResponseType must be Completed or Cancelled.']);
        }

        $operation = MpesaApiOperation::create([
            'organisation_id' => (int) $request->user()->organisation_id,
            'callback_token' => (string) Str::uuid(),
            'product' => $data['product'],
            'environment' => config('services.mpesa.env', 'sandbox'),
            'status' => 'queued',
            'request_payload' => $payload,
        ]);

        try {
            $response = $daraja->submit($operation, $payload);
        } catch (\Throwable $exception) {
            return redirect()->route('mpesa.operations.index')
                ->with('error', 'Daraja request failed: '.$exception->getMessage());
        }

        $message = $definition['async']
            ? 'Request accepted by Daraja; waiting for its callback.'
            : 'Request completed successfully.';

        return redirect()->route('mpesa.operations.index')
            ->with('success', $message)
            ->with('operation_response', $response);
    }

    public function storeC2bRegister(Request $request, MpesaDarajaOperationsService $daraja): RedirectResponse
    {
        $data = $request->validate([
            'product'       => ['required', 'in:c2b_v2_register,c2b_v1_register'],
            'short_code'    => ['required', 'string', 'regex:/^\d{4,10}$/'],
            'response_type' => ['required', 'in:Completed,Cancelled'],
        ]);

        $payload = [
            'ShortCode'    => $data['short_code'],
            'ResponseType' => $data['response_type'],
        ];

        $definition = $daraja->catalog()[$data['product']];

        $operation = MpesaApiOperation::create([
            'organisation_id' => (int) $request->user()->organisation_id,
            'callback_token'  => (string) Str::uuid(),
            'product'         => $data['product'],
            'environment'     => config('services.mpesa.env', 'sandbox'),
            'status'          => 'queued',
            'request_payload' => $payload,
        ]);

        try {
            $response = $daraja->submit($operation, $payload);
        } catch (\Throwable $exception) {
            return redirect()->route('mpesa.operations.index')
                ->with('error', 'URL registration failed: '.$exception->getMessage());
        }

        return redirect()->route('mpesa.operations.index')
            ->with('success', 'C2B URLs registered successfully with Safaricom.')
            ->with('operation_response', $response);
    }

    public function callback(
        Request $request,
        string $operation,
        string $kind,
        MpesaDarajaOperationsService $daraja,
        PaymentReversalService $paymentReversals,
    ): JsonResponse {
        $payload = $request->json()->all();
        $record = MpesaApiOperation::where('callback_token', $operation)->first();

        if (! $record || ! in_array($kind, ['result', 'timeout'], true) || $payload === []) {
            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Invalid callback.'], 404);
        }

        $organisation = Organisation::whereKey($record->organisation_id)
            ->where('is_active', true)
            ->first();
        if (! $organisation) {
            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Unknown organisation.'], 404);
        }

        try {
            DB::transaction(function () use ($record, $kind, $payload, $daraja, $paymentReversals): void {
                $record->refresh();
                if (in_array($record->status, ['completed', 'failed', 'timeout'], true)) {
                    return;
                }

                $result = $payload['Result'] ?? $payload;
                if (
                    $record->product === 'reversal'
                    && $record->loan_payment_id
                    && $kind === 'result'
                    && isset($result['ResultCode'])
                    && (int) $result['ResultCode'] === 0
                ) {
                    $payment = LoanPayment::where('organisation_id', $record->organisation_id)
                        ->findOrFail($record->loan_payment_id);
                    $paymentReversals->reversePayment(
                        $payment,
                        (string) ($record->request_payload['Remarks'] ?? 'Daraja reversal confirmed'),
                    );
                }

                $daraja->acceptCallback($record, $kind, $payload);
            });
        } catch (\Throwable $exception) {
            Log::error('Daraja operation callback processing failed', [
                'operation_id' => $record->id,
                'product' => $record->product,
                'error' => $exception->getMessage(),
            ]);

            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Callback processing failed.'], 500);
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }
}
