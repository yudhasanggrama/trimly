@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto bg-white shadow-xl rounded-2xl p-8" 
     x-data="{ 
        async refreshTable() {
            // Mengambil html dari halaman yang sama secara background
            let response = await fetch(window.location.href);
            let text = await response.text();
            
            // Mencari elemen tabel di html baru dan menggantinya di halaman sekarang
            let parser = new DOMParser();
            let htmlDocument = parser.parseFromString(text, 'text/html');
            let newTable = htmlDocument.getElementById('booking-table-content');
            document.getElementById('booking-table-content').innerHTML = newTable.innerHTML;
        }
     }" 
     x-init="setInterval(() => refreshTable(), 5000)">
    
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Dashboard Antrean</h2>
        <div class="flex items-center gap-2">
            <span class="flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-3 w-3 rounded-full bg-green-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
            </span>
            <span class="text-xs font-bold text-green-600">REAL-TIME ACTIVE</span>
        </div>
    </div>
    <div class="overflow-x-auto" id="booking-table-content">
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-100 text-left">
                    <th class="p-3">Pelanggan</th>
                    <th class="p-3">Jadwal</th>
                    <th class="p-3">Status</th>
                    <th class="p-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bookings as $b)
                <tr class="border-b hover:bg-gray-50">
                    <td class="p-3">
                        <div class="font-bold">{{ $b->customer->name }}</div>
                        <div class="text-xs text-gray-500">{{ $b->customer->phone }}</div>
                    </td>
                    <td class="p-3">
                        <div>{{ $b->booking_date }}</div>
                        <div class="text-amber-600 font-bold">{{ substr($b->booking_time,0,5) }}</div>
                    </td>
                    <td class="p-3">
                        <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase
                            {{ $b->status == 'active' ? 'bg-green-100 text-green-700' : '' }}
                            {{ $b->status == 'on-progress' ? 'bg-blue-600 text-white animate-pulse' : '' }}
                            {{ $b->status == 'completed' ? 'bg-gray-100 text-gray-600' : '' }}
                            {{ $b->status == 'cancelled' ? 'bg-red-100 text-red-700' : '' }}">
                            {{ $b->status }}
                        </span>
                        @if($b->status == 'completed')
                            <div class="text-[10px] text-gray-400 mt-1 italic">Selesai: {{ $b->updated_at->format('H:i') }} WIB</div>
                        @endif
                    </td>
                    <td class="p-3 text-right">
                        @if($b->status == 'active')
                            <form action="{{ route('admin.start', $b->id) }}" method="POST" class="inline">@csrf
                                <button class="bg-blue-500 text-white px-3 py-1 rounded-lg text-xs font-bold">START</button>
                            </form>
                            <form action="{{ route('admin.cancel', $b->id) }}" method="POST" class="inline">@csrf
                                <button class="bg-red-500 text-white px-3 py-1 rounded-lg text-xs font-bold">CANCEL</button>
                            </form>
                        @elseif($b->status == 'on-progress')
                            <form action="{{ route('admin.complete', $b->id) }}" method="POST" class="inline">@csrf
                                <button class="bg-green-600 text-white px-3 py-1 rounded-lg text-xs font-bold">FINISH</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection