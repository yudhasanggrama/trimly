@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto py-6 px-4"
     x-data="adminDashboard()"
     x-init="init()">

    {{-- Reschedule Modal --}}
    <div x-show="rescheduleOpen"
         x-cloak
         class="fixed inset-0 z-[999] flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100">

        <div @click="rescheduleOpen = false" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>

        <div class="bg-white w-full max-w-sm rounded-[2.5rem] p-8 relative shadow-2xl z-10"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="scale-90 opacity-0"
             x-transition:enter-end="scale-100 opacity-100">

            <h3 class="text-2xl font-black italic tracking-tighter text-slate-900 uppercase mb-1">
                Reschedule<span class="text-amber-500">.</span>
            </h3>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-6"
               x-text="'Pelanggan: ' + rescheduleCustomer"></p>

            <form id="reschedule-form" action="" method="POST">
                @csrf
                <input type="hidden" name="_method" value="PUT">

                <div class="space-y-4">
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block italic">Tanggal Baru</label>
                        <input type="date" name="booking_date"
                            :min="today"
                            x-model="rescheduleDate"
                            @change="loadSlots($event.target.value)"
                            required
                            class="w-full px-4 py-3 bg-slate-50 border-2 border-transparent focus:border-black rounded-2xl text-sm font-bold outline-none transition-all">
                    </div>

                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block italic">
                            Jam Baru
                            <span x-show="loadingSlots" class="ml-1 text-amber-500 normal-case font-bold">Loading...</span>
                        </label>

                        <div x-show="loadingSlots" class="w-full px-4 py-3 bg-slate-50 rounded-2xl text-sm text-slate-400 font-bold animate-pulse">
                            Mengecek slot tersedia...
                        </div>

                        <div x-show="!loadingSlots && availableSlots.length > 0">
                            <div class="relative">
                                <select name="booking_time" required
                                    class="w-full px-4 py-3 bg-slate-50 border-2 border-transparent focus:border-black rounded-2xl text-sm font-bold outline-none appearance-none transition-all cursor-pointer">
                                    <template x-for="slot in availableSlots" :key="slot">
                                        <option :value="slot" x-text="slot"></option>
                                    </template>
                                </select>
                                <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </div>
                        </div>

                        <div x-show="!loadingSlots && availableSlots.length === 0 && rescheduleDate !== ''"
                            class="w-full px-4 py-3 bg-red-50 border-2 border-red-100 rounded-2xl text-xs font-black text-red-500 uppercase">
                            ✕ Tidak ada slot tersedia di tanggal ini
                        </div>

                        <div x-show="!loadingSlots && rescheduleDate === ''"
                            class="w-full px-4 py-3 bg-slate-50 rounded-2xl text-xs font-bold text-slate-400">
                            Pilih tanggal terlebih dahulu
                        </div>
                    </div>
                </div>

                <div class="mt-6 space-y-2">
                    <button type="button" @click="submitReschedule()"
                        :disabled="availableSlots.length === 0 || loadingSlots"
                        :class="availableSlots.length === 0 || loadingSlots ? 'opacity-40 cursor-not-allowed' : 'hover:bg-amber-400 active:scale-95'"
                        class="w-full bg-amber-500 text-black py-4 rounded-2xl font-black text-xs uppercase tracking-widest transition shadow-lg">
                        ✦ Konfirmasi Reschedule
                    </button>
                    <button type="button" @click="rescheduleOpen = false"
                        class="w-full py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-red-500 transition">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Header --}}
    <div class="flex flex-wrap justify-between items-start gap-4 mb-6">
        <div>
            <h2 class="text-2xl sm:text-3xl font-black italic tracking-tighter text-slate-900 uppercase">Dashboard<span class="text-amber-500">.</span></h2>
            <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest mt-1">Manajemen Antrean TRIMLY</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <form action="{{ route('admin.settings') }}" method="POST" class="flex items-center gap-2 bg-white border-2 border-slate-100 rounded-2xl px-4 py-2 shadow-sm">
                @csrf
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest italic whitespace-nowrap">Kapasitas/Jam</span>
                <input type="number" name="capacity" min="1" max="20"
                    value="{{ $capacity }}"
                    class="w-14 text-center bg-slate-50 border-2 border-transparent focus:border-black rounded-xl py-1.5 font-black text-base outline-none transition-all">
                <button class="bg-black text-white px-3 py-1.5 rounded-xl text-[10px] font-black uppercase hover:bg-amber-500 transition whitespace-nowrap">Simpan</button>
            </form>
            <div class="flex items-center gap-2 bg-black text-white px-3 py-2 rounded-full">
                <span class="flex h-2 w-2 relative">
                    <span class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                </span>
                <span class="text-[10px] font-black uppercase tracking-widest">Live</span>
            </div>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="mb-4 bg-green-50 border-2 border-green-100 p-4 rounded-2xl">
            <p class="text-green-700 text-xs font-black uppercase italic">✓ {{ session('success') }}</p>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 bg-red-50 border-2 border-red-100 p-4 rounded-2xl">
            <p class="text-red-600 text-xs font-black uppercase italic">✕ {{ $errors->first() }}</p>
        </div>
    @endif

    {{-- ==================== STATS CARDS ==================== --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        {{-- Total Hari Ini --}}
        <div class="bg-black text-white rounded-3xl p-5 relative overflow-hidden">
            <div class="absolute -top-4 -right-4 w-20 h-20 bg-amber-500/20 rounded-full"></div>
            <div class="absolute -bottom-6 -left-2 w-16 h-16 bg-white/5 rounded-full"></div>
            <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-2">Hari Ini</p>
            <p class="text-4xl font-black italic tracking-tighter text-white" x-text="stats.today">{{ $stats['today'] }}</p>
            <p class="text-[10px] font-bold text-amber-400 mt-1 uppercase">booking</p>
        </div>

        {{-- Active --}}
        <div class="bg-green-500 text-white rounded-3xl p-5 relative overflow-hidden">
            <div class="absolute -top-4 -right-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <p class="text-[9px] font-black uppercase tracking-widest text-green-100 mb-2">Menunggu</p>
            <p class="text-4xl font-black italic tracking-tighter" x-text="stats.active">{{ $stats['active'] }}</p>
            <p class="text-[10px] font-bold text-green-100 mt-1 uppercase">antrian aktif</p>
        </div>

        {{-- On Progress --}}
        <div class="bg-blue-600 text-white rounded-3xl p-5 relative overflow-hidden">
            <div class="absolute -top-4 -right-4 w-20 h-20 bg-white/10 rounded-full"></div>
            <p class="text-[9px] font-black uppercase tracking-widest text-blue-200 mb-2">On Progress</p>
            <p class="text-4xl font-black italic tracking-tighter" x-text="stats.on_progress">{{ $stats['on_progress'] }}</p>
            <p class="text-[10px] font-bold text-blue-200 mt-1 uppercase">sedang dikerjakan</p>
        </div>

        {{-- Completed --}}
        <div class="bg-white border-2 border-slate-100 rounded-3xl p-5 relative overflow-hidden shadow-sm">
            <div class="absolute -top-4 -right-4 w-20 h-20 bg-amber-50 rounded-full"></div>
            <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-2">Selesai</p>
            <p class="text-4xl font-black italic tracking-tighter text-slate-900" x-text="stats.completed">{{ $stats['completed'] }}</p>
            <p class="text-[10px] font-bold text-slate-400 mt-1 uppercase">hari ini</p>
        </div>
    </div>

    {{-- ==================== GRAFIK PENGUNJUNG ==================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">

        {{-- Chart Utama: Tren 7 Hari --}}
        <div class="lg:col-span-2 bg-white border-2 border-slate-100 rounded-3xl p-6 shadow-sm">
            <div class="flex items-start justify-between mb-5">
                <div>
                    <h3 class="text-base font-black italic tracking-tighter text-slate-900 uppercase">
                        Tren Pengunjung<span class="text-amber-500">.</span>
                    </h3>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">7 hari terakhir</p>
                </div>
                <div class="flex gap-2">
                    <button onclick="switchChart('week')" id="btn-week"
                        class="chart-tab active-tab px-3 py-1.5 rounded-full text-[10px] font-black uppercase transition-all bg-black text-white">
                        7 Hari
                    </button>
                    <button onclick="switchChart('month')" id="btn-month"
                        class="chart-tab px-3 py-1.5 rounded-full text-[10px] font-black uppercase transition-all bg-slate-100 text-slate-500">
                        30 Hari
                    </button>
                </div>
            </div>

            <div class="relative h-48 sm:h-56">
                <canvas id="visitorChart"></canvas>
            </div>

            {{-- Legend --}}
            <div class="flex gap-4 mt-4 pt-4 border-t-2 border-slate-50">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                    <span class="text-[10px] font-black text-slate-400 uppercase">Total Booking</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-slate-900"></div>
                    <span class="text-[10px] font-black text-slate-400 uppercase">Selesai</span>
                </div>
            </div>
        </div>

        {{-- Chart Kanan: Distribusi Per Jam --}}
        <div class="bg-white border-2 border-slate-100 rounded-3xl p-6 shadow-sm">
            <div class="mb-5">
                <h3 class="text-base font-black italic tracking-tighter text-slate-900 uppercase">
                    Jam Sibuk<span class="text-amber-500">.</span>
                </h3>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Hari ini per jam</p>
            </div>

            {{-- Horizontal Bar Chart Manual --}}
            <div class="space-y-3" id="hourly-bars">
                @php
                    $hourlyData = $stats['hourly'] ?? [];
                    $maxHourly = max(array_values($hourlyData) ?: [1]);
                @endphp
                @foreach($hourlyData as $hour => $count)
                <div class="flex items-center gap-3">
                    <span class="text-[10px] font-black text-slate-400 w-10 text-right shrink-0">{{ $hour }}</span>
                    <div class="flex-1 bg-slate-50 rounded-full h-5 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-700 flex items-center justify-end pr-2
                            {{ $count == $maxHourly && $count > 0 ? 'bg-amber-500' : 'bg-slate-200' }}"
                            style="width: {{ $maxHourly > 0 ? round(($count / $maxHourly) * 100) : 0 }}%">
                            @if($count > 0)
                            <span class="text-[8px] font-black {{ $count == $maxHourly ? 'text-black' : 'text-slate-500' }}">{{ $count }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach

                @if(empty($hourlyData))
                <div class="text-center py-8">
                    <p class="text-3xl mb-2">📊</p>
                    <p class="text-[10px] font-black text-slate-300 uppercase">Belum ada data hari ini</p>
                </div>
                @endif
            </div>

            {{-- Peak hour info --}}
            @if(!empty($hourlyData) && max(array_values($hourlyData)) > 0)
            @php
                $peakHour = array_search(max($hourlyData), $hourlyData);
            @endphp
            <div class="mt-4 pt-4 border-t-2 border-slate-50">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Jam Tersibuk</p>
                <p class="text-2xl font-black italic tracking-tighter text-amber-500 mt-0.5">{{ $peakHour }}</p>
            </div>
            @endif
        </div>
    </div>

    {{-- ==================== FILTER BAR ==================== --}}
    <div class="bg-white border-2 border-slate-100 rounded-3xl p-5 mb-6 shadow-sm">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
            <div>
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block italic">Cari Nama</label>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input type="text" x-model="filterSearch" @input="applyFilter()"
                        placeholder="Nama pelanggan..."
                        class="w-full pl-9 pr-4 py-3 bg-slate-50 border-2 border-transparent focus:border-black rounded-2xl text-sm font-bold outline-none transition-all">
                </div>
            </div>
            <div>
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block italic">
                    Tanggal <span class="ml-2 text-amber-500 normal-case font-bold">(default: hari ini)</span>
                </label>
                <input type="date" x-model="filterDate" @change="applyFilter()"
                    class="w-full px-4 py-3 bg-slate-50 border-2 border-transparent focus:border-black rounded-2xl text-sm font-bold outline-none transition-all">
            </div>
        </div>
        <div class="flex flex-wrap gap-2 mb-4">
            <button @click="filterStatus = 'all'; applyFilter()" :class="filterStatus === 'all' ? 'bg-black text-white' : 'bg-slate-100 text-slate-500'" class="px-3 py-1.5 rounded-full text-[10px] font-black uppercase transition-all">Semua</button>
            <button @click="filterStatus = 'active'; applyFilter()" :class="filterStatus === 'active' ? 'bg-green-500 text-white' : 'bg-green-50 text-green-600'" class="px-3 py-1.5 rounded-full text-[10px] font-black uppercase transition-all">● Active</button>
            <button @click="filterStatus = 'on-progress'; applyFilter()" :class="filterStatus === 'on-progress' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-600'" class="px-3 py-1.5 rounded-full text-[10px] font-black uppercase transition-all">● On-Progress</button>
            <button @click="filterStatus = 'completed'; applyFilter()" :class="filterStatus === 'completed' ? 'bg-slate-700 text-white' : 'bg-slate-100 text-slate-500'" class="px-3 py-1.5 rounded-full text-[10px] font-black uppercase transition-all">● Completed</button>
        </div>
        <div class="flex flex-wrap gap-2 mb-4">
            <button @click="sortOrder = 'newest'; applyFilter()"
                :class="sortOrder === 'newest' ? 'bg-black text-white' : 'bg-slate-100 text-slate-500'"
                class="px-3 py-1.5 rounded-full text-[10px] font-black uppercase transition-all">
                ⬇ Newest
            </button>
            <button @click="sortOrder = 'oldest'; applyFilter()"
                :class="sortOrder === 'oldest' ? 'bg-black text-white' : 'bg-slate-100 text-slate-500'"
                class="px-3 py-1.5 rounded-full text-[10px] font-black uppercase transition-all">
                ⬆ Oldest
            </button>
        </div>
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-[10px] font-black text-slate-400 uppercase italic">Menampilkan</span>
                <span class="bg-black text-amber-400 font-black text-sm px-3 py-1 rounded-full" id="result-count">{{ count($bookings) }}</span>
                <span class="text-[10px] font-black text-slate-400 uppercase italic">data</span>
            </div>
            <button @click="resetFilter()" class="px-4 py-2 bg-slate-100 hover:bg-red-50 hover:text-red-500 rounded-xl text-[10px] font-black uppercase transition-all">✕ Reset (Semua Data)</button>
        </div>
    </div>

    {{-- ==================== MOBILE & TABLET: Card Layout ==================== --}}
    <div class="lg:hidden space-y-3" id="booking-cards-content">
        @forelse($bookings as $b)
        <div class="bg-white border-2 border-slate-100 rounded-3xl p-5 shadow-sm"
             data-row
             data-status="{{ $b->status }}"
             data-date="{{ $b->booking_date }}"
             data-time="{{ $b->booking_time }}"
             data-name="{{ strtolower($b->customer->name) }}">

            <div class="flex justify-between items-start mb-4">
                <div>
                    <div class="font-black text-slate-900 uppercase italic">{{ $b->customer->name }}</div>
                    <div class="text-xs text-slate-400 font-bold mt-0.5">{{ $b->customer->phone }}</div>
                </div>
                <span class="px-3 py-1.5 rounded-full text-[10px] font-black uppercase ml-2 shrink-0
                    {{ $b->status == 'active' ? 'bg-green-100 text-green-700' : '' }}
                    {{ $b->status == 'on-progress' ? 'bg-blue-600 text-white animate-pulse' : '' }}
                    {{ $b->status == 'completed' ? 'bg-slate-100 text-slate-600' : '' }}">
                    {{ $b->status }}
                </span>
            </div>

            <div class="flex items-center gap-4 bg-slate-50 rounded-2xl px-4 py-3 mb-4">
                <div>
                    <div class="text-[10px] font-black text-slate-400 uppercase italic mb-0.5">Tanggal</div>
                    <div class="text-sm font-black text-slate-700 uppercase">{{ \Carbon\Carbon::parse($b->booking_date)->isoFormat('ddd, D MMM YYYY') }}</div>
                </div>
                <div class="w-px h-8 bg-slate-200 shrink-0"></div>
                <div>
                    <div class="text-[10px] font-black text-slate-400 uppercase italic mb-0.5">Jam</div>
                    <div class="text-xl font-black text-amber-500 italic tracking-tighter">{{ substr($b->booking_time, 0, 5) }}</div>
                </div>
                @if($b->status == 'completed')
                <div class="ml-auto text-right shrink-0">
                    <div class="text-[10px] font-black text-slate-400 uppercase italic mb-0.5">Selesai</div>
                    <div class="text-sm font-black text-slate-500">{{ $b->updated_at->format('H:i') }} WIB</div>
                </div>
                @endif
            </div>

            @if($b->status == 'active')
                <div class="grid grid-cols-2 gap-2 mb-2">
                    <form action="{{ route('admin.start', $b->id) }}" method="POST">@csrf
                        <button class="w-full bg-blue-600 text-white py-3 rounded-2xl text-xs font-black uppercase hover:bg-blue-700 transition">▶ Mulai</button>
                    </form>
                    <form action="{{ route('admin.cancel', $b->id) }}" method="POST">@csrf
                        <button class="w-full bg-red-500 text-white py-3 rounded-2xl text-xs font-black uppercase hover:bg-red-600 transition">✕ Batal</button>
                    </form>
                </div>
                <button
                    @click="openReschedule({{ $b->id }}, '{{ addslashes($b->customer->name) }}', '{{ $b->booking_date }}')"
                    class="w-full bg-amber-50 border-2 border-amber-200 text-amber-600 py-3 rounded-2xl text-xs font-black uppercase hover:bg-amber-100 transition">
                    ↺ Reschedule
                </button>
            @elseif($b->status == 'on-progress')
                <form action="{{ route('admin.complete', $b->id) }}" method="POST">@csrf
                    <button class="w-full bg-green-600 text-white py-3 rounded-2xl text-xs font-black uppercase hover:bg-green-700 transition">✓ Selesai</button>
                </form>
            @endif
        </div>
        @empty
            <div class="text-center py-16 bg-white rounded-3xl border-2 border-slate-100">
                <p class="text-4xl mb-3">✂️</p>
                <p class="font-black text-slate-300 uppercase italic">Belum ada booking</p>
            </div>
        @endforelse
    </div>

    {{-- ==================== DESKTOP: Table Layout ==================== --}}
    <div class="hidden lg:block bg-white border-2 border-slate-100 rounded-3xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto" id="booking-table-content">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b-2 border-slate-100">
                        <th class="p-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Pelanggan</th>
                        <th class="p-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Jadwal</th>
                        <th class="p-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                        <th class="p-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Aksi</th>
                    </tr>
                </thead>
                <tbody id="booking-table-body">
                    @forelse($bookings as $b)
                    <tr class="border-b border-slate-50 hover:bg-amber-50 transition-colors"
                        data-row
                        data-status="{{ $b->status }}"
                        data-date="{{ $b->booking_date }}"
                        data-time="{{ $b->booking_time }}"
                        data-name="{{ strtolower($b->customer->name) }}">
                        <td class="p-4">
                            <div class="font-black text-slate-900 uppercase italic">{{ $b->customer->name }}</div>
                            <div class="text-xs text-slate-400 font-bold mt-0.5">{{ $b->customer->phone }}</div>
                        </td>
                        <td class="p-4">
                            <div class="text-xs font-bold text-slate-500 uppercase">{{ \Carbon\Carbon::parse($b->booking_date)->isoFormat('ddd, D MMM YYYY') }}</div>
                            <div class="text-amber-500 font-black text-lg italic tracking-tighter">{{ substr($b->booking_time, 0, 5) }}</div>
                        </td>
                        <td class="p-4">
                            <span class="px-3 py-1.5 rounded-full text-[10px] font-black uppercase
                                {{ $b->status == 'active' ? 'bg-green-100 text-green-700' : '' }}
                                {{ $b->status == 'on-progress' ? 'bg-blue-600 text-white animate-pulse' : '' }}
                                {{ $b->status == 'completed' ? 'bg-slate-100 text-slate-600' : '' }}">
                                {{ $b->status }}
                            </span>
                            @if($b->status == 'completed')
                                <div class="text-[10px] text-slate-400 mt-1 italic">{{ $b->updated_at->format('H:i') }} WIB</div>
                            @endif
                        </td>
                        <td class="p-4 text-right whitespace-nowrap">
                            @if($b->status == 'active')
                                <form action="{{ route('admin.start', $b->id) }}" method="POST" class="inline">@csrf
                                    <button class="bg-blue-600 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase hover:bg-blue-700 transition mr-1">▶ Start</button>
                                </form>
                                <button
                                    @click="openReschedule({{ $b->id }}, '{{ addslashes($b->customer->name) }}', '{{ $b->booking_date }}')"
                                    class="bg-amber-400 text-black px-4 py-2 rounded-xl text-[10px] font-black uppercase hover:bg-amber-300 transition mr-1">
                                    ↺ Reschedule
                                </button>
                                <form action="{{ route('admin.cancel', $b->id) }}" method="POST" class="inline">@csrf
                                    <button class="bg-red-500 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase hover:bg-red-600 transition">✕ Cancel</button>
                                </form>
                            @elseif($b->status == 'on-progress')
                                <form action="{{ route('admin.complete', $b->id) }}" method="POST" class="inline">@csrf
                                    <button class="bg-green-600 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase hover:bg-green-700 transition">✓ Finish</button>
                                </form>
                            @else
                                <span class="text-slate-300 text-[10px] font-bold uppercase">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4">
                            <div class="text-center py-16">
                                <p class="text-4xl mb-3">✂️</p>
                                <p class="font-black text-slate-300 uppercase italic">Belum ada booking</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- Chart.js — defer agar tidak block LCP --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>

<script>
    // ==================== DATA DARI LARAVEL ====================
    const chartDataWeek = {
        labels: @json($chartData['week']['labels']),
        total:  @json($chartData['week']['total']),
        done:   @json($chartData['week']['done']),
    };

    const chartDataMonth = {
        labels: @json($chartData['month']['labels']),
        total:  @json($chartData['month']['total']),
        done:   @json($chartData['month']['done']),
    };

    // ==================== CHART SETUP ====================
    let visitorChart = null;

    function buildChart(data) {
        const ctx = document.getElementById('visitorChart').getContext('2d');

        const gradientTotal = ctx.createLinearGradient(0, 0, 0, 220);
        gradientTotal.addColorStop(0, 'rgba(245, 158, 11, 0.25)');
        gradientTotal.addColorStop(1, 'rgba(245, 158, 11, 0)');

        const gradientDone = ctx.createLinearGradient(0, 0, 0, 220);
        gradientDone.addColorStop(0, 'rgba(15, 23, 42, 0.15)');
        gradientDone.addColorStop(1, 'rgba(15, 23, 42, 0)');

        if (visitorChart) visitorChart.destroy();

        visitorChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Total Booking',
                        data: data.total,
                        borderColor: '#f59e0b',
                        backgroundColor: gradientTotal,
                        borderWidth: 3,
                        pointBackgroundColor: '#f59e0b',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        fill: true,
                        tension: 0.4,
                    },
                    {
                        label: 'Selesai',
                        data: data.done,
                        borderColor: '#0f172a',
                        backgroundColor: gradientDone,
                        borderWidth: 2,
                        borderDash: [5, 4],
                        pointBackgroundColor: '#0f172a',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        fill: true,
                        tension: 0.4,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleColor: '#94a3b8',
                        bodyColor: '#fff',
                        titleFont: { size: 10, weight: '900', family: 'inherit' },
                        bodyFont: { size: 12, weight: '900', family: 'inherit' },
                        padding: 12,
                        cornerRadius: 12,
                        callbacks: {
                            title: (items) => items[0].label.toUpperCase(),
                            label: (item) => `  ${item.dataset.label}: ${item.raw} orang`,
                        }
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: {
                            color: '#94a3b8',
                            font: { size: 9, weight: '700' },
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9', lineWidth: 1 },
                        border: { display: false, dash: [4, 4] },
                        ticks: {
                            color: '#94a3b8',
                            font: { size: 9, weight: '700' },
                            stepSize: 1,
                            precision: 0,
                        }
                    }
                }
            }
        });
    }

    function switchChart(mode) {
        document.querySelectorAll('.chart-tab').forEach(el => {
            el.classList.remove('bg-black', 'text-white');
            el.classList.add('bg-slate-100', 'text-slate-500');
        });
        const active = document.getElementById('btn-' + mode);
        active.classList.add('bg-black', 'text-white');
        active.classList.remove('bg-slate-100', 'text-slate-500');

        buildChart(mode === 'week' ? chartDataWeek : chartDataMonth);
    }

    // Chart hanya diinisialisasi setelah Chart.js selesai load (karena defer)
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof Chart !== 'undefined') {
            buildChart(chartDataWeek);
        } else {
            // Fallback: tunggu script defer selesai
            document.querySelector('script[src*="chart.js"]').addEventListener('load', () => {
                buildChart(chartDataWeek);
            });
        }
    });

    // ==================== ALPINE DASHBOARD ====================
    function adminDashboard() {
        return {
            filterStatus: 'all',
            filterDate: '{{ $today }}',
            filterSearch: '',
            sortOrder: 'newest',

            rescheduleOpen: false,
            rescheduleId: null,
            rescheduleCustomer: '',
            rescheduleDate: '',
            today: '{{ $today }}',
            availableSlots: [],
            loadingSlots: false,
            loadingRefresh: false,

            // Stats reaktif — diupdate dari JSON refresh
            stats: {
                today: {{ $stats['today'] }},
                active: {{ $stats['active'] }},
                on_progress: {{ $stats['on_progress'] }},
                completed: {{ $stats['completed'] }},
            },

            // AbortController untuk cegah request admin menumpuk
            _abortController: null,

            init() {
                this.$nextTick(() => this.applyFilter());

                // Refresh setiap 15 detik via JSON endpoint (bukan full HTML)
                setInterval(() => {
                    if (document.visibilityState === 'visible') {
                        this.refreshData();
                    }
                }, 15000);
            },

            /**
             * Refresh ringan: hanya fetch JSON stats + bookings.
             * Sebelumnya fetch seluruh HTML halaman — sangat boros.
             * Dengan AbortController, request lama dibatalkan jika belum selesai.
             */
            async refreshData() {
                if (this._abortController) {
                    this._abortController.abort();
                }
                this._abortController = new AbortController();

                try {
                    const res  = await fetch('/admin/live-data', {
                        signal: this._abortController.signal,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await res.json();

                    // Update stats cards reaktif
                    this.stats = data.stats;

                    // Update tabel desktop
                    this.updateTable(data.bookings);

                    // Update cards mobile
                    this.updateCards(data.bookings);

                    this.$nextTick(() => this.applyFilter());

                } catch (e) {
                    if (e.name !== 'AbortError') {
                        console.error('Refresh gagal:', e);
                    }
                }
            },

            updateTable(bookings) {
                const tbody = document.getElementById('booking-table-body');
                if (!tbody) return;

                tbody.innerHTML = bookings.length === 0
                    ? `<tr><td colspan="4"><div class="text-center py-16"><p class="text-4xl mb-3">✂️</p><p class="font-black text-slate-300 uppercase italic">Belum ada booking</p></div></td></tr>`
                    : bookings.map(b => this.buildTableRow(b)).join('');
            },

            updateCards(bookings) {
                const container = document.getElementById('booking-cards-content');
                if (!container) return;

                container.innerHTML = bookings.length === 0
                    ? `<div class="text-center py-16 bg-white rounded-3xl border-2 border-slate-100"><p class="text-4xl mb-3">✂️</p><p class="font-black text-slate-300 uppercase italic">Belum ada booking</p></div>`
                    : bookings.map(b => this.buildCard(b)).join('');
            },

            buildTableRow(b) {
                const statusClass = b.status === 'active'
                    ? 'bg-green-100 text-green-700'
                    : b.status === 'on-progress'
                    ? 'bg-blue-600 text-white animate-pulse'
                    : 'bg-slate-100 text-slate-600';

                const completedTime = b.status === 'completed'
                    ? `<div class="text-[10px] text-slate-400 mt-1 italic">${b.updated_at} WIB</div>` : '';

                const actions = b.status === 'active'
                    ? `<form action="/admin/start/${b.id}" method="POST" class="inline">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <button class="bg-blue-600 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase hover:bg-blue-700 transition mr-1">▶ Start</button>
                       </form>
                       <button onclick="window.__alpine.$data(document.querySelector('[x-data]')).openReschedule(${b.id}, '${b.customer_name.replace(/'/g, "\\'")}', '${b.booking_date}')"
                           class="bg-amber-400 text-black px-4 py-2 rounded-xl text-[10px] font-black uppercase hover:bg-amber-300 transition mr-1">
                           ↺ Reschedule
                       </button>
                       <form action="/admin/cancel/${b.id}" method="POST" class="inline">
                           <input type="hidden" name="_token" value="{{ csrf_token() }}">
                           <button class="bg-red-500 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase hover:bg-red-600 transition">✕ Cancel</button>
                       </form>`
                    : b.status === 'on-progress'
                    ? `<form action="/admin/complete/${b.id}" method="POST" class="inline">
                           <input type="hidden" name="_token" value="{{ csrf_token() }}">
                           <button class="bg-green-600 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase hover:bg-green-700 transition">✓ Finish</button>
                       </form>`
                    : `<span class="text-slate-300 text-[10px] font-bold uppercase">—</span>`;

                return `<tr class="border-b border-slate-50 hover:bg-amber-50 transition-colors"
                            data-row data-status="${b.status}" data-date="${b.booking_date}"
                            data-time="${b.booking_time}" data-name="${b.customer_name.toLowerCase()}">
                            <td class="p-4">
                                <div class="font-black text-slate-900 uppercase italic">${b.customer_name}</div>
                                <div class="text-xs text-slate-400 font-bold mt-0.5">${b.customer_phone}</div>
                            </td>
                            <td class="p-4">
                                <div class="text-xs font-bold text-slate-500 uppercase">${b.booking_date}</div>
                                <div class="text-amber-500 font-black text-lg italic tracking-tighter">${b.booking_time}</div>
                            </td>
                            <td class="p-4">
                                <span class="px-3 py-1.5 rounded-full text-[10px] font-black uppercase ${statusClass}">${b.status}</span>
                                ${completedTime}
                            </td>
                            <td class="p-4 text-right whitespace-nowrap">${actions}</td>
                        </tr>`;
            },

            buildCard(b) {
                const statusClass = b.status === 'active'
                    ? 'bg-green-100 text-green-700'
                    : b.status === 'on-progress'
                    ? 'bg-blue-600 text-white animate-pulse'
                    : 'bg-slate-100 text-slate-600';

                const actions = b.status === 'active'
                    ? `<div class="grid grid-cols-2 gap-2 mb-2">
                            <form action="/admin/start/${b.id}" method="POST">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <button class="w-full bg-blue-600 text-white py-3 rounded-2xl text-xs font-black uppercase hover:bg-blue-700 transition">▶ Mulai</button>
                            </form>
                            <form action="/admin/cancel/${b.id}" method="POST">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <button class="w-full bg-red-500 text-white py-3 rounded-2xl text-xs font-black uppercase hover:bg-red-600 transition">✕ Batal</button>
                            </form>
                       </div>`
                    : b.status === 'on-progress'
                    ? `<form action="/admin/complete/${b.id}" method="POST">
                           <input type="hidden" name="_token" value="{{ csrf_token() }}">
                           <button class="w-full bg-green-600 text-white py-3 rounded-2xl text-xs font-black uppercase hover:bg-green-700 transition">✓ Selesai</button>
                       </form>`
                    : '';

                return `<div class="bg-white border-2 border-slate-100 rounded-3xl p-5 shadow-sm"
                             data-row data-status="${b.status}" data-date="${b.booking_date}"
                             data-time="${b.booking_time}" data-name="${b.customer_name.toLowerCase()}">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <div class="font-black text-slate-900 uppercase italic">${b.customer_name}</div>
                                    <div class="text-xs text-slate-400 font-bold mt-0.5">${b.customer_phone}</div>
                                </div>
                                <span class="px-3 py-1.5 rounded-full text-[10px] font-black uppercase ml-2 shrink-0 ${statusClass}">${b.status}</span>
                            </div>
                            <div class="flex items-center gap-4 bg-slate-50 rounded-2xl px-4 py-3 mb-4">
                                <div>
                                    <div class="text-[10px] font-black text-slate-400 uppercase italic mb-0.5">Tanggal</div>
                                    <div class="text-sm font-black text-slate-700 uppercase">${b.booking_date}</div>
                                </div>
                                <div class="w-px h-8 bg-slate-200 shrink-0"></div>
                                <div>
                                    <div class="text-[10px] font-black text-slate-400 uppercase italic mb-0.5">Jam</div>
                                    <div class="text-xl font-black text-amber-500 italic tracking-tighter">${b.booking_time}</div>
                                </div>
                            </div>
                            ${actions}
                        </div>`;
            },

            applyFilter() {
                let rows = Array.from(document.querySelectorAll('[data-row]'));

                rows.forEach(row => {
                    const status = row.dataset.status;
                    const date   = row.dataset.date;
                    const name   = row.dataset.name;

                    const needle = this.filterSearch.toLowerCase().trim().replace(/\s+/g, ' ');

                    const matchStatus = this.filterStatus === 'all' || status === this.filterStatus;
                    const matchDate   = this.filterDate === '' || date === this.filterDate;
                    const matchSearch = needle === '' || name.includes(needle);

                    row.style.display = (matchStatus && matchDate && matchSearch) ? '' : 'none';
                });

                let visibleRows = rows.filter(row => row.style.display !== 'none');

                visibleRows.sort((a, b) => {
                    const dateTimeA = new Date(a.dataset.date + ' ' + (a.dataset.time || '00:00'));
                    const dateTimeB = new Date(b.dataset.date + ' ' + (b.dataset.time || '00:00'));
                    return this.sortOrder === 'newest' ? dateTimeB - dateTimeA : dateTimeA - dateTimeB;
                });

                visibleRows.forEach(row => row.parentNode.appendChild(row));

                const result = document.getElementById('result-count');
                if (result) result.textContent = visibleRows.length;
            },

            resetFilter() {
                this.filterStatus = 'all';
                this.filterDate   = '';
                this.filterSearch = '';
                this.sortOrder    = 'newest';
                this.$nextTick(() => this.applyFilter());
            },

            async openReschedule(id, name, date) {
                this.rescheduleId = id;
                this.rescheduleCustomer = name;
                this.rescheduleDate = date;
                this.rescheduleOpen = true;
                await this.loadSlots(date);
            },

            async loadSlots(date) {
                if (!date) return;
                this.loadingSlots = true;
                this.availableSlots = [];
                try {
                    const res = await fetch(`/admin/available-slots?date=${date}&exclude_id=${this.rescheduleId}`);
                    this.availableSlots = await res.json();
                } catch (e) {
                    console.error('Gagal load slots:', e);
                }
                this.loadingSlots = false;
            },

            submitReschedule() {
                const form = document.getElementById('reschedule-form');
                form.action = '/admin/reschedule/' + this.rescheduleId;
                form.submit();
            }
        }
    }
</script>
@endsection