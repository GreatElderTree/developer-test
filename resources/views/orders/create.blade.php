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
                @if (old('items'))
                    {{-- Repopulate rows on validation error --}}
                    @foreach (old('items') as $i => $oldItem)
                    <div class="item-row flex gap-3 items-start">
                        <div class="product-picker relative flex-1">
                            <input type="text"
                                class="search-input w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 @error("items.{$i}.product_id") border-red-400 @enderror"
                                placeholder="Search product…"
                                value="{{ $oldItem['product_name'] ?? '' }}"
                                autocomplete="off">
                            <input type="hidden" name="items[{{ $i }}][product_id]"   class="product-id-input"    value="{{ $oldItem['product_id']   ?? '' }}">
                            <input type="hidden" name="items[{{ $i }}][product_name]" class="product-name-input"  value="{{ $oldItem['product_name'] ?? '' }}">
                            <input type="hidden" name="items[{{ $i }}][_price]"       class="product-price-input" value="{{ $oldItem['_price']       ?? '0' }}">
                            @error("items.{$i}.product_id")
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                            <div class="search-dropdown absolute z-10 top-full left-0 right-0 bg-white border border-gray-200 rounded-lg shadow-lg mt-1 hidden">
                                <div class="results-list max-h-48 overflow-y-auto divide-y divide-gray-100"></div>
                                <div class="pagination-controls hidden items-center justify-between px-3 py-2 border-t border-gray-100 text-xs text-gray-500">
                                    <button type="button" class="prev-page px-2 py-1 rounded hover:bg-gray-100 disabled:opacity-30">← Prev</button>
                                    <span class="page-info"></span>
                                    <button type="button" class="next-page px-2 py-1 rounded hover:bg-gray-100 disabled:opacity-30">Next →</button>
                                </div>
                            </div>
                        </div>
                        <div class="w-24">
                            <input type="number" name="items[{{ $i }}][qty]"
                                value="{{ $oldItem['qty'] ?? 1 }}"
                                min="1"
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
                        <div class="product-picker relative flex-1">
                            <input type="text"
                                class="search-input w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                                placeholder="Search product…"
                                autocomplete="off">
                            <input type="hidden" name="items[0][product_id]"   class="product-id-input">
                            <input type="hidden" name="items[0][product_name]" class="product-name-input">
                            <input type="hidden" name="items[0][_price]"       class="product-price-input" value="0">
                            <div class="search-dropdown absolute z-10 top-full left-0 right-0 bg-white border border-gray-200 rounded-lg shadow-lg mt-1 hidden">
                                <div class="results-list max-h-48 overflow-y-auto divide-y divide-gray-100"></div>
                                <div class="pagination-controls hidden items-center justify-between px-3 py-2 border-t border-gray-100 text-xs text-gray-500">
                                    <button type="button" class="prev-page px-2 py-1 rounded hover:bg-gray-100 disabled:opacity-30">← Prev</button>
                                    <span class="page-info"></span>
                                    <button type="button" class="next-page px-2 py-1 rounded hover:bg-gray-100 disabled:opacity-30">Next →</button>
                                </div>
                            </div>
                        </div>
                        <div class="w-24">
                            <input type="number" name="items[0][qty]" value="1" min="1"
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
const SEARCH_URL = '{{ route('products.search') }}';
const container  = document.getElementById('items-container');
let rowIndex     = container.querySelectorAll('.item-row').length;

function escHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

class ProductPicker {
    constructor(row) {
        this.row         = row;
        this.page        = 1;
        this.lastPage    = 1;
        this.query       = '';
        this.timer       = null;

        this.searchInput  = row.querySelector('.search-input');
        this.idInput      = row.querySelector('.product-id-input');
        this.nameInput    = row.querySelector('.product-name-input');
        this.priceInput   = row.querySelector('.product-price-input');
        this.dropdown     = row.querySelector('.search-dropdown');
        this.resultsList  = row.querySelector('.results-list');
        this.prevBtn      = row.querySelector('.prev-page');
        this.nextBtn      = row.querySelector('.next-page');
        this.pageInfo     = row.querySelector('.page-info');
        this.pagination   = row.querySelector('.pagination-controls');

        this.bind();
    }

    bind() {
        this.searchInput.addEventListener('focus', () => {
            if (!this.idInput.value) this.fetch();
        });

        this.searchInput.addEventListener('input', () => {
            this.clearSelection();
            clearTimeout(this.timer);
            this.timer = setTimeout(() => {
                this.query = this.searchInput.value.trim();
                this.page  = 1;
                this.fetch();
            }, 250);
        });

        this.prevBtn.addEventListener('click', () => { this.page--; this.fetch(); });
        this.nextBtn.addEventListener('click', () => { this.page++; this.fetch(); });

        document.addEventListener('click', e => {
            if (!this.row.contains(e.target)) this.close();
        });
    }

    clearSelection() {
        this.idInput.value    = '';
        this.nameInput.value  = '';
        this.priceInput.value = '0';
        updateSummary();
    }

