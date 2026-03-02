@extends('layouts.app')

@section('content')
<style>
    [x-cloak] { display: none !important; }
    .hide-scrollbar::-webkit-scrollbar { display: none; }
    .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    .date-slider-container {
        display: flex;
        gap: 12px;
        overflow-x: auto;
        scroll-snap-type: x mandatory;
        scroll-behavior: smooth;
        -webkit-overflow-scrolling: touch;
        padding: 10px 0;
    }
    .date-card {
        flex: 0 0 auto;
        scroll-snap-align: start;
    }
</style>

<div x-data="{ 
    selectedDate: '{{ $date }}',
    selectedSlot: '',
    openModal: false,
    currentMonth: '{{ \Carbon\Carbon::parse($date)->format('m') }}',

    scrollLeft() { $refs.slider.scrollBy({ left: -300, behavior: 'smooth' }) },
    scrollRight() { $refs.slider.scrollBy({ left: 300, behavior: 'smooth' }) },
    
    selectDate(date) {
        window.location.href = '/?date=' + date;
    }
}" 
x-init="
    $nextTick(() => {
        const activeElem = $refs.slider.querySelector('.bg-black');
    })
"
class="max-w-xl mx-auto pb-24 px-4">

    <div class="py-8 text-center">
        <h1 class="text-4xl font-black italic tracking-tighter italic text-slate-900">TRIMLY<span class="text-amber-500">.</span></h1>
        <p class="text-slate-400 text-[10px] font-bold uppercase tracking-[0.3em] mt-1 italic">Toko Buka • Antrean Normal</p>
    </div>

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
                $daysInMonth = $startOfMonth->daysInMonth;
            @endphp

            @for($i = 0; $i < $daysInMonth; $i++)
                @php 
                    $targetDate = $startOfMonth->copy()->addDays($i);
                    $active = $targetDate->toDateString() == $date;
                    $isPast = $targetDate->isPast() && !$targetDate->isToday();
                @endphp
                
                <button 
                    @click="if(!{{ $isPast ? 'true' : 'false' }}) selectDate('{{ $targetDate->toDateString() }}')"
                    class="date-card w-20 h-28 rounded-[2rem] flex flex-col items-center justify-center transition-all border-2
                    {{ $active ? 'bg-black border-black text-white shadow-xl scale-105 z-10' : 'bg-white border-slate-100 text-slate-500 hover:border-slate-300' }}
                    {{ $isPast ? 'opacity-30 cursor-not-allowed border-transparent' : '' }}">
                    
                    <span class="text-[9px] uppercase font-black opacity-60 mb-1 tracking-tighter">{{ $targetDate->isoFormat('ddd') }}</span>
                    <span class="text-2xl font-black tracking-tighter leading-none">{{ $targetDate->format('d') }}</span>
                    <span class="text-[9px] font-bold mt-2 uppercase opacity-80">{{ $targetDate->isoFormat('MMM') }}</span>

                    @if($targetDate->isToday() && !$active)
                        <span class="mt-2 w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                    @endif
                </button>
            @endfor
        </div>
    </div>

    <div class="mb-10">
        <div class="flex justify-between items-end mb-6">
            <h3 class="font-black text-2xl text-slate-900 tracking-tighter italic uppercase">PILIH JAM</h3>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest italic">Waktu Setempat (WIB)</p>
        </div>

        <div class="grid grid-cols-3 gap-4">
            @foreach($timeSlots as $slot)
                @php 
                    $slotTime = substr($slot, 0, 5);
                    $isBooked = in_array($slotTime, $bookedSlots); 
                    $isPastTime = now()->toDateString() == $date && now()->format('H:i') > $slotTime;
                @endphp
                
                <button 
                    @click="if(!{{ $isBooked || $isPastTime ? 'true' : 'false' }}) { selectedSlot = '{{ $slotTime }}'; openModal = true }"
                    :class="selectedSlot === '{{ $slotTime }}' ? 'ring-4 ring-amber-500/20 border-black bg-slate-50' : ''"
                    class="relative py-6 rounded-[2rem] border-2 text-center transition-all
                    {{ $isBooked || $isPastTime ? 'bg-slate-50 border-transparent text-slate-200 cursor-not-allowed' : 'bg-white border-slate-100 hover:border-black active:scale-95 shadow-sm' }}">
                    
                    <span class="block font-black text-xl tracking-tighter italic">{{ $slotTime }}</span>
                    
                    @if($isBooked)
                        <span class="text-[8px] font-black uppercase text-red-300 tracking-widest">Sudah di pesan</span>
                    @elseif($isPastTime)
                        <span class="text-[8px] font-black uppercase text-slate-300 tracking-widest">Canceled / End</span>
                    @else
                        {{-- Jika belum lewat dan tidak ada pesanan --}}
                        <span class="text-[8px] font-black uppercase text-green-500 tracking-widest group-hover:text-black">Tersedia</span>
                    @endif
                </button>
            @endforeach
        </div>
    </div>

    </div>
@endsection