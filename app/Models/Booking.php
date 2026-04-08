<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Booking extends Model
{
    use HasFactory;
    protected $fillable = [
        'customer_id',
        'user_id',
        'booking_date',
        'booking_time',
        'status'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
