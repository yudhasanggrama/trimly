<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingCapacityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::set('capacity', 2);
    }

    /** @test */
    public function setting_get_mengembalikan_nilai_default_jika_belum_diset(): void
    {
        $value = Setting::get('tidak_ada_key', 99);

        $this->assertEquals(99, $value);
    }

    /** @test */
    public function setting_set_dan_get_bekerja_dengan_benar(): void
    {
        Setting::set('capacity', 5);

        $this->assertEquals(5, (int) Setting::get('capacity'));
    }

    /** @test */
    public function setting_bisa_diupdate(): void
    {
        Setting::set('capacity', 3);
        Setting::set('capacity', 7);

        $this->assertEquals(7, (int) Setting::get('capacity'));
    }

    /** @test */
    public function booking_baru_memiliki_status_active(): void
    {
        $customer = Customer::factory()->create();
        $booking  = Booking::factory()->create(['customer_id' => $customer->id, 'status' => 'active']);

        $this->assertEquals('active', $booking->fresh()->status);
    }

    /** @test */
    public function status_booking_bisa_diubah_ke_on_progress(): void
    {
        $booking = Booking::factory()->active()->create();
        $booking->update(['status' => 'on-progress']);

        $this->assertEquals('on-progress', $booking->fresh()->status);
    }

    /** @test */
    public function status_booking_bisa_diubah_ke_completed(): void
    {
        $booking = Booking::factory()->onProgress()->create();
        $booking->update(['status' => 'completed']);

        $this->assertEquals('completed', $booking->fresh()->status);
    }

    /** @test */
    public function booking_bisa_dihapus(): void
    {
        $booking = Booking::factory()->active()->create();
        $id      = $booking->id;

        $booking->delete();

        $this->assertDatabaseMissing('bookings', ['id' => $id]);
    }

    /** @test */
    public function query_menghitung_booking_aktif_per_slot_dengan_benar(): void
    {
        $date = now()->addDay()->toDateString();

        Booking::factory()->count(2)->create([
            'booking_date' => $date,
            'booking_time' => '09:00:00',
            'status'       => 'active',
        ]);

        Booking::factory()->create([
            'booking_date' => $date,
            'booking_time' => '09:00:00',
            'status'       => 'completed',
        ]);

        $count = Booking::where('booking_date', $date)
            ->where('booking_time', '09:00:00')
            ->whereIn('status', ['active', 'on-progress'])
            ->count();

        $this->assertEquals(2, $count);
    }

    /** @test */
    public function query_on_progress_dihitung_dalam_kapasitas(): void
    {
        $date = now()->addDay()->toDateString();

        Booking::factory()->create([
            'booking_date' => $date,
            'booking_time' => '10:00:00',
            'status'       => 'on-progress',
        ]);

        $count = Booking::where('booking_date', $date)
            ->where('booking_time', '10:00:00')
            ->whereIn('status', ['active', 'on-progress'])
            ->count();

        $this->assertEquals(1, $count);
    }

    /** @test */
    public function slot_dianggap_penuh_jika_count_sama_dengan_kapasitas(): void
    {
        $date     = now()->addDay()->toDateString();
        $capacity = (int) Setting::get('capacity', 1);

        Booking::factory()->count($capacity)->create([
            'booking_date' => $date,
            'booking_time' => '11:00:00',
            'status'       => 'active',
        ]);

        $bookedCount = Booking::where('booking_date', $date)
            ->where('booking_time', '11:00:00')
            ->whereIn('status', ['active', 'on-progress'])
            ->count();

        $this->assertGreaterThanOrEqual($capacity, $bookedCount);
    }

    /** @test */
    public function slot_dianggap_tersedia_jika_count_kurang_dari_kapasitas(): void
    {
        $date     = now()->addDay()->toDateString();
        $capacity = (int) Setting::get('capacity', 1);

        // Hanya 1 booking (kapasitas 2)
        Booking::factory()->create([
            'booking_date' => $date,
            'booking_time' => '13:00:00',
            'status'       => 'active',
        ]);

        $bookedCount = Booking::where('booking_date', $date)
            ->where('booking_time', '13:00:00')
            ->whereIn('status', ['active', 'on-progress'])
            ->count();

        $this->assertLessThan($capacity, $bookedCount);
    }

    /** @test */
    public function booking_memiliki_relasi_ke_customer(): void
    {
        $customer = Customer::factory()->create();
        $booking  = Booking::factory()->create(['customer_id' => $customer->id]);

        $this->assertInstanceOf(Customer::class, $booking->customer);
        $this->assertEquals($customer->id, $booking->customer->id);
    }

    /** @test */
    public function customer_memiliki_relasi_ke_bookings(): void
    {
        $customer = Customer::factory()->create();
        Booking::factory()->count(3)->create(['customer_id' => $customer->id]);

        $this->assertCount(3, $customer->bookings);
    }

    /** @test */
    public function customer_firstorcreate_memakai_data_yang_sudah_ada_jika_phone_sama(): void
    {
        $existing = Customer::factory()->create([
            'phone' => '08111111111',
            'name'  => 'Nama Lama',
        ]);

        $result = Customer::firstOrCreate(
            ['phone' => '08111111111'],
            ['name' => 'Nama Baru', 'email' => 'baru@example.com']
        );

        $this->assertEquals($existing->id, $result->id);
        $this->assertEquals('Nama Lama', $result->name);
        $this->assertDatabaseCount('customers', 1);
    }

    /** @test */
    public function customer_firstorcreate_membuat_baru_jika_phone_berbeda(): void
    {
        Customer::factory()->create(['phone' => '08111111111']);

        Customer::firstOrCreate(
            ['phone' => '08222222222'],
            ['name' => 'Baru', 'email' => 'baru@example.com']
        );

        $this->assertDatabaseCount('customers', 2);
    }

    /** @test */
    public function booking_time_disimpan_dengan_format_detik(): void
    {
        $customer = Customer::factory()->create();
        $booking  = Booking::create([
            'customer_id'  => $customer->id,
            'booking_date' => now()->addDay()->toDateString(),
            'booking_time' => '09:00' . ':00',
            'status'       => 'active',
        ]);

        $this->assertEquals('09:00:00', $booking->fresh()->booking_time);
    }

    /** @test */
    public function booking_time_5_karakter_diambil_dari_substr(): void
    {
        $fullTime   = '09:00:00';
        $shortTime  = substr($fullTime, 0, 5);

        $this->assertEquals('09:00', $shortTime);
    }

    /** @test */
    public function booked_slots_query_mengelompokkan_berdasarkan_waktu(): void
    {
        $date     = now()->addDay()->toDateString();
        $capacity = 2;

        Booking::factory()->count(2)->create([
            'booking_date' => $date,
            'booking_time' => '09:00:00',
            'status'       => 'active',
        ]);

        Booking::factory()->create([
            'booking_date' => $date,
            'booking_time' => '10:00:00',
            'status'       => 'active',
        ]);

        $bookedSlots = Booking::where('booking_date', $date)
            ->whereIn('status', ['active', 'on-progress'])
            ->select('booking_time', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->groupBy('booking_time')
            ->having('total', '>=', $capacity)
            ->pluck('booking_time')
            ->map(fn($time) => substr($time, 0, 5))
            ->toArray();

        $this->assertContains('09:00', $bookedSlots);
        $this->assertNotContains('10:00', $bookedSlots);
    }

    /** @test */
    public function stats_query_menghitung_status_dengan_benar(): void
    {
        $today    = now()->toDateString();
        $customer = Customer::factory()->create();

        Booking::factory()->count(2)->create(['customer_id' => $customer->id, 'booking_date' => $today, 'status' => 'active']);
        Booking::factory()->count(1)->create(['customer_id' => $customer->id, 'booking_date' => $today, 'status' => 'on-progress']);
        Booking::factory()->count(3)->create(['customer_id' => $customer->id, 'booking_date' => $today, 'status' => 'completed']);

        $raw = Booking::whereDate('booking_date', $today)
            ->selectRaw("
                count(*) as total,
                count(case when status = 'active' then 1 end) as active,
                count(case when status = 'on-progress' then 1 end) as on_progress,
                count(case when status = 'completed' then 1 end) as completed
            ")
            ->first();

        $this->assertEquals(6, $raw->total);
        $this->assertEquals(2, $raw->active);
        $this->assertEquals(1, $raw->on_progress);
        $this->assertEquals(3, $raw->completed);
    }
}