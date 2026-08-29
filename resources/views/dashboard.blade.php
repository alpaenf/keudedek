@extends('layouts.app')

@section('title', 'Dashboard Monitoring Anggaran')
@section('page-title', 'Dashboard Overview Keuangan')

@section('content')
<!-- Filter Bar -->
<div class="mb-8 bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-wrap items-center justify-between gap-4">
    <div class="flex items-center gap-2">
        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/></svg>
        <span class="text-sm font-semibold text-slate-700">Filter Unit / Jurusan:</span>
    </div>

    <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-3">
        <select name="department_id" onchange="this.form.submit()" class="px-4 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-500">
            <option value="">-- Seluruh Fakultas Teknik --</option>
            @foreach($departments as $dept)
                <option value="{{ $dept->id }}" {{ $selectedDepartmentId == $dept->id ? 'selected' : '' }}>
                    {{ $dept->code }} - {{ $dept->name }}
                </option>
            @endforeach
        </select>
        @if($selectedDepartmentId)
            <a href="{{ route('dashboard') }}" class="px-3 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs font-medium transition">
                Reset Filter
            </a>
        @endif
    </form>
</div>

<!-- KPI Cards Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Pagu Aktif -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden">
        <div class="flex items-center justify-between mb-4">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Pagu Aktif</span>
            <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
        <div class="text-2xl font-bold text-slate-900 mb-1">Rp {{ number_format($totalAllocated, 0, ',', '.') }}</div>
        <p class="text-xs text-slate-500">Total alokasi pagu disetujui</p>
    </div>

    <!-- Reserved / Komitmen -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden">
        <div class="flex items-center justify-between mb-4">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Komitmen (Reserved)</span>
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </div>
        </div>
        <div class="text-2xl font-bold text-amber-600 mb-1">Rp {{ number_format($totalReserved, 0, ',', '.') }}</div>
        <p class="text-xs text-slate-500">Anggaran dikunci pada pengajuan approved</p>
    </div>

    <!-- Realisasi Final -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden">
        <div class="flex items-center justify-between mb-4">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Realisasi</span>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
        <div class="text-2xl font-bold text-emerald-600 mb-1">Rp {{ number_format($totalRealized, 0, ',', '.') }}</div>
        <p class="text-xs text-slate-500">Telah dicairkan & SPJ diverifikasi</p>
    </div>

    <!-- Saldo Tersedia -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden">
        <div class="flex items-center justify-between mb-4">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Saldo Tersedia</span>
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
            </div>
        </div>
        <div class="text-2xl font-bold text-indigo-600 mb-1">Rp {{ number_format($totalAvailable, 0, ',', '.') }}</div>
        <p class="text-xs text-slate-500">Sisa saldo aman untuk pengajuan baru</p>
    </div>
</div>

