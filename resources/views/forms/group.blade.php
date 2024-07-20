<select class="form-control mr-2 mb-2" name="group_id" id="group_id">
    <option value="">Grupo</option>
    @foreach ($groups as $group)
        <option value="{{ $group->id }}" {{ ($group_id == $group->id) ? 'selected' : '' }}>({{ $group->marketGroupParentId }}) {{ $group->name }}</option>
    @endforeach
</select>
