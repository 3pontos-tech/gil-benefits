@php
    use Illuminate\Support\Str;

    $plans = config('cashier.plans', []);

    $getPriceLabel = function ($price, $fallbackIndex = null) {
        // Try common property/method names safely
        if (is_array($price)) {
            return $price['name'] ?? $price['label'] ?? $price['id'] ?? ($fallbackIndex !== null ? 'Price #' . ($fallbackIndex + 1) : 'Price');
        }
        if (is_object($price)) {
            // Public properties first
            foreach (['name', 'label', 'nickname', 'title'] as $prop) {
                if (property_exists($price, $prop) && ! empty($price->{$prop})) {
                    return (string) $price->{$prop};
                }
            }
            // Common getters
            foreach (['getName', 'name', 'getLabel', 'label', 'getNickname', 'nickname', 'getTitle', 'title'] as $method) {
                if (method_exists($price, $method)) {
                    try { return (string) $price->{$method}(); } catch (Throwable) {}
                }
            }
            // Fallback to ID-like
            foreach (['id', 'priceId', 'stripe_price_id', 'getId', 'getPriceId'] as $idProp) {
                if (property_exists($price, $idProp) && ! empty($price->{$idProp})) {
                    return (string) $price->{$idProp};
                }
                if (method_exists($price, $idProp)) {
                    try { return (string) $price->{$idProp}(); } catch (Throwable) {}
                }
            }
        }
        return $fallbackIndex !== null ? 'Price #' . ($fallbackIndex + 1) : 'Price';
    };

    $getPriceId = function ($price) {
        if (is_array($price)) {
            return $price['id'] ?? $price['price_id'] ?? null;
        }
        if (is_object($price)) {
            foreach (['id', 'priceId', 'stripe_price_id'] as $prop) {
                if (property_exists($price, $prop) && ! empty($price->{$prop})) {
                    return (string) $price->{$prop};
                }
            }
            foreach (['getId', 'getPriceId'] as $method) {
                if (method_exists($price, $method)) {
                    try { return (string) $price->{$method}(); } catch (Throwable) {}
                }
            }
        }
        return null;
    };
@endphp

<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-8 text-center">
        <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">Choose your plan</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Select the pricing option that fits you best. You can change anytime.</p>
    </div>

    @if (empty($plans))
        <div class="rounded-lg border border-dashed p-8 text-center text-gray-500 dark:text-gray-400">
            No plans are configured. Please define plans in config/cashier.php.
        </div>
    @else
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($plans as $planKey => $plan)
                @php
                    $productId = is_array($plan) ? ($plan['product_id'] ?? null) : (is_object($plan) && property_exists($plan, 'productId') ? $plan->productId : null);
                    $trialDays = is_array($plan) ? ($plan['trial_days'] ?? null) : (is_object($plan) && property_exists($plan, 'trialDays') ? $plan->trialDays : null);
                    $prices = is_array($plan) ? ($plan['prices'] ?? []) : (is_object($plan) && property_exists($plan, 'prices') ? $plan->prices : []);
                    // If it's a Laravel collection
                    if (is_object($prices) && method_exists($prices, 'all')) { $prices = $prices->all(); }
                @endphp

                <div class="flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="border-b border-gray-200 p-6 dark:border-gray-800">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ Str::headline((string) $planKey) }}</h2>
                        @if ($trialDays)
                            <p class="mt-1 text-sm text-green-700 dark:text-green-400">{{ $trialDays }}-day free trial</p>
                        @endif
                        @if ($productId)
                            <p class="mt-1 text-xs text-gray-500">Product: {{ $productId }}</p>
                        @endif
                    </div>

                    <div class="flex flex-1 flex-col justify-between p-6">
                        <div>
                            <p class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">Available prices</p>
                            <div class="space-y-2">
                                @forelse ($prices as $idx => $price)
                                    @php
                                        $label = $getPriceLabel($price, $idx);
                                        $priceId = $getPriceId($price);
                                    @endphp
                                    <label class="flex cursor-pointer items-center justify-between rounded-lg border px-4 py-3 text-sm hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">
                                        <div class="flex items-center gap-3">
                                            <input type="radio" name="price[{{ $planKey }}]" value="{{ $priceId ?? $label }}" class="h-4 w-4 text-primary-600 focus:ring-primary-500" />
                                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ Str::headline($label) }}</span>
                                        </div>
                                        @if ($priceId)
                                            <span class="text-xs text-gray-500">{{ $priceId }}</span>
                                        @endif
                                    </label>
                                @empty
                                    <div class="rounded-md bg-yellow-50 p-3 text-xs text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-200">No prices configured for this plan.</div>
                                @endforelse
                            </div>
                        </div>

                        <div class="mt-6">
                            <button type="button" class="w-full rounded-lg bg-primary-600 px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-60" disabled>
                                Continue
                            </button>
                            <p class="mt-2 text-xs text-gray-500">Frontend mockup only. Integrate with your checkout action to enable.</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
