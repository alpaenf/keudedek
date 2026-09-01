<script setup>
import { Link } from '@inertiajs/vue3';
import LineTrendChart from '../../Components/Dashboard/LineTrendChart.vue';
import StackedBarChart from '../../Components/Dashboard/StackedBarChart.vue';
import StatusBarChart from '../../Components/Dashboard/StatusBarChart.vue';

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
  activeWarningsCount: Number,
  attentionBuckets: Array,
  monthlyTrend: Object,
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
        <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Monitoring &amp; Evaluasi Anggaran Ketua Jurusan</h2>
        <p class="text-xs text-slate-500">Evaluasi penyerapan anggaran unit, verifikasi usulan prioritas, dan awasi indikator EWS.</p>
      </div>

      <div class="flex items-center gap-3">
        <Link 
          href="/approvals" 
          class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition flex items-center shadow-sm"
        >
          Antrean Persetujuan Jurusan
        </Link>
      </div>
    </div>

    <!-- 5 Clean KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">PAGU JURUSAN</div>
        <div class="text-xl font-extrabold text-slate-900 font-sans tracking-tight truncate" :title="formatRupiah(totalAllocated)">
          {{ formatRupiahCompact(totalAllocated) }}
        </div>
        <div class="text-xs text-slate-500">Alokasi Resmi</div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-indigo-700 uppercase tracking-wider">KOMITMEN (RESERVED)</div>
        <div class="text-xl font-extrabold text-indigo-900 font-sans tracking-tight truncate" :title="formatRupiah(totalReserved)">
          {{ formatRupiahCompact(totalReserved) }}
        </div>
        <div class="text-xs text-indigo-700 font-semibold">Terkunci</div>
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
        <div class="text-xs text-sky-700 font-semibold">Sisa: {{ availableRate }}%</div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-slate-600 uppercase tracking-wider">SERAPAN REALISASI</div>
        <div class="text-xl font-extrabold text-emerald-700 font-sans">
          {{ serapanRate }}%
        </div>
        <div class="text-xs text-slate-500 font-medium">LRA / Pagu</div>
      </div>
    </div>

    <!-- 3 Dedicated Charts for KAJUR -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <LineTrendChart 
        title="Tren Realisasi Bulanan Jurusan"
        description="Pergerakan serapan realisasi belanja kumulatif (Jan–Des)"
        :labels="monthlyTrend?.labels || []"
        :realizedData="monthlyTrend?.realized || []"
        :reservedData="monthlyTrend?.reserved || []"
      />

      <StatusBarChart 
        title="Pengajuan Berdasarkan Status"
        description="Jumlah usulan kegiatan pada setiap tahapan alur verifikasi jurusan"
        :statusCounts="statusCounts"
      />
    </div>

    <StackedBarChart 
      title="Komposisi Anggaran Jurusan"
      description="Rincian perbandingan Realisasi Final, Reserved, dan Saldo Bebas"
      :isSingleDepartment="true"
      :singleAllocated="totalAllocated"
      :singleReserved="totalReserved"
      :singleRealized="totalRealized"
      :singleAvailable="totalAvailable"
    />

    <!-- Actionable Panels -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Pos Anggaran Perlu Perhatian (2 cols) -->
      <div class="lg:col-span-2 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="text-sm font-bold text-slate-900">Pos Anggaran Perlu Perhatian (EWS / Saldo Rendah)</h3>
          <Link href="/budgets" class="text-xs font-bold text-sky-600 hover:text-sky-700">Daftar Pagu &rarr;</Link>
        </div>

        <div v-if="attentionBuckets && attentionBuckets.length > 0" class="overflow-x-auto">
          <table class="w-full text-left text-xs font-sans">
            <thead>
              <tr class="border-b border-slate-200 text-slate-500 uppercase tracking-wider text-[10px]">
                <th class="py-2.5 px-3">Kode / Nama Pos</th>
                <th class="py-2.5 px-3 text-right">Alokasi</th>
                <th class="py-2.5 px-3 text-right">Realisasi</th>
                <th class="py-2.5 px-3 text-right">Sisa Saldo</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="b in attentionBuckets" :key="b.id" class="hover:bg-slate-50">
                <td class="py-2.5 px-3 font-bold text-slate-900">
                  <div class="font-bold text-slate-900">{{ b.account_code }}</div>
                  <div class="text-[11px] text-slate-500 font-medium truncate max-w-[220px]">{{ b.budget_bucket_name || b.account_name }}</div>
                </td>
                <td class="py-2.5 px-3 text-right font-bold text-slate-900 font-sans">{{ formatRupiah(b.allocated_budget) }}</td>
                <td class="py-2.5 px-3 text-right font-bold text-emerald-700 font-sans">{{ formatRupiah(b.realized_budget) }}</td>
                <td class="py-2.5 px-3 text-right font-bold text-sky-700 font-sans">{{ formatRupiah(b.available_balance) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="py-6 text-center text-xs text-slate-400">
          Seluruh pos anggaran dalam kondisi aman dan sehat.
        </div>
      </div>

      <!-- Warning Jurusan (1 col) -->
      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
        <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">Warning Jurusan</h3>
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
          Tidak ada peringatan aktif saat ini.
        </div>
      </div>
    </div>
  </div>
</template>
