@component('mail::message')
# Pedido de {{ $order->market->name }} {{ $order->shop->name }}

<b>Pedido: </b>{{ $order->marketOrderId }}<br>
<b>Estado: </b><span style="color:red">{{ $order->status->name }}</span><br>
<b>Número de líneas:</b>   {{ $order_items->count() }}<br><br>

@foreach ($order_items as $order_item)
<b>Línea:</b>   {{ $order_item->marketItemId }}<br>
{!! isset($order_item->product->pn) ? "<b>Part Number: </b>" .$order_item->product->pn."<br>" : '' !!}
{!! isset($order_item->product->ean) ? "<b>EAN13: </b>" .$order_item->product->ean."<br>" : '' !!}
<b>Proveedor:</b> {{ $order_item->product->supplier->name ?? '' }}<br>
<b>SKU Prov:</b> {{ $order_item->product->supplierSku ?? '' }}<br>
<b>Artículo:</b> {{ $order_item->name }}<br>
<b>Unidades:</b> {{ $order_item->quantity }}<br>
<b>Precio:</b>   {{ $order_item->price }} {{ $order_item->currency->code }}<br>
<b>Portes:</b>   {{ $order_item->shipping_price }} {{ $order_item->currency->code }}<br><br>
@endforeach

<b>Enviar a:</b><br>
{!! isset($order->shipping_address) ? $order->shipping_address->getHMTL() : null !!}<br>
{!! $order->info !!}<br><br>

@component('mail::button', ['url' => $url, 'color' => 'green'])
TRAMITAR
@endcomponent

{{ config('app.name') }}
@endcomponent
