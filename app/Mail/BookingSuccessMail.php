<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;

class BookingSuccessMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $booking;

    public function __construct($booking)
    {
        $this->booking = $booking->loadMissing('customer');
    }

    public function build()
    {
        return $this->subject('Booking Berhasil - TRIMLY')
            ->view('emails.booking-success');
    }
}