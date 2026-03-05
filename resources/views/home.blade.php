@extends('layouts.app')

@section('content')
<style>
    [x-cloak] { display: none !important; }
    .hide-scrollbar::-webkit-scrollbar { display: none; }
    .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    .date-slider-container {
        display: flex; gap: 12px; overflow-x: auto;
        scroll-snap-type: x mandatory; scroll-behavior: smooth;
        -webkit-overflow-scrolling: touch; padding: 10px 0;
    }
    .date-card { flex: 0 0 auto; scroll-snap-align: start; }
</style>

<div x-data="{
    selectedDate: '{{ $date }}',
    selectedSlot: '',
    openModal: false,
    currentMonth: '{{ \Carbon\Carbon::parse($date)->format('m') }}',

    bookedSlots: {{ json_encode($bookedSlots) }},
    now: '{{ now()->format('H:i') }}',
    today: '{{ now()->toDateString() }}',

    _abortController: null,

    isBooked(slot) {
        return this.bookedSlots.includes(slot);
    },

    isPast(slot) {
        return this.selectedDate === this.today && this.now >= slot;
    },

    isUnavailable(slot) {
        return this.isBooked(slot) || this.isPast(slot);
    },

    slotLabel(slot) {
        if (this.isBooked(slot)) return 'Penuh';
        if (this.isPast(slot)) return 'Selesai';
        return 'Tersedia';
    },

    slotLabelColor(slot) {
        if (this.isBooked(slot)) return 'text-red-300';
        if (this.isPast(slot)) return 'text-slate-300';
        return 'text-green-500';
    },

    slotBtnClass(slot) {
        if (this.isUnavailable(slot))
            return 'bg-slate-50 border-transparent text-slate-200 cursor-not-allowed';
        return 'bg-white border-slate-100 hover:border-black active:scale-95 shadow-sm';
    },

    async refreshSlots() {
        // Batalkan request sebelumnya jika masih pending — ini yang mencegah request menumpuk
        if (this._abortController) {
            this._abortController.abort();
        }
        this._abortController = new AbortController();

        try {
            const res = await fetch('/?date=' + this.selectedDate + '&json=1', {
                signal: this._abortController.signal
            });
            const data = await res.json();
            this.bookedSlots = data.bookedSlots;
            this.now         = data.now;
        } catch(e) {
            if (e.name !== 'AbortError') {
                console.error('Gagal refresh slots:', e);
            }
        }
    },

    scrollLeft()  { this.$refs.slider.scrollBy({ left: -300, behavior: 'smooth' }) },
    scrollRight() { this.$refs.slider.scrollBy({ left:  300, behavior: 'smooth' }) },

    selectDate(date) {
        window.location.href = '/?date=' + date;
    }
}"
x-init="
    setInterval(() => refreshSlots(), 15000);
