<a href="{{ route('suppliers.supplier_categories.index', [$supplier, http_build_query(array_merge($order_params, ['order_by' => $order_by]))]) }}">{{ $title }}</a>

