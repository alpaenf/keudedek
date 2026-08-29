@extends('layouts.app')

@section('title', 'Detail Pengajuan')
@section('page-title', 'Detail Pengajuan: ' . $submission->submission_number)

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
    <!-- Main Submission Details -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm lg:col-span-2">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
            <div>
                <span class="font-mono text-xs font-bold text-sky-600">{{ $submission->submission_number }}</span>
                <h3 class="text-xl font-bold text-slate-900 mt-1">{{ $submission->title }}</h3>
                <p class="text-xs text-slate-500 mt-1">Pembuat: {{ $submission->creator->name }} &bull; {{ $submission->department->name }}</p>
            </div>
            
            @php
                $badgeClass = match($submission->status) {
                    'DRAFT' => 'bg-slate-100 text-slate-700 border-slate-300',
                    'SUBMITTED' => 'bg-blue-100 text-blue-800 border-blue-300',
                    'REVIEW' => 'bg-amber-100 text-amber-800 border-amber-300',
                    'APPROVED', 'RESERVED' => 'bg-purple-100 text-purple-800 border-purple-300',
                    'COMPLETED' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                    'RETURNED' => 'bg-orange-100 text-orange-800 border-orange-300',
                    'REJECTED' => 'bg-rose-100 text-rose-800 border-rose-300',
                    default => 'bg-slate-100 text-slate-800 border-slate-300',
                };
            @endphp
            <span class="px-3 py-1 rounded-full text-xs font-extrabold border {{ $badgeClass }}">{{ $submission->status }}</span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-6">
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                <span class="text-slate-400 text-[11px] block">Mata Anggaran</span>
                <span class="font-mono font-bold text-slate-800 text-xs">{{ $submission->budgetBucket->account_code }}</span>
            </div>
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                <span class="text-slate-400 text-[11px] block">Sumber Dana</span>
                <span class="font-bold text-slate-800 text-xs">{{ $submission->budgetBucket->fundingSource->code }}</span>
            </div>
            <div class="p-3 bg-sky-50 rounded-xl border border-sky-100">
                <span class="text-sky-600 text-[11px] block">Total Nominal</span>
                <span class="font-bold text-sky-900 text-sm">Rp {{ number_format($submission->amount, 0, ',', '.') }}</span>
            </div>
        </div>

        @if($submission->notes)
            <div class="mb-6 p-4 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                <span class="font-bold text-slate-700 block mb-1">Catatan Workflow:</span>
                <p class="text-slate-600">{{ $submission->notes }}</p>
            </div>
        @endif

        <!-- Rincian Items Table -->
        <h4 class="font-bold text-slate-900 text-sm mb-3">Rincian Barang / Belanja</h4>
        <div class="border border-slate-200 rounded-xl overflow-hidden text-xs">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
                        <th class="py-2.5 px-4">Nama Item</th>
                        <th class="py-2.5 px-4 text-center">Qty</th>
                        <th class="py-2.5 px-4 text-right">Harga Satuan</th>
                        <th class="py-2.5 px-4 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($submission->items as $item)
                        <tr>
                            <td class="py-3 px-4 font-medium">{{ $item->item_name }}</td>
                            <td class="py-3 px-4 text-center">{{ $item->quantity }}</td>
                            <td class="py-3 px-4 text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                            <td class="py-3 px-4 text-right font-bold">Rp {{ number_format($item->total_price, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Workflow Action Card -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <h4 class="font-bold text-slate-900 text-sm mb-2">Aksi Status Workflow</h4>
        <p class="text-xs text-slate-500 mb-4">Ubah status pengajuan sesuai alur persetujuan & reservasi anggaran.</p>

        <form action="{{ route('submissions.status', $submission) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Status Baru</label>
                <select name="status" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:ring-2 focus:ring-sky-500">
                    <option value="SUBMITTED" {{ $submission->status == 'SUBMITTED' ? 'selected' : '' }}>SUBMITTED (Kirim Pengajuan)</option>
                    <option value="REVIEW" {{ $submission->status == 'REVIEW' ? 'selected' : '' }}>REVIEW (Verifikasi PTU)</option>
                    <option value="APPROVED" {{ $submission->status == 'APPROVED' ? 'selected' : '' }}>APPROVED (Setujui & Reserve Budget)</option>
                    <option value="RETURNED" {{ $submission->status == 'RETURNED' ? 'selected' : '' }}>RETURNED (Kembalikan ke PTK)</option>
                    <option value="COMPLETED" {{ $submission->status == 'COMPLETED' ? 'selected' : '' }}>COMPLETED (Selesai Realisasi)</option>
                    <option value="REJECTED" {{ $submission->status == 'REJECTED' ? 'selected' : '' }}>REJECTED (Tolak Pengajuan)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Perubahan Status</label>
                <textarea name="notes" rows="3" placeholder="Catatan verifikasi / persetujuan..." class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs text-slate-800 focus:ring-2 focus:ring-sky-500"></textarea>
            </div>

            <button type="submit" class="w-full py-2.5 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-semibold transition">
                Update Status Pengajuan
            </button>
        </form>
    </div>
</div>
@endsection
