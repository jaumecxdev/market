<select class="form-control mr-2 mb-2" name="{{ $field_name }}" id="{{ $field_name }}"">
    <option value="">{{ $placeholder ?? '' }}</option>
    @foreach ($options as $option)
        <option value="{{ $option->id }}" {{ $option_id == $option->id ? 'selected' : '' }} >{{ $option->name }}</option>
    @endforeach
</select>
