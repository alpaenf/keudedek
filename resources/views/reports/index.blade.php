@extends('layouts.app')

@section('title', 'Laporan Realisasi')
@section('page-title', 'Laporan Realisasi & Serapan Anggaran (LRA)')

@section('content')
<div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h3 class="font-bold text-slate-900 text-base">Rekapitulasi LRA Fakultas Teknik</h3>
        <p class="text-xs text-slate-500">Laporan realisasi anggaran komprehensif tingkat unit & akun</p>
    </div>

    <form method="GET" action="{{ route('reports.index') }}" class="flex items-center gap-3">
        <select name="department_id" onchange="this.form.submit()" class="px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700">
            <option value="">Semua Jurusan / Unit</option>
            @foreach($departments as $dept)
                <option value="{{ $dept->id }}" {{ $selectedDepartmentId == $dept->id ? 'selected' : '' }}>
                    {{ $dept->code }} - {{ $dept->name }}
                </option>
            @endforeach
        </select>
        <button type="button" onclick="window.print()" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-semibold flex items-center gap-2 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Cetak / Export PDF
        </button>
    </form>
</div>

<!-- Financial Totals Banner -->
<div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
    <div class="bg-white p-4 rounded-xl border border-slate-200">
        <span class="text-xs text-slate-400 block">Total Pagu Aktif</span>
        <span class="font-bold text-slate-900 text-base">Rp {{ number_format($totalAllocated, 0, ',', '.') }}</span>
    </div>
    <div class="bg-white p-4 rounded-xl border border-slate-200">
        <span class="text-xs text-slate-400 block">Total Komitmen (Reserved)</span>
        <span class="font-bold text-amber-600 text-base">Rp {{ number_format($totalReserved, 0, ',', '.') }}</span>
    </div>
    <div class="bg-white p-4 rounded-xl border border-slate-200">
        <span class="text-xs text-slate-400 block">Total Realisasi</span>
        <span class="font-bold text-emerald-600 text-base">Rp {{ number_format($totalRealized, 0, ',', '.') }}</span>
    </div>
    <div class="bg-white p-4 rounded-xl border border-slate-200">
        <span class="text-xs text-slate-400 block">Total Saldo Tersedia</span>
        <span class="font-bold text-indigo-600 text-base">Rp {{ number_format($totalAvailable, 0, ',', '.') }}</span>
    </div>
</div>

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
            <thead>
                <tr class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
                    <th class="py-3.5 px-6">Kode & Nama Akun</th>
                    <th class="py-3.5 px-6">Jurusan</th>
                    <th class="py-3.5 px-6 text-right">Pagu Aktif (Rp)</th>
                    <th class="py-3.5 px-6 text-right">Reserved (Rp)</th>
                    <th class="py-3.5 px-6 text-right">Realisasi (Rp)</th>
                    <th class="py-3.5 px-6 text-right">Available (Rp)</th>
                    <th class="py-3.5 px-6 text-center">% Serapan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @foreach($buckets as $bucket)
                    @php
                        $rate = $bucket->allocated_budget > 0 ? (($bucket->realized_budget + $bucket->reserved_budget) / $bucket->allocated_budget) * 100 : 0;
                    @endphp
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="py-4 px-6">
                            <span class="font-mono font-bold text-sky-700 block">{{ $bucket->account_code }}</span>
                            <span class="font-medium text-slate-900">{{ $bucket->account_name }}</span>
                        </td>
                        <td class="py-4 px-6 font-semibold">{{ $bucket->department->code }}</td>
                        <td class="py-4 px-6 text-right font-medium">Rp {{ number_format($bucket->allocated_budget, 0, ',', '.') }}</td>
                        <td class="py-4 px-6 text-right text-amber-600 font-medium">Rp {{ number_format($bucket->reserved_budget, 0, ',', '.') }}</td>
                        <td class="py-4 px-6 text-right text-emerald-600 font-medium">Rp {{ number_format($bucket->realized_budget, 0, ',', '.') }}</td>
                        <td class="py-4 px-6 text-right text-indigo-600 font-bold">Rp {{ number_format($bucket->available_balance, 0, ',', '.') }}</td>
                        <td class="py-4 px-6 text-center font-bold text-slate-800">{{ number_format($rate, 1) }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
