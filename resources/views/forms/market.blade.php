<select class="form-control mr-2 mb-2" name="market_id" id="market_id">
    <option value=""></option>
    @foreach ($markets as $market)
        <option value="{{ $market->id }}" {{ ($market_id == $market->id) ? 'selected' : '' }}>{{ $market->name }}</option>
    @endforeach
</select>
