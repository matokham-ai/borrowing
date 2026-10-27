<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanProduct;
use App\Models\LoanTierRule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LoanProductController extends Controller
{
    private function organisationId(): int
    {
        $organisationId = request()->user()?->organisation_id;

        abort_if(!$organisationId, 403, 'No organisation is assigned to this account.');

        return (int) $organisationId;
    }

    private function products()
    {
        return LoanProduct::query()
            ->where('organisation_id', $this->organisationId());
    }

    public function index(Request $request)
    {
        $search = trim($request->string('search')->toString());
        $status = $request->string('status')->toString();

        $query = $this->products()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, ['active', 'inactive'], true), function ($query) use ($status) {
                $query->where('is_active', $status === 'active');
            });

        $base = $this->products();
        $stats = [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('is_active', true)->count(),
            'inactive' => (clone $base)->where('is_active', false)->count(),
        ];

        $products = $query->latest()->paginate(10)->withQueryString();

        return view('loan-products.index', compact('products', 'search', 'status', 'stats'));
    }

    public function create()
    {
        $product = new LoanProduct([
            'interest_type' => 'flat',
            'interest_period' => 'per_month',
            'interest_rate' => 0.05,
            'fee_type' => 'none',
            'fee_rate' => 0,
            'fixed_fee_cents' => 0,
            'repayment_frequency' => 'monthly',
            'grace_period_days' => 0,
            'requires_appraisal' => true,
            'requires_statement' => true,
            'is_active' => false,
        ]);

        return view('loan-products.form', compact('product'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $data['organisation_id'] = $this->organisationId();
        $data['version'] = 1;

        $product = $this->products()->create($data);

        $message = $request->boolean('is_active') && !$this->supportsActivePricing($product)
            ? 'Product created but kept inactive because its pricing method is not supported for live lending yet.'
            : 'Loan product created successfully.';

        return redirect()
            ->route('loan-products.index')
            ->with('success', $message);
    }

    public function show(LoanProduct $loan_product)
    {
        $this->assertOwnsProduct($loan_product);

        return view('loan-products.show', ['product' => $loan_product]);
    }

    public function edit(LoanProduct $loan_product)
    {
        $this->assertOwnsProduct($loan_product);

        return view('loan-products.form', ['product' => $loan_product]);
    }

    public function update(Request $request, LoanProduct $loan_product)
    {
        $this->assertOwnsProduct($loan_product);

        $data = $this->validatedData($request, $loan_product);
        $data['version'] = (int) $loan_product->version + 1;
        $loan_product->fill($data)->save();

        $message = $request->boolean('is_active') && !$this->supportsActivePricing($loan_product)
            ? 'Product saved but kept inactive because its pricing method is not supported for live lending yet.'
            : 'Loan product updated successfully.';

        return redirect()
            ->route('loan-products.index')
            ->with('success', $message);
    }

    public function toggleStatus(LoanProduct $loan_product)
    {
        $this->assertOwnsProduct($loan_product);

        if (!$loan_product->is_active && !$this->supportsActivePricing($loan_product)) {
            return redirect()
                ->route('loan-products.index')
                ->with('error', 'This product cannot be activated until its interest period and calculation method are supported.');
        }

        $loan_product->is_active = !$loan_product->is_active;
        $loan_product->version = (int) $loan_product->version + 1;
        $loan_product->save();

        return redirect()
            ->route('loan-products.index')
            ->with('success', 'Loan product ' . ($loan_product->is_active ? 'activated' : 'deactivated') . ' successfully.');
    }

    private function supportsActivePricing(LoanProduct $product): bool
    {
        return $product->interest_period === 'per_loan'
            && $product->interest_type === 'flat';
    }

    public function destroy(LoanProduct $loan_product)
    {
        $this->assertOwnsProduct($loan_product);

        $isInUse = LoanApplication::where('organisation_id', $loan_product->organisation_id)
            ->where('loan_product_id', $loan_product->id)
            ->exists()
            || Loan::where('organisation_id', $loan_product->organisation_id)
                ->where('loan_product_id', $loan_product->id)
                ->exists()
            || LoanTierRule::where('organisation_id', $loan_product->organisation_id)
                ->where('loan_product_id', $loan_product->id)
                ->exists();

        if ($isInUse) {
            return redirect()
                ->route('loan-products.index')
                ->with('error', 'This product is linked to lending records. Deactivate it instead of deleting it.');
        }

        $loan_product->delete();

        return redirect()
            ->route('loan-products.index')
            ->with('success', 'Loan product deleted successfully.');
    }

    private function assertOwnsProduct(LoanProduct $product): void
    {
        abort_unless(
            (int) $product->organisation_id === $this->organisationId(),
            404
        );
    }

    private function validatedData(Request $request, ?LoanProduct $product = null): array
    {
        $organisationId = $this->organisationId();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('loan_products', 'code')
                    ->where('organisation_id', $organisationId)
                    ->ignore($product?->id),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'minimum_amount' => ['required', 'numeric', 'min:0.01'],
            'maximum_amount' => ['required', 'numeric', 'gt:minimum_amount'],
            'minimum_term_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'maximum_term_days' => ['required', 'integer', 'gte:minimum_term_days', 'max:3650'],
            'interest_type' => ['required', Rule::in(['flat', 'reducing_balance'])],
            'interest_period' => ['required', Rule::in(['per_loan', 'per_month'])],
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'fee_type' => ['required', Rule::in(['none', 'percentage', 'fixed'])],
            'fee_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'fixed_fee' => ['nullable', 'numeric', 'min:0'],
            'repayment_frequency' => ['required', Rule::in(['daily', 'weekly', 'biweekly', 'monthly'])],
            'grace_period_days' => ['required', 'integer', 'min:0', 'max:365'],
            'requires_appraisal' => ['nullable', 'boolean'],
            'requires_statement' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['minimum_amount_cents'] = (int) round((float) $data['minimum_amount'] * 100);
        $data['maximum_amount_cents'] = (int) round((float) $data['maximum_amount'] * 100);
        $data['interest_rate'] = (float) $data['interest_rate'] / 100;
        $data['fixed_fee_cents'] = $data['fee_type'] === 'fixed'
            ? (int) round((float) ($data['fixed_fee'] ?? 0) * 100)
            : 0;
        $data['fee_rate'] = $data['fee_type'] === 'percentage'
            ? (float) ($data['fee_rate'] ?? 0) / 100
            : 0;
        $data['requires_appraisal'] = $request->boolean('requires_appraisal');
        $data['requires_statement'] = $request->boolean('requires_statement');
        $data['is_active'] = $request->boolean('is_active');
        if ($data['interest_period'] !== 'per_loan' || $data['interest_type'] !== 'flat') {
            $data['is_active'] = false;
        }

        unset($data['minimum_amount'], $data['maximum_amount'], $data['fixed_fee']);

        return $data;
    }
}
