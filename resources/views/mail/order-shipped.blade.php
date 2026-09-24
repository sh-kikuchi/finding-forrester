<x-mail::message>
# {{ __('ご注文の商品を発送しました') }}

{{ $order->user->name }} {{ __('様') }}

{{ __('ご注文 #:id の商品を、:seller が発送いたしました。', ['id' => $order->id, 'seller' => $sellerName]) }}

@foreach ($items as $item)
- {{ $item->title }} × {{ $item->quantity }}
@endforeach

{{ __('合計') }}: ¥{{ number_format($total) }}

{{ __('発送日時') }}: {{ $shippedAt->format('Y-m-d H:i') }}

{{ __('ご利用ありがとうございました。') }}<br>
{{ config('app.name') }}
</x-mail::message>
