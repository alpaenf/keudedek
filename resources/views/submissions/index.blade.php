@extends('layouts.app')

@section('title', 'Daftar Pengajuan')
@section('page-title', 'Daftar Pengajuan Anggaran Unit')

@section('content')
<div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h3 class="font-bold text-slate-900 text-base">Monitoring Submissions</h3>
        <p class="text-xs text-slate-500">Kelola dan lacak alur status pengajuan anggaran dari PTK hingga Completed</p>
    </div>

    <div class="flex items-center gap-3">
        <form method="GET" action="{{ route('submissions.index') }}" class="flex items-center gap-2">
            <select name="status" onchange="this.form.submit()" class="px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700">
                <option value="">Semua Status</option>
                <option value="DRAFT" {{ request('status') == 'DRAFT' ? 'selected' : '' }}>DRAFT</option>
                <option value="SUBMITTED" {{ request('status') == 'SUBMITTED' ? 'selected' : '' }}>SUBMITTED</option>
                <option value="REVIEW" {{ request('status') == 'REVIEW' ? 'selected' : '' }}>REVIEW</option>
                <option value="APPROVED" {{ request('status') == 'APPROVED' ? 'selected' : '' }}>APPROVED</option>
                <option value="RESERVED" {{ request('status') == 'RESERVED' ? 'selected' : '' }}>RESERVED</option>
                <option value="COMPLETED" {{ request('status') == 'COMPLETED' ? 'selected' : '' }}>COMPLETED</option>
                <option value="RETURNED" {{ request('status') == 'RETURNED' ? 'selected' : '' }}>RETURNED</option>
            </select>
        </form>

        <a href="{{ route('submissions.create') }}" class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded-lg text-xs font-semibold flex items-center gap-2 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Buat Pengajuan Baru
        </a>
    </div>
</div>

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
            <thead>
                <tr class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
                    <th class="py-3.5 px-6">No. Pengajuan</th>
                    <th class="py-3.5 px-6">Kegiatan / Judul</th>
                    <th class="py-3.5 px-6">Jurusan</th>
                    <th class="py-3.5 px-6">Mata Anggaran</th>
                    <th class="py-3.5 px-6 text-right">Nominal</th>
                    <th class="py-3.5 px-6 text-center">Status</th>
                    <th class="py-3.5 px-6 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse($submissions as $sub)
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="py-4 px-6 font-mono font-bold text-sky-700">{{ $sub->submission_number }}</td>
                        <td class="py-4 px-6 font-medium text-slate-900">{{ $sub->title }}</td>
                        <td class="py-4 px-6 font-semibold">{{ $sub->department->code }}</td>
                        <td class="py-4 px-6 font-mono text-slate-600">{{ $sub->budgetBucket->account_code }}</td>
                        <td class="py-4 px-6 text-right font-bold text-slate-900">Rp {{ number_format($sub->amount, 0, ',', '.') }}</td>
                        <td class="py-4 px-6 text-center">
                            @php
                                $badgeClass = match($sub->status) {
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
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold border {{ $badgeClass }}">{{ $sub->status }}</span>
                        </td>
                        <td class="py-4 px-6 text-center">
                            <a href="{{ route('submissions.show', $sub) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-medium transition">
                                Detail / Review
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400">Belum ada pengajuan anggaran yang tercatat.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4 border-t border-slate-100">
        {{ $submissions->links() }}
    </div>
</div>
@endsection
