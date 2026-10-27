<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * LoanAppraisalEngine
 *
 * Determines a borrower's maximum eligible loan offer by combining:
 *  1. KYC gate       — identity must be verified (or manually cleared)
 *  2. Tier gate      — borrower's current tier locks the absolute ceiling
 *  3. Tier-rule gate — the (product × tier) rule applies score & history thresholds
 *  4. Affordability  — discretionary cashflow caps repayment capacity
 *  5. Risk scoring   — blended financial + behaviour score modulates the offer
 *
 * All monetary values stored and returned in **KES cents** (integer).
 * Scores are on a **0–1 float** scale.
 *
 * Usage:
 *   $result = LoanAppraisalEngine::appraise($borrowerId, $loanProductId, $orgId);
 *   // $result->eligible  : bool
 *   // $result->maxAmount : int (cents)
 *   // $result->maxTerm   : int (days)
 *   // $result->reasons   : string[]
 *   // $result->snapshot  : array (freeze into decision_snapshot)
 */
class LoanAppraisalEngine
{
    // ── Tuning constants ────────────────────────────────────────────────

    /**
     * Maximum portion of discretionary cashflow that can be committed
     * to a new loan repayment (monthly instalment ÷ discretionary).
     * 0.50 = 50 % — conservative for unsecured micro-lending.
     */
    private const MAX_INSTALMENT_TO_DISCRETIONARY = 0.50;

    /**
     * Assumed worst-case interest + fee multiplier for a short-term loan
     * used when computing the monthly instalment from principal.
     * 1.25 → 25 % total cost on the principal (safe upper bound for KES market).
     */
    private const COST_MULTIPLIER = 1.25;

    /**
     * Absolute minimum loan offer in KES cents.
     * Even the most conservative score factor will never produce less than this.
     * KSh 500 = 50 000 cents.
     */
    private const ABSOLUTE_MIN_CENTS = 50_000;

    /**
     * Minimum financial score for eligibility when a financial profile exists.
     * Profiles from M-Pesa statements have a computed score; declared-only
     * profiles have null — handled separately (conservative proxy applied).
     */
    private const MIN_FINANCIAL_SCORE_DEFAULT = 0.20;

    /**
     * How much to scale down the max offer when the financial data source
     * is self-declared only (no M-Pesa statement verified).
     */
    private const DECLARED_ONLY_SCALE = 0.60;

    /**
     * Multiplier applied when the financial score is above 0.70
     * (reward high-quality borrowers with a slight uplift up to tier ceiling).
     */
    private const HIGH_SCORE_UPLIFT = 1.15;

    /**
     * Score threshold above which the uplift kicks in.
     */
    private const HIGH_SCORE_THRESHOLD = 0.70;

    // ────────────────────────────────────────────────────────────────────

