<select class="form-control mr-2 mb-2" name="shop_id" id="shop_id">
    <option value="">Tienda</option>
    @foreach ($shops as $shop)
        <option value="{{ $shop->id }}" {{ $shop_id == $shop->id ? 'selected' : '' }} >{{ $shop->market_shop_name }}</option>
    @endforeach
</select>
