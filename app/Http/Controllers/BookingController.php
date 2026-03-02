<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use App\Mail\BookingSuccessMail;
use Illuminate\Support\Facades\Log;


class BookingController extends Controller
{
    private $timeSlots = ['08:00','09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00'];

    public function index(Request $request) {
        $date = $request->date ?? now()->toDateString();
        
        // Mengambil slot yang terisi (ditambah pengecekan format waktu agar sinkron)
        $bookedSlots = Booking::where('booking_date', $date)
            ->whereIn('status', ['active', 'on-progress', 'completed'])
            ->pluck('booking_time')
            ->map(function($time) {
                return substr($time, 0, 5);
            })->toArray();


            $successBooking = null;

            if (session('booking_success_id')) {
                $successBooking = Booking::with('customer')
                    ->find(session('booking_success_id'));
            }


        return view('home', compact('date','bookedSlots','successBooking'))->with('timeSlots', $this->timeSlots);
    }

    public function store(Request $request) {
            if (auth()->check()) {
                $name = auth()->user()->name;
                $phone = auth()->user()->phone;
                $email = auth()->user()->email;

                if (!$phone) {
                    return back()->withErrors(['msg' => 'Nomor HP profil kosong!']);
                }
            } else {
                $request->validate([
                    'name' => 'required',
                    'phone' => 'required',
                    'email' => 'required|email'
                ]);

                $name = $request->name;
                $phone = $request->phone;
                $email = $request->email;
            }

            $request->validate([
                'booking_date' => 'required',
                'booking_time' => 'required',
            ]);
            $isBooked = Booking::where('booking_date', $request->booking_date)
                ->whereRaw("TIME_FORMAT(booking_time, '%H:%i') = ?", [$request->booking_time])
                ->whereIn('status', ['active', 'on-progress', 'completed'])
                ->exists();

            if ($isBooked) {
                return back()->withErrors([
                    'msg' => 'Maaf, slot ini baru saja dipesan orang lain. Pilih jam lain!'
                ]);
            }

            try {

                $customer = Customer::firstOrCreate(
                    ['phone' => $phone],
                    [
                        'name' => $name,
                        'email' => $email
                    ]
                );

                $booking = Booking::create([
                    'customer_id' => $customer->id,
                    'booking_date' => $request->booking_date,
                    'booking_time' => $request->booking_time . ':00',
                    'status' => 'active'
                ]);

                Mail::to($customer->email)
                    ->send(new BookingSuccessMail($booking));

                return redirect()
                    ->route('home')
                    ->with('booking_success_id', $booking->id);

            } catch (\Exception $e) {
                Log::error($e->getMessage());

                return back()->withErrors([
                    'msg' => 'Terjadi kesalahan sistem. Silakan coba lagi.'
                ]);
            }
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
        Booking::findOrFail($id)->delete();
        return back()->with('success', 'Booking dibatalkan.');
    }

    public function complete($id) {
        Booking::findOrFail($id)->update(['status' => 'completed']);
        return back()->with('success', 'Layanan telah selesai!');
    }
}