    /**
     * Run the full appraisal for a (borrower, product, organisation) triple.
     *
     * @return object {
     *   eligible: bool,
     *   maxAmountCents: int,
     *   maxTermDays: int,
     *   blendedScore: float,
     *   financialScore: float|null,
     *   behaviourScore: float|null,
     *   kycStatus: string,
     *   tierName: string,
     *   tierRuleId: int|null,
     *   tierRuleVersion: int|null,
     *   reasons: string[],          // human-readable explanations
     *   warnings: string[],         // soft warnings (not blockers)
     *   snapshot: array,            // full audit data
     * }
     */
    public static function appraise(int $borrowerId, int $loanProductId, int $organisationId): object
    {
        $reasons  = [];
        $warnings = [];

        // ── 1. Load borrower + tier ──────────────────────────────────────
        $borrower = DB::table('borrowers')
            ->join('users', 'users.id', '=', 'borrowers.user_id')
            ->leftJoin('borrower_tiers', function ($j) use ($organisationId) {
                $j->on('borrower_tiers.id', '=', 'borrowers.current_tier_id')
                  ->where('borrower_tiers.organisation_id', '=', $organisationId);
            })
            ->where('borrowers.organisation_id', $organisationId)
            ->where('borrowers.id', $borrowerId)
            ->select(
                'borrowers.*',
                'users.name as borrower_name',
                'users.email as borrower_email',
                'borrower_tiers.id as tier_id',
                'borrower_tiers.name as tier_name',
                'borrower_tiers.tier_number',
                'borrower_tiers.min_loan_amount_cents as tier_min_cents',
                'borrower_tiers.max_loan_amount_cents as tier_max_cents',
                'borrower_tiers.max_term_days as tier_max_term',
                'borrower_tiers.minimum_behaviour_score as tier_min_behaviour',
                'borrower_tiers.minimum_financial_score as tier_min_financial',
                'borrower_tiers.minimum_successful_loans as tier_min_success',
                'borrower_tiers.minimum_on_time_loans as tier_min_on_time',
                'borrower_tiers.minimum_consecutive_on_time_loans as tier_min_consec',
                'borrower_tiers.max_total_exposure_cents as tier_max_exposure',
            )
            ->first();

        if (!$borrower) {
            return self::ineligible('Borrower not found.', [], [], [], 'unknown', 'No Tier', null, null);
        }

        // Resolve tier name early so all early-returns can show it
        $tierName = $borrower->tier_name ?? 'No Tier Assigned';

        // ── 2. Account status gate ───────────────────────────────────────
        if ($borrower->account_status !== 'active') {
            $reasons[] = "Account is {$borrower->account_status} — lending is suspended.";
            return self::ineligible(
                implode(' ', $reasons), $reasons, $warnings,
                compact('borrower'),
                'unknown', $tierName,
                $borrower->behaviour_score !== null ? (float) $borrower->behaviour_score : null,
                null
            );
        }

        // ── 3. KYC gate ──────────────────────────────────────────────────
        $kyc = DB::table('kyc_records')
            ->where('organisation_id', $organisationId)
            ->where('borrower_id', $borrowerId)
            ->where('is_current', true)
            ->first();

        $kycStatus = $kyc?->verification_status ?? 'missing';

        if (!in_array($kycStatus, ['verified', 'manual'], true)) {
            $reasons[] = "KYC identity is not verified (status: {$kycStatus}). Identity must be confirmed before lending.";
            return self::ineligible(
                implode(' ', $reasons), $reasons, $warnings,
                compact('borrower', 'kyc', 'kycStatus'),
                $kycStatus, $tierName,
                $borrower->behaviour_score !== null ? (float) $borrower->behaviour_score : null,
                null
            );
        }

        // ── 4. Load loan product ─────────────────────────────────────────
        $product = DB::table('loan_products')
            ->where('organisation_id', $organisationId)
            ->where('id', $loanProductId)
            ->where('is_active', true)
            ->first();

        if (!$product) {
            $reasons[] = 'Loan product is not available.';
            return self::ineligible(
                implode(' ', $reasons), $reasons, $warnings,
                compact('borrower', 'kycStatus'),
                $kycStatus, $tierName, null, null
            );
        }

        // ── 5. Resolve the tier-rule for this (product × tier) pair ──────
        $tierRule = null;
        if ($borrower->tier_id) {
            $tierRule = DB::table('loan_tier_rules')
                ->where('organisation_id', $organisationId)
                ->where('loan_product_id', $loanProductId)
                ->where('borrower_tier_id', $borrower->tier_id)
                ->where('is_active', true)
                ->orderByDesc('version')
                ->first();
        }

        // ── 6. Load current financial profile ────────────────────────────
        $fp = DB::table('financial_profiles')
            ->where('organisation_id', $organisationId)
            ->where('borrower_id', $borrowerId)
            ->where('is_current', true)
            ->first();

        $financialScore = $fp ? (float) ($fp->financial_score ?? 0) : null;
        $isDeclaredOnly = $fp && $fp->model_version === 'declared-v1';
        $hasStatement   = $fp && !$isDeclaredOnly;

        // ── 7. Behaviour score from borrower row ─────────────────────────
        $behaviourScore = $borrower->behaviour_score !== null
            ? (float) $borrower->behaviour_score
            : null;

        // ── 8. Compute blended score ──────────────────────────────────────
        // Weights: financial 60 %, behaviour 40 %
        // When behaviour score is 0 (new borrower), financial score carries full weight
        // When no financial profile exists, behaviour score is used or conservative default
        $blendedScore = self::computeBlendedScore($financialScore, $behaviourScore, $isDeclaredOnly);

        // ── 9. Check tier-level score thresholds ─────────────────────────
        if ($borrower->tier_min_behaviour && $blendedScore < (float) $borrower->tier_min_behaviour) {
            $reasons[] = sprintf(
                'Blended score %.2f is below the tier minimum of %.2f.',
                $blendedScore,
                $borrower->tier_min_behaviour
            );
        }

        if ($borrower->tier_min_financial && $financialScore !== null && $financialScore < (float) $borrower->tier_min_financial) {
            $reasons[] = sprintf(
                'Financial score %.2f%% is below the tier minimum of %.2f%%.',
                $financialScore * 100,
                (float) $borrower->tier_min_financial * 100
            );
        }

        // ── 10. Tier-rule specific checks ─────────────────────────────────
        $ruleMinFinancial = null;
        $ruleMinBehaviour = null;
        $ruleTierRuleId   = null;
        $ruleTierRuleVer  = null;

        if ($tierRule) {
            $ruleTierRuleId  = $tierRule->id;
            $ruleTierRuleVer = $tierRule->version;
            $ruleMinFinancial = $tierRule->minimum_financial_score
                ? (float) $tierRule->minimum_financial_score
                : null;
            $ruleMinBehaviour = $tierRule->minimum_behaviour_score
                ? (float) $tierRule->minimum_behaviour_score
                : null;

            if ($ruleMinFinancial && $financialScore !== null && $financialScore < $ruleMinFinancial) {
                $reasons[] = sprintf(
                    'Financial score %.2f%% does not meet this product\'s minimum of %.2f%%.',
                    $financialScore * 100,
                    $ruleMinFinancial * 100
                );
            }

            if ($ruleMinBehaviour && $behaviourScore !== null && $behaviourScore < $ruleMinBehaviour) {
                $reasons[] = sprintf(
                    'Behaviour score %.4f does not meet this product\'s minimum of %.4f.',
                    $behaviourScore,
                    $ruleMinBehaviour
                );
            }

            // History requirements
            if ($tierRule->minimum_successful_loans && $borrower->successful_loans_count < $tierRule->minimum_successful_loans) {
                $reasons[] = sprintf(
                    'Borrower has %d successful loans; %d required for this product.',
                    $borrower->successful_loans_count,
                    $tierRule->minimum_successful_loans
                );
            }

            if ($tierRule->minimum_on_time_loans && $borrower->on_time_loans_count < $tierRule->minimum_on_time_loans) {
                $reasons[] = sprintf(
                    'Borrower has %d on-time loans; %d required for this product.',
                    $borrower->on_time_loans_count,
                    $tierRule->minimum_on_time_loans
                );
            }

            if ($tierRule->minimum_consecutive_on_time_loans && $borrower->consecutive_on_time_loans < $tierRule->minimum_consecutive_on_time_loans) {
                $reasons[] = sprintf(
                    'Borrower has %d consecutive on-time loans; %d required.',
                    $borrower->consecutive_on_time_loans,
                    $tierRule->minimum_consecutive_on_time_loans
                );
            }
        } else {
            // No tier rule found — product may not be available for this tier
            $warnings[] = 'No tier rule configured for this product + borrower tier combination. Using product limits only.';
        }

        // ── 11. Hard eligibility checks for missing financial data ────────
        if (!$fp) {
            $warnings[] = 'No financial profile on record. Using conservative defaults based on tier ceiling.';
        } elseif ($isDeclaredOnly && !$hasStatement) {
            $warnings[] = 'Financial profile is self-declared only (no M-Pesa statement). Offer is scaled conservatively.';
        }

        // If financial score is null and a financial score threshold exists — block
        if (($ruleMinFinancial !== null || ($borrower->tier_min_financial && (float) $borrower->tier_min_financial > 0))
            && $financialScore === null) {
            $reasons[] = 'A verified financial score is required for this product. Please upload an M-Pesa statement.';
        }

        // ── 12. Return ineligible if hard blockers exist ──────────────────
        if (!empty($reasons)) {
            return self::buildResult(
                eligible:       false,
                maxAmountCents: 0,
                maxTermDays:    0,
                blendedScore:   $blendedScore,
                financialScore: $financialScore,
                behaviourScore: $behaviourScore,
                kycStatus:      $kycStatus,
                tierName:       $borrower->tier_name ?? 'No Tier',
                tierRuleId:     $ruleTierRuleId,
                tierRuleVersion:$ruleTierRuleVer,
                reasons:        $reasons,
                warnings:       $warnings,
                snapshot:       self::buildSnapshot(
                    $borrower, $kyc, $fp, $product, $tierRule,
                    $blendedScore, $financialScore, $behaviourScore,
                    $kycStatus, $isDeclaredOnly, $reasons, $warnings
                ),
            );
        }

        // ── 13. Determine maximum loan amount ─────────────────────────────
        // Ceiling from tier
        $tierCeilCents  = (int) ($borrower->tier_max_cents ?? $product->maximum_amount_cents);
        // Ceiling from tier-rule (more restrictive if set)
        $ruleCeilCents  = $tierRule ? (int) $tierRule->maximum_amount_cents : $tierCeilCents;
        // Ceiling from product
        $productCeilCents = (int) $product->maximum_amount_cents;

        // Hard ceiling = tightest of the three
        $hardCeilCents = min($tierCeilCents, $ruleCeilCents, $productCeilCents);

        // Floor = highest of configured floors (most restrictive), but never below the absolute minimum (KSh 500)
        $configuredFloor = max(
            (int) ($borrower->tier_min_cents ?? 0),
            $tierRule ? (int) $tierRule->minimum_amount_cents : 0,
            (int) $product->minimum_amount_cents
        );
        $floorCents = max($configuredFloor, self::ABSOLUTE_MIN_CENTS);

        // ── 14. Affordability cap (cashflow-based) ────────────────────────
        $affordabilityCents = self::computeAffordabilityCents($fp, $product, $hardCeilCents);

        // ── 15. Score-based modulation ────────────────────────────────────
        $scoreFactor   = self::computeScoreFactor($blendedScore);
        $modulated     = (int) round($hardCeilCents * $scoreFactor);

        // Apply declared-only penalty
        if ($isDeclaredOnly) {
            $modulated = (int) round($modulated * self::DECLARED_ONLY_SCALE);
        }

        // Uplift for high scorers (capped at hard ceiling)
        if ($blendedScore >= self::HIGH_SCORE_THRESHOLD) {
            $modulated = (int) min($hardCeilCents, round($modulated * self::HIGH_SCORE_UPLIFT));
        }

        // Final offer = min of affordability and modulated score offer
        $maxOfferCents = min($affordabilityCents, $modulated);

        // Ensure it doesn't exceed hard ceiling or go below floor
        $maxOfferCents = max($floorCents, min($hardCeilCents, $maxOfferCents));

        // ── 16. Check exposure limit (total outstanding loans) ────────────
        $exposureLimit = $tierRule
            ? (int) $tierRule->maximum_total_exposure_cents
            : (int) ($borrower->tier_max_exposure ?? 0);

        if ($exposureLimit > 0) {
            $currentExposure = (int) DB::table('loans')
                ->where('organisation_id', $organisationId)
                ->where('borrower_id', $borrowerId)
                ->whereIn('status', ['active', 'overdue', 'disbursement_pending'])
                ->sum('total_payable_cents');

            $headroom = max(0, $exposureLimit - $currentExposure);
            if ($headroom <= 0) {
                $reasons[] = sprintf(
                    'Borrower has reached the maximum total exposure of KSh %s.',
                    number_format($exposureLimit / 100, 2)
                );
                return self::buildResult(
                    eligible:       false,
                    maxAmountCents: 0,
                    maxTermDays:    0,
                    blendedScore:   $blendedScore,
                    financialScore: $financialScore,
                    behaviourScore: $behaviourScore,
                    kycStatus:      $kycStatus,
                    tierName:       $borrower->tier_name ?? 'No Tier',
                    tierRuleId:     $ruleTierRuleId,
                    tierRuleVersion: $ruleTierRuleVer,
                    reasons:        $reasons,
                    warnings:       $warnings,
                    snapshot:       self::buildSnapshot(
                        $borrower, $kyc, $fp, $product, $tierRule,
                        $blendedScore, $financialScore, $behaviourScore,
                        $kycStatus, $isDeclaredOnly, $reasons, $warnings
                    ),
                );
            }
            if ($maxOfferCents > $headroom) {
                $maxOfferCents = $headroom;
                $warnings[]    = sprintf(
                    'Offer capped at KSh %s due to existing loan exposure.',
                    number_format($headroom / 100, 2)
                );
            }
        }

        // ── 17. Determine term ────────────────────────────────────────────
        $tierMaxTerm    = (int) ($borrower->tier_max_term ?? $product->maximum_term_days);
        $ruleMaxTerm    = $tierRule ? (int) $tierRule->maximum_term_days : $tierMaxTerm;
        $productMaxTerm = (int) $product->maximum_term_days;
        $maxTermDays    = min($tierMaxTerm, $ruleMaxTerm, $productMaxTerm);

        // ── 18. Build positive result ─────────────────────────────────────
        $snapshot = self::buildSnapshot(
            $borrower, $kyc, $fp, $product, $tierRule,
            $blendedScore, $financialScore, $behaviourScore,
            $kycStatus, $isDeclaredOnly, $reasons, $warnings,
            [
                'hard_ceiling_cents'      => $hardCeilCents,
                'affordability_cents'     => $affordabilityCents,
                'score_factor'            => $scoreFactor,
                'modulated_cents'         => $modulated,
                'max_offer_cents'         => $maxOfferCents,
                'declared_only'           => $isDeclaredOnly,
            ]
        );

        return self::buildResult(
            eligible:       true,
            maxAmountCents: $maxOfferCents,
            maxTermDays:    $maxTermDays,
            blendedScore:   $blendedScore,
            financialScore: $financialScore,
            behaviourScore: $behaviourScore,
            kycStatus:      $kycStatus,
            tierName:       $borrower->tier_name ?? 'No Tier',
            tierRuleId:     $ruleTierRuleId,
            tierRuleVersion: $ruleTierRuleVer,
            reasons:        $reasons,
            warnings:       $warnings,
            snapshot:       $snapshot,
        );
    }

