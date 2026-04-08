<?php

namespace Tests\Feature;

use App\Mail\BookingCancelledMail;
use App\Mail\BookingRescheduledMail;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        Setting::set('capacity', 2);
    }

    private function asAdmin()
    {
        return $this->actingAs($this->admin);
    }

    /** @test */
    public function admin_bisa_mengakses_dashboard(): void
    {
        $response = $this->asAdmin()->get(route('admin.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin');
        $response->assertViewHas(['bookings', 'capacity', 'today', 'stats', 'chartData']);
    }

    /** @test */
    public function guest_tidak_bisa_mengakses_dashboard(): void
    {
        $response = $this->get(route('admin.index'));
        $response->assertRedirect();
        $response->assertStatus(302);
    }

    /** @test */
    public function user_biasa_tidak_bisa_mengakses_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->get(route('admin.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function dashboard_menampilkan_bookings_3_hari_terakhir(): void
    {
        $customer = Customer::factory()->create();
        Booking::factory()->create([
            'customer_id'  => $customer->id,
            'booking_date' => now()->toDateString(),
        ]);
        Booking::factory()->create([
            'customer_id'  => $customer->id,
            'booking_date' => now()->subDays(5)->toDateString(),
        ]);

        $response = $this->asAdmin()->get(route('admin.index'));

        $bookings = $response->viewData('bookings');
        $this->assertEquals(1, $bookings->total());
    }

    /** @test */
    public function stats_menghitung_jumlah_booking_hari_ini_dengan_benar(): void
    {
        $customer = Customer::factory()->create();

        Booking::factory()->create(['customer_id' => $customer->id, 'booking_date' => now()->toDateString(), 'status' => 'active']);
        Booking::factory()->create(['customer_id' => $customer->id, 'booking_date' => now()->toDateString(), 'status' => 'on-progress']);
        Booking::factory()->create(['customer_id' => $customer->id, 'booking_date' => now()->toDateString(), 'status' => 'completed']);
        Booking::factory()->create(['customer_id' => $customer->id, 'booking_date' => now()->subDay()->toDateString(), 'status' => 'active']);

        $response = $this->asAdmin()->get(route('admin.index'));
        $stats    = $response->viewData('stats');

        $this->assertEquals(3, $stats['today']);
        $this->assertEquals(1, $stats['active']);
        $this->assertEquals(1, $stats['on_progress']);
        $this->assertEquals(1, $stats['completed']);
    }

    /** @test */
    public function chart_data_berisi_struktur_week_dan_month(): void
    {
        $response  = $this->asAdmin()->get(route('admin.index'));
        $chartData = $response->viewData('chartData');

        $this->assertArrayHasKey('week', $chartData);
        $this->assertArrayHasKey('month', $chartData);
        $this->assertArrayHasKey('labels', $chartData['week']);
        $this->assertArrayHasKey('total', $chartData['week']);
        $this->assertArrayHasKey('done', $chartData['week']);

        // Week = 7 label, month = 30 label
        $this->assertCount(7, $chartData['week']['labels']);
        $this->assertCount(30, $chartData['month']['labels']);
    }

    /** @test */
    public function live_data_mengembalikan_struktur_json_yang_benar(): void
    {
        $response = $this->asAdmin()->getJson(route('admin.live-data'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'stats'    => ['today', 'active', 'on_progress', 'completed', 'hourly'],
            'bookings',
        ]);
    }

    /** @test */
    public function live_data_booking_memuat_field_yang_diperlukan(): void
    {
        $customer = Customer::factory()->create();
        Booking::factory()->create([
            'customer_id'  => $customer->id,
            'booking_date' => now()->toDateString(),
        ]);

        $response = $this->asAdmin()->getJson(route('admin.live-data'));

        $response->assertJsonStructure([
            'bookings' => [[
                'id', 'customer_name', 'customer_phone',
                'booking_date', 'booking_time', 'status', 'updated_at',
            ]],
        ]);
    }

    /** @test */
    public function live_data_booking_time_diformat_5_karakter(): void
    {
        $customer = Customer::factory()->create();
        Booking::factory()->create([
            'customer_id'  => $customer->id,
            'booking_date' => now()->toDateString(),
            'booking_time' => '09:00:00',
        ]);

        $response = $this->asAdmin()->getJson(route('admin.live-data'));

        $bookingTime = $response->json('bookings.0.booking_time');
        $this->assertEquals('09:00', $bookingTime);
    }

    /** @test */
    public function admin_bisa_memulai_layanan(): void
    {
        $booking = Booking::factory()->active()->create();

        $this->asAdmin()
            ->post(route('admin.start', $booking->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('bookings', [
            'id'     => $booking->id,
            'status' => 'on-progress',
        ]);
    }

    /** @test */
    public function start_dengan_id_tidak_valid_mengembalikan_404(): void
    {
        $this->asAdmin()
            ->post(route('admin.start', 99999))
            ->assertStatus(404);
    }

    /** @test */
    public function admin_bisa_menyelesaikan_layanan(): void
    {
        $booking = Booking::factory()->onProgress()->create();

        $this->asAdmin()
            ->post(route('admin.complete', $booking->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('bookings', [
            'id'     => $booking->id,
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function complete_dengan_id_tidak_valid_mengembalikan_404(): void
    {
        $this->asAdmin()
            ->post(route('admin.complete', 99999))
            ->assertStatus(404);
    }

    /** @test */
    public function admin_bisa_membatalkan_booking(): void
    {
        Mail::fake();

        $customer = Customer::factory()->create();
        $booking  = Booking::factory()->active()->create(['customer_id' => $customer->id]);

        $this->asAdmin()
            ->post(route('admin.cancel', $booking->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
    }

    /** @test */
    public function email_pembatalan_dikirim_setelah_cancel(): void
    {
        Mail::fake();

        $customer = Customer::factory()->create();
        $booking  = Booking::factory()->active()->create(['customer_id' => $customer->id]);

        $this->asAdmin()->post(route('admin.cancel', $booking->id));

        Mail::assertQueued(BookingCancelledMail::class, function ($mail) use ($customer) {
            return $mail->customerName === $customer->name;
        });
    }

    /** @test */
    public function cancel_dengan_id_tidak_valid_mengembalikan_404(): void
    {
        $this->asAdmin()
            ->post(route('admin.cancel', 99999))
            ->assertStatus(404);
    }

    /** @test */
    public function kegagalan_mail_tidak_menggagalkan_proses_cancel(): void
    {
        Mail::shouldReceive('to')->andThrow(new \Exception('SMTP Error'));

        $customer = Customer::factory()->create();
        $booking  = Booking::factory()->active()->create(['customer_id' => $customer->id]);

        // Proses cancel tetap berhasil meski mail error
        $this->asAdmin()
            ->post(route('admin.cancel', $booking->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
    }

    /** @test */
    public function admin_bisa_reschedule_booking_ke_slot_tersedia(): void
    {
        Mail::fake();

        $customer  = Customer::factory()->create();
        $booking   = Booking::factory()->active()->create([
            'customer_id'  => $customer->id,
            'booking_date' => now()->addDay()->toDateString(),
            'booking_time' => '09:00:00',
        ]);

        $newDate = now()->addDays(3)->toDateString();

        $this->asAdmin()
            ->put(route('admin.reschedule', $booking->id), [
                'booking_date' => $newDate,
                'booking_time' => '14:00',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('bookings', [
            'id'           => $booking->id,
            'booking_date' => $newDate,
            'booking_time' => '14:00:00',
        ]);
    }

    /** @test */
    public function email_reschedule_dikirim_setelah_reschedule_berhasil(): void
    {
        Mail::fake();

        $customer = Customer::factory()->create();
        $booking  = Booking::factory()->active()->create([
            'customer_id'  => $customer->id,
            'booking_date' => now()->addDay()->toDateString(),
            'booking_time' => '09:00:00',
        ]);

        $this->asAdmin()->put(route('admin.reschedule', $booking->id), [
            'booking_date' => now()->addDays(3)->toDateString(),
            'booking_time' => '11:00',
        ]);

        Mail::assertQueued(BookingRescheduledMail::class);
    }

    /** @test */
    public function reschedule_menyimpan_jadwal_lama_di_mail(): void
    {
        Mail::fake();

        $customer = Customer::factory()->create();
        $booking  = Booking::factory()->active()->create([
            'customer_id'  => $customer->id,
            'booking_date' => now()->addDay()->toDateString(),
            'booking_time' => '09:00:00',
        ]);

        $oldDate = $booking->booking_date;
        $oldTime = $booking->booking_time;

        $this->asAdmin()->put(route('admin.reschedule', $booking->id), [
            'booking_date' => now()->addDays(3)->toDateString(),
            'booking_time' => '11:00',
        ]);

        Mail::assertQueued(BookingRescheduledMail::class, function ($mail) use ($oldDate, $oldTime) {
            return $mail->oldDate === $oldDate && $mail->oldTime === $oldTime;
        });
    }

    /** @test */
    public function reschedule_gagal_jika_slot_tujuan_penuh(): void
    {
        Mail::fake();

        $customer = Customer::factory()->create();
        $booking  = Booking::factory()->active()->create(['customer_id' => $customer->id]);

        $fullDate = now()->addDays(5)->toDateString();

        Booking::factory()->count(2)->create([
            'booking_date' => $fullDate,
            'booking_time' => '14:00:00',
            'status'       => 'active',
        ]);

        $this->asAdmin()
            ->put(route('admin.reschedule', $booking->id), [
                'booking_date' => $fullDate,
                'booking_time' => '14:00',
            ])
            ->assertSessionHasErrors('msg');

        // Booking tidak berubah
        $this->assertDatabaseHas('bookings', [
            'id'           => $booking->id,
            'booking_date' => $booking->booking_date,
            'booking_time' => $booking->booking_time,
        ]);

        Mail::assertNothingQueued();
    }

    /** @test */
    public function reschedule_booking_itu_sendiri_tidak_dihitung_dalam_kapasitas_slot_baru(): void
    {
        Mail::fake();

        $customer = Customer::factory()->create();
        $newDate  = now()->addDays(3)->toDateString();

        $booking = Booking::factory()->active()->create([
            'customer_id'  => $customer->id,
            'booking_date' => $newDate,
            'booking_time' => '10:00:00',
        ]);

        Booking::factory()->create([
            'booking_date' => $newDate,
            'booking_time' => '10:00:00',
            'status'       => 'active',
        ]);

        $this->asAdmin()
            ->put(route('admin.reschedule', $booking->id), [
                'booking_date' => $newDate,
                'booking_time' => '10:00',
            ])
            ->assertSessionHas('success');
    }

    /** @test */
    public function reschedule_ditolak_jika_tanggal_di_masa_lalu(): void
    {
        $booking = Booking::factory()->active()->create();

        $this->asAdmin()
            ->put(route('admin.reschedule', $booking->id), [
                'booking_date' => now()->subDay()->toDateString(),
                'booking_time' => '09:00',
            ])
            ->assertSessionHasErrors('booking_date');
    }

    /** @test */
    public function reschedule_dengan_id_tidak_valid_mengembalikan_404(): void
    {
        $this->asAdmin()
            ->put(route('admin.reschedule', 99999), [
                'booking_date' => now()->addDay()->toDateString(),
                'booking_time' => '09:00',
            ])
            ->assertStatus(404);
    }

    /** @test */
    public function admin_bisa_mengubah_kapasitas(): void
    {
        $this->asAdmin()
            ->post(route('admin.settings'), ['capacity' => 5])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEquals(5, (int) Setting::get('capacity'));
    }

    /** @test */
    public function kapasitas_harus_angka_minimal_1(): void
    {
        $this->asAdmin()
            ->post(route('admin.settings'), ['capacity' => 0])
            ->assertSessionHasErrors('capacity');
    }

    /** @test */
    public function kapasitas_maksimal_20(): void
    {
        $this->asAdmin()
            ->post(route('admin.settings'), ['capacity' => 21])
            ->assertSessionHasErrors('capacity');
    }

    /** @test */
    public function kapasitas_harus_integer_bukan_string(): void
    {
        $this->asAdmin()
            ->post(route('admin.settings'), ['capacity' => 'banyak'])
            ->assertSessionHasErrors('capacity');
    }

    /** @test */
    public function available_slots_mengembalikan_semua_slot_jika_kosong(): void
    {
        $date     = now()->addDays(2)->toDateString();
        $response = $this->asAdmin()
            ->getJson(route('admin.available-slots', ['date' => $date]));

        $response->assertStatus(200);
        $slots = $response->json();
        $this->assertIsArray($slots);
        $this->assertNotEmpty($slots);
    }

    /** @test */
    public function available_slots_tidak_mengembalikan_slot_yang_penuh(): void
    {
        $date = now()->addDays(2)->toDateString();

        Booking::factory()->count(2)->create([
            'booking_date' => $date,
            'booking_time' => '08:00:00',
            'status'       => 'active',
        ]);

        $response = $this->asAdmin()
            ->getJson(route('admin.available-slots', ['date' => $date]));

        $slots = $response->json();
        $this->assertNotContains('08:00', $slots);
        $this->assertContains('09:00', $slots);
    }

    /** @test */
    public function available_slots_dengan_exclude_id_mengabaikan_booking_tersebut(): void
    {
        $date     = now()->addDays(2)->toDateString();
        $customer = Customer::factory()->create();

        $booking = Booking::factory()->create([
            'customer_id'  => $customer->id,
            'booking_date' => $date,
            'booking_time' => '08:00:00',
            'status'       => 'active',
        ]);

        Booking::factory()->create([
            'booking_date' => $date,
            'booking_time' => '08:00:00',
            'status'       => 'active',
        ]);

        $withoutExclude = $this->asAdmin()
            ->getJson(route('admin.available-slots', ['date' => $date]))
            ->json();
        $this->assertNotContains('08:00', $withoutExclude);

        $withExclude = $this->asAdmin()
            ->getJson(route('admin.available-slots', ['date' => $date, 'exclude_id' => $booking->id]))
            ->json();
        $this->assertContains('08:00', $withExclude);
    }

    /** @test */
    public function available_slots_tidak_mengembalikan_slot_yang_sudah_lewat(): void
    {
        $today    = now()->toDateString();
        $response = $this->asAdmin()
            ->getJson(route('admin.available-slots', ['date' => $today]));

        $slots = $response->json();

        foreach ($slots as $slot) {
            $slotTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $today . ' ' . $slot);
            $this->assertTrue($slotTime->isFuture(), "Slot $slot seharusnya di masa depan");
        }
    }
}