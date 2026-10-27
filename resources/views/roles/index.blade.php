@extends('layouts.app')
@section('title', 'Roles')

@section('content')
<div class="page-header">
    <div>
        <div class="eyebrow">ADMINISTRATION</div>
        <h1>Roles</h1>
        <p>Define access levels and assign permissions to each role.</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('roles.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New Role</a>
    </div>
</div>

@if(session('success'))
    <div class="alert-banner success"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
@endif

<div class="roles-list">
    @forelse($roles as $role)
    <div class="role-row panel">
        <div class="role-row-left">
            <div class="role-icon-wrap {{ $role->is_system ? 'system' : '' }}">
                <i class="bi bi-shield-{{ $role->is_system ? 'fill' : 'lock' }}"></i>
            </div>
            <div>
                <div class="role-row-name">
                    {{ $role->display_name }}
                    @if($role->is_system)
                        <span class="tag tag-system">System</span>
                    @endif
                </div>
                <div class="role-row-slug">{{ $role->name }}</div>
                @if($role->description)
                    <div class="role-row-desc">{{ $role->description }}</div>
                @endif
            </div>
        </div>
        <div class="role-row-meta">
            <div class="role-meta-item">
                <span class="role-meta-val">{{ $role->users_count }}</span>
                <span class="role-meta-label">users</span>
            </div>
            <div class="role-meta-item">
                <span class="role-meta-val">{{ $role->permissions->count() }}</span>
                <span class="role-meta-label">permissions</span>
            </div>
        </div>
        <div class="role-row-perms">
            @foreach($role->permissions->take(6) as $perm)
                <span class="perm-tag">{{ $perm->display_name }}</span>
            @endforeach
            @if($role->permissions->count() > 6)
                <span class="perm-tag perm-more">+{{ $role->permissions->count() - 6 }} more</span>
            @endif
        </div>
        <div class="row-actions">
            <a href="{{ route('roles.edit', $role) }}" class="row-action" title="Edit">
                <i class="bi bi-pencil"></i>
            </a>
            @if(!$role->is_system)
            <form method="POST" action="{{ route('roles.destroy', $role) }}"
                  onsubmit="return confirm('Delete role {{ addslashes($role->display_name) }}?')">
                @csrf @method('DELETE')
                <button type="submit" class="row-action danger" title="Delete"><i class="bi bi-trash3"></i></button>
            </form>
            @endif
        </div>
    </div>
    @empty
    <div class="panel empty-state">
        <i class="bi bi-shield-lock"></i>
        <p>No roles yet. <a href="{{ route('roles.create') }}">Create one</a>.</p>
    </div>
    @endforelse
</div>
@endsection
