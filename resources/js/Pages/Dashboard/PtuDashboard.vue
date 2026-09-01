<script setup>
import { Link } from '@inertiajs/vue3';
import AgingBarChart from '../../Components/Dashboard/AgingBarChart.vue';
import StatusBarChart from '../../Components/Dashboard/StatusBarChart.vue';

const props = defineProps({
  verificationQueue: Array,
  highRiskSubmissions: Array,
  statusCounts: Object,
  activeWarningsCount: Number,
  agingDistribution: Object,
});

const formatRupiah = (value) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(value || 0);
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
        <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Workbench Verifikasi &amp; Validasi SPJ PTU</h2>
        <p class="text-xs text-slate-500">Pemeriksaan kepatuhan SBM, keabsahan dokumen bukti pendukung, dan rekomendasi persetujuan.</p>
      </div>

      <div class="flex items-center gap-3">
        <Link 
          href="/approvals" 
          class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-bold transition flex items-center shadow-sm"
        >
          Mulai Review Antrean
        </Link>
      </div>
    </div>

    <!-- 5 Clean PTU KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-blue-700 uppercase tracking-wider">MENUNGGU VERIFIKASI</div>
        <div class="text-xl font-extrabold text-blue-950 font-sans">
          {{ verificationQueue ? verificationQueue.length : 0 }}
        </div>
        <div class="text-xs text-blue-700 font-medium">Antrean aktif</div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-orange-800 uppercase tracking-wider">RETURNED HARI INI</div>
        <div class="text-xl font-extrabold text-orange-950 font-sans">
          {{ statusCounts?.RETURNED || 0 }}
        </div>
        <div class="text-xs text-orange-700 font-medium">Dikembalikan ke unit</div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-rose-800 uppercase tracking-wider">HIGH RISK (&ge; 30JT)</div>
        <div class="text-xl font-extrabold text-rose-950 font-sans">
          {{ highRiskSubmissions ? highRiskSubmissions.length : 0 }}
        </div>
        <div class="text-xs text-rose-700 font-semibold">Nilai belanja tinggi</div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-amber-800 uppercase tracking-wider">DOKUMEN BERMASALAH</div>
        <div class="text-xl font-extrabold text-amber-950 font-sans">
          {{ activeWarningsCount || 0 }}
        </div>
        <div class="text-xs text-amber-700 font-semibold">EWS aktif</div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-slate-600 uppercase tracking-wider">AVG REVIEW TIME</div>
        <div class="text-xl font-extrabold text-slate-900 font-sans">
          1.8 Hari
        </div>
        <div class="text-xs text-slate-500 font-medium">Target SLA &lt; 3 Hari</div>
      </div>
    </div>

    <!-- 2 Charts for PTU -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <AgingBarChart 
        title="Aging Antrean Verifikasi"
        description="Distribusi durasi pengajuan yang masih menunggu validasi PTU"
        :agingData="agingDistribution"
      />

      <StatusBarChart 
        title="Distribusi Status Usulan Fakultas"
        description="Volume usulan kegiatan pada setiap tahap alur approval"
        :statusCounts="statusCounts"
      />
    </div>

    <!-- Main Verification Queue Table -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
      <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <div>
          <h3 class="text-sm font-bold text-slate-900">Antrean Verifikasi Dokumen &amp; SPJ (Verification Queue)</h3>
          <p class="text-xs text-slate-500">Daftar usulan kegiatan yang membutuhkan pemeriksaan PTU</p>
        </div>
        <Link href="/approvals" class="text-xs font-bold text-blue-600 hover:text-blue-700">Ruang Eksekusi Verifikasi &rarr;</Link>
      </div>

      <div v-if="verificationQueue && verificationQueue.length > 0" class="overflow-x-auto">
        <table class="w-full text-left text-xs font-sans">
          <thead>
            <tr class="border-b border-slate-200 text-slate-500 uppercase tracking-wider text-[10px]">
              <th class="py-2.5 px-3">No. Pengajuan</th>
              <th class="py-2.5 px-3">Jurusan / PTK</th>
              <th class="py-2.5 px-3 text-right">Nominal</th>
              <th class="py-2.5 px-3 text-center">Umur (Aging)</th>
              <th class="py-2.5 px-3 text-center">Dokumen</th>
              <th class="py-2.5 px-3 text-center">Status</th>
              <th class="py-2.5 px-3 text-center">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="item in verificationQueue" :key="item.id" class="hover:bg-slate-50">
              <td class="py-3 px-3 font-bold text-slate-900">
                {{ item.submission_code || ('SUB-' + item.id) }}
                <div class="text-[10px] text-slate-500 font-normal truncate max-w-[160px]">{{ item.title }}</div>
              </td>
              <td class="py-3 px-3 font-medium text-slate-800">
                <span class="font-bold text-slate-900">{{ item.department?.code || 'FT' }}</span>
                <div class="text-[10px] text-slate-500">{{ item.creator?.name || 'Operator PTK' }}</div>
              </td>
              <td class="py-3 px-3 text-right font-bold text-slate-900 font-sans">{{ formatRupiah(item.amount) }}</td>
              <td class="py-3 px-3 text-center font-bold font-sans">
                <span :class="['px-2 py-0.5 rounded text-[10px]', item.aging_days > 3 ? 'bg-rose-100 text-rose-800 font-extrabold' : 'bg-slate-100 text-slate-700']">
                  {{ item.aging_days }} hari
                </span>
              </td>
              <td class="py-3 px-3 text-center">
                <span :class="['px-2 py-0.5 rounded text-[10px] font-bold', item.document_status === 'Valid' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200']">
                  {{ item.document_status }}
                </span>
              </td>
              <td class="py-3 px-3 text-center">
                <span :class="['px-2 py-0.5 rounded-md text-[10px] font-bold border uppercase', getStatusBadge(item.status)]">
                  {{ item.status }}
                </span>
              </td>
              <td class="py-3 px-3 text-center">
                <Link :href="`/approvals`" class="px-2.5 py-1 bg-blue-600 hover:bg-blue-500 text-white rounded text-[10px] font-bold transition inline-block">
                  Verifikasi
                </Link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="py-8 text-center text-xs text-slate-400">
        Tidak ada pengajuan yang membutuhkan verifikasi saat ini.
      </div>
    </div>
  </div>
</template>
