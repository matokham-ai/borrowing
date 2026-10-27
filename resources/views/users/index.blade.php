@extends('layouts.app')
@section('title', 'Users')

@section('content')
<div class="page-header">
    <div>
        <div class="eyebrow">ADMINISTRATION</div>
        <h1>Users</h1>
        <p>Manage team members and their access levels.</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('users.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Add User
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert-banner success"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
@endif

<div class="panel">
    {{-- Filters --}}
    <form method="GET" class="table-filters">
        <div class="filter-search">
            <i class="bi bi-search"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or email…">
        </div>
        <select name="role" class="filter-select">
            <option value="">All roles</option>
            @foreach($roles as $role)
                <option value="{{ $role->name }}" @selected(request('role') === $role->name)>{{ $role->display_name }}</option>
            @endforeach
        </select>
        <select name="status" class="filter-select">
            <option value="">All statuses</option>
            <option value="1" @selected(request('status') === '1')>Active</option>
            <option value="0" @selected(request('status') === '0')>Inactive</option>
        </select>
        <button type="submit" class="btn btn-light">Filter</button>
        @if(request()->hasAny(['search','role','status']))
            <a href="{{ route('users.index') }}" class="btn btn-light">Clear</a>
        @endif
    </form>

    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Roles</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Joined</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td>
                        <div class="borrower-cell">
                            <div class="mini-avatar">{{ strtoupper(substr($user->name, 0, 2)) }}</div>
                            <div>
                                <strong>{{ $user->name }}</strong>
                                <small>{{ $user->email }}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        @forelse($user->roles as $role)
                            <span class="tag">{{ $role->display_name }}</span>
                        @empty
                            <span class="text-muted-sm">No role</span>
                        @endforelse
                    </td>
                    <td>
                        <span class="status-pill {{ $user->is_active ? 'success' : 'danger' }}">
                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="text-muted-sm">{{ $user->last_login_at?->diffForHumans() ?? '—' }}</td>
                    <td class="text-muted-sm">{{ $user->created_at->format('d M Y') }}</td>
                    <td>
                        <div class="row-actions">
                            <a href="{{ route('users.edit', $user) }}" class="row-action" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="{{ route('users.toggle-active', $user) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="row-action" title="{{ $user->is_active ? 'Deactivate' : 'Activate' }}">
                                    <i class="bi bi-{{ $user->is_active ? 'pause-circle' : 'play-circle' }}"></i>
                                </button>
                            </form>
                            <form method="POST" action="{{ route('users.destroy', $user) }}"
                                  onsubmit="return confirm('Delete {{ addslashes($user->name) }}? This cannot be undone.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="row-action danger" title="Delete">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="empty-row">No users found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
        <div class="pagination-wrap">{{ $users->links() }}</div>
    @endif
</div>
@endsection
