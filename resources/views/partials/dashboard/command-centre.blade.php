    <!-- ================= VIEW: COMMAND CENTRE ================= -->
    <div id="view-dashboard" class="space-y-xl">
        <!-- Header Section -->
        <section class="flex flex-col sm:flex-row sm:justify-between sm:items-end gap-4">
            <div class="space-y-1 min-w-0">
                <h2 class="font-display-lg text-2xl sm:text-3xl lg:text-[40px] leading-tight text-primary font-bold">Command Centre</h2>
                <div class="flex items-center gap-sm">
                    <span class="inline-block w-2 h-2 rounded-full bg-secondary animate-pulse"></span>
                    <p class="font-body-sm sm:font-body-md text-on-surface-variant font-medium">Live performance dashboard • Real-time visibility</p>
                </div>
            </div>
            <button onclick="toggleModal('modal-add-customer')" class="bg-secondary-container text-on-secondary-container hover:bg-secondary px-5 sm:px-8 py-3 sm:py-4 rounded-xl font-title-sm flex items-center justify-center gap-md shadow-lg shadow-secondary-container/20 transition-all hover:-translate-y-0.5 active:scale-95 group w-full sm:w-auto">
                <span class="material-symbols-outlined font-bold group-hover:rotate-90 transition-transform">add</span>
                New Sales Enquiry
            </button>
        </section>
        
        <!-- KPI Grid -->
        <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-lg">
            <div class="bg-white p-xl border border-outline-variant/30 rounded-2xl shadow-sm hover:shadow-md transition-all group">
                <div class="flex justify-between items-start mb-md">
                    <span class="font-label-caps text-label-caps text-on-surface-variant font-bold uppercase tracking-widest">Today's Enquiries</span>
                    <div class="p-2 bg-primary-fixed text-primary rounded-lg">
                        <span class="material-symbols-outlined text-lg">inbox</span>
                    </div>
                </div>
                <div class="flex items-baseline gap-sm mb-lg">
                    <span class="font-display-lg text-[48px] text-primary font-bold">{{ sprintf('%02d', $todayEnquiries) }}</span>
                    <span class="font-body-sm text-on-surface-variant/70">Units today</span>
                </div>
                <div class="h-2 w-full bg-surface-container-low rounded-full overflow-hidden">
                    <div class="h-full bg-primary w-[40%] rounded-full"></div>
                </div>
                <p class="mt-md text-[11px] font-medium text-on-surface-variant/60 flex items-center gap-1">
                    <span class="material-symbols-outlined text-xs text-green-600">trending_up</span>
                    Updated in real time
                </p>
            </div>
            
            <div class="bg-white p-xl border border-outline-variant/30 rounded-2xl shadow-sm hover:shadow-md transition-all">
                <div class="flex justify-between items-start mb-md">
                    <span class="font-label-caps text-label-caps text-on-surface-variant font-bold uppercase tracking-widest">Quotations Sent</span>
                    <div class="p-2 bg-secondary-fixed text-secondary rounded-lg">
                        <span class="material-symbols-outlined text-lg">description</span>
                    </div>
                </div>
                <div class="flex items-baseline gap-sm mb-lg">
                    <span class="font-display-lg text-[48px] text-primary font-bold">{{ sprintf('%02d', $quotationsSent) }}</span>
                    <span class="font-label-caps text-sm font-bold text-secondary bg-secondary-fixed/50 px-2 py-0.5 rounded">Active</span>
                </div>
                <div class="h-2 w-full bg-surface-container-low rounded-full overflow-hidden">
                    <div class="h-full bg-secondary w-[60%] rounded-full"></div>
                </div>
                <p class="mt-md text-[11px] font-medium text-on-surface-variant/60">Across all active branches</p>
            </div>
            
            <div class="bg-white p-xl border border-outline-variant/30 rounded-2xl shadow-sm hover:shadow-md transition-all">
                <div class="flex justify-between items-start mb-md">
                    <span class="font-label-caps text-label-caps text-on-surface-variant font-bold uppercase tracking-widest">Orders Won (MTD)</span>
                    <div class="p-2 bg-tertiary-fixed text-primary rounded-lg">
                        <span class="material-symbols-outlined text-lg">workspace_premium</span>
                    </div>
                </div>
                <div class="flex items-baseline gap-sm mb-lg">
                    <span class="font-display-lg text-[48px] text-primary font-bold">{{ sprintf('%02d', $ordersWon) }}</span>
                    <span class="font-body-sm text-on-surface-variant/70">Target: 12</span>
                </div>
                <div class="h-2 w-full bg-surface-container-low rounded-full overflow-hidden">
                    <div class="h-full bg-primary w-[16%] rounded-full"></div>
                </div>
                <p class="mt-md text-[11px] font-medium text-on-surface-variant/60">Goal: ₹ 25L revenue</p>
            </div>
            
            <div class="bg-white p-xl border border-outline-variant/30 rounded-2xl shadow-sm hover:shadow-md transition-all">
                <div class="flex justify-between items-start mb-md">
                    <span class="font-label-caps text-label-caps text-on-surface-variant font-bold uppercase tracking-widest">Critical Follow-ups</span>
                    <div class="p-2 bg-error-container text-error rounded-lg">
                        <span class="material-symbols-outlined text-lg">event_busy</span>
                    </div>
                </div>
                <div class="flex items-baseline gap-sm mb-lg">
                    <span class="font-display-lg text-[48px] text-error font-bold">{{ sprintf('%02d', $criticalFollowups) }}</span>
                    <span class="font-body-sm text-on-surface-variant/70">Pending action</span>
                </div>
                <div class="h-2 w-full bg-surface-container-low rounded-full overflow-hidden">
                    <div class="h-full bg-error w-[80%] rounded-full"></div>
                </div>
                <p class="mt-md text-[11px] font-medium text-error font-semibold flex items-center gap-1 uppercase">
                    <span class="material-symbols-outlined text-xs">warning</span>
                    Immediate attention required
                </p>
            </div>
        </section>
        
        <!-- Branch and Team Section -->
        <section class="grid grid-cols-1 lg:grid-cols-12 gap-xl">
            <!-- Branch-wise Sales -->
            <div class="lg:col-span-7 bg-white border border-outline-variant/30 rounded-2xl p-xl shadow-sm">
                <div class="flex justify-between items-center mb-xl">
                    <div>
                        <h3 class="font-label-caps text-label-caps text-primary font-bold uppercase tracking-wider">Branch Performance</h3>
                        <p class="text-xs text-on-surface-variant mt-1">Real-time target vs achievement metrics</p>
                    </div>
                </div>
                <div class="space-y-lg">
                    @foreach($citySales as $cs)
                    <div class="group cursor-default branch-row city-filterable" data-branch="{{ $cs['name'] }}" data-city="{{ $cs['name'] }}">
                        <div class="flex justify-between items-center mb-base">
                            <span class="font-title-sm text-title-sm text-primary font-semibold">{{ $cs['name'] }} Branch</span>
                            <div class="text-right">
                                <span class="font-data-tabular text-lg text-primary font-bold">₹ {{ \App\Support\IndianNumber::format($cs['sales'], 0) }}</span>
                                <span class="text-[10px] block text-on-surface-variant">of ₹ {{ \App\Support\IndianNumber::format($cs['target'], 0) }}</span>
                            </div>
                        </div>
                        <div class="h-4 w-full bg-surface-container-low rounded-full overflow-hidden p-0.5">
                            <div class="h-full bg-secondary-container rounded-full shadow-sm transition-all duration-1000 group-hover:brightness-110" style="width: {{ min(100, max(5, ($cs['sales'] / $cs['target']) * 100)) }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            
            <!-- Team Visibility -->
            <div class="lg:col-span-5 bg-white border border-outline-variant/30 rounded-2xl p-xl shadow-sm">
                <div class="flex justify-between items-center mb-xl">
                    <div>
                        <h3 class="font-label-caps text-label-caps text-primary font-bold uppercase tracking-wider">Top Executives</h3>
                        <p class="text-xs text-on-surface-variant mt-1">Activity leaderboards</p>
                    </div>
                </div>
                <table class="w-full">
                    <thead>
                        <tr class="text-left border-b border-surface-container-high">
                            <th class="pb-md font-label-caps text-[10px] text-on-surface-variant font-bold tracking-widest uppercase">EXECUTIVE</th>
                            <th class="pb-md font-label-caps text-[10px] text-on-surface-variant font-bold tracking-widest uppercase text-center">DEALS</th>
                            <th class="pb-md font-label-caps text-[10px] text-on-surface-variant font-bold tracking-widest uppercase text-right">VALUE</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container-high/50">
                        @forelse($executivesList as $exec)
                        <tr class="hover:bg-surface-container-low transition-colors cursor-pointer group table-row-hover">
                            <td class="py-md font-body-sm text-primary font-semibold group-hover:text-secondary">{{ $exec['name'] }}</td>
                            <td class="py-md font-data-tabular text-data-tabular text-center">{{ sprintf('%02d', $exec['deals']) }}</td>
                            <td class="py-md font-data-tabular text-data-tabular text-right font-bold">₹ {{ \App\Support\IndianNumber::format($exec['revenue'], 0) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="py-4 text-center text-on-surface-variant/60 text-xs">No executive leaderboard stats recorded.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        
        <!-- Recent Activity Feed -->
        <section class="bg-white border border-outline-variant/30 rounded-2xl p-xl shadow-sm">
            <div class="flex justify-between items-center mb-xl">
                <div>
                    <h3 class="font-label-caps text-label-caps text-primary font-bold uppercase tracking-wider">Live Activity Feed</h3>
                    <p class="text-xs text-on-surface-variant mt-1">Latest updates from across India</p>
                </div>
                <span class="px-3 py-1 bg-green-50 text-green-700 text-[10px] font-bold rounded-full border border-green-100 uppercase tracking-tighter">Live</span>
            </div>
            <div class="space-y-0 relative before:absolute before:left-3 before:top-2 before:bottom-2 before:w-px before:bg-surface-container-high">
                @forelse($activityLog as $log)
                <div class="flex items-start gap-xl py-md group relative">
                    <div class="mt-1 w-6 h-6 rounded-full bg-secondary-fixed border-4 border-white shadow-sm flex items-center justify-center z-10 transition-transform group-hover:scale-125">
                        <span class="w-1.5 h-1.5 bg-secondary rounded-full"></span>
                    </div>
                    <div class="flex-1 pb-md border-b border-surface-container-low group-last:border-none">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-body-md text-on-surface leading-snug">
                                    <span class="font-bold text-primary">{{ $log['user'] }}</span> executed 
                                    <span class="text-secondary font-semibold">{{ $log['action'] }}</span> — {{ $log['detail'] }}
                                </p>
                            </div>
                            <span class="font-data-tabular text-xs text-on-surface-variant bg-surface px-2 py-1 rounded">{{ $log['time'] }}</span>
                        </div>
                    </div>
                </div>
                @empty
                <p class="text-on-surface-variant/60 text-xs py-4 text-center">No system logs registered today.</p>
                @endforelse
            </div>
        </section>
    </div>
