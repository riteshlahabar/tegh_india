@extends('layouts.telecaller')

@section('content')
@php
    $stageBadge = function (?string $stage): string {
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
@endphp
<div class="space-y-8">
    <section class="relative overflow-hidden rounded-2xl bg-primary text-white px-6 py-8 sm:px-8">
        <div class="absolute right-0 top-0 h-full w-1/2 opacity-30" style="background-image: radial-gradient(rgba(251,120,0,.5) 1.4px, transparent 1.4px); background-size: 16px 16px;"></div>
        <div class="absolute left-0 top-0 h-full w-1.5 bg-secondary-container"></div>
        <p class="text-[11px] tracking-[0.28em] uppercase text-secondary-container font-bold">Today's board</p>
        <h2 class="mt-2 text-3xl sm:text-4xl font-bold tracking-tight">{{ auth()->user()->name }}, your queue is live.</h2>
        <p class="mt-2 text-white/70 text-sm max-w-xl">
            Assigned accounts plus open {{ $branch ?: 'branch' }} customers you can pick up. Logging a call assigns the lead to you.
        </p>
        <div class="mt-8 grid grid-cols-3 gap-4 max-w-xl">
            <div>
                <p class="text-[10px] tracking-widest uppercase text-white/50">Leads</p>
                <p class="text-3xl font-bold font-data-tabular">{{ $leadCount }}</p>
            </div>
            <div>
                <p class="text-[10px] tracking-widest uppercase text-white/50">Calls today</p>
                <p class="text-3xl font-bold font-data-tabular text-secondary-container">{{ $callsToday }}</p>
            </div>
            <div>
                <p class="text-[10px] tracking-widest uppercase text-white/50">Due now</p>
                <p class="text-3xl font-bold font-data-tabular">{{ $dueCount }}</p>
            </div>
        </div>
        <a href="{{ route('telecaller.quotations') }}" class="relative mt-6 inline-flex items-center gap-2 bg-secondary-container text-on-secondary-container font-bold px-5 py-3 rounded-lg">
            <span class="material-symbols-outlined">request_quote</span> Create quotation
        </a>
    </section>

    <section class="grid lg:grid-cols-2 gap-6">
        <div class="bg-white border border-outline-variant/30 rounded-2xl overflow-hidden shadow-sm">
            <div class="px-5 py-4 border-b border-outline-variant/20 flex items-center justify-between">
                <h3 class="font-title-sm text-primary">Follow-ups due</h3>
                <a href="{{ route('telecaller.leads') }}" class="text-xs font-bold text-secondary">All leads</a>
            </div>
            <div class="divide-y divide-outline-variant/20">
                @forelse($dueFollowups as $lead)
                    @php $latest = $lead->calls->first(); @endphp
                    <a href="{{ route('telecaller.leads.show', $lead->id) }}" class="flex items-center gap-3 px-5 py-4 hover:bg-primary/5">
                        <span class="w-1.5 self-stretch rounded-full bg-secondary-container"></span>
                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-primary truncate">{{ $lead->name }}</p>
                            <p class="text-xs text-on-surface-variant truncate">{{ $lead->contact_person_name ?: 'No contact' }} · {{ $lead->mobile_number ?: 'No mobile' }}</p>
                        </div>
                        <span class="text-xs font-bold text-secondary whitespace-nowrap">{{ \App\Support\IndiaTime::format($latest?->next_followup, 'd M, h:i A') }}</span>
                    </a>
                @empty
                    <p class="px-5 py-10 text-center text-sm text-on-surface-variant">No callbacks due. Open Leads to start dialing.</p>
                @endforelse
            </div>
        </div>

        <div class="bg-white border border-outline-variant/30 rounded-2xl overflow-hidden shadow-sm">
            <div class="px-5 py-4 border-b border-outline-variant/20 flex items-center justify-between">
                <h3 class="font-title-sm text-primary">Open in {{ $branch ?: 'your branch' }}</h3>
                <a href="{{ route('telecaller.leads') }}" class="text-xs font-bold text-secondary">All leads</a>
            </div>
            <div class="divide-y divide-outline-variant/20">
                @forelse($availableLeads as $lead)
                    <a href="{{ route('telecaller.leads.show', $lead->id) }}" class="flex items-center gap-3 px-5 py-4 hover:bg-primary/5">
                        <span class="w-1.5 self-stretch rounded-full bg-primary"></span>
                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-primary truncate">{{ $lead->name }}</p>
                            <p class="text-xs text-on-surface-variant truncate">{{ $lead->contact_person_name ?: 'No contact' }} · {{ $lead->mobile_number ?: 'No mobile' }}</p>
                        </div>
                        <span class="text-[10px] font-bold uppercase text-secondary">Take</span>
                    </a>
                @empty
                    <p class="px-5 py-10 text-center text-sm text-on-surface-variant">No open {{ $branch ?: 'branch' }} customers waiting. Ask admin to import or assign accounts.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="bg-white border border-outline-variant/30 rounded-2xl overflow-hidden shadow-sm">
        <div class="px-5 py-4 border-b border-outline-variant/20 flex items-center justify-between">
            <h3 class="font-title-sm text-primary">Recent calls</h3>
            <a href="{{ route('telecaller.calls') }}" class="text-xs font-bold text-secondary">Full log</a>
        </div>
        <div class="divide-y divide-outline-variant/20">
            @forelse($recentCalls as $call)
                <div class="px-5 py-4">
                    <div class="flex items-center justify-between gap-3">
                        <p class="font-bold text-primary truncate">{{ $call->company->name ?? 'Lead' }}</p>
                        <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded {{ $stageBadge(match($call->outcome) {
                            'Not Interested', 'Not interested', 'No Requirement', 'Lost' => 'Lost',
                            'Interested', 'Profile Shared', 'Regular Party' => 'Won',
                            default => 'Follow-up',
                        }) }}">{{ $call->outcome }}</span>
                    </div>
                    <p class="text-xs text-on-surface-variant mt-1">{{ \App\Support\IndiaTime::format($call->called_at) }} IST · {{ \Illuminate\Support\Str::limit($call->remarks ?: 'No remarks', 80) }}</p>
                </div>
            @empty
                <p class="px-5 py-10 text-center text-sm text-on-surface-variant">No calls logged yet.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
