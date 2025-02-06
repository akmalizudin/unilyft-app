<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    
     public $otp;

     public function __construct($otp)
     {
         $this->otp = $otp;
     }

    // public function build()
    // {
    //     return $this->view('emails.otp')->with(['otp' => $this->otp]);
    // }

    public function build()
    {
        return $this->subject('OTP Verification')
            ->html("
                <html>
                <head>
                    <title>OTP Verification</title>
                </head>
                <body>
                    <p>Your OTP code is: {$this->otp}</p>
                    <p>Please enter this code in the app to verify your email address.</p>
                </body>
                </html>
            ");
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('support@unilyft.com', 'UniLyft Support'),
            subject: 'OTP Verification',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'view.name',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
