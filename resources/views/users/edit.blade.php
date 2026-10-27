@extends('layouts.app')
@section('title', 'Edit User')

@section('content')
<div class="page-header">
    <div>
        <div class="eyebrow">ADMINISTRATION · USERS</div>
        <h1>Edit User</h1>
        <p>Update account details and role assignments for {{ $user->name }}.</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('users.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
</div>

@if(session('success'))
    <div class="alert-banner success"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
@endif

<div class="form-layout">
    <form method="POST" action="{{ route('users.update', $user) }}">
        @csrf @method('PUT')

        <div class="form-card">
            <div class="form-card-head"><h2>Account Details</h2></div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-input @error('name') is-invalid @enderror" required>
                    @error('name')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-input @error('email') is-invalid @enderror" required>
                    @error('email')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label>New Password <span class="label-hint">(leave blank to keep current)</span></label>
                    <input type="password" name="password" class="form-input @error('password') is-invalid @enderror" placeholder="Min 8 chars, mixed case + numbers">
                    @error('password')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <label class="toggle-switch">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                        <span class="toggle-track"></span>
                        <span class="toggle-label">Active</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="form-card">
            <div class="form-card-head">
                <h2>Assign Roles</h2>
                <p>Select one or more roles for this user.</p>
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label for="branch_id">Branch assignment</label>
                    <select id="branch_id" name="branch_id" class="form-input">
                        <option value="">No branch (organization-wide access)</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected(old('branch_id', $user->branch_id) == $branch->id)>{{ $branch->name }} · {{ $branch->code }}</option>
                        @endforeach
                    </select>
                    <small class="form-hint">Loan officers and supervisors must be assigned to an active branch.</small>
                    @error('branch_id')<span class="field-error">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="roles-grid">
                @foreach($roles as $role)
                @php $checked = in_array($role->id, old('roles', $userRoles)); @endphp
                <label class="role-card {{ $checked ? 'selected' : '' }}">
                    <input type="checkbox" name="roles[]" value="{{ $role->id }}" {{ $checked ? 'checked' : '' }}>
                    <div class="role-card-body">
                        <div class="role-card-icon"><i class="bi bi-shield-lock"></i></div>
                        <div>
                            <strong>{{ $role->display_name }}</strong>
                            <small>{{ $role->description ?? 'No description' }}</small>
                        </div>
                    </div>
                    <div class="role-card-check"><i class="bi bi-check-lg"></i></div>
                </label>
                @endforeach
            </div>
        </div>

        <div class="form-actions">
            <a href="{{ route('users.index') }}" class="btn btn-light">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</div>
@endsection