    // ── Score helpers ────────────────────────────────────────────────────

    /**
     * Blend financial and behaviour scores.
     * New borrowers (behaviour_score = 0 or null) have behaviour score treated as neutral.
     * Financial score is given 60 % weight; behaviour 40 %.
     */
    private static function computeBlendedScore(?float $financialScore, ?float $behaviourScore, bool $isDeclaredOnly): float
    {
        // Neutral baseline if either score is missing
        $fs = $financialScore ?? 0.40; // conservative neutral for missing financial data
        $bs = $behaviourScore ?? 0.50; // neutral for brand-new borrowers

        // When financial data is declared only, reduce its effective contribution
        if ($isDeclaredOnly) {
            $fs *= 0.80;
        }

        $blended = ($fs * 0.60) + ($bs * 0.40);
        return round(min(1.0, max(0.0, $blended)), 4);
    }

    /**
     * Map blended score to a loan offer factor (0–1 scalar on the hard ceiling).
     *
     * Score bands:
     *  ≥ 0.80 → 100 % of ceiling
     *  0.60–0.79 → 80 %
     *  0.40–0.59 → 60 %
     *  0.20–0.39 → 40 %
     *  < 0.20 → 20 %
     */
    private static function computeScoreFactor(float $blendedScore): float
    {
        return match (true) {
            $blendedScore >= 0.80 => 1.00,
            $blendedScore >= 0.60 => 0.80,
            $blendedScore >= 0.40 => 0.60,
            $blendedScore >= 0.20 => 0.40,
            default               => 0.20,
        };
    }

