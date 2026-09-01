<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { 
  PlusCircle, 
  Upload, 
  Calendar, 
  Wallet, 
  GitBranch, 
  Building2, 
  AlertCircle, 
  Clock, 
  CheckCircle2, 
  AlertTriangle, 
  RotateCcw,
  ArrowRight,
  FileText,
  HelpCircle
} from 'lucide-vue-next';
import StackedBarChart from '../../Components/Dashboard/StackedBarChart.vue';
import LineTrendChart from '../../Components/Dashboard/LineTrendChart.vue';

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
  departmentName: String,
  activeFiscalYear: [String, Number],
  revisionNumber: String,
  fundSourceCode: String,
  thisMonthCount: Number,
  thisMonthAmount: Number,
  returnedSubmissions: Array,
  processingSubmissions: Array,
  monthlyTrend: Object,
});

const actionTab = ref('returned'); // 'returned', 'processing', 'warnings'

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
    SUBMITTED: 'bg-indigo-50 text-indigo-700 border-indigo-200',
    UNDER_REVIEW: 'bg-indigo-50 text-indigo-700 border-indigo-200',
    RETURNED: 'bg-amber-50 text-amber-700 border-amber-200',
    APPROVED: 'bg-indigo-100 text-indigo-800 border-indigo-300',
    RESERVED: 'bg-indigo-100 text-indigo-800 border-indigo-300',
    PROCESSING: 'bg-indigo-50 text-indigo-700 border-indigo-200',
    FINAL: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    REJECTED: 'bg-rose-50 text-rose-700 border-rose-200',
  };
  return map[status] || 'bg-slate-100 text-slate-700 border-slate-200';
};
</script>

