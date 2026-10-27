<?php

namespace App\Http\Controllers\Kyc;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Handles manual KYC verification decisions by loan officers.
 *
 * An officer can:
 *  - Approve (verify) a borrower's KYC record
 *  - Mark it as manually cleared
 *  - Fail it with a reason
 *  - Reset it to pending for re-submission
 */
class KycVerificationController extends Controller
{
    /**
     * POST /kyc/verify/{borrower}
     *
     * Updates the current KYC record's verification_status.
     * Redirects back to the borrower show page.
     */
    public function update(Request $request, int $borrower): JsonResponse|RedirectResponse
    {
        abort_unless(auth()->user()?->hasPermission('borrowers.verify_kyc'), 403);
        $orgId = (int) auth()->user()->organisation_id;

        $data = $request->validate([
            'verification_status' => ['required', 'in:verified,manual,failed,pending'],
            'verification_notes'  => ['nullable', 'string', 'max:2000'],
        ]);

        // Confirm borrower belongs to this organisation
        $borrowerRecord = DB::table('borrowers')
            ->where('organisation_id', $orgId)
            ->where('id', $borrower)
            ->when(auth()->user()->hasRole(['loan-officer', 'supervisor']), fn ($query) =>
                $query->where('branch_id', auth()->user()->branch_id ?? 0)
            )
            ->first();

        abort_unless($borrowerRecord, 404);
        abort_if(
            (int) $borrowerRecord->registered_by === (int) auth()->id(),
            403,
            'The borrower registrant cannot verify their own identity documents.'
        );

        $kycId = DB::table('kyc_records')
            ->where('organisation_id', $orgId)
            ->where('borrower_id', $borrower)
            ->where('is_current', true)
            ->value('id');

        abort_unless($kycId, 404, 'No current KYC record found for this borrower.');

        $updateData = [
            'verification_status' => $data['verification_status'],
            'verification_notes'  => $data['verification_notes'] ?? null,
            'updated_at'          => now(),
        ];

        // Stamp verified_at only when moving into an approved state
        if (in_array($data['verification_status'], ['verified', 'manual'], true)) {
            $updateData['verified_at']   = now();
            $updateData['verification_provider'] = 'manual_officer';
        } else {
            // Clear verified_at if failing or resetting
            $updateData['verified_at'] = null;
        }

        DB::table('kyc_records')
            ->where('id', $kycId)
            ->update($updateData);

        // Audit trail
        DB::table('system_events')->insert([
            'organisation_id' => $orgId,
            'event_type'      => 'kyc_status_updated',
            'event_id'        => (string) \Illuminate\Support\Str::uuid(),
            'idempotency_key' => 'kyc-upd-' . $kycId . '-' . now()->timestamp,
            'aggregate_type'  => 'borrower',
            'aggregate_id'    => $borrower,
            'payload'         => json_encode([
                'kyc_record_id'       => $kycId,
                'new_status'          => $data['verification_status'],
                'notes'               => $data['verification_notes'] ?? null,
                'officer_user_id'     => auth()->id(),
                'officer_name'        => auth()->user()->name,
            ]),
            'status'       => 'processed',
            'processed_at' => now(),
            'occurred_at'  => now(),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $label = match($data['verification_status']) {
            'verified' => 'KYC verified — borrower is now cleared for lending.',
            'manual'   => 'KYC manually cleared — borrower is cleared for lending.',
            'failed'   => 'KYC marked as failed.',
            'pending'  => 'KYC reset to pending.',
            default    => 'KYC status updated.',
        };

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $label,
                'verification_status' => $data['verification_status'],
            ]);
        }

        return redirect()
            ->route('borrowers.show', $borrower)
            ->with('success', $label);
    }
}
