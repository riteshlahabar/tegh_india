<div class="bg-white border border-outline-variant/30 p-md rounded-xl shadow-sm space-y-sm hover:shadow transition-shadow group relative">
    <div class="space-y-0.5">
        <h4 class="text-sm font-bold text-primary">{{ $lead->name }}</h4>
        <p class="text-[11px] text-on-surface-variant font-medium">{{ $lead->industry ?? 'Other' }} • {{ $lead->city ?? 'N/A' }}</p>
    </div>
    <div class="flex justify-between items-center text-xs pt-1 border-t border-surface-container-high/50">
        <span class="text-on-surface-variant">Outstanding:</span>
        <span class="font-bold text-secondary font-mono">₹{{ \App\Support\IndianNumber::format($lead->outstanding_amount, 0) }}</span>
    </div>
    <p class="text-[10px] text-on-surface-variant">
        Sales: {{ $lead->executive->name ?? 'Unassigned' }}
    </p>

    <!-- Stage selector form -->
    <form action="{{ route('admin.companies.stage', $lead->id) }}" method="POST" class="mt-2">
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
    <button
        type="button"
        onclick="openAssignModal({{ $lead->id }}, @js($lead->name), {{ $lead->assigned_exec_id ?? 'null' }}, {{ $lead->assigned_tc_id ?? 'null' }})"
        class="w-full mt-2 inline-flex items-center justify-center gap-1 px-2 py-1.5 rounded-lg text-[11px] font-bold bg-surface-container-low text-primary border border-outline-variant/40 hover:bg-primary hover:text-on-primary transition-all"
    >
        <span class="material-symbols-outlined text-sm">person_add</span>
        Assign Salesperson
    </button>
</div>
