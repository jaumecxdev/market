<select class="form-control mr-2 mb-2" name="{{ $type }}_address_country_id" id="{{ $type }}_address_country_id">
    <option value="">País</option>
    @foreach ($countries as $country)
        <option value="{{ $country->id }}" {{ ($country_id == $country->id) ? 'selected' : '' }}>{{ $country->name }}</option>
    @endforeach
</select>
