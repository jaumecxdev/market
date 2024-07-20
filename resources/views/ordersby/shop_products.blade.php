<a href="{{ route('shops.shop_products.index', [$shop, http_build_query(array_merge($order_params, ['order_by' => $order_by]))]) }}">{{ $title }}</a>
