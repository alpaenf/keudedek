<script setup>
import { Link } from '@inertiajs/vue3';
import StackedBarChart from '../../Components/Dashboard/StackedBarChart.vue';

const props = defineProps({
  totalAllocated: Number,
  totalReserved: Number,
  totalRealized: Number,
  totalAvailable: Number,
  serapanRate: Number,
  utilizationRate: Number,
  availableRate: Number,
  statusCounts: Object,
  recentSubmissions: Array,
  activeWarnings: Array,
  attentionBuckets: Array,
});

const formatRupiah = (value) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(value || 0);
};

const formatRupiahCompact = (val) => {
  const num = Number(val) || 0;
  if (Math.abs(num) >= 1_000_000_000_000) return 'Rp ' + (num / 1_000_000_000_000).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) + ' T';
  if (Math.abs(num) >= 1_000_000_000) return 'Rp ' + (num / 1_000_000_000).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) + ' M';
  if (Math.abs(num) >= 1_000_000) return 'Rp ' + (num / 1_000_000).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 1 }) + ' Jt';
  return formatRupiah(num);
};

const getStatusBadge = (status) => {
  const map = {
    DRAFT: 'bg-slate-100 text-slate-700 border-slate-200',
    SUBMITTED: 'bg-blue-50 text-blue-700 border-blue-200',
    UNDER_REVIEW: 'bg-amber-50 text-amber-700 border-amber-200',
    RETURNED: 'bg-orange-50 text-orange-700 border-orange-200',
    APPROVED: 'bg-indigo-50 text-indigo-700 border-indigo-200',
    RESERVED: 'bg-indigo-100 text-indigo-800 border-indigo-300',
    PROCESSING: 'bg-cyan-50 text-cyan-700 border-cyan-200',
    FINAL: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    REJECTED: 'bg-rose-50 text-rose-700 border-rose-200',
  };
  return map[status] || 'bg-slate-100 text-slate-700 border-slate-200';
};
</script>

