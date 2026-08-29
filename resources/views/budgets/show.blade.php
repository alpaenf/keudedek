@extends('layouts.app')

@section('title', 'Detail Pagu Anggaran')
@section('page-title', 'Detail Pagu Akun: ' . $budgetBucket->account_code)

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
    <!-- Header Details Card -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm lg:col-span-2">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
            <div>
                <span class="px-2.5 py-1 bg-sky-100 text-sky-800 rounded font-mono text-xs font-bold">{{ $budgetBucket->account_code }}</span>
                <h3 class="text-xl font-bold text-slate-900 mt-2">{{ $budgetBucket->account_name }}</h3>
                <p class="text-xs text-slate-500 mt-1">{{ $budgetBucket->department->name }} ({{ $budgetBucket->department->code }}) &bull; Sumber Dana: {{ $budgetBucket->fundingSource->name }}</p>
            </div>
            <a href="{{ route('budgets.index') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition">
                Kembali
            </a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-center">
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                <span class="text-slate-400 text-[11px] block">Pagu Awal</span>
                <span class="font-bold text-slate-800 text-sm">Rp {{ number_format($budgetBucket->initial_budget, 0, ',', '.') }}</span>
            </div>
            <div class="p-3 bg-sky-50 rounded-xl border border-sky-100">
                <span class="text-sky-600 text-[11px] block">Pagu Aktif</span>
                <span class="font-bold text-sky-900 text-sm">Rp {{ number_format($budgetBucket->allocated_budget, 0, ',', '.') }}</span>
            </div>
            <div class="p-3 bg-amber-50 rounded-xl border border-amber-100">
                <span class="text-amber-600 text-[11px] block">Komitmen (Reserved)</span>
                <span class="font-bold text-amber-900 text-sm">Rp {{ number_format($budgetBucket->reserved_budget, 0, ',', '.') }}</span>
            </div>
            <div class="p-3 bg-indigo-50 rounded-xl border border-indigo-100">
                <span class="text-indigo-600 text-[11px] block">Saldo Tersedia</span>
                <span class="font-bold text-indigo-900 text-sm">Rp {{ number_format($budgetBucket->available_balance, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <!-- Apply Revision Form Card -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <h4 class="font-bold text-slate-900 text-sm mb-2">Revisi Pagu Anggaran</h4>
        <p class="text-xs text-slate-500 mb-4">Lakukan pergeseran/revisi nominal pagu aktif untuk pos anggaran ini.</p>

        <form action="{{ route('budgets.revise', $budgetBucket) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Pagu Aktif Baru (Rp)</label>
                <input type="number" step="1000" name="revised_amount" value="{{ old('revised_amount', $budgetBucket->allocated_budget) }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:ring-2 focus:ring-sky-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Alasan / Skematik Revisi</label>
                <textarea name="reason" rows="3" required placeholder="Contoh: Pergeseran sisa operasional lab ke riset..." class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs text-slate-800 focus:ring-2 focus:ring-sky-500"></textarea>
            </div>

            <button type="submit" class="w-full py-2.5 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-semibold transition">
                Simpan & Terapkan Revisi
            </button>
        </form>
    </div>
</div>

<!-- History Revisions Table -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-8">
    <div class="p-6 border-b border-slate-200">
        <h3 class="font-bold text-slate-900 text-base">Histori Revisi Anggaran</h3>
        <p class="text-xs text-slate-500">Jejak perubahan dan pergeseran nominal pagu aktif pada pos ini</p>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
            <thead>
                <tr class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
                    <th class="py-3 px-6">No. Revisi</th>
                    <th class="py-3 px-6 text-right">Pagu Sebelumnya</th>
                    <th class="py-3 px-6 text-right">Pagu Baru</th>
                    <th class="py-3 px-6 text-right">Selisih (+/-)</th>
                    <th class="py-3 px-6">Alasan</th>
                    <th class="py-3 px-6">Disetujui Oleh</th>
                    <th class="py-3 px-6">Waktu</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse($budgetBucket->revisions as $rev)
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="py-3.5 px-6 font-mono font-bold text-sky-700">{{ $rev->revision_number }}</td>
                        <td class="py-3.5 px-6 text-right">Rp {{ number_format($rev->previous_amount, 0, ',', '.') }}</td>
                        <td class="py-3.5 px-6 text-right font-bold">Rp {{ number_format($rev->revised_amount, 0, ',', '.') }}</td>
                        <td class="py-3.5 px-6 text-right font-bold {{ $rev->difference >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $rev->difference >= 0 ? '+' : '' }}Rp {{ number_format($rev->difference, 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-6">{{ $rev->reason }}</td>
                        <td class="py-3.5 px-6 font-medium">{{ $rev->approver->name }}</td>
                        <td class="py-3.5 px-6 text-slate-500">{{ $rev->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400">Belum ada riwayat revisi untuk pos anggaran ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
