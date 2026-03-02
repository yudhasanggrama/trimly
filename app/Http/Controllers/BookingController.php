<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Customer;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    private $timeSlots = ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'];

    public function index(Request $request) {
        $date = $request->date ?? now()->toDateString();
        
        // Mengambil slot yang terisi (ditambah pengecekan format waktu agar sinkron)
        $bookedSlots = Booking::where('booking_date', $date)
            ->whereIn('status', ['active', 'on-progress', 'completed'])
            ->pluck('booking_time')
            ->map(function($time) {
                return substr($time, 0, 5);
            })->toArray();

        return view('home', compact('date', 'bookedSlots'))->with('timeSlots', $this->timeSlots);
    }

    public function store(Request $request) {
        if (auth()->check()) {
            $name = auth()->user()->name;
            $phone = auth()->user()->phone;
            
            // Proteksi jika user belum punya nomor HP (error yang tadi)
            if (!$phone) {
                return back()->withErrors(['msg' => 'Nomor HP di profil Anda kosong. Silakan lengkapi profil.']);
            }
        } else {
            $request->validate(['name' => 'required', 'phone' => 'required']);
            $name = $request->name;
            $phone = $request->phone;
        }

        $request->validate(['booking_date' => 'required|date', 'booking_time' => 'required']);

        // Proteksi double booking
        $exists = Booking::where('booking_date', $request->booking_date)
            ->where('booking_time', $request->booking_time)
            ->where('status', '!=', 'cancelled')->exists();

        if ($exists) return back()->withErrors(['msg' => 'Slot sudah terisi!']);

        $customer = Customer::firstOrCreate(['phone' => $phone], ['name' => $name]);

        $booking = Booking::create([
            'customer_id' => $customer->id,
            'booking_date' => $request->booking_date,
            'booking_time' => $request->booking_time,
            'status' => 'active'
        ]);

        // DIUBAH: Redirect ke halaman sukses membawa ID booking
        return redirect()->route('booking.success', $booking->id)->with('success', 'Booking berhasil!');
    }

    // Fungsi baru untuk menampilkan Tiket Digital
    public function success($id) {
        $booking = Booking::with('customer')->findOrFail($id);
        return view('success', compact('booking'));
    }

    public function admin() {
        if(auth()->user()->role !== 'admin') return redirect('/');
        $bookings = Booking::with('customer')->orderBy('booking_date', 'desc')->get();
        return view('admin', compact('bookings'));
    }

    public function start($id) {
        Booking::findOrFail($id)->update(['status' => 'on-progress']);
        return back()->with('success', 'Layanan dimulai!');
    }

    public function cancel($id) {
        Booking::findOrFail($id)->update(['status' => 'cancelled']);
        return back()->with('success', 'Booking dibatalkan.');
    }

    public function complete($id) {
        Booking::findOrFail($id)->update(['status' => 'completed']);
        return back()->with('success', 'Layanan telah selesai!');
    }
}