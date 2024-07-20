<div class="form-check">
    <input class="form-check-input" type="checkbox" name="{{ $field_name }}" id="{{ $field_name }}" {{ $value ? 'checked' : '' }}>
    <label class="form-check-label" for="{{ $field_name }}">{{ $label ?? '' }}</label>
</div>
