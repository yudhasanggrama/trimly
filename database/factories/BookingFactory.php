<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        return [
            'customer_id'  => Customer::factory(),
            'booking_date' => $this->faker->dateTimeBetween('today', '+30 days')->format('Y-m-d'),
            'booking_time' => $this->faker->randomElement([
                '08:00:00', '09:00:00', '10:00:00', '11:00:00',
                '13:00:00', '14:00:00', '15:00:00', '16:00:00',
                '17:00:00', '18:00:00', '19:00:00', '20:00:00',
            ]),
            'status' => 'active',
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => 'active']);
    }

    public function onProgress(): static
    {
        return $this->state(['status' => 'on-progress']);
    }

    public function completed(): static
    {
        return $this->state(['status' => 'completed']);
    }
}