@extends('layouts.app')
@section('title', 'Activity Log')

@section('content')
<div class="page-header">
    <div>
        <div class="eyebrow">ADMINISTRATION</div>
        <h1>Activity Log</h1>
        <p>Audit trail of user actions and system events for your organisation.</p>
    </div>
</div>

{{-- Stats bar --}}
<div class="stats-grid" style="margin-bottom:1.5rem">
    <div class="stat-card">
        <div class="stat-card-body">
            <div class="stat-card-icon text-primary"><i class="bi bi-clock-history"></i></div>
            <div>
                <div class="stat-card-value">{{ number_format($totalCount) }}</div>
                <div class="stat-card-label">Total Records</div>
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-body">
            <div class="stat-card-icon text-info"><i class="bi bi-person-check"></i></div>
            <div>
                <div class="stat-card-value">{{ number_format($auditCount) }}</div>
                <div class="stat-card-label">User Actions</div>
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-body">
            <div class="stat-card-icon text-warning"><i class="bi bi-gear"></i></div>
            <div>
                <div class="stat-card-value">{{ number_format($systemCount) }}</div>
                <div class="stat-card-label">System Events</div>
            </div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="panel mb-4">
    <div class="panel-body">
        <form method="GET" action="{{ route('activities.index') }}" class="filter-bar">
            <div class="filter-bar-inputs">
                {{-- Search --}}
                <div class="input-wrap input-wrap-icon">
                    <i class="bi bi-search"></i>
                    <input type="text" name="search" class="form-control"
                           placeholder="Search actions, users, events…"
                           value="{{ $search }}">
                </div>

                {{-- Type tabs --}}
                <div class="btn-group" role="group" aria-label="Activity type filter">
                    @foreach(['all' => 'All', 'audit' => 'User Actions', 'system' => 'System Events'] as $val => $label)
                    <a href="{{ route('activities.index', array_merge(request()->except(['type','page']), ['type' => $val])) }}"
                       class="btn btn-sm {{ $type === $val ? 'btn-primary' : 'btn-outline-secondary' }}">
                        {{ $label }}
                    </a>
                    @endforeach
                </div>

                {{-- Date range --}}
                <input type="date" name="from" class="form-control form-control-sm"
                       value="{{ $from }}" title="From date" style="max-width:150px">
                <input type="date" name="to" class="form-control form-control-sm"
                       value="{{ $to }}" title="To date" style="max-width:150px">

                <button type="submit" class="btn btn-sm btn-primary">Filter</button>

                @if($search || $from || $to || $type !== 'all')
                <a href="{{ route('activities.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="panel">
    <div class="panel-body p-0">
        @if($entries->isEmpty())
        <div class="empty-state py-5 text-center">
            <i class="bi bi-clock-history fs-1 text-muted"></i>
            <p class="mt-3 text-muted">No activity records match your filters.</p>
        </div>
        @else
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:180px">Date &amp; Time</th>
                    <th>Action / Event</th>
                    <th>Actor</th>
                    <th>Target</th>
                    <th style="width:100px">Type</th>
                </tr>
            </thead>
            <tbody>
                @if($entryType === 'audit')
                    @foreach($entries as $entry)
                    <tr>
                        <td class="text-muted-sm text-nowrap">
                            {{ $entry->occurred_at?->format('d M Y, H:i') ?? '—' }}
                        </td>
                        <td>
                            <div class="fw-semibold">{{ ucwords(str_replace(['-','_'], ' ', $entry->action)) }}</div>
                            @if($entry->description)
                            <div class="text-muted-sm">{{ Str::limit($entry->description, 100) }}</div>
                            @endif
                        </td>
                        <td>
                            @if($entry->user)
                                <span class="badge-avatar">{{ strtoupper(substr($entry->user->name, 0, 2)) }}</span>
                                {{ $entry->user->name }}
                            @else
                                <span class="text-muted">System</span>
                            @endif
                        </td>
                        <td class="text-muted-sm">
                            @if($entry->auditable_type)
                                {{ class_basename($entry->auditable_type) }}
                                @if($entry->auditable_id) #{{ $entry->auditable_id }} @endif
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            <span class="status-pill info">Audit</span>
                        </td>
                    </tr>
                    @endforeach
                @else
                    @foreach($entries as $entry)
                    <tr>
                        <td class="text-muted-sm text-nowrap">
                            {{ $entry->occurred_at?->format('d M Y, H:i') ?? '—' }}
                        </td>
                        <td>
                            <div class="fw-semibold">{{ ucwords(str_replace(['.','_'], ' ', $entry->event_type)) }}</div>
                            @php $p = $entry->payload ?? []; @endphp
                            @if(!empty($p))
                            <div class="text-muted-sm">
                                @foreach(array_slice($p, 0, 3) as $key => $val)
                                    {{ ucfirst(str_replace('_', ' ', $key)) }}: <strong>{{ is_array($val) ? json_encode($val) : $val }}</strong>
                                    @if(!$loop->last) &nbsp;·&nbsp; @endif
                                @endforeach
                            </div>
                            @endif
                        </td>
                        <td>
                            <span class="text-muted-sm">System</span>
                        </td>
                        <td class="text-muted-sm">
                            {{ ucfirst($entry->aggregate_type ?? '—') }}
                            @if($entry->aggregate_id) #{{ $entry->aggregate_id }} @endif
                        </td>
                        <td>
                            <span class="status-pill
                                {{ in_array($entry->status, ['failed','error']) ? 'danger' : ($entry->status === 'processed' ? 'success' : 'secondary') }}">
                                {{ ucfirst($entry->status ?? 'event') }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                @endif
            </tbody>
        </table>

        @if($entries->hasPages())
        <div class="pagination-wrap">
            {{ $entries->links() }}
        </div>
        @endif
        @endif
    </div>
</div>
@endsection
