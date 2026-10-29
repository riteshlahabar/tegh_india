@php
    $wa = app(\App\Services\Admin\WhatsAppPageService::class);
@endphp
<div id="view-whatsapp" class="space-y-xl">
    <section class="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
        <div>
            <h2 class="font-headline-md text-headline-md text-primary font-bold">WhatsApp Quotations</h2>
            <p class="font-body-sm text-on-surface-variant">Send a quotation from the Tegh WhatsApp number, or review what has already gone out.</p>
        </div>
    </section>

    <section class="bg-white border border-outline-variant/30 rounded-xl px-5 py-4 flex flex-wrap items-center justify-between gap-3 shadow-sm">
        <div class="flex items-center gap-3">
            <span class="material-symbols-outlined {{ ($whatsapp['ok'] ?? false) ? 'text-green-700' : 'text-on-surface-variant' }}">chat</span>
            <div>
                @if(!($whatsapp['configured'] ?? false))
                    <p class="text-sm font-semibold text-primary">WhatsApp API not connected</p>
                    <p class="text-xs text-on-surface-variant">Recording a send only saves it in this CRM. Nothing is delivered to the customer until the API is added.</p>
                @else
                    <p class="text-sm font-semibold text-primary">GREEN-API {{ $whatsapp['state_label'] }}</p>
                    <p class="text-xs text-on-surface-variant">
                        Instance {{ $whatsapp['id_instance'] }}
                        @if($whatsapp['phone'])
                            · {{ $whatsapp['phone'] }}
                        @endif
                        @if($whatsapp['error'])
                            · {{ $whatsapp['error'] }}
                        @endif
                    </p>
                @endif
            </div>
        </div>
        @if($whatsapp['ok'] ?? false)
            <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded bg-green-100 text-green-800">Live</span>
        @elseif($whatsapp['configured'] ?? false)
            <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded bg-amber-100 text-amber-800">Check token</span>
        @else
            <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded bg-amber-100 text-amber-800">Dummy</span>
        @endif
    </section>

    <section class="bg-white border border-outline-variant/30 rounded-xl p-5 shadow-sm">
        <h3 class="text-xs font-bold tracking-wide text-primary mb-3">GREEN-API INSTANCE</h3>
        <form method="POST" action="{{ route('admin.whatsapp.settings') }}" class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @csrf
            <div>
                <label class="block text-[11px] font-bold text-on-surface-variant mb-1">API URL</label>
                <input name="api_url" value="{{ old('api_url', $whatsappSettings['api_url'] ?? '') }}" class="w-full py-2.5 px-3 bg-surface-container-low border-none rounded-lg text-sm" required>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-on-surface-variant mb-1">Instance ID</label>
                <input name="id_instance" value="{{ old('id_instance', $whatsappSettings['id_instance'] ?? '') }}" class="w-full py-2.5 px-3 bg-surface-container-low border-none rounded-lg text-sm" required>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-on-surface-variant mb-1">API token</label>
                <input name="api_token" type="text" autocomplete="off" value="{{ old('api_token', $whatsappSettings['api_token'] ?? '') }}" placeholder="Paste apiTokenInstance" class="w-full py-2.5 px-3 bg-surface-container-low border-none rounded-lg text-sm font-mono">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-on-surface-variant mb-1">WhatsApp phone</label>
                <input name="phone" value="{{ old('phone', $whatsappSettings['phone'] ?? '') }}" class="w-full py-2.5 px-3 bg-surface-container-low border-none rounded-lg text-sm" placeholder="919820392245">
            </div>
            <div class="md:col-span-2">
                <button class="bg-primary text-on-primary px-4 py-2.5 rounded-lg text-sm font-bold">Save and check connection</button>
            </div>
        </form>
    </section>

    <section class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div class="bg-white border border-outline-variant/30 rounded-xl px-5 py-4 shadow-sm">
            <p class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">To send</p>
            <p class="text-2xl font-bold text-primary mt-1">{{ $pendingQuotes->count() }}</p>
        </div>
        <div class="bg-white border border-outline-variant/30 rounded-xl px-5 py-4 shadow-sm">
            <p class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Sent</p>
            <p class="text-2xl font-bold text-primary mt-1">{{ $sentQuotes->count() }}</p>
        </div>
    </section>

    <section class="space-y-3">
        <h3 class="font-title-sm text-primary">Have to send</h3>
        @forelse($pendingQuotes as $quote)
            @include('partials.dashboard.whatsapp-quote', ['quote' => $quote, 'wa' => $wa, 'sent' => false])
        @empty
            <div class="bg-white border border-outline-variant/30 rounded-xl px-5 py-8 text-center text-sm text-on-surface-variant">
                Every quotation is already recorded as sent.
            </div>
        @endforelse
    </section>

    <section class="space-y-3">
        <h3 class="font-title-sm text-primary">Recorded as sent</h3>
        @forelse($sentQuotes as $quote)
            @include('partials.dashboard.whatsapp-quote', ['quote' => $quote, 'wa' => $wa, 'sent' => true])
        @empty
            <div class="bg-white border border-outline-variant/30 rounded-xl px-5 py-8 text-center text-sm text-on-surface-variant">
                No quotations have been recorded as sent yet.
            </div>
        @endforelse
    </section>
</div>
