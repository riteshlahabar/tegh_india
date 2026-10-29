    <!-- ================= VIEW: LEADS ================= -->
    <div id="view-pipeline" class="space-y-xl">
        @php
            $pipelineTotalValue = $companies->sum('outstanding_amount');
            $pipelineActiveLeads = $companies->whereNotIn('stage', ['Won', 'Lost'])->count();
            $pipelineCities = $companies->pluck('city')->filter()->unique()->sort()->values();
            $pipelineIndustries = $companies->pluck('industry')->filter()->unique()->sort()->values();
            $stageBadgeClass = function (string $stage): string {
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
            $stageLabel = function (string $stage): string {
                return match ($stage) {
                    'Quotation Sent' => 'Quote Sent',
                    default => $stage,
                };
            };
        @endphp

        <section class="flex flex-col lg:flex-row lg:justify-between lg:items-end gap-4">
            <div>
                <h2 class="font-display-lg text-display-lg text-primary mb-1">Leads</h2>
                <div class="flex flex-wrap items-center gap-3">
                    <span class="flex items-center gap-1 text-on-surface-variant text-body-sm">
                        <span class="material-symbols-outlined text-[18px]">trending_up</span>
                        Total Value: <span class="font-data-tabular font-bold text-primary">₹{{ \App\Support\IndianNumber::format($pipelineTotalValue, 0) }}</span>
                    </span>
                    <span class="h-4 w-px bg-outline-variant"></span>
                    <span class="flex items-center gap-1 text-on-surface-variant text-body-sm">
                        <span class="material-symbols-outlined text-[18px]">assignment</span>
                        Active Leads: <span class="font-data-tabular font-bold text-primary">{{ $pipelineActiveLeads }}</span>
                    </span>
                </div>
            </div>
            <div class="flex gap-3">
                <button type="button" id="pipeline-filters-btn" class="bg-surface-container-high text-on-surface-variant px-4 py-2 rounded-lg flex items-center gap-2 border border-outline-variant font-bold hover:bg-surface-container-highest transition-all">
                    <span class="material-symbols-outlined">filter_list</span>
                    Filters
                </button>
                <button type="button" onclick="toggleModal('modal-add-customer')" class="bg-secondary text-on-secondary px-6 py-2 rounded-lg flex items-center gap-2 font-bold shadow-sm hover:shadow-md active:scale-95 transition-all">
                    <span class="material-symbols-outlined">add</span>
                    New Enquiry
                </button>
            </div>
        </section>

        <div class="flex flex-wrap items-center gap-3 sm:gap-4 bg-white p-3 sm:p-4 rounded-xl border border-outline-variant shadow-sm">
            <div class="relative flex-1 min-w-[100%] sm:min-w-[240px]">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
                <input id="pipeline-filter-search" class="w-full pl-10 pr-4 py-2.5 sm:py-2 bg-surface-container-low border-none rounded-lg text-body-sm focus:ring-2 focus:ring-secondary" placeholder="Search deals, customers..." type="text"/>
            </div>
            <div class="flex flex-wrap items-center gap-2 sm:gap-3 w-full sm:w-auto">
                <select id="pipeline-filter-stage" class="flex-1 sm:flex-none min-w-[130px] bg-surface-container-low border-none rounded-lg text-body-sm py-2.5 sm:py-2 px-3 sm:px-4 focus:ring-2 focus:ring-secondary">
                    <option value="">All Statuses</option>
                    <option value="New">New</option>
                    <option value="Follow-up">Follow-up</option>
                    <option value="Quotation Sent">Quote Sent</option>
                    <option value="Negotiation">Negotiation</option>
                    <option value="Won">Won</option>
                    <option value="Lost">Lost</option>
                </select>
                <select id="pipeline-filter-city" class="flex-1 sm:flex-none min-w-[130px] bg-surface-container-low border-none rounded-lg text-body-sm py-2.5 sm:py-2 px-3 sm:px-4 focus:ring-2 focus:ring-secondary">
                    <option value="">All Regions</option>
                    @foreach($pipelineCities as $city)
                        <option value="{{ $city }}">{{ $city }}</option>
                    @endforeach
                </select>
                <select id="pipeline-filter-industry" class="flex-1 sm:flex-none min-w-[130px] bg-surface-container-low border-none rounded-lg text-body-sm py-2.5 sm:py-2 px-3 sm:px-4 focus:ring-2 focus:ring-secondary">
                    <option value="">All Sectors</option>
                    @foreach($pipelineIndustries as $industry)
                        <option value="{{ $industry }}">{{ $industry }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-outline-variant shadow-sm">
            <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[1100px]">
                <thead>
                    <tr class="bg-surface-container-high border-b border-outline-variant">
                        <th class="px-6 py-4 font-label-caps text-label-caps text-on-surface-variant">Customer Name</th>
                        <th class="px-6 py-4 font-label-caps text-label-caps text-on-surface-variant">Stage</th>
                        <th class="px-6 py-4 font-label-caps text-label-caps text-on-surface-variant">Deal Value</th>
                        <th class="px-6 py-4 font-label-caps text-label-caps text-on-surface-variant">Last Activity</th>
                        <th class="px-6 py-4 font-label-caps text-label-caps text-on-surface-variant">Owner</th>
                        <th class="px-6 py-4 font-label-caps text-label-caps text-on-surface-variant">Added By</th>
                        <th class="px-4 py-4 font-label-caps text-label-caps text-on-surface-variant text-right sticky right-0 z-20 bg-surface-container-high min-w-[120px] shadow-[-6px_0_8px_-6px_rgba(0,0,0,0.12)]">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/30">
                    @forelse($companies as $lead)
                        @php
                            $ownerName = $lead->executive->name ?? 'Unassigned';
                        @endphp
                        <tr
                            class="pipeline-row city-filterable hover:bg-surface-container-low transition-colors group"
                            data-name="{{ strtolower($lead->name) }}"
                            data-stage="{{ $lead->stage }}"
                            data-city="{{ $lead->city }}"
                            data-industry="{{ $lead->industry }}"
                            data-owner="{{ strtolower($ownerName) }}"
                        >
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.customers.show', $lead->id) }}" class="font-title-sm text-primary hover:text-secondary hover:underline underline-offset-2">{{ $lead->name }}</a>
                                <div class="text-xs text-on-surface-variant">
                                    @if($lead->showsEnquiryProduct())
                                        {{ $lead->enquiryProductsLabel() }} ·
                                    @endif
                                    {{ $lead->industry ?? 'Other' }} • {{ $lead->city ?? 'N/A' }}@if($lead->sub_city) / {{ $lead->sub_city }}@endif
                                    @if($lead->contact_person_name)
                                        · {{ $lead->contact_person_name }}
                                    @endif
                                    @if($lead->mobile_number)
                                        · {{ $lead->mobile_number }}
                                    @endif
                                </div>
                                @if($lead->house_wire_discount || $lead->flexible_wire_discount || $lead->other_wire_discount || $lead->house_wire_discount_note || $lead->flexible_wire_discount_note || $lead->other_wire_discount_note)
                                    <div class="mt-1.5 space-y-1">
                                        @if($lead->house_wire_discount || $lead->house_wire_discount_note)
                                            <div class="text-[11px]">
                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-amber-100 text-amber-800">House Wire</span>
                                                @if($lead->house_wire_discount_note)
                                                    <span class="text-on-surface-variant ml-1">{{ \Illuminate\Support\Str::limit($lead->house_wire_discount_note, 60) }}</span>
                                                @endif
                                            </div>
                                        @endif
                                        @if($lead->flexible_wire_discount || $lead->flexible_wire_discount_note)
                                            <div class="text-[11px]">
                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-amber-100 text-amber-800">Flexible Wire</span>
                                                @if($lead->flexible_wire_discount_note)
                                                    <span class="text-on-surface-variant ml-1">{{ \Illuminate\Support\Str::limit($lead->flexible_wire_discount_note, 60) }}</span>
                                                @endif
                                            </div>
                                        @endif
                                        @if($lead->other_wire_discount || $lead->other_wire_discount_note)
                                            <div class="text-[11px]">
                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-amber-100 text-amber-800">Other Wire</span>
                                                @if($lead->other_wire_discount_note)
                                                    <span class="text-on-surface-variant ml-1">{{ \Illuminate\Support\Str::limit($lead->other_wire_discount_note, 60) }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @endif
                                @if($lead->enquiry_admin_note)
                                    <p class="text-[11px] text-on-surface-variant mt-1 max-w-xs" title="{{ $lead->enquiry_admin_note }}">Note: {{ \Illuminate\Support\Str::limit($lead->enquiry_admin_note, 80) }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-2 min-w-[140px]">
                                    <span class="px-2 py-1 rounded w-fit text-[10px] font-bold uppercase {{ $stageBadgeClass($lead->stage) }}">{{ $stageLabel($lead->stage) }}</span>
                                    <form action="{{ route('admin.companies.stage', $lead->id) }}" method="POST">
                                        @csrf
                                        <select onchange="this.form.submit()" name="stage" class="w-full text-[11px] font-semibold bg-surface-container-low border border-outline-variant/40 rounded px-2 py-1 text-primary focus:ring-1 focus:ring-primary outline-none cursor-pointer">
                                            <option value="New" {{ $lead->stage == 'New' ? 'selected' : '' }}>New</option>
                                            <option value="Follow-up" {{ $lead->stage == 'Follow-up' ? 'selected' : '' }}>Follow-up</option>
                                            <option value="Quotation Sent" {{ $lead->stage == 'Quotation Sent' ? 'selected' : '' }}>Quote Sent</option>
                                            <option value="Negotiation" {{ $lead->stage == 'Negotiation' ? 'selected' : '' }}>Negotiating</option>
                                            <option value="Won" {{ $lead->stage == 'Won' ? 'selected' : '' }}>Won</option>
                                            <option value="Lost" {{ $lead->stage == 'Lost' ? 'selected' : '' }}>Lost</option>
                                        </select>
                                    </form>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-data-tabular text-primary font-bold">₹{{ \App\Support\IndianNumber::format($lead->outstanding_amount, 0) }}</td>
                            <td class="px-6 py-4 text-body-sm text-on-surface-variant">{{ $lead->updated_at?->diffForHumans() ?? '—' }}</td>
                            <td class="px-6 py-4 text-body-sm">{{ $ownerName }}</td>
                            <td class="px-6 py-4 text-body-sm">
                                @php $originType = $lead->leadOriginType(); @endphp
                                @if($originType === 'admin_assign')
                                    <p class="font-medium text-primary">Admin Assign</p>
                                    <p class="text-xs text-on-surface-variant">{{ $lead->executive->name ?? 'Unassigned' }}</p>
                                @elseif($originType === 'self')
                                    <p class="font-medium text-primary">Self</p>
                                    <p class="text-xs text-on-surface-variant">{{ $lead->creator->name ?? '—' }}</p>
                                @else
                                    <p class="font-medium text-primary">{{ $lead->creator->name ?? '—' }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-right sticky right-0 z-10 bg-white group-hover:bg-surface-container-low min-w-[120px] shadow-[-6px_0_8px_-6px_rgba(0,0,0,0.12)]">
                                <button
                                    type="button"
                                    title="Assign salesperson"
                                    onclick="openAssignModal({{ $lead->id }}, @js($lead->name), {{ $lead->assigned_exec_id ?? 'null' }}, {{ $lead->assigned_tc_id ?? 'null' }})"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-primary text-on-primary hover:bg-secondary transition-all whitespace-nowrap"
                                >
                                    <span class="material-symbols-outlined text-sm">person_add</span>
                                    Assign
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr id="pipeline-empty-data">
                            <td colspan="7" class="px-6 py-10 text-center text-on-surface-variant text-body-sm">No leads yet.</td>
                        </tr>
                    @endforelse
                    <tr id="pipeline-filter-empty" class="hidden">
                        <td colspan="7" class="px-6 py-10 text-center text-on-surface-variant text-body-sm">No leads match your filters.</td>
                    </tr>
                </tbody>
            </table>
            </div>
        </div>
    </div>
