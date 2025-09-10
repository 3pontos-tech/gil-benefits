@php
    use App\Models\Voucher;

    $voucher = $voucher instanceof Voucher ? $voucher : null;

@endphp

<div class="grid grid-cols-3 gap-2 my-2">
    @if($voucher)
            <div class="col-span-3 p-4 bg-green-100 border border-green-400 text-green-800 rounded text-center text-lg font-semibold">
                Voucher ID: <span class="font-mono bg-white px-2 py-1 rounded">{{ $voucher->id }}</span>
            </div>
        @else
            <div class="col-span-3 p-4 bg-yellow-100 border border-yellow-400 text-yellow-800 rounded text-center text-lg">
                No voucher available. Please contact your company HR for a new voucher.
            </div>
        @endif
</div>

