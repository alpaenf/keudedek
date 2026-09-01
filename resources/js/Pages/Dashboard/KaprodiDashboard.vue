<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { 
  Building2, 
  Layers, 
  CheckCircle2, 
  Clock, 
  AlertTriangle, 
  FileText, 
  ExternalLink,
  Info,
  Calendar,
  Wallet
} from 'lucide-vue-next';
import LineTrendChart from '../../Components/Dashboard/LineTrendChart.vue';

const props = defineProps({
  totalRealized: Number,
  totalReserved: Number,
  statusCounts: Object,
  recentSubmissions: Array,
  activeWarnings: Array,
  prodiName: String,
  thisMonthCount: Number,
  thisMonthAmount: Number,
  monthlyTrend: Object,
});

const totalProdiTransactionsCount = computed(() => {
  if (!props.statusCounts) return 0;
  return (props.statusCounts.FINAL || 0) + 
         (props.statusCounts.PROCESSING || 0) + 
         (props.statusCounts.SUBMITTED || 0) + 
         (props.statusCounts.RETURNED || 0) + 
         (props.statusCounts.DRAFT || 0);
});

const formatRupiah = (val) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(val || 0);
};

const formatRupiahCompact = (val) => {
  const num = Number(val) || 0;
  if (Math.abs(num) >= 1_000_000_000_000) return 'Rp ' + (num / 1_000_000_000_000).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) + ' T';
  if (Math.abs(num) >= 1_000_000_000) return 'Rp ' + (num / 1_000_000_000).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) + ' M';
  if (Math.abs(num) >= 1_000_000) return 'Rp ' + (num / 1_000_000).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 1 }) + ' Jt';
  return formatRupiah(num);
};

