<?php

namespace App\Http\Controllers;

use App\Mail\BookingCancelledMail;
use App\Mail\BookingRescheduledMail;
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
        '17:00', '18:00', '19:00', '20:00'
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

        $successBooking = null;
        if (session('booking_success_id')) {
            $successBooking = Booking::with('customer')
                ->find(session('booking_success_id'));
        }

        return view('home', compact(
            'date',
            'bookedSlots',
            'successBooking',
            'capacity'
        ))->with('timeSlots', $this->timeSlots);
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
                return back()->withErrors([
                    'msg' => 'Maaf, slot ini sudah penuh. Pilih jam lain!'
                ]);
            }

            $existingBooking = Booking::where('booking_date', $request->booking_date)
                ->where('booking_time', $request->booking_time . ':00')
                ->whereIn('status', ['active', 'on-progress'])
                ->whereHas('customer', function ($query) use ($email, $phone) {
                    $query->where('email', $email)
                          ->orWhere('phone', $phone);
                })
                ->lockForUpdate()
                ->first();

            if ($existingBooking) {
                DB::rollBack();
                return back()->withErrors([
                    'msg' => 'Kamu masih memiliki booking aktif pada ' .
                        Carbon::parse($existingBooking->booking_date)->isoFormat('D MMM YYYY') .
                        ' jam ' . substr($existingBooking->booking_time, 0, 5) .
                        '. Tunggu hingga selesai atau hubungi admin.'
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
                Mail::to($customer->email)
                    ->queue(new BookingSuccessMail($booking));
            } catch (\Exception $mailError) {
                Log::error('Mail error: ' . $mailError->getMessage());
            }

            return redirect()
                ->route('home')
                ->with('booking_success_id', $booking->id);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Booking error: ' . $e->getMessage());

            return back()->withErrors([
                'msg' => 'Terjadi kesalahan sistem.'
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

        DB::beginTransaction();

        $bookedCount = Booking::where('booking_date', $request->booking_date)
            ->where('booking_time', $request->booking_time . ':00')
            ->whereIn('status', ['active', 'on-progress'])
            ->where('id', '!=', $id)
            ->lockForUpdate()
            ->count();

        if ($bookedCount >= $capacity) {
            DB::rollBack();
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

        DB::commit();

        try {
            Mail::to($booking->customer->email)
                ->queue(new BookingRescheduledMail($booking, $oldDate, $oldTime));
        } catch (\Exception $e) {
            Log::error('Reschedule mail error: ' . $e->getMessage());
        }

        return back()->with('success', 'Booking berhasil direschedule.');
    }

    public function admin()
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            return redirect('/');
        }

        $bookings = Booking::with('customer')
            ->where('booking_date', '>=', now()->subDays(3)->toDateString())
            ->orderBy('booking_date', 'desc')
            ->orderBy('booking_time', 'asc')
            ->get();

        $capacity = (int) Setting::get('capacity', 1);
        $today    = now()->toDateString();

        return view('admin', compact('bookings', 'capacity', 'today'));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'capacity' => 'required|integer|min:1|max:20'
        ]);

        Setting::set('capacity', $request->capacity);

        return back()->with('success', 'Kapasitas berhasil diupdate.');
    }

    public function start($id)
    {
        Booking::findOrFail($id)->update(['status' => 'on-progress']);
        return back()->with('success', 'Layanan dimulai!');
    }

    public function complete($id)
    {
        Booking::findOrFail($id)->update(['status' => 'completed']);
        return back()->with('success', 'Layanan selesai!');
    }

    public function cancel($id)
    {
        $booking = Booking::with('customer')->findOrFail($id);

        $booking->delete();

        try {
            Mail::to($booking->customer->email)
                ->queue(new BookingCancelledMail(
                    $booking->customer->name,
                    $booking->booking_date,
                    $booking->booking_time
                ));
        } catch (\Exception $e) {
            Log::error('Cancel mail error: ' . $e->getMessage());
        }

        return back()->with('success', 'Booking dibatalkan dan slot tersedia kembali.');
    }

    public function availableSlots(Request $request)
    {
        $date      = $request->date;
        $excludeId = $request->exclude_id;
        $capacity  = (int) Setting::get('capacity', 1);
        $now       = now();

        $availableSlots = [];

        foreach ($this->timeSlots as $slot) {

            $slotTime = Carbon::createFromFormat(
                'Y-m-d H:i',
                $date . ' ' . $slot
            );

            if ($slotTime->isPast()) {
                continue;
            }

            $bookedCount = Booking::where('booking_date', $date)
                ->where('booking_time', $slot . ':00')
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