    /**
     * Compute affordability ceiling from the financial profile.
     *
     * Monthly instalment capacity = discretionary × MAX_INSTALMENT_TO_DISCRETIONARY
     * Monthly instalment ≈ (principal × COST_MULTIPLIER) / (term_months)
     * → max_principal = (monthly_capacity × term_months) / COST_MULTIPLIER
     *
     * Falls back to hard ceiling when no profile data is available.
     */
    private static function computeAffordabilityCents(?object $fp, object $product, int $hardCeilCents): int
    {
        if (!$fp || (int) $fp->average_monthly_discretionary_cashflow_cents <= 0) {
            // No discretionary data — use 25 % of hard ceiling as conservative fallback
            return max(self::ABSOLUTE_MIN_CENTS, (int) round($hardCeilCents * 0.25));
        }

        $discretionaryCents   = (int) $fp->average_monthly_discretionary_cashflow_cents;
        $monthlyCapacityCents = $discretionaryCents * self::MAX_INSTALMENT_TO_DISCRETIONARY;

        // Use product's maximum term to compute maximum principal
        $termMonths        = max(1, (int) $product->maximum_term_days / 30);
        $maxPrincipalCents = (int) round(($monthlyCapacityCents * $termMonths) / self::COST_MULTIPLIER);

        // Sanity bounds: at least ABSOLUTE_MIN_CENTS, at most hard ceiling
        return max(self::ABSOLUTE_MIN_CENTS, min($hardCeilCents, $maxPrincipalCents));
    }

