@extends('layouts.app')

@section('title', 'Audit Trail Log')
@section('page-title', 'Rekam Jejak Audit Log System')

@section('content')
<div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h3 class="font-bold text-slate-900 text-base">Security & Activity Audit Logs</h3>
        <p class="text-xs text-slate-500">Pencatatan transparan untuk seluruh event perubahan data, reservasi, dan revisi anggaran</p>
    </div>

    <form method="GET" action="{{ route('audit-logs.index') }}" class="flex items-center gap-3">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Nama Event / Model..." class="px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:ring-2 focus:ring-sky-500 w-64">
        <button type="submit" class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded-lg text-xs font-semibold transition">
            Filter Log
        </button>
    </form>
</div>

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
            <thead>
                <tr class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
                    <th class="py-3.5 px-6">Waktu Event</th>
                    <th class="py-3.5 px-6">User / Pelaku</th>
                    <th class="py-3.5 px-6">Nama Event Action</th>
                    <th class="py-3.5 px-6">Model Target</th>
                    <th class="py-3.5 px-6">IP Address</th>
                    <th class="py-3.5 px-6">Perubahan Payload</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse($logs as $log)
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="py-3.5 px-6 text-slate-500 font-mono">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                        <td class="py-3.5 px-6 font-semibold text-slate-900">{{ $log->user?->name ?? 'System' }}</td>
                        <td class="py-3.5 px-6 font-mono font-bold text-sky-700">{{ $log->action }}</td>
                        <td class="py-3.5 px-6 font-mono text-slate-600">{{ class_basename($log->model_type) }} #{{ $log->model_id }}</td>
                        <td class="py-3.5 px-6 font-mono text-slate-500">{{ $log->ip_address ?? '127.0.0.1' }}</td>
                        <td class="py-3.5 px-6">
                            @if($log->new_values)
                                <span class="px-2 py-1 bg-slate-100 border border-slate-200 rounded font-mono text-[10px] text-slate-700 block truncate max-w-xs">
                                    {{ json_encode($log->new_values) }}
                                </span>
                            @else
                                <span class="text-slate-400 italic">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400">Belum ada log aktivitas yang terekam.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4 border-t border-slate-100">
        {{ $logs->links() }}
    </div>
</div>
@endsection
