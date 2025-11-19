<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Address;


class EmailServices extends Mailable
{
    use Queueable, SerializesModels;

    public $subject;
    public $views;
    public $data;
    public $attach;
    /**
     * Create a new message instance.
     */
    public function __construct($subject, $views, $data = null, $attach)
    {
        $this->subject = $subject;
        $this->views = $views;
        $this->data = $data;
        $this->attach = $attach;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject,
            replyTo: [
                new Address('info@almysapp.com', 'Almysauto'),
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // return new Content(
        //     view: $this->views,
        // );
        return new Content(
            view: $this->views,
            with: $this->data,
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        // return $this->attach;
        return is_array($this->attach) ? $this->attach : [$this->attach];
        // return [
        //     Attachment::fromStorage('public/Estimate/65f1458499d54.jpg'),
        //     Attachment::fromStorage('public/Estimate/65f15a3c016fe.jpeg'),
        // ];
    }
}