    // ── Result builders ──────────────────────────────────────────────────

    private static function ineligible(
        string $reason,
        array $reasons,
        array $warnings,
        array $extra,
        string $kycStatus = 'unknown',
        string $tierName  = 'No Tier Assigned',
        ?float $behaviourScore = null,
        ?float $financialScore = null,
    ): object {
        return (object) [
            'eligible'        => false,
            'maxAmountCents'  => 0,
            'maxTermDays'     => 0,
            'blendedScore'    => 0.0,
            'financialScore'  => $financialScore,
            'behaviourScore'  => $behaviourScore,
            'kycStatus'       => $kycStatus,
            'tierName'        => $tierName,
            'tierRuleId'      => null,
            'tierRuleVersion' => null,
            'reasons'         => $reasons ?: [$reason],
            'warnings'        => $warnings,
            'snapshot'        => ['error' => $reason, 'extra' => $extra],
        ];
    }

    private static function buildResult(
        bool $eligible, int $maxAmountCents, int $maxTermDays,
        float $blendedScore, ?float $financialScore, ?float $behaviourScore,
        string $kycStatus, string $tierName,
        ?int $tierRuleId, ?int $tierRuleVersion,
        array $reasons, array $warnings, array $snapshot
    ): object {
        return (object) [
            'eligible'        => $eligible,
            'maxAmountCents'  => $maxAmountCents,
            'maxTermDays'     => $maxTermDays,
            'blendedScore'    => $blendedScore,
            'financialScore'  => $financialScore,
            'behaviourScore'  => $behaviourScore,
            'kycStatus'       => $kycStatus,
            'tierName'        => $tierName,
            'tierRuleId'      => $tierRuleId,
            'tierRuleVersion' => $tierRuleVersion,
            'reasons'         => $reasons,
            'warnings'        => $warnings,
            'snapshot'        => $snapshot,
        ];
    }

