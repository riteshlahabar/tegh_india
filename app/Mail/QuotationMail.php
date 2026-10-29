<?php

namespace App\Mail;

use App\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuotationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Quotation $quotation,
        public ?string $pdfBinary = null
    ) {
        $this->quotation->loadMissing(['company', 'items']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Quotation '.$this->quotation->quotation_number.' — Tegh Cables',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quotation',
            with: [
                'quotation' => $this->quotation,
                'company' => $this->quotation->company,
            ],
        );
    }

    public function attachments(): array
    {
        if (! $this->pdfBinary) {
            return [];
        }

        $safeName = preg_replace('/[^A-Za-z0-9_\-\/]+/', '-', $this->quotation->quotation_number);

        return [
            Attachment::fromData(fn () => $this->pdfBinary, $safeName.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
