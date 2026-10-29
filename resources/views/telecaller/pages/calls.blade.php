@extends('layouts.telecaller')

@section('content')
<div class="space-y-6">
    <section>
        <h2 class="text-2xl sm:text-3xl font-bold text-primary tracking-tight">Call log</h2>
        <p class="text-sm text-on-surface-variant">Outcomes you have saved from this desk and the app.</p>
    </section>

    <section class="bg-white border border-outline-variant/30 rounded-2xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left min-w-[720px]">
                <thead class="bg-surface-container-low border-b border-outline-variant/30">
                    <tr>
                        <th class="px-5 py-3 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Time</th>
                        <th class="px-5 py-3 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Lead</th>
                        <th class="px-5 py-3 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Outcome</th>
                        <th class="px-5 py-3 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Remarks</th>
                        <th class="px-5 py-3 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Follow-up</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    @forelse($calls as $call)
                        <tr class="hover:bg-primary/5">
                            <td class="px-5 py-4 text-xs font-data-tabular text-primary">{{ \App\Support\IndiaTime::format($call->called_at) }} IST</td>
                            <td class="px-5 py-4">
                                @if($call->company)
                                    <a href="{{ route('telecaller.leads.show', $call->company->id) }}" class="font-bold text-primary hover:underline">{{ $call->company->name }}</a>
                                @else
                                    <span class="text-on-surface-variant">Removed</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-sm font-bold">{{ $call->outcome }}</td>
                            <td class="px-5 py-4 text-sm text-on-surface-variant">{{ $call->remarks ?: '—' }}</td>
                            <td class="px-5 py-4 text-xs font-bold text-secondary">{{ \App\Support\IndiaTime::format($call->next_followup, 'd M Y, h:i A') ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-sm text-on-surface-variant">No calling records yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
