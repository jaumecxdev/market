<select class="form-control mr-2 mb-2" name="supplier_id" id="supplier_id">
    <option value="">Proveedor</option>
    @foreach ($suppliers as $supplier)
        <option value="{{ $supplier->id }}" {{ ($supplier_id == $supplier->id) ? 'selected' : '' }}>{{ $supplier->name }}</option>
    @endforeach
</select>
