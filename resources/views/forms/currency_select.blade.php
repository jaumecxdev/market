<select class="form-control mr-2 mb-2" name="currency_id" id="currency_id">
    <option value="">Moneda</option>
    @foreach ($currencies as $currency)
        <option value="{{ $currency->id }}" {{ $currency_id == $currency->id ? 'selected' : '' }} >{{ $currency->name }}</option>
    @endforeach
</select>
