<a href="{{ route('products.index', [http_build_query(array_merge($order_params, ['order_by' => $order_by]))]) }}">{{ $title }}</a>
