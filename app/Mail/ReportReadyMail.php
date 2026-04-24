<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class ReportReadyMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        private readonly array $filePaths, 
        private readonly string $fromDt, 
        private readonly string $toDt,
        private readonly string $searchQuery
        ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Report for {$this->searchQuery} ready",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        //$attachmentNames = []
            
        return new Content(
            view: 'emails.report-ready',
            with: [
                'searchQuery'    => $this->searchQuery,
                'fromDmy'        => date('d.m.Y', strtotime($this->fromDt)),
                'toDmy'        => date('d.m.Y', strtotime($this->toDt)),
          //      'attachmentNames' => ...,
            ],
        );

    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];
        foreach ($this->filePaths as $fp) {
            $reportName = basename($fp);
            $attachments[]= 
                Attachment::fromPath($fp)
                    ->as($reportName)
                    ->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ;
        }
        return $attachments;
    }
}