    private static function buildSnapshot(
        object $borrower, ?object $kyc, ?object $fp,
        object $product, ?object $tierRule,
        float $blendedScore, ?float $financialScore, ?float $behaviourScore,
        string $kycStatus, bool $isDeclaredOnly,
        array $reasons, array $warnings,
        array $computationDetail = []
    ): array {
        return [
            'appraisal_model_version' => 'appraisal-engine-v1',
            'appraised_at'            => now()->toIso8601String(),
            'borrower' => [
                'id'                        => $borrower->id,
                'name'                      => $borrower->borrower_name,
                'account_status'            => $borrower->account_status,
                'tier_id'                   => $borrower->tier_id,
                'tier_name'                 => $borrower->tier_name,
                'tier_number'               => $borrower->tier_number,
                'behaviour_score'           => $borrower->behaviour_score,
                'lifetime_loans_count'      => $borrower->lifetime_loans_count,
                'successful_loans_count'    => $borrower->successful_loans_count,
                'on_time_loans_count'       => $borrower->on_time_loans_count,
                'consecutive_on_time_loans' => $borrower->consecutive_on_time_loans,
                'late_loans_count'          => $borrower->late_loans_count,
                'defaulted_loans_count'     => $borrower->defaulted_loans_count,
            ],
            'kyc' => [
                'status'        => $kycStatus,
                'document_type' => $kyc?->document_type,
                'verified_at'   => $kyc?->verified_at,
            ],
            'financial_profile' => $fp ? [
                'id'                                   => $fp->id,
                'model_version'                        => $fp->model_version,
                'is_declared_only'                     => $isDeclaredOnly,
                'financial_score'                      => $fp->financial_score,
                'income_stability_score'               => $fp->income_stability_score,
                'cashflow_score'                       => $fp->cashflow_score,
                'debt_service_ratio'                   => $fp->debt_service_ratio,
                'avg_monthly_income_kes'               => round((int)$fp->average_monthly_income_cents / 100, 2),
                'avg_monthly_expense_kes'              => round((int)$fp->average_monthly_expense_cents / 100, 2),
                'avg_monthly_net_cashflow_kes'         => round((int)$fp->average_monthly_net_cashflow_cents / 100, 2),
                'avg_monthly_discretionary_kes'        => round((int)$fp->average_monthly_discretionary_cashflow_cents / 100, 2),
                'avg_monthly_debt_service_kes'         => round((int)$fp->average_monthly_debt_service_cents / 100, 2),
                'avg_monthly_salary_kes'               => round((int)$fp->average_monthly_salary_income_cents / 100, 2),
                'avg_monthly_business_kes'             => round((int)$fp->average_monthly_business_income_cents / 100, 2),
                'analysis_months'                      => $fp->analysis_months,
                'calculated_at'                        => $fp->calculated_at,
            ] : null,
            'product' => [
                'id'                   => $product->id,
                'code'                 => $product->code,
                'name'                 => $product->name,
                'requires_appraisal'   => $product->requires_appraisal,
                'requires_statement'   => $product->requires_statement,
                'maximum_amount_cents' => $product->maximum_amount_cents,
                'minimum_amount_cents' => $product->minimum_amount_cents,
                'maximum_term_days'    => $product->maximum_term_days,
            ],
            'tier_rule' => $tierRule ? [
                'id'                              => $tierRule->id,
                'version'                         => $tierRule->version,
                'maximum_amount_cents'            => $tierRule->maximum_amount_cents,
                'minimum_amount_cents'            => $tierRule->minimum_amount_cents,
                'maximum_term_days'               => $tierRule->maximum_term_days,
                'minimum_financial_score'         => $tierRule->minimum_financial_score,
                'minimum_behaviour_score'         => $tierRule->minimum_behaviour_score,
                'minimum_successful_loans'        => $tierRule->minimum_successful_loans,
                'minimum_on_time_loans'           => $tierRule->minimum_on_time_loans,
                'minimum_consecutive_on_time_loans'=> $tierRule->minimum_consecutive_on_time_loans,
            ] : null,
            'scores' => [
                'financial_score'  => $financialScore,
                'behaviour_score'  => $behaviourScore,
                'blended_score'    => $blendedScore,
            ],
            'computation' => $computationDetail,
            'reasons'     => $reasons,
            'warnings'    => $warnings,
        ];
    }
}
