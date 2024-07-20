<select class="form-control mr-2 mb-2" name="ready" id="ready">
    <option value="">Ok</option>
    <option value="0" {{ $ready === 0 ? 'selected' : '' }}>Sí</option>
    <option value="1" {{ $ready === 1 ? 'selected' : '' }}>No</option>
</select>
