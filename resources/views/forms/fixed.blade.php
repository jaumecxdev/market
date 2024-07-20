<select class="form-control mr-2 mb-2" name="fixed" id="fixed">
    @foreach ($fixeds as $key => $name)
        <option value="{{ $key }}" {{ ($fixed == $key) ? 'selected' : '' }}>{{ $name }}</option>
    @endforeach
</select>
