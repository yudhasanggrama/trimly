<?php

namespace App\Http\Controllers;

use App\Mail\BookingCancelledMail;
use App\Mail\BookingRescheduledMail;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingSuccessMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    private $timeSlots = [
        '08:00', '09:00', '10:00', '11:00',
        '13:00', '14:00', '15:00', '16:00',
        '17:00', '18:00', '19:00', '20:00'
    ];

    public function index(Request $request)
    {
        $date = $request->date ?? now()->toDateString();
        $capacity = (int) Setting::get('capacity', 1);

        $bookedSlots = Booking::where('booking_date', $date)
            ->whereIn('status', ['active', 'on-progress'])
            ->select('booking_time', DB::raw('count(*) as total'))
            ->groupBy('booking_time')
            ->having('total', '>=', $capacity)
            ->pluck('booking_time')
            ->map(fn($time) => substr($time, 0, 5))
            ->toArray();

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
            'booking_date' => 'required|date',
            'booking_time' => 'required',
        ]);

        $capacity = (int) Setting::get('capacity', 1);

        $bookedCount = Booking::where('booking_date', $request->booking_date)
            ->whereRaw("TIME_FORMAT(booking_time, '%H:%i') = ?", [$request->booking_time])
            ->whereIn('status', ['active', 'on-progress'])
            ->count();

        if ($bookedCount >= $capacity) {
            return back()->withErrors([
                'msg' => 'Maaf, slot ini sudah penuh. Pilih jam lain!'
            ]);
        }

        $existingBooking = Booking::where('booking_date', $request->booking_date)
            ->whereRaw("TIME_FORMAT(booking_time, '%H:%i') = ?", [$request->booking_time])
            ->whereHas('customer', function ($query) use ($email, $phone) {
                $query->where('email', $email)
                        ->orWhere('phone', $phone);
            })
            ->first();

        if ($existingBooking) { 
            return back()->withErrors([
                'msg' => 'Kamu masih memiliki booking aktif pada ' . 
                 \Carbon\Carbon::parse($existingBooking->booking_date)->isoFormat('D MMM YYYY') . 
                 ' jam ' . substr($existingBooking->booking_time, 0, 5) . 
                 '. Tunggu hingga selesai atau hubungi admin untuk membatalkan.'
            ]);
        }

        try {
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

                try {
                        Mail::to($customer->email)
                            ->queue(new BookingSuccessMail($booking));
                    } catch (\Exception $mailError) {
                        Log::error('Mail error: ' . $mailError->getMessage());
                }

            return redirect()
                ->route('home')
                ->with('booking_success_id', $booking->id);

        } catch (\Exception $e) {
            Log::error('Booking error: ' . $e->getMessage());

            return back()->withErrors([
                'msg' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ]);
        }
    }

    public function reschedule(Request $request, $id)
    {
        $request->validate([
            'booking_date' => 'required|date|after_or_equal:today',
            'booking_time' => 'required',
        ]);

        $booking  = Booking::with('customer')->findOrFail($id);
        $capacity = (int) Setting::get('capacity', 1);

        $bookedCount = Booking::where('booking_date', $request->booking_date)
            ->whereRaw("TIME_FORMAT(booking_time, '%H:%i') = ?", [$request->booking_time])
            ->whereIn('status', ['active', 'on-progress'])
            ->where('id', '!=', $id)
            ->count();

        if ($bookedCount >= $capacity) {
            return back()->withErrors([
                'msg' => 'Slot baru sudah penuh! Pilih jam lain.'
            ]);
        }

        $oldDate = $booking->booking_date;
        $oldTime = $booking->booking_time;

        $booking->update([
            'booking_date' => $request->booking_date,
            'booking_time' => $request->booking_time . ':00',
        ]);

        try {
            Mail::to($booking->customer->email)
                ->queue(new BookingRescheduledMail($booking, $oldDate, $oldTime));
        } catch (\Exception $mailError) {
            Log::error('Reschedule mail error: ' . $mailError->getMessage());
        }

        return back()->with('success',
            "Booking {$booking->customer->name} berhasil direschedule ke " .
            \Carbon\Carbon::parse($request->booking_date)->isoFormat('D MMM YYYY') .
            " jam {$request->booking_time}."
        );
    }

    public function admin()
    {
        if (auth()->user()->role !== 'admin') return redirect('/');

        $bookings = Booking::with('customer')
            ->where('booking_date', '>=', now()->subDays(3)->toDateString()) 
            ->orderBy('booking_date', 'desc')
            ->orderBy('booking_time', 'asc')
            ->get();

        $capacity = (int) Setting::get('capacity', 1);
        $today = now()->toDateString();

        return view('admin', compact('bookings', 'capacity', 'today'));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'capacity' => 'required|integer|min:1|max:20'
        ]);

        Setting::set('capacity', $request->capacity);

        return back()->with('success', 'Kapasitas berhasil diupdate menjadi ' . $request->capacity . ' orang/jam!');
    }

    public function start($id)
    {
        Booking::findOrFail($id)->update(['status' => 'on-progress']);
        return back()->with('success', 'Layanan dimulai!');
    }

    public function cancel($id)
    {
        $booking = Booking::with('customer')->findOrFail($id);

        // Ambil data sebelum delete
        $customerName = $booking->customer->name;
        $customerEmail = $booking->customer->email;
        $bookingDate  = $booking->booking_date;
        $bookingTime  = $booking->booking_time;

        $booking->delete(); // delete dulu

        try {
                Mail::to($customerEmail)
                    ->queue(new BookingCancelledMail($customerName, $bookingDate, $bookingTime));
            } catch (\Exception $e) {
                Log::error('Cancel mail error: ' . $e->getMessage());
            }

        return back()->with('success', 'Booking dibatalkan dan slot kembali tersedia.');
    }

    public function complete($id)
    {
        Booking::findOrFail($id)->update(['status' => 'completed']);
        return back()->with('success', 'Layanan telah selesai!');
    }

    public function availableSlots(Request $request)
    {
        $date     = $request->date;
        $excludeId = $request->exclude_id;
        $capacity = (int) Setting::get('capacity', 1);
        $now      = now();

        $allSlots = [
            '08:00', '09:00', '10:00', '11:00',
            '13:00', '14:00', '15:00', '16:00',
            '17:00', '18:00', '19:00', '20:00'
        ];

        $availableSlots = [];

        foreach ($allSlots as $slot) {
            if ($date === $now->toDateString()) {
                $slotTime = \Carbon\Carbon::createFromFormat('H:i', $slot);
                if ($slotTime->isPast()) {
                    continue;
                }
            }

            // Cek kapasitas slot (exclude booking yang sedang direschedule)
            $bookedCount = Booking::where('booking_date', $date)
                ->whereRaw("TIME_FORMAT(booking_time, '%H:%i') = ?", [$slot])
                ->whereIn('status', ['active', 'on-progress'])
                ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
                ->count();

            if ($bookedCount < $capacity) {
                $availableSlots[] = $slot;
            }
        }

        return response()->json($availableSlots);
    }
}

