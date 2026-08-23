<?php

namespace App\Mail;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class DailySalesReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Project $project,
        public array $summary,
        public Carbon $date,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Ringkasan Jualan {$this->project->name} ({$this->date->translatedFormat('d M Y')})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.daily-sales-report',
        );
    }
}
