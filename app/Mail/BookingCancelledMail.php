<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BookingCancelledMail extends Mailable
{
    use Queueable, SerializesModels;

    public $customerName;
    public $bookingDate;
    public $bookingTime;

    public function __construct(string $customerName, string $bookingDate, string $bookingTime)
    {
        $this->customerName = $customerName;
        $this->bookingDate  = $bookingDate;
        $this->bookingTime  = $bookingTime;
    }

    public function build()
    {
        return $this->subject('Booking Dibatalkan - TRIMLY')
            ->view('emails.booking-cancelled');
    }
}