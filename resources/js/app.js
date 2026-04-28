const SEARCH_URL = document.querySelector('meta[name="search-url"]').content;
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

container.querySelectorAll('.item-row').forEach(initRow);
updateSummary();
