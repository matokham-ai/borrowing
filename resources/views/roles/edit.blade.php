@extends('layouts.app')
@section('title', 'Edit Role')

@section('content')
<div class="page-header">
    <div>
        <div class="eyebrow">ADMINISTRATION · ROLES</div>
        <h1>Edit Role</h1>
        <p>Update permissions for <strong>{{ $role->display_name }}</strong>.</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('roles.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
</div>

@if(session('success'))
    <div class="alert-banner success"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
@endif

<div class="form-layout">
    <form method="POST" action="{{ route('roles.update', $role) }}">
        @csrf @method('PUT')
        @include('roles._form', ['assigned' => old('permissions', $assigned)])
        <div class="form-actions">
            <a href="{{ route('roles.index') }}" class="btn btn-light">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</div>
@endsection
