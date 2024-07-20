<select class="form-control mr-2 mb-2" name="{{ $field_name }}" id="{{ $field_name }}">
    <option value="">{{ $placeholder ?? '' }}</option>
    @foreach ($options as $option)
        <option value="{{ $option }}" {{ ($option == $option_selected) ? 'selected' : '' }}>{{ $option }}</option>
    @endforeach
</select>
