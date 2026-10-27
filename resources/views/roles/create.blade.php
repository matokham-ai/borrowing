@extends('layouts.app')
@section('title', 'New Role')

@section('content')
<div class="page-header">
    <div>
        <div class="eyebrow">ADMINISTRATION · ROLES</div>
        <h1>New Role</h1>
        <p>Define a role and assign the permissions it grants.</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('roles.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
</div>

<div class="form-layout">
    <form method="POST" action="{{ route('roles.store') }}">
        @csrf
        @include('roles._form', ['assigned' => old('permissions', [])])
        <div class="form-actions">
            <a href="{{ route('roles.index') }}" class="btn btn-light">Cancel</a>
            <button type="submit" class="btn btn-primary">Create Role</button>
        </div>
    </form>
</div>
@endsection
