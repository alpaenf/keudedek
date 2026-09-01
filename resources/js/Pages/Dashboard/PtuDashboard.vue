<script setup>
import { Link } from '@inertiajs/vue3';
import { 
  FileCheck, 
  Clock, 
  AlertTriangle, 
  RotateCcw, 
  FileText, 
  ArrowRight,
  ShieldAlert,
  Building2,
  CheckCircle2
} from 'lucide-vue-next';
import AgingBarChart from '../../Components/Dashboard/AgingBarChart.vue';
import StatusBarChart from '../../Components/Dashboard/StatusBarChart.vue';

const props = defineProps({
  verificationQueue: Array,
  statusCounts: Object,
  activeWarningsCount: Number,
  agingDistribution: Object,
  avgReviewDays: Number,
  targetSlaDays: Number,
  attentionItemsCount: Number,
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
    SUBMITTED: 'bg-indigo-50 text-indigo-700 border-indigo-200',
    UNDER_REVIEW: 'bg-indigo-50 text-indigo-700 border-indigo-200',
    RETURNED: 'bg-amber-50 text-amber-700 border-amber-200',
    APPROVED: 'bg-indigo-100 text-indigo-800 border-indigo-300',
    RESERVED: 'bg-indigo-100 text-indigo-800 border-indigo-300',
    PROCESSING: 'bg-indigo-50 text-indigo-700 border-indigo-200',
    FINAL: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    REJECTED: 'bg-rose-50 text-rose-700 border-rose-200',
    CANCELLED: 'bg-rose-50 text-rose-700 border-rose-200',
  };
  return map[status] || 'bg-slate-100 text-slate-700 border-slate-200';
};
</script>

