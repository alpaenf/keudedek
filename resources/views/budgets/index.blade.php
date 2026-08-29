@extends('layouts.app')

@section('title', 'Pagu Anggaran')
@section('page-title', 'Daftar Pos Pagu Anggaran Unit')

@section('content')
<div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h3 class="font-bold text-slate-900 text-base">Budget Buckets Overview</h3>
        <p class="text-xs text-slate-500">Daftar alokasi pagu, komitmen reservasi, dan ketersediaan saldo per mata anggaran</p>
    </div>

    <form method="GET" action="{{ route('budgets.index') }}" class="flex items-center gap-3">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Kode / Nama Akun..." class="px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500 w-64">
        
        <select name="department_id" onchange="this.form.submit()" class="px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-medium text-slate-700">
            <option value="">Semua Jurusan</option>
            @foreach($departments as $dept)
                <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                    {{ $dept->code }}
                </option>
            @endforeach
        </select>
        
        <button type="submit" class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded-lg text-xs font-semibold transition">
            Cari
        </button>
    </form>
</div>

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
            <thead>
                <tr class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
                    <th class="py-3.5 px-6">Mata Anggaran (Akun)</th>
                    <th class="py-3.5 px-6">Jurusan</th>
                    <th class="py-3.5 px-6">Sumber Dana</th>
                    <th class="py-3.5 px-6 text-right">Pagu Aktif</th>
                    <th class="py-3.5 px-6 text-right">Reserved</th>
                    <th class="py-3.5 px-6 text-right">Realisasi</th>
                    <th class="py-3.5 px-6 text-right">Saldo Tersedia</th>
                    <th class="py-3.5 px-6 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse($buckets as $bucket)
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="py-4 px-6">
                            <span class="font-mono font-bold text-sky-700 block">{{ $bucket->account_code }}</span>
                            <span class="font-medium text-slate-900">{{ $bucket->account_name }}</span>
                        </td>
                        <td class="py-4 px-6 font-semibold">{{ $bucket->department->code }}</td>
                        <td class="py-4 px-6"><span class="px-2 py-0.5 bg-slate-100 border border-slate-200 rounded text-[11px] font-mono">{{ $bucket->fundingSource->code }}</span></td>
                        <td class="py-4 px-6 text-right font-medium">Rp {{ number_format($bucket->allocated_budget, 0, ',', '.') }}</td>
                        <td class="py-4 px-6 text-right text-amber-600 font-medium">Rp {{ number_format($bucket->reserved_budget, 0, ',', '.') }}</td>
                        <td class="py-4 px-6 text-right text-emerald-600 font-medium">Rp {{ number_format($bucket->realized_budget, 0, ',', '.') }}</td>
                        <td class="py-4 px-6 text-right font-bold text-indigo-600">Rp {{ number_format($bucket->available_balance, 0, ',', '.') }}</td>
                        <td class="py-4 px-6 text-center">
                            <a href="{{ route('budgets.show', $bucket) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-medium transition">
                                Detail / Revisi
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-8 text-center text-slate-400">Tidak ada data pagu anggaran yang ditemukan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4 border-t border-slate-100">
        {{ $buckets->links() }}
    </div>
</div>
@endsection
