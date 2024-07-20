<div id="filters-button" class="btn btn-success"><i class="fas fa-filter mr-2"></i>Filtros avanzados</div>

    @if (isset($params['provider_category_id']) && !isset($params['page']))
        <span style="" id="filters-display">
    @else
        <span style="display:none" id="filters-display">
    @endif

        <hr>
        <div class="row">
            @foreach ($provider_filters as $key_id => $provider_filter_types)
                @foreach ($provider_filter_types as $attribute_name => $attribute_values)

                    <div class="col-xl-2 col-lg-2 col-md-3 col-sm-4 col-6">
                        <h5>{{ $attribute_name }}</h5>
                        @php $count_attribute_values = 0; @endphp
                        @foreach ($attribute_values as $attribute_value_name => $attribute_value_param)       {{-- $attribute_value_id) --}}

                            @if (isset($attribute_value_param['DELETE'])) {{-- $attribute_value_id == 'DELETE')  --}}
                                @php
                                    $params_array = explode(',', $params[$key_id]);
                                    if (($key = array_search($attribute_value_param['DELETE'], $params_array)) !== false)
                                        unset($params_array[$key]);

                                    $unset_params = $params;
                                    unset($unset_params[$key_id]);
                                    $unset_params[$key_id] = implode(',', $params_array);
                                @endphp
                                <a class="text-danger" href="{{ route('products.index', [http_build_query($unset_params)]) }}"><i class="far fa-times-circle"></i> {{ $attribute_value_name }}</a>
                            @else
                                @php
                                    $count_attribute_values++;
                                    $params_array = isset($params[$key_id]) ? explode(',', $params[$key_id]) : [];
                                    $params_array[] = $attribute_value_param['SELECT'];
                                    $attribute_name_http = preg_replace("/[^a-zA-Z]/", "", $attribute_name);
                                @endphp
                                @if ($count_attribute_values >= 6)
                                    <span style="display:none" class="attribute-display-{{$attribute_name_http}}">
                                @endif
                                <a href="{{ route('products.index', [http_build_query(array_merge($params, [$key_id => implode(',', $params_array)]))]) }}">{{ $attribute_value_name }}</a>
                                <br>
                                </span>
                            @endif
                        @endforeach

                        @if ($count_attribute_values > 5)
                            <div class="attributes-button btn btn-outline-info btn-sm mt-1" attribute_name={{$attribute_name_http}} id="attributes-button"><i class="fas fa-plus-circle mr-2"></i>Más opciones</div>
                            <br>
                        @endif
                        <br>
                    </div>

                @endforeach
            @endforeach
        </div>
    </span>
    <hr>

@push('scriptsEnd')
    @include('scripts.products-filters')
@endpush
