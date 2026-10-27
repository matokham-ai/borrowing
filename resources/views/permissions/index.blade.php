@extends('layouts.app')
@section('title', 'Permissions')

@section('content')

{{-- ── Page header ── --}}
<div class="page-header">
    <div>
        <div class="eyebrow">ADMINISTRATION</div>
        <h1>Permissions</h1>
        <p>Granular capabilities you can assign to roles. System permissions are locked.</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" id="addPermBtn">
            <i class="bi bi-plus-lg"></i> New Permission
        </button>
    </div>
</div>

{{-- ── Flash ── --}}
@if(session('success'))
    <div class="alert-banner success" style="margin: 0 var(--pg) 18px">
        <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="alert-banner danger" style="margin: 0 var(--pg) 18px">
        <i class="bi bi-exclamation-circle-fill"></i> {{ session('error') }}
    </div>
@endif

{{-- ── Stat bar ── --}}
<div class="stats-grid" style="grid-template-columns:repeat(3,1fr); margin-bottom:18px">
    <div class="stat-card stat-card--indigo">
        <div class="stat-card-top">
            <div class="stat-icon stat-icon--indigo"><i class="bi bi-key-fill"></i></div>
        </div>
        <div class="stat-label">Total Permissions</div>
        <div class="stat-value">{{ $totalCount }}</div>
    </div>
    <div class="stat-card stat-card--green">
        <div class="stat-card-top">
            <div class="stat-icon stat-icon--green"><i class="bi bi-check-circle-fill"></i></div>
        </div>
        <div class="stat-label">Active</div>
        <div class="stat-value">{{ $activeCount }}</div>
    </div>
    <div class="stat-card stat-card--red">
        <div class="stat-card-top">
            <div class="stat-icon stat-icon--red"><i class="bi bi-slash-circle-fill"></i></div>
        </div>
        <div class="stat-label">Inactive</div>
        <div class="stat-value">{{ $totalCount - $activeCount }}</div>
    </div>
</div>

