    <!-- ================= VIEW: TELECALLING ================= -->
    <div id="view-telecalling" class="space-y-xl">
        <section class="flex flex-col sm:flex-row sm:justify-between sm:items-end gap-4">
            <div class="min-w-0">
                <h2 class="font-headline-md text-xl sm:text-headline-md text-primary font-bold">Telecalling &amp; Inside Sales</h2>
                <p class="font-body-sm text-on-surface-variant">Log customer dial attempts and follow-up reminders.</p>
            </div>
            <button onclick="toggleModal('modal-add-call')" class="bg-primary text-on-primary hover:bg-secondary-container hover:text-on-secondary-container px-5 sm:px-6 py-3 rounded-lg font-title-sm flex items-center justify-center gap-2 shadow-md w-full sm:w-auto">
                <span class="material-symbols-outlined">call</span> Log Call Outcome
            </button>
        </section>

        <section class="bg-white border border-outline-variant/30 rounded-2xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-surface-container-low border-b border-outline-variant/30">
                        <tr>
                            <th class="px-6 py-4 font-label-caps text-label-caps text-on-surface-variant">Time</th>
                            <th class="px-6 py-4 font-label-caps text-label-caps text-on-surface-variant">Customer</th>
                            <th class="px-6 py-4 font-label-caps text-label-caps text-on-surface-variant">Telecaller</th>
                            <th class="px-6 py-4 font-label-caps text-label-caps text-on-surface-variant">Outcome</th>
                            <th class="px-6 py-4 font-label-caps text-label-caps text-on-surface-variant">Remarks / Response details</th>
                            <th class="px-6 py-4 font-label-caps text-label-caps text-on-surface-variant">Next Follow-up</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/20">
                        @forelse($allCalls as $call)
                        <tr class="hover:bg-primary/5 transition-all city-filterable" data-city="{{ $call->company->city ?? '' }}">
                            <td class="px-6 py-4 text-xs font-data-tabular text-primary js-local-datetime" data-iso="{{ $call->called_at?->toIso8601String() }}">{{ $call->called_at?->format('Y-m-d h:i A') }}</td>
                            <td class="px-6 py-4 text-sm font-bold text-primary">{{ $call->company->name }}</td>
                            <td class="px-6 py-4 text-sm text-on-surface-variant">{{ $call->telecaller ? $call->telecaller->name : 'Super Admin' }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ match($call->outcome) {
                                    'Interested', 'Profile Shared', 'Regular Party' => 'bg-green-100 text-green-800',
                                    'Follow-Up', 'Call Back', 'Callback', 'Ringing' => 'bg-orange-100 text-orange-800',
                                    'Not Interested', 'Not interested', 'No Requirement' => 'bg-red-100 text-red-800',
                                    default => 'bg-primary-fixed text-primary',
                                } }}">
                                    {{ $call->outcome }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-on-surface-variant leading-snug">{{ $call->remarks ?? 'No remarks added.' }}</td>
                            <td class="px-6 py-4 text-xs font-bold text-secondary font-data-tabular">{{ $call->next_followup ? $call->next_followup->format('Y-m-d') : 'None' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-on-surface-variant/60 text-xs">No calling records entered.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
