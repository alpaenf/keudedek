@extends('layouts.app')

@section('title', 'Early Warning System (EWS)')
@section('page-title', 'Early Warning System (EWS) Alerts')

@section('content')
<div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h3 class="font-bold text-slate-900 text-base">Monitoring Deteksi Dini Saldo Anggaran</h3>
        <p class="text-xs text-slate-500">Daftar Peringatan Dini otomatis berbasis Rule-Engine (EWS-001, EWS-002, EWS-003)</p>
    </div>

    <form method="GET" action="{{ route('warnings.index') }}" class="flex items-center gap-3">
        <select name="severity" onchange="this.form.submit()" class="px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700">
            <option value="">Semua Severity</option>
            <option value="CRITICAL" {{ request('severity') == 'CRITICAL' ? 'selected' : '' }}>CRITICAL</option>
            <option value="HIGH" {{ request('severity') == 'HIGH' ? 'selected' : '' }}>HIGH</option>
            <option value="MEDIUM" {{ request('severity') == 'MEDIUM' ? 'selected' : '' }}>MEDIUM</option>
        </select>

        <select name="status" onchange="this.form.submit()" class="px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700">
            <option value="">Semua Status</option>
            <option value="ACTIVE" {{ request('status') == 'ACTIVE' ? 'selected' : '' }}>ACTIVE</option>
            <option value="ACKNOWLEDGED" {{ request('status') == 'ACKNOWLEDGED' ? 'selected' : '' }}>ACKNOWLEDGED</option>
            <option value="RESOLVED" {{ request('status') == 'RESOLVED' ? 'selected' : '' }}>RESOLVED</option>
        </select>
    </form>
</div>

<div class="space-y-4">
    @forelse($warnings as $warn)
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 {{ $warn->severity == 'CRITICAL' ? 'bg-rose-100 text-rose-600' : 'bg-amber-100 text-amber-600' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="font-mono font-extrabold text-xs text-sky-700">{{ $warn->rule_code }}</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold {{ $warn->severity == 'CRITICAL' ? 'bg-rose-600 text-white' : 'bg-amber-500 text-white' }}">{{ $warn->severity }}</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $warn->status == 'ACTIVE' ? 'bg-amber-50 text-amber-800 border-amber-300' : 'bg-emerald-50 text-emerald-800 border-emerald-300' }}">{{ $warn->status }}</span>
                    </div>
                    <h4 class="font-bold text-slate-900 text-sm">{{ $warn->department?->name }} ({{ $warn->department?->code }})</h4>
                    <p class="text-xs text-slate-600 mt-1">{{ $warn->message }}</p>
                </div>
            </div>

            <div class="flex items-center gap-3 w-full md:w-auto justify-between md:justify-end border-t md:border-0 pt-3 md:pt-0 border-slate-100">
                @if($warn->status == 'ACTIVE')
                    <form action="{{ route('warnings.acknowledge', $warn) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-500 text-white rounded-xl text-xs font-semibold transition">
                            Konfirmasi (Acknowledge)
                        </button>
                    </form>
                @else
                    <span class="text-xs text-slate-400 italic">Dikonfirmasi oleh {{ $warn->acknowledger?->name ?? 'User' }}</span>
                @endif
            </div>
        </div>
    @empty
        <div class="bg-white p-12 rounded-2xl border border-slate-200 text-center text-slate-400">
            Tidak ada peringatan dini yang terdaftar saat ini.
        </div>
    @endforelse

    <div class="mt-4">
        {{ $warnings->links() }}
    </div>
</div>
@endsection