const getStatusBadge = (status) => {
  switch (status) {
    case 'FINAL':
      return { label: 'Final / Realisasi', class: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
    case 'PROCESSING':
    case 'SUBMITTED':
      return { label: 'Dalam Proses', class: 'bg-indigo-50 text-indigo-700 border-indigo-200' };
    case 'RETURNED':
      return { label: 'Perlu Perbaikan', class: 'bg-amber-50 text-amber-700 border-amber-200' };
    default:
      return { label: 'Draft', class: 'bg-slate-50 text-slate-600 border-slate-200' };
  }
};
</script>

<template>
  <div class="space-y-6">
    <!-- Header Section (Default Read-Only) -->
    <div class="bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2">
          <span class="px-2.5 py-0.5 bg-sky-100 text-sky-800 text-[10px] font-black rounded-lg uppercase tracking-wider">
            Monitoring Program Studi
          </span>
          <span class="text-xs text-slate-400 font-semibold">&bull; Mode Read-Only</span>
        </div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight mt-1">
          Dashboard Ketua {{ prodiName || 'Program Studi' }}
        </h1>
        <p class="text-xs text-slate-500 mt-0.5">
          Monitoring terstruktur atas realisasi belanja dan transaksi yang ditagkan terkait program studi Anda.
        </p>
      </div>

      <div class="flex items-center gap-2">
        <Link 
          href="/submissions" 
          class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-2xl transition flex items-center gap-1.5 shadow-sm"
        >
          <FileText class="w-4 h-4 text-slate-500" />
          <span>Lihat Semua Transaksi</span>
        </Link>
      </div>
    </div>

    <!-- Institutional Context Note -->
    <div class="p-4 bg-sky-50/70 border border-sky-200 rounded-2xl flex items-start gap-3 text-xs text-sky-950">
      <Info class="w-4 h-4 text-sky-600 shrink-0 mt-0.5" />
      <p class="leading-relaxed">
        <strong class="font-bold">Ketentuan Anggaran:</strong> Pagu anggaran resmi DIPA dikelola pada level Jurusan oleh Operator PTK &amp; Ketua Jurusan. Dashboard Ketua Program Studi ini menampilkan data realisasi, transaksi berjalan, dan riwayat belanja yang secara spesifik ditagkan ke program studi Anda.
      </p>
    </div>

    <!-- 4 Clean KPI Cards (No "Pagu Prodi") -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
      <!-- 1. Transaksi Terkait Prodi -->
      <div class="bg-white p-4 sm:p-5 rounded-3xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">
          Transaksi Terkait Prodi
        </div>
        <div class="text-xl sm:text-2xl font-black text-slate-900 font-sans tracking-tight">
          {{ totalProdiTransactionsCount }} <span class="text-xs font-normal text-slate-400">Kegiatan</span>
        </div>
        <div class="text-[11px] text-slate-400">Akumulasi pengajuan prodi</div>
      </div>

      <!-- 2. Realisasi Terkait Prodi -->
      <div class="bg-white p-4 sm:p-5 rounded-3xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider">
          Realisasi Terkait Prodi
        </div>
        <div class="text-xl sm:text-2xl font-black text-emerald-900 font-sans tracking-tight truncate" :title="formatRupiah(totalRealized)">
          {{ formatRupiahCompact(totalRealized) }}
        </div>
        <div class="text-[11px] text-emerald-700 font-semibold">Total belanja selesai &amp; final</div>
      </div>

      <!-- 3. Dalam Proses -->
      <div class="bg-white p-4 sm:p-5 rounded-3xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-indigo-700 uppercase tracking-wider">
          Dalam Proses
        </div>
        <div class="text-xl sm:text-2xl font-black text-indigo-900 font-sans tracking-tight truncate" :title="formatRupiah(totalReserved)">
          {{ formatRupiahCompact(totalReserved) }}
        </div>
        <div class="text-[11px] text-indigo-600 font-semibold">Menunggu verifikasi SPJ</div>
      </div>

      <!-- 4. Aktivitas Bulan Ini -->
      <div class="bg-white p-4 sm:p-5 rounded-3xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-sky-800 uppercase tracking-wider">
          Aktivitas Bulan Ini
        </div>
        <div class="text-xl sm:text-2xl font-black text-sky-950 font-sans tracking-tight truncate">
          {{ thisMonthCount || 0 }} <span class="text-xs font-normal text-slate-400">Trx ({{ formatRupiahCompact(thisMonthAmount || 0) }})</span>
        </div>
        <div class="text-[11px] text-sky-700">Belanja berjalan bulan ini</div>
      </div>
    </div>

    <!-- Chart: Tren Realisasi Terkait Prodi -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
      <LineTrendChart 
        title="Tren Realisasi Terkait Program Studi (TA 2026)"
        description="Perkembangan realisasi belanja kumulatif yang ditagkan ke program studi Anda dari bulan ke bulan"
        :labels="monthlyTrend?.labels || ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']"
        :realizedData="monthlyTrend?.realized || []"
        :reservedData="monthlyTrend?.reserved || []"
      />
    </div>

    <!-- Table: Transaksi Sesuai Scope Study Program -->
    <div class="bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
      <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <div>
          <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
            <FileText class="w-4 h-4 text-sky-600" />
            <span>Daftar Transaksi Terkait {{ prodiName || 'Program Studi' }}</span>
          </h3>
          <p class="text-xs text-slate-500">Hanya menampilkan transaksi yang secara spesifik ditagkan ke program studi Anda.</p>
        </div>
        <span class="text-[11px] text-slate-400 font-medium">Read-Only View</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="border-b border-slate-200 text-slate-400 text-[11px]">
              <th class="pb-2.5 font-semibold">Nomor Bukti</th>
              <th class="pb-2.5 font-semibold">Tanggal</th>
              <th class="pb-2.5 font-semibold">Akun</th>
              <th class="pb-2.5 font-semibold">Uraian Transaksi</th>
              <th class="pb-2.5 font-semibold text-right">Nominal</th>
              <th class="pb-2.5 font-semibold text-center">Status</th>
              <th class="pb-2.5 font-semibold text-center">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="tx in recentSubmissions" :key="tx.id" class="hover:bg-slate-50/70 transition">
              <td class="py-3 font-sans font-bold text-slate-900">{{ tx.evidence_number || tx.submission_number }}</td>
              <td class="py-3 text-slate-500 font-sans">{{ tx.transaction_date || (new Date(tx.created_at).toISOString().split('T')[0]) }}</td>
              <td class="py-3 font-sans text-slate-600">{{ tx.budget_bucket?.account_code || '-' }}</td>
              <td class="py-3 text-slate-800 font-medium max-w-xs truncate" :title="tx.title">{{ tx.title }}</td>
              <td class="py-3 text-right font-sans font-black text-slate-900">{{ formatRupiah(tx.amount) }}</td>
              <td class="py-3 text-center">
                <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-bold border inline-block uppercase', getStatusBadge(tx.status).class]">
                  {{ getStatusBadge(tx.status).label }}
                </span>
              </td>
              <td class="py-3 text-center">
                <Link 
                  :href="`/submissions/${tx.id}`" 
                  class="px-2.5 py-1 bg-slate-100 hover:bg-sky-50 text-slate-700 hover:text-sky-700 rounded-lg text-[11px] font-bold transition inline-flex items-center gap-1"
                >
                  Detail
                </Link>
              </td>
            </tr>
            <tr v-if="!recentSubmissions || recentSubmissions.length === 0">
              <td colspan="7" class="py-8 text-center text-slate-400">
                Belum ada transaksi belanja yang ditagkan ke {{ prodiName || 'program studi ini' }}. Transaksi umum jurusan tidak dicantumkan di sini.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
