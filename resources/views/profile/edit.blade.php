@extends('layouts.app')
@section('title', 'My Profile')

@section('content')
@php
    $isSupervisor = auth()->user()->hasRole('supervisor') && !auth()->user()->hasRole(['admin', 'super-admin']);
    $isLoanOfficer = auth()->user()->hasRole('loan-officer') && !auth()->user()->hasRole(['admin', 'super-admin', 'supervisor']);
    $profileUpdateRoute = $isSupervisor
        ? 'supervisor.profile.update'
        : ($isLoanOfficer ? 'loan-officer.profile.update' : 'profile.update');
    $passwordUpdateRoute = $isSupervisor
        ? 'supervisor.profile.password'
        : ($isLoanOfficer ? 'loan-officer.profile.password' : 'profile.password');
@endphp
<div class="page-header">
    <div>
        <div class="eyebrow">ACCOUNT</div>
        <h1>My Profile</h1>
        <p>Manage your personal information and password.</p>
    </div>
</div>

@if(session('success'))
    <div class="alert-banner success"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
@endif

<div class="form-layout form-layout-narrow">

    {{-- Profile card --}}
    <div class="profile-hero panel">
        <div class="avatar avatar-xl">{{ strtoupper(substr($user->name, 0, 2)) }}</div>
        <div>
            <strong class="profile-hero-name">{{ $user->name }}</strong>
            <small>{{ $user->email }}</small>
            <div class="profile-hero-roles">
                @foreach($user->roles as $role)
                    <span class="tag">{{ $role->display_name }}</span>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Account info --}}
    <form method="POST" action="{{ route($profileUpdateRoute) }}">
        @csrf @method('PUT')
        <div class="form-card">
            <div class="form-card-head"><h2>Account Information</h2></div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}"
                           class="form-input @error('name') is-invalid @enderror" required>
                    @error('name')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}"
                           class="form-input @error('email') is-invalid @enderror" required>
                    @error('email')<span class="field-error">{{ $message }}</span>@enderror
                </div>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Profile</button>
        </div>
    </form>

    {{-- Password --}}
    <form method="POST" action="{{ route($passwordUpdateRoute) }}">
        @csrf @method('PUT')
        <div class="form-card">
            <div class="form-card-head"><h2>Change Password</h2></div>
            <div class="form-grid">
                <div class="form-group form-group-full">
                    <label>Current Password</label>
                    <input type="password" name="current_password"
                           class="form-input @error('current_password') is-invalid @enderror" required>
                    @error('current_password')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="password"
                           class="form-input @error('password') is-invalid @enderror"
                           placeholder="Min 8 chars, mixed case + numbers" required>
                    @error('password')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="password_confirmation" class="form-input" required>
                </div>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Password</button>
        </div>
    </form>

</div>
@endsection
