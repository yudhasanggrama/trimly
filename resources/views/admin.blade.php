@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto py-6 px-4"
     x-data="{
        filterStatus: 'all',
        filterDate: '',
        filterSearch: '',

        applyFilter() {
            document.querySelectorAll('[data-row]').forEach(row => {
                const status = row.dataset.status;
                const date = row.dataset.date;
                const name = row.dataset.name;

                const matchStatus = this.filterStatus === 'all' || status === this.filterStatus;
                const matchDate = this.filterDate === '' || date === this.filterDate;
                const matchSearch = this.filterSearch === '' || name.toLowerCase().includes(this.filterSearch.toLowerCase());

                row.style.display = (matchStatus && matchDate && matchSearch) ? '' : 'none';
            });

            const visible = document.querySelectorAll('[data-row]:not([style*=\'display: none\'])').length;
            document.getElementById('result-count').textContent = visible;
        },

        resetFilter() {
            this.filterStatus = 'all';
            this.filterDate = '';
            this.filterSearch = '';
            this.$nextTick(() => this.applyFilter());
        },

        async refreshTable() {
            let response = await fetch(window.location.href);
            let text = await response.text();
            let parser = new DOMParser();
            let htmlDocument = parser.parseFromString(text, 'text/html');

            let newTable = htmlDocument.getElementById('booking-table-content');
            if (newTable) document.getElementById('booking-table-content').innerHTML = newTable.innerHTML;

            let newCards = htmlDocument.getElementById('booking-cards-content');
            if (newCards) document.getElementById('booking-cards-content').innerHTML = newCards.innerHTML;

            this.$nextTick(() => this.applyFilter());
        }
     }"
     x-init="setInterval(() => refreshTable(), 5000)">

    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl sm:text-3xl font-black italic tracking-tighter text-slate-900 uppercase">Dashboard<span class="text-amber-500">.</span></h2>
            <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest mt-1">Manajemen Antrean TRIMLY</p>
        </div>
        <div class="flex items-center gap-2 bg-black text-white px-3 py-2 rounded-full">
            <span class="flex h-2 w-2 relative">
                <span class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-green-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
            </span>
            <span class="text-[10px] font-black uppercase tracking-widest">Live</span>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="mb-4 bg-green-50 border-2 border-green-100 p-4 rounded-2xl">
            <p class="text-green-700 text-xs font-black uppercase italic">✓ {{ session('success') }}</p>
        </div>
    @endif

    {{-- Filter Bar --}}
    <div class="bg-white border-2 border-slate-100 rounded-3xl p-5 mb-6 shadow-sm">

        {{-- Search + Date --}}
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
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block italic">Tanggal</label>
                <input type="date" x-model="filterDate" @change="applyFilter()"
                    class="w-full px-4 py-3 bg-slate-50 border-2 border-transparent focus:border-black rounded-2xl text-sm font-bold outline-none transition-all">
            </div>
        </div>

        {{-- Status Pills --}}
        <div class="flex flex-wrap gap-2 mb-4">
            <button @click="filterStatus = 'all'; applyFilter()"
                :class="filterStatus === 'all' ? 'bg-black text-white' : 'bg-slate-100 text-slate-500'"
                class="px-3 py-1.5 rounded-full text-[10px] font-black uppercase transition-all">Semua</button>
            <button @click="filterStatus = 'active'; applyFilter()"
                :class="filterStatus === 'active' ? 'bg-green-500 text-white' : 'bg-green-50 text-green-600'"
                class="px-3 py-1.5 rounded-full text-[10px] font-black uppercase transition-all">● Active</button>
            <button @click="filterStatus = 'on-progress'; applyFilter()"
                :class="filterStatus === 'on-progress' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-600'"
                class="px-3 py-1.5 rounded-full text-[10px] font-black uppercase transition-all">● On-Progress</button>
            <button @click="filterStatus = 'completed'; applyFilter()"
                :class="filterStatus === 'completed' ? 'bg-slate-700 text-white' : 'bg-slate-100 text-slate-500'"
                class="px-3 py-1.5 rounded-full text-[10px] font-black uppercase transition-all">● Completed</button>
            <button @click="filterStatus = 'cancelled'; applyFilter()"
                :class="filterStatus === 'cancelled' ? 'bg-red-500 text-white' : 'bg-red-50 text-red-500'"
                class="px-3 py-1.5 rounded-full text-[10px] font-black uppercase transition-all">● Cancelled</button>
        </div>

        {{-- Count + Reset --}}
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-[10px] font-black text-slate-400 uppercase italic">Menampilkan</span>
                <span class="bg-black text-amber-400 font-black text-sm px-3 py-1 rounded-full" id="result-count">{{ count($bookings) }}</span>
                <span class="text-[10px] font-black text-slate-400 uppercase italic">data</span>
            </div>
            <button @click="resetFilter()"
                class="px-4 py-2 bg-slate-100 hover:bg-red-50 hover:text-red-500 rounded-xl text-[10px] font-black uppercase transition-all">
                ✕ Reset
            </button>
        </div>
    </div>

    {{-- ==================== MOBILE & TABLET: Card Layout ==================== --}}
    <div class="lg:hidden space-y-3" id="booking-cards-content">
        @forelse($bookings as $b)
        <div class="bg-white border-2 border-slate-100 rounded-3xl p-5 shadow-sm"
             data-row
             data-status="{{ $b->status }}"
             data-date="{{ $b->booking_date }}"
             data-name="{{ $b->customer->name }}">

            {{-- Name + Status --}}
            <div class="flex justify-between items-start mb-4">
                <div>
                    <div class="font-black text-slate-900 uppercase italic">{{ $b->customer->name }}</div>
                    <div class="text-xs text-slate-400 font-bold mt-0.5">{{ $b->customer->phone }}</div>
                </div>
                <span class="px-3 py-1.5 rounded-full text-[10px] font-black uppercase ml-2 shrink-0
                    {{ $b->status == 'active' ? 'bg-green-100 text-green-700' : '' }}
                    {{ $b->status == 'on-progress' ? 'bg-blue-600 text-white animate-pulse' : '' }}
                    {{ $b->status == 'completed' ? 'bg-slate-100 text-slate-600' : '' }}
                    {{ $b->status == 'cancelled' ? 'bg-red-100 text-red-600' : '' }}">
                    {{ $b->status }}
                </span>
            </div>

            {{-- Schedule --}}
            <div class="flex items-center gap-4 bg-slate-50 rounded-2xl px-4 py-3 mb-4">
                <div>
                    <div class="text-[10px] font-black text-slate-400 uppercase italic mb-0.5">Tanggal</div>
                    <div class="text-sm font-black text-slate-700 uppercase">
                        {{ \Carbon\Carbon::parse($b->booking_date)->isoFormat('ddd, D MMM YYYY') }}
                    </div>
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

            {{-- Action Buttons --}}
            @if($b->status == 'active')
                <div class="grid grid-cols-2 gap-2">
                    <form action="{{ route('admin.start', $b->id) }}" method="POST">@csrf
                        <button class="w-full bg-blue-600 text-white py-3 rounded-2xl text-xs font-black uppercase hover:bg-blue-700 transition">▶ Mulai</button>
                    </form>
                    <form action="{{ route('admin.cancel', $b->id) }}" method="POST">@csrf
                        <button class="w-full bg-red-500 text-white py-3 rounded-2xl text-xs font-black uppercase hover:bg-red-600 transition">✕ Batal</button>
                    </form>
                </div>
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
                                {{ $b->status == 'completed' ? 'bg-slate-100 text-slate-600' : '' }}
                                {{ $b->status == 'cancelled' ? 'bg-red-100 text-red-600' : '' }}">
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
@endsection