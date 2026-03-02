<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: Arial, sans-serif; background:#f4f4f4; padding:30px;">
    <div style="max-width:500px; margin:auto; background:white; border-radius:16px; overflow:hidden;">
        
        <div style="background:black; padding:30px; text-align:center;">
            <h1 style="color:#f59e0b; margin:0; font-size:28px;">💈 TRIMLY</h1>
            <p style="color:white; margin:8px 0 0; font-size:12px;">BOOKING BERHASIL</p>
        </div>

        <div style="padding:30px;">
            <p style="color:#333;">Halo <strong>{{ $booking->customer->name }}</strong>,</p>
            <p style="color:#555;">Booking kamu telah dikonfirmasi! Berikut detailnya:</p>

            <div style="background:#f9f9f9; border-radius:12px; padding:20px; margin:20px 0;">
                <table width="100%">
                    <tr>
                        <td style="color:#888; font-size:12px; padding:6px 0;">TANGGAL</td>
                        <td style="font-weight:bold; text-align:right;">{{ \Carbon\Carbon::parse($booking->booking_date)->isoFormat('dddd, D MMMM YYYY') }}</td>
                    </tr>
                    <tr>
                        <td style="color:#888; font-size:12px; padding:6px 0;">JAM</td>
                        <td style="font-weight:bold; text-align:right; font-size:20px; color:#f59e0b;">{{ substr($booking->booking_time, 0, 5) }} WIB</td>
                    </tr>
                    <tr>
                        <td style="color:#888; font-size:12px; padding:6px 0;">STATUS</td>
                        <td style="font-weight:bold; text-align:right; color:green;">AKTIF ✓</td>
                    </tr>
                </table>
            </div>

            <p style="color:#555; font-size:13px;">Tunjukkan email ini atau nomor booking saat tiba di toko.</p>
            <p style="color:#555; font-size:13px;">Terima kasih sudah booking di <strong>TRIMLY</strong>! ✂️</p>
        </div>

        <div style="background:#f4f4f4; padding:15px; text-align:center;">
            <p style="color:#aaa; font-size:11px; margin:0;">© 2026 TRIMLY Barbershop</p>
        </div>
    </div>
</body>
</html>