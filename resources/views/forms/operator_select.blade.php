<select class="form-control mr-2 mb-2" name="field_operator" id="field_operator">
    <option value="">Operador</option>
    <option value="=" {{ $field_operator == '=' ? 'selected' : '' }} >=</option>
    <option value="<>" {{ $field_operator == '<>' ? 'selected' : '' }} ><></option>
    <option value="<=" {{ $field_operator == '<=' ? 'selected' : '' }} ><=</option>
    <option value=">=" {{ $field_operator == '>=' ? 'selected' : '' }} >>=</option>
    <option value="<" {{ $field_operator == '<' ? 'selected' : '' }} ><</option>
    <option value=">" {{ $field_operator == '>' ? 'selected' : '' }} >></option>
</select>
