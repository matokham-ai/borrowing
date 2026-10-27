<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Auditor
 *
 * Thin static facade around the AuditLog model.
 *
 * Usage:
 *   Auditor::log('application.approved', $application, [
 *       'old' => ['status' => 'submitted'],
 *       'new' => ['status' => 'approved', 'approved_amount_cents' => 50000],
 *   ]);
 *
 *   Auditor::log('borrower.created', $borrower);
 *
 * Every call captures: authenticated user, org, IP, user-agent, timestamp.
 * Silently swallows exceptions so audit failures never break user flows.
 */
class Auditor
{
    /**
     * Write an audit log entry.
     *
     * @param  string       $action       Dot-namespaced action, e.g. 'application.approved'
     * @param  Model|null   $subject      The Eloquent model being acted on (sets auditable).
     * @param  array        $options {
     *   @type array  old         Old attribute values (optional)
     *   @type array  new         New attribute values (optional)
     *   @type string description Human-readable description (optional)
     *   @type array  context     Additional contextual data (optional)
     *   @type int    orgId       Override organisation_id (optional; defaults to auth user's org)
     * }
     */
    public static function log(string $action, ?Model $subject = null, array $options = []): void
    {
        try {
            $user   = Auth::user();
            $orgId  = $options['orgId']
                ?? $user?->organisation_id
                ?? ($subject?->organisation_id ?? null);

            if (!$orgId) {
                return; // Can't write without an org scope
            }

            AuditLog::create([
                'organisation_id' => (int) $orgId,
                'user_id'         => $user?->id,
                'action'          => $action,
                'auditable_type'  => $subject ? get_class($subject) : null,
                'auditable_id'    => $subject?->getKey(),
                'description'     => $options['description'] ?? static::descriptionFor($action, $subject),
                'old_values'      => $options['old'] ?? null,
                'new_values'      => $options['new'] ?? null,
                'context'         => $options['context'] ?? null,
                'ip_address'      => Request::ip(),
                'user_agent'      => Request::userAgent(),
                'occurred_at'     => now(),
            ]);
        } catch (\Throwable $e) {
            // Audit failures must never crash the calling flow
            \Illuminate\Support\Facades\Log::warning('Auditor: failed to write audit log', [
                'action' => $action,
                'error'  => $e->getMessage(),
            ]);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private static function descriptionFor(string $action, ?Model $subject): string
    {
        $label = $subject ? class_basename($subject) . ' #' . $subject->getKey() : '';

        return match(true) {
            str_ends_with($action, '.created')  => "{$label} created.",
            str_ends_with($action, '.updated')  => "{$label} updated.",
            str_ends_with($action, '.deleted')  => "{$label} deleted.",
            str_ends_with($action, '.approved') => "{$label} approved.",
            str_ends_with($action, '.rejected') => "{$label} rejected.",
            str_ends_with($action, '.reversed') => "{$label} reversed.",
            str_ends_with($action, '.disbursed')=> "{$label} disbursed.",
            default                             => ucfirst(str_replace(['.', '_'], ' ', $action)) . ($label ? ": {$label}" : '.'),
        };
    }
}
