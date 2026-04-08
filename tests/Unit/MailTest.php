<?php

namespace Tests\Unit;

use App\Mail\BookingCancelledMail;
use App\Mail\BookingRescheduledMail;
use App\Mail\BookingSuccessMail;
use App\Models\Booking;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailable;
use Tests\TestCase;

class MailTest extends TestCase
{
    use RefreshDatabase;
    /** @test */
    public function booking_success_mail_subject_benar(): void
    {
        $customer = Customer::factory()->create();
        $booking  = Booking::factory()->create(['customer_id' => $customer->id]);

        $mail = new BookingSuccessMail($booking);

        $this->assertEquals('Booking Berhasil - TRIMLY', $mail->build()->subject);
    }

    /** @test */
    public function booking_success_mail_memakai_view_yang_benar(): void
    {
        $customer = Customer::factory()->create();
        $booking  = Booking::factory()->create(['customer_id' => $customer->id]);

        $mail = new BookingSuccessMail($booking);

        $this->assertEquals('emails.booking-success', $mail->build()->view);
    }

    /** @test */
    public function booking_success_mail_memuat_relasi_customer(): void
    {
        $customer = Customer::factory()->create();
        $booking  = Booking::factory()->create(['customer_id' => $customer->id]);

        $mail = new BookingSuccessMail($booking);

        $this->assertNotNull($mail->booking->customer);
        $this->assertEquals($customer->id, $mail->booking->customer->id);
    }

    /** @test */
    public function booking_success_mail_adalah_instance_mailable(): void
    {
        $customer = Customer::factory()->create();
        $booking  = Booking::factory()->create(['customer_id' => $customer->id]);

        $mail = new BookingSuccessMail($booking);

        $this->assertInstanceOf(Mailable::class, $mail);
    }

    /** @test */
    public function booking_success_mail_mengimplementasikan_should_queue(): void
    {
        $customer = Customer::factory()->create();
        $booking  = Booking::factory()->create(['customer_id' => $customer->id]);

        $mail = new BookingSuccessMail($booking);

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $mail);
    }

    /** @test */
    public function booking_success_mail_menyimpan_data_booking(): void
    {
        $customer = Customer::factory()->create();
        $booking  = Booking::factory()->create([
            'customer_id'  => $customer->id,
            'booking_date' => '2025-06-10',
            'booking_time' => '09:00:00',
        ]);

        $mail = new BookingSuccessMail($booking);

        $this->assertEquals($booking->id, $mail->booking->id);
        $this->assertEquals('2025-06-10', $mail->booking->booking_date);
        $this->assertEquals('09:00:00', $mail->booking->booking_time);
    }

    // =========================================================
    // BookingCancelledMail
    // =========================================================

    /** @test */
    public function booking_cancelled_mail_subject_benar(): void
    {
        $mail = new BookingCancelledMail('Budi', '2025-06-01', '09:00:00');

        $this->assertEquals('Booking Dibatalkan - TRIMLY', $mail->build()->subject);
    }

    /** @test */
    public function booking_cancelled_mail_memakai_view_yang_benar(): void
    {
        $mail = new BookingCancelledMail('Budi', '2025-06-01', '09:00:00');

        $this->assertEquals('emails.booking-cancelled', $mail->build()->view);
    }

    /** @test */
    public function booking_cancelled_mail_menyimpan_customer_name(): void
    {
        $mail = new BookingCancelledMail('Budi Santoso', '2025-06-01', '09:00:00');

        $this->assertEquals('Budi Santoso', $mail->customerName);
    }

    /** @test */
    public function booking_cancelled_mail_menyimpan_booking_date(): void
    {
        $mail = new BookingCancelledMail('Budi', '2025-06-01', '09:00:00');

        $this->assertEquals('2025-06-01', $mail->bookingDate);
    }

    /** @test */
    public function booking_cancelled_mail_menyimpan_booking_time(): void
    {
        $mail = new BookingCancelledMail('Budi', '2025-06-01', '09:00:00');

        $this->assertEquals('09:00:00', $mail->bookingTime);
    }

    /** @test */
    public function booking_cancelled_mail_menerima_berbagai_format_waktu(): void
    {
        $cases = [
            ['08:00:00', '08:00:00'],
            ['20:00:00', '20:00:00'],
            ['13:30:00', '13:30:00'],
        ];

        foreach ($cases as [$input, $expected]) {
            $mail = new BookingCancelledMail('Test', '2025-06-01', $input);
            $this->assertEquals($expected, $mail->bookingTime, "Gagal untuk waktu $input");
        }
    }

    // =========================================================
    // BookingRescheduledMail
    // =========================================================

    /** @test */
    public function booking_rescheduled_mail_subject_benar(): void
    {
        $customer = Customer::factory()->create();
        $booking  = Booking::factory()->create(['customer_id' => $customer->id]);

        $mail = new BookingRescheduledMail($booking, '2025-05-01', '08:00:00');

        $this->assertEquals('Jadwal Booking Diubah - TRIMLY', $mail->build()->subject);
    }

    /** @test */
    public function booking_rescheduled_mail_memakai_view_yang_benar(): void
    {
        $customer = Customer::factory()->create();
        $booking  = Booking::factory()->create(['customer_id' => $customer->id]);

        $mail = new BookingRescheduledMail($booking, '2025-05-01', '08:00:00');

        $this->assertEquals('emails.booking-rescheduled', $mail->build()->view);
    }

    /** @test */
    public function booking_rescheduled_mail_menyimpan_old_date(): void
    {
        $customer = Customer::factory()->create();
        $booking  = Booking::factory()->create(['customer_id' => $customer->id]);

        $mail = new BookingRescheduledMail($booking, '2025-05-01', '08:00:00');

        $this->assertEquals('2025-05-01', $mail->oldDate);
    }

    /** @test */
    public function booking_rescheduled_mail_menyimpan_old_time(): void
    {
        $customer = Customer::factory()->create();
        $booking  = Booking::factory()->create(['customer_id' => $customer->id]);

        $mail = new BookingRescheduledMail($booking, '2025-05-01', '08:00:00');

        $this->assertEquals('08:00:00', $mail->oldTime);
    }

    /** @test */
    public function booking_rescheduled_mail_memuat_relasi_customer(): void
    {
        $customer = Customer::factory()->create(['name' => 'Budi Santoso']);
        $booking  = Booking::factory()->create(['customer_id' => $customer->id]);

        $mail = new BookingRescheduledMail($booking, '2025-05-01', '08:00:00');

        $this->assertNotNull($mail->booking->customer);
        $this->assertEquals('Budi Santoso', $mail->booking->customer->name);
    }

    /** @test */
    public function booking_rescheduled_mail_menyimpan_data_booking_baru(): void
    {
        $customer = Customer::factory()->create();
        $newDate  = '2025-06-15';
        $newTime  = '14:00:00';

        $booking = Booking::factory()->create([
            'customer_id'  => $customer->id,
            'booking_date' => $newDate,
            'booking_time' => $newTime,
        ]);

        $mail = new BookingRescheduledMail($booking, '2025-05-01', '08:00:00');

        $this->assertEquals($newDate, $mail->booking->booking_date);
        $this->assertEquals($newTime, $mail->booking->booking_time);
        $this->assertEquals('2025-05-01', $mail->oldDate);
        $this->assertEquals('08:00:00', $mail->oldTime);
    }
}