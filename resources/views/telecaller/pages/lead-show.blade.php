@extends('layouts.telecaller')

@section('content')
@php
    $latest = $lead->calls->first();
    $tel = preg_replace('/\s+/', '', (string) ($lead->mobile_number ?: $lead->whatsapp_number ?: ''));
@endphp
<div class="space-y-6 max-w-5xl">
    <a href="{{ route('telecaller.leads') }}" class="inline-flex items-center gap-1 text-sm font-bold text-on-surface-variant hover:text-primary">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span> All leads
    </a>

    <section class="grid lg:grid-cols-[1.2fr_0.8fr] gap-6">
        <div class="bg-white border border-outline-variant/30 rounded-2xl p-6 shadow-sm relative overflow-hidden">
            <div class="absolute left-0 top-0 h-full w-1.5 bg-secondary-container"></div>
            <p class="text-[11px] tracking-[0.22em] uppercase text-secondary font-bold">Lead desk</p>
            <h2 class="text-3xl font-bold text-primary mt-1">{{ $lead->name }}</h2>
            <p class="text-sm text-on-surface-variant mt-1">{{ $lead->city }}{{ $lead->sub_city ? ' · '.$lead->sub_city : '' }}{{ $lead->industry ? ' · '.$lead->industry : '' }}</p>

            <dl class="mt-6 grid sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-[10px] uppercase tracking-wider text-on-surface-variant font-bold">Contact</dt>
                    <dd class="font-semibold text-primary">{{ $lead->contact_person_name ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] uppercase tracking-wider text-on-surface-variant font-bold">Mobile</dt>
                    <dd class="font-semibold text-primary">{{ $lead->mobile_number ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] uppercase tracking-wider text-on-surface-variant font-bold">WhatsApp</dt>
                    <dd>{{ $lead->whatsapp_number ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] uppercase tracking-wider text-on-surface-variant font-bold">Email</dt>
                    <dd>{{ $lead->email ?: '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-[10px] uppercase tracking-wider text-on-surface-variant font-bold">Address</dt>
                    <dd>{{ $lead->address ?: '—' }}</dd>
                </div>
            </dl>

            <div class="mt-6 flex flex-wrap gap-3">
                @if($tel)
                    <a href="tel:{{ $tel }}" class="inline-flex items-center gap-2 bg-secondary text-white font-bold px-5 py-3 rounded-lg">
                        <span class="material-symbols-outlined">call</span> Dial now
                    </a>
                @endif
                <span class="inline-flex items-center px-3 py-2 rounded-lg bg-surface-container-low text-xs font-bold uppercase">{{ $lead->stage }}</span>
                <div class="min-w-[220px]">
                    <input value="{{ $lead->stage }}" readonly class="w-full text-xs font-bold uppercase rounded-lg border-outline-variant/50 text-primary bg-white/70 px-3 py-2">
                </div>
                @if((int) $lead->assigned_tc_id !== (int) auth()->id())
                    <form method="POST" action="{{ route('telecaller.leads.claim', $lead->id) }}">
                        @csrf
                        <button class="inline-flex items-center px-3 py-2 rounded-lg bg-primary text-white text-xs font-bold uppercase">Take this lead</button>
                    </form>
                @endif
                <a href="{{ route('telecaller.quotations', ['company_id' => $lead->id]) }}" class="inline-flex items-center gap-2 bg-primary text-white font-bold px-5 py-3 rounded-lg">
                    <span class="material-symbols-outlined">request_quote</span> Add quotation
                </a>
            </div>
        </div>

        <form method="POST" action="{{ route('telecaller.calls.store') }}" class="bg-primary text-white rounded-2xl p-6 shadow-sm space-y-4">
            @csrf
            <input type="hidden" name="company_id" value="{{ $lead->id }}">
            <h3 class="font-title-sm">Log this call</h3>
            <div>
                <label class="block text-xs text-white/70 mb-1">Outcome</label>
                <select name="outcome" required class="w-full rounded-lg text-primary text-sm">
                    @foreach(\App\Models\Call::OUTCOMES as $opt)
                        <option value="{{ $opt }}">{{ $opt }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-white/70 mb-1">Remarks</label>
                <textarea name="remarks" rows="3" class="w-full rounded-lg text-primary text-sm" placeholder="What did they say?"></textarea>
            </div>
            <div>
                <label class="block text-xs text-white/70 mb-1">Next follow-up (India time)</label>
                <input type="datetime-local" name="next_followup" class="w-full rounded-lg text-primary text-sm">
            </div>
            <button class="w-full bg-secondary-container text-on-secondary-container font-bold py-3 rounded-lg">Save outcome</button>
            @if($latest)
                <p class="text-xs text-white/60">Last: {{ $latest->outcome }} · {{ \App\Support\IndiaTime::format($latest->called_at) }} IST</p>
            @endif
        </form>
    </section>

    <section class="bg-white border border-outline-variant/30 rounded-2xl overflow-hidden shadow-sm">
        <div class="px-5 py-4 border-b border-outline-variant/20 flex items-center justify-between">
            <h3 class="font-title-sm text-primary">Quotations</h3>
            <a href="{{ route('telecaller.quotations', ['company_id' => $lead->id]) }}" class="text-xs font-bold text-secondary">Create</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-surface-container-low text-[11px] uppercase tracking-wider text-on-surface-variant">
                    <tr>
                        <th class="px-5 py-3">Quote no.</th>
                        <th class="px-5 py-3">Amount</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    @forelse($lead->quotations as $quote)
                        <tr>
                            <td class="px-5 py-3 font-bold text-primary font-data-tabular">{{ $quote->quotation_number }}</td>
                            <td class="px-5 py-3 text-sm font-data-tabular">₹ {{ \App\Support\IndianNumber::format((float) $quote->amount, 2) }}</td>
                            <td class="px-5 py-3 text-xs font-bold uppercase">{{ $quote->status }}</td>
                            <td class="px-5 py-3 text-xs">{{ \App\Support\IndiaTime::format($quote->date, 'd M Y') ?: '—' }}</td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('telecaller.quotations.pdf', $quote->id) }}" class="text-xs font-bold text-secondary">PDF</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-sm text-on-surface-variant">No quotation sent yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="bg-white border border-outline-variant/30 rounded-2xl overflow-hidden shadow-sm">
        <div class="px-5 py-4 border-b border-outline-variant/20">
            <h3 class="font-title-sm text-primary">Call history</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-surface-container-low text-[11px] uppercase tracking-wider text-on-surface-variant">
                    <tr>
                        <th class="px-5 py-3">When</th>
                        <th class="px-5 py-3">Outcome</th>
                        <th class="px-5 py-3">Remarks</th>
                        <th class="px-5 py-3">Follow-up</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    @forelse($lead->calls as $call)
                        <tr>
                            <td class="px-5 py-3 text-xs font-data-tabular">{{ \App\Support\IndiaTime::format($call->called_at) }} IST</td>
                            <td class="px-5 py-3 text-sm font-bold">{{ $call->outcome }}</td>
                            <td class="px-5 py-3 text-sm text-on-surface-variant">{{ $call->remarks ?: '—' }}</td>
                            <td class="px-5 py-3 text-xs font-bold text-secondary">{{ \App\Support\IndiaTime::format($call->next_followup, 'd M Y, h:i A') ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-sm text-on-surface-variant">No calls on this lead yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
