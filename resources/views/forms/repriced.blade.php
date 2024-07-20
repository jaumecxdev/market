<select class="form-control mr-2 mb-2" name="repriced" id="repriced">
    <option value="">R€</option>
    <option value="1" {{ ("1" == $option_selected) ? 'selected' : '' }}>Sí</option>
    <option value="0" {{ ("0" == $option_selected) ? 'selected' : '' }}>No</option>
</select>