<!-- Serapan Progress & Warning Alert Bar -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
    <!-- Progress Bar Card -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm lg:col-span-2">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="font-bold text-slate-900 text-base">Tingkat Penyerapan Anggaran Total</h3>
                <p class="text-xs text-slate-500">Persentase Realisasi + Komitmen terhadap Pagu Aktif</p>
            </div>
            <span class="text-lg font-extrabold text-sky-600">{{ number_format($absorptionRate, 1) }}%</span>
        </div>
        
        <div class="w-full bg-slate-100 rounded-full h-4 overflow-hidden mb-4 p-0.5 border border-slate-200">
            <div class="bg-gradient-to-r from-sky-500 to-indigo-600 h-full rounded-full transition-all duration-500" style="width: {{ min(100, $absorptionRate) }}%"></div>
        </div>

        <div class="grid grid-cols-3 text-center text-xs border-t border-slate-100 pt-4">
            <div>
                <span class="text-slate-400 block">Realisasi</span>
                <span class="font-bold text-slate-800">Rp {{ number_format($totalRealized, 0, ',', '.') }}</span>
            </div>
            <div>
                <span class="text-slate-400 block">Komitmen</span>
                <span class="font-bold text-slate-800">Rp {{ number_format($totalReserved, 0, ',', '.') }}</span>
            </div>
            <div>
                <span class="text-slate-400 block">Saldo Bebas</span>
                <span class="font-bold text-slate-800">Rp {{ number_format($totalAvailable, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <!-- Active EWS Alert Summary Card -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-slate-900 text-base flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    Peringatan Dini (EWS)
                </h3>
                <span class="px-2.5 py-1 bg-amber-100 text-amber-800 rounded-full text-xs font-bold">{{ $activeWarningsCount }} Aktif</span>
            </div>
            <p class="text-xs text-slate-500 mb-4">Indikator deteksi dini batas ketersediaan saldo di bawah threshold.</p>
        </div>

        <div>
            @if($activeWarnings->count() > 0)
                <div class="space-y-2 mb-4">
                    @foreach($activeWarnings->take(2) as $warn)
                        <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs">
                            <div class="flex items-center justify-between font-bold text-amber-900 mb-1">
                                <span>{{ $warn->rule_code }} - {{ $warn->department?->code ?? 'FT' }}</span>
                                <span class="uppercase text-[10px] px-2 py-0.5 rounded font-extrabold {{ $warn->severity == 'CRITICAL' ? 'bg-rose-600 text-white' : 'bg-amber-600 text-white' }}">{{ $warn->severity }}</span>
                            </div>
                            <p class="text-amber-800 text-[11px] line-clamp-2">{{ $warn->message }}</p>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs text-center font-medium mb-4">
                    Seluruh pos anggaran dalam batas aman.
                </div>
            @endif

            <a href="{{ route('warnings.index') }}" class="w-full py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-semibold text-center block transition">
                Lihat Seluruh Early Warning
            </a>
        </div>
    </div>
</div>

<!-- Department Comparison Table -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-8">
    <div class="p-6 border-b border-slate-200 flex items-center justify-between">
        <div>
            <h3 class="font-bold text-slate-900 text-base">Rekapitulasi Anggaran Antar Jurusan</h3>
            <p class="text-xs text-slate-500">Perbandingan Pagu, Komitmen, Realisasi, dan Saldo per Unit</p>
        </div>
        <a href="{{ route('reports.index') }}" class="px-3 py-1.5 bg-sky-50 text-sky-700 hover:bg-sky-100 rounded-lg text-xs font-semibold transition">
            Lihat Laporan Lengkap
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
            <thead>
                <tr class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
                    <th class="py-3.5 px-6">Jurusan / Unit</th>
                    <th class="py-3.5 px-6 text-right">Pagu Aktif</th>
                    <th class="py-3.5 px-6 text-right">Reserved (Komitmen)</th>
                    <th class="py-3.5 px-6 text-right">Realisasi</th>
                    <th class="py-3.5 px-6 text-right">Saldo Tersedia</th>
                    <th class="py-3.5 px-6 text-center">Status Saldo</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @foreach($departmentSummaries as $dept)
                    @php
                        $pagu = $dept->budgetBuckets->sum('allocated_budget');
                        $reserved = $dept->budgetBuckets->sum('reserved_budget');
                        $realized = $dept->budgetBuckets->sum('realized_budget');
                        $available = $dept->budgetBuckets->sum('available_balance');
                        $percentage = $pagu > 0 ? ($available / $pagu) * 100 : 0;
                    @endphp
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="py-4 px-6 font-semibold text-slate-900">{{ $dept->name }} ({{ $dept->code }})</td>
                        <td class="py-4 px-6 text-right font-medium">Rp {{ number_format($pagu, 0, ',', '.') }}</td>
                        <td class="py-4 px-6 text-right text-amber-600 font-medium">Rp {{ number_format($reserved, 0, ',', '.') }}</td>
                        <td class="py-4 px-6 text-right text-emerald-600 font-medium">Rp {{ number_format($realized, 0, ',', '.') }}</td>
                        <td class="py-4 px-6 text-right text-indigo-600 font-bold">Rp {{ number_format($available, 0, ',', '.') }}</td>
                        <td class="py-4 px-6 text-center">
                            @if($percentage < 10)
                                <span class="px-2.5 py-1 bg-rose-100 text-rose-800 rounded-full font-bold text-[10px]">Kritis (< 10%)</span>
                            @elseif($percentage < 20)
                                <span class="px-2.5 py-1 bg-amber-100 text-amber-800 rounded-full font-bold text-[10px]">Perhatian (< 20%)</span>
                            @else
                                <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 rounded-full font-bold text-[10px]">Aman</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
