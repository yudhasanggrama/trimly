<?php

namespace App\Http\Controllers;

use App\Mail\BookingCancelledMail;
use App\Mail\BookingRescheduledMail;
use App\Models\Booking;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminController extends Controller
{
    private $timeSlots = [
        '08:00', '09:00', '10:00', '11:00',
        '13:00', '14:00', '15:00', '16:00',
        '17:00', '18:00', '19:00', '20:00',
    ];

    public function index()
    {
        $bookings = Booking::with('customer')
            ->where('booking_date', '>=', now()->subDays(3)->toDateString())
            ->orderBy('booking_date', 'desc')
            ->orderBy('booking_time', 'asc')
            ->get();

        $capacity = (int) Setting::get('capacity', 1);
        $today    = now()->toDateString();

        // ── Stats Cards ──────────────────────────────────────
        $stats = [
            'today'       => Booking::whereDate('booking_date', $today)->count(),
            'active'      => Booking::whereDate('booking_date', $today)->where('status', 'active')->count(),
            'on_progress' => Booking::whereDate('booking_date', $today)->where('status', 'on-progress')->count(),
            'completed'   => Booking::whereDate('booking_date', $today)->where('status', 'completed')->count(),
            'hourly'      => Booking::whereDate('booking_date', $today)
                                ->selectRaw("TIME_FORMAT(booking_time, '%H:00') as hour, COUNT(*) as total")
                                ->groupBy('hour')
                                ->orderBy('hour')
                                ->pluck('total', 'hour')
                                ->toArray(),
        ];

        // ── Chart: 7 hari terakhir ───────────────────────────
        [$weekLabels, $weekTotal, $weekDone] = $this->buildChartData(7, 'ddd D/M');

        // ── Chart: 30 hari terakhir ──────────────────────────
        [$monthLabels, $monthTotal, $monthDone] = $this->buildChartData(30, 'd/m', 5);

        $chartData = [
            'week'  => ['labels' => $weekLabels,  'total' => $weekTotal,  'done' => $weekDone],
            'month' => ['labels' => $monthLabels, 'total' => $monthTotal, 'done' => $monthDone],
        ];

        return view('admin', compact('bookings', 'capacity', 'today', 'stats', 'chartData'));
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
            return back()->withErrors(['msg' => 'Slot baru sudah penuh! Pilih jam lain.']);
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

    public function updateSettings(Request $request)
    {
        $request->validate(['capacity' => 'required|integer|min:1|max:20']);

        Setting::set('capacity', $request->capacity);

        return back()->with('success', 'Kapasitas berhasil diupdate.');
    }

    public function availableSlots(Request $request)
    {
        $date      = $request->date;
        $excludeId = $request->exclude_id;
        $capacity  = (int) Setting::get('capacity', 1);

        $availableSlots = [];

        foreach ($this->timeSlots as $slot) {
            $slotTime = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $slot);

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

    // ── Helper ───────────────────────────────────────────────
    private function buildChartData(int $days, string $format, int $labelEvery = 1): array
    {
        $labels = [];
        $total  = [];
        $done   = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date     = now()->subDays($i)->toDateString();
            $labels[] = ($i % $labelEvery === 0)
                ? now()->subDays($i)->isoFormat($format)
                : '';
            $total[]  = Booking::whereDate('booking_date', $date)->count();
            $done[]   = Booking::whereDate('booking_date', $date)->where('status', 'completed')->count();
        }

        return [$labels, $total, $done];
    }
}