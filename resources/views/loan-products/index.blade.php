@extends('layouts.app')

@section('title', 'Loan Products')

@section('content')
<style>
.lp-page{max-width:1540px;margin:auto;color:#101828}
.lp-hero{display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:25px}
.lp-eyebrow{font-size:10px;font-weight:800;letter-spacing:.15em;color:#7c6cf2;text-transform:uppercase}
.lp-title{font-size:28px;font-weight:800;letter-spacing:-1px;margin:6px 0}
.lp-subtitle{font-size:13px;color:#667085;margin:0}
.lp-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:10px 15px;border-radius:10px;font-size:12px;font-weight:700;text-decoration:none;border:1px solid #e4e7ec;cursor:pointer;transition:.2s}
.lp-btn-primary{background:#6554e8;color:#fff;border-color:#6554e8;box-shadow:0 4px 10px #6554e822}
.lp-btn-primary:hover{background:#5141d0;color:#fff;transform:translateY(-1px)}
.lp-btn-light{background:#fff;color:#344054}
.lp-btn-light:hover{background:#f9fafb;color:#101828}
.lp-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-bottom:23px}
.lp-stat{padding:20px;background:#fff;border:1px solid #eaecf0;border-radius:15px;box-shadow:0 4px 16px #10182805}
.lp-stat-top{display:flex;align-items:center;justify-content:space-between}
.lp-stat-label{font-size:12px;color:#667085;font-weight:600}
.lp-stat-icon{width:36px;height:36px;display:grid;place-items:center;border-radius:11px;background:#f0edff;color:#6554e8;font-size:17px}
.lp-stat-value{font-size:27px;font-weight:800;letter-spacing:-1px;margin-top:12px}
.lp-stat-note{font-size:11px;color:#98a2b3;margin-top:3px}
.lp-card{background:#fff;border:1px solid #e4e7ec;border-radius:16px;overflow:hidden;box-shadow:0 8px 26px #10182807}
.lp-card-head{display:flex;justify-content:space-between;align-items:center;gap:20px;padding:21px 23px;border-bottom:1px solid #eaecf0}
.lp-card-title{font-size:14px;font-weight:800;margin:0}
.lp-card-desc{font-size:11px;color:#98a2b3;margin-top:4px}
.lp-filters{display:flex;align-items:center;gap:9px}
.lp-input{height:39px;padding:0 12px;border:1px solid #d0d5dd;border-radius:9px;background:#fff;color:#344054;font-size:12px;outline:none;min-width:0}
.lp-input:focus{border-color:#8b7cf6;box-shadow:0 0 0 3px #6554e818}
.lp-search{width:220px}
.lp-select{min-width:125px}
.lp-table-wrap{overflow-x:auto}
.lp-table{width:100%;min-width:900px;border-collapse:collapse}
.lp-table th{background:#fcfcfd;color:#98a2b3;font-size:9px;font-weight:800;letter-spacing:.09em;text-transform:uppercase;padding:13px 21px;text-align:left;border-bottom:1px solid #eaecf0;white-space:nowrap}
.lp-table td{padding:17px 21px;border-bottom:1px solid #f0f2f5;font-size:12px;color:#475467;vertical-align:middle}
.lp-table tbody tr:last-child td{border-bottom:0}
.lp-table tbody tr:hover{background:#fcfcff}
.lp-product{display:flex;align-items:center;gap:12px;min-width:190px}
.lp-product-icon{height:39px;width:39px;display:grid;place-items:center;border-radius:12px;background:#f1efff;color:#6655e8;font-size:17px;flex-shrink:0}
.lp-product-name{font-size:12px;color:#101828;font-weight:750}
.lp-product-code{font-size:10px;color:#98a2b3;margin-top:4px}
.lp-amount{font-weight:750;color:#344054;white-space:nowrap}
.lp-muted{display:block;color:#98a2b3;font-size:10px;margin-top:4px}
.lp-status{display:inline-flex;align-items:center;gap:6px;padding:6px 9px;border-radius:99px;font-size:10px;font-weight:750;white-space:nowrap}
.lp-status:before{content:'';height:6px;width:6px;border-radius:50%;background:currentColor}
.lp-status-active{background:#ecfdf3;color:#027a48}
.lp-status-inactive{background:#f2f4f7;color:#667085}
.lp-actions{display:flex;align-items:center;gap:6px}
.lp-actions form{margin:0}
.lp-action{height:31px;width:32px;display:inline-grid;place-items:center;border:1px solid #eaecf0;background:#fff;border-radius:8px;color:#667085;text-decoration:none;cursor:pointer}
.lp-action:hover{color:#6554e8;background:#f8f7ff;border-color:#d9d3ff}
.lp-action-danger:hover{color:#d92d20;background:#fff5f4;border-color:#fecdca}
.lp-empty{text-align:center;padding:55px 20px}
.lp-empty i{font-size:28px;color:#98a2b3}
.lp-empty h3{font-size:14px;margin:12px 0 5px}
.lp-empty p{font-size:12px;color:#98a2b3}
.lp-flash{padding:12px 15px;border-radius:10px;font-size:12px;margin-bottom:16px}
.lp-success{background:#ecfdf3;color:#027a48;border:1px solid #abefc6}
.lp-error{background:#fef3f2;color:#b42318;border:1px solid #fecdca}
.lp-foot{padding:15px 22px;border-top:1px solid #eaecf0;font-size:12px;color:#667085}
.lp-foot nav[role=navigation] p{margin:0}
.lp-foot nav[role=navigation] a,.lp-foot nav[role=navigation] span{font-size:12px}
@media(max-width:900px){.lp-card-head{align-items:flex-start;flex-direction:column}.lp-filters{width:100%;flex-wrap:wrap}.lp-search{flex:1;width:auto}}
@media(max-width:600px){.lp-hero{align-items:flex-start;flex-direction:column}.lp-title{font-size:24px}.lp-stats{grid-template-columns:1fr}.lp-filters{align-items:stretch;flex-direction:column}.lp-search,.lp-select{width:100%}.lp-filters .lp-btn{width:100%}}
</style>

<div class="lp-page">
    <div class="lp-hero">
        <div>
            <div class="lp-eyebrow">Lending / Configuration</div>
            <h1 class="lp-title">Loan Products</h1>
            <p class="lp-subtitle">Configure lending offers, pricing and repayment terms for your organisation.</p>
        </div>
        <a href="{{ route('loan-products.create') }}" class="lp-btn lp-btn-primary">
            <i class="bi bi-plus-lg"></i> New loan product
        </a>
    </div>

    @if(session('success'))
        <div class="lp-flash lp-success"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="lp-flash lp-error"><i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}</div>
    @endif

    <div class="lp-stats">
        @foreach([
            ['Total products', $stats['total'], 'Configured lending products', 'bi-grid-1x2', ''],
            ['Active products', $stats['active'], 'Available for lending', 'bi-check-circle', 'background:#ecfdf3;color:#039855'],
            ['Inactive products', $stats['inactive'], 'Not currently offered', 'bi-pause-circle', 'background:#f2f4f7;color:#667085'],
        ] as [$label, $value, $note, $icon, $iconStyle])
            <div class="lp-stat">
                <div class="lp-stat-top">
                    <span class="lp-stat-label">{{ $label }}</span>
                    <span class="lp-stat-icon" @if($iconStyle) style="{{ $iconStyle }}" @endif><i class="bi {{ $icon }}"></i></span>
                </div>
                <div class="lp-stat-value">{{ number_format($value) }}</div>
                <div class="lp-stat-note">{{ $note }}</div>
            </div>
        @endforeach
    </div>

    <section class="lp-card">
        <div class="lp-card-head">
            <div>
                <h2 class="lp-card-title">Product catalogue</h2>
                <div class="lp-card-desc">{{ $products->total() }} matching products</div>
            </div>
            <form method="GET" action="{{ route('loan-products.index') }}" class="lp-filters">
                <input class="lp-input lp-search" type="search" name="search" value="{{ $search }}" placeholder="Search name or product code">
                <select class="lp-input lp-select" name="status" aria-label="Filter by status">
                    <option value="">All statuses</option>
                    <option value="active" @selected($status === 'active')>Active</option>
                    <option value="inactive" @selected($status === 'inactive')>Inactive</option>
                </select>
                <button class="lp-btn lp-btn-light" type="submit"><i class="bi bi-search"></i> Filter</button>
                @if($search || $status)
                    <a href="{{ route('loan-products.index') }}" class="lp-btn lp-btn-light">Reset</a>
                @endif
            </form>
        </div>

        <div class="lp-table-wrap">
            <table class="lp-table">
                <thead>
                    <tr><th>Product</th><th>Loan amount</th><th>Term</th><th>Interest</th><th>Repayment</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                @forelse($products as $product)
                    <tr>
                        <td>
                            <div class="lp-product">
                                <div class="lp-product-icon"><i class="bi bi-wallet2"></i></div>
                                <div>
                                    <div class="lp-product-name">{{ $product->name }}</div>
                                    <div class="lp-product-code">{{ $product->code }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="lp-amount">KSh {{ number_format($product->minimum_amount_cents / 100, 2) }} – {{ number_format($product->maximum_amount_cents / 100, 2) }}</span>
                            <span class="lp-muted">Minimum – maximum</span>
                        </td>
                        <td>{{ $product->minimum_term_days }}–{{ $product->maximum_term_days }} days</td>
                        <td>
                            <span class="lp-amount">{{ number_format($product->interest_rate * 100, 2) }}%</span>
                            <span class="lp-muted">{{ ucfirst(str_replace('_', ' ', $product->interest_period)) }} · {{ ucfirst(str_replace('_', ' ', $product->interest_type)) }}</span>
                        </td>
                        <td>{{ ucfirst(str_replace('_', ' ', $product->repayment_frequency)) }}</td>
                        <td><span class="lp-status {{ $product->is_active ? 'lp-status-active' : 'lp-status-inactive' }}">{{ $product->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td>
                            <div class="lp-actions">
                                <a class="lp-action" title="View product" aria-label="View {{ $product->name }}" href="{{ route('loan-products.show', $product) }}"><i class="bi bi-eye"></i></a>
                                <a class="lp-action" title="Edit product" aria-label="Edit {{ $product->name }}" href="{{ route('loan-products.edit', $product) }}"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('loan-products.toggle-status', $product) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="lp-action" type="submit" title="{{ $product->is_active ? 'Deactivate' : 'Activate' }} product" aria-label="{{ $product->is_active ? 'Deactivate' : 'Activate' }} {{ $product->name }}">
                                        <i class="bi {{ $product->is_active ? 'bi-pause' : 'bi-play' }}"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('loan-products.destroy', $product) }}" onsubmit="return confirm('Delete this product? Products referenced by lending records cannot be deleted.');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="lp-action lp-action-danger" type="submit" title="Delete product" aria-label="Delete {{ $product->name }}"><i class="bi bi-trash3"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="lp-empty">
                                <i class="bi bi-inbox"></i>
                                <h3>{{ $search || $status ? 'No matching products' : 'Your catalogue is ready to begin' }}</h3>
                                <p>{{ $search || $status ? 'Try changing your search or filters.' : 'Create your first lending product to get started.' }}</p>
                                @if(!$search && !$status)
                                    <a href="{{ route('loan-products.create') }}" class="lp-btn lp-btn-primary"><i class="bi bi-plus-lg"></i> Create product</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
            <div class="lp-foot">{{ $products->links() }}</div>
        @endif
    </section>
</div>
@endsection