"
class="max-w-xl mx-auto pb-24 px-4">

    {{-- Header --}}
    <div class="py-8 text-center">
        <h1 class="text-4xl font-black italic tracking-tighter text-slate-900 uppercase">TRIMLY<span class="text-amber-500">.</span></h1>
        <p class="text-slate-400 text-[10px] font-bold uppercase tracking-[0.3em] mt-1 italic">Toko Buka • Antrean Normal</p>
    </div>

    {{-- Notifikasi Pesan Error --}}
    @if($errors->any())
        <div class="mb-6 bg-red-50 border-2 border-red-100 p-4 rounded-2xl">
            <p class="text-red-600 text-[10px] font-black uppercase italic">{{ $errors->first() }}</p>
        </div>
    @endif

    {{-- Pemilihan Bulan --}}
    <div class="mb-4">
        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 mb-2 block italic">Periode Booking</label>
        <div class="relative bg-white border-2 border-slate-100 rounded-3xl p-1 shadow-sm focus-within:border-black transition-all">
            <select x-model="currentMonth" @change="window.location.href = '/?date=2026-' + $event.target.value + '-01'"
                    class="w-full bg-transparent font-black text-slate-900 py-3 px-4 outline-none appearance-none cursor-pointer uppercase">
                @for($m = 1; $m <= 12; $m++)
                    @php $monthDate = \Carbon\Carbon::create(2026, $m, 1); @endphp
                    <option value="{{ $monthDate->format('m') }}" {{ $m == \Carbon\Carbon::parse($date)->month ? 'selected' : '' }}>
                        {{ $monthDate->isoFormat('MMMM YYYY') }}
                    </option>
                @endfor
            </select>
            <div class="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
            </div>
        </div>
    </div>

    {{-- Slider Tanggal --}}
    <div class="mb-10 relative group">
        <div class="flex justify-between items-center mb-3">
            <h3 class="font-black text-sm text-slate-800 italic uppercase tracking-tighter">Pilih Tanggal</h3>
            <div class="flex gap-2">
                <button @click="scrollLeft()" class="w-8 h-8 rounded-full border border-slate-200 flex items-center justify-center hover:bg-black hover:text-white transition shadow-sm bg-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7"></path></svg>
                </button>
                <button @click="scrollRight()" class="w-8 h-8 rounded-full border border-slate-200 flex items-center justify-center hover:bg-black hover:text-white transition shadow-sm bg-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"></path></svg>
                </button>
            </div>
        </div>

        <div x-ref="slider" class="date-slider-container hide-scrollbar">
            @php
                $startOfMonth = \Carbon\Carbon::parse($date)->startOfMonth();
                $daysInMonth  = $startOfMonth->daysInMonth;
            @endphp
            @for($i = 0; $i < $daysInMonth; $i++)
                @php
                    $targetDate = $startOfMonth->copy()->addDays($i);
                    $active     = $targetDate->toDateString() == $date;
                    $isPast     = $targetDate->isPast() && !$targetDate->isToday();
                @endphp
                <button @click="if(!{{ $isPast ? 'true' : 'false' }}) selectDate('{{ $targetDate->toDateString() }}')"
                    class="date-card w-20 h-28 rounded-[2rem] flex flex-col items-center justify-center transition-all border-2
                    {{ $active ? 'bg-black border-black text-white shadow-xl scale-105 z-10' : 'bg-white border-slate-100 text-slate-500 hover:border-slate-300' }}
                    {{ $isPast ? 'opacity-30 cursor-not-allowed border-transparent' : '' }}">
                    <span class="text-[9px] uppercase font-black opacity-60 mb-1 tracking-tighter">{{ $targetDate->isoFormat('ddd') }}</span>
                    <span class="text-2xl font-black tracking-tighter leading-none">{{ $targetDate->format('d') }}</span>
                    <span class="text-[9px] font-bold mt-2 uppercase opacity-80">{{ $targetDate->isoFormat('MMM') }}</span>
                </button>
            @endfor
        </div>
    </div>

    {{-- Grid Jam --}}
    <div class="mb-10">
        <div class="flex justify-between items-end mb-6">
            <h3 class="font-black text-2xl text-slate-900 tracking-tighter italic uppercase">PILIH JAM</h3>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest italic">Waktu Setempat (WIB)</p>
        </div>

        <div class="grid grid-cols-3 gap-4">
            @foreach($timeSlots as $slot)
                <button
                    @click="if(!isUnavailable('{{ $slot }}')) { selectedSlot = '{{ $slot }}'; openModal = true }"
                    :class="slotBtnClass('{{ $slot }}')"
                    class="relative py-6 rounded-[2rem] border-2 text-center transition-all">
                    <span class="block font-black text-xl tracking-tighter italic">{{ $slot }}</span>
                    <span class="text-[8px] font-black uppercase tracking-widest"
                          :class="slotLabelColor('{{ $slot }}')"
                          x-text="slotLabel('{{ $slot }}')"></span>
                </button>
            @endforeach
        </div>
    </div>

    {{-- Modal Konfirmasi --}}
    <div x-show="openModal"
        x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center p-4"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100">

        <div @click="openModal = false" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>

        <div class="bg-white w-full max-w-md rounded-[2.5rem] p-8 relative shadow-2xl z-10"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="scale-90 opacity-0"
                x-transition:enter-end="scale-100 opacity-100">

            <h3 class="text-3xl font-black italic tracking-tighter text-slate-900 uppercase mb-6">
                KONFIRMASI<span class="text-amber-500">.</span>
            </h3>

            <form action="{{ route('booking.store') }}" method="POST">
                @csrf
                <input type="hidden" name="booking_date" :value="selectedDate">
                <input type="hidden" name="booking_time" :value="selectedSlot">

                @auth
                    <div class="bg-slate-50 p-5 rounded-2xl border-2 border-slate-100 mb-6">
                        <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Booking Sebagai:</p>
                        <p class="font-black text-lg text-slate-900 uppercase italic">{{ auth()->user()->name }}</p>
                        <p class="text-xs font-bold text-slate-500">{{ auth()->user()->phone }}</p>
                    </div>
                @endauth

                @guest
                    <div class="space-y-4 mb-6">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-2 mb-1 block tracking-widest italic">Nama Lengkap</label>
                            <input type="text" name="name" required placeholder="NAMA"
                                class="w-full bg-slate-50 border-2 border-transparent focus:border-black p-4 rounded-2xl font-bold outline-none uppercase transition-all">
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-2 mb-1 block tracking-widest italic">Nomor WhatsApp</label>
                            <input type="tel" name="phone" required placeholder="0812XXXXXXXX"
                                class="w-full bg-slate-50 border-2 border-transparent focus:border-black p-4 rounded-2xl font-bold outline-none transition-all">
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-2 mb-1 block tracking-widest italic">Email</label>
                            <input type="email" name="email" required placeholder="youremail@example.com"
                                class="w-full bg-slate-50 border-2 border-transparent focus:border-black p-4 rounded-2xl font-bold outline-none transition-all">
                        </div>
                    </div>
                @endguest

                <div class="flex justify-between items-center mb-6 px-2 bg-amber-50 p-3 rounded-xl">
                    <span class="text-[10px] font-black text-amber-600 uppercase italic">Jadwal:</span>
                    <span class="font-black text-slate-900 uppercase italic" x-text="selectedDate + ' @ ' + selectedSlot"></span>
                </div>

                <button type="submit"
                    class="w-full bg-black text-white py-5 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-amber-500 transition shadow-lg active:scale-95">
                    AMANKAN SLOT SEKARANG
                </button>

                <button type="button" @click="openModal = false"
                    class="w-full py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest mt-2 hover:text-red-500 transition">
                    Batal
                </button>
            </form>
        </div>
    </div>

    {{-- Success Modal --}}
    @if($successBooking)
        <div
            x-data="{ openSuccess: true }"
            x-show="openSuccess"
            x-cloak
            class="fixed inset-0 z-[200] flex items-center justify-center p-4"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100">

            <div @click="openSuccess = false" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>

            <div class="bg-white w-full max-w-md rounded-[3rem] shadow-2xl overflow-hidden border-2 border-slate-50 z-10">
                <div class="bg-black p-8 text-center">
                    <div class="w-16 h-16 bg-amber-500 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h2 class="text-white font-black italic text-2xl uppercase tracking-tighter">Booking Berhasil!</h2>
                    <p class="text-slate-400 text-[10px] font-bold uppercase tracking-widest mt-1">Tunjukkan tiket ini ke kasir</p>
                </div>

                <div class="p-8 space-y-6">
                    <div class="flex justify-between border-b border-dashed pb-4">
                        <span class="text-xs font-black text-slate-400 uppercase">Pelanggan</span>
                        <span class="text-xs font-black text-slate-900 uppercase italic">{{ $successBooking->customer->name }}</span>
                    </div>
                    <div class="flex justify-between border-b border-dashed pb-4">
                        <span class="text-xs font-black text-slate-400 uppercase">Tanggal</span>
                        <span class="text-xs font-black text-slate-900 uppercase italic">{{ $successBooking->booking_date }}</span>
                    </div>
                    <div class="text-center py-4 bg-slate-50 rounded-2xl">
                        <span class="block text-[10px] font-black text-slate-400 uppercase mb-1">Jam Kedatangan</span>
                        <span class="text-5xl font-black italic tracking-tighter text-slate-900">{{ substr($successBooking->booking_time, 0, 5) }}</span>
                    </div>
                    <button @click="openSuccess = false"
                        class="block w-full bg-slate-100 text-center py-4 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-black hover:text-white transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
@endsection