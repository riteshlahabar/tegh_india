@extends('layouts.telecaller')

@section('content')
<div class="space-y-6 max-w-6xl">
    <section class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl sm:text-3xl font-bold text-primary tracking-tight">Customers</h2>
            <p class="text-sm text-on-surface-variant">Search {{ $branch ?: 'your branch' }} directory and add accounts to your calling queue.</p>
        </div>
        <form method="GET" action="{{ route('telecaller.customers') }}" class="flex gap-2 w-full sm:w-auto">
            <input type="search" name="q" value="{{ $q }}" placeholder="Search name, city, mobile..."
                   class="rounded-lg border-outline-variant/50 text-sm w-full sm:min-w-[320px] focus:ring-secondary focus:border-secondary">
            <button class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-bold shrink-0">Search</button>
        </form>
    </section>

    <section class="bg-white border border-outline-variant/30 rounded-2xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left min-w-[980px]">
                <thead class="bg-surface-container-low border-b border-outline-variant/30">
                    <tr>
                        <th class="px-5 py-3 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Customer</th>
                        <th class="px-5 py-3 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">City</th>
                        <th class="px-5 py-3 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Mobile</th>
                        <th class="px-5 py-3 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Staff</th>
                        <th class="px-5 py-3 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Stage</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    @forelse($customers as $c)
                        <tr class="hover:bg-primary/5">
                            <td class="px-5 py-4">
                                <p class="font-bold text-primary">{{ $c['name'] ?? '—' }}</p>
                                <p class="text-xs text-on-surface-variant">{{ $c['contact_person_name'] ?? '' }}</p>
                            </td>
                            <td class="px-5 py-4 text-sm text-on-surface-variant">
                                {{ $c['city'] ?? '—' }}{{ !empty($c['sub_city']) ? ' · '.$c['sub_city'] : '' }}
                            </td>
                            <td class="px-5 py-4 text-sm font-data-tabular text-primary">{{ $c['mobile_number'] ?? '—' }}</td>
                            <td class="px-5 py-4">
                                <p class="text-sm font-semibold text-primary">{{ $c['executive_name'] ?? 'Unassigned' }}</p>
                                <p class="text-xs text-on-surface-variant">{{ !empty($c['telecaller_name']) ? 'TC: '.$c['telecaller_name'] : 'TC: —' }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded bg-surface-container-low text-on-surface-variant">
                                    {{ $c['stage'] ?? '—' }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        type="button"
                                        onclick="openAssignModal({{ (int) ($c['id'] ?? 0) }}, @js($c['name'] ?? ''), {{ $c['assigned_exec_id'] ?? 'null' }}, {{ $c['assigned_tc_id'] ?? 'null' }})"
                                        class="inline-flex items-center justify-center bg-primary text-white text-xs font-bold px-3 py-2 rounded-lg hover:bg-secondary transition-all"
                                    >
                                        Assign
                                    </button>
                                    <form method="POST" action="{{ route('telecaller.leads.claim', (int) ($c['id'] ?? 0)) }}">
                                        @csrf
                                        <button class="inline-flex items-center gap-1 bg-secondary text-white text-xs font-bold px-3 py-2 rounded-lg">
                                            Add to queue
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-sm text-on-surface-variant">No customers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<div id="modal-assign-lead" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center hidden">
    <div class="bg-white border border-outline-variant/30 rounded-2xl w-full max-w-md p-xl space-y-md shadow-2xl relative mx-4">
        <button type="button" onclick="toggleAssignModal()" class="absolute top-4 right-4 text-on-surface-variant hover:text-primary">
            <span class="material-symbols-outlined">close</span>
        </button>
        <h3 class="font-headline-md text-primary font-bold">Assign Lead</h3>
        <p id="assign-lead-name" class="text-xs text-on-surface-variant -mt-2"></p>
        <form id="form-assign-lead" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-on-surface-variant mb-1">Assign Salesperson</label>
                <select id="assign-exec-select" name="assigned_exec_id" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
                    <option value="">Unassigned</option>
                    @foreach($salesExecutives as $exec)
                        <option value="{{ $exec->id }}">{{ $exec->name }} ({{ $exec->branch }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-on-surface-variant mb-1">Assign Telecaller</label>
                <select id="assign-tc-select" name="assigned_tc_id" class="w-full border-outline-variant/50 rounded-lg text-sm focus:ring-primary focus:border-primary">
                    <option value="">Unassigned</option>
                    @foreach($telecallers as $tc)
                        <option value="{{ $tc->id }}">{{ $tc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="pt-4">
                <button type="submit" class="w-full bg-primary text-on-primary py-3 rounded-lg font-bold shadow-md hover:bg-secondary transition-all">SAVE ASSIGNMENT</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleAssignModal() {
        const modal = document.getElementById('modal-assign-lead');
        if (!modal) return;
        modal.classList.toggle('hidden');
        document.body.classList.toggle('overflow-hidden', !modal.classList.contains('hidden'));
    }

    function openAssignModal(companyId, companyName, execId, tcId) {
        const form = document.getElementById('form-assign-lead');
        const nameEl = document.getElementById('assign-lead-name');
        const execSelect = document.getElementById('assign-exec-select');
        const tcSelect = document.getElementById('assign-tc-select');
        if (!form || !nameEl || !execSelect || !tcSelect) return;

        form.action = `/telecaller/customers/${companyId}/assign`;
        nameEl.textContent = companyName || '';
        execSelect.value = execId != null ? String(execId) : '';
        tcSelect.value = tcId != null ? String(tcId) : '';
        const modal = document.getElementById('modal-assign-lead');
        if (modal && modal.classList.contains('hidden')) {
            toggleAssignModal();
        }
    }
</script>
@endsection
