@php
    $estimatedValue = $asset['estimated_value'] ?? (isset($asset['estimated_value_cents']) ? $asset['estimated_value_cents'] / 100 : '');
    $savedPhotos = $asset['photos'] ?? [];
@endphp
<article class="collateral-card" data-collateral-card data-index="{{ $index }}">
    <div class="collateral-card-header">
        <h3>Collateral item</h3>
        <button type="button" class="btn btn-light btn-sm" data-remove-collateral>Remove item</button>
    </div>
    <div class="form-grid">
        <div class="form-field">
            <label>Asset type <span>*</span></label>
            <input class="form-input" name="collateral[{{ $index }}][type]" maxlength="100" required value="{{ $asset['type'] ?? '' }}" placeholder="e.g. Vehicle, equipment, property">
            @error("collateral.{$index}.type")<div class="form-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-field">
            <label>Owner <span>*</span></label>
            <input class="form-input" name="collateral[{{ $index }}][owner]" maxlength="255" required value="{{ $asset['owner'] ?? '' }}">
            @error("collateral.{$index}.owner")<div class="form-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-field">
            <label>Serial / registration number</label>
            <input class="form-input" name="collateral[{{ $index }}][reference]" maxlength="100" value="{{ $asset['reference'] ?? '' }}">
        </div>
        <div class="form-field">
            <label>Estimated value (KES) <span>*</span></label>
            <input class="form-input collateral-value" type="number" min="0" step="0.01" name="collateral[{{ $index }}][estimated_value]" required value="{{ $estimatedValue }}">
            @error("collateral.{$index}.estimated_value")<div class="form-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-field form-field-full">
            <label>Description <span>*</span></label>
            <input class="form-input" name="collateral[{{ $index }}][description]" maxlength="500" required value="{{ $asset['description'] ?? '' }}">
            @error("collateral.{$index}.description")<div class="form-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-field form-field-full">
            <label>Location <span>*</span></label>
            <input class="form-input" name="collateral[{{ $index }}][location]" maxlength="500" required value="{{ $asset['location'] ?? '' }}">
            @error("collateral.{$index}.location")<div class="form-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-field">
            <label>Field visit date <span>*</span></label>
            <input class="form-input" type="date" name="collateral[{{ $index }}][field_visit_date]" required value="{{ $asset['field_visit_date'] ?? '' }}">
            @error("collateral.{$index}.field_visit_date")<div class="form-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-field">
            <label>Condition <span>*</span></label>
            <input class="form-input" name="collateral[{{ $index }}][condition]" maxlength="500" required value="{{ $asset['condition'] ?? '' }}" placeholder="Describe current condition">
            @error("collateral.{$index}.condition")<div class="form-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-field form-field-full">
            <label>Ownership evidence <span>*</span></label>
            <textarea class="form-input" name="collateral[{{ $index }}][ownership_evidence]" maxlength="1000" rows="2" required placeholder="Record evidence seen during the field visit">{{ $asset['ownership_evidence'] ?? '' }}</textarea>
            @error("collateral.{$index}.ownership_evidence")<div class="form-error">{{ $message }}</div>@enderror
        </div>
        <div class="form-field form-field-full">
            <label>Notes</label>
            <textarea class="form-input" name="collateral[{{ $index }}][notes]" maxlength="2000" rows="2">{{ $asset['notes'] ?? '' }}</textarea>
        </div>
        <div class="form-field form-field-full">
            <label>Photographs <small>At least one required · JPEG, PNG or WebP · 5 MB each</small></label>
            <input class="form-input collateral-photo-input" type="file" name="collateral[{{ $index }}][photos][]" accept="image/jpeg,image/png,image/webp" capture="environment" multiple>
            <div class="form-hint">Capture multiple angles. Add a caption to each selected photograph where helpful.</div>
            <div class="saved-collateral-photos">
                @foreach($savedPhotos as $photoIndex => $photo)
                    @if(!empty($photo['path']) && $application)
                        <div class="secure-note">
                            <a href="{{ route('applications.security-file', [$application, "collateral-{$index}-{$photoIndex}"]) }}" target="_blank" rel="noopener">View saved photograph{{ !empty($photo['caption']) ? ': '.$photo['caption'] : '' }}</a>
                            <label><input type="checkbox" name="remove_collateral_photos[{{ $index }}][]" value="{{ $photoIndex }}"> Remove saved photo</label>
                        </div>
                    @endif
                @endforeach
            </div>
            @error("collateral.{$index}.photos")<div class="form-error">{{ $message }}</div>@enderror
            <div class="photo-captions" data-caption-list></div>
        </div>
    </div>
</article>
