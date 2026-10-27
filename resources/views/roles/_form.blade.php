<div class="form-card">
    <div class="form-card-head"><h2>Role Details</h2></div>
    <div class="form-grid">
        <div class="form-group">
            <label>Role Name <span class="label-hint">(slug, e.g. loan-officer)</span></label>
            <input type="text" name="name" value="{{ old('name', $role->name ?? '') }}"
                   class="form-input @error('name') is-invalid @enderror"
                   placeholder="loan-officer"
                   {{ isset($role) && $role->is_system ? 'readonly' : '' }} required>
            @error('name')<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
            <label>Display Name</label>
            <input type="text" name="display_name" value="{{ old('display_name', $role->display_name ?? '') }}"
                   class="form-input @error('display_name') is-invalid @enderror"
                   placeholder="Loan Officer" required>
            @error('display_name')<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <div class="form-group form-group-full">
            <label>Description <span class="label-hint">(optional)</span></label>
            <textarea name="description" class="form-input" rows="2"
                      placeholder="Briefly describe what this role can do…">{{ old('description', $role->description ?? '') }}</textarea>
        </div>
    </div>
</div>

<div class="form-card">
    <div class="form-card-head">
        <div>
            <h2>Permissions</h2>
            <p>Check the capabilities this role should grant.</p>
        </div>
        <button type="button" class="btn btn-light btn-sm" id="toggleAllPerms">Select all</button>
    </div>

    @foreach($permissions as $group => $perms)
    <div class="perm-group">
        <div class="perm-group-head">{{ ucfirst(str_replace('-', ' ', $group)) }}</div>
        <div class="perm-check-grid">
            @foreach($perms as $perm)
            <label class="perm-check {{ in_array($perm->id, (array)$assigned) ? 'checked' : '' }}">
                <input type="checkbox" name="permissions[]" value="{{ $perm->id }}"
                       {{ in_array($perm->id, (array)$assigned) ? 'checked' : '' }}>
                <span class="perm-check-box"><i class="bi bi-check-lg"></i></span>
                <span>{{ $perm->display_name }}</span>
            </label>
            @endforeach
        </div>
    </div>
    @endforeach
</div>

<script>
(function(){
    const btn = document.getElementById('toggleAllPerms');
    if (!btn) return;
    btn.addEventListener('click', function(){
        const boxes = document.querySelectorAll('input[name="permissions[]"]');
        const allChecked = [...boxes].every(b => b.checked);
        boxes.forEach(b => {
            b.checked = !allChecked;
            b.closest('.perm-check').classList.toggle('checked', !allChecked);
        });
        btn.textContent = allChecked ? 'Select all' : 'Deselect all';
    });
    document.querySelectorAll('input[name="permissions[]"]').forEach(cb => {
        cb.addEventListener('change', function(){
            this.closest('.perm-check').classList.toggle('checked', this.checked);
        });
    });
    document.querySelectorAll('input[name="roles[]"]').forEach(cb => {
        cb.addEventListener('change', function(){
            this.closest('.role-card').classList.toggle('selected', this.checked);
        });
    });
})();
</script>
