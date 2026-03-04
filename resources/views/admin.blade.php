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
                    {{-- Tanggal --}}
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block italic">Tanggal Baru</label>
                        <input type="date" name="booking_date"
                            :min="today"
                            x-model="rescheduleDate"
                            @change="loadSlots($event.target.value)"
                            required
                            class="w-full px-4 py-3 bg-slate-50 border-2 border-transparent focus:border-black rounded-2xl text-sm font-bold outline-none transition-all">
                    </div>

                    {{-- Jam --}}
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block italic">
                            Jam Baru
                            <span x-show="loadingSlots" class="ml-1 text-amber-500 normal-case font-bold">Loading...</span>
                        </label>

                        {{-- Loading state --}}
                        <div x-show="loadingSlots" class="w-full px-4 py-3 bg-slate-50 rounded-2xl text-sm text-slate-400 font-bold animate-pulse">
                            Mengecek slot tersedia...
                        </div>

                        {{-- Slot tersedia --}}
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

                        {{-- Tidak ada slot --}}
                        <div x-show="!loadingSlots && availableSlots.length === 0 && rescheduleDate !== ''"
                            class="w-full px-4 py-3 bg-red-50 border-2 border-red-100 rounded-2xl text-xs font-black text-red-500 uppercase">
                            ✕ Tidak ada slot tersedia di tanggal ini
                        </div>

                        {{-- Belum pilih tanggal --}}
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

    {{-- Filter Bar --}}
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
             data-name="{{ $b->customer->name }}">

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
                <tbody>
                    @forelse($bookings as $b)
                    <tr class="border-b border-slate-50 hover:bg-amber-50 transition-colors"
                        data-row
                        data-status="{{ $b->status }}"
                        data-date="{{ $b->booking_date }}"
                        data-name="{{ $b->customer->name }}">
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

<script>
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

            init() {
                this.$nextTick(() => this.applyFilter());

                setInterval(() => {
                    if (document.visibilityState === 'visible') {
                        this.refreshTable();
                    }
                }, 2000);
            },

            async refreshTable() {
                if (this.loadingRefresh) return;
                this.loadingRefresh = true;

                try {
                    let response = await fetch(window.location.href);
                    let text = await response.text();
                    let parser = new DOMParser();
                    let htmlDocument = parser.parseFromString(text, 'text/html');

                    let newTable = htmlDocument.getElementById('booking-table-content');
                    if (newTable) {
                        document.getElementById('booking-table-content').innerHTML = newTable.innerHTML;
                    }

                    let newCards = htmlDocument.getElementById('booking-cards-content');
                    if (newCards) {
                        document.getElementById('booking-cards-content').innerHTML = newCards.innerHTML;
                    }

                    this.$nextTick(() => this.applyFilter());

                } catch (e) {
                    console.error('Refresh gagal:', e);
                }

                this.loadingRefresh = false;
            },

            applyFilter() {

                let rows = Array.from(document.querySelectorAll('[data-row]'));

                rows.forEach(row => {

                    const status = row.dataset.status;
                    const date   = row.dataset.date;
                    const name   = row.dataset.name;

                    const matchStatus = this.filterStatus === 'all' || status === this.filterStatus;
                    const matchDate   = this.filterDate === '' || date === this.filterDate;
                    const matchSearch = this.filterSearch === '' || 
                        name.includes(this.filterSearch.toLowerCase());

                    row.style.display = (matchStatus && matchDate && matchSearch) ? '' : 'none';
                });

                let visibleRows = rows.filter(row => row.style.display !== 'none');

                // 🔥 SORTING FIX (DATE + TIME)
                visibleRows.sort((a, b) => {

                    const dateTimeA = new Date(
                        a.dataset.date + ' ' + a.dataset.time
                    );

                    const dateTimeB = new Date(
                        b.dataset.date + ' ' + b.dataset.time
                    );

                    return this.sortOrder === 'newest'
                        ? dateTimeB - dateTimeA
                        : dateTimeA - dateTimeB;
                });

                visibleRows.forEach(row => {
                    row.parentNode.appendChild(row);
                });

                const result = document.getElementById('result-count');
                if (result) {
                    result.textContent = visibleRows.length;
                }
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