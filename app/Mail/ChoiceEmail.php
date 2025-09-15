<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ChoiceEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public $data;
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Get the message envelope.
     */
    public function build()
    {
        $address = 'noreply-sctvesd@wb.gov.in';
        $subject = $this->data['subject'];
        $name = 'WBSCTVESD';

        return $this->view('emails.choice')
            ->from($address, $name)
            ->cc($address, $name)
            ->replyTo($address, $name)
            ->subject($subject);
    }
}
