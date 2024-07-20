<select class="form-control mr-2 mb-2" name="action_name" id="action_name">
    <option value="">Acción</option>
    @foreach ($actions as $action)
        <option value="{{ $action }}" {{ ($action == $action_name) ? 'selected' : '' }}>{{ $action }}</option>
    @endforeach
</select>