    async fetch() {
        const params = new URLSearchParams({ q: this.query, page: this.page });
        const { data, meta } = await fetch(`${SEARCH_URL}?${params}`).then(r => r.json());

        this.lastPage = meta.last_page;
        this.renderResults(data);
        this.renderPagination(meta);
        this.open();
    }

    renderResults(items) {
        if (!items.length) {
            this.resultsList.innerHTML =
                '<p class="px-3 py-2 text-sm text-gray-400">No products found.</p>';
            return;
        }

        this.resultsList.innerHTML = items.map(p =>
            `<button type="button"
                data-id="${escHtml(p.id)}" data-name="${escHtml(p.name)}" data-price="${escHtml(p.price)}"
                class="result-item w-full text-left px-3 py-2 text-sm hover:bg-indigo-50 flex justify-between gap-2">
                <span>${escHtml(p.name)}</span>
                <span class="text-gray-400 font-mono shrink-0">€${escHtml(p.price)}</span>
            </button>`
        ).join('');

        this.resultsList.querySelectorAll('.result-item').forEach(btn =>
            btn.addEventListener('click', () => this.select(btn.dataset))
        );
    }

    select({ id, name, price }) {
        this.searchInput.value = name;
        this.idInput.value     = id;
        this.nameInput.value   = name;
        this.priceInput.value  = price;
        this.close();
        updateSummary();
    }

    renderPagination(meta) {
        const multi = meta.last_page > 1;
        this.pagination.classList.toggle('hidden', !multi);
        this.pagination.style.display = multi ? 'flex' : '';
        if (!multi) return;
        this.prevBtn.disabled     = meta.current_page <= 1;
        this.nextBtn.disabled     = meta.current_page >= meta.last_page;
        this.pageInfo.textContent = `${meta.current_page} / ${meta.last_page}`;
    }

    open()  { this.dropdown.classList.remove('hidden'); }
    close() { this.dropdown.classList.add('hidden'); }
}

function rowTemplate(index) {
    return `
    <div class="item-row flex gap-3 items-start">
        <div class="product-picker relative flex-1">
            <input type="text"
                class="search-input w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                placeholder="Search product…" autocomplete="off">
            <input type="hidden" name="items[${index}][product_id]"   class="product-id-input">
            <input type="hidden" name="items[${index}][product_name]" class="product-name-input">
            <input type="hidden" name="items[${index}][_price]"       class="product-price-input" value="0">
            <div class="search-dropdown absolute z-10 top-full left-0 right-0 bg-white border border-gray-200 rounded-lg shadow-lg mt-1 hidden">
                <div class="results-list max-h-48 overflow-y-auto divide-y divide-gray-100"></div>
                <div class="pagination-controls hidden items-center justify-between px-3 py-2 border-t border-gray-100 text-xs text-gray-500">
                    <button type="button" class="prev-page px-2 py-1 rounded hover:bg-gray-100 disabled:opacity-30">← Prev</button>
                    <span class="page-info"></span>
                    <button type="button" class="next-page px-2 py-1 rounded hover:bg-gray-100 disabled:opacity-30">Next →</button>
                </div>
            </div>
        </div>
        <div class="w-24">
            <input type="number" name="items[${index}][qty]" value="1" min="1"
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

function addRow() {
    const div = document.createElement('div');
    div.innerHTML = rowTemplate(rowIndex++).trim();
    const row = div.firstElementChild;
    container.appendChild(row);
    initRow(row);
    updateSummary();
}

function initRow(row) {
    new ProductPicker(row);
    row.querySelector('.qty-input').addEventListener('input', updateSummary);
}

function reindexRows() {
    container.querySelectorAll('.item-row').forEach((row, i) => {
        row.querySelector('.product-id-input').name    = `items[${i}][product_id]`;
        row.querySelector('.product-name-input').name  = `items[${i}][product_name]`;
        row.querySelector('.product-price-input').name = `items[${i}][_price]`;
        row.querySelector('.qty-input').name           = `items[${i}][qty]`;
    });
    rowIndex = container.querySelectorAll('.item-row').length;
}

function updateSummary() {
    let subtotal = 0;

    container.querySelectorAll('.item-row').forEach(row => {
        const price = parseFloat(row.querySelector('.product-price-input').value) || 0;
        const qty   = parseInt(row.querySelector('.qty-input').value)             || 0;
        const line  = price * qty;
        subtotal   += line;
        row.querySelector('.line-total').textContent = '€' + line.toFixed(2);
    });

    let discountPct = subtotal > 100 ? 10 : 0;
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

document.getElementById('add-row').addEventListener('click', addRow);

container.addEventListener('click', e => {
    const btn = e.target.closest('.remove-row');
    if (!btn) return;
    const rows = container.querySelectorAll('.item-row');
    if (rows.length <= 1) return;
    btn.closest('.item-row').remove();
    reindexRows();
    updateSummary();
});

// Initialise all existing rows (new form or repopulated after validation error)
container.querySelectorAll('.item-row').forEach(initRow);
updateSummary();
</script>

</body>
</html>
