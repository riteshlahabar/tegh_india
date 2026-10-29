<?php

namespace App\Services\Admin;

use App\Models\Quotation;
use App\Services\WhatsApp\GreenApiClient;
use App\Support\IndianNumber;

class WhatsAppPageService
{
    public function __construct(private GreenApiClient $whatsapp) {}

    public function data(): array
    {
        $quotes = Quotation::with(['company', 'items'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        return [
            'pendingQuotes' => $quotes->whereNull('whatsapp_sent_at')->values(),
            'sentQuotes' => $quotes->whereNotNull('whatsapp_sent_at')->values(),
            'whatsapp' => $this->whatsapp->status(),
            'whatsappSettings' => [
                'api_url' => $this->whatsapp->apiUrl(),
                'id_instance' => $this->whatsapp->idInstance(),
                'phone' => $this->whatsapp->phone(),
                'api_token' => $this->whatsapp->token(),
                'has_token' => $this->whatsapp->token() !== '',
            ],
        ];
    }

    public function phone(Quotation $quote): ?string
    {
        $company = $quote->company;
        $raw = $company?->whatsapp_number
            ?: ($quote->party_mobile ?: $company?->mobile_number);

        $raw = trim((string) $raw);

        return $raw !== '' ? $raw : null;
    }

    public function message(Quotation $quote): string
    {
        $name = $quote->company->name ?? 'Customer';
        $amount = IndianNumber::format($quote->amount, 2);
        $date = optional($quote->date)->format('d M Y') ?: '—';

        $lines = [
            "Hello {$name},",
            '',
            "Your quotation {$quote->quotation_number} from Tegh Cables is ready.",
            "Amount: ₹ {$amount}",
            "Date: {$date}",
        ];

        $items = $quote->relationLoaded('items') ? $quote->items : $quote->items()->get();
        foreach ($items->take(5) as $item) {
            $label = trim((string) ($item->description ?: 'Item'));
            $qty = rtrim(rtrim(number_format((float) $item->qty, 2, '.', ''), '0'), '.');
            $lines[] = "• {$label} × {$qty}";
        }

        if ($items->count() > 5) {
            $lines[] = '• +'.($items->count() - 5).' more items';
        }

        $lines[] = '';
        $lines[] = 'Reply if you want any change in this quotation.';

        return implode("\n", $lines);
    }
}
