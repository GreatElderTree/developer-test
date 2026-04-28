<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Place an Order</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen py-10">

<div class="max-w-2xl mx-auto px-4">

    <h1 class="text-3xl font-bold text-gray-800 mb-2">Place an Order</h1>
    <p class="text-gray-500 mb-8">Enter your email and add products to your order. Premium customers receive an extra 5% discount.</p>

    {{-- Success flash --}}
    @if (session('success'))
        <div class="bg-green-50 border border-green-300 text-green-800 rounded-lg px-4 py-3 mb-6 flex items-start gap-3">
            <svg class="w-5 h-5 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- General errors --}}
    @if ($errors->has('items'))
        <div class="bg-red-50 border border-red-300 text-red-800 rounded-lg px-4 py-3 mb-6">
            {{ $errors->first('items') }}
        </div>
    @endif

    <form method="POST" action="{{ route('orders.store') }}" id="order-form">
        @csrf

        {{-- Customer email --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Your Details</h2>
            <label class="block text-sm font-medium text-gray-600 mb-1" for="customer_email">
                Email address
            </label>
            <input
                type="email"
                id="customer_email"
                name="customer_email"
                value="{{ old('customer_email') }}"
                placeholder="you@example.com"
                required
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 @error('customer_email') border-red-400 @enderror"
            >
            @error('customer_email')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
            <p class="text-xs text-gray-400 mt-2">Existing customers are resolved automatically. Guests are welcome!</p>
        </div>

        {{-- Order items --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Order Items</h2>

            <div id="items-container" class="space-y-3">
                {{-- Repopulate on validation error --}}
                @if (old('items'))
                    @foreach (old('items') as $i => $oldItem)
                    <div class="item-row flex gap-3 items-start">
                        <div class="flex-1">
                            <select name="items[{{ $i }}][product_id]"
                                class="product-select w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                                <option value="">— Select product —</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}"
                                        data-price="{{ $product->price }}"
                                        {{ (int)($oldItem['product_id'] ?? 0) === $product->id ? 'selected' : '' }}>
                                        {{ $product->name }} — €{{ number_format($product->price, 2) }}
                                    </option>
                                @endforeach
                            </select>
                            @error("items.{$i}.product_id")
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="w-24">
                            <input type="number" name="items[{{ $i }}][qty]"
                                value="{{ $oldItem['qty'] ?? 1 }}"
                                min="1"
                                placeholder="Qty"
                                class="qty-input w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                            @error("items.{$i}.qty")
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="w-24 pt-2 text-sm text-gray-600 line-total text-right font-mono">€0.00</div>
                        <button type="button" class="remove-row pt-1 text-red-400 hover:text-red-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    @endforeach
                @else
                    {{-- Default first row --}}
                    <div class="item-row flex gap-3 items-start">
                        <div class="flex-1">
                            <select name="items[0][product_id]"
                                class="product-select w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                                <option value="">— Select product —</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" data-price="{{ $product->price }}">
                                        {{ $product->name }} — €{{ number_format($product->price, 2) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="w-24">
                            <input type="number" name="items[0][qty]" value="1" min="1" placeholder="Qty"
                                class="qty-input w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        </div>
                        <div class="w-24 pt-2 text-sm text-gray-600 line-total text-right font-mono">€0.00</div>
                        <button type="button" class="remove-row pt-1 text-red-400 hover:text-red-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                @endif
            </div>

            <button type="button" id="add-row"
                class="mt-4 text-indigo-600 hover:text-indigo-800 text-sm font-medium flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add product
            </button>
        </div>

        {{-- Order summary --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Order Summary</h2>
            <div class="space-y-2 text-sm text-gray-700">
                <div class="flex justify-between">
                    <span>Subtotal</span>
                    <span id="summary-subtotal" class="font-mono">€0.00</span>
                </div>
                <div class="flex justify-between text-green-600" id="discount-row" style="display:none!important">
                    <span id="discount-label">Discount</span>
                    <span id="summary-discount" class="font-mono">−€0.00</span>
                </div>
                <div class="flex justify-between font-bold text-base border-t pt-2">
                    <span>Estimated Total</span>
                    <span id="summary-total" class="font-mono">€0.00</span>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-3">
                Discounts applied at checkout: 10% for orders over €100, additional 5% for premium customers (max 20%).
            </p>
        </div>

        <button type="submit"
            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-6 rounded-xl transition-colors">
            Place Order
        </button>
    </form>
</div>

<script>
    const products = @json($products->keyBy('id'));
    const container = document.getElementById('items-container');
    let rowIndex = container.querySelectorAll('.item-row').length;

    function rowTemplate(index) {
        const options = Object.values(products).map(p =>
            `<option value="${p.id}" data-price="${p.price}">${p.name} — €${parseFloat(p.price).toFixed(2)}</option>`
        ).join('');

        return `
        <div class="item-row flex gap-3 items-start">
            <div class="flex-1">
                <select name="items[${index}][product_id]"
                    class="product-select w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    <option value="">— Select product —</option>${options}
                </select>
            </div>
            <div class="w-24">
                <input type="number" name="items[${index}][qty]" value="1" min="1" placeholder="Qty"
                    class="qty-input w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
            </div>
            <div class="w-24 pt-2 text-sm text-gray-600 line-total text-right font-mono">€0.00</div>
            <button type="button" class="remove-row pt-1 text-red-400 hover:text-red-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>`;
    }

    document.getElementById('add-row').addEventListener('click', () => {
        const div = document.createElement('div');
        div.innerHTML = rowTemplate(rowIndex++).trim();
        container.appendChild(div.firstChild);
        bindRow(container.lastElementChild);
        updateSummary();
    });

    container.addEventListener('click', e => {
        const btn = e.target.closest('.remove-row');
        if (!btn) return;
        const rows = container.querySelectorAll('.item-row');
        if (rows.length <= 1) return;
        btn.closest('.item-row').remove();
        reindexRows();
        updateSummary();
    });

    function bindRow(row) {
        row.querySelector('.product-select').addEventListener('change', updateSummary);
        row.querySelector('.qty-input').addEventListener('input', updateSummary);
    }

    function reindexRows() {
        container.querySelectorAll('.item-row').forEach((row, i) => {
            row.querySelector('.product-select').name = `items[${i}][product_id]`;
            row.querySelector('.qty-input').name      = `items[${i}][qty]`;
        });
        rowIndex = container.querySelectorAll('.item-row').length;
    }

    function updateSummary() {
        let subtotal = 0;

        container.querySelectorAll('.item-row').forEach(row => {
            const select = row.querySelector('.product-select');
            const qty    = parseInt(row.querySelector('.qty-input').value) || 0;
            const price  = select.value ? parseFloat(products[select.value]?.price ?? 0) : 0;
            const line   = price * qty;
            subtotal += line;
            row.querySelector('.line-total').textContent = '€' + line.toFixed(2);
        });

        // Estimate discount client-side (mirrors server rules)
        let discountPct = 0;
        if (subtotal > 100) discountPct = Math.min(20, discountPct + 10);

        const discountAmt = subtotal * discountPct / 100;
        const total       = subtotal - discountAmt;

        document.getElementById('summary-subtotal').textContent = '€' + subtotal.toFixed(2);
        document.getElementById('summary-total').textContent    = '€' + total.toFixed(2);

        const discountRow = document.getElementById('discount-row');
        if (discountPct > 0) {
            document.getElementById('discount-label').textContent  = `Discount (${discountPct}%)`;
            document.getElementById('summary-discount').textContent = '−€' + discountAmt.toFixed(2);
            discountRow.style.removeProperty('display');
        } else {
            discountRow.style.setProperty('display', 'none', 'important');
        }
    }

    // Bind existing rows
    container.querySelectorAll('.item-row').forEach(bindRow);
    updateSummary();
</script>

</body>
</html>
