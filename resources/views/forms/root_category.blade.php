<select class="form-control mr-2 mb-2" name="root_category_id" id="root_category_id">
    <option value="">Categoría Raíz</option>
    @foreach ($root_categories as $root_category)
        <option value="{{ $root_category->id }}" {{ ($root_category_id == $root_category->id) ? 'selected' : '' }}>({{ $root_category->marketCategoryId }}) {{ $root_category->name }}</option>
    @endforeach
</select>
