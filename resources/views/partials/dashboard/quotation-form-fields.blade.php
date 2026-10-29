{{-- Shared quotation form fields. $prefix = '' for create, 'edit-' for edit. $namePrefix for input names. --}}
@php
    $idPrefix = $idPrefix ?? '';
    $itemsContainer = $itemsContainer ?? 'quote-items-create';
    $companiesList = $quoteCompanies ?? $companies ?? collect();
    $productsList = $quoteProducts ?? collect();
@endphp
<div>
    <label class="block text-xs font-bold text-on-surface-variant mb-1">Select Customer *</label>
    <select required name="company_id" id="{{ $idPrefix }}quote-company" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary js-quote-company">
        <option value="">Select customer</option>
        @foreach($companiesList as $c)
            <option
                value="{{ $c->id }}"
                data-email="{{ e($c->email ?? '') }}"
                data-mobile="{{ e($c->mobile_number ?? '') }}"
                data-gstin="{{ e($c->gst_number ?? '') }}"
                data-address="{{ e(trim(implode("\n", array_filter([$c->address ?? null, $c->city ?? null, $c->sub_city ?? null])))) }}"
            >{{ $c->name }}</option>
        @endforeach
    </select>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    <div>
        <label class="block text-xs font-bold text-on-surface-variant mb-1">Status *</label>
        <select required name="status" id="{{ $idPrefix }}quote-status" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
            <option value="Sent">Sent</option>
            <option value="Approved">Approved</option>
            <option value="Lost">Lost</option>
        </select>
    </div>
    <div>
        <label class="block text-xs font-bold text-on-surface-variant mb-1">Issue Date *</label>
        <input required name="date" id="{{ $idPrefix }}quote-date" type="datetime-local" value="{{ date('Y-m-d\TH:i') }}" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    <div>
        <label class="block text-xs font-bold text-on-surface-variant mb-1">Salesman</label>
        <select name="sales_person_name" id="{{ $idPrefix }}quote-sales-person" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary js-quote-salesman">
            <option value="">Select salesman</option>
            @foreach(($salesExecutives ?? collect()) as $exec)
                <option value="{{ $exec->name }}">{{ $exec->name }}{{ !empty($exec->branch) ? ' · '.$exec->branch : '' }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-bold text-on-surface-variant mb-1">Sales Mobile</label>
        <input name="sales_person_mobile" id="{{ $idPrefix }}quote-sales-mobile" type="text" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary" placeholder="9820008237">
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    <div>
        <label class="block text-xs font-bold text-on-surface-variant mb-1">Party Mobile</label>
        <input name="party_mobile" id="{{ $idPrefix }}quote-party-mobile" type="text" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
    </div>
    <div>
        <label class="block text-xs font-bold text-on-surface-variant mb-1">Party GSTIN</label>
        <input name="party_gstin" id="{{ $idPrefix }}quote-party-gstin" type="text" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
    </div>
</div>

<div>
    <label class="block text-xs font-bold text-on-surface-variant mb-1">Party Address</label>
    <textarea name="party_address" id="{{ $idPrefix }}quote-party-address" rows="2" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary"></textarea>
</div>

<div class="rounded-xl border border-outline-variant/40 overflow-hidden">
    <div class="px-3 py-2 bg-surface-container-low flex items-center justify-between gap-2">
        <div>
            <p class="text-xs font-bold uppercase tracking-wide text-primary">Line Items</p>
            @if(($itemsContainer ?? '') === 'quote-items-create')
                <p class="text-[10px] text-on-surface-variant mt-0.5">Type a product name, size, HSN or code to search the catalog. Selecting a customer also adds products from salesman meetings.</p>
            @else
                <p class="text-[10px] text-on-surface-variant mt-0.5">Type a product name, size, HSN or code to search the catalog.</p>
            @endif
        </div>
        <button type="button" class="js-add-quote-item text-xs font-bold text-secondary hover:underline" data-target="{{ $itemsContainer }}">+ Add Row</button>
    </div>
    <div id="{{ $itemsContainer }}" class="js-quote-items divide-y divide-outline-variant/20 p-0" data-prefix="{{ $idPrefix }}" data-autofill-meeting="{{ ($itemsContainer ?? '') === 'quote-items-create' ? '1' : '0' }}" data-product-search="{{ route('admin.products.search') }}"></div>
    <div class="px-3 py-2 bg-surface-container-low flex flex-wrap items-center justify-end gap-4 text-xs border-t border-outline-variant/30">
        <span class="text-on-surface-variant">Total QTY: <strong class="js-quote-total-qty text-primary font-data-tabular">0</strong></span>
        <span class="text-on-surface-variant">Grand Total: <strong class="js-quote-grand-total text-primary font-data-tabular">₹ 0.00</strong></span>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    <div>
        <label class="block text-xs font-bold text-on-surface-variant mb-1">Make</label>
        <input name="product_make" id="{{ $idPrefix }}quote-make" type="text" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary" placeholder="e.g. Polycab">
    </div>
    <div>
        <label class="block text-xs font-bold text-on-surface-variant mb-1">Payment Terms</label>
        <input name="payment_terms" id="{{ $idPrefix }}quote-payment-terms" type="text" value="Against Proforma Invoice" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
    </div>
</div>

<div>
    <label class="block text-xs font-bold text-on-surface-variant mb-1">Terms &amp; Conditions</label>
    <textarea name="terms_conditions" id="{{ $idPrefix }}quote-terms" rows="4" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">{{ $defaultQuoteTerms ?? \App\Models\Quotation::defaultTerms() }}</textarea>
</div>

<div>
    <label class="block text-xs font-bold text-on-surface-variant mb-1">Internal Notes</label>
    <textarea name="notes" id="{{ $idPrefix }}quote-notes" rows="2" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary"></textarea>
</div>

<script type="application/json" id="{{ $idPrefix }}quote-products-json">@json($productsList)</script>
@if(($itemsContainer ?? '') === 'quote-items-create')
<script type="application/json" id="quote-meeting-products-json">@json($quoteMeetingProducts ?? new \stdClass())</script>
@endif