{{-- ── Filters ── --}}
<div class="panel" style="margin: 0 var(--pg) 18px; padding:0">
    <form method="GET" action="{{ route('permissions.index') }}" class="table-filters">
        <div class="filter-search">
            <i class="bi bi-search"></i>
            <input type="text" name="search" value="{{ $search }}" placeholder="Search permissions…">
        </div>
        <select name="group" class="filter-select">
            <option value="">All groups</option>
            @foreach($groups as $g)
                <option value="{{ $g }}" @selected($group === $g)>{{ ucfirst(str_replace('-', ' ', $g)) }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-light">Filter</button>
        @if($search || $group)
            <a href="{{ route('permissions.index') }}" class="btn btn-light">Clear</a>
        @endif
    </form>
</div>

{{-- ── Permission groups ── --}}
<div class="perm-groups-wrap" style="padding: 0 var(--pg)">
    @forelse($permissions as $groupName => $perms)
    <div class="panel perm-group-panel">

        {{-- Group header --}}
        <div class="perm-group-panel-head">
            <div class="perm-group-icon"><i class="bi bi-key"></i></div>
            <div style="flex:1">
                <h2>{{ ucfirst(str_replace('-', ' ', $groupName)) }}</h2>
                <p>{{ $perms->count() }} permission{{ $perms->count() !== 1 ? 's' : '' }}</p>
            </div>
        </div>

        {{-- Table --}}
        <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Permission slug</th>
                    <th>Display name</th>
                    <th>Description</th>
                    <th>Roles</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($perms as $perm)
                <tr>
                    <td>
                        <code class="perm-code">{{ $perm->name }}</code>
                        @if($perm->is_system)
                            <span class="tag tag-system ms-1">System</span>
                        @endif
                    </td>
                    <td style="font-weight:600;color:#101828">{{ $perm->display_name }}</td>
                    <td class="text-muted-sm" style="max-width:260px">
                        {{ $perm->description ?: '—' }}
                    </td>
                    <td>
                        <span class="text-muted-sm">
                            {{ $perm->roles_count }} role{{ $perm->roles_count !== 1 ? 's' : '' }}
                        </span>
                    </td>
                    <td>
                        <span class="status-pill {{ $perm->is_active ? 'success' : 'danger' }}">
                            {{ $perm->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td>
                        <div class="row-actions">
                            {{-- Edit --}}
                            <button type="button"
                                    class="row-action edit-perm-btn"
                                    title="Edit"
                                    data-id="{{ $perm->id }}"
                                    data-name="{{ $perm->name }}"
                                    data-display="{{ $perm->display_name }}"
                                    data-desc="{{ $perm->description }}"
                                    data-active="{{ $perm->is_active ? '1' : '0' }}"
                                    data-system="{{ $perm->is_system ? '1' : '0' }}">
                                <i class="bi bi-pencil"></i>
                            </button>

                            {{-- Toggle active --}}
                            <form method="POST"
                                  action="{{ route('permissions.toggle-active', $perm) }}">
                                @csrf @method('PATCH')
                                <button type="submit"
                                        class="row-action"
                                        title="{{ $perm->is_active ? 'Deactivate' : 'Activate' }}">
                                    <i class="bi bi-{{ $perm->is_active ? 'pause-circle' : 'play-circle' }}"></i>
                                </button>
                            </form>

                            {{-- Delete (non-system only) --}}
                            @if(!$perm->is_system)
                            <form method="POST"
                                  action="{{ route('permissions.destroy', $perm) }}"
                                  onsubmit="return confirm('Delete permission {{ addslashes($perm->display_name) }}? This will remove it from all roles.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="row-action danger" title="Delete">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
    @empty
    <div class="panel empty-state">
        <i class="bi bi-key fs-1 text-muted"></i>
        <p>No permissions match your filters.</p>
        @if($search || $group)
            <a href="{{ route('permissions.index') }}" class="btn btn-light btn-sm">Clear filters</a>
        @endif
    </div>
    @endforelse
</div>


{{-- ════════════════════════════════════════
     CREATE MODAL
════════════════════════════════════════ --}}
<div class="bm-overlay" id="createModal" role="dialog" aria-modal="true" aria-labelledby="createModalTitle">
    <div class="bm-modal bm-modal-sm">
        <div class="bm-header">
            <div class="bm-header-left">
                <div class="bm-icon-wrap"><i class="bi bi-key-fill"></i></div>
                <div>
                    <div class="bm-title" id="createModalTitle">New Permission</div>
                    <div class="bm-sub">Define a new capability that can be assigned to roles.</div>
                </div>
            </div>
            <button type="button" class="bm-close" id="createModalClose" aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('permissions.store') }}">
            @csrf
            <div class="bm-body">

                @if($errors->any() && !old('_edit_id'))
                <div class="bm-errors">
                    @foreach($errors->all() as $err)
                        <div class="bm-error-line"><i class="bi bi-exclamation-circle-fill"></i> {{ $err }}</div>
                    @endforeach
                </div>
                @endif

                <div class="bm-form-grid">
                    <div class="bm-full">
                        <label class="bm-label">
                            Permission Slug
                            <span class="label-hint" style="font-weight:400;color:#98a2b3">(e.g. approve-loans)</span>
                        </label>
                        <input type="text" name="name"
                               value="{{ old('name') }}"
                               class="form-input @error('name') is-invalid @enderror"
                               placeholder="approve-loans"
                               pattern="[a-z0-9\-]+"
                               title="Lowercase letters, numbers and hyphens only"
                               required>
                        @error('name')<span class="field-error">{{ $message }}</span>@enderror
                        <span class="field-error" style="color:#98a2b3;margin-top:4px;display:block;font-size:10px">
                            Lowercase, hyphens only. Cannot be changed for system permissions.
                        </span>
                    </div>
                    <div class="bm-full">
                        <label class="bm-label">Display Name</label>
                        <input type="text" name="display_name"
                               value="{{ old('display_name') }}"
                               class="form-input @error('display_name') is-invalid @enderror"
                               placeholder="Approve Loans"
                               required>
                        @error('display_name')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="bm-full">
                        <label class="bm-label">Description <span class="label-hint">(optional)</span></label>
                        <textarea name="description"
                                  class="form-input"
                                  rows="2"
                                  placeholder="Briefly describe what this permission allows…">{{ old('description') }}</textarea>
                    </div>
                    <div class="bm-full">
                        <label class="toggle-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1"
                                   {{ old('is_active', '1') === '1' ? 'checked' : '' }}>
                            <span class="toggle-track"></span>
                            <span class="toggle-label">Active</span>
                        </label>
                    </div>
                </div>

            </div>
            <div class="bm-footer">
                <button type="button" class="btn btn-light" id="createModalCancel">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Create Permission
                </button>
            </div>
        </form>
    </div>
</div>


{{-- ════════════════════════════════════════
     EDIT MODAL
════════════════════════════════════════ --}}
<div class="bm-overlay" id="editModal" role="dialog" aria-modal="true" aria-labelledby="editModalTitle">
    <div class="bm-modal bm-modal-sm">
        <div class="bm-header">
            <div class="bm-header-left">
                <div class="bm-icon-wrap"><i class="bi bi-pencil-fill"></i></div>
                <div>
                    <div class="bm-title" id="editModalTitle">Edit Permission</div>
                    <div class="bm-sub" id="editModalSub">Update the permission details.</div>
                </div>
            </div>
            <button type="button" class="bm-close" id="editModalClose" aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form method="POST" id="editForm">
            @csrf @method('PUT')
            <input type="hidden" name="_edit_id" id="editId">
            <div class="bm-body">

                @if($errors->any() && old('_edit_id'))
                <div class="bm-errors">
                    @foreach($errors->all() as $err)
                        <div class="bm-error-line"><i class="bi bi-exclamation-circle-fill"></i> {{ $err }}</div>
                    @endforeach
                </div>
                @endif

                <div class="bm-form-grid">
                    <div class="bm-full" id="editNameWrap">
                        <label class="bm-label">Permission Slug</label>
                        <input type="text" name="name" id="editName"
                               class="form-input @error('name') is-invalid @enderror"
                               pattern="[a-z0-9\-]+"
                               title="Lowercase letters, numbers and hyphens only">
                        @error('name')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="bm-full">
                        <label class="bm-label">Display Name</label>
                        <input type="text" name="display_name" id="editDisplayName"
                               class="form-input @error('display_name') is-invalid @enderror"
                               required>
                        @error('display_name')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="bm-full">
                        <label class="bm-label">Description <span class="label-hint">(optional)</span></label>
                        <textarea name="description" id="editDesc"
                                  class="form-input" rows="2"></textarea>
                    </div>
                    <div class="bm-full">
                        <label class="toggle-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" id="editActive" value="1">
                            <span class="toggle-track"></span>
                            <span class="toggle-label">Active</span>
                        </label>
                    </div>
                </div>

            </div>
            <div class="bm-footer">
                <button type="button" class="btn btn-light" id="editModalCancel">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    /* ── helpers ── */
    function openModal(el)  { el.classList.add('open');  document.body.style.overflow = 'hidden'; }
    function closeModal(el) { el.classList.remove('open'); document.body.style.overflow = ''; }
    function closeAll()     { document.querySelectorAll('.bm-overlay').forEach(closeModal); }

    /* ── close on backdrop click ── */
    document.querySelectorAll('.bm-overlay').forEach(function (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeModal(overlay);
        });
    });

    /* ── close on Escape ── */
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeAll();
    });

    /* ── CREATE modal ── */
    var createModal  = document.getElementById('createModal');
    var addPermBtn   = document.getElementById('addPermBtn');
    var createClose  = document.getElementById('createModalClose');
    var createCancel = document.getElementById('createModalCancel');

    if (addPermBtn)   addPermBtn.addEventListener('click',   function () { openModal(createModal); });
    if (createClose)  createClose.addEventListener('click',  function () { closeModal(createModal); });
    if (createCancel) createCancel.addEventListener('click', function () { closeModal(createModal); });

    /* ── EDIT modal ── */
    var editModal   = document.getElementById('editModal');
    var editForm    = document.getElementById('editForm');
    var editClose   = document.getElementById('editModalClose');
    var editCancel  = document.getElementById('editModalCancel');
    var editSub     = document.getElementById('editModalSub');
    var editNameWrap = document.getElementById('editNameWrap');

    if (editClose)  editClose.addEventListener('click',  function () { closeModal(editModal); });
    if (editCancel) editCancel.addEventListener('click', function () { closeModal(editModal); });

    document.querySelectorAll('.edit-perm-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id       = btn.dataset.id;
            var name     = btn.dataset.name;
            var display  = btn.dataset.display;
            var desc     = btn.dataset.desc;
            var active   = btn.dataset.active === '1';
            var isSystem = btn.dataset.system === '1';

            /* populate form */
            editForm.action = '/admin/permissions/' + id;
            document.getElementById('editId').value          = id;
            document.getElementById('editName').value        = name;
            document.getElementById('editDisplayName').value = display;
            document.getElementById('editDesc').value        = desc || '';
            document.getElementById('editActive').checked    = active;

            /* system permissions: slug is read-only */
            var nameInput = document.getElementById('editName');
            nameInput.readOnly    = isSystem;
            nameInput.style.background = isSystem ? '#f9fafb' : '';
            nameInput.style.color      = isSystem ? '#98a2b3' : '';

            /* update modal subtitle */
            editSub.textContent = isSystem
                ? 'System permission — slug is locked.'
                : 'Update the permission details.';

            openModal(editModal);
        });
    });

    /* ── Re-open the correct modal on validation failure ── */
    @if($errors->any())
        @if(old('_edit_id'))
            /* edit modal had errors — re-open it */
            (function () {
                var modal = document.getElementById('editModal');
                if (modal) openModal(modal);
                /* re-populate from old() values */
                var f = document.getElementById('editForm');
                if (f) f.action = '/admin/permissions/{{ old("_edit_id") }}';
                var idField = document.getElementById('editId');
                if (idField) idField.value = '{{ old("_edit_id") }}';
            })();
        @else
            /* create modal had errors — re-open it */
            (function () {
                var modal = document.getElementById('createModal');
                if (modal) openModal(modal);
            })();
        @endif
    @endif
})();
</script>
@endpush
