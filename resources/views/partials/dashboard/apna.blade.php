<div id="view-apna" class="space-y-xl">
    <section class="flex flex-col lg:flex-row lg:justify-between lg:items-end gap-4">
        <div>
            <h2 class="font-headline-md text-headline-md text-primary font-bold">Apna Bazar</h2>
            <p class="text-on-surface-variant font-body-sm">
                Accounts, items, outstanding, sales vouchers, receipts, and pending orders from the Apna Bazar API.
                @if($lastSync)
                    Last pull {{ $lastSync->created_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}.
                @endif
            </p>
        </div>
        <form method="POST" action="{{ route('admin.apna.sync') }}">
            @csrf
            <button type="submit" class="bg-primary text-on-primary hover:bg-secondary px-6 py-3 rounded-lg font-title-sm flex items-center justify-center gap-2 shadow-md w-full sm:w-auto">
                <span class="material-symbols-outlined">cloud_download</span>
                Pull latest data
            </button>
        </form>
    </section>

    <section class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
        @foreach($tabs as $key => $label)
            <a href="{{ route('admin.apna', ['tab' => $key]) }}" class="rounded-2xl border px-4 py-3 {{ $tab === $key ? 'bg-primary text-on-primary border-primary' : 'bg-white border-outline-variant/30 text-primary hover:bg-surface-container-low' }}">
                <p class="text-[11px] uppercase tracking-wide opacity-70">{{ $label }}</p>
                <p class="text-2xl font-bold mt-1">{{ number_format($counts[$key] ?? 0) }}</p>
            </a>
        @endforeach
    </section>

    <section class="bg-white border border-outline-variant/30 rounded-2xl overflow-hidden shadow-sm">
        <div class="p-5 border-b border-outline-variant/30 bg-surface-container-lowest">
            <form method="GET" action="{{ route('admin.apna') }}" class="flex flex-col sm:flex-row gap-3">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="relative flex-1">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg">search</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Search {{ strtolower($tabs[$tab]) }}…" class="w-full pl-10 pr-4 py-2.5 bg-white border border-outline-variant/50 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none">
                </div>
                <button type="submit" class="px-5 py-2.5 rounded-lg text-sm font-bold bg-primary text-on-primary hover:bg-secondary">Search</button>
                @if($search !== '')
                    <a href="{{ route('admin.apna', ['tab' => $tab]) }}" class="px-4 py-2.5 rounded-lg text-sm font-bold border border-outline-variant/50 text-on-surface-variant text-center">Clear</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            @if($tab === 'accounts')
                <table class="w-full text-left min-w-[980px]">
                    <thead class="bg-surface-container-low border-b border-outline-variant/30">
                        <tr>
                            <th class="px-4 py-3 text-xs font-bold text-on-surface-variant">ERP</th>
                            <th class="px-4 py-3 text-xs font-bold text-on-surface-variant">Name</th>
                            <th class="px-4 py-3 text-xs font-bold text-on-surface-variant">Group</th>
                            <th class="px-4 py-3 text-xs font-bold text-on-surface-variant">Mobile</th>
                            <th class="px-4 py-3 text-xs font-bold text-on-surface-variant">GST</th>
                            <th class="px-4 py-3 text-xs font-bold text-on-surface-variant">State</th>
                            <th class="px-4 py-3 text-xs font-bold text-on-surface-variant text-right">Closing</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/20">
                        @forelse($rows as $row)
                            <tr class="hover:bg-primary/5">
                                <td class="px-4 py-3 text-xs">{{ $row->erp_code }}</td>
                                <td class="px-4 py-3 text-sm font-semibold text-primary">
                                    {{ $row->name }}
                                    @if($row->address)
                                        <p class="text-[11px] font-normal text-on-surface-variant">{{ $row->address }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs">{{ $row->parent_group ?: '—' }}</td>
                                <td class="px-4 py-3 text-xs">{{ $row->mobile ?: '—' }}</td>
                                <td class="px-4 py-3 text-xs">{{ $row->gst_number ?: '—' }}</td>
                                <td class="px-4 py-3 text-xs">{{ $row->state ?: '—' }}</td>
                                <td class="px-4 py-3 text-xs text-right">{{ number_format((float) $row->closing_balance, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-10 text-center text-sm text-on-surface-variant">No accounts yet. Pull latest data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @elseif($tab === 'items')
                <table class="w-full text-left min-w-[900px]">
                    <thead class="bg-surface-container-low border-b border-outline-variant/30">
                        <tr>
                            <th class="px-4 py-3 text-xs font-bold text-on-surface-variant">ERP</th>
                            <th class="px-4 py-3 text-xs font-bold text-on-surface-variant">Item</th>
                            <th class="px-4 py-3 text-xs font-bold text-on-surface-variant">Alias</th>
                            <th class="px-4 py-3 text-xs font-bold text-on-surface-variant">Group</th>
                            <th class="px-4 py-3 text-xs font-bold text-on-surface-variant">Unit</th>
                            <th class="px-4 py-3 text-xs font-bold text-on-surface-variant text-right">Sale price</th>
                            <th class="px-4 py-3 text-xs font-bold text-on-surface-variant text-right">Stock</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/20">
                        @forelse($rows as $row)
                            <tr class="hover:bg-primary/5">
                                <td class="px-4 py-3 text-xs">{{ $row->erp_code }}</td>
                                <td class="px-4 py-3 text-sm font-semibold text-primary">{{ $row->name }}</td>
                                <td class="px-4 py-3 text-xs">{{ $row->alias ?: '—' }}</td>
                                <td class="px-4 py-3 text-xs">{{ $row->parent_group ?: '—' }}</td>
                                <td class="px-4 py-3 text-xs">{{ $row->unit ?: '—' }}</td>
                                <td class="px-4 py-3 text-xs text-right">{{ number_format((float) $row->sale_price, 2) }}</td>
                                <td class="px-4 py-3 text-xs text-right">{{ number_format((float) $row->closing_stock, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-10 text-center text-sm text-on-surface-variant">No items yet. Pull latest data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @elseif($tab === 'outstanding')
                <table class="w-full text-left min-w-[860px]">
                    <thead class="bg-surface-container-low border-b border-outline-variant/30">
                        <tr>
                            <th class="px-4 py-3 text-xs font-bold text-on-surface-variant">Account</th>
                            <th class="px-4 py-3 text-xs font-bold text-on-surface-variant">Invoice</th>
                            <th class="px-4 py-3 text-xs font-bold text-on-surface-variant">Date</th>
                            <th class="px-4 py-3 text-xs font-bold text-on-surface-variant">Due</th>
                            <th class="px-4 py-3 text-xs font-bold text-on-surface-variant text-right">Amount</th>
                            <th class="px-4 py-3 text-xs font-bold text-on-surface-variant text-right">Outstanding</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/20">
                        @forelse($rows as $row)
                            <tr class="hover:bg-primary/5">
                                <td class="px-4 py-3 text-sm font-semibold text-primary">{{ $row->account_name ?: '—' }}</td>
                                <td class="px-4 py-3 text-xs">{{ $row->invoice_no ?: '—' }}</td>
                                <td class="px-4 py-3 text-xs">{{ $row->invoice_date?->format('d M Y') ?: '—' }}</td>
                                <td class="px-4 py-3 text-xs">{{ $row->due_date?->format('d M Y') ?: '—' }}</td>
                                <td class="px-4 py-3 text-xs text-right">{{ number_format((float) $row->invoice_amount, 2) }}</td>
                                <td class="px-4 py-3 text-xs text-right font-semibold">{{ number_format((float) $row->outstanding_amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-10 text-center text-sm text-on-surface-variant">No outstanding bills yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @elseif($tab === 'vouchers')
                <table class="w-full text-left min-w-[860px]">
                    <thead class="bg-surface-container-low border-b border-outline-variant/30">
                        <tr>
                            <th class="px-4 py-3 text-xs font-bold text-on-surface-variant">Kind</th>
                            <th class="px-4 py-3 text-xs font-bold text-on-surface-variant">Voucher</th>
                            <th class="px-4 py-3 text-xs font-bold text-on-surface-variant">Type</th>
                            <th class="px-4 py-3 text-xs font-bold text-on-surface-variant">Date</th>
                            <th class="px-4 py-3 text-xs font-bold text-on-surface-variant">Status</th>
                            <th class="px-4 py-3 text-xs font-bold text-on-surface-variant text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/20">
                        @forelse($rows as $row)
                            <tr class="hover:bg-primary/5">
                                <td class="px-4 py-3 text-xs capitalize">{{ $row->kind }}</td>
                                <td class="px-4 py-3 text-sm font-semibold text-primary">{{ $row->voucher_no ?: $row->voucher_id }}</td>
                                <td class="px-4 py-3 text-xs">{{ $row->voucher_type ?: '—' }}</td>
                                <td class="px-4 py-3 text-xs">{{ $row->tran_date?->format('d M Y') ?: '—' }}</td>
                                <td class="px-4 py-3 text-xs">{{ $row->status ?: '—' }}</td>
                                <td class="px-4 py-3 text-xs text-right">{{ number_format((float) $row->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-10 text-center text-sm text-on-surface-variant">No vouchers yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @elseif($tab === 'orders')
                <table class="w-full text-left min-w-[860px]">
                    <thead class="bg-surface-container-low border-b border-outline-variant/30">
                        <tr>
                            <th class="px-4 py-3 text-xs font-bold text-on-surface-variant">Item</th>
                            <th class="px-4 py-3 text-xs font-bold text-on-surface-variant">Account</th>
                            <th class="px-4 py-3 text-xs font-bold text-on-surface-variant">Ref</th>
                            <th class="px-4 py-3 text-xs font-bold text-on-surface-variant">Date</th>
                            <th class="px-4 py-3 text-xs font-bold text-on-surface-variant text-right">Ordered</th>
                            <th class="px-4 py-3 text-xs font-bold text-on-surface-variant text-right">Pending</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/20">
                        @forelse($rows as $row)
                            <tr class="hover:bg-primary/5">
                                <td class="px-4 py-3 text-sm font-semibold text-primary">{{ $row->item ?: '—' }}</td>
                                <td class="px-4 py-3 text-xs">{{ $row->account_name ?: '—' }}</td>
                                <td class="px-4 py-3 text-xs">{{ $row->order_ref ?: '—' }}</td>
                                <td class="px-4 py-3 text-xs">{{ $row->order_date?->format('d M Y') ?: '—' }}</td>
                                <td class="px-4 py-3 text-xs text-right">{{ number_format((float) $row->order_qty, 2) }}</td>
                                <td class="px-4 py-3 text-xs text-right font-semibold">{{ number_format((float) $row->pending_qty, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-10 text-center text-sm text-on-surface-variant">No pending orders yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @else
                <table class="w-full text-left min-w-[720px]">
                    <thead class="bg-surface-container-low border-b border-outline-variant/30">
                        <tr>
                            <th class="px-4 py-3 text-xs font-bold text-on-surface-variant">Kind</th>
                            <th class="px-4 py-3 text-xs font-bold text-on-surface-variant">ERP</th>
                            <th class="px-4 py-3 text-xs font-bold text-on-surface-variant">Name</th>
                            <th class="px-4 py-3 text-xs font-bold text-on-surface-variant">Parent</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/20">
                        @forelse($rows as $row)
                            <tr class="hover:bg-primary/5">
                                <td class="px-4 py-3 text-xs capitalize">{{ $row->kind }}</td>
                                <td class="px-4 py-3 text-xs">{{ $row->erp_code }}</td>
                                <td class="px-4 py-3 text-sm font-semibold text-primary">{{ $row->name }}</td>
                                <td class="px-4 py-3 text-xs">{{ $row->parent_name ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-10 text-center text-sm text-on-surface-variant">No groups yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>

        @if($rows->hasPages())
            <div class="px-5 py-4 border-t border-outline-variant/30 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-sm">
                <p class="text-on-surface-variant">Showing {{ $rows->firstItem() }}–{{ $rows->lastItem() }} of {{ $rows->total() }}</p>
                <div class="flex gap-2">
                    @if($rows->onFirstPage())
                        <span class="px-3 py-2 rounded-lg border border-outline-variant/40 text-on-surface-variant/50">Previous</span>
                    @else
                        <a href="{{ $rows->previousPageUrl() }}" class="px-3 py-2 rounded-lg border border-outline-variant/50 hover:bg-surface-container-low">Previous</a>
                    @endif
                    @if($rows->hasMorePages())
                        <a href="{{ $rows->nextPageUrl() }}" class="px-3 py-2 rounded-lg border border-outline-variant/50 hover:bg-surface-container-low">Next</a>
                    @else
                        <span class="px-3 py-2 rounded-lg border border-outline-variant/40 text-on-surface-variant/50">Next</span>
                    @endif
                </div>
            </div>
        @endif
    </section>

    <section class="bg-white border border-outline-variant/30 rounded-2xl p-5 shadow-sm">
        <h3 class="text-xs font-bold tracking-wide text-primary mb-3">CONNECTED READ ENDPOINTS</h3>
        <ul class="grid sm:grid-cols-2 gap-2 text-sm text-on-surface-variant">
            @foreach($endpoints as $endpoint)
                <li class="flex items-center justify-between gap-3 rounded-lg bg-surface-container-low px-3 py-2">
                    <span>{{ $endpoint['name'] }}</span>
                    <code class="text-[11px] text-primary">{{ $endpoint['path'] }}</code>
                </li>
            @endforeach
        </ul>
    </section>
</div>
