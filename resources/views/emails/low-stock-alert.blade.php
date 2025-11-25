@component('mail::message')
    # Low Stock Alert!

    The product **{{ $stock->product->name ?? $stock->name_product }}** is running low on stock.

    @component('mail::panel')
        **Product Details:**
        - Product Name: {{ $stock->product->name ?? $stock->name_product }}
        - Warehouse: {{ ucfirst(str_replace('_', ' ', $stock->warehouse)) }}
        - Current Quantity: **{{ $stock->available_stock }} {{ $stock->unit_type }}**
        - Status: **{{ strtoupper(str_replace('_', ' ', $stock->status)) }}**
    @endcomponent

    @component('mail::button', ['url' => url("/admin/stocks/{$stock->id}")])
        View Stock Details
    @endcomponent

    **Action Required:** Please reorder this product as soon as possible to avoid stock shortages.

    Thanks,<br>
    {{ config('app.name') }}
@endcomponent
