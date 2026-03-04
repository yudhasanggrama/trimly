<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; background:#f4f4f4; padding:30px;">
    <div style="max-width:500px; margin:auto; background:white; border-radius:16px; overflow:hidden;">

        <div style="background:black; padding:30px; text-align:center;">
            <h1 style="color:#f59e0b; margin:0; font-size:28px;">💈 TRIMLY</h1>
            <p style="color:white; margin:8px 0 0; font-size:12px;">JADWAL BOOKING DIUBAH</p>
        </div>

        <div style="padding:30px;">
            <p style="color:#333;">Halo <strong>{{ $booking->customer->name }}</strong>,</p>
            <p style="color:#555;">Jadwal booking kamu telah diubah oleh admin. Berikut detailnya:</p>

            {{-- Jadwal Lama --}}
            <div style="background:#fee2e2; border-radius:12px; padding:16px; margin:16px 0;">
                <p style="color:#991b1b; font-size:11px; font-weight:bold; margin:0 0 8px; text-transform:uppercase;">Jadwal Lama (Dibatalkan)</p>
                <table width="100%">
                    <tr>
                        <td style="color:#888; font-size:12px;">Tanggal</td>
                        <td style="font-weight:bold; text-align:right; text-decoration:line-through; color:#999;">
                            {{ \Carbon\Carbon::parse($oldDate)->isoFormat('dddd, D MMMM YYYY') }}
                        </td>
                    </tr>
                    <tr>
                        <td style="color:#888; font-size:12px;">Jam</td>
                        <td style="font-weight:bold; text-align:right; text-decoration:line-through; color:#999;">
                            {{ substr($oldTime, 0, 5) }} WIB
                        </td>
                    </tr>
                </table>
            </div>

            {{-- Jadwal Baru --}}
            <div style="background:#f0fdf4; border-radius:12px; padding:16px; margin:16px 0;">
                <p style="color:#166534; font-size:11px; font-weight:bold; margin:0 0 8px; text-transform:uppercase;">Jadwal Baru ✓</p>
                <table width="100%">
                    <tr>
                        <td style="color:#888; font-size:12px;">Tanggal</td>
                        <td style="font-weight:bold; text-align:right;">
                            {{ \Carbon\Carbon::parse($booking->booking_date)->isoFormat('dddd, D MMMM YYYY') }}
                        </td>
                    </tr>
                    <tr>
                        <td style="color:#888; font-size:12px;">Jam</td>
                        <td style="font-weight:bold; text-align:right; font-size:20px; color:#f59e0b;">
                            {{ substr($booking->booking_time, 0, 5) }} WIB
                        </td>
                    </tr>
                </table>
            </div>

            <p style="color:#555; font-size:13px;">Mohon maaf atas perubahan ini. Tunjukkan email ini saat tiba di toko.</p>
            <p style="color:#555; font-size:13px;">Terima kasih atas pengertiannya! ✂️</p>
        </div>

        <div style="background:#f4f4f4; padding:15px; text-align:center;">
            <p style="color:#aaa; font-size:11px; margin:0;">© 2026 TRIMLY Barbershop</p>
        </div>
    </div>
</body>
</html>