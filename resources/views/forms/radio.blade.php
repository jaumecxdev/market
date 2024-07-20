<div class="form-check">
    <input class="form-check-input" type="radio" name="{{ $field_name }}" id="{{ $field_id }}" value="{{ $value }}" {{ $checked ? 'checked' : '' }}>
    <label class="form-check-label" for="{{ $field_id }}">
        {{ $label ?? '' }}
    </label>
</div>
