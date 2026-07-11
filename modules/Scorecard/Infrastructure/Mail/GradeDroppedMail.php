<?php

declare(strict_types=1);

namespace Modules\Scorecard\Infrastructure\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Scorecard\Infrastructure\Persistence\Eloquent\Model\MonitorModel;
use Modules\Scorecard\Infrastructure\Persistence\Eloquent\Model\ScanModel;

class GradeDroppedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public MonitorModel $monitor,
        public ScanModel $scan,
        public string $previousGrade,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Security grade for {$this->monitor->host} dropped to {$this->scan->grade_letter}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.grade-dropped',
            with: [
                'host' => $this->monitor->host,
                'previousGrade' => $this->previousGrade,
                'newGrade' => $this->scan->grade_letter,
                'findings' => $this->scan->findings ?? [],
                'reportUrl' => route('report', $this->scan),
                'manageUrl' => route('monitor.manage', $this->monitor),
            ],
        );
    }
}
