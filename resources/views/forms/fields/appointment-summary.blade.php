@php use App\Models\Consultant; @endphp
@php use Carbon\Carbon; @endphp
@php use App\Models\Voucher; @endphp
<div class="space-y-2">
    <h3 class="text-lg font-bold">Appointment Summary</h3>

    <div>
        <strong>Consultant:</strong>
        {{ Consultant::find($get('consultant_id'))?->name }}
        ({{ Consultant::find($get('consultant_id'))?->email }})
    </div>

    <div>
        <strong>Date:</strong>
        {{ Carbon::parse($get('date'))->translatedFormat('l, d F Y') }}
    </div>

    <div>
        <strong>Time:</strong>
        {{ $get('time') }}
    </div>

    <div>
        <strong>Duration:</strong>
        60 minutes
    </div>

    <div>
        <strong>Voucher:</strong>
        @if($get('voucher_id'))
            {{ Voucher::find($get('voucher_id'))?->code }}
        @else
            No voucher applied
        @endif
    </div>
</div>
