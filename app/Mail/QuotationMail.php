<?php

namespace App\Mail;

use App\Models\Quotation;
use App\Services\QuotationGenerator;
use App\Services\QuotationSharingService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuotationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Quotation $quotation,
        public string $customMessage = '',
    ) {}

    public function envelope(): Envelope
    {
        $companyEmail = settings('contact.email', 'mi.cnc.factory@gmail.com');
        $companyName = settings('company.name_ar', 'إم آي للصناعات المعدنية');

        return new Envelope(
            from: new Address($companyEmail, $companyName),
            subject: "عرض سعر #{$this->quotation->quotation_number} - {$this->quotation->project_name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quotation',
            with: [
                'quotation' => $this->quotation,
                'customMessage' => $this->customMessage,
                'publicUrl' => app(QuotationSharingService::class)
                    ->getPublicPreviewUrl($this->quotation),
            ],
        );
    }

    public function attachments(): array
    {
        $pdfPath = app(QuotationGenerator::class)
            ->generatePdf($this->quotation);

        return [
            Attachment::fromStorageDisk('local', $pdfPath)
                ->as("Quotation-{$this->quotation->quotation_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
