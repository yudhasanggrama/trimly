<?php

namespace App\Http\Controllers;

use App\Mail\BookingSuccessMail;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BookingController extends Controller
{
    private $timeSlots = [
        '08:00', '09:00', '10:00', '11:00',
        '13:00', '14:00', '15:00', '16:00',
        '17:00', '18:00', '19:00', '20:00',
    ];

    public function index(Request $request)
    {
        $date     = $request->date ?? now()->toDateString();
        $capacity = (int) Setting::get('capacity', 1);

        $bookedSlots = Booking::where('booking_date', $date)
            ->whereIn('status', ['active', 'on-progress'])
            ->select('booking_time', DB::raw('count(*) as total'))
            ->groupBy('booking_time')
            ->having('total', '>=', $capacity)
            ->pluck('booking_time')
            ->map(fn($time) => substr($time, 0, 5))
            ->toArray();

        if ($request->has('json')) {
            return response()->json([
                'bookedSlots' => $bookedSlots,
                'now'         => now()->format('H:i'),
            ]);
        }

        $successBooking = null;
        if (session('booking_success_id')) {
            $successBooking = Booking::with('customer')
                ->find(session('booking_success_id'));
        }

        return view('home', compact('date', 'bookedSlots', 'successBooking', 'capacity'))
            ->with('timeSlots', $this->timeSlots);
    }

    public function store(Request $request)
    {
        if (auth()->check()) {
            $name  = auth()->user()->name;
            $phone = auth()->user()->phone;
            $email = auth()->user()->email;

            if (!$phone) {
                return back()->withErrors(['msg' => 'Nomor HP profil kosong!']);
            }
        } else {
            $request->validate([
                'name'  => 'required',
                'phone' => 'required',
                'email' => 'required|email',
            ]);

            $name  = $request->name;
            $phone = $request->phone;
            $email = $request->email;
        }

        $request->validate([
            'booking_date' => 'required|date|after_or_equal:today',
            'booking_time' => 'required',
        ]);

        $capacity = (int) Setting::get('capacity', 1);

        try {
            DB::beginTransaction();

            $bookedCount = Booking::where('booking_date', $request->booking_date)
                ->where('booking_time', $request->booking_time . ':00')
                ->whereIn('status', ['active', 'on-progress'])
                ->lockForUpdate()
                ->count();

            if ($bookedCount >= $capacity) {
                DB::rollBack();
                return back()->withErrors(['msg' => 'Maaf, slot ini sudah penuh. Pilih jam lain!']);
            }

            $existingBooking = Booking::where('booking_date', $request->booking_date)
                ->where('booking_time', $request->booking_time . ':00')
                ->whereIn('status', ['active', 'on-progress'])
                ->whereHas('customer', fn($q) => $q->where('email', $email)->orWhere('phone', $phone))
                ->lockForUpdate()
                ->first();

            if ($existingBooking) {
                DB::rollBack();
                return back()->withErrors([
                    'msg' => 'Kamu masih memiliki booking aktif pada ' .
                        Carbon::parse($existingBooking->booking_date)->isoFormat('D MMM YYYY') .
                        ' jam ' . substr($existingBooking->booking_time, 0, 5) .
                        '. Tunggu hingga selesai atau hubungi admin.',
                ]);
            }

            $customer = Customer::firstOrCreate(
                ['phone' => $phone],
                ['name' => $name, 'email' => $email]
            );

            $booking = Booking::create([
                'customer_id'  => $customer->id,
                'booking_date' => $request->booking_date,
                'booking_time' => $request->booking_time . ':00',
                'status'       => 'active',
            ]);

            DB::commit();

            try {
                Mail::to($customer->email)->queue(new BookingSuccessMail($booking));
            } catch (\Exception $mailError) {
                Log::error('Mail error: ' . $mailError->getMessage());
            }

            return redirect()->route('home')->with('booking_success_id', $booking->id);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Booking error: ' . $e->getMessage());

            return back()->withErrors(['msg' => 'Terjadi kesalahan sistem.']);
        }
    }
}