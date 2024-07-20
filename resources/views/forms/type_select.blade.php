<select class="form-control mr-2 mb-2" name="type_id" id="type_id">
    <option value="">Tipo</option>
    @foreach ($types as $type)
        <option value="{{ $type->id }}" {{ $type_id == $type->id ? 'selected' : '' }} >{{ $type->name }}</option>
    @endforeach
</select>