<template>
  <div class="space-y-6">
    <!-- Clean Section Header -->
    <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-slate-200/80">
      <div>
        <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Workbench Operasional Operator PTK</h2>
        <p class="text-xs text-slate-500">Kelola usulan kegiatan jurusan, perbaiki pengajuan returned, dan pantau saldo.</p>
      </div>

      <div class="flex flex-wrap items-center gap-3">
        <Link 
          href="/submissions/create" 
          class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-bold transition flex items-center shadow-sm"
        >
          Buat Pengajuan Baru
        </Link>
        <Link 
          href="/submissions-import" 
          class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-200 rounded-xl text-xs font-bold transition flex items-center shadow-sm"
        >
          Import Pengajuan (CSV)
        </Link>
      </div>
    </div>

    <!-- 5 Clean KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">PAGU ALOKASI JURUSAN</div>
        <div class="text-xl font-extrabold text-slate-900 font-sans tracking-tight truncate" :title="formatRupiah(totalAllocated)">
          {{ formatRupiahCompact(totalAllocated) }}
        </div>
        <div class="text-xs text-slate-500">Pagu Aktif Unit</div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-indigo-700 uppercase tracking-wider">KOMITMEN (RESERVED)</div>
        <div class="text-xl font-extrabold text-indigo-900 font-sans tracking-tight truncate" :title="formatRupiah(totalReserved)">
          {{ formatRupiahCompact(totalReserved) }}
        </div>
        <div class="text-xs text-indigo-700 font-semibold">Komitmen Terkunci</div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider">REALISASI FINAL (LRA)</div>
        <div class="text-xl font-extrabold text-emerald-900 font-sans tracking-tight truncate" :title="formatRupiah(totalRealized)">
          {{ formatRupiahCompact(totalRealized) }}
        </div>
        <div class="text-xs text-emerald-700 font-bold">Serapan: {{ serapanRate }}%</div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-sky-800 uppercase tracking-wider">SALDO BEBAS (AVAILABLE)</div>
        <div class="text-xl font-extrabold text-sky-950 font-sans tracking-tight truncate" :title="formatRupiah(totalAvailable)">
          {{ formatRupiahCompact(totalAvailable) }}
        </div>
        <div class="text-xs text-sky-700 font-semibold">Sisa Saldo: {{ availableRate }}%</div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-orange-800 uppercase tracking-wider">PERLU PERBAIKAN</div>
        <div class="text-xl font-extrabold text-orange-950 font-sans tracking-tight">
          {{ statusCounts?.RETURNED || 0 }}
        </div>
        <div class="text-xs text-orange-700 font-semibold">Status Returned</div>
      </div>
    </div>

    <!-- 1 Single Chart: Komposisi Anggaran Jurusan -->
    <StackedBarChart 
      title="Komposisi Anggaran Jurusan"
      description="Proporsi Realisasi Final, Komitmen Terkunci (Reserved), dan Saldo Bebas (Available)"
      :isSingleDepartment="true"
      :singleAllocated="totalAllocated"
      :singleReserved="totalReserved"
      :singleRealized="totalRealized"
      :singleAvailable="totalAvailable"
    />

    <!-- Operational Panels -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Recent Submissions (2 cols) -->
      <div class="lg:col-span-2 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="text-sm font-bold text-slate-900">Daftar Pengajuan Terbaru &amp; Perlu Tindakan</h3>
          <Link href="/submissions" class="text-xs font-bold text-sky-600 hover:text-sky-700">Lihat Semua &rarr;</Link>
        </div>

        <div v-if="recentSubmissions && recentSubmissions.length > 0" class="overflow-x-auto">
          <table class="w-full text-left text-xs font-sans">
            <thead>
              <tr class="border-b border-slate-200 text-slate-500 uppercase tracking-wider text-[10px]">
                <th class="py-2.5 px-3">No. Pengajuan</th>
                <th class="py-2.5 px-3">Nama Kegiatan</th>
                <th class="py-2.5 px-3 text-right">Nominal</th>
                <th class="py-2.5 px-3 text-center">Status</th>
                <th class="py-2.5 px-3 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="sub in recentSubmissions" :key="sub.id" class="hover:bg-slate-50">
                <td class="py-2.5 px-3 font-bold text-slate-900">{{ sub.submission_code || ('SUB-' + sub.id) }}</td>
                <td class="py-2.5 px-3 font-medium text-slate-800 truncate max-w-[200px]" :title="sub.title">{{ sub.title }}</td>
                <td class="py-2.5 px-3 text-right font-bold text-slate-900 font-sans">{{ formatRupiah(sub.amount) }}</td>
                <td class="py-2.5 px-3 text-center">
                  <span :class="['px-2 py-0.5 rounded-md text-[10px] font-bold border uppercase', getStatusBadge(sub.status)]">
                    {{ sub.status }}
                  </span>
                </td>
                <td class="py-2.5 px-3 text-center">
                  <Link :href="`/submissions/${sub.id}`" class="text-sky-600 font-bold hover:underline">Detail</Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="py-8 text-center text-xs text-slate-400">
          Belum ada data pengajuan pada jurusan ini.
        </div>
      </div>

      <!-- Warnings (1 col) -->
      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
        <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">Warning &amp; Perhatian Jurusan</h3>
        <div v-if="activeWarnings && activeWarnings.length > 0" class="space-y-2.5">
          <div v-for="w in activeWarnings" :key="w.id" class="p-3 rounded-xl border border-amber-200 bg-amber-50/40 text-xs space-y-1">
            <div class="flex items-center justify-between font-bold text-amber-900">
              <span>{{ w.title }}</span>
              <span class="text-[10px] uppercase px-1.5 py-0.5 rounded bg-amber-200/80 text-amber-900 font-extrabold">{{ w.severity }}</span>
            </div>
            <p class="text-amber-800 text-[11px] leading-relaxed">{{ w.description }}</p>
          </div>
        </div>
        <div v-else class="py-6 text-center text-xs text-slate-400">
          Tidak ada peringatan aktif pada jurusan ini.
        </div>
      </div>
    </div>
  </div>
</template>
