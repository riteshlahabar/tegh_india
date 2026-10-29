@extends('layouts.telecaller')

@section('content')
@php
    $preselectCompany = $preselectCompany ?? null;
    $preselectId = (int) ($preselectCompany?->id ?? 0);
    $preselectLabel = $preselectCompany
        ? trim($preselectCompany->name.($preselectCompany->city ? ' · '.$preselectCompany->city : ''))
        : '';
@endphp
<div class="space-y-6 max-w-6xl">
    <section class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl sm:text-3xl font-bold text-primary tracking-tight">Quotations</h2>
            <p class="text-sm text-on-surface-variant">Search the product catalog, add line items, and save a quote against a lead.</p>
        </div>
    </section>

    <section class="bg-white border border-outline-variant/30 rounded-2xl p-5 sm:p-6 shadow-sm">
        <h3 class="font-title-sm text-primary mb-4">Create quotation</h3>
        <form method="POST" action="{{ route('telecaller.quotations.store') }}" id="tc-quote-form" class="space-y-4">
            @csrf
            <div>
                <div class="flex items-center justify-between gap-2 mb-1">
                    <label class="block text-xs font-bold text-on-surface-variant">Customer *</label>
                    <button type="button" onclick="toggleTcCustomerModal(true)" class="text-xs font-bold text-secondary hover:underline">+ Add customer</button>
                </div>
                <input type="hidden" name="company_id" id="tc-company-id" value="{{ $preselectId ?: '' }}">
                <input type="search" id="tc-customer-search" value="{{ $preselectLabel }}"
                       placeholder="Type name, city or mobile…"
                       class="w-full border-outline-variant/50 rounded-lg text-sm" autocomplete="off">
                <div id="tc-customer-results" class="mt-2 border border-outline-variant/30 rounded-lg divide-y divide-outline-variant/20 hidden max-h-56 overflow-y-auto bg-white"></div>
                <p id="tc-customer-hint" class="text-[11px] text-on-surface-variant mt-1">{{ $preselectId ? 'Customer selected. Type to pick a different lead.' : 'Start typing to search your leads, or add a new customer.' }}</p>
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant mb-1">Call outcome (optional)</label>
                    <select name="call_outcome" class="w-full border-outline-variant/50 rounded-lg text-sm">
                        <option value="">—</option>
                        @foreach(\App\Models\Call::OUTCOMES as $opt)
                            <option value="{{ $opt }}" @selected(old('call_outcome') === $opt)>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant mb-1">Call remarks (optional)</label>
                    <input name="call_remarks" value="{{ old('call_remarks') }}" class="w-full border-outline-variant/50 rounded-lg text-sm" placeholder="Short note for this outcome">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-on-surface-variant mb-1">Search products</label>
                <input type="search" id="tc-product-search" placeholder="Type name, size, HSN or code…"
                       class="w-full border-outline-variant/50 rounded-lg text-sm" autocomplete="off">
                <div id="tc-product-results" class="mt-2 border border-outline-variant/30 rounded-lg divide-y divide-outline-variant/20 hidden max-h-56 overflow-y-auto bg-white"></div>
                <p id="tc-product-hint" class="text-[11px] text-on-surface-variant mt-1">Start typing to search the catalog. Tap a product to add a line.</p>
            </div>

            <div class="rounded-xl border border-outline-variant/40 overflow-hidden">
                <div class="px-3 py-2 bg-surface-container-low flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-wide text-primary">Line items</p>
                    <button type="button" id="tc-add-custom" class="text-xs font-bold text-secondary hover:underline">+ Custom line</button>
                </div>
                <div id="tc-quote-items" class="divide-y divide-outline-variant/20"></div>
                <div class="px-3 py-2 bg-surface-container-low text-right text-xs">
                    Grand total: <strong id="tc-grand-total" class="font-data-tabular text-primary">₹ 0.00</strong>
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant mb-1">Make</label>
                    <input name="product_make" value="{{ old('product_make') }}" class="w-full border-outline-variant/50 rounded-lg text-sm" placeholder="e.g. Polycab">
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant mb-1">Payment terms</label>
                    <input name="payment_terms" value="{{ old('payment_terms', 'Against Proforma Invoice') }}" class="w-full border-outline-variant/50 rounded-lg text-sm">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-on-surface-variant mb-1">Internal notes</label>
                <textarea name="notes" rows="2" class="w-full border-outline-variant/50 rounded-lg text-sm">{{ old('notes') }}</textarea>
            </div>
            <button class="w-full sm:w-auto bg-secondary text-white font-bold px-6 py-3 rounded-lg">Save quotation</button>
        </form>
    </section>

    <section class="bg-white border border-outline-variant/30 rounded-2xl overflow-hidden shadow-sm">
        <div class="px-5 py-4 border-b border-outline-variant/20">
            <h3 class="font-title-sm text-primary">Recent quotations</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left min-w-[640px]">
                <thead class="bg-surface-container-low text-[11px] uppercase tracking-wider text-on-surface-variant">
                    <tr>
                        <th class="px-5 py-3">Quote no.</th>
                        <th class="px-5 py-3">Customer</th>
                        <th class="px-5 py-3">Amount</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    @forelse($allQuotations as $quote)
                        <tr>
                            <td class="px-5 py-3 font-bold text-primary font-data-tabular">{{ $quote['quotation_number'] ?? '' }}</td>
                            <td class="px-5 py-3 text-sm">{{ $quote['company_name'] ?? '—' }}</td>
                            <td class="px-5 py-3 text-sm font-bold font-data-tabular">₹ {{ \App\Support\IndianNumber::format((float) ($quote['amount'] ?? 0), 2) }}</td>
                            <td class="px-5 py-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-blue-100 text-blue-800">{{ $quote['status'] ?? '' }}</span>
                            </td>
                            <td class="px-5 py-3 text-xs font-data-tabular">{{ $quote['date_display'] ?? $quote['date'] ?? '—' }}</td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('telecaller.quotations.pdf', $quote['id']) }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-primary text-on-primary">
                                    PDF
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center text-sm text-on-surface-variant">No quotations yet. Create one above.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<script>
(function () {
    const customerSearchUrl = @json(route('telecaller.companies.search'));
    const customerInput = document.getElementById('tc-customer-search');
    const customerIdInput = document.getElementById('tc-company-id');
    const customerResultsEl = document.getElementById('tc-customer-results');
    const customerHintEl = document.getElementById('tc-customer-hint');
    const quoteForm = document.getElementById('tc-quote-form');
    let customerTimer = null;
    let selectedCustomerLabel = customerInput?.value?.trim() || '';

    function hideCustomerResults() {
        customerResultsEl.classList.add('hidden');
        customerResultsEl.innerHTML = '';
    }

    function selectCustomer(company) {
        selectedCustomerLabel = company.label || company.name || '';
        customerIdInput.value = company.id || '';
        customerInput.value = selectedCustomerLabel;
        hideCustomerResults();
        customerHintEl.textContent = 'Customer selected. Type to pick a different lead.';
    }

    function renderCustomers(payload) {
        const companies = payload.companies || [];
        const total = payload.total || 0;
        if (!companies.length) {
            hideCustomerResults();
            customerHintEl.textContent = customerInput.value.trim()
                ? 'No customers match that search.'
                : 'No leads in your queue yet.';
            return;
        }
        customerResultsEl.classList.remove('hidden');
        customerHintEl.textContent = total > companies.length
            ? `Showing ${companies.length} of ${total}. Type more to narrow.`
            : `${total} customer(s) found. Tap to select.`;
        customerResultsEl.innerHTML = companies.map(() => (
            `<button type="button" class="tc-pick-customer w-full text-left px-3 py-2 hover:bg-primary/5">
                <p class="text-sm font-semibold text-primary"></p>
                <p class="text-[11px] text-on-surface-variant"></p>
            </button>`
        )).join('');
        customerResultsEl.querySelectorAll('.tc-pick-customer').forEach((btn, i) => {
            const company = companies[i];
            btn.querySelector('p').textContent = company.name || 'Customer';
            btn.querySelectorAll('p')[1].textContent = [company.city, company.mobile, company.contact].filter(Boolean).join(' · ');
            btn.onclick = () => selectCustomer(company);
        });
    }

    async function fetchCustomers(q) {
        try {
            const url = new URL(customerSearchUrl, window.location.origin);
            if (q) url.searchParams.set('q', q);
            url.searchParams.set('limit', '40');
            const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
            if (!res.ok) {
                customerHintEl.textContent = 'Could not load customers (' + res.status + ').';
                hideCustomerResults();
                return;
            }
            renderCustomers(await res.json());
        } catch (e) {
            customerHintEl.textContent = 'Network error while loading customers.';
        }
    }

    customerInput.addEventListener('input', () => {
        if (customerInput.value.trim() !== selectedCustomerLabel) {
            customerIdInput.value = '';
        }
        clearTimeout(customerTimer);
        customerTimer = setTimeout(() => fetchCustomers(customerInput.value.trim()), 250);
    });
    customerInput.addEventListener('focus', () => {
        fetchCustomers(customerInput.value.trim());
    });
    quoteForm.addEventListener('submit', (e) => {
        if (!customerIdInput.value) {
            e.preventDefault();
            customerHintEl.textContent = 'Select a customer from the search list.';
            customerInput.focus();
            fetchCustomers(customerInput.value.trim());
        }
    });
    document.addEventListener('click', (e) => {
        if (!customerResultsEl.contains(e.target) && e.target !== customerInput) {
            hideCustomerResults();
        }
    });

    const searchUrl = @json(route('telecaller.products.search'));
    const searchInput = document.getElementById('tc-product-search');
    const resultsEl = document.getElementById('tc-product-results');
    const hintEl = document.getElementById('tc-product-hint');
    const itemsEl = document.getElementById('tc-quote-items');
    const grandEl = document.getElementById('tc-grand-total');
    let timer = null;
    let itemIndex = 0;

    function money(n) {
        return Number(n || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function lineAmount(row) {
        const qty = Number(row.querySelector('[data-qty]')?.value) || 0;
        const list = Number(row.querySelector('[data-list]')?.value) || 0;
        const disc = Number(row.querySelector('[data-disc]')?.value) || 0;
        return Math.round(list * (1 - disc / 100) * qty * 100) / 100;
    }

    function refreshTotals() {
        let grand = 0;
        itemsEl.querySelectorAll('.tc-line').forEach((row) => {
            const amt = lineAmount(row);
            grand += amt;
            const label = row.querySelector('[data-amount]');
            if (label) label.textContent = '₹ ' + money(amt);
        });
        grandEl.textContent = '₹ ' + money(grand);
    }

    function addLine(product) {
        const idx = itemIndex++;
        const name = product?.name || '';
        const hsn = product?.hsn_code || '';
        const unit = product?.unit || product?.usage_unit || 'Metre';
        const pid = product?.id || '';
        const row = document.createElement('div');
        row.className = 'tc-line p-3 space-y-2 bg-white';
        row.innerHTML = `
            <div class="flex items-start gap-2">
                <input type="hidden" name="items[${idx}][product_id]" value="${pid}">
                <input type="hidden" name="items[${idx}][hsn_code]" value="${hsn.replace(/"/g, '&quot;')}">
                <input type="hidden" name="items[${idx}][unit]" value="${unit.replace(/"/g, '&quot;')}">
                <textarea required name="items[${idx}][description]" rows="2" class="flex-1 border-outline-variant/50 rounded text-sm" placeholder="Description">${name.replace(/</g, '&lt;')}</textarea>
                <button type="button" class="tc-remove shrink-0 w-8 h-8 rounded-full text-red-600 hover:bg-red-50">✕</button>
            </div>
            <div class="grid grid-cols-3 gap-2">
                <div>
                    <label class="block text-[10px] font-bold text-on-surface-variant mb-0.5">Qty *</label>
                    <input required data-qty type="number" step="0.001" min="0.001" name="items[${idx}][qty]" value="1" class="w-full border-outline-variant/50 rounded text-sm">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-on-surface-variant mb-0.5">List price *</label>
                    <input required data-list type="number" step="0.01" min="0" name="items[${idx}][list_price]" value="" class="w-full border-outline-variant/50 rounded text-sm">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-on-surface-variant mb-0.5">Disc %</label>
                    <input data-disc type="number" step="0.01" min="0" max="100" name="items[${idx}][discount_percent]" value="0" class="w-full border-outline-variant/50 rounded text-sm">
                </div>
            </div>
            <p class="text-right text-xs font-bold text-primary" data-amount>₹ 0.00</p>
        `;
        row.querySelector('.tc-remove').onclick = () => { row.remove(); refreshTotals(); };
        row.querySelectorAll('input').forEach((el) => el.addEventListener('input', refreshTotals));
        itemsEl.appendChild(row);
        refreshTotals();
    }

    document.getElementById('tc-add-custom').addEventListener('click', () => addLine({ name: '' }));

    function renderResults(payload) {
        const products = payload.products || [];
        const total = payload.total || 0;
        if (!products.length) {
            resultsEl.classList.add('hidden');
            hintEl.textContent = searchInput.value.trim()
                ? 'No products match that search.'
                : 'No products in catalog yet. Ask admin to add products.';
            return;
        }
        resultsEl.classList.remove('hidden');
        hintEl.textContent = total > products.length
            ? `Showing ${products.length} of ${total}. Type more to narrow.`
            : `${total} product(s) found. Tap to add.`;
        resultsEl.innerHTML = products.map((p, i) => {
            const meta = [p.product_code, p.hsn_code, p.size_sqmm].filter(Boolean).join(' · ');
            return `<button type="button" class="tc-pick w-full text-left px-3 py-2 hover:bg-primary/5" data-i="${i}">
                <p class="text-sm font-semibold text-primary"></p>
                <p class="text-[11px] text-on-surface-variant"></p>
            </button>`;
        }).join('');
        resultsEl.querySelectorAll('.tc-pick').forEach((btn, i) => {
            const p = products[i];
            btn.querySelector('p').textContent = p.name || 'Product';
            btn.querySelectorAll('p')[1].textContent = [p.product_code, p.hsn_code, p.size_sqmm].filter(Boolean).join(' · ');
            btn.onclick = () => {
                addLine(p);
                searchInput.value = '';
                resultsEl.classList.add('hidden');
            };
        });
    }

    async function fetchProducts(q) {
        try {
            const url = new URL(searchUrl, window.location.origin);
            if (q) url.searchParams.set('q', q);
            url.searchParams.set('limit', '40');
            const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
            if (!res.ok) {
                hintEl.textContent = 'Could not load products (' + res.status + ').';
                resultsEl.classList.add('hidden');
                return;
            }
            renderResults(await res.json());
        } catch (e) {
            hintEl.textContent = 'Network error while loading products.';
        }
    }

    searchInput.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => fetchProducts(searchInput.value.trim()), 250);
    });
    searchInput.addEventListener('focus', () => {
        if (!resultsEl.children.length) fetchProducts(searchInput.value.trim());
    });
})();
</script>

