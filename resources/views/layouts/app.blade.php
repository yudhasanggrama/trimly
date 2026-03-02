<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trimly | Modern Barbershop</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
    </style>
</head>
<body class="bg-slate-50" x-data="{ 
    openAuth: {{ session('openLoginModal') ? 'true' : 'false' }}, 
    authMode: 'login' 
}">

<nav class="sticky top-0 z-[50] bg-black text-white px-8 py-4 flex justify-between items-center shadow-lg">
    <h1 class="font-bold text-2xl tracking-tighter text-amber-500">💈 TRIMLY</h1>
    <div class="flex items-center space-x-6">
        <a href="{{ route('home') }}" class="hover:text-amber-400 transition text-sm font-bold uppercase">Home</a>
        @auth
            @if(auth()->user()->role == 'admin')
                <a href="{{ route('admin') }}" class="bg-amber-500 text-black px-4 py-1 rounded-full font-bold hover:bg-amber-400 text-xs uppercase italic">Admin</a>
            @endif
            <form action="{{ route('logout') }}" method="POST" class="m-0">@csrf 
                <button type="submit" class="text-red-400 hover:text-red-300 text-sm font-bold uppercase">Logout</button>
            </form>
        @else
            <button @click="openAuth = true; authMode = 'login'" class="bg-white text-black px-5 py-2 rounded-full font-bold hover:bg-gray-200 transition text-sm uppercase">Login</button>
        @endauth
    </div>
</nav>

<main class="container mx-auto p-4 md:p-8"> @yield('content') </main>

<div x-show="openAuth" class="fixed inset-0 z-[999]" x-cloak>
    <div @click="openAuth = false" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white p-8 rounded-3xl w-full max-w-sm shadow-2xl relative z-10">
            <div class="flex bg-gray-100 p-1 rounded-xl mb-6">
                <button @click="authMode = 'login'" class="flex-1 py-2 rounded-lg font-bold transition" :class="authMode == 'login' ? 'bg-white shadow text-black' : 'text-gray-500'">Login</button>
                <button @click="authMode = 'register'" class="flex-1 py-2 rounded-lg font-bold transition" :class="authMode == 'register' ? 'bg-white shadow text-black' : 'text-gray-500'">Daftar</button>
            </div>

            <form x-show="authMode == 'login'" action="{{ route('login') }}" method="POST" class="space-y-4">
                @csrf
                <input type="email" name="email" placeholder="Email" class="w-full border p-3 rounded-xl outline-none focus:ring-2 focus:ring-amber-500" required>
                <input type="password" name="password" placeholder="Password" class="w-full border p-3 rounded-xl outline-none focus:ring-2 focus:ring-amber-500" required>
                <button class="w-full bg-black text-white py-3 rounded-xl font-bold hover:bg-gray-800 transition">Sign In</button>
            </form>

            <form x-show="authMode == 'register'" action="{{ route('register') }}" method="POST" class="space-y-3">
                @csrf
                <input type="text" name="name" placeholder="Nama Lengkap" class="w-full border p-3 rounded-xl outline-none" required>
                <input type="email" name="email" placeholder="Email" class="w-full border p-3 rounded-xl outline-none" required>
                <input type="text" name="phone" placeholder="No HP" class="w-full border p-3 rounded-xl outline-none" required>
                <input type="password" name="password" placeholder="Password (Min 6)" class="w-full border p-3 rounded-xl outline-none" required>
                <input type="password" name="password_confirmation" placeholder="Ulangi Password" class="w-full border p-3 rounded-xl outline-none" required>
                <button class="w-full bg-amber-500 text-black py-3 rounded-xl font-bold hover:bg-amber-400 transition">Daftar Akun</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>