<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BookingRescheduledMail extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;
    public $oldDate;
    public $oldTime;

    public function __construct($booking, $oldDate, $oldTime)
    {
        $this->booking = $booking->loadMissing('customer');
        $this->oldDate = $oldDate;
        $this->oldTime = $oldTime;
    }

    public function build()
    {
        return $this->subject('Jadwal Booking Diubah - TRIMLY')
            ->view('emails.booking-rescheduled');
    }
}