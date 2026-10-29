@php
    $phone = $sent ? ($quote->whatsapp_sent_to ?: $wa->phone($quote)) : $wa->phone($quote);
    $message = $sent && $quote->whatsapp_message ? $quote->whatsapp_message : $wa->message($quote);
@endphp
<article class="bg-white border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-sm">
    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
        <div class="min-w-0">
            <p class="font-data-tabular font-bold text-primary">{{ $quote->quotation_number }}</p>
            <p class="text-sm text-primary font-medium mt-1">{{ $quote->company->name ?? '—' }}</p>
            <p class="text-xs text-on-surface-variant mt-1">
                ₹ {{ \App\Support\IndianNumber::format($quote->amount, 2) }}
                · {{ optional($quote->date)->format('d M Y') ?: '—' }}
                · {{ $quote->status }}
            </p>
            @if($sent && $quote->whatsapp_sent_at)
                <p class="text-xs text-green-700 font-semibold mt-2">
                    Recorded {{ $quote->whatsapp_sent_at->format('d M Y H:i') }}
                    @if($quote->whatsapp_sent_to)
                        · {{ $quote->whatsapp_sent_to }}
                    @endif
                </p>
            @endif
        </div>
        @unless($sent)
            <form method="POST" action="{{ route('admin.whatsapp.send', $quote) }}" class="flex flex-col sm:flex-row sm:items-end gap-2 w-full lg:w-auto lg:min-w-[320px]">
                @csrf
                <div class="flex-1">
                    <label class="block text-[11px] font-bold text-on-surface-variant mb-1">WhatsApp number</label>
                    <input type="hidden" name="quote_id" value="{{ $quote->id }}">
                    <input type="text" name="phone" value="{{ (int) old('quote_id') === (int) $quote->id ? old('phone', $phone) : $phone }}" placeholder="10-digit mobile" class="w-full py-2.5 px-3 bg-surface-container-low border-none rounded-lg text-sm focus:ring-2 focus:ring-secondary" required>
                </div>
                <button class="bg-primary text-on-primary px-4 py-2.5 rounded-lg text-sm font-bold inline-flex items-center justify-center gap-1">
                    <span class="material-symbols-outlined text-[18px]">chat</span>
                    {{ ($whatsapp['configured'] ?? false) ? 'Send on WhatsApp' : 'Record send' }}
                </button>
            </form>
        @endunless
    </div>
    <details class="mt-4">
        <summary class="text-xs font-bold text-secondary cursor-pointer">Message that will be sent</summary>
        <pre class="mt-2 whitespace-pre-wrap text-sm text-on-surface bg-surface-container-low rounded-lg px-4 py-3">{{ $message }}</pre>
    </details>
</article>
