    <!-- ================= VIEW: QUOTATIONS ================= -->
    <div id="view-quotations" class="space-y-xl">
        <section class="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
            <div>
                <h2 class="font-headline-md text-headline-md text-primary font-bold">Quotations &amp; Billing</h2>
                <p class="font-body-sm text-on-surface-variant">Tegh Cables style quotations with line items, PDF download, and email.</p>
            </div>
            <div class="flex flex-col sm:flex-row flex-wrap gap-2 w-full lg:w-auto">
                <button type="button" onclick="toggleModal('modal-smtp-settings')" class="bg-surface-container-high text-primary border border-outline-variant px-5 py-3 rounded-lg font-title-sm flex items-center justify-center gap-2 hover:bg-surface-container-highest transition-all w-full sm:w-auto">
                    <span class="material-symbols-outlined">mail</span> SMTP Settings
                </button>
                <button type="button" onclick="openCreateQuoteModal()" class="bg-primary text-on-primary hover:bg-secondary-container hover:text-on-secondary-container px-6 py-3 rounded-lg font-title-sm flex items-center justify-center gap-2 shadow-md w-full sm:w-auto">
                    <span class="material-symbols-outlined">request_quote</span> Create Quotation
                </button>
            </div>
        </section>

        <section class="bg-white border border-outline-variant/30 rounded-xl px-5 py-4 flex flex-wrap items-center justify-between gap-3 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined {{ !empty($smtpReady) ? 'text-green-600' : 'text-on-surface-variant' }}">
                    {{ !empty($smtpReady) ? 'mark_email_read' : 'outgoing_mail' }}
                </span>
                <div>
                    <p class="text-sm font-semibold text-primary">
                        SMTP {{ !empty($smtpReady) ? 'ready' : 'not configured' }}
                    </p>
                    <p class="text-xs text-on-surface-variant">
                        @if(!empty($smtpReady))
                            Sending from {{ $smtpSettings['from_address'] ?? '' }} via {{ $smtpSettings['host'] ?? '' }}
                        @else
                            Configure SMTP to email quotation PDFs to customers.
                        @endif
                    </p>
                </div>
            </div>
            <button type="button" onclick="toggleModal('modal-smtp-settings')" class="text-xs font-bold uppercase text-secondary hover:underline">
                {{ !empty($smtpReady) ? 'Edit SMTP' : 'Set up SMTP' }}
            </button>
        </section>

        @php
            $filters = $quoteFilters ?? [];
            $quoteCities = ($quoteCompanies ?? collect())->pluck('city')->filter()->unique()->sort()->values();
            $filtersActive = collect($filters)->filter(fn ($v) => $v !== null && $v !== '' && $v !== 0)->isNotEmpty();
        @endphp
        <section class="bg-white border border-outline-variant/30 rounded-2xl p-4 shadow-sm">
            <form method="GET" action="{{ route('admin.quotations') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8 gap-3">
                <div class="sm:col-span-2">
                    <label class="block text-[11px] font-bold text-on-surface-variant mb-1">Search</label>
                    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Quote no., customer, mobile…" class="w-full py-2.5 px-3 bg-surface-container-low border-none rounded-lg text-sm focus:ring-2 focus:ring-secondary">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-on-surface-variant mb-1">Customer</label>
                    <select name="company_id" class="w-full py-2.5 px-3 bg-surface-container-low border-none rounded-lg text-sm focus:ring-2 focus:ring-secondary">
                        <option value="">All</option>
                        @foreach(($quoteCompanies ?? collect()) as $c)
                            <option value="{{ $c->id }}" @selected((int) ($filters['company_id'] ?? 0) === (int) $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-on-surface-variant mb-1">Sales person</label>
                    <select name="salesman_id" class="w-full py-2.5 px-3 bg-surface-container-low border-none rounded-lg text-sm focus:ring-2 focus:ring-secondary">
                        <option value="">All</option>
                        @foreach(($salesExecutives ?? collect()) as $exec)
                            <option value="{{ $exec->id }}" @selected((int) ($filters['salesman_id'] ?? 0) === (int) $exec->id)>{{ $exec->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-on-surface-variant mb-1">Telecaller</label>
                    <select name="telecaller_id" class="w-full py-2.5 px-3 bg-surface-container-low border-none rounded-lg text-sm focus:ring-2 focus:ring-secondary">
                        <option value="">All</option>
                        @foreach(($telecallers ?? collect()) as $tc)
                            <option value="{{ $tc->id }}" @selected((int) ($filters['telecaller_id'] ?? 0) === (int) $tc->id)>{{ $tc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-on-surface-variant mb-1">Quote status</label>
                    <select name="status" class="w-full py-2.5 px-3 bg-surface-container-low border-none rounded-lg text-sm focus:ring-2 focus:ring-secondary">
                        <option value="">All</option>
                        <option value="Sent" @selected(($filters['status'] ?? '') === 'Sent')>Sent</option>
                        <option value="Approved" @selected(($filters['status'] ?? '') === 'Approved')>Approved</option>
                        <option value="Lost" @selected(($filters['status'] ?? '') === 'Lost')>Lost</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-on-surface-variant mb-1">City</label>
                    <select name="city" class="w-full py-2.5 px-3 bg-surface-container-low border-none rounded-lg text-sm focus:ring-2 focus:ring-secondary">
                        <option value="">All</option>
                        @foreach($quoteCities as $cityName)
                            <option value="{{ $cityName }}" @selected(($filters['city'] ?? '') === $cityName)>{{ $cityName }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-on-surface-variant mb-1">From</label>
                    <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="w-full py-2.5 px-3 bg-surface-container-low border-none rounded-lg text-sm focus:ring-2 focus:ring-secondary">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-on-surface-variant mb-1">To</label>
                    <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="w-full py-2.5 px-3 bg-surface-container-low border-none rounded-lg text-sm focus:ring-2 focus:ring-secondary">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-on-surface-variant mb-1">Lead stage</label>
                    <select name="stage_group" class="w-full py-2.5 px-3 bg-surface-container-low border-none rounded-lg text-sm focus:ring-2 focus:ring-secondary">
                        <option value="">All</option>
                        <option value="won" @selected(($filters['stage_group'] ?? '') === 'won')>Win</option>
                        <option value="lost" @selected(($filters['stage_group'] ?? '') === 'lost')>Lost</option>
                        <option value="other" @selected(($filters['stage_group'] ?? '') === 'other')>Other</option>
                    </select>
                </div>
                <div class="flex items-end gap-2 sm:col-span-2 xl:col-span-1">
                    <button class="flex-1 bg-primary text-on-primary px-4 py-2.5 rounded-lg text-sm font-bold">Apply</button>
                    <a href="{{ route('admin.quotations') }}" class="shrink-0 px-3 py-2.5 rounded-lg text-sm font-bold text-on-surface-variant hover:text-primary">Reset</a>
                </div>
            </form>
            <p class="mt-3 text-[11px] text-on-surface-variant">
                Showing {{ $allQuotations->count() }} {{ \Illuminate\Support\Str::plural('quotation', $allQuotations->count()) }}
                @if($filtersActive)
                    · Filters applied
                @endif
            </p>
        </section>

        <section class="bg-white border border-outline-variant/30 rounded-2xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-surface-container-low border-b border-outline-variant/30">
                        <tr>
                            <th class="px-6 py-4 font-label-caps text-label-caps text-on-surface-variant">Quotation No.</th>
                            <th class="px-6 py-4 font-label-caps text-label-caps text-on-surface-variant">Client Name</th>
                            <th class="px-6 py-4 font-label-caps text-label-caps text-on-surface-variant">Amount</th>
                            <th class="px-6 py-4 font-label-caps text-label-caps text-on-surface-variant">Items</th>
                            <th class="px-6 py-4 font-label-caps text-label-caps text-on-surface-variant">Status</th>
                            <th class="px-6 py-4 font-label-caps text-label-caps text-on-surface-variant">Date Issued</th>
                            <th class="px-6 py-4 font-label-caps text-label-caps text-on-surface-variant">Emailed</th>
                            <th class="px-6 py-4 font-label-caps text-label-caps text-on-surface-variant text-right sticky right-0 z-20 bg-surface-container-low min-w-[220px] shadow-[-6px_0_8px_-6px_rgba(0,0,0,0.12)]">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/20">
                        @forelse($allQuotations as $quote)
                        @php
                            $quotePayload = [
                                'id' => $quote->id,
                                'company_id' => $quote->company_id,
                                'quotation_number' => $quote->quotation_number,
                                'amount' => $quote->amount,
                                'status' => $quote->status,
                                'date' => optional($quote->date)->format('Y-m-d\TH:i'),
                                'sales_person_name' => $quote->sales_person_name,
                                'sales_person_mobile' => $quote->sales_person_mobile,
                                'payment_terms' => $quote->payment_terms,
                                'terms_conditions' => $quote->terms_conditions,
                                'product_make' => $quote->product_make,
                                'party_gstin' => $quote->party_gstin,
                                'party_mobile' => $quote->party_mobile,
                                'party_address' => $quote->party_address,
                                'notes' => $quote->notes,
                                'company_name' => $quote->company->name ?? '',
                                'company_email' => $quote->company->email ?? '',
                                'items' => $quote->items->map(fn ($i) => [
                                    'product_id' => $i->product_id,
                                    'description' => $i->description,
                                    'hsn_code' => $i->hsn_code,
                                    'qty' => $i->qty,
                                    'unit' => $i->unit,
                                    'list_price' => $i->list_price,
                                    'discount_percent' => $i->discount_percent,
                                    'price' => $i->price,
                                    'amount' => $i->amount,
                                ])->values(),
                            ];
                        @endphp
                        <tr class="hover:bg-primary/5 transition-all city-filterable" data-city="{{ $quote->company->city ?? '' }}">
                            <td class="px-6 py-4 font-data-tabular font-bold text-primary">{{ $quote->quotation_number }}</td>
                            <td class="px-6 py-4 text-sm text-primary font-medium">
                                {{ $quote->company->name ?? '—' }}
                                @if($quote->company->email ?? null)
                                    <p class="text-[11px] text-on-surface-variant font-normal">{{ $quote->company->email }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm font-bold font-data-tabular">₹ {{ \App\Support\IndianNumber::format($quote->amount, 2) }}</td>
                            <td class="px-6 py-4 text-xs text-on-surface-variant">{{ $quote->items->count() }} item(s)</td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded text-[10px] font-bold uppercase tracking-wider {{ $quote->status == 'Approved' ? 'bg-green-100 text-green-800' : ($quote->status == 'Sent' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800') }}">
                                    {{ $quote->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs font-data-tabular">{{ optional($quote->date)->format('d M Y') }}</td>
                            <td class="px-6 py-4 text-xs text-on-surface-variant">
                                @if($quote->emailed_at)
                                    <span class="text-green-700 font-semibold">{{ $quote->emailed_at->format('d M Y H:i') }}</span>
                                    @if($quote->emailed_to)
                                        <p class="text-[10px]">to {{ $quote->emailed_to }}</p>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right sticky right-0 z-10 bg-white group-hover:bg-primary/5 min-w-[220px] shadow-[-6px_0_8px_-6px_rgba(0,0,0,0.12)]">
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="{{ route('admin.quotations.pdf', $quote->id) }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-primary text-on-primary hover:bg-secondary">
                                        <span class="material-symbols-outlined text-[16px]">picture_as_pdf</span> PDF
                                    </a>
                                    <button type="button" class="js-edit-quote inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-surface-container-high text-primary hover:bg-surface-container-highest" data-quote="{{ json_encode($quotePayload) }}">
                                        <span class="material-symbols-outlined text-[16px]">edit</span> Edit
                                    </button>
                                    <button type="button" class="js-send-quote inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-secondary text-white hover:opacity-90" data-quote="{{ json_encode($quotePayload) }}">
                                        <span class="material-symbols-outlined text-[16px]">send</span> Email
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-on-surface-variant/60 text-xs">
                                @if($filtersActive)
                                    No quotations match these filters.
                                    <a href="{{ route('admin.quotations') }}" class="text-primary font-semibold underline ml-1">Clear filters</a>
                                @else
                                    No quotation documents generated.
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