<template>
  <div class="space-y-6">
    
    <!-- 1. Dedicated Context Bar (Jurusan Sendiri - Locked Scope) -->
    <div class="bg-white border border-slate-200/80 rounded-2xl p-3.5 sm:p-4 shadow-sm flex flex-wrap items-center justify-between gap-3 text-xs">
      <div class="flex flex-wrap items-center gap-2 sm:gap-3">
        <!-- Fiscal Year Pill -->
        <div class="flex items-center gap-1.5 px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-700">
          <Calendar class="w-3.5 h-3.5 text-slate-400 shrink-0" />
          <span>TA {{ activeFiscalYear || '2026' }}</span>
        </div>

        <!-- Funding Source Pill -->
        <div class="flex items-center gap-1.5 px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-700">
          <Wallet class="w-3.5 h-3.5 text-slate-400 shrink-0" />
          <span>{{ fundSourceCode || 'RM' }}</span>
        </div>

        <!-- Revision Pill -->
        <div class="flex items-center gap-1.5 px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-700">
          <GitBranch class="w-3.5 h-3.5 text-slate-400 shrink-0" />
          <span>{{ revisionNumber || 'Rev 02' }}</span>
        </div>

        <!-- Department Scope Pill -->
        <div class="flex items-center gap-1.5 px-3.5 py-1.5 bg-sky-50 border border-sky-200 rounded-xl font-bold text-sky-900">
          <Building2 class="w-3.5 h-3.5 text-sky-600 shrink-0" />
          <span>{{ departmentName || 'Jurusan Informatika' }}</span>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <span class="px-2.5 py-1 bg-slate-100 border border-slate-200 text-slate-600 text-[10px] font-bold rounded-lg uppercase tracking-wider">
          Workbench Operasional
        </span>
      </div>
    </div>

    <!-- 2. Action Banner & Large CTAs -->
    <div class="bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
          Workbench Operator {{ departmentName || 'Jurusan' }}
        </h1>
        <p class="text-xs text-slate-500 mt-1 max-w-2xl">
          Pencatatan transaksi belanja harian, penanganan usulan perlu perbaikan, dan monitoring sisa saldo ketersediaan anggaran unit Anda.
        </p>
      </div>

      <div class="flex flex-wrap items-center gap-2.5 shrink-0">
        <!-- Big CTA Button -->
        <Link 
          href="/submissions/create" 
          class="px-5 py-3 bg-sky-600 hover:bg-sky-500 text-white rounded-2xl text-xs font-bold transition flex items-center gap-2 shadow-lg shadow-sky-600/20 hover:scale-[1.01] active:scale-[0.99]"
        >
          <PlusCircle class="w-4 h-4" />
          <span>+ Catat Transaksi</span>
        </Link>

        <!-- Secondary CTA Button -->
        <Link 
          href="/submissions-import" 
          class="px-4 py-3 bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-2xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm"
        >
          <Upload class="w-4 h-4 text-slate-500" />
          <span>Import Transaksi</span>
        </Link>
      </div>
    </div>

    <!-- 3. Primary KPI Cards (4 Cards - Clickable to open filtered lists) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
      <!-- Pagu Aktif -->
      <Link 
        href="/budgets" 
        class="bg-white p-4 sm:p-5 rounded-3xl border border-slate-200/80 hover:border-sky-300 hover:shadow-md transition group block space-y-1"
        title="Klik untuk membuka daftar pos anggaran"
      >
        <div class="flex items-center justify-between text-[11px] font-bold text-slate-500 uppercase tracking-wider">
          <span>Pagu Aktif</span>
          <ArrowRight class="w-3.5 h-3.5 text-slate-300 group-hover:text-sky-600 transition" />
        </div>
        <div class="text-xl sm:text-2xl font-black text-slate-900 font-sans tracking-tight truncate">
          {{ formatRupiahCompact(totalAllocated) }}
        </div>
        <div class="text-[11px] text-slate-400 font-medium">Pagu DIPA aktif unit</div>
      </Link>

      <!-- Dalam Proses -->
      <Link 
        href="/submissions?status=PROCESSING" 
        class="bg-white p-4 sm:p-5 rounded-3xl border border-slate-200/80 hover:border-indigo-300 hover:shadow-md transition group block space-y-1"
        title="Klik untuk melihat transaksi dalam proses"
      >
        <div class="flex items-center justify-between text-[11px] font-bold text-indigo-700 uppercase tracking-wider">
          <span>Dalam Proses</span>
          <ArrowRight class="w-3.5 h-3.5 text-indigo-300 group-hover:text-indigo-600 transition" />
        </div>
        <div class="text-xl sm:text-2xl font-black text-indigo-900 font-sans tracking-tight truncate">
          {{ formatRupiahCompact(totalReserved) }}
        </div>
        <div class="text-[11px] text-indigo-600 font-semibold">Komitmen terkunci</div>
      </Link>

      <!-- Realisasi -->
      <Link 
        href="/submissions?status=FINAL" 
        class="bg-white p-4 sm:p-5 rounded-3xl border border-slate-200/80 hover:border-emerald-300 hover:shadow-md transition group block space-y-1"
        title="Klik untuk melihat transaksi final/realisasi"
      >
        <div class="flex items-center justify-between text-[11px] font-bold text-emerald-700 uppercase tracking-wider">
          <span>Realisasi</span>
          <ArrowRight class="w-3.5 h-3.5 text-emerald-300 group-hover:text-emerald-600 transition" />
        </div>
        <div class="text-xl sm:text-2xl font-black text-emerald-900 font-sans tracking-tight truncate">
          {{ formatRupiahCompact(totalRealized) }}
        </div>
        <div class="text-[11px] text-emerald-700 font-bold">Serapan: {{ serapanRate }}%</div>
      </Link>

      <!-- Saldo Tersedia -->
      <Link 
        href="/budgets" 
        class="bg-white p-4 sm:p-5 rounded-3xl border border-slate-200/80 hover:border-sky-300 hover:shadow-md transition group block space-y-1"
        title="Klik untuk melihat detail sisa saldo per pos"
      >
        <div class="flex items-center justify-between text-[11px] font-bold text-sky-800 uppercase tracking-wider">
          <span>Saldo Tersedia</span>
          <ArrowRight class="w-3.5 h-3.5 text-sky-300 group-hover:text-sky-600 transition" />
        </div>
        <div class="text-xl sm:text-2xl font-black text-sky-950 font-sans tracking-tight truncate">
          {{ formatRupiahCompact(totalAvailable) }}
        </div>
        <div class="text-[11px] text-sky-700 font-semibold">Sisa bebas: {{ availableRate }}%</div>
      </Link>
    </div>

    <!-- 4. Secondary KPI Cards (2 Cards - Clickable) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
      <!-- Perlu Diperbaiki -->
      <Link 
        href="/submissions?status=RETURNED" 
        class="bg-amber-50/50 p-4 sm:p-5 rounded-3xl border border-amber-200/80 hover:border-amber-400 hover:shadow-md transition group flex items-center justify-between"
      >
        <div class="space-y-0.5">
          <div class="text-[11px] font-bold text-amber-800 uppercase tracking-wider flex items-center gap-1.5">
            <RotateCcw class="w-3.5 h-3.5 text-amber-600" />
            <span>Perlu Diperbaiki</span>
          </div>
          <div class="text-xl font-black text-amber-950 font-sans tracking-tight">
            {{ statusCounts?.RETURNED || 0 }} <span class="text-xs font-normal text-amber-700">Berkas Pengajuan</span>
          </div>
          <div class="text-[11px] text-amber-700">Dikembalikan reviewer untuk revisi SPJ</div>
        </div>
        <div class="p-3 bg-amber-100 text-amber-800 rounded-2xl group-hover:scale-105 transition">
          <ArrowRight class="w-4 h-4" />
        </div>
      </Link>

      <!-- Transaksi Bulan Ini -->
      <Link 
        href="/submissions" 
        class="bg-slate-50 p-4 sm:p-5 rounded-3xl border border-slate-200/80 hover:border-sky-300 hover:shadow-md transition group flex items-center justify-between"
      >
        <div class="space-y-0.5">
          <div class="text-[11px] font-bold text-slate-600 uppercase tracking-wider flex items-center gap-1.5">
            <Calendar class="w-3.5 h-3.5 text-slate-500" />
            <span>Transaksi Bulan Ini</span>
          </div>
          <div class="text-xl font-black text-slate-900 font-sans tracking-tight">
            {{ thisMonthCount || 0 }} <span class="text-xs font-normal text-slate-500">Transaksi ({{ formatRupiahCompact(thisMonthAmount || 0) }})</span>
          </div>
          <div class="text-[11px] text-slate-500">Aktivitas belanja berjalan bulan ini</div>
        </div>
        <div class="p-3 bg-slate-200/70 text-slate-700 rounded-2xl group-hover:scale-105 transition">
          <ArrowRight class="w-4 h-4" />
        </div>
      </Link>
    </div>

    <!-- 5. Charts (Maksimal 2: Komposisi & Tren Realisasi) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
      <!-- Chart 1: Komposisi Anggaran Jurusan -->
      <StackedBarChart 
        title="Komposisi Anggaran Unit"
        description="Proporsi Realisasi, Dalam Proses (Komitmen), dan Saldo Tersedia"
        :isSingleDepartment="true"
        :singleAllocated="totalAllocated"
        :singleReserved="totalReserved"
        :singleRealized="totalRealized"
        :singleAvailable="totalAvailable"
      />

      <!-- Chart 2: Tren Realisasi Bulanan -->
      <LineTrendChart 
        title="Tren Realisasi Bulanan (TA 2026)"
        description="Perkembangan realisasi belanja kumulatif unit jurusan"
        :labels="monthlyTrend?.labels || ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']"
        :realizedData="monthlyTrend?.realized || []"
        :reservedData="monthlyTrend?.reserved || []"
      />
    </div>

    <!-- 6. Section "Perlu Tindakan" -->
    <div class="bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
      <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-3">
        <div>
          <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
            <AlertCircle class="w-4 h-4 text-amber-600" />
            <span>Perlu Tindakan Segera</span>
          </h3>
          <p class="text-xs text-slate-500">Daftar item operasional yang membutuhkan penanganan atau tindak lanjut dari operator PTK.</p>
        </div>

        <!-- Filter Sub-tabs -->
        <div class="flex bg-slate-100 p-1 rounded-xl border border-slate-200 text-xs">
          <button 
            @click="actionTab = 'returned'" 
            :class="['px-3 py-1 rounded-lg font-bold transition flex items-center gap-1.5 text-[11px]', actionTab === 'returned' ? 'bg-amber-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900']"
          >
            <span>Returned ({{ statusCounts?.RETURNED || 0 }})</span>
          </button>
          <button 
            @click="actionTab = 'processing'" 
            :class="['px-3 py-1 rounded-lg font-bold transition flex items-center gap-1.5 text-[11px]', actionTab === 'processing' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900']"
          >
            <span>Belum Final ({{ (statusCounts?.PROCESSING || 0) + (statusCounts?.SUBMITTED || 0) }})</span>
          </button>
          <button 
            @click="actionTab = 'warnings'" 
            :class="['px-3 py-1 rounded-lg font-bold transition flex items-center gap-1.5 text-[11px]', actionTab === 'warnings' ? 'bg-rose-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900']"
          >
            <span>Peringatan Saldo ({{ activeWarnings?.length || 0 }})</span>
          </button>
        </div>
      </div>

      <!-- Tab Content: Returned Items -->
      <div v-if="actionTab === 'returned'" class="space-y-2.5">
        <div v-if="returnedSubmissions && returnedSubmissions.length > 0" class="divide-y divide-slate-100">
          <div v-for="item in returnedSubmissions" :key="item.id" class="py-3 flex flex-wrap items-center justify-between gap-3 hover:bg-slate-50/70 p-2.5 rounded-2xl transition">
            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-2">
                <span class="font-sans font-bold text-xs text-slate-900">{{ item.evidence_number || item.submission_number }}</span>
                <span class="px-2 py-0.5 bg-amber-100 text-amber-800 border border-amber-200 text-[9px] font-black rounded uppercase">Perlu Perbaikan</span>
              </div>
              <p class="text-xs text-slate-800 font-medium mt-0.5 truncate">{{ item.title }}</p>
              <p class="text-[11px] text-amber-800 italic mt-0.5">Catatan: {{ item.notes || 'Perbaiki lampiran kuitansi/faktur sebelum diajukan kembali.' }}</p>
            </div>
            <div class="text-right shrink-0">
              <div class="font-sans font-black text-xs text-slate-900">{{ formatRupiah(item.amount) }}</div>
              <Link :href="`/submissions/${item.id}`" class="inline-flex items-center gap-1 text-[11px] font-bold text-sky-600 hover:text-sky-700 mt-1">
                Perbaiki Sekarang &rarr;
              </Link>
            </div>
          </div>
        </div>
        <div v-else class="py-6 text-center text-xs text-slate-400 bg-slate-50/50 rounded-2xl border border-dashed border-slate-200">
          Tidak ada pengajuan berstatus returned. Seluruh transaksi berada dalam status aman.
        </div>
      </div>

      <!-- Tab Content: Processing / Belum Final Items -->
      <div v-else-if="actionTab === 'processing'" class="space-y-2.5">
        <div v-if="processingSubmissions && processingSubmissions.length > 0" class="divide-y divide-slate-100">
          <div v-for="item in processingSubmissions" :key="item.id" class="py-3 flex flex-wrap items-center justify-between gap-3 hover:bg-slate-50/70 p-2.5 rounded-2xl transition">
            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-2">
                <span class="font-sans font-bold text-xs text-slate-900">{{ item.evidence_number || item.submission_number }}</span>
                <span class="px-2 py-0.5 bg-indigo-100 text-indigo-800 border border-indigo-200 text-[9px] font-black rounded uppercase">Dalam Proses</span>
              </div>
              <p class="text-xs text-slate-800 font-medium mt-0.5 truncate">{{ item.title }}</p>
              <p class="text-[11px] text-slate-500 mt-0.5">Pos: {{ item.budget_bucket?.budget_bucket_name || '-' }} (Akun: {{ item.budget_bucket?.account_code || '-' }})</p>
            </div>
            <div class="text-right shrink-0">
              <div class="font-sans font-black text-xs text-slate-900">{{ formatRupiah(item.amount) }}</div>
              <Link :href="`/submissions/${item.id}`" class="inline-flex items-center gap-1 text-[11px] font-bold text-slate-600 hover:text-sky-600 mt-1">
                Lihat Detail &rarr;
              </Link>
            </div>
          </div>
        </div>
        <div v-else class="py-6 text-center text-xs text-slate-400 bg-slate-50/50 rounded-2xl border border-dashed border-slate-200">
          Tidak ada transaksi yang tertunda dalam proses.
        </div>
      </div>

      <!-- Tab Content: EWS / Saldo Warnings -->
      <div v-else-if="actionTab === 'warnings'" class="space-y-2.5">
        <div v-if="activeWarnings && activeWarnings.length > 0" class="space-y-2">
          <div v-for="w in activeWarnings" :key="w.id" class="p-3 rounded-2xl border border-amber-200 bg-amber-50/40 text-xs space-y-1">
            <div class="flex items-center justify-between font-bold text-amber-900">
              <span class="flex items-center gap-1.5">
                <AlertTriangle class="w-3.5 h-3.5 text-amber-600" />
                {{ w.title }}
              </span>
              <span class="text-[9px] uppercase px-2 py-0.5 rounded bg-amber-200 text-amber-950 font-black">{{ w.severity }}</span>
            </div>
            <p class="text-amber-800 text-[11px] leading-relaxed">{{ w.description }}</p>
          </div>
        </div>
        <div v-else class="py-6 text-center text-xs text-slate-400 bg-slate-50/50 rounded-2xl border border-dashed border-slate-200">
          Tidak ada peringatan dini (EWS) saldo kritis aktif pada unit Anda.
        </div>
      </div>
    </div>

    <!-- 7. Section "Transaksi Terbaru" -->
    <div class="bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
      <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <div>
          <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
            <FileText class="w-4 h-4 text-sky-600" />
            <span>Transaksi Terbaru Jurusan</span>
          </h3>
          <p class="text-xs text-slate-500">Histori pencatatan transaksi belanja terakhir di unit kerja Anda.</p>
        </div>
        <Link href="/submissions" class="text-xs font-bold text-sky-600 hover:text-sky-700 transition flex items-center gap-1">
          <span>Lihat Semua Transaksi</span>
          <ArrowRight class="w-3.5 h-3.5" />
        </Link>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="border-b border-slate-200 text-slate-400 text-[11px]">
              <th class="pb-2.5 font-semibold">Nomor Bukti</th>
              <th class="pb-2.5 font-semibold">Tanggal</th>
              <th class="pb-2.5 font-semibold">Akun</th>
              <th class="pb-2.5 font-semibold">Uraian</th>
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
                <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-bold border inline-block uppercase', getStatusBadge(tx.status)]">
                  {{ tx.status }}
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
                Belum ada data transaksi yang dicatat untuk jurusan ini.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
