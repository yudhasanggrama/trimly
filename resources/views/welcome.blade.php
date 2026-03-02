<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trimly Interaktif</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-900 flex items-center justify-center min-h-screen text-white">

    <div class="text-center p-8 bg-slate-800 rounded-2xl shadow-2xl border border-slate-700 hover:scale-105 transform transition">
        <h1 class="text-4xl font-bold mb-4 bg-gradient-to-r from-blue-400 to-emerald-400 bg-clip-text text-transparent animate-pulse">
            Tailwind Berhasil!
        </h1>
        <p class="text-slate-400 mb-6">Sekarang kamu bisa membuat UI yang keren dan responsif.</p>
        
        <button class="px-6 py-3 bg-blue-600 hover:bg-blue-500 rounded-lg font-semibold shadow-lg shadow-blue-500/30 active:scale-95 transition-all">
            Klik Saya (Efek Interaktif)
        </button>
    </div>

</body>
</html>