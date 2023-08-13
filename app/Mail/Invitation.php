<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Content;

class Invitation extends Mailable
{
    protected $event;
    protected $toUser;
    protected $fromUser;

    /**
     * Create a new message instance.
     * 
     * @param string $eventTitle 
     * @param string $toName 
     * @param string $fromName 
     * @param string $url 
     * @return void
     */
    public function __construct(string $eventTitle, string $toName, string $fromName, string $url)
    {
        $this->eventTitle = $eventTitle;
        $this->toName     = $toName;
        $this->fromName   = $fromName;
        $this->url        = $url;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            from: new Address(config('mail.from.address')),
            subject: sprintf(_pgettext('%s is the title of an event', 'Invitation: %s'), $this->eventTitle),
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            view: 'emails.invitation',
            text: 'emails.invitation-text',
            with: [
                'eventTitle' => $this->eventTitle,
                'toName'     => $this->toName,
                'fromName'   => $this->fromName,
                'url'        => $this->url,
            ],
        );
    }
}
