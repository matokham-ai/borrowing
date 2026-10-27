<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Organisation extends Model
{
    protected static function booted(): void
    {
        static::created(function (Organisation $organisation): void {
            $now = now();
            $permissions = [
                'view-dashboard',
                'view-borrowers',
                'create-borrowers',
                'view-loans',
                'create-loans',
                'borrowers.verify_kyc',
                'loans.submit',
                'loans.approve.first_level',
                'loans.return',
                'loans.reject',
                'collateral.verify',
            ];

            $role = $organisation->roles()->firstOrCreate(
                ['name' => 'supervisor'],
                [
                    'display_name' => 'Supervisor',
                    'description' => 'Verifies loan applications and provides first-level approval.',
                    'is_system' => true,
                    'is_active' => true,
                ]
            );
            $role->permissions()->syncWithoutDetaching(
                Permission::query()->whereIn('name', $permissions)->where('is_active', true)->pluck('id')
            );

            Branch::firstOrCreate(
                ['organisation_id' => $organisation->id, 'code' => 'MAIN'],
                ['name' => 'Main Branch', 'is_active' => true]
            );
        });
    }

    protected $fillable = [
        'name',
        'slug',
        'company_reg_number',
        'country_code',
        'currency',
        'timezone',
        'is_active',
        // Additional identity fields
        'trading_name',
        'organisation_type',
        'tax_pin',
        'phone',
        'email',
        'website',
        // Address fields
        'county',
        'town',
        'physical_address',
        // Branding fields
        'logo_path',
        'primary_brand_color',
        'secondary_brand_color',
        'use_org_branding',
        // Lending configuration defaults
        'default_loan_term',
        'default_repayment_frequency',
        'interest_calculation_method',
        'late_payment_handling',
        'grace_period',
        'maximum_active_loans',
        // General configuration
        'date_format',
        'financial_year_start',
        'financial_year_end',
        // Notification configuration
        'notifications_enabled',
        'sms_notifications_enabled',
        'email_notifications_enabled',
        'in_app_notifications_enabled',
        'notification_sender_name',
        'notification_reply_to_email',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'late_payment_handling' => 'boolean',
            'use_org_branding' => 'boolean',
            'notifications_enabled' => 'boolean',
            'sms_notifications_enabled' => 'boolean',
            'email_notifications_enabled' => 'boolean',
            'in_app_notifications_enabled' => 'boolean',
        ];
    }

    /**
     * Get the formatted organisation ID (accessor)
     */
    protected function getFormattedOrganisationIdAttribute(): string
    {
        return '#ORG-' . str_pad($this->id, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Get the full address as a formatted string (accessor)
     */
    protected function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->physical_address,
            $this->town,
            $this->county,
            $this->country_code,
        ]);

        return implode(', ', $parts);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function borrowers(): HasMany
    {
        return $this->hasMany(Borrower::class);
    }

    public function borrowerTiers(): HasMany
    {
        return $this->hasMany(BorrowerTier::class);
    }

    public function loanProducts(): HasMany
    {
        return $this->hasMany(LoanProduct::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function loanApplications(): HasMany
    {
        return $this->hasMany(LoanApplication::class);
    }

    public function mpesaSettings(): HasMany
    {
        return $this->hasMany(MpesaSetting::class);
    }

    public function mpesaTransactions(): HasMany
    {
        return $this->hasMany(MpesaTransaction::class);
    }
}
