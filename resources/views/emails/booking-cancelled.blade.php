<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: Arial, sans-serif; background:#f4f4f4; padding:30px;">
    <div style="max-width:500px; margin:auto; background:white; border-radius:16px; overflow:hidden;">
        
        <!-- Header -->
        <div style="background:black; padding:30px; text-align:center;">
            <h1 style="color:#f59e0b; margin:0; font-size:28px;">💈 TRIMLY</h1>
            <p style="color:white; margin:8px 0 0; font-size:12px;">BOOKING DIBATALKAN</p>
        </div>

        <!-- Body -->
        <div style="padding:30px;">
            <p style="color:#333;">Halo <strong>{{ $customerName }}</strong>,</p>
            
            <p style="color:#555;">
                Mohon maaf, booking kamu telah <strong style="color:#dc2626;">dibatalkan oleh admin</strong>.
            </p>

            <div style="background:#fef2f2; border-radius:12px; padding:20px; margin:20px 0; border:1px solid #fecaca;">
                <table width="100%">
                    <tr>
                        <td style="color:#888; font-size:12px; padding:6px 0;">TANGGAL</td>
                        <td style="font-weight:bold; text-align:right;">
                            {{ \Carbon\Carbon::parse($bookingDate)->isoFormat('dddd, D MMMM YYYY') }}
                        </td>
                    </tr>
                    <tr>
                        <td style="color:#888; font-size:12px; padding:6px 0;">JAM</td>
                        <td style="font-weight:bold; text-align:right; font-size:20px; color:#dc2626;">
                            {{ substr($bookingTime, 0, 5) }} WIB
                        </td>
                    </tr>
                    <tr>
                        <td style="color:#888; font-size:12px; padding:6px 0;">STATUS</td>
                        <td style="font-weight:bold; text-align:right; color:#dc2626;">
                            DIBATALKAN ✕
                        </td>
                    </tr>
                </table>
            </div>

            <p style="color:#555; font-size:13px;">
                Silakan melakukan booking ulang melalui website kami untuk mendapatkan jadwal baru.
            </p>

            <!-- Button -->
            <div style="text-align:center; margin:25px 0;">
                <a href="{{ config('app.url') }}"
                   style="background:black; color:white; padding:12px 24px; 
                          text-decoration:none; border-radius:8px; 
                          font-size:13px; font-weight:bold; display:inline-block;">
                    BOOKING ULANG
                </a>
            </div>

            <p style="color:#555; font-size:13px;">
                Jika ada pertanyaan, silakan hubungi tim kami.
            </p>

            <p style="color:#555; font-size:13px;">
                Terima kasih atas pengertiannya 🙏
            </p>
        </div>

        <!-- Footer -->
        <div style="background:#f4f4f4; padding:15px; text-align:center;">
            <p style="color:#aaa; font-size:11px; margin:0;">
                © 2026 TRIMLY Barbershop
            </p>
        </div>
    </div>
</body>
</html>