<div id="modal-tc-add-customer" class="fixed inset-0 z-50 {{ ($errors->any() && old('_form') === 'add-customer') ? '' : 'hidden' }}" aria-modal="true" role="dialog">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="toggleTcCustomerModal(false)"></div>
    <div class="relative z-10 h-full w-full flex items-end sm:items-center justify-center p-0 sm:p-4 pointer-events-none">
        <div class="pointer-events-auto bg-white border border-outline-variant/30 w-full max-w-lg max-h-[92dvh] sm:max-h-[90vh] rounded-t-2xl sm:rounded-2xl shadow-2xl flex flex-col overflow-hidden">
            <form action="{{ route('telecaller.companies.store') }}" method="POST" class="flex flex-col min-h-0 max-h-[92dvh] sm:max-h-[90vh]">
                @csrf
                <input type="hidden" name="_form" value="add-customer">
                <div class="shrink-0 flex items-center justify-between gap-3 px-4 sm:px-6 py-3 sm:py-4 border-b border-outline-variant/30 bg-white">
                    <h3 class="font-headline-md text-primary font-bold text-lg sm:text-xl pr-2">New customer</h3>
                    <button type="button" onclick="toggleTcCustomerModal(false)" class="shrink-0 w-10 h-10 flex items-center justify-center rounded-full bg-surface-container-low text-on-surface-variant hover:text-primary" aria-label="Close">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <div class="flex-1 min-h-0 overflow-y-auto overscroll-contain px-4 sm:px-6 py-4 space-y-4">
                    @include('partials.telecaller.company-enquiry-fields')
                </div>
                <div class="shrink-0 border-t border-outline-variant/20 bg-white px-4 sm:px-6 py-3 pb-[max(0.75rem,env(safe-area-inset-bottom))]">
                    <button type="submit" class="w-full bg-primary text-on-primary py-3 rounded-lg font-bold shadow-md hover:bg-secondary transition-all">SAVE CUSTOMER</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    function toggleTcCustomerModal(open) {
        const modal = document.getElementById('modal-tc-add-customer');
        if (!modal) return;
        const shouldOpen = typeof open === 'boolean' ? open : modal.classList.contains('hidden');
        modal.classList.toggle('hidden', !shouldOpen);
        document.body.classList.toggle('overflow-hidden', shouldOpen);
    }
    window.toggleTcCustomerModal = toggleTcCustomerModal;
    if (!document.getElementById('modal-tc-add-customer')?.classList.contains('hidden')) {
        document.body.classList.add('overflow-hidden');
    }

    const citySubMap = @json(
        ($cities ?? collect())->mapWithKeys(fn ($c) => [$c->name => $c->subCities->pluck('name')->values()])
    );
    const cityEl = document.getElementById('tc-register-city');
    const subEl = document.getElementById('tc-register-sub-city');
    function applySubCities(keepSelected) {
        if (!cityEl || !subEl) return;
        const cityName = cityEl.value;
        const subs = citySubMap[cityName] || [];
        const current = keepSelected ? (subEl.getAttribute('data-selected') || subEl.value || '') : '';
        subEl.innerHTML = '<option value="">Select sub city</option>';
        subs.forEach((name) => {
            const opt = document.createElement('option');
            opt.value = name;
            opt.textContent = name;
            if (current && name === current) opt.selected = true;
            subEl.appendChild(opt);
        });
        subEl.disabled = subs.length === 0 && !current;
        if (subs.length === 0 && !current) {
            subEl.innerHTML = '<option value="">No sub cities for this city</option>';
            subEl.disabled = true;
        }
    }
    cityEl?.addEventListener('change', () => applySubCities(false));
    applySubCities(true);
})();
</script>
@endsection
