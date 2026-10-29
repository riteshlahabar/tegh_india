    <!-- ================= VIEW: REPORTS & ANALYTICS ================= -->
    <div id="view-reports" class="space-y-xl">
        <section class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
            <div>
                <h2 class="font-headline-md text-headline-md text-primary font-bold">Reports &amp; Analytics</h2>
                <p class="font-body-sm text-body-sm text-on-surface-variant font-medium">Salesman, telecaller, and customer-wise detail for admin review.</p>
            </div>
            <div class="inline-flex flex-wrap p-1 rounded-xl bg-surface-container-low border border-outline-variant/40" role="tablist" aria-label="Report views">
                <button type="button" class="js-report-tab px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wide transition-all bg-primary text-on-primary" data-report-tab="overview">Overview</button>
                <button type="button" class="js-report-tab px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wide transition-all text-on-surface-variant hover:text-primary" data-report-tab="salesman">Salesman</button>
                <button type="button" class="js-report-tab px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wide transition-all text-on-surface-variant hover:text-primary" data-report-tab="telecaller">Telecaller</button>
                <button type="button" class="js-report-tab px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wide transition-all text-on-surface-variant hover:text-primary" data-report-tab="customer">Customer</button>
            </div>
        </section>

        <section class="bg-white border border-outline-variant/30 rounded-2xl p-4 shadow-sm">
            <form method="GET" action="{{ route('admin.reports') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                <input type="hidden" name="tab" id="report-active-tab" value="{{ $filters['tab'] ?? 'overview' }}">
                <input type="hidden" name="customer_q" value="{{ $filters['customer_q'] ?? '' }}">
                <input type="hidden" name="customer_stage" value="{{ $filters['customer_stage'] ?? '' }}">
                @if(($filters['activity'] ?? '') !== '')
                    <input type="hidden" name="activity" value="{{ $filters['activity'] }}">
                @endif
                @if(($filters['per_page'] ?? 25) != 25)
                    <input type="hidden" name="per_page" value="{{ $filters['per_page'] }}">
                @endif
                <div>
                    <label class="block text-[11px] font-bold text-on-surface-variant mb-1">Telecaller</label>
                    <select name="telecaller_id" class="w-full py-2.5 px-3 bg-surface-container-low border-none rounded-lg text-sm focus:ring-2 focus:ring-secondary">
                        <option value="">All</option>
                        @foreach($telecallers as $tc)
                            <option value="{{ $tc->id }}" @selected((int)($filters['telecaller_id'] ?? 0) === (int)$tc->id)>{{ $tc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-on-surface-variant mb-1">Sales person</label>
                    <select name="salesman_id" class="w-full py-2.5 px-3 bg-surface-container-low border-none rounded-lg text-sm focus:ring-2 focus:ring-secondary">
                        <option value="">All</option>
                        @foreach($salesmen as $sp)
                            <option value="{{ $sp->id }}" @selected((int)($filters['salesman_id'] ?? 0) === (int)$sp->id)>{{ $sp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-on-surface-variant mb-1">From</label>
                    <input type="date" name="from" value="{{ $filters['from']?->toDateString() }}" class="w-full py-2.5 px-3 bg-surface-container-low border-none rounded-lg text-sm focus:ring-2 focus:ring-secondary">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-on-surface-variant mb-1">To</label>
                    <input type="date" name="to" value="{{ $filters['to']?->toDateString() }}" class="w-full py-2.5 px-3 bg-surface-container-low border-none rounded-lg text-sm focus:ring-2 focus:ring-secondary">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-on-surface-variant mb-1">Stage</label>
                    <select name="stage_group" class="w-full py-2.5 px-3 bg-surface-container-low border-none rounded-lg text-sm focus:ring-2 focus:ring-secondary">
                        <option value="">All</option>
                        <option value="won" @selected(($filters['stage_group'] ?? '') === 'won')>Win</option>
                        <option value="lost" @selected(($filters['stage_group'] ?? '') === 'lost')>Lost</option>
                        <option value="other" @selected(($filters['stage_group'] ?? '') === 'other')>Other</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button class="w-full bg-primary text-on-primary px-4 py-2.5 rounded-lg text-sm font-bold">Apply</button>
                    <a href="{{ route('admin.reports') }}" class="shrink-0 px-3 py-2.5 rounded-lg text-sm font-bold text-on-surface-variant hover:text-primary">Reset</a>
                </div>
            </form>
            <p class="mt-2 text-[11px] text-on-surface-variant">Tip: Select Telecaller first, then date to generate period-wise report.</p>
        </section>

        @php
            $quoteParamsBase = array_filter([
                'from' => $filters['from']?->toDateString(),
                'to' => $filters['to']?->toDateString(),
                'salesman_id' => $filters['salesman_id'] ?? null,
                'telecaller_id' => $filters['telecaller_id'] ?? null,
                'stage_group' => $filters['stage_group'] ?? null,
            ], fn ($v) => $v !== null && $v !== '');
            $customerReportTotals = $customerReportTotals ?? [
                'count' => method_exists($customerReport, 'total') ? $customerReport->total() : $customerReport->count(),
                'visits' => $customerReport->sum('visits'),
                'quote_value' => $customerReport->sum('quote_value'),
                'won_value' => $customerReport->sum('won_value'),
            ];
            $customerTabParams = array_filter([
                'from' => $filters['from']?->toDateString(),
                'to' => $filters['to']?->toDateString(),
                'stage_group' => $filters['stage_group'] ?? null,
                'customer_q' => $filters['customer_q'] ?? null,
                'customer_stage' => $filters['customer_stage'] ?? null,
                'activity' => $filters['activity'] ?? null,
                'salesman_id' => $filters['salesman_id'] ?? null,
                'telecaller_id' => $filters['telecaller_id'] ?? null,
                'per_page' => (($filters['per_page'] ?? 25) == 25) ? null : ($filters['per_page'] ?? null),
            ], fn ($v) => $v !== null && $v !== '');
        @endphp

        {{-- ================= OVERVIEW ================= --}}
        <div class="js-report-panel space-y-xl" data-report-panel="overview">
            <section class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-7 gap-3">
                <div class="bg-white border border-outline-variant/30 rounded-xl p-4 shadow-sm">
                    <p class="font-label-caps text-[10px] text-on-surface-variant mb-1">TOTAL LEADS</p>
                    <p class="text-2xl font-bold text-primary font-data-tabular">{{ $reportKpis['total_leads'] }}</p>
                </div>
                <div class="bg-white border border-outline-variant/30 rounded-xl p-4 shadow-sm">
                    <p class="font-label-caps text-[10px] text-on-surface-variant mb-1">ACTIVE PIPELINE</p>
                    <p class="text-2xl font-bold text-primary font-data-tabular">{{ $reportKpis['active_pipeline'] }}</p>
                </div>
                <div class="bg-white border border-outline-variant/30 rounded-xl p-4 shadow-sm">
                    <p class="font-label-caps text-[10px] text-on-surface-variant mb-1">VISITS DONE</p>
                    <p class="text-2xl font-bold text-primary font-data-tabular">{{ $reportKpis['visits_completed'] }}</p>
                </div>
                <div class="bg-white border border-outline-variant/30 rounded-xl p-4 shadow-sm">
                    <p class="font-label-caps text-[10px] text-on-surface-variant mb-1">SALESMEN</p>
                    <p class="text-2xl font-bold text-primary font-data-tabular">{{ $reportKpis['salesmen_count'] ?? 0 }}</p>
                </div>
                <a href="{{ route('admin.quotations', $quoteParamsBase) }}" class="block bg-white border border-outline-variant/30 rounded-xl p-4 shadow-sm hover:bg-primary/5 transition-colors">
                    <p class="font-label-caps text-[10px] text-on-surface-variant mb-1">TOTAL QUOTATIONS</p>
                    <p class="text-2xl font-bold text-primary font-data-tabular">{{ $reportKpis['total_quotations'] }}</p>
                    <p class="text-[10px] text-on-surface-variant mt-1">Click to view details</p>
                </a>
                <div class="bg-white border border-outline-variant/30 rounded-xl p-4 shadow-sm">
                    <p class="font-label-caps text-[10px] text-on-surface-variant mb-1">QUOTE VALUE</p>
                    <p class="text-2xl font-bold text-primary font-data-tabular">₹{{ \App\Support\IndianNumber::format($reportKpis['quote_value'], 0) }}</p>
                </div>
                <div class="bg-white border border-outline-variant/30 rounded-xl p-4 shadow-sm">
                    <p class="font-label-caps text-[10px] text-on-surface-variant mb-1">WON VALUE</p>
                    <p class="text-2xl font-bold text-secondary font-data-tabular">₹{{ \App\Support\IndianNumber::format($reportKpis['won_value'], 0) }}</p>
                </div>
            </section>

            <section class="grid grid-cols-12 gap-4">
                <div class="col-span-12 lg:col-span-8 bg-white border border-outline-variant/30 rounded-2xl p-5 shadow-sm">
                    <div class="flex justify-between items-center mb-5 flex-wrap gap-3">
                        <h3 class="font-label-caps text-label-caps text-primary font-bold">Monthly Quotation Trends</h3>
                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-primary"></span>
                                <span class="text-[11px] text-on-surface-variant">Quoted</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-secondary"></span>
                                <span class="text-[11px] text-on-surface-variant">Won</span>
                            </div>
                        </div>
                    </div>
                    <div class="h-56 flex items-end justify-between gap-3 px-1 relative border-b border-outline-variant/40">
                        @foreach($reportMonths as $month)
                            @php
                                $sentH = (int) max(8, round(($month['sent'] / $maxMonthAmount) * 200));
                                $wonH = (int) max(8, round(($month['won'] / $maxMonthAmount) * 200));
                                if ($month['sent'] <= 0) $sentH = 4;
                                if ($month['won'] <= 0) $wonH = 4;
                            @endphp
                            <div class="flex-1 flex flex-col items-center gap-2 min-w-0">
                                <div class="w-full flex items-end gap-1 h-48">
                                    <div class="flex-1 bg-primary/30 hover:bg-primary transition-all chart-bar rounded-t-sm" data-height="{{ $sentH }}px" style="height: {{ $sentH }}px;" title="Quoted: ₹{{ \App\Support\IndianNumber::format($month['sent'], 0) }}"></div>
                                    <div class="flex-1 bg-secondary/30 hover:bg-secondary transition-all chart-bar rounded-t-sm" data-height="{{ $wonH }}px" style="height: {{ $wonH }}px;" title="Won: ₹{{ \App\Support\IndianNumber::format($month['won'], 0) }}"></div>
                                </div>
                                <span class="font-label-caps text-[10px] text-on-surface-variant">{{ $month['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="col-span-12 lg:col-span-4 bg-white border border-outline-variant/30 rounded-2xl p-5 shadow-sm">
                    <h3 class="font-label-caps text-label-caps text-primary font-bold mb-4">Pipeline Conversion</h3>
                    <div class="relative flex flex-col items-center justify-center h-48">
                        @php
                            $circ = 2 * 3.14159265 * 40;
                            $offset = $circ * (1 - min(100, max(0, $reportKpis['conversion'])) / 100);
                        @endphp
                        <svg class="w-40 h-40 -rotate-90" viewBox="0 0 100 100">
                            <circle cx="50" cy="50" fill="none" r="40" stroke="#e2e8f0" stroke-width="8"></circle>
                            <circle cx="50" cy="50" fill="none" r="40" stroke="url(#gradient-convert)" stroke-dasharray="{{ $circ }}" stroke-dashoffset="{{ $offset }}" stroke-linecap="round" stroke-width="8"></circle>
                            <defs>
                                <linearGradient id="gradient-convert" x1="0%" x2="100%" y1="0%" y2="0%">
                                    <stop offset="0%" stop-color="#994700"></stop>
                                    <stop offset="100%" stop-color="#fb7800"></stop>
                                </linearGradient>
                            </defs>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-3xl font-bold text-primary font-data-tabular">{{ $reportKpis['conversion'] }}%</span>
                            <span class="font-label-caps text-[10px] text-on-surface-variant">WON / LEADS</span>
                        </div>
                    </div>
                    <div class="mt-2 space-y-2 text-sm border-t border-outline-variant/30 pt-3">
                        <div class="flex justify-between"><span class="text-on-surface-variant">Quote value</span><span class="font-bold text-primary font-data-tabular">₹{{ \App\Support\IndianNumber::format($reportKpis['quote_value'], 0) }}</span></div>
                        <div class="flex justify-between"><span class="text-on-surface-variant">Won value</span><span class="font-bold text-secondary font-data-tabular">₹{{ \App\Support\IndianNumber::format($reportKpis['won_value'], 0) }}</span></div>
                    </div>
                </div>
            </section>

            <section class="bg-white border border-outline-variant/30 rounded-2xl p-5 shadow-sm">
                <h3 class="font-label-caps text-label-caps text-primary font-bold mb-4">Branch Performance</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                    @forelse($citySales as $cs)
                    <div class="city-filterable" data-city="{{ $cs['name'] }}">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm font-semibold text-primary">{{ $cs['name'] }}</span>
                            <span class="font-data-tabular text-sm font-bold text-primary">₹{{ \App\Support\IndianNumber::format($cs['sales'], 0) }}</span>
                        </div>
                        <div class="h-2 w-full bg-surface-container-low rounded-full overflow-hidden">
                            <div class="h-full bg-secondary-container rounded-full" style="width: {{ min(100, max(4, ($cs['sales'] / max(1, $cs['target'])) * 100)) }}%"></div>
                        </div>
                    </div>
                    @empty
                    <p class="text-sm text-on-surface-variant">No cities configured yet.</p>
                    @endforelse
                </div>
            </section>
        </div>

        {{-- ================= SALESMAN WISE ================= --}}
        <div class="js-report-panel hidden space-y-4" data-report-panel="salesman">
            <section class="bg-white border border-outline-variant/30 rounded-2xl p-4 shadow-sm">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">search</span>
                        <input id="report-salesman-search" type="text" placeholder="Search salesman or branch..."
                               class="w-full pl-10 pr-3 py-2.5 bg-surface-container-low border-none rounded-lg text-sm focus:ring-2 focus:ring-secondary">
                    </div>
                    <div class="flex items-center gap-2 text-xs text-on-surface-variant sm:col-span-1 lg:col-span-2">
                        <span class="material-symbols-outlined text-base">insights</span>
                        <span>Sorted by won value · Click a row for assigned customers</span>
                    </div>
                </div>
            </section>

            <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
                <div class="bg-white border border-outline-variant/30 rounded-xl p-4 shadow-sm">
                    <p class="font-label-caps text-[10px] text-on-surface-variant mb-1">TEAM SIZE</p>
                    <p class="text-2xl font-bold text-primary font-data-tabular">{{ $salesmanReport->count() }}</p>
                </div>
                <div class="bg-white border border-outline-variant/30 rounded-xl p-4 shadow-sm">
                    <p class="font-label-caps text-[10px] text-on-surface-variant mb-1">TOTAL VISITS</p>
                    <p class="text-2xl font-bold text-primary font-data-tabular">{{ $salesmanReport->sum('visits') }}</p>
                </div>
                <div class="bg-white border border-outline-variant/30 rounded-xl p-4 shadow-sm">
                    <p class="font-label-caps text-[10px] text-on-surface-variant mb-1">TEAM QUOTES</p>
                    <p class="text-2xl font-bold text-primary font-data-tabular">{{ $salesmanReport->sum('quotes') }}</p>
                </div>
                <div class="bg-white border border-outline-variant/30 rounded-xl p-4 shadow-sm">
                    <p class="font-label-caps text-[10px] text-on-surface-variant mb-1">TEAM WON</p>
                    <p class="text-2xl font-bold text-secondary font-data-tabular">₹{{ \App\Support\IndianNumber::format($salesmanReport->sum('won_value'), 0) }}</p>
                </div>
            </section>

            <section class="bg-white border border-outline-variant/30 rounded-2xl overflow-hidden shadow-sm">
                <div class="p-5 border-b border-outline-variant/30 bg-surface-container-lowest flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="font-label-caps text-label-caps text-primary font-bold">Salesman-wise Report</h3>
                        <p class="text-xs text-on-surface-variant mt-1">Leads, field visits, quotations and won revenue per executive</p>
                    </div>
                    <span class="text-xs font-semibold text-on-surface-variant bg-surface-container-low px-3 py-1.5 rounded-full">{{ $salesmanReport->count() }} salesmen</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[960px]">
                        <thead class="bg-surface-container-low border-b border-outline-variant/30">
                            <tr>
                                <th class="px-5 py-3.5 font-label-caps text-label-caps text-on-surface-variant">Salesman</th>
                                <th class="px-5 py-3.5 font-label-caps text-label-caps text-on-surface-variant">Leads</th>
                                <th class="px-5 py-3.5 font-label-caps text-label-caps text-on-surface-variant">Visits</th>
                                <th class="px-5 py-3.5 font-label-caps text-label-caps text-on-surface-variant">Quotes</th>
                                <th class="px-5 py-3.5 font-label-caps text-label-caps text-on-surface-variant">Conversion</th>
                                <th class="px-5 py-3.5 font-label-caps text-label-caps text-on-surface-variant">Won Value</th>
                                <th class="px-5 py-3.5 font-label-caps text-label-caps text-on-surface-variant">Last Visit</th>
                                <th class="px-5 py-3.5 font-label-caps text-label-caps text-on-surface-variant">Customers</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/20">
                            @forelse($salesmanReport as $row)
                            <tr class="report-salesman-row hover:bg-primary/5 transition-all city-filterable"
                                data-city="{{ $row['city'] }}"
                                data-name="{{ strtolower($row['name'].' '.$row['branch']) }}"
                                data-salesman-id="{{ $row['id'] }}">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-primary text-on-primary flex items-center justify-center text-xs font-bold shrink-0">
                                            {{ strtoupper(substr($row['name'], 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-primary">{{ $row['name'] }}</p>
                                            <p class="text-[11px] text-on-surface-variant">{{ $row['branch'] }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="text-sm font-bold font-data-tabular text-primary">{{ $row['leads'] }}</p>
                                    <p class="text-[10px] text-on-surface-variant">{{ $row['active_leads'] }} active · {{ $row['won_leads'] }} won</p>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="text-sm font-bold font-data-tabular text-primary">{{ $row['visits'] }}</p>
                                    <p class="text-[10px] text-on-surface-variant">{{ $row['completed_visits'] }} completed</p>
                                </td>
                                <td class="px-5 py-4">
                                    <a href="{{ route('admin.quotations', array_merge($quoteParamsBase, ['salesman_id' => $row['id']])) }}" class="text-sm font-bold font-data-tabular text-primary hover:underline">
                                        {{ $row['quotes'] }}
                                    </a>
                                    <p class="text-[10px] text-on-surface-variant">{{ $row['approved_quotes'] }} approved · ₹{{ \App\Support\IndianNumber::format($row['quote_value'], 0) }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold font-data-tabular
                                        {{ $row['conversion'] >= 40 ? 'bg-green-100 text-green-800' : ($row['conversion'] >= 15 ? 'bg-amber-100 text-amber-800' : 'bg-surface-container-low text-on-surface-variant') }}">
                                        {{ $row['conversion'] }}%
                                    </span>
                                </td>
                                <td class="px-5 py-4 min-w-[140px]">
                                    <p class="text-sm font-bold text-secondary font-data-tabular mb-1">₹{{ \App\Support\IndianNumber::format($row['won_value'], 0) }}</p>
                                    <div class="h-1.5 w-full bg-surface-container-low rounded-full overflow-hidden">
                                        <div class="h-full bg-secondary rounded-full" style="width: {{ $row['won_bar'] }}%"></div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-xs font-data-tabular text-on-surface-variant">
                                    {{ $row['last_visit_at'] ? $row['last_visit_at']->format('d M Y') : '—' }}
                                </td>
                                <td class="px-5 py-4">
                                    <a href="{{ route('admin.reports', array_filter(array_merge($customerTabParams, ['tab' => 'customer', 'salesman_id' => $row['id'], 'telecaller_id' => $filters['telecaller_id'] ?? null]))) }}"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-primary text-on-primary hover:bg-secondary transition-all">
                                        <span class="material-symbols-outlined text-sm">group</span>
                                        View
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="py-10 text-center text-on-surface-variant/70 text-sm">No sales executives found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        {{-- ================= TELECALLER WISE ================= --}}
        @php
            $telecallerReport = $telecallerReport ?? collect();
            $telecallerCallLog = $telecallerCallLog ?? collect();
        @endphp
        <div class="js-report-panel hidden space-y-4" data-report-panel="telecaller">
            <section class="bg-white border border-outline-variant/30 rounded-2xl p-4 shadow-sm">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">search</span>
                        <input id="report-telecaller-search" type="text" placeholder="Search telecaller or branch..."
                               class="w-full pl-10 pr-3 py-2.5 bg-surface-container-low border-none rounded-lg text-sm focus:ring-2 focus:ring-secondary">
                    </div>
                    <div class="flex items-center gap-2 text-xs text-on-surface-variant sm:col-span-1 lg:col-span-2">
                        <span class="material-symbols-outlined text-base">call</span>
                        <span>Sorted by won value · Click a row for assigned customers</span>
                    </div>
                </div>
            </section>

            <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
                <div class="bg-white border border-outline-variant/30 rounded-xl p-4 shadow-sm">
                    <p class="font-label-caps text-[10px] text-on-surface-variant mb-1">TEAM SIZE</p>
                    <p class="text-2xl font-bold text-primary font-data-tabular">{{ $telecallerReport->count() }}</p>
                </div>
                <div class="bg-white border border-outline-variant/30 rounded-xl p-4 shadow-sm">
                    <p class="font-label-caps text-[10px] text-on-surface-variant mb-1">TOTAL CALLS</p>
                    <p class="text-2xl font-bold text-primary font-data-tabular">{{ $telecallerReport->sum('calls') }}</p>
                </div>
                <div class="bg-white border border-outline-variant/30 rounded-xl p-4 shadow-sm">
                    <p class="font-label-caps text-[10px] text-on-surface-variant mb-1">TEAM QUOTES</p>
                    <p class="text-2xl font-bold text-primary font-data-tabular">{{ $telecallerReport->sum('quotes') }}</p>
                </div>
                <div class="bg-white border border-outline-variant/30 rounded-xl p-4 shadow-sm">
                    <p class="font-label-caps text-[10px] text-on-surface-variant mb-1">TEAM WON</p>
                    <p class="text-2xl font-bold text-secondary font-data-tabular">₹{{ \App\Support\IndianNumber::format($telecallerReport->sum('won_value'), 0) }}</p>
                </div>
            </section>

            <section class="bg-white border border-outline-variant/30 rounded-2xl overflow-hidden shadow-sm">
                <div class="p-5 border-b border-outline-variant/30 bg-surface-container-lowest flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="font-label-caps text-label-caps text-primary font-bold">Telecaller-wise Report</h3>
                        <p class="text-xs text-on-surface-variant mt-1">Assigned leads, call outcomes, quotations and won revenue per telecaller</p>
                    </div>
                    <span class="text-xs font-semibold text-on-surface-variant bg-surface-container-low px-3 py-1.5 rounded-full">{{ $telecallerReport->count() }} telecallers</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[960px]">
                        <thead class="bg-surface-container-low border-b border-outline-variant/30">
                            <tr>
                                <th class="px-5 py-3.5 font-label-caps text-label-caps text-on-surface-variant">Telecaller</th>
                                <th class="px-5 py-3.5 font-label-caps text-label-caps text-on-surface-variant">Leads</th>
                                <th class="px-5 py-3.5 font-label-caps text-label-caps text-on-surface-variant">Calls</th>
                                <th class="px-5 py-3.5 font-label-caps text-label-caps text-on-surface-variant">Quotes</th>
                                <th class="px-5 py-3.5 font-label-caps text-label-caps text-on-surface-variant">Conversion</th>
                                <th class="px-5 py-3.5 font-label-caps text-label-caps text-on-surface-variant">Won Value</th>
                                <th class="px-5 py-3.5 font-label-caps text-label-caps text-on-surface-variant">Last Call</th>
                                <th class="px-5 py-3.5 font-label-caps text-label-caps text-on-surface-variant">Customers</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/20">
                            @forelse($telecallerReport as $row)
                            <tr class="report-telecaller-row hover:bg-primary/5 transition-all city-filterable"
                                data-city="{{ $row['city'] }}"
                                data-name="{{ strtolower($row['name'].' '.$row['branch']) }}"
                                data-telecaller-id="{{ $row['id'] }}">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center text-xs font-bold shrink-0">
                                            {{ strtoupper(substr($row['name'], 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-primary">{{ $row['name'] }}</p>
                                            <p class="text-[11px] text-on-surface-variant">{{ $row['branch'] }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="text-sm font-bold font-data-tabular text-primary">{{ $row['leads'] }}</p>
                                    <p class="text-[10px] text-on-surface-variant">{{ $row['active_leads'] }} active · {{ $row['won_leads'] }} won</p>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="text-sm font-bold font-data-tabular text-primary">{{ $row['calls'] }}</p>
                                    <p class="text-[10px] text-on-surface-variant">{{ $row['interested'] }} interested · {{ $row['followups'] }} follow-up</p>
                                    <p class="text-[10px] text-on-surface-variant">{{ $row['quote_requested'] }} quote req · {{ $row['not_interested'] }} not interested</p>
                                </td>
                                <td class="px-5 py-4">
                                    <a href="{{ route('admin.quotations', array_merge($quoteParamsBase, ['telecaller_id' => $row['id']])) }}" class="text-sm font-bold font-data-tabular text-primary hover:underline">
                                        {{ $row['quotes'] }}
                                    </a>
                                    <p class="text-[10px] text-on-surface-variant">{{ $row['approved_quotes'] }} approved · ₹{{ \App\Support\IndianNumber::format($row['quote_value'], 0) }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold font-data-tabular
                                        {{ $row['conversion'] >= 40 ? 'bg-green-100 text-green-800' : ($row['conversion'] >= 15 ? 'bg-amber-100 text-amber-800' : 'bg-surface-container-low text-on-surface-variant') }}">
                                        {{ $row['conversion'] }}%
                                    </span>
                                </td>
                                <td class="px-5 py-4 min-w-[140px]">
                                    <p class="text-sm font-bold text-secondary font-data-tabular mb-1">₹{{ \App\Support\IndianNumber::format($row['won_value'], 0) }}</p>
                                    <div class="h-1.5 w-full bg-surface-container-low rounded-full overflow-hidden">
                                        <div class="h-full bg-secondary rounded-full" style="width: {{ $row['won_bar'] }}%"></div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-xs font-data-tabular text-on-surface-variant">
                                    {{ $row['last_call_at'] ? $row['last_call_at']->format('d M Y') : '—' }}
                                </td>
                                <td class="px-5 py-4">
                                    <a href="{{ route('admin.reports', array_filter(array_merge($customerTabParams, ['tab' => 'customer', 'telecaller_id' => $row['id'], 'salesman_id' => $filters['salesman_id'] ?? null]))) }}"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-primary text-on-primary hover:bg-secondary transition-all">
                                        <span class="material-symbols-outlined text-sm">group</span>
                                        View
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="py-10 text-center text-on-surface-variant/70 text-sm">No telecallers found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="bg-white border border-outline-variant/30 rounded-2xl overflow-hidden shadow-sm">
                <div class="p-5 border-b border-outline-variant/30 bg-surface-container-lowest flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="font-label-caps text-label-caps text-primary font-bold">Call Activity Detail</h3>
                        <p class="text-xs text-on-surface-variant mt-1">Recent dials with outcome, remarks and next follow-up</p>
                    </div>
                    <span class="text-xs font-semibold text-on-surface-variant bg-surface-container-low px-3 py-1.5 rounded-full">{{ $telecallerCallLog->count() }} calls</span>
                </div>
                <div class="overflow-x-auto max-h-[480px] overflow-y-auto">
                    <table class="w-full text-left border-collapse min-w-[900px]">
                        <thead class="bg-surface-container-low border-b border-outline-variant/30 sticky top-0">
                            <tr>
                                <th class="px-5 py-3 font-label-caps text-label-caps text-on-surface-variant">Time</th>
                                <th class="px-5 py-3 font-label-caps text-label-caps text-on-surface-variant">Telecaller</th>
                                <th class="px-5 py-3 font-label-caps text-label-caps text-on-surface-variant">Customer</th>
                                <th class="px-5 py-3 font-label-caps text-label-caps text-on-surface-variant">Outcome</th>
                                <th class="px-5 py-3 font-label-caps text-label-caps text-on-surface-variant">Remarks</th>
                                <th class="px-5 py-3 font-label-caps text-label-caps text-on-surface-variant">Follow-up</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/20">
                            @forelse($telecallerCallLog as $call)
                            <tr class="hover:bg-primary/5 transition-all city-filterable report-telecaller-call-row"
                                data-city="{{ $call->company->city ?? '' }}"
                                data-name="{{ strtolower(($call->telecaller->name ?? '').' '.($call->company->name ?? '')) }}">
                                <td class="px-5 py-3 text-xs font-data-tabular text-primary js-local-datetime" data-iso="{{ $call->called_at?->toIso8601String() }}">
                                    {{ $call->called_at?->format('d M Y, h:i A') }}
                                </td>
                                <td class="px-5 py-3 text-sm font-medium text-primary">{{ $call->telecaller->name ?? '—' }}</td>
                                <td class="px-5 py-3">
                                    @if($call->company)
                                        <a href="{{ route('admin.customers.show', $call->company->id) }}" class="text-sm font-bold text-primary hover:underline">{{ $call->company->name }}</a>
                                        <p class="text-[11px] text-on-surface-variant">{{ $call->company->city ?: '—' }} · {{ $call->company->stage ?: '—' }}</p>
                                    @else
                                        <span class="text-sm text-on-surface-variant">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ match($call->outcome) {
                                        'Interested', 'Profile Shared', 'Regular Party' => 'bg-green-100 text-green-800',
                                        'Follow-Up', 'Call Back', 'Callback', 'Ringing' => 'bg-orange-100 text-orange-800',
                                        'Not Interested', 'Not interested', 'No Requirement' => 'bg-red-100 text-red-800',
                                        'Quotation requested' => 'bg-blue-100 text-blue-800',
                                        default => 'bg-primary-fixed text-primary',
                                    } }}">{{ $call->outcome }}</span>
                                </td>
                                <td class="px-5 py-3 text-sm text-on-surface-variant leading-snug max-w-[280px]">{{ $call->remarks ?: '—' }}</td>
                                <td class="px-5 py-3 text-xs font-data-tabular {{ $call->next_followup ? 'text-secondary font-bold' : 'text-on-surface-variant' }}">
                                    {{ $call->next_followup ? $call->next_followup->format('d M Y') : '—' }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-on-surface-variant/60 text-xs">No call records in this period.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        {{-- ================= CUSTOMER WISE ================= --}}
        <div class="js-report-panel hidden space-y-4" data-report-panel="customer">
            <section class="bg-white border border-outline-variant/30 rounded-2xl p-4 shadow-sm">
                <form method="GET" action="{{ route('admin.reports') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                    <input type="hidden" name="tab" value="customer">
                    @if($filters['from'])
                        <input type="hidden" name="from" value="{{ $filters['from']->toDateString() }}">
                    @endif
                    @if($filters['to'])
                        <input type="hidden" name="to" value="{{ $filters['to']->toDateString() }}">
                    @endif
                    @if(($filters['stage_group'] ?? '') !== '')
                        <input type="hidden" name="stage_group" value="{{ $filters['stage_group'] }}">
                    @endif
                    @if(($filters['per_page'] ?? 25) != 25)
                        <input type="hidden" name="per_page" value="{{ $filters['per_page'] }}">
                    @endif
                    @if(($filters['activity'] ?? '') !== '')
                        <input type="hidden" name="activity" value="{{ $filters['activity'] }}">
                    @endif
                    <div class="relative sm:col-span-2 lg:col-span-2">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">search</span>
                        <input id="report-customer-search" type="text" name="customer_q" value="{{ $filters['customer_q'] ?? '' }}" placeholder="Search customer, city, product, mobile..."
                               class="w-full pl-10 pr-3 py-2.5 bg-surface-container-low border-none rounded-lg text-sm focus:ring-2 focus:ring-secondary">
                        <button type="submit" class="sr-only">Search</button>
                    </div>
                    <div>
                        <select id="report-customer-salesman" name="salesman_id" class="w-full py-2.5 px-3 bg-surface-container-low border-none rounded-lg text-sm focus:ring-2 focus:ring-secondary" onchange="this.form.submit()">
                            <option value="">All salesmen</option>
                            @foreach(($salesExecutives ?? collect()) as $exec)
                                <option value="{{ $exec->id }}" @selected((int) ($filters['salesman_id'] ?? 0) === (int) $exec->id)>{{ $exec->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <select id="report-customer-telecaller" name="telecaller_id" class="w-full py-2.5 px-3 bg-surface-container-low border-none rounded-lg text-sm focus:ring-2 focus:ring-secondary" onchange="this.form.submit()">
                            <option value="">All telecallers</option>
                            @foreach(($telecallers ?? collect()) as $tc)
                                <option value="{{ $tc->id }}" @selected((int) ($filters['telecaller_id'] ?? 0) === (int) $tc->id)>{{ $tc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <select id="report-customer-stage" name="customer_stage" class="w-full py-2.5 px-3 bg-surface-container-low border-none rounded-lg text-sm focus:ring-2 focus:ring-secondary" onchange="this.form.submit()">
                            <option value="">All stages</option>
                            @foreach(\App\Models\Company::STAGES as $stage)
                                <option value="{{ $stage }}" @selected(($filters['customer_stage'] ?? '') === $stage)>{{ $stage }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <span class="text-[10px] font-bold uppercase tracking-wide text-on-surface-variant mr-1">Activity</span>
                    @foreach(['' => 'All', 'visits' => 'Visits', 'quotes' => 'Quotes', 'calls' => 'Calls'] as $value => $label)
                        <a href="{{ route('admin.reports', array_filter(array_merge($customerTabParams, ['tab' => 'customer', 'activity' => $value !== '' ? $value : null]))) }}"
                           class="px-3 py-1.5 rounded-full text-xs font-bold transition-all {{ ($filters['activity'] ?? '') === $value ? 'bg-primary text-on-primary shadow-sm' : 'bg-surface-container-low text-on-surface-variant hover:text-primary' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
                @if(($filters['customer_q'] ?? '') !== '' || ($filters['customer_stage'] ?? '') !== '' || ($filters['activity'] ?? '') !== '')
                    <p class="mt-3 text-xs text-secondary font-semibold">Filtered customer list · <a href="{{ route('admin.reports', array_filter(array_merge($customerTabParams, ['tab' => 'customer', 'customer_q' => null, 'customer_stage' => null, 'activity' => null]))) }}" class="underline">Clear search</a></p>
                @endif
            </section>

            <section class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="bg-white border border-outline-variant/30 rounded-xl p-4 shadow-sm">
                    <p class="font-label-caps text-[10px] text-on-surface-variant mb-1">CUSTOMERS</p>
                    <p class="text-2xl font-bold text-primary font-data-tabular" id="report-customer-visible-count">{{ $customerReportTotals['count'] }}</p>
                </div>
                <div class="bg-white border border-outline-variant/30 rounded-xl p-4 shadow-sm">
                    <p class="font-label-caps text-[10px] text-on-surface-variant mb-1">TOTAL VISITS</p>
                    <p class="text-2xl font-bold text-primary font-data-tabular">{{ $customerReportTotals['visits'] }}</p>
                </div>
                <div class="bg-white border border-outline-variant/30 rounded-xl p-4 shadow-sm">
                    <p class="font-label-caps text-[10px] text-on-surface-variant mb-1">QUOTE VALUE</p>
                    <p class="text-2xl font-bold text-primary font-data-tabular">₹{{ \App\Support\IndianNumber::format($customerReportTotals['quote_value'], 0) }}</p>
                </div>
                <div class="bg-white border border-outline-variant/30 rounded-xl p-4 shadow-sm">
                    <p class="font-label-caps text-[10px] text-on-surface-variant mb-1">WON VALUE</p>
                    <p class="text-2xl font-bold text-secondary font-data-tabular">₹{{ \App\Support\IndianNumber::format($customerReportTotals['won_value'], 0) }}</p>
                </div>
            </section>

            <section class="bg-white border border-outline-variant/30 rounded-2xl overflow-hidden shadow-sm">
                <div class="p-5 border-b border-outline-variant/30 bg-surface-container-lowest flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="font-label-caps text-label-caps text-primary font-bold">Customer-wise Detail</h3>
                        <p class="text-xs text-on-surface-variant mt-1">Pipeline stage, salesman, visits, quotes, products and value per customer</p>
                    </div>
                    <span class="text-xs font-semibold text-on-surface-variant bg-surface-container-low px-3 py-1.5 rounded-full">{{ $customerReport->total() }} customers</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[1100px]">
                        <thead class="bg-surface-container-low border-b border-outline-variant/30">
                            <tr>
                                <th class="px-5 py-3.5 font-label-caps text-label-caps text-on-surface-variant">Customer</th>
                                <th class="px-5 py-3.5 font-label-caps text-label-caps text-on-surface-variant">Salesman</th>
                                <th class="px-5 py-3.5 font-label-caps text-label-caps text-on-surface-variant">Stage</th>
                                <th class="px-5 py-3.5 font-label-caps text-label-caps text-on-surface-variant">Activity</th>
                                <th class="px-5 py-3.5 font-label-caps text-label-caps text-on-surface-variant">Products</th>
                                <th class="px-5 py-3.5 font-label-caps text-label-caps text-on-surface-variant">Value</th>
                                <th class="px-5 py-3.5 font-label-caps text-label-caps text-on-surface-variant">Last Touch</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/20">
                            @forelse($customerReport as $row)
                            <tr class="report-customer-row hover:bg-primary/5 transition-all city-filterable"
                                data-city="{{ $row['city'] }}"
                                data-name="{{ strtolower($row['name'].' '.$row['city'].' '.$row['sub_city'].' '.$row['mobile'].' '.$row['products_label'].' '.$row['salesman'].' '.$row['telecaller']) }}"
                                data-salesman-id="{{ $row['salesman_id'] ?: 'unassigned' }}"
                                data-telecaller-id="{{ $row['telecaller_id'] ?: 'unassigned' }}"
                                data-stage="{{ $row['stage'] }}">
                                <td class="px-5 py-4">
                                    <a href="{{ route('admin.customers.show', $row['id']) }}" class="text-sm font-bold text-primary hover:text-secondary hover:underline underline-offset-2">{{ $row['name'] }}</a>
                                    <p class="text-[11px] text-on-surface-variant">
                                        {{ trim(implode(' · ', array_filter([$row['city'], $row['sub_city']]))) ?: '—' }}
                                        @if($row['mobile'] !== '—') · {{ $row['mobile'] }} @endif
                                    </p>
                                    <p class="text-[10px] text-on-surface-variant mt-0.5">
                                        @if(($row['origin_type'] ?? '') === 'admin_assign')
                                            <span class="text-blue-700 font-semibold">Admin Assign</span>
                                        @elseif(($row['origin_type'] ?? '') === 'self')
                                            <span class="text-green-700 font-semibold">Self</span>
                                        @else
                                            {{ $row['origin'] }}
                                        @endif
                                        · TC: {{ $row['telecaller'] }}
                                    </p>
                                </td>
                                <td class="px-5 py-4 text-sm font-medium text-primary">{{ $row['salesman'] }}</td>
                                <td class="px-5 py-4">
                                    @php
                                        $stageClass = match($row['stage']) {
                                            'Won' => 'bg-green-100 text-green-800',
                                            'Lost' => 'bg-red-100 text-red-800',
                                            'Proposal', 'Negotiation' => 'bg-amber-100 text-amber-800',
                                            default => 'bg-surface-container-low text-on-surface-variant',
                                        };
                                    @endphp
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide {{ $stageClass }}">{{ $row['stage'] }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap gap-1.5">
                                        <button type="button"
                                           class="js-report-activity inline-flex items-center gap-1 px-2 py-1 rounded-md bg-surface-container-low text-[10px] font-bold text-primary hover:bg-primary hover:text-on-primary transition-colors"
                                           data-company-id="{{ $row['id'] }}"
                                           data-company-name="{{ $row['name'] }}"
                                           data-type="visits"
                                           title="View visits for {{ $row['name'] }}">
                                            <span class="material-symbols-outlined text-[13px]">pin_drop</span>{{ $row['visits'] }} visits
                                        </button>
                                        <button type="button"
                                           class="js-report-activity inline-flex items-center gap-1 px-2 py-1 rounded-md bg-surface-container-low text-[10px] font-bold text-primary hover:bg-primary hover:text-on-primary transition-colors"
                                           data-company-id="{{ $row['id'] }}"
                                           data-company-name="{{ $row['name'] }}"
                                           data-type="quotes"
                                           title="View quotations for {{ $row['name'] }}">
                                            <span class="material-symbols-outlined text-[13px]">description</span>{{ $row['quotes'] }} quotes
                                        </button>
                                        <button type="button"
                                           class="js-report-activity inline-flex items-center gap-1 px-2 py-1 rounded-md bg-surface-container-low text-[10px] font-bold text-primary hover:bg-primary hover:text-on-primary transition-colors"
                                           data-company-id="{{ $row['id'] }}"
                                           data-company-name="{{ $row['name'] }}"
                                           data-type="calls"
                                           title="View calls for {{ $row['name'] }}">
                                            <span class="material-symbols-outlined text-[13px]">call</span>{{ $row['calls'] }} calls
                                        </button>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-xs text-on-surface-variant max-w-[220px]">
                                    {{ $row['products_label'] }}
                                </td>
                                <td class="px-5 py-4">
                                    <p class="text-sm font-bold text-secondary font-data-tabular">₹{{ \App\Support\IndianNumber::format($row['won_value'], 0) }}</p>
                                    <p class="text-[10px] text-on-surface-variant font-data-tabular">Quoted ₹{{ \App\Support\IndianNumber::format($row['quote_value'], 0) }}</p>
                                    @if($row['outstanding'] > 0)
                                        <p class="text-[10px] text-red-700 font-semibold font-data-tabular">OS ₹{{ \App\Support\IndianNumber::format($row['outstanding'], 0) }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-xs font-data-tabular text-on-surface-variant">
                                    <p>Visit: {{ $row['last_visit_at'] ? $row['last_visit_at']->format('d M Y') : '—' }}</p>
                                    <p>Quote: {{ $row['last_quote_at'] ? $row['last_quote_at']->format('d M Y') : '—' }}</p>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="py-10 text-center text-on-surface-variant/70 text-sm">No customers found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($customerReport->total() > 0)
                <div class="p-5 border-t border-outline-variant/30 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 bg-surface-container-lowest">
                    <p class="text-xs text-on-surface-variant">
                        Showing {{ $customerReport->firstItem() }}–{{ $customerReport->lastItem() }} of {{ $customerReport->total() }}
                        · Page {{ $customerReport->currentPage() }} of {{ $customerReport->lastPage() }}
                    </p>
                    <div class="flex flex-wrap items-center gap-3">
                        <form method="GET" action="{{ route('admin.reports') }}" class="flex items-center gap-2">
                            <input type="hidden" name="tab" value="customer">
                            @if($filters['from'])
                                <input type="hidden" name="from" value="{{ $filters['from']->toDateString() }}">
                            @endif
                            @if($filters['to'])
                                <input type="hidden" name="to" value="{{ $filters['to']->toDateString() }}">
                            @endif
                            @if($filters['salesman_id'])
                                <input type="hidden" name="salesman_id" value="{{ $filters['salesman_id'] }}">
                            @endif
                            @if($filters['telecaller_id'])
                                <input type="hidden" name="telecaller_id" value="{{ $filters['telecaller_id'] }}">
                            @endif
                            @if(($filters['stage_group'] ?? '') !== '')
                                <input type="hidden" name="stage_group" value="{{ $filters['stage_group'] }}">
                            @endif
                            @if(($filters['customer_q'] ?? '') !== '')
                                <input type="hidden" name="customer_q" value="{{ $filters['customer_q'] }}">
                            @endif
                            @if(($filters['customer_stage'] ?? '') !== '')
                                <input type="hidden" name="customer_stage" value="{{ $filters['customer_stage'] }}">
                            @endif
                            @if(($filters['activity'] ?? '') !== '')
                                <input type="hidden" name="activity" value="{{ $filters['activity'] }}">
                            @endif
                            <label for="report-customer-per-page" class="text-xs font-bold text-on-surface-variant whitespace-nowrap">Rows</label>
                            <select id="report-customer-per-page" name="per_page" onchange="this.form.submit()"
                                    class="border border-outline-variant/50 rounded-lg text-xs py-1.5 px-2 bg-white focus:ring-2 focus:ring-primary focus:border-primary">
                                @foreach([25, 50, 100, 200] as $option)
                                    <option value="{{ $option }}" @selected((int) ($filters['per_page'] ?? 25) === $option)>{{ $option }} / page</option>
                                @endforeach
                            </select>
                        </form>
                        @if($customerReport->hasPages())
                        <div class="flex flex-wrap items-center gap-1">
                            @if($customerReport->onFirstPage())
                                <span class="px-3 py-1.5 rounded-lg text-xs font-semibold text-on-surface-variant/40 border border-outline-variant/20">Prev</span>
                            @else
                                <a href="{{ $customerReport->previousPageUrl() }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-primary border border-outline-variant/40 hover:bg-white transition-all">Prev</a>
                            @endif
                            @foreach($customerReport->getUrlRange(max(1, $customerReport->currentPage() - 2), min($customerReport->lastPage(), $customerReport->currentPage() + 2)) as $page => $url)
                                @if($page == $customerReport->currentPage())
                                    <span class="px-3 py-1.5 rounded-lg text-xs font-bold bg-primary text-on-primary">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-primary border border-outline-variant/40 hover:bg-white transition-all">{{ $page }}</a>
                                @endif
                            @endforeach
                            @if($customerReport->hasMorePages())
                                <a href="{{ $customerReport->nextPageUrl() }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-primary border border-outline-variant/40 hover:bg-white transition-all">Next</a>
                            @else
                                <span class="px-3 py-1.5 rounded-lg text-xs font-semibold text-on-surface-variant/40 border border-outline-variant/20">Next</span>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
                @endif
                <p id="report-customer-empty" class="hidden py-10 text-center text-on-surface-variant/70 text-sm">No customers match these filters.</p>
            </section>

            {{-- Keep secondary operational tables under customer tab for continuity --}}
            <section class="bg-white border border-outline-variant/30 rounded-2xl overflow-hidden shadow-sm">
                <div class="p-5 border-b border-outline-variant/30 bg-surface-container-lowest flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="font-label-caps text-label-caps text-primary font-bold">Recent Shop Visits</h3>
                        <p class="text-xs text-on-surface-variant mt-1">Latest visit status per shop</p>
                    </div>
                    <span class="text-xs font-semibold text-on-surface-variant bg-surface-container-low px-3 py-1.5 rounded-full">{{ $shopVisitReport->count() }} shops</span>
                </div>
                <div class="overflow-x-auto max-h-[420px] overflow-y-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-surface-container-low border-b border-outline-variant/30 sticky top-0">
                            <tr>
                                <th class="px-5 py-3 font-label-caps text-label-caps text-on-surface-variant">Shop</th>
                                <th class="px-5 py-3 font-label-caps text-label-caps text-on-surface-variant">Salesperson</th>
                                <th class="px-5 py-3 font-label-caps text-label-caps text-on-surface-variant">Last Visit</th>
                                <th class="px-5 py-3 font-label-caps text-label-caps text-on-surface-variant">Status</th>
                                <th class="px-5 py-3 font-label-caps text-label-caps text-on-surface-variant">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/20">
                            @forelse($shopVisitReport->take(40) as $row)
                            <tr class="hover:bg-primary/5 transition-all city-filterable" data-city="{{ $row['city'] ?? '' }}">
                                <td class="px-5 py-3">
                                    <p class="text-sm font-bold text-primary">{{ $row['shop'] }}</p>
                                    <p class="text-[11px] text-on-surface-variant">{{ $row['city'] ?: '—' }}</p>
                                </td>
                                <td class="px-5 py-3 text-sm">{{ $row['salesperson'] }}</td>
                                <td class="px-5 py-3 text-xs font-data-tabular">
                                    {{ $row['last_visit_at'] ? $row['last_visit_at']->format('d M Y, h:i A') : '—' }}
                                </td>
                                <td class="px-5 py-3">
                                    @if(($row['status'] ?? '') === 'checked_in')
                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase bg-secondary-fixed text-on-secondary-fixed-variant">In Progress</span>
                                    @elseif(($row['status'] ?? '') === 'checked_out')
                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase bg-green-100 text-green-800">Completed</span>
                                    @else
                                        <span class="text-on-surface-variant text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3">
                                    @if($row['visit_id'])
                                    <a href="{{ route('admin.visits.show', $row['visit_id']) }}"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-primary text-on-primary hover:bg-secondary transition-all">
                                        <span class="material-symbols-outlined text-sm">visibility</span>
                                        Details
                                    </a>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-on-surface-variant/60 text-xs">No shop visits recorded yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

<div id="modal-report-activity" class="fixed inset-0 z-50 hidden" aria-modal="true" role="dialog">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="toggleModal('modal-report-activity')"></div>
    <div class="relative z-10 h-full w-full flex items-end sm:items-center justify-center p-0 sm:p-4 pointer-events-none">
        <div class="pointer-events-auto bg-white border border-outline-variant/30 w-full max-w-lg max-h-[86dvh] rounded-t-2xl sm:rounded-2xl shadow-2xl flex flex-col overflow-hidden">
            <div class="shrink-0 flex items-center justify-between gap-3 px-4 sm:px-5 py-3 border-b border-outline-variant/30">
                <div class="min-w-0">
                    <p id="report-activity-type" class="text-[10px] font-bold uppercase tracking-wide text-on-surface-variant">Activity</p>
                    <h3 id="report-activity-title" class="font-headline-md text-primary font-bold truncate">Customer</h3>
                </div>
                <button type="button" onclick="toggleModal('modal-report-activity')" class="shrink-0 w-10 h-10 flex items-center justify-center rounded-full bg-surface-container-low text-on-surface-variant hover:text-primary" aria-label="Close">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div id="report-activity-body" class="flex-1 min-h-0 overflow-y-auto px-4 sm:px-5 py-4 space-y-2">
                <p class="text-sm text-on-surface-variant">Loading…</p>
            </div>
        </div>
    </div>
</div>
