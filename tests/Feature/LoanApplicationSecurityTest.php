<?php

namespace Tests\Feature;

use App\Models\Borrower;
use App\Models\Branch;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanProduct;
use App\Models\Organisation;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Tests\TestCase;

class LoanApplicationSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_screen_displays_the_guided_security_flow(): void
    {
        [$user] = $this->makeLoanOfficer();

        $this->actingAs($user)
            ->get(route('applications.create'))
            ->assertOk()
            ->assertSee('New Loan Application')
            ->assertSee(route('applications.create'))
            ->assertSee('data-step="1"', false)
            ->assertSee('data-step="4" hidden', false)
            ->assertSee('Borrower & Loan')
            ->assertSee('Guarantor identification')
            ->assertSee('Collateral assets')
            ->assertSee('Review and submit')
            ->assertSee('Save draft')
            ->assertSee('Selected borrower details')
            ->assertSee('data-borrower-detail="residence_address"', false)
            ->assertSee('aria-controls="borrower_options">', false)
            ->assertSee('aria-controls="loan_product_options">', false)
            ->assertSee('<select id="borrower_id" name="borrower_id" class="form-input" required>', false)
            ->assertSee('<select id="loan_product_id" name="loan_product_id" class="form-input" required>', false);
    }

    public function test_eligibility_lookup_returns_selected_borrower_contact_and_residence_details(): void
    {
        [$user, $organisation, $borrower] = $this->makeLoanOfficerWithApplicationContext();
        DB::table('system_events')->insert([
            'organisation_id' => $organisation->id,
            'event_type' => 'borrower_profile_created',
            'aggregate_type' => 'borrower',
            'aggregate_id' => $borrower->id,
            'event_id' => (string) Str::uuid(),
            'payload' => json_encode([
                'residence_county' => 'Nairobi',
                'residence_address' => 'Westlands, Nairobi',
            ]),
            'status' => 'processed',
            'occurred_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('applications.eligibility', $borrower))
            ->assertOk()
            ->assertJsonPath('borrower_details.name', 'Test Borrower')
            ->assertJsonPath('borrower_details.phone', '254712345678')
            ->assertJsonPath('borrower_details.email', 'borrower@example.test')
            ->assertJsonPath('borrower_details.residence_county', 'Nairobi')
            ->assertJsonPath('borrower_details.residence_address', 'Westlands, Nairobi');
    }

    public function test_draft_persists_encrypted_guarantor_details(): void
    {
        [$user, $organisation, $borrower, $product] = $this->makeLoanOfficerWithApplicationContext();

        $this->actingAs($user)
            ->post(route('applications.store'), [
                'submit_mode' => 'draft',
                'borrower_id' => $borrower->id,
                'loan_product_id' => $product->id,
                'requested_amount' => '1000',
                'requested_term_days' => 14,
                'guarantor' => [
                    'name' => 'Amina Test Guarantor',
                    'national_id' => '12345678',
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $application = LoanApplication::withoutGlobalScopes()
            ->where('organisation_id', $organisation->id)
            ->firstOrFail();

        $this->assertSame('draft', $application->status);
        $this->assertSame('Amina Test Guarantor', $application->security_data['guarantor']['name']);
        $this->assertSame('12345678', $application->security_data['guarantor']['national_id']);
        $this->assertTrue($application->security_requirements_required);

        $storedSecurityData = DB::table('loan_applications')->where('id', $application->id)->value('security_data');
        $this->assertStringNotContainsString('12345678', $storedSecurityData);

        $this->get(route('applications.edit', $application))
            ->assertOk()
            ->assertSee('Amina Test Guarantor');
    }

    public function test_submission_requires_guarantor_details_documents_and_consent(): void
    {
        [$user, $organisation, $borrower, $product] = $this->makeLoanOfficerWithApplicationContext();

        $this->actingAs($user)
            ->from(route('applications.create'))
            ->post(route('applications.store'), [
                'submit_mode' => 'submit',
                'borrower_id' => $borrower->id,
                'loan_product_id' => $product->id,
                'requested_amount' => '1000',
                'requested_term_days' => 14,
            ])
            ->assertRedirect(route('applications.create'))
            ->assertSessionHasErrors([
                'guarantor.name',
                'guarantor.national_id',
                'guarantor_photo',
                'guarantor_id_front',
                'guarantor_consent',
            ]);

        $this->assertDatabaseMissing('loan_applications', ['organisation_id' => $organisation->id]);
    }

    public function test_complete_submission_saves_documents_and_repeatable_collateral_privately(): void
    {
        [$user, $organisation, $borrower, $product] = $this->makeLoanOfficerWithApplicationContext();
        DB::table('kyc_records')->insert([
            'organisation_id' => $organisation->id,
            'borrower_id' => $borrower->id,
            'document_type' => 'national_id',
            'verification_status' => 'verified',
            'verified_at' => now(),
            'is_current' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Storage::fake('local');

        $this->actingAs($user)
            ->post(route('applications.store'), [
                'submit_mode' => 'submit',
                'borrower_id' => $borrower->id,
                'loan_product_id' => $product->id,
                'requested_amount' => '1000',
                'requested_term_days' => 14,
                'guarantor' => [
                    'name' => 'Amina Test Guarantor',
                    'national_id' => '87654321',
                    'phone' => '0712345678',
                    'relationship' => 'Sibling',
                    'address' => 'Nairobi, Kenya',
                ],
                'guarantor_photo' => UploadedFile::fake()->image('guarantor.jpg'),
                'guarantor_id_front' => UploadedFile::fake()->image('id-front.png'),
                'guarantor_consent' => '1',
                'collateral_present' => '1',
                'collateral' => [[
                    'type' => 'Laptop',
                    'description' => 'Business laptop',
                    'owner' => 'Amina Test Guarantor',
                    'reference' => 'LT-2048',
                    'estimated_value' => '45000',
                    'location' => 'Nairobi',
                    'field_visit_date' => '2026-10-16',
                    'condition' => 'Good working condition',
                    'ownership_evidence' => 'Original receipt inspected',
                    'notes' => 'Good condition',
                    'photos' => [UploadedFile::fake()->image('laptop.webp')],
                    'captions' => ['Left side'],
                ]],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $application = LoanApplication::withoutGlobalScopes()
            ->where('organisation_id', $organisation->id)
            ->firstOrFail();
        $security = $application->security_data;

        $this->assertContains($application->status, ['submitted', 'under_review']);
        $this->assertNotNull($application->guarantor_consent_at);
        $this->get(route('applications.show', $application))
            ->assertOk()
            ->assertSee('Loan progress')
            ->assertSee('Supervisor review')
            ->assertSee('Admin approval')
            ->assertSee('Disbursement')
            ->assertSee('in the supervisor review queue');
        $this->assertSame(4500000, $security['collateral'][0]['estimated_value_cents']);
        $this->assertSame('Original receipt inspected', $security['collateral'][0]['ownership_evidence']);
        Storage::disk('local')->assertExists($security['guarantor']['photo']);
        Storage::disk('local')->assertExists($security['guarantor']['id_front']);
        Storage::disk('local')->assertExists($security['collateral'][0]['photos'][0]['path']);
        $this->assertSame('Left side', $security['collateral'][0]['photos'][0]['caption']);

        $application->update([
            'status' => 'approved',
            'decision' => 'approved',
            'approved_amount_cents' => $application->requested_amount_cents,
            'approved_term_days' => $application->requested_term_days,
        ]);
        $this->get(route('applications.show', $application))
            ->assertOk()
            ->assertSee('An administrator will initiate and confirm disbursement.')
            ->assertDontSee('Disburse via M-Pesa');
        $this->post(route('applications.disburse', $application))->assertForbidden();
    }

    public function test_first_level_approval_is_blocked_when_security_is_incomplete(): void
    {
        [$user, $organisation, $borrower, $product] = $this->makeLoanOfficerWithApplicationContext();
        $supervisorRole = Role::where('organisation_id', $organisation->id)
            ->where('name', 'supervisor')->firstOrFail();
        $supervisor = User::create([
            'organisation_id' => $organisation->id,
            'branch_id' => $user->branch_id,
            'name' => 'Test Supervisor',
            'email' => 'supervisor@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $supervisor->roles()->syncWithPivotValues([$supervisorRole->id], ['organisation_id' => $organisation->id]);
        $application = LoanApplication::withoutGlobalScopes()->create([
            'organisation_id' => $organisation->id,
            'branch_id' => $user->branch_id,
            'created_by' => $user->id,
            'borrower_id' => $borrower->id,
            'loan_product_id' => $product->id,
            'requested_amount_cents' => 150000,
            'requested_term_days' => 14,
            'status' => 'submitted',
            'security_requirements_required' => true,
        ]);

        $this->actingAs($supervisor)
            ->from(route('applications.show', $application))
            ->post(route('applications.first-approve', $application), ['decision_reason' => 'Checked documents'])
            ->assertRedirect(route('applications.show', $application))
            ->assertSessionHasErrors('application');

        $this->assertSame('submitted', $application->fresh()->status);
    }

    public function test_approval_requires_separate_supervisor_and_final_approver(): void
    {
        [$officer, $organisation, $borrower, $product] = $this->makeLoanOfficerWithApplicationContext();
        $supervisorRole = Role::where('organisation_id', $organisation->id)
            ->where('name', 'supervisor')->firstOrFail();
        $supervisor = User::create([
            'organisation_id' => $organisation->id,
            'branch_id' => $officer->branch_id,
            'name' => 'Workflow Supervisor',
            'email' => 'workflow-supervisor@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $supervisor->roles()->syncWithPivotValues([$supervisorRole->id], ['organisation_id' => $organisation->id]);
        $adminRole = Role::create([
            'organisation_id' => $organisation->id,
            'name' => 'admin',
            'display_name' => 'Admin',
            'is_system' => true,
            'is_active' => true,
        ]);
        $adminRole->permissions()->sync([
            Permission::where('name', 'loans.approve.final')->value('id'),
        ]);
        $supervisor->roles()->syncWithPivotValues(
            [$supervisorRole->id, $adminRole->id],
            ['organisation_id' => $organisation->id]
        );
        $application = LoanApplication::withoutGlobalScopes()->create([
            'organisation_id' => $organisation->id,
            'branch_id' => $officer->branch_id,
            'created_by' => $officer->id,
            'borrower_id' => $borrower->id,
            'loan_product_id' => $product->id,
            'requested_amount_cents' => 100000,
            'requested_term_days' => 14,
            'status' => 'submitted',
            'security_requirements_required' => true,
        ]);
        Storage::fake('local');
        $photo = UploadedFile::fake()->image('workflow-guarantor.jpg')->store('security/workflow', 'local');
        $idFront = UploadedFile::fake()->image('workflow-id-front.jpg')->store('security/workflow', 'local');
        $application->update([
            'security_data' => [
                'guarantor' => [
                    'name' => 'Amina Test Guarantor',
                    'national_id' => '12345678',
                    'phone' => '0712345678',
                    'relationship' => 'Sibling',
                    'address' => 'Nairobi',
                    'photo' => $photo,
                    'id_front' => $idFront,
                ],
                'collateral' => [],
            ],
            'guarantor_consent_at' => now(),
        ]);
        DB::table('kyc_records')->insert([
            'organisation_id' => $organisation->id,
            'borrower_id' => $borrower->id,
            'document_type' => 'national_id',
            'verification_status' => 'verified',
            'verified_at' => now(),
            'is_current' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($supervisor)
            ->post(route('applications.first-approve', $application), [
                'decision_reason' => 'Documents and field evidence reviewed.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $application->refresh();
        $this->assertSame('first_level_approved', $application->status);
        $this->assertSame($supervisor->id, $application->first_level_approved_by);

        $this->post(route('applications.approve', $application))
            ->assertForbidden();
        $this->assertSame('first_level_approved', $application->fresh()->status);

        $finalApprover = User::create([
            'organisation_id' => $organisation->id,
            'branch_id' => $officer->branch_id,
            'name' => 'Final Approver',
            'email' => 'final-approver@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $finalApprover->roles()->syncWithPivotValues([$adminRole->id], ['organisation_id' => $organisation->id]);

        $this->actingAs($finalApprover)
            ->post(route('applications.approve', $application))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('approved', $application->fresh()->status);
        $this->assertSame($finalApprover->id, $application->fresh()->final_approved_by);
    }

    public function test_application_details_are_scoped_to_officer_and_supervisor_branch(): void
    {
        [$officer, $organisation, $borrower, $product] = $this->makeLoanOfficerWithApplicationContext();
        $application = LoanApplication::withoutGlobalScopes()->create([
            'organisation_id' => $organisation->id,
            'branch_id' => $officer->branch_id,
            'created_by' => $officer->id,
            'borrower_id' => $borrower->id,
            'loan_product_id' => $product->id,
            'requested_amount_cents' => 150000,
            'requested_term_days' => 14,
            'status' => 'submitted',
        ]);
        $otherBranch = Branch::create([
            'organisation_id' => $organisation->id,
            'name' => 'Other Branch',
            'code' => 'OTHER',
            'is_active' => true,
        ]);
        $otherOfficer = User::create([
            'organisation_id' => $organisation->id,
            'branch_id' => $otherBranch->id,
            'name' => 'Other Officer',
            'email' => 'other-officer@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $officerRole = Role::where('organisation_id', $organisation->id)
            ->where('name', 'loan-officer')->firstOrFail();
        $otherOfficer->roles()->syncWithPivotValues([$officerRole->id], ['organisation_id' => $organisation->id]);
        $supervisor = User::create([
            'organisation_id' => $organisation->id,
            'branch_id' => $otherBranch->id,
            'name' => 'Other Branch Supervisor',
            'email' => 'other-supervisor@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $supervisorRole = Role::where('organisation_id', $organisation->id)
            ->where('name', 'supervisor')->firstOrFail();
        $supervisorRole->permissions()->syncWithoutDetaching([
            Permission::firstOrCreate(
                ['name' => 'view-borrowers'],
                ['display_name' => 'View Borrowers', 'is_system' => true, 'is_active' => true]
            )->id,
        ]);
        $supervisor->roles()->syncWithPivotValues([$supervisorRole->id], ['organisation_id' => $organisation->id]);

        $this->actingAs($otherOfficer)
            ->get(route('applications.show', $application))
            ->assertNotFound();
        $this->actingAs($supervisor)
            ->get(route('applications.show', $application))
            ->assertNotFound();
        $this->get(route('borrowers.show', $borrower))
            ->assertNotFound();
    }

    public function test_kyc_requires_a_different_authorized_verifier_than_the_registrant(): void
    {
        [$officer, $organisation, $borrower] = $this->makeLoanOfficerWithApplicationContext();
        $borrower->update(['registered_by' => $officer->id]);
        DB::table('kyc_records')->insert([
            'organisation_id' => $organisation->id,
            'borrower_id' => $borrower->id,
            'document_type' => 'national_id',
            'verification_status' => 'pending',
            'is_current' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($officer)
            ->post(route('kyc.verify', $borrower), [
                'verification_status' => 'verified',
                'verification_notes' => 'Looks valid',
            ])
            ->assertForbidden();

        $supervisorRole = Role::where('organisation_id', $organisation->id)
            ->where('name', 'supervisor')->firstOrFail();
        $supervisor = User::create([
            'organisation_id' => $organisation->id,
            'branch_id' => $officer->branch_id,
            'name' => 'KYC Supervisor',
            'email' => 'kyc-supervisor@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $supervisor->roles()->syncWithPivotValues([$supervisorRole->id], ['organisation_id' => $organisation->id]);

        $this->actingAs($supervisor)
            ->post(route('kyc.verify', $borrower), [
                'verification_status' => 'verified',
                'verification_notes' => 'Identity confirmed independently',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('kyc_records', [
            'organisation_id' => $organisation->id,
            'borrower_id' => $borrower->id,
            'verification_status' => 'verified',
        ]);
    }

    public function test_disbursement_cannot_bypass_the_two_approval_stages(): void
    {
        [, $organisation, $borrower, $product] = $this->makeLoanOfficerWithApplicationContext();
        $adminRole = Role::create([
            'organisation_id' => $organisation->id,
            'name' => 'admin',
            'display_name' => 'Admin',
            'is_system' => true,
            'is_active' => true,
        ]);
        $disbursePermission = Permission::firstOrCreate(
            ['name' => 'loans.disburse'],
            ['display_name' => 'Disburse approved loans', 'is_system' => true, 'is_active' => true]
        );
        $adminRole->permissions()->sync([$disbursePermission->id]);
        $admin = User::create([
            'organisation_id' => $organisation->id,
            'name' => 'Disbursement Admin',
            'email' => 'disbursement-admin@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $admin->roles()->syncWithPivotValues([$adminRole->id], ['organisation_id' => $organisation->id]);
        $application = LoanApplication::withoutGlobalScopes()->create([
            'organisation_id' => $organisation->id,
            'borrower_id' => $borrower->id,
            'loan_product_id' => $product->id,
            'requested_amount_cents' => 150000,
            'requested_term_days' => 14,
            'approved_amount_cents' => 150000,
            'approved_term_days' => 14,
            'status' => 'approved',
        ]);

        $this->actingAs($admin)
            ->post(route('applications.disburse', $application))
            ->assertUnprocessable();

        $this->assertDatabaseMissing('loans', ['loan_application_id' => $application->id]);
    }

    public function test_duplicate_guarantor_check_returns_only_a_match_warning(): void
    {
        [$user, $organisation, $borrower, $product] = $this->makeLoanOfficerWithApplicationContext();
        LoanApplication::withoutGlobalScopes()->create([
            'organisation_id' => $organisation->id,
            'borrower_id' => $borrower->id,
            'loan_product_id' => $product->id,
            'requested_amount_cents' => 150000,
            'requested_term_days' => 14,
            'status' => 'draft',
            'guarantor_id_fingerprint' => hash_hmac('sha256', '87654321', (string) config('app.key')),
        ]);

        $this->actingAs($user)
            ->postJson(route('applications.guarantor-match'), ['national_id' => '87654321'])
            ->assertOk()
            ->assertExactJson(['possible_match' => true])
            ->assertDontSee('87654321');
    }

    public function test_closed_loan_security_data_is_erased_after_seven_years(): void
    {
        [$user, $organisation, $borrower, $product] = $this->makeLoanOfficerWithApplicationContext();
        $application = LoanApplication::withoutGlobalScopes()->create([
            'organisation_id' => $organisation->id,
            'branch_id' => $user->branch_id,
            'created_by' => $user->id,
            'borrower_id' => $borrower->id,
            'loan_product_id' => $product->id,
            'requested_amount_cents' => 150000,
            'requested_term_days' => 14,
            'status' => 'loan_created',
            'security_data' => ['guarantor' => ['name' => 'Amina Test Guarantor']],
            'security_requirements_required' => true,
        ]);
        $loan = Loan::withoutGlobalScopes()->create([
            'organisation_id' => $organisation->id,
            'borrower_id' => $borrower->id,
            'loan_application_id' => $application->id,
            'loan_product_id' => $product->id,
            'principal_cents' => 150000,
            'interest_rate' => 0.05,
            'interest_cents' => 7500,
            'fees_cents' => 0,
            'total_payable_cents' => 157500,
            'term_days' => 14,
            'repayment_frequency' => 'monthly',
            'status' => 'active',
        ]);
        $closedAt = now()->subYears(8)->startOfSecond();
        $loan->update(['status' => 'completed', 'completed_at' => $closedAt]);
        $application->refresh();
        $this->assertNotNull($application->security_retention_until);
        $this->assertTrue($application->security_retention_until->equalTo(
            $closedAt->copy()->addYears(LoanApplication::SECURITY_DATA_RETENTION_YEARS)
        ));

        Storage::fake('local');
        $path = UploadedFile::fake()->image('guarantor.jpg')->store(
            "application-security/{$organisation->id}/{$application->id}",
            'local'
        );
        $application->update([
            'security_data' => ['guarantor' => ['name' => 'Amina Test Guarantor', 'photo' => $path]],
            'guarantor_id_fingerprint' => hash_hmac('sha256', '12345678', (string) config('app.key')),
            'guarantor_consent_at' => now()->subYears(8),
        ]);

        $this->actingAs($user)
            ->artisan('security:purge-expired-loan-data')
            ->expectsOutput('Purged 1 expired loan security record(s).')
            ->assertExitCode(0);

        $application->refresh();
        $this->assertNull($application->security_data);
        $this->assertNull($application->guarantor_id_fingerprint);
        $this->assertNull($application->guarantor_consent_at);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_private_security_file_is_only_served_from_the_authorized_application(): void
    {
        [$user, $organisation, $borrower, $product] = $this->makeLoanOfficerWithApplicationContext();
        Storage::fake('local');
        $application = LoanApplication::withoutGlobalScopes()->create([
            'organisation_id' => $organisation->id,
            'branch_id' => $user->branch_id,
            'created_by' => $user->id,
            'borrower_id' => $borrower->id,
            'loan_product_id' => $product->id,
            'requested_amount_cents' => 150000,
            'requested_term_days' => 14,
            'status' => 'submitted',
        ]);
        $path = UploadedFile::fake()->image('guarantor.png')->store(
            "application-security/{$organisation->id}/{$application->id}",
            'local'
        );
        $application->update(['security_data' => ['guarantor' => ['photo' => $path]]]);

        $this->actingAs($user)
            ->get(route('applications.show', $application))
            ->assertOk()
            ->assertSee('Guarantor and collateral')
            ->assertSee('View private image');

        $this->get(route('applications.security-file', [$application, 'guarantor-photo']))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private');

        [$otherUser] = $this->makeLoanOfficer();
        $this->actingAs($otherUser)
            ->get(route('applications.security-file', [$application, 'guarantor-photo']))
            ->assertNotFound();
    }

    private function makeLoanOfficerWithApplicationContext(): array
    {
        [$user, $organisation] = $this->makeLoanOfficer();
        $borrowerUser = User::create([
            'organisation_id' => $organisation->id,
            'name' => 'Test Borrower',
            'email' => 'borrower@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $borrower = Borrower::create([
            'organisation_id' => $organisation->id,
            'branch_id' => $user->branch_id,
            'user_id' => $borrowerUser->id,
            'phone_number' => '254712345678',
            'account_status' => 'active',
        ]);
        $product = LoanProduct::create([
            'organisation_id' => $organisation->id,
            'code' => 'TEST',
            'name' => 'Test Product',
            'minimum_amount_cents' => 100000,
            'maximum_amount_cents' => 500000,
            'minimum_term_days' => 7,
            'maximum_term_days' => 30,
            'interest_type' => 'flat',
            'interest_rate' => 0.05,
            'is_active' => true,
        ]);

        return [$user, $organisation, $borrower, $product];
    }

    private function makeLoanOfficer(): array
    {
        $suffix = Str::lower(Str::random(8));
        $organisation = Organisation::create([
            'name' => 'Loan Security Test',
            'slug' => 'loan-security-'.$suffix,
        ]);
        $user = User::create([
            'organisation_id' => $organisation->id,
            'branch_id' => Branch::where('organisation_id', $organisation->id)->where('code', 'MAIN')->value('id'),
            'name' => 'Test Loan Officer',
            'email' => "officer-{$suffix}@example.test",
            'password' => 'password',
            'is_active' => true,
        ]);
        $role = Role::create([
            'organisation_id' => $organisation->id,
            'name' => 'loan-officer',
            'display_name' => 'Loan Officer',
            'is_system' => true,
            'is_active' => true,
        ]);
        $permissionIds = collect([
            Permission::firstOrCreate(
                ['name' => 'create-loans'],
                ['display_name' => 'Create Loans', 'is_system' => true, 'is_active' => true]
            )->id,
            Permission::firstOrCreate(
                ['name' => 'loans.submit'],
                ['display_name' => 'Submit loan applications', 'is_system' => true, 'is_active' => true]
            )->id,
        ]);
        $role->permissions()->sync($permissionIds);
        $user->roles()->syncWithPivotValues([$role->id], ['organisation_id' => $organisation->id]);

        return [$user, $organisation];
    }
}
