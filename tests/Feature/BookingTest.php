<?php

namespace Tests\Feature;

use App\Mail\BookingSuccessMail;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush(); // ← tambahkan ini
        Setting::set('capacity', 2);
    }

    /** @test */
    // public function halaman_booking_bisa_diakses_publik(): void
    // {
    //     $response = $this->get(route('home'));

    //     $response->assertStatus(200);
    //     $response->assertViewIs('home');
    //     $response->assertViewHas(['date', 'bookedSlots', 'capacity']);
    //     $response->assertViewHas('timeSlots');
    // }

    // /** @test */
    // public function halaman_booking_menerima_parameter_date(): void
    // {
    //     $date     = now()->addDays(2)->toDateString();
    //     $response = $this->get(route('home', ['date' => $date]));

    //     $response->assertStatus(200);
    //     $response->assertViewHas('date', $date);
    // }

    // /** @test */
    // public function endpoint_json_mengembalikan_booked_slots_dan_now(): void
    // {
    //     $date = now()->addDay()->toDateString();
    //     Booking::factory()->count(2)->create([
    //         'booking_date' => $date,
    //         'booking_time' => '09:00:00',
    //         'status'       => 'active',
    //     ]);

    //     $response = $this->getJson(route('home', ['json' => 1, 'date' => $date]));

    //     $response->assertStatus(200);
    //     $response->assertJsonStructure(['bookedSlots', 'now']);
    //     $response->assertJsonFragment(['bookedSlots' => ['09:00']]);
    // }

    // /** @test */
    // public function endpoint_json_tidak_memuat_slot_yang_belum_penuh(): void
    // {
    //     $date = now()->addDay()->toDateString();
    //     Booking::factory()->create([
    //         'booking_date' => $date,
    //         'booking_time' => '10:00:00',
    //         'status'       => 'active',
    //     ]);

    //     $response = $this->getJson(route('home', ['json' => 1, 'date' => $date]));

    //     $bookedSlots = $response->json('bookedSlots');
    //     $this->assertNotContains('10:00', $bookedSlots);
    // }

    // /** @test */
    // public function endpoint_json_tidak_memuat_status_completed_sebagai_booked(): void
    // {
    //     $date = now()->addDay()->toDateString();

    //     Booking::factory()->count(2)->create([
    //         'booking_date' => $date,
    //         'booking_time' => '11:00:00',
    //         'status'       => 'completed',
    //     ]);

    //     $response = $this->getJson(route('home', ['json' => 1, 'date' => $date]));

    //     $bookedSlots = $response->json('bookedSlots');
    //     $this->assertNotContains('11:00', $bookedSlots);
    // }

    // /** @test */
    // public function success_booking_ditampilkan_dari_session(): void
    // {
    //     $customer = Customer::factory()->create();
    //     $booking  = Booking::factory()->create(['customer_id' => $customer->id]);

    //     $response = $this->withSession(['booking_success_id' => $booking->id])
    //         ->get(route('home'));

    //     $response->assertStatus(200);
    //     $response->assertViewHas('successBooking');
    // }

    // /** @test */
    // public function guest_berhasil_membuat_booking_baru(): void
    // {
    //     Mail::fake();

    //     $date = now()->addDay()->toDateString();

    //     $response = $this->post(route('booking.store'), [
    //         'name'         => 'Budi Santoso',
    //         'phone'        => '08123456789',
    //         'email'        => 'budi@example.com',
    //         'booking_date' => $date,
    //         'booking_time' => '09:00',
    //     ]);

    //     $response->assertRedirect(route('home'));
    //     $response->assertSessionHas('booking_success_id');

    //     $this->assertDatabaseHas('bookings', [
    //         'booking_date' => $date,
    //         'booking_time' => '09:00:00',
    //         'status'       => 'active',
    //     ]);

    //     $this->assertDatabaseHas('customers', [
    //         'email' => 'budi@example.com',
    //         'phone' => '08123456789',
    //     ]);

    //     Mail::assertQueued(BookingSuccessMail::class);
    // }

    // /** @test */
    // public function guest_booking_membuat_customer_baru_jika_belum_ada(): void
    // {
    //     Mail::fake();

    //     $this->assertDatabaseCount('customers', 0);

    //     $this->post(route('booking.store'), [
    //         'name'         => 'Ani Rahayu',
    //         'phone'        => '08111111111',
    //         'email'        => 'ani@example.com',
    //         'booking_date' => now()->addDay()->toDateString(),
    //         'booking_time' => '10:00',
    //     ]);

    //     $this->assertDatabaseCount('customers', 1);
    //     $this->assertDatabaseHas('customers', ['email' => 'ani@example.com']);
    // }

    // /** @test */
    // public function guest_booking_memakai_customer_yang_sudah_ada_jika_phone_sama(): void
    // {
    //     Mail::fake();

    //     $existing = Customer::factory()->create(['phone' => '08111111111']);

    //     $this->post(route('booking.store'), [
    //         'name'         => 'Nama Beda',
    //         'phone'        => '08111111111',
    //         'email'        => 'beda@example.com',
    //         'booking_date' => now()->addDay()->toDateString(),
    //         'booking_time' => '10:00',
    //     ]);

    //     $this->assertDatabaseCount('customers', 1);
    //     $this->assertDatabaseHas('bookings', ['customer_id' => $existing->id]);
    // }

    // /** @test */
    // public function user_login_berhasil_membuat_booking_tanpa_isi_form_identitas(): void
    // {
    //     Mail::fake();

    //     $user = User::factory()->withPhone('08199999999')->create();

    //     $response = $this->actingAs($user)->post(route('booking.store'), [
    //         'booking_date' => now()->addDay()->toDateString(),
    //         'booking_time' => '14:00',
    //     ]);

    //     $response->assertRedirect(route('home'));
    //     $this->assertDatabaseHas('customers', ['phone' => $user->phone]);
    // }

    // /** @test */
    // public function user_login_tanpa_phone_ditolak(): void
    // {
    //     $user = User::factory()->create(['phone' => null]);

    //     $response = $this->actingAs($user)->post(route('booking.store'), [
    //         'booking_date' => now()->addDay()->toDateString(),
    //         'booking_time' => '14:00',
    //     ]);

    //     $response->assertSessionHasErrors('msg');
    // }

    // /** @test */
    // public function booking_ditolak_jika_tanggal_di_masa_lalu(): void
    // {
    //     $response = $this->post(route('booking.store'), [
    //         'name'         => 'Test',
    //         'phone'        => '081234',
    //         'email'        => 'test@test.com',
    //         'booking_date' => now()->subDay()->toDateString(),
    //         'booking_time' => '09:00',
    //     ]);

    //     $response->assertSessionHasErrors('booking_date');
    //     $this->assertDatabaseCount('bookings', 0);
    // }

    // /** @test */
    // public function booking_ditolak_jika_waktu_kosong(): void
    // {
    //     $response = $this->post(route('booking.store'), [
    //         'name'         => 'Test',
    //         'phone'        => '081234',
    //         'email'        => 'test@test.com',
    //         'booking_date' => now()->addDay()->toDateString(),
    //         'booking_time' => '',
    //     ]);

    //     $response->assertSessionHasErrors('booking_time');
    // }

    // /** @test */
    // public function guest_booking_ditolak_jika_email_tidak_valid(): void
    // {
    //     $response = $this->post(route('booking.store'), [
    //         'name'         => 'Test',
    //         'phone'        => '081234',
    //         'email'        => 'bukan-email',
    //         'booking_date' => now()->addDay()->toDateString(),
    //         'booking_time' => '09:00',
    //     ]);

    //     $response->assertSessionHasErrors('email');
    // }

    /** @test */
    public function booking_ditolak_ketika_slot_sudah_penuh(): void
    {
        Mail::fake();

        $date = now()->addDay()->toDateString();
        Booking::factory()->count(2)->create([
            'booking_date' => $date,
            'booking_time' => '10:00:00',
            'status'       => 'active',
        ]);

        $response = $this->post(route('booking.store'), [
            'name'         => 'Baru',
            'phone'        => '08999999999',
            'email'        => 'baru@example.com',
            'booking_date' => $date,
            'booking_time' => '10:00',
        ]);

        $response->assertSessionHasErrors('msg');
        $this->assertDatabaseCount('bookings', 2);
        Mail::assertNothingQueued();
    }

    /** @test */
    public function slot_masih_bisa_diisi_jika_belum_penuh(): void
    {
        Mail::fake();

        $date = now()->addDay()->toDateString();
        Booking::factory()->create([
            'booking_date' => $date,
            'booking_time' => '10:00:00',
            'status'       => 'active',
        ]);

        $response = $this->post(route('booking.store'), [
            'name'         => 'Baru',
            'phone'        => '08999999999',
            'email'        => 'baru@example.com',
            'booking_date' => $date,
            'booking_time' => '10:00',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertDatabaseCount('bookings', 2);
    }

    /** @test */
    public function booking_completed_tidak_dihitung_dalam_kapasitas(): void
    {
        Mail::fake();

        $date = now()->addDay()->toDateString();
        Booking::factory()->count(2)->create([
            'booking_date' => $date,
            'booking_time' => '15:00:00',
            'status'       => 'completed',
        ]);

        $response = $this->post(route('booking.store'), [
            'name'         => 'Baru',
            'phone'        => '08999999999',
            'email'        => 'baru@example.com',
            'booking_date' => $date,
            'booking_time' => '15:00',
        ]);

        $response->assertRedirect(route('home'));
    }

//     /** @test */
//     public function ditolak_jika_email_sama_masih_punya_booking_aktif_di_slot_yang_sama(): void
//     {
//         Mail::fake();

//         $date     = now()->addDay()->toDateString();
//         $customer = Customer::factory()->create([
//             'email' => 'budi@example.com',
//             'phone' => '08123456789',
//         ]);

//         Booking::factory()->create([
//             'customer_id'  => $customer->id,
//             'booking_date' => $date,
//             'booking_time' => '09:00:00',
//             'status'       => 'active',
//         ]);

//         $response = $this->post(route('booking.store'), [
//             'name'         => 'Budi',
//             'email'        => 'budi@example.com',
//             'phone'        => '08123456789',
//             'booking_date' => $date,
//             'booking_time' => '09:00',
//         ]);

//         $response->assertSessionHasErrors('msg');
//         $this->assertDatabaseCount('bookings', 1);
//         Mail::assertNothingQueued();
//     }

//     /** @test */
//     public function ditolak_jika_phone_sama_masih_punya_booking_aktif(): void
//     {
//         Mail::fake();

//         $date     = now()->addDay()->toDateString();
//         $customer = Customer::factory()->create(['phone' => '08123456789']);

//         Booking::factory()->create([
//             'customer_id'  => $customer->id,
//             'booking_date' => $date,
//             'booking_time' => '09:00:00',
//             'status'       => 'active',
//         ]);

//         $response = $this->post(route('booking.store'), [
//             'name'         => 'Budi Lain',
//             'email'        => 'budilain@example.com',
//             'phone'        => '08123456789', // phone sama
//             'booking_date' => $date,
//             'booking_time' => '09:00',
//         ]);

//         $response->assertSessionHasErrors('msg');
//     }

//     /** @test */
//     public function bisa_booking_lagi_jika_booking_sebelumnya_sudah_completed(): void
//     {
//         Mail::fake();

//         $date     = now()->addDay()->toDateString();
//         $customer = Customer::factory()->create([
//             'email' => 'budi@example.com',
//             'phone' => '08123456789',
//         ]);

//         Booking::factory()->create([
//             'customer_id'  => $customer->id,
//             'booking_date' => $date,
//             'booking_time' => '09:00:00',
//             'status'       => 'completed', // sudah selesai
//         ]);

//         $response = $this->post(route('booking.store'), [
//             'name'         => 'Budi',
//             'email'        => 'budi@example.com',
//             'phone'        => '08123456789',
//             'booking_date' => $date,
//             'booking_time' => '09:00',
//         ]);

//         $response->assertRedirect(route('home'));
//     }

//     /** @test */
//     public function email_konfirmasi_dikirim_ke_customer_setelah_booking(): void
//     {
//         Mail::fake();

//         $this->post(route('booking.store'), [
//             'name'         => 'Budi',
//             'phone'        => '08123456789',
//             'email'        => 'budi@example.com',
//             'booking_date' => now()->addDay()->toDateString(),
//             'booking_time' => '09:00',
//         ]);

//         Mail::assertQueued(BookingSuccessMail::class, function ($mail) {
//             return true;
//         });
//     }

//     /** @test */
//     public function email_tidak_dikirim_jika_booking_gagal(): void
//     {
//         Mail::fake();

//         $date = now()->addDay()->toDateString();

//         Booking::factory()->count(2)->create([
//             'booking_date' => $date,
//             'booking_time' => '09:00:00',
//             'status'       => 'active',
//         ]);

//         $this->post(route('booking.store'), [
//             'name'         => 'Budi',
//             'phone'        => '08123456789',
//             'email'        => 'budi@example.com',
//             'booking_date' => $date,
//             'booking_time' => '09:00',
//         ]);

//         Mail::assertNothingQueued();
//     }
}