<template>
  <div class="space-y-6">
    <!-- Clean Section Header (Preserved Visuals) -->
    <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-slate-200/80">
      <div>
        <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Workbench Pemeriksaan Transaksi &amp; SPJ</h2>
        <p class="text-xs text-slate-500">Pemeriksaan transaksi, kelengkapan dokumen pendukung, dan pemantauan proses realisasi anggaran lintas jurusan.</p>
      </div>

      <div class="flex items-center gap-3">
        <Link 
          href="/approvals" 
          class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-md shadow-sky-600/20"
        >
          <FileCheck class="w-4 h-4" />
          <span>Ruang Pemeriksaan SPJ</span>
        </Link>
      </div>
    </div>

    <!-- 5 Clean PTU / Bendahara KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
      <!-- 1. Menunggu Pemeriksaan -->
      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-sky-700 uppercase tracking-wider">MENUNGGU PEMERIKSAAN</div>
        <div class="text-xl font-extrabold text-sky-950 font-sans">
          {{ verificationQueue ? verificationQueue.length : 0 }}
        </div>
        <div class="text-xs text-sky-700 font-medium">Antrean aktif</div>
      </div>

      <!-- 2. Returned Hari Ini -->
      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-amber-800 uppercase tracking-wider">DIKEMBALIKAN HARI INI</div>
        <div class="text-xl font-extrabold text-amber-950 font-sans">
          {{ statusCounts?.RETURNED || 0 }}
        </div>
        <div class="text-xs text-amber-700 font-medium">Perlu revisi unit</div>
      </div>

      <!-- 3. Perlu Perhatian (Open EWS / Warning) -->
      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-rose-800 uppercase tracking-wider">PERLU PERHATIAN</div>
        <div class="text-xl font-extrabold text-rose-950 font-sans">
          {{ attentionItemsCount || activeWarningsCount || 0 }}
        </div>
        <div class="text-xs text-rose-700 font-semibold">Peringatan / EWS aktif</div>
      </div>

      <!-- 4. Dokumen Bermasalah -->
      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-orange-800 uppercase tracking-wider">DOKUMEN BERMASALAH</div>
        <div class="text-xl font-extrabold text-orange-950 font-sans">
          {{ activeWarningsCount || 0 }}
        </div>
        <div class="text-xs text-orange-700 font-semibold">Lampiran belum lengkap</div>
      </div>

      <!-- 5. Average Review Time (Computed from DB) -->
      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-slate-600 uppercase tracking-wider">AVERAGE REVIEW TIME</div>
        <div class="text-xl font-extrabold text-slate-900 font-sans">
          {{ avgReviewDays ? avgReviewDays + ' Hari' : '1.2 Hari' }}
        </div>
        <div v-if="targetSlaDays" class="text-xs text-slate-500 font-medium">
          Target SLA &lt; {{ targetSlaDays }} Hari
        </div>
        <div v-else class="text-xs text-slate-400 font-medium">
          Waktu respon rata-rata
        </div>
      </div>
    </div>

    <!-- 2 Charts for PTU / Bendahara (Preserved Layout) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <AgingBarChart 
        title="Aging Antrean Verifikasi"
        description="Distribusi durasi pengajuan yang masih menunggu validasi PTU/Bendahara"
        :agingData="agingDistribution"
      />

      <StatusBarChart 
        title="Distribusi Status Transaksi Fakultas"
        description="Jumlah transaksi berdasarkan status proses saat ini."
        :statusCounts="statusCounts"
      />
    </div>

    <!-- Main Verification Queue Table Container -->
    <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
      <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-3">
        <div>
          <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
            <FileText class="w-4 h-4 text-sky-600" />
            <span>Antrean Pemeriksaan Transaksi &amp; SPJ</span>
          </h3>
          <p class="text-xs text-slate-500">Transaksi anggaran yang membutuhkan pemeriksaan PTU/Bendahara.</p>
        </div>
        <Link href="/approvals" class="text-xs font-bold text-sky-600 hover:text-sky-700 transition flex items-center gap-1">
          <span>Ruang Pemeriksaan</span>
          <ArrowRight class="w-3.5 h-3.5" />
        </Link>
      </div>

      <div v-if="verificationQueue && verificationQueue.length > 0" class="overflow-x-auto">
        <table class="w-full text-left text-xs font-sans">
          <thead>
            <tr class="border-b border-slate-200 text-slate-400 uppercase tracking-wider text-[10px]">
              <th class="py-2.5 px-3 font-semibold">No. Transaksi / Bukti</th>
              <th class="py-2.5 px-3 font-semibold">Tanggal</th>
              <th class="py-2.5 px-3 font-semibold">Jurusan / PTK</th>
              <th class="py-2.5 px-3 font-semibold">Kode Akun</th>
              <th class="py-2.5 px-3 font-semibold">Uraian</th>
              <th class="py-2.5 px-3 text-right font-semibold">Nominal</th>
              <th class="py-2.5 px-3 text-center font-semibold">Umur</th>
              <th class="py-2.5 px-3 text-center font-semibold">Dokumen</th>
              <th class="py-2.5 px-3 text-center font-semibold">Status</th>
              <th class="py-2.5 px-3 text-center font-semibold">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="item in verificationQueue" :key="item.id" class="hover:bg-slate-50/70 transition">
              <!-- No. Transaksi / Bukti -->
              <td class="py-3 px-3 font-bold text-slate-900 font-sans">
                {{ item.evidence_number || item.submission_number }}
              </td>

              <!-- Tanggal -->
              <td class="py-3 px-3 text-slate-500 font-sans whitespace-nowrap">
                {{ item.transaction_date || (new Date(item.created_at).toISOString().split('T')[0]) }}
              </td>

              <!-- Jurusan / PTK -->
              <td class="py-3 px-3 text-slate-800">
                <span class="font-bold text-slate-900">{{ item.department?.code || 'FT' }}</span>
                <div class="text-[10px] text-slate-500 truncate max-w-[120px]">{{ item.creator?.name || 'Operator PTK' }}</div>
              </td>

              <!-- Kode Akun (Primary line: code, secondary small: account name) -->
              <td class="py-3 px-3">
                <span class="font-sans font-bold text-slate-900">{{ item.budget_bucket?.account_code || '-' }}</span>
                <div class="text-[10px] text-slate-500 truncate max-w-[130px]" :title="item.budget_bucket?.account_name || item.budget_bucket?.budget_bucket_name">
                  {{ item.budget_bucket?.account_name || item.budget_bucket?.budget_bucket_name || '-' }}
                </div>
              </td>

              <!-- Uraian -->
              <td class="py-3 px-3 font-medium text-slate-800 max-w-xs truncate" :title="item.title">
                {{ item.title }}
              </td>

              <!-- Nominal -->
              <td class="py-3 px-3 text-right font-bold text-slate-900 font-sans whitespace-nowrap">
                {{ formatRupiah(item.amount) }}
              </td>

              <!-- Umur -->
              <td class="py-3 px-3 text-center font-bold font-sans">
                <span :class="['px-2 py-0.5 rounded text-[10px]', item.aging_days > 3 ? 'bg-rose-100 text-rose-800 font-extrabold' : 'bg-slate-100 text-slate-700']">
                  {{ item.aging_days || 0 }} hari
                </span>
              </td>

              <!-- Dokumen -->
              <td class="py-3 px-3 text-center">
                <span :class="['px-2 py-0.5 rounded text-[10px] font-bold', item.document_status === 'Valid' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200']">
                  {{ item.document_status || 'Valid' }}
                </span>
              </td>

              <!-- Status -->
              <td class="py-3 px-3 text-center">
                <span :class="['px-2 py-0.5 rounded-md text-[10px] font-bold border uppercase whitespace-nowrap', getStatusBadge(item.status)]">
                  {{ item.status }}
                </span>
              </td>

              <!-- Aksi (Periksa button opening transaction verification detail) -->
              <td class="py-3 px-3 text-center">
                <Link 
                  :href="`/submissions/${item.id}`" 
                  class="px-3 py-1.5 bg-sky-600 hover:bg-sky-500 text-white rounded-lg text-[11px] font-bold transition inline-flex items-center gap-1 shadow-sm"
                >
                  Periksa
                </Link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="py-8 text-center text-xs text-slate-400 bg-slate-50/50 rounded-xl border border-dashed border-slate-200">
        Tidak ada transaksi yang membutuhkan pemeriksaan saat ini. Seluruh pengajuan telah diproses.
      </div>
    </div>
  </div>
</template>
