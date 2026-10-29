    @php
        $c = $company;
        $money = fn ($n) => '₹ '.number_format((float) $n, 2);
        $cityNames = collect($cities ?? [])->pluck('name');
        $stageClass = match ($c->stage) {
            'New' => 'bg-blue-100 text-blue-800',
            'Follow-up' => 'bg-amber-100 text-amber-800',
            'Quotation Sent' => 'bg-indigo-100 text-indigo-800',
            'Negotiation' => 'bg-teal-100 text-teal-800',
            'Won' => 'bg-green-100 text-green-800',
            'Lost' => 'bg-red-100 text-red-800',
            default => 'bg-primary-fixed text-on-primary-fixed-variant',
        };
        $originType = $c->leadOriginType();
    @endphp

    <div id="view-customer-detail" class="space-y-xl">
        <section class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <a href="{{ route('admin.customers') }}" class="inline-flex items-center gap-1 text-xs font-bold text-on-surface-variant hover:text-primary mb-2">
                    <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                    Customer Directory
                </a>
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="font-headline-md text-xl sm:text-headline-md text-primary font-bold">{{ $c->name }}</h2>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $stageClass }}">{{ $c->stage }}</span>
                </div>
                <p class="font-body-sm text-on-surface-variant mt-1">
                    {{ trim(implode(' · ', array_filter([$c->industry, $c->city, $c->sub_city, $c->state]))) ?: 'Full account profile' }}
                </p>
            </div>
            <div class="flex flex-col xs:flex-row flex-wrap gap-2 w-full lg:w-auto">
                <button type="button" onclick="toggleModal('modal-edit-customer')" class="bg-primary text-on-primary hover:bg-secondary px-5 py-3 rounded-lg font-title-sm flex items-center justify-center gap-2 shadow-md w-full sm:w-auto">
                    <span class="material-symbols-outlined">edit</span>
                    Edit Customer
                </button>
                <form action="{{ route('admin.companies.destroy', $c->id) }}" method="POST" class="w-full sm:w-auto" onsubmit="return confirm(@js('Delete '.$c->name.'? Quotations, visits and calls for this customer will also be removed.'));">
                    @csrf
                    <button type="submit" class="w-full bg-red-50 text-red-700 border border-red-200 hover:bg-red-100 px-5 py-3 rounded-lg font-title-sm flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined">delete</span>
                        Delete Customer
                    </button>
                </form>
            </div>
        </section>

        <section class="grid grid-cols-2 lg:grid-cols-5 gap-3">
            <div class="bg-white border border-outline-variant/30 rounded-xl p-4 shadow-sm">
                <p class="font-label-caps text-[10px] text-on-surface-variant mb-1">TOTAL QUOTED</p>
                <p class="text-lg sm:text-xl font-bold text-primary font-data-tabular">{{ $money($totals['quoted']) }}</p>
                <p class="text-[11px] text-on-surface-variant mt-0.5">{{ $totals['quoted_count'] }} quotation(s)</p>
            </div>
            <div class="bg-white border border-outline-variant/30 rounded-xl p-4 shadow-sm">
                <p class="font-label-caps text-[10px] text-on-surface-variant mb-1">SENT TO CUSTOMER</p>
                <p class="text-lg sm:text-xl font-bold text-secondary font-data-tabular">{{ $money($totals['sent']) }}</p>
                <p class="text-[11px] text-on-surface-variant mt-0.5">{{ $totals['sent_count'] }} emailed</p>
            </div>
            <div class="bg-white border border-outline-variant/30 rounded-xl p-4 shadow-sm">
                <p class="font-label-caps text-[10px] text-on-surface-variant mb-1">APPROVED / WON</p>
                <p class="text-lg sm:text-xl font-bold text-green-700 font-data-tabular">{{ $money($totals['approved']) }}</p>
                <p class="text-[11px] text-on-surface-variant mt-0.5">{{ $totals['approved_count'] }} approved</p>
            </div>
            <div class="bg-white border border-outline-variant/30 rounded-xl p-4 shadow-sm">
                <p class="font-label-caps text-[10px] text-on-surface-variant mb-1">OUTSTANDING</p>
                <p class="text-lg sm:text-xl font-bold text-secondary font-data-tabular">{{ $money($totals['outstanding']) }}</p>
            </div>
            <div class="bg-white border border-outline-variant/30 rounded-xl p-4 shadow-sm col-span-2 lg:col-span-1">
                <p class="font-label-caps text-[10px] text-on-surface-variant mb-1">CREDIT LIMIT</p>
                <p class="text-lg sm:text-xl font-bold text-primary font-data-tabular">{{ $money($totals['credit_limit']) }}</p>
            </div>
        </section>

        <section class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bg-white border border-outline-variant/30 rounded-2xl p-5 sm:p-6 shadow-sm space-y-4">
                <h3 class="font-title-sm text-primary font-bold">Profile</h3>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div>
                        <dt class="text-[11px] font-bold uppercase tracking-wide text-on-surface-variant">Contact person</dt>
                        <dd class="text-primary font-semibold">{{ $c->contact_person_name ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-bold uppercase tracking-wide text-on-surface-variant">Mobile</dt>
                        <dd class="text-primary">{{ $c->mobile_number ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-bold uppercase tracking-wide text-on-surface-variant">WhatsApp</dt>
                        <dd class="text-primary">{{ $c->whatsapp_number ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-bold uppercase tracking-wide text-on-surface-variant">Alternate mobile</dt>
                        <dd class="text-primary">{{ $c->alternate_mobile ?: '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-[11px] font-bold uppercase tracking-wide text-on-surface-variant">Email</dt>
                        <dd class="text-primary break-all">{{ $c->email ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-bold uppercase tracking-wide text-on-surface-variant">GST / Registration</dt>
                        <dd class="font-data-tabular text-primary">{{ $c->gst_number ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-bold uppercase tracking-wide text-on-surface-variant">Brand</dt>
                        <dd class="text-primary">{{ $c->brand ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-bold uppercase tracking-wide text-on-surface-variant">Payment terms</dt>
                        <dd class="text-primary">{{ $c->payment_terms ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-bold uppercase tracking-wide text-on-surface-variant">PIN</dt>
                        <dd class="text-primary">{{ $c->pin_code ?: '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-[11px] font-bold uppercase tracking-wide text-on-surface-variant">Address</dt>
                        <dd class="text-primary">{{ $c->address ?: 'No address logged' }}</dd>
                    </div>
                    @if($c->latitude && $c->longitude)
                    <div class="sm:col-span-2">
                        <dt class="text-[11px] font-bold uppercase tracking-wide text-on-surface-variant">Map pin</dt>
                        <dd class="text-primary font-data-tabular text-xs">{{ $c->latitude }}, {{ $c->longitude }}</dd>
                    </div>
                    @endif
                </dl>
            </div>

            <div class="bg-white border border-outline-variant/30 rounded-2xl p-5 sm:p-6 shadow-sm space-y-4">
                <h3 class="font-title-sm text-primary font-bold">Assignment &amp; notes</h3>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div>
                        <dt class="text-[11px] font-bold uppercase tracking-wide text-on-surface-variant">Salesperson</dt>
                        <dd class="text-primary font-semibold">{{ $c->executive->name ?? 'Unassigned' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-bold uppercase tracking-wide text-on-surface-variant">Telecaller</dt>
                        <dd class="text-primary font-semibold">{{ $c->telecaller->name ?? 'Unassigned' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-bold uppercase tracking-wide text-on-surface-variant">Added by</dt>
                        <dd class="text-primary">
                            @if($originType === 'admin_assign')
                                Admin Assign · {{ $c->executive->name ?? 'Unassigned' }}
                            @elseif($originType === 'self')
                                Self · {{ $c->creator->name ?? '—' }}
                            @else
                                {{ $c->creator->name ?? '—' }}
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-bold uppercase tracking-wide text-on-surface-variant">Registered</dt>
                        <dd class="text-primary">{{ optional($c->created_at)->format('d M Y') ?: '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-[11px] font-bold uppercase tracking-wide text-on-surface-variant">Enquiry product</dt>
                        <dd class="text-primary">{{ $c->enquiryProductsLabel() ?: '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-[11px] font-bold uppercase tracking-wide text-on-surface-variant">Wire discounts</dt>
                        <dd class="space-y-1.5 mt-1">
                            @forelse([
                                ['House Wire', $c->house_wire_discount, $c->house_wire_discount_note],
                                ['Flexible Wire', $c->flexible_wire_discount, $c->flexible_wire_discount_note],
                                ['Other Wire', $c->other_wire_discount, $c->other_wire_discount_note],
                            ] as [$label, $on, $note])
                                @if($on || $note)
                                    <div>
                                        <span class="inline-block px-2 py-0.5 rounded bg-amber-100 text-amber-800 font-semibold text-xs">{{ $label }}</span>
                                        @if($note)
                                            <p class="text-on-surface-variant text-xs mt-0.5">{{ $note }}</p>
                                        @endif
                                    </div>
                                @endif
                            @empty
                            @endforelse
                            @if(!$c->house_wire_discount && !$c->flexible_wire_discount && !$c->other_wire_discount && !$c->house_wire_discount_note && !$c->flexible_wire_discount_note && !$c->other_wire_discount_note)
                                <span class="text-on-surface-variant">None</span>
                            @endif
                        </dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-[11px] font-bold uppercase tracking-wide text-on-surface-variant">Admin note</dt>
                        <dd class="text-on-surface-variant whitespace-pre-wrap">{{ $c->enquiry_admin_note ?: '—' }}</dd>
                    </div>
                </dl>
            </div>
        </section>

        <section id="customer-quotes" class="scroll-mt-28 bg-white border border-outline-variant/30 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-outline-variant/30 flex items-center justify-between gap-3">
                <h3 class="font-title-sm text-primary font-bold">Quotations sent</h3>
                <a href="{{ route('admin.quotations') }}" class="text-xs font-bold text-secondary hover:underline">Open quotations</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[720px]">
                    <thead class="bg-surface-container-low border-b border-outline-variant/30">
                        <tr>
                            <th class="px-5 py-3 font-label-caps text-label-caps text-on-surface-variant">Quote No.</th>
                            <th class="px-5 py-3 font-label-caps text-label-caps text-on-surface-variant">Amount</th>
                            <th class="px-5 py-3 font-label-caps text-label-caps text-on-surface-variant">Status</th>
                            <th class="px-5 py-3 font-label-caps text-label-caps text-on-surface-variant">Date</th>
                            <th class="px-5 py-3 font-label-caps text-label-caps text-on-surface-variant">Emailed</th>
                            <th class="px-5 py-3 font-label-caps text-label-caps text-on-surface-variant text-right">PDF</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/20">
                        @forelse($c->quotations as $quote)
                        <tr class="hover:bg-primary/5">
                            <td class="px-5 py-3 font-data-tabular font-bold text-primary">{{ $quote->quotation_number }}</td>
                            <td class="px-5 py-3 font-data-tabular font-semibold text-primary">{{ $money($quote->amount) }}</td>
                            <td class="px-5 py-3">
                                <span class="px-2.5 py-1 rounded text-[10px] font-bold uppercase tracking-wider {{ $quote->status == 'Approved' ? 'bg-green-100 text-green-800' : ($quote->status == 'Sent' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800') }}">
                                    {{ $quote->status }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-xs font-data-tabular">{{ optional($quote->date)->format('d M Y') }}</td>
                            <td class="px-5 py-3 text-xs text-on-surface-variant">
                                @if($quote->emailed_at)
                                    <span class="text-green-700 font-semibold">{{ $quote->emailed_at->format('d M Y H:i') }}</span>
                                    @if($quote->emailed_to)
                                        <p class="text-[10px]">to {{ $quote->emailed_to }}</p>
                                    @endif
                                @else
                                    Not emailed
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('admin.quotations.pdf', $quote->id) }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-primary text-on-primary hover:bg-secondary">
                                    PDF
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-on-surface-variant/60 text-xs">No quotations sent to this customer yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div id="customer-visits" class="scroll-mt-28 bg-white border border-outline-variant/30 rounded-2xl shadow-sm overflow-hidden target:ring-2 target:ring-secondary/50">
                <div class="px-5 py-4 border-b border-outline-variant/30">
                    <h3 class="font-title-sm text-primary font-bold">Field visits ({{ $totals['visits_count'] }})</h3>
                </div>
                <div class="divide-y divide-outline-variant/20">
                    @forelse($c->visits as $visit)
                    <a href="{{ route('admin.visits.show', $visit->id) }}" class="block px-5 py-3 hover:bg-primary/5 transition-colors">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-primary">{{ optional($visit->checked_in_at)->format('d M Y H:i') ?: 'Visit #'.$visit->id }}</p>
                                <p class="text-[11px] text-on-surface-variant">{{ $visit->executive->name ?? 'Unassigned' }} · {{ $visit->status }}</p>
                                @if($visit->notes)
                                    <p class="text-xs text-on-surface-variant mt-1">{{ \Illuminate\Support\Str::limit($visit->notes, 120) }}</p>
                                @endif
                            </div>
                            @if($visit->discount_amount)
                                <span class="text-xs font-data-tabular font-bold text-secondary">{{ $money($visit->discount_amount) }}</span>
                            @endif
                        </div>
                    </a>
                    @empty
                    <p class="py-8 text-center text-on-surface-variant/60 text-xs">No field visits logged.</p>
                    @endforelse
                </div>
            </div>

            <div id="customer-calls" class="scroll-mt-28 bg-white border border-outline-variant/30 rounded-2xl shadow-sm overflow-hidden target:ring-2 target:ring-secondary/50">
                <div class="px-5 py-4 border-b border-outline-variant/30">
                    <h3 class="font-title-sm text-primary font-bold">Calls ({{ $totals['calls_count'] }})</h3>
                </div>
                <div class="divide-y divide-outline-variant/20">
                    @forelse($c->calls as $call)
                    <div class="px-5 py-3">
                        <p class="text-sm font-semibold text-primary">{{ $call->outcome }}</p>
                        <p class="text-[11px] text-on-surface-variant">
                            {{ optional($call->called_at)->format('d M Y H:i') }}
                            · {{ $call->telecaller->name ?? 'Admin' }}
                        </p>
                        @if($call->remarks)
                            <p class="text-xs text-on-surface-variant mt-1">{{ $call->remarks }}</p>
                        @endif
                    </div>
                    @empty
                    <p class="py-8 text-center text-on-surface-variant/60 text-xs">No call outcomes logged.</p>
                    @endforelse
                </div>
            </div>
        </section>
    </div>

    <div id="modal-edit-customer" class="fixed inset-0 z-50 hidden" aria-modal="true" role="dialog">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="toggleModal('modal-edit-customer')"></div>
        <div class="relative z-10 h-full w-full flex items-end sm:items-center justify-center p-0 sm:p-4 pointer-events-none">
            <div class="pointer-events-auto bg-white border border-outline-variant/30 w-full max-w-lg max-h-[92dvh] sm:max-h-[90vh] rounded-t-2xl sm:rounded-2xl shadow-2xl flex flex-col overflow-hidden">
                <form action="{{ route('admin.companies.update', $c->id) }}" method="POST" class="flex flex-col min-h-0 max-h-[92dvh] sm:max-h-[90vh]">
                    @csrf
                    <input type="hidden" name="_form" value="edit-customer">
                    <div class="shrink-0 flex items-center justify-between gap-3 px-4 sm:px-6 py-3 sm:py-4 border-b border-outline-variant/30 bg-white">
                        <h3 class="font-headline-md text-primary font-bold text-lg sm:text-xl pr-2">Edit Customer</h3>
                        <button type="button" onclick="toggleModal('modal-edit-customer')" class="shrink-0 w-10 h-10 flex items-center justify-center rounded-full bg-surface-container-low text-on-surface-variant hover:text-primary hover:bg-surface-container-high transition-colors" aria-label="Close">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <div class="flex-1 min-h-0 overflow-y-auto overscroll-contain px-4 sm:px-6 py-4 space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant mb-1">Company Name *</label>
                            <input required name="name" type="text" value="{{ old('name', $c->name) }}" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
                        </div>
                        <div class="rounded-xl border border-outline-variant/40 bg-surface-container-low/60 p-3 sm:p-4 space-y-3">
                            <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wide">Wire Discounts</p>
                            <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
                                <label class="flex items-center gap-2 cursor-pointer shrink-0 sm:w-48">
                                    <input type="checkbox" name="house_wire_discount" value="1" @checked(old('house_wire_discount', $c->house_wire_discount)) class="rounded border-outline-variant text-secondary focus:ring-secondary">
                                    <span class="text-sm text-primary font-medium">House Wire Discount</span>
                                </label>
                                <input type="text" name="house_wire_discount_note" value="{{ old('house_wire_discount_note', $c->house_wire_discount_note) }}" class="w-full flex-1 border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary" placeholder="Note for house wire discount...">
                            </div>
                            <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
                                <label class="flex items-center gap-2 cursor-pointer shrink-0 sm:w-48">
                                    <input type="checkbox" name="flexible_wire_discount" value="1" @checked(old('flexible_wire_discount', $c->flexible_wire_discount)) class="rounded border-outline-variant text-secondary focus:ring-secondary">
                                    <span class="text-sm text-primary font-medium">Flexible Wire Discount</span>
                                </label>
                                <input type="text" name="flexible_wire_discount_note" value="{{ old('flexible_wire_discount_note', $c->flexible_wire_discount_note) }}" class="w-full flex-1 border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary" placeholder="Note for flexible wire discount...">
                            </div>
                            <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
                                <label class="flex items-center gap-2 cursor-pointer shrink-0 sm:w-48">
                                    <input type="checkbox" name="other_wire_discount" value="1" @checked(old('other_wire_discount', $c->other_wire_discount)) class="rounded border-outline-variant text-secondary focus:ring-secondary">
                                    <span class="text-sm text-primary font-medium">Other Wire Discount</span>
                                </label>
                                <input type="text" name="other_wire_discount_note" value="{{ old('other_wire_discount_note', $c->other_wire_discount_note) }}" class="w-full flex-1 border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary" placeholder="Note for other wire discount...">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant mb-1">Contact Person Name</label>
                            <input name="contact_person_name" type="text" value="{{ old('contact_person_name', $c->contact_person_name) }}" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant mb-1">Mobile Number</label>
                                <input name="mobile_number" type="tel" value="{{ old('mobile_number', $c->mobile_number) }}" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant mb-1">WhatsApp Number</label>
                                <input name="whatsapp_number" type="tel" value="{{ old('whatsapp_number', $c->whatsapp_number) }}" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant mb-1">Alternate Mobile</label>
                            <input name="alternate_mobile" type="tel" value="{{ old('alternate_mobile', $c->alternate_mobile) }}" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant mb-1">Email ID</label>
                            <input name="email" type="email" value="{{ old('email', $c->email) }}" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant mb-1">Industry Sector</label>
                                <input name="industry" type="text" value="{{ old('industry', $c->industry) }}" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant mb-1">Brand</label>
                                <input name="brand" type="text" value="{{ old('brand', $c->brand) }}" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant mb-1">GST Number</label>
                            <input name="gst_number" type="text" value="{{ old('gst_number', $c->gst_number) }}" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant mb-1">Office / Shipping Address</label>
                            <textarea name="address" rows="2" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">{{ old('address', $c->address) }}</textarea>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant mb-1">Latitude (optional)</label>
                                <input name="latitude" type="number" step="any" value="{{ old('latitude', $c->latitude) }}" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant mb-1">Longitude (optional)</label>
                                <input name="longitude" type="number" step="any" value="{{ old('longitude', $c->longitude) }}" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant mb-1">City / Branch *</label>
                                <select required name="city" id="edit-city" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
                                    <option value="">Select city</option>
                                    @foreach($cities as $city)
                                        <option value="{{ $city->name }}" @selected(old('city', $c->city) === $city->name)>{{ $city->name }}</option>
                                    @endforeach
                                    @if($c->city && !$cityNames->contains($c->city))
                                        <option value="{{ $c->city }}" @selected(old('city', $c->city) === $c->city)>{{ $c->city }}</option>
                                    @endif
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant mb-1">Sub City</label>
                                <select name="sub_city" id="edit-sub-city" data-selected="{{ old('sub_city', $c->sub_city) }}" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
                                    <option value="">Select sub city</option>
                                    @if($c->sub_city)
                                        <option value="{{ $c->sub_city }}" selected>{{ $c->sub_city }}</option>
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant mb-1">State</label>
                                <input name="state" type="text" value="{{ old('state', $c->state) }}" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant mb-1">PIN Code</label>
                                <input name="pin_code" type="text" value="{{ old('pin_code', $c->pin_code) }}" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant mb-1">Credit Limit (₹) *</label>
                                <input required name="credit_limit" type="number" min="0" step="0.01" value="{{ old('credit_limit', $c->credit_limit) }}" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant mb-1">Outstanding (₹)</label>
                                <input name="outstanding_amount" type="number" min="0" step="0.01" value="{{ old('outstanding_amount', $c->outstanding_amount) }}" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant mb-1">Payment Terms</label>
                            <input name="payment_terms" type="text" value="{{ old('payment_terms', $c->payment_terms) }}" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant mb-1">Assign Salesperson</label>
                                <select name="assigned_exec_id" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
                                    <option value="">Unassigned</option>
                                    @foreach($salesExecutives as $exec)
                                        <option value="{{ $exec->id }}" @selected((string) old('assigned_exec_id', $c->assigned_exec_id) === (string) $exec->id)>{{ $exec->name }} ({{ $exec->branch }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant mb-1">Assign Telecaller</label>
                                <select name="assigned_tc_id" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
                                    <option value="">Unassigned</option>
                                    @foreach($telecallers as $tc)
                                        <option value="{{ $tc->id }}" @selected((string) old('assigned_tc_id', $c->assigned_tc_id) === (string) $tc->id)>{{ $tc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant mb-1">Pipeline Stage *</label>
                            <select required name="stage" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
                                @foreach(['New','Follow-up','Quotation Sent','Negotiation','Won','Lost'] as $stage)
                                    <option value="{{ $stage }}" @selected(old('stage', $c->stage) === $stage)>{{ $stage }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant mb-1">Admin Note</label>
                            <textarea name="enquiry_admin_note" rows="3" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">{{ old('enquiry_admin_note', $c->enquiry_admin_note) }}</textarea>
                        </div>
                    </div>
                    <div class="shrink-0 border-t border-outline-variant/20 bg-white px-4 sm:px-6 py-3 pb-[max(0.75rem,env(safe-area-inset-bottom))]">
                        <button type="submit" class="w-full bg-primary text-on-primary py-3 rounded-lg font-bold shadow-md hover:bg-secondary transition-all">SAVE CHANGES</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
