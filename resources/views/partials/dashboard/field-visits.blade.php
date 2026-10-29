    <!-- ================= VIEW: FIELD VISITS ================= -->
    <div id="view-field" class="space-y-xl">
        @php
            $visitCities = $visits->map(fn ($v) => $v->company->city ?? null)->filter()->unique()->sort()->values();
            $visitExecutives = $visits->map(fn ($v) => $v->executive)->filter()->unique('id')->sortBy('name')->values();
        @endphp
        <section class="flex flex-col lg:flex-row gap-6">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-1">
                    <span class="material-symbols-outlined text-secondary text-[24px]">calendar_today</span>
                    <h2 class="font-headline-md text-headline-md text-primary font-bold">Field Visit Log</h2>
                </div>
                <p class="text-on-surface-variant font-body-sm font-medium">Real-time scheduling and execution tracking for power infrastructure audits.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 w-full lg:w-auto">
                <div class="bg-white border border-outline-variant/30 rounded-xl p-4 flex flex-col min-w-[180px] shadow-sm">
                    <span class="font-label-caps text-label-caps text-on-surface-variant/70 mb-2">TODAY'S TOTAL</span>
                    <div class="flex items-end justify-between">
                        <span class="font-display-lg text-3xl font-bold text-primary">{{ count($visits) }}</span>
                        <span class="text-secondary font-bold text-xs mb-1">Live count</span>
                    </div>
                </div>
                <div class="bg-white border border-outline-variant/30 rounded-xl p-4 flex flex-col min-w-[180px] shadow-sm">
                    <span class="font-label-caps text-label-caps text-on-surface-variant/70 mb-2">IN PROGRESS</span>
                    <div class="flex items-end justify-between">
                        <span class="font-display-lg text-3xl font-bold text-secondary">{{ $visits->where('status', 'checked_in')->count() }}</span>
                        <div class="w-12 h-1 bg-surface-container rounded-full mb-2 overflow-hidden">
                            <div class="bg-secondary h-full w-2/3"></div>
                        </div>
                    </div>
                </div>
                <div class="bg-white border border-outline-variant/30 rounded-xl p-4 flex flex-col min-w-[180px] shadow-sm">
                    <span class="font-label-caps text-label-caps text-on-surface-variant/70 mb-2">COMPLETED</span>
                    <div class="flex items-end justify-between">
                        <span class="font-display-lg text-3xl font-bold text-green-700">{{ $visits->where('status', 'checked_out')->count() }}</span>
                        <span class="material-symbols-outlined text-green-600 mb-1">task_alt</span>
                    </div>
                </div>
            </div>
        </section>

        <div class="flex flex-wrap items-center gap-3 sm:gap-4 bg-white p-3 sm:p-4 rounded-xl border border-outline-variant shadow-sm">
            <div class="relative flex-1 min-w-[100%] sm:min-w-[220px]">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
                <input id="field-filter-search" class="w-full pl-10 pr-4 py-2.5 sm:py-2 bg-surface-container-low border-none rounded-lg text-body-sm focus:ring-2 focus:ring-secondary" placeholder="Search visit ID, customer, salesperson, notes..." type="text"/>
            </div>
            <div class="flex flex-wrap items-center gap-2 sm:gap-3 w-full sm:w-auto">
                <select id="field-filter-status" class="flex-1 sm:flex-none min-w-[140px] bg-surface-container-low border-none rounded-lg text-body-sm py-2.5 sm:py-2 px-3 sm:px-4 focus:ring-2 focus:ring-secondary">
                    <option value="">All Statuses</option>
                    <option value="checked_in">In Progress</option>
                    <option value="checked_out">Completed</option>
                </select>
                <select id="field-filter-exec" class="flex-1 sm:flex-none min-w-[140px] bg-surface-container-low border-none rounded-lg text-body-sm py-2.5 sm:py-2 px-3 sm:px-4 focus:ring-2 focus:ring-secondary">
                    <option value="">All Salespersons</option>
                    @foreach($visitExecutives as $exec)
                        <option value="{{ $exec->id }}">{{ $exec->name }}</option>
                    @endforeach
                </select>
                <select id="field-filter-city" class="flex-1 sm:flex-none min-w-[140px] bg-surface-container-low border-none rounded-lg text-body-sm py-2.5 sm:py-2 px-3 sm:px-4 focus:ring-2 focus:ring-secondary">
                    <option value="">All Regions</option>
                    @foreach($visitCities as $city)
                        <option value="{{ $city }}">{{ $city }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Full meeting history table -->
        <section class="bg-white border border-outline-variant/30 rounded-2xl shadow-sm">
            <div class="p-4 sm:p-5 border-b border-outline-variant/30 bg-surface-container-lowest flex flex-wrap items-center justify-between gap-3 rounded-t-2xl">
                <div>
                    <h3 class="font-label-caps text-label-caps text-primary font-bold">ALL MEETING HISTORY</h3>
                    <p class="text-xs text-on-surface-variant mt-1">Field visit log across all executives</p>
                </div>
                <span id="field-filter-count" class="text-xs font-semibold text-on-surface-variant bg-surface-container-low px-3 py-1.5 rounded-full">{{ $visits->count() }} visits</span>
            </div>
            <div class="overflow-x-auto rounded-b-2xl">
                <table class="w-full text-left border-collapse min-w-[1100px]">
                    <thead class="bg-surface-container-low border-b border-outline-variant/30">
                        <tr>
                            <th class="px-4 sm:px-6 py-4 font-label-caps text-label-caps text-on-surface-variant whitespace-nowrap">Visit ID</th>
                            <th class="px-4 sm:px-6 py-4 font-label-caps text-label-caps text-on-surface-variant whitespace-nowrap">Customer</th>
                            <th class="px-4 sm:px-6 py-4 font-label-caps text-label-caps text-on-surface-variant whitespace-nowrap">Salesperson</th>
                            <th class="px-4 sm:px-6 py-4 font-label-caps text-label-caps text-on-surface-variant whitespace-nowrap">Check-in</th>
                            <th class="px-4 sm:px-6 py-4 font-label-caps text-label-caps text-on-surface-variant whitespace-nowrap">Check-out</th>
                            <th class="px-4 sm:px-6 py-4 font-label-caps text-label-caps text-on-surface-variant whitespace-nowrap">Status</th>
                            <th class="px-4 sm:px-6 py-4 font-label-caps text-label-caps text-on-surface-variant whitespace-nowrap">Discount</th>
                            <th class="px-4 sm:px-6 py-4 font-label-caps text-label-caps text-on-surface-variant min-w-[140px]">Notes</th>
                            <th class="px-3 sm:px-4 py-4 font-label-caps text-label-caps text-on-surface-variant text-right sticky right-0 z-20 bg-surface-container-low min-w-[110px] shadow-[-6px_0_8px_-6px_rgba(0,0,0,0.12)]">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/20">
                        @forelse($visits as $v)
                        <tr
                            class="hover:bg-primary/5 transition-all city-filterable field-visit-row group"
                            data-city="{{ $v->company->city ?? '' }}"
                            data-status="{{ $v->status }}"
                            data-exec="{{ $v->executive->id ?? '' }}"
                            data-search="{{ strtolower(trim(implode(' ', array_filter([
                                'fv-'.$v->id,
                                '#fv-'.$v->id,
                                (string) $v->id,
                                $v->company->name ?? null,
                                $v->company->city ?? null,
                                $v->executive->name ?? null,
                                $v->notes ?? null,
                                $v->discount_note ?? null,
                            ])))) }}"
                        >
                            <td class="px-4 sm:px-6 py-4 font-data-tabular text-xs font-bold text-primary whitespace-nowrap">#FV-{{ $v->id }}</td>
                            <td class="px-4 sm:px-6 py-4">
                                <p class="text-sm font-bold text-primary">{{ $v->company->name ?? '—' }}</p>
                                <p class="text-[11px] text-on-surface-variant">{{ $v->company->city ?? '' }}</p>
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-sm whitespace-nowrap">{{ $v->executive->name ?? '—' }}</td>
                            <td class="px-4 sm:px-6 py-4 text-xs font-data-tabular whitespace-nowrap">
                                {{ $v->checked_in_at ? $v->checked_in_at->format('d M Y, h:i A') : '—' }}
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-xs font-data-tabular whitespace-nowrap">
                                {{ $v->checked_out_at ? $v->checked_out_at->format('d M Y, h:i A') : '—' }}
                            </td>
                            <td class="px-4 sm:px-6 py-4">
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase whitespace-nowrap {{ $v->status == 'checked_in' ? 'bg-secondary-fixed text-on-secondary-fixed-variant' : 'bg-green-100 text-green-800' }}">
                                    {{ $v->status == 'checked_in' ? 'In Progress' : 'Completed' }}
                                </span>
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-xs max-w-[140px]">
                                @if($v->discount_amount || $v->discount_note)
                                    @if($v->discount_amount)
                                        <p class="font-bold text-secondary font-data-tabular">₹{{ \App\Support\IndianNumber::format($v->discount_amount, 0) }}</p>
                                    @endif
                                    @if($v->discount_note)
                                        <p class="text-on-surface-variant truncate" title="{{ $v->discount_note }}">{{ \Illuminate\Support\Str::limit($v->discount_note, 40) }}</p>
                                    @endif
                                @else
                                    <span class="text-on-surface-variant">—</span>
                                @endif
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-xs text-on-surface-variant max-w-[160px]">
                                <span class="line-clamp-2" title="{{ $v->notes }}">{{ $v->notes ? \Illuminate\Support\Str::limit($v->notes, 50) : '—' }}</span>
                            </td>
                            <td class="px-3 sm:px-4 py-4 text-right sticky right-0 z-10 bg-white group-hover:bg-primary/5 min-w-[110px] shadow-[-6px_0_8px_-6px_rgba(0,0,0,0.12)]">
                                <a href="{{ route('admin.visits.show', $v->id) }}"
                                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-primary text-on-primary hover:bg-secondary transition-all whitespace-nowrap">
                                    <span class="material-symbols-outlined text-sm">visibility</span>
                                    Details
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr id="field-filter-empty-default">
                            <td colspan="9" class="py-8 text-center text-on-surface-variant/60 text-xs">No field meetings recorded yet.</td>
                        </tr>
                        @endforelse
                        <tr id="field-filter-empty" class="hidden">
                            <td colspan="9" class="py-8 text-center text-on-surface-variant/60 text-xs">No visits match your search or filters.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
