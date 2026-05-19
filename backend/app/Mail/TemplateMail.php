<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TemplateMail extends Mailable
{
    use Queueable, SerializesModels;
 
    public function __construct(
        public string $subject,
        public string $renderedHtml,
        public string $renderedText,
        public string $messageUuid
    ) {}
 
    public function build()
    {
        return $this->subject($this->subject)
            ->html($this->renderedHtml . $this->trackingPixel())
            ->text(new HtmlString($this->renderedText));
    }
 
    private function trackingPixel(): string
    {
        $url = route('messages.pixel', ['uuid' => $this->messageUuid]);
        return "<img src=\"{$url}\" width=\"1\" height=\"1\" alt=\"\"/>";
    }
}
