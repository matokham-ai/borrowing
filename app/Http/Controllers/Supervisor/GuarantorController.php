<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\LoanApplication;
use Illuminate\Http\Request;

class GuarantorController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $branchId = (int) ($user->branch_id ?? 0);
        $search = trim($request->string('search')->toString());

        $applications = LoanApplication::query()
            ->where('organisation_id', $user->organisation_id)
            ->where('branch_id', $branchId)
            ->whereNotNull('security_data')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('id', is_numeric($search) ? (int) $search : -1)
                    ->orWhereHas('borrower.user', fn ($borrower) => $borrower
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('loanProduct', fn ($product) => $product
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%"));
            }))
            ->with(['borrower.user', 'loanProduct'])
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        $guarantors = $applications->getCollection()
            ->filter(fn (LoanApplication $application) => filled(data_get($application->security_data, 'guarantor.name')))
            ->values();

        return view('supervisor.guarantors.index', compact('applications', 'guarantors', 'search'));
    }
}
