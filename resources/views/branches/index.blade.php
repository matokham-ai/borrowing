@extends('layouts.app')
@section('title', 'Branches')

@section('content')
<div class="page-header">
    <div>
        <div class="eyebrow">ORGANISATION · ACCESS CONTROL</div>
        <h1>Branches</h1>
        <p>Organize staff and loan applications by branch.</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="form-layout" style="max-width:1120px;margin:0 var(--pg)">
    <form method="POST" action="{{ route('branches.store') }}" class="form-card">
        @csrf
        <div class="form-card-head"><h2>Create a branch</h2></div>
        <div class="form-grid">
            <div class="form-group">
                <label for="branch-name">Branch name</label>
                <input id="branch-name" class="form-input" name="name" value="{{ old('name') }}" required maxlength="120" placeholder="e.g. Westlands">
            </div>
            <div class="form-group">
                <label for="branch-code">Branch code</label>
                <input id="branch-code" class="form-input" name="code" value="{{ old('code') }}" required maxlength="40" placeholder="e.g. WESTLANDS">
            </div>
            <div class="form-group">
                <label for="branch-county">County <span class="label-hint">Optional</span></label>
                <input id="branch-county" class="form-input" name="county" value="{{ old('county') }}" maxlength="120">
            </div>
            <div class="form-group">
                <label for="branch-town">Town <span class="label-hint">Optional</span></label>
                <input id="branch-town" class="form-input" name="town" value="{{ old('town') }}" maxlength="120">
            </div>
            <div class="form-actions form-group-full">
                <button class="btn btn-primary" type="submit"><i class="bi bi-plus-lg"></i> Create branch</button>
            </div>
        </div>
    </form>

    <section class="panel">
        <div class="panel-header">
            <div><h2>Organization branches</h2><p>Staff access to applications follows their assigned branch.</p></div>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Branch</th><th>Location</th><th>Staff</th><th>Applications</th><th>Status</th><th>Manage</th></tr></thead>
                <tbody>
                @forelse($branches as $branch)
                    <tr>
                        <td>
                            <form method="POST" action="{{ route('branches.update', $branch) }}" class="branch-inline-form">
                                @csrf @method('PUT')
                                <input class="form-input" name="name" value="{{ $branch->name }}" aria-label="Branch name" required>
                                <input class="form-input branch-code" name="code" value="{{ $branch->code }}" aria-label="Branch code" required>
                                <input type="hidden" name="county" value="{{ $branch->county }}">
                                <input type="hidden" name="town" value="{{ $branch->town }}">
                                <button class="btn btn-light btn-sm" type="submit">Save</button>
                            </form>
                        </td>
                        <td>{{ collect([$branch->town, $branch->county])->filter()->join(', ') ?: '—' }}</td>
                        <td>{{ $branch->users_count }}</td>
                        <td>{{ $branch->loan_applications_count }}</td>
                        <td><span class="status-pill status-{{ $branch->is_active ? 'active' : 'inactive' }}">{{ $branch->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td>
                            <form method="POST" action="{{ route('branches.toggle-active', $branch) }}">
                                @csrf @method('PATCH')
                                <button class="btn btn-light btn-sm" type="submit">{{ $branch->is_active ? 'Deactivate' : 'Activate' }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty-state">No branches yet. Create one above, then assign staff to it.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<style>
.branch-inline-form{display:flex;align-items:center;gap:6px;min-width:320px}
.branch-inline-form .form-input{min-width:110px}
.branch-inline-form .branch-code{width:100px;flex:0 0 100px}
@media(max-width:760px){.branch-inline-form{align-items:stretch;flex-direction:column;min-width:190px}.branch-inline-form .form-input{width:100%}}
</style>
@endsection
