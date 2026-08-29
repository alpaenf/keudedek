@extends('layouts.app')

@section('title', 'Buat Pengajuan Baru')
@section('page-title', 'Form Pengajuan Anggaran Baru')

@section('content')
<div class="max-w-4xl mx-auto bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
    <div class="border-b border-slate-100 pb-4 mb-6 flex items-center justify-between">
        <div>
            <h3 class="text-lg font-bold text-slate-900">Form Pengajuan Kegiatan & Belanja</h3>
            <p class="text-xs text-slate-500">Lengkapi data pengajuan dan rincian belanja di bawah ini</p>
        </div>
        <a href="{{ route('submissions.index') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition">
            Batal
        </a>
    </div>

    <form action="{{ route('submissions.store') }}" method="POST" class="space-y-6">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Judul / Nama Kegiatan</label>
                <input type="text" name="title" required placeholder="Contoh: Pengadaan Bahan Uji Laboratorium..." class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs text-slate-800 focus:ring-2 focus:ring-sky-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Unit / Jurusan Penanggung Jawab</label>
                <select name="department_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs text-slate-800 focus:ring-2 focus:ring-sky-500">
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->code }} - {{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Pos Pagu Anggaran (Budget Bucket)</label>
                <select name="budget_bucket_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs text-slate-800 focus:ring-2 focus:ring-sky-500">
                    @foreach($buckets as $bucket)
                        <option value="{{ $bucket->id }}">
                            [{{ $bucket->account_code }}] {{ $bucket->account_name }} (Saldo: Rp {{ number_format($bucket->available_balance, 0, ',', '.') }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Total Nominal Pengajuan (Rp)</label>
                <input type="number" step="1000" name="amount" required placeholder="45000000" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs font-bold text-slate-900 focus:ring-2 focus:ring-sky-500">
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan / Keterangan Pendukung</label>
            <textarea name="notes" rows="2" placeholder="Catatan tambahan..." class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs text-slate-800 focus:ring-2 focus:ring-sky-500"></textarea>
        </div>

        <!-- Rincian Item Belanja -->
        <div class="border-t border-slate-100 pt-6">
            <h4 class="font-bold text-slate-900 text-sm mb-4">Rincian Item Belanja</h4>
            
            <div class="space-y-4">
                <div class="grid grid-cols-12 gap-3 items-center">
                    <div class="col-span-6">
                        <label class="block text-[11px] font-semibold text-slate-500 mb-1">Nama Barang / Belanja</label>
                        <input type="text" name="items[0][item_name]" required placeholder="Nama item barang/jasa" class="w-full px-3 py-1.5 border border-slate-300 rounded-lg text-xs">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-[11px] font-semibold text-slate-500 mb-1">Jumlah</label>
                        <input type="number" name="items[0][quantity]" value="1" min="1" required class="w-full px-3 py-1.5 border border-slate-300 rounded-lg text-xs text-center">
                    </div>
                    <div class="col-span-4">
                        <label class="block text-[11px] font-semibold text-slate-500 mb-1">Harga Satuan (Rp)</label>
                        <input type="number" step="1000" name="items[0][unit_price]" required placeholder="Harga satuan" class="w-full px-3 py-1.5 border border-slate-300 rounded-lg text-xs">
                    </div>
                </div>
            </div>
        </div>

        <div class="pt-4 flex justify-end">
            <button type="submit" class="px-6 py-2.5 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-semibold transition">
                Simpan Draft Pengajuan
            </button>
        </div>
    </form>
</div>
@endsection
