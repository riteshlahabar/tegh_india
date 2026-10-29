    <!-- ================= VIEW: CUSTOMERS ================= -->
    <div id="view-customers" class="space-y-xl">
        @php
            $stageBadgeClass = function (?string $stage): string {
                return match ($stage) {
                    'New' => 'bg-blue-100 text-blue-800',
                    'Follow-up' => 'bg-amber-100 text-amber-800',
                    'Quotation Sent' => 'bg-indigo-100 text-indigo-800',
                    'Negotiation' => 'bg-teal-100 text-teal-800',
                    'Won' => 'bg-green-100 text-green-800',
                    'Lost' => 'bg-red-100 text-red-800',
                    default => 'bg-surface-container-high text-on-surface-variant',
                };
            };
            $numberFilter = in_array($number ?? '', ['with', 'without'], true) ? $number : '';
            $customersQuery = function (array $overrides = []) use ($search, $city, $perPage, $numberFilter) {
                $params = [
                    'q' => array_key_exists('q', $overrides) ? $overrides['q'] : (($search ?? '') !== '' ? $search : null),
                    'city' => array_key_exists('city', $overrides) ? $overrides['city'] : ($city ?? null),
                    'per_page' => array_key_exists('per_page', $overrides)
                        ? $overrides['per_page']
                        : ((($perPage ?? 50) == 50) ? null : $perPage),
                    'number' => array_key_exists('number', $overrides)
                        ? $overrides['number']
                        : ($numberFilter !== '' ? $numberFilter : null),
                ];

                return array_filter($params, fn ($v) => $v !== null && $v !== '');
            };
        @endphp

        <section class="flex flex-col sm:flex-row sm:justify-between sm:items-end gap-4">
            <div class="min-w-0">
                <h2 class="font-headline-md text-xl sm:text-headline-md text-primary font-bold">Customer Directory</h2>
                <p class="font-body-sm text-on-surface-variant">Click a company name for full details, quotations sent, edit and delete.</p>
            </div>
            <div class="flex flex-col xs:flex-row flex-wrap gap-2 w-full sm:w-auto">
                <button type="button" onclick="toggleModal('modal-import-customers')" class="bg-surface-container-high text-primary border border-outline-variant px-5 py-3 rounded-lg font-title-sm flex items-center justify-center gap-2 hover:bg-surface-container-highest transition-all w-full sm:w-auto">
                    <span class="material-symbols-outlined">upload_file</span>
                    Upload Sheet
                </button>
                <a href="{{ route('admin.companies.import.demo') }}" class="bg-white text-primary border border-outline-variant px-5 py-3 rounded-lg font-title-sm flex items-center justify-center gap-2 hover:bg-surface-container-low transition-all w-full sm:w-auto">
                    <span class="material-symbols-outlined">download</span>
                    Demo sheet
                </a>
                <button onclick="toggleModal('modal-add-customer')" class="bg-primary text-on-primary hover:bg-secondary-container hover:text-on-secondary-container px-5 sm:px-6 py-3 rounded-lg font-title-sm flex items-center justify-center gap-2 shadow-md w-full sm:w-auto">
                    <span class="material-symbols-outlined">person_add</span> Register Customer
                </button>
            </div>
        </section>

        <section class="bg-white border border-outline-variant/30 rounded-2xl shadow-sm">
            <div class="px-5 py-3.5 border-b border-outline-variant/30 bg-surface-container-lowest flex flex-col lg:flex-row lg:items-center gap-3">
                <div class="flex items-center justify-between gap-3 shrink-0">
                    <h3 class="font-label-caps text-label-caps text-primary font-bold">Accounts</h3>
                    <span id="customer-visible-count" class="text-xs font-semibold text-on-surface-variant bg-surface-container-low px-3 py-1.5 rounded-full whitespace-nowrap">
                        {{ $companies->total() }} {{ \Illuminate\Support\Str::plural('account', $companies->total()) }}
                    </span>
                </div>
                <form method="GET" action="{{ route('admin.customers') }}" class="flex flex-1 min-w-0 gap-2">
                    @if(($perPage ?? 50) !== 50)
                        <input type="hidden" name="per_page" value="{{ $perPage }}">
                    @endif
                    @if(filled($city ?? null))
                        <input type="hidden" name="city" value="{{ $city }}">
                    @endif
                    @if($numberFilter !== '')
                        <input type="hidden" name="number" value="{{ $numberFilter }}">
                    @endif
                    <div class="relative flex-1 min-w-0">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg pointer-events-none">search</span>
                        <input
                            id="customer-filter-search"
                            type="search"
                            name="q"
                            value="{{ $search ?? '' }}"
                            placeholder="Search company, contact, mobile, city, product, GST…"
                            class="w-full pl-10 pr-4 py-2.5 bg-white border border-outline-variant/50 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none"
                            autocomplete="off"
                        >
                    </div>
                    <button type="submit" class="shrink-0 px-4 py-2.5 rounded-lg text-sm font-bold bg-primary text-on-primary hover:bg-secondary transition-all">
                        Search
                    </button>
                    @if(($search ?? '') !== '')
                        <a href="{{ route('admin.customers', $customersQuery(['q' => null])) }}" class="shrink-0 px-4 py-2.5 rounded-lg text-sm font-bold border border-outline-variant/50 text-on-surface-variant hover:bg-surface-container-low transition-all">
                            Clear
                        </a>
                    @endif
                </form>
            </div>
            <div class="px-5 py-3 border-b border-outline-variant/30 flex flex-wrap items-center gap-2">
                <span class="text-[10px] font-bold uppercase tracking-wide text-on-surface-variant mr-1">Phone</span>
                @foreach(['' => 'All', 'with' => 'With number', 'without' => 'Without number'] as $value => $label)
                    <a href="{{ route('admin.customers', $customersQuery(['number' => $value !== '' ? $value : null])) }}"
                       class="px-3 py-1.5 rounded-full text-xs font-bold transition-all {{ $numberFilter === $value ? 'bg-primary text-on-primary shadow-sm' : 'bg-surface-container-low text-on-surface-variant hover:text-primary' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
            <div class="customers-table-wrap">
                <table class="customers-table w-full text-left border-collapse">
                    <colgroup>
                        <col class="w-[22%]">
                        <col class="w-[16%]">
                        <col class="w-[12%]">
                        <col class="w-[22%]">
                        <col class="w-[12%]">
                        <col class="w-[12%]">
                        <col class="w-[10%]">
                        <col style="width: 118px">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Company</th>
                            <th>Contact</th>
                            <th>Location</th>
                            <th>Products</th>
                            <th>Outstanding</th>
                            <th>Staff</th>
                            <th>Stage</th>
                            <th class="text-right sticky-action">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($companies as $c)
                        @php
                            $productLabel = $c->showsEnquiryProduct() ? $c->enquiryProductsLabel() : '';
                            $discountChips = array_values(array_filter([
                                ($c->house_wire_discount || $c->house_wire_discount_note) ? 'House' : null,
                                ($c->flexible_wire_discount || $c->flexible_wire_discount_note) ? 'Flex' : null,
                                ($c->other_wire_discount || $c->other_wire_discount_note) ? 'Other' : null,
                            ]));
                            $searchHaystack = strtolower(implode(' ', array_filter([
                                $c->name,
                                $c->contact_person_name,
                                $c->mobile_number,
                                $c->whatsapp_number,
                                $c->email,
                                $c->city,
                                $c->sub_city,
                                $c->industry,
                                $c->gst_number,
                                $c->address,
                                $productLabel,
                                $c->executive->name ?? null,
                                $c->telecaller->name ?? null,
                                $c->stage,
                            ])));
                        @endphp
                        <tr class="customer-row city-filterable group" data-city="{{ $c->city }}" data-search="{{ $searchHaystack }}">
                            <td>
                                <a href="{{ route('admin.customers.show', $c->id) }}" class="customer-name-link">{{ $c->name }}</a>
                                @if($c->industry)
                                    <p class="cell-meta">{{ $c->industry }}</p>
                                @endif
                                @if($c->address)
                                    <p class="cell-clip" title="{{ $c->address }}">{{ $c->address }}</p>
                                @endif
                            </td>
                            <td>
                                <p class="cell-strong">{{ $c->contact_person_name ?: '' }}</p>
                                @if($c->mobile_number)
                                    <p class="cell-meta">{{ $c->mobile_number }}</p>
                                @elseif($c->whatsapp_number)
                                    <p class="cell-meta">{{ $c->whatsapp_number }}</p>
                                @endif
                                @if($c->email)
                                    <p class="cell-clip" title="{{ $c->email }}">{{ $c->email }}</p>
                                @endif
                                @if(!$c->contact_person_name && !$c->mobile_number && !$c->whatsapp_number && !$c->email)
                                    <span class="cell-empty">—</span>
                                @endif
                            </td>
                            <td>
                                <p class="cell-strong whitespace-nowrap">{{ $c->city ?: '—' }}</p>
                                @if($c->sub_city)
                                    <p class="cell-meta">{{ $c->sub_city }}</p>
                                @endif
                            </td>
                            <td>
                                @if($productLabel)
                                    <p class="cell-clip" title="{{ $productLabel }}">{{ $productLabel }}</p>
                                @else
                                    <span class="cell-empty">—</span>
                                @endif
                                @if($discountChips)
                                    <div class="mt-1.5 flex flex-wrap gap-1">
                                        @foreach($discountChips as $chip)
                                            <span class="discount-chip">{{ $chip }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td>
                                <p class="font-data-tabular font-bold text-secondary whitespace-nowrap">₹ {{ \App\Support\IndianNumber::format($c->outstanding_amount, 0) }}</p>
                                <p class="cell-meta whitespace-nowrap">Limit ₹ {{ \App\Support\IndianNumber::format($c->credit_limit, 0) }}</p>
                            </td>
                            <td>
                                <p class="cell-strong">{{ $c->executive->name ?? 'Unassigned' }}</p>
                                <p class="cell-meta">{{ $c->telecaller?->name ? 'TC: '.$c->telecaller->name : 'TC: —' }}</p>
                            </td>
                            <td>
                                <span class="stage-badge {{ $stageBadgeClass($c->stage) }}">{{ $c->stage }}</span>
                            </td>
                            <td class="sticky-action text-right">
                                <button
                                    type="button"
                                    onclick="openAssignModal({{ $c->id }}, @js($c->name), {{ $c->assigned_exec_id ?? 'null' }}, {{ $c->assigned_tc_id ?? 'null' }})"
                                    class="assign-btn"
                                >
                                    Assign
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="empty-row">
                                @if(($search ?? '') !== '' || $numberFilter !== '')
                                    @if(($search ?? '') !== '')
                                        No customers match “{{ $search }}”.
                                    @elseif($numberFilter === 'with')
                                        No customers with a phone number.
                                    @else
                                        No customers without a phone number.
                                    @endif
                                    <a href="{{ route('admin.customers', $customersQuery(['q' => null, 'number' => null])) }}" class="text-primary font-semibold underline ml-1">Clear filters</a>
                                @else
                                    No registered customer accounts found.
                                @endif
                            </td>
                        </tr>
                        @endforelse
                        @if($companies->isNotEmpty())
                        <tr id="customer-filter-empty" class="hidden">
                            <td colspan="8" class="empty-row">No customers match this search.</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            @if($companies->total() > 0)
            <div class="p-5 border-t border-outline-variant/30 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 bg-surface-container-lowest rounded-b-2xl">
                <p class="text-xs text-on-surface-variant">
                    Showing {{ $companies->firstItem() }}–{{ $companies->lastItem() }} of {{ $companies->total() }}
                    · Page {{ $companies->currentPage() }} of {{ $companies->lastPage() }}
                </p>
                <div class="flex flex-wrap items-center gap-3">
                    <form method="GET" action="{{ route('admin.customers') }}" class="flex items-center gap-2">
                        @if(($search ?? '') !== '')
                            <input type="hidden" name="q" value="{{ $search }}">
                        @endif
                        @if(filled($city ?? null))
                            <input type="hidden" name="city" value="{{ $city }}">
                        @endif
                        @if($numberFilter !== '')
                            <input type="hidden" name="number" value="{{ $numberFilter }}">
                        @endif
                        <label for="customers-per-page" class="text-xs font-bold text-on-surface-variant whitespace-nowrap">Rows</label>
                        <select
                            id="customers-per-page"
                            name="per_page"
                            onchange="this.form.submit()"
                            class="border border-outline-variant/50 rounded-lg text-xs py-1.5 px-2 bg-white focus:ring-2 focus:ring-primary focus:border-primary"
                        >
                            @foreach(($perPageOptions ?? [25, 50, 100, 200]) as $option)
                                <option value="{{ $option }}" @selected(($perPage ?? 50) == $option)>{{ $option }} / page</option>
                            @endforeach
                            <option value="all" @selected(($perPage ?? 50) === 'all')>All</option>
                        </select>
                    </form>
                    @if($companies->hasPages())
                    <div class="flex flex-wrap items-center gap-1">
                        @if($companies->onFirstPage())
                            <span class="px-3 py-1.5 rounded-lg text-xs font-semibold text-on-surface-variant/40 border border-outline-variant/20">Prev</span>
                        @else
                            <a href="{{ $companies->previousPageUrl() }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-primary border border-outline-variant/40 hover:bg-white transition-all">Prev</a>
                        @endif

                        @foreach($companies->getUrlRange(max(1, $companies->currentPage() - 2), min($companies->lastPage(), $companies->currentPage() + 2)) as $page => $url)
                            @if($page == $companies->currentPage())
                                <span class="px-3 py-1.5 rounded-lg text-xs font-bold bg-primary text-on-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-primary border border-outline-variant/40 hover:bg-white transition-all">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if($companies->hasMorePages())
                            <a href="{{ $companies->nextPageUrl() }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-primary border border-outline-variant/40 hover:bg-white transition-all">Next</a>
                        @else
                            <span class="px-3 py-1.5 rounded-lg text-xs font-semibold text-on-surface-variant/40 border border-outline-variant/20">Next</span>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </section>
    </div>
