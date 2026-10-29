<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Quotation;
use App\Models\QuotationItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class QuotationDocumentService
{
    public function syncItems(Quotation $quote, array $items): void
    {
        $quote->items()->delete();

        $totalAmount = 0.0;
        $totalQty = 0.0;

        foreach (array_values($items) as $index => $row) {
            $description = trim((string) ($row['description'] ?? ''));
            if ($description === '') {
                continue;
            }

            $qty = (float) ($row['qty'] ?? 0);
            $listPrice = (float) ($row['list_price'] ?? 0);
            $discount = (float) ($row['discount_percent'] ?? 0);

            if (isset($row['price']) && $row['price'] !== '' && $row['price'] !== null) {
                $price = round((float) $row['price'], 4);
            } else {
                $price = round($listPrice * (1 - ($discount / 100)), 4);
            }

            if (isset($row['amount']) && $row['amount'] !== '' && $row['amount'] !== null) {
                $amount = round((float) $row['amount'], 2);
            } else {
                $amount = round($price * $qty, 2);
            }

            // Keep discount in sync when price was entered manually against list price.
            if ($listPrice > 0 && isset($row['price']) && $row['price'] !== '' && $row['price'] !== null) {
                $discount = round((1 - ($price / $listPrice)) * 100, 4);
                if ($discount < 0) {
                    $discount = 0;
                }
                if ($discount > 100) {
                    $discount = 100;
                }
            }

            QuotationItem::create([
                'quotation_id' => $quote->id,
                'product_id' => ! empty($row['product_id']) ? (int) $row['product_id'] : null,
                'description' => $description,
                'hsn_code' => trim((string) ($row['hsn_code'] ?? '')) ?: null,
                'qty' => $qty,
                'unit' => trim((string) ($row['unit'] ?? '')) ?: null,
                'list_price' => $listPrice,
                'discount_percent' => $discount,
                'price' => $price,
                'amount' => $amount,
                'sort_order' => $index,
            ]);

            $totalAmount += $amount;
            $totalQty += $qty;
        }

        $quote->update([
            'amount' => round($totalAmount, 2),
            'total_qty' => round($totalQty, 3),
        ]);
    }

    public function fillPartyFromCompany(Quotation $quote, Company $company): void
    {
        $addressParts = array_filter([
            $company->address,
            $company->city,
            $company->sub_city,
        ]);

        $quote->fill([
            'party_gstin' => $quote->party_gstin ?: $company->gst_number,
            'party_mobile' => $quote->party_mobile ?: $company->mobile_number,
            'party_address' => $quote->party_address ?: implode("\n", $addressParts),
        ]);
    }

    public function pdf(Quotation $quotation)
    {
        $quotation->loadMissing(['company', 'items']);

        $letterhead = config('quotation');
        $logoRelative = (string) ($letterhead['logo'] ?? 'images/logo.png');
        $logoPath = public_path($logoRelative);
        $logoSrc = null;
        if (is_file($logoPath)) {
            $mime = mime_content_type($logoPath) ?: 'image/png';
            $logoSrc = 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($logoPath));
        }

        return Pdf::loadView('pdf.quotation', [
            'quotation' => $quotation,
            'letterhead' => $letterhead,
            'logoSrc' => $logoSrc,
        ])->setPaper('a4', 'portrait');
    }

    public function download(Quotation $quotation)
    {
        $safeName = preg_replace('/[^A-Za-z0-9_\-\.]+/', '-', str_replace(['/', '\\'], '-', (string) $quotation->quotation_number));
        $party = preg_replace('/[^A-Za-z0-9_\-\.]+/', '-', str_replace(['/', '\\'], '-', (string) ($quotation->company->name ?? 'Customer')));

        return $this->pdf($quotation)->download($party.'-'.$safeName.'.pdf');
    }

    public function output(Quotation $quotation): string
    {
        return $this->pdf($quotation)->output();
    }

    public function createWithItems(array $header, array $items): Quotation
    {
        return DB::transaction(function () use ($header, $items) {
            $company = Company::findOrFail($header['company_id']);

            $quote = new Quotation($header);
            if (empty($quote->quotation_number)) {
                $quote->quotation_number = Quotation::nextNumber();
            }
            if (empty($quote->terms_conditions)) {
                $quote->terms_conditions = Quotation::defaultTerms();
            }
            if (empty($quote->payment_terms)) {
                $quote->payment_terms = 'Against Proforma Invoice';
            }
            $this->fillPartyFromCompany($quote, $company);
            $quote->amount = 0;
            $quote->save();

            $this->syncItems($quote, $items);
            $quote->refresh();

            return $quote->load(['company', 'items']);
        });
    }

    public function updateWithItems(Quotation $quote, array $header, array $items): Quotation
    {
        return DB::transaction(function () use ($quote, $header, $items) {
            $company = Company::findOrFail($header['company_id']);
            $quote->fill($header);
            $this->fillPartyFromCompany($quote, $company);
            $quote->save();
            $this->syncItems($quote, $items);
            $quote->refresh();

            return $quote->load(['company', 'items']);
        });
    }
}
