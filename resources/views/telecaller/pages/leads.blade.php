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
<div class="space-y-6">
    <section class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl sm:text-3xl font-bold text-primary tracking-tight">Leads</h2>
            <p class="text-sm text-on-surface-variant">{{ $activeCount }} active in {{ $branch ?: 'your branch' }} — assigned to you first, then open accounts</p>
        </div>
        <form method="GET" action="{{ route('telecaller.leads') }}" class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
            <input type="search" name="q" value="{{ $q }}" placeholder="Search name, city, mobile..."
                   class="rounded-lg border-outline-variant/50 text-sm min-w-[220px] focus:ring-secondary focus:border-secondary">
            <select name="stage" class="rounded-lg border-outline-variant/50 text-sm focus:ring-secondary focus:border-secondary">
                <option value="">All stages</option>
                @foreach(['New','Follow-up','Quotation Sent','Negotiation','Won','Lost'] as $opt)
                    <option value="{{ $opt }}" @selected($stage === $opt)>{{ $opt }}</option>
                @endforeach
            </select>
            <button class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-bold">Filter</button>
        </form>
    </section>

    <section class="bg-white border border-outline-variant/30 rounded-2xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left min-w-[720px]">
                <thead class="bg-surface-container-low border-b border-outline-variant/30">
                    <tr>
                        <th class="px-5 py-3 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Lead</th>
                        <th class="px-5 py-3 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Contact</th>
                        <th class="px-5 py-3 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Stage</th>
                        <th class="px-5 py-3 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Last / next</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    @forelse($leads as $lead)
                        @php $latest = $lead->calls->first(); @endphp
                        <tr class="hover:bg-primary/5">
                            <td class="px-5 py-4">
                                <p class="font-bold text-primary">{{ $lead->name }}</p>
                                <p class="text-xs text-on-surface-variant">{{ $lead->city ?: '—' }}{{ $lead->industry ? ' · '.$lead->industry : '' }}</p>
                            </td>
                            <td class="px-5 py-4 text-sm">
                                <p>{{ $lead->contact_person_name ?: '—' }}</p>
                                <p class="text-xs text-on-surface-variant">{{ $lead->mobile_number ?: 'No mobile' }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded {{ $stageBadge($lead->stage) }}">{{ $lead->stage }}</span>
                                @if((int) $lead->assigned_tc_id === (int) auth()->id())
                                    <span class="ml-1 text-[10px] font-bold uppercase text-secondary">Yours</span>
                                @else
                                    <span class="ml-1 text-[10px] font-bold uppercase text-on-surface-variant">Open</span>
                                @endif
                                <form method="POST" action="{{ route('telecaller.calls.store') }}" class="mt-2">
                                    @csrf
                                    <input type="hidden" name="company_id" value="{{ $lead->id }}">
                                    <select name="outcome" onchange="this.form.submit()" class="text-[11px] font-semibold bg-surface-container-low border border-outline-variant/40 rounded px-2 py-1 text-primary">
                                        @foreach(\App\Models\Call::OUTCOMES as $opt)
                                            <option value="{{ $opt }}">{{ $opt }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <td class="px-5 py-4 text-xs text-on-surface-variant">
                                <p>{{ \App\Support\IndiaTime::format($latest?->called_at, 'd M, h:i A') ?: 'Never called' }}</p>
                                <p class="font-bold text-secondary">{{ \App\Support\IndiaTime::format($latest?->next_followup, 'd M Y, h:i A') ?: 'No follow-up' }}</p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('telecaller.leads.show', $lead->id) }}" class="inline-flex items-center gap-1 bg-secondary text-white text-xs font-bold px-3 py-2 rounded-lg">
                                    Open desk
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-sm text-on-surface-variant">No {{ $branch ?: 'branch' }} customers found. Ask admin to assign accounts, or check that customers are tagged with your city.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($leads, 'links'))
            <div class="px-5 py-4 border-t border-outline-variant/20">
                {{ $leads->links() }}
            </div>
        @endif
    </section>
</div>
@endsection
