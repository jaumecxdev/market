<div class="input-group mr-2 mb-2">
    <div class="input-group-prepend">
        <div class="input-group-prepend">
            <select class="btn btn-default" name="item_select" id="item_select">
                <option value="name" {{ $item_select == 'name' ? 'selected' : '' }}>{{ __('Título') }}</option>
                <option value="id" {{ $item_select == 'id' ? 'selected' : '' }}>{{ __('ID') }}</option>
                <option value="supplierSku" {{ $item_select == 'supplierSku' ? 'selected' : '' }}>{{ __('SKU Proveedor') }}</option>
                <option value="pn" {{ $item_select == 'pn' ? 'selected' : '' }}>{{ __('Part number') }}</option>
                <option value="ean" {{ $item_select == 'ean' ? 'selected' : '' }}>{{ __('EAN13') }}</option>
                <option value="upc" {{ $item_select == 'upc' ? 'selected' : '' }}>{{ __('UPC') }}</option>
                <option value="isbn" {{ $item_select == 'isbn' ? 'selected' : '' }}>{{ __('ISBN') }}</option>
                <option value="gtin" {{ $item_select == 'gtin' ? 'selected' : '' }}>{{ __('GTIN') }}</option>
            </select>
        </div>
    </div>
    <input type="hidden" id="product_id" name="product_id" value="{{ $product_id }}">
    <input type="text" class="form-control" name="item_reference" id="item_reference" placeholder="Producto" value="{{ $item_reference }}">
</div>

