<select class="form-control mr-2 mb-2" name="status_id" id="status_id">
    <option value="">Estado</option>
    @foreach ($statuses as $status)
        <option value="{{ $status->id }}" {{ (isset($status_id) && $status_id == $status->id) ? 'selected' : '' }} {{ (isset($status_name) && $status_name == $status->name) ? 'selected' : '' }}>{{ $status->name }}</option>
    @endforeach
</select>
