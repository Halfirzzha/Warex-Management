@component('mail::message')
    # New Inbound Transaction Created

    A new inbound transaction has been successfully created in the system.

    @component('mail::panel')
        **Transaction Details:**
        - Transaction Code: **{{ $inbound->code }}**
        - Product: **{{ $inbound->product->name ?? $inbound->name_product }}**
        - Quantity: **{{ $inbound->quantity ?? $inbound->total_containers }} {{ $inbound->unit_type }}**
        - Warehouse: **{{ ucfirst(str_replace('_', ' ', $inbound->warehouse)) }}**
        - Status: **{{ strtoupper($inbound->status) }}**
        - Created: {{ $inbound->created_at->format('d M Y H:i') }}
    @endcomponent

    @component('mail::button', ['url' => url("/admin/inbounds/{$inbound->id}")])
        View Transaction Details
    @endcomponent

    Thank you for using our warehouse management system!

    Best regards,<br>
    {{ config('app.name') }}
@endcomponent
