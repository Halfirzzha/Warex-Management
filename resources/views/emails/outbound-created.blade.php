@component('mail::message')
    # New Outbound Transaction Created

    A new outbound transaction has been successfully created in the system.

    @component('mail::panel')
        **Transaction Details:**
        - Transaction Code: **{{ $outbound->code }}**
        - Product: **{{ $outbound->product->name ?? $outbound->name_product }}**
        - Quantity: **{{ $outbound->quantity ?? $outbound->total_containers }} {{ $outbound->unit_type }}**
        - Warehouse From: **{{ ucfirst(str_replace('_', ' ', $outbound->warehouse_from)) }}**
        - Purpose: **{{ ucfirst(str_replace('_', ' ', $outbound->purpose_type)) }}**
        - Department: **{{ $outbound->department ?? 'N/A' }}**
        - Status: **{{ strtoupper($outbound->status) }}**
        - Created: {{ $outbound->created_at->format('d M Y H:i') }}
    @endcomponent

    @component('mail::button', ['url' => url("/admin/outbounds/{$outbound->id}")])
        View Transaction Details
    @endcomponent

    Thank you for using our warehouse management system!

    Best regards,<br>
    {{ config('app.name') }}
@endcomponent
