<script setup>
import { ref, computed } from 'vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { 
  FileText, 
  Eye, 
  ArrowLeft, 
  Building2, 
  Layers, 
  Wallet, 
  AlertTriangle, 
  CheckCircle2, 
  Clock, 
  ChevronRight,
  History,
  Calculator,
  FileSpreadsheet,
  PlusCircle,
  ShieldCheck,
  Info
} from 'lucide-vue-next';

const props = defineProps({
  budgetBucket: Object,
  hierarchy: Object,
  financialSummary: Object,
  sourceLines: Array,
});

const page = usePage();
const currentUser = page.props.auth?.user;
const canRevise = ['ADMIN', 'KABAG'].includes(currentUser?.role);

// Tabs state
const activeTab = ref('source_lines'); // 'source_lines' | 'transactions' | 'revisions' | 'warnings' | 'calculation'

// Calculation Simulation state
const simNewExpense = ref('');
const simVolume = ref(1);

const simulatedTotalExpense = computed(() => {
  const amount = Number(String(simNewExpense.value).replace(/\D/g, '')) || 0;
  return amount * (simVolume.value || 1);
});

const simulatedAvailableBalance = computed(() => {
  const currentAvail = Number(props.financialSummary?.available_balance || 0);
  return currentAvail - simulatedTotalExpense.value;
});

const simulatedUtilization = computed(() => {
  const allocated = Number(props.financialSummary?.allocated_budget || 0);
  if (allocated <= 0) return 0;
  const totalUsed = Number(props.financialSummary?.realized_budget || 0) + 
                    Number(props.financialSummary?.reserved_budget || 0) + 
                    simulatedTotalExpense.value;
  return Math.min(100, Math.round((totalUsed / allocated) * 100 * 10) / 10);
});

const isSolvent = computed(() => simulatedAvailableBalance.value >= 0);

// Revision Modal state
const isRevisionModalOpen = ref(false);
const form = useForm({
  revised_amount: props.budgetBucket?.allocated_budget ?? 0,
  reason: '',
});

const displayRevisedAmount = ref('');

const formatDots = (val) => {
  if (!val && val !== 0) return '';
  const numStr = String(val).replace(/\D/g, '');
  return numStr.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
};

displayRevisedAmount.value = formatDots(props.budgetBucket?.allocated_budget ?? 0);

const onRevisedInput = (e) => {
  const raw = e.target.value.replace(/\D/g, '');
  form.revised_amount = raw ? parseInt(raw, 10) : 0;
  displayRevisedAmount.value = formatDots(raw);
};

const submitRevision = () => {
  if (props.budgetBucket?.id) {
    form.post(`/budgets/${props.budgetBucket.id}/revise`, {
      onSuccess: () => {
        isRevisionModalOpen.value = false;
      }
    });
  }
};

const formatRupiah = (val) => {
  return new Intl.NumberFormat('id-ID', { 
    style: 'currency', 
    currency: 'IDR', 
    maximumFractionDigits: 0 
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
    case 'COMPLETED':
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
  <AppLayout :title="`Detail Pagu: ${budgetBucket?.account_code} - ${budgetBucket?.account_name}`">
    <div class="space-y-6 font-sans">
      
      <!-- Top Context & Action Header -->
      <div class="bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2 mb-2">
            <Link 
              href="/budgets" 
              class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition inline-flex items-center gap-1"
            >
              <ArrowLeft class="w-3.5 h-3.5" />
              <span>Kembali ke Daftar</span>
            </Link>
            <span class="px-2.5 py-1 bg-sky-100 text-sky-800 text-[10px] font-black rounded-lg uppercase tracking-wider">
              Pos Anggaran Aktif
            </span>
          </div>

          <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
            {{ budgetBucket?.account_code }} &mdash; {{ budgetBucket?.account_name }}
          </h1>
          <p class="text-xs text-slate-500 mt-1">
            {{ budgetBucket?.budget_bucket_name || budgetBucket?.description }}
          </p>
        </div>

        <div class="flex items-center gap-2.5">
          <button 
            v-if="canRevise"
            @click="isRevisionModalOpen = true" 
            class="px-4 py-2.5 bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold rounded-2xl transition flex items-center gap-2 shadow-md shadow-sky-600/20"
          >
            <History class="w-4 h-4" />
            <span>Revisi Pagu Anggaran</span>
          </button>
        </div>
      </div>

      <!-- Header Hierarchy Breadcrumb (Full 11-Segment Path) -->
      <div class="bg-slate-900 text-white p-4 sm:p-5 rounded-3xl shadow-sm overflow-x-auto">
        <div class="text-[10px] font-bold tracking-wider text-sky-300 uppercase mb-2">
          Hierarki Alokasi RKAKL DIPA FT UNSOED
        </div>
        <div class="flex items-center gap-2 text-xs font-medium whitespace-nowrap">
          <span class="bg-slate-800 px-2.5 py-1 rounded-lg text-sky-200 font-bold">TA {{ budgetBucket?.fiscal_year?.year || 2026 }}</span>
          <ChevronRight class="w-3.5 h-3.5 text-slate-500 shrink-0" />
          <span class="bg-slate-800 px-2.5 py-1 rounded-lg text-sky-200 font-bold">{{ budgetBucket?.funding_source?.code || 'RM' }}</span>
          <ChevronRight class="w-3.5 h-3.5 text-slate-500 shrink-0" />
          <span class="bg-slate-800 px-2.5 py-1 rounded-lg text-sky-200 font-bold">{{ budgetBucket?.budget_version?.revision_no || 'Rev 02' }}</span>
          <ChevronRight class="w-3.5 h-3.5 text-slate-500 shrink-0" />
          <span class="bg-sky-500/20 text-sky-300 px-2.5 py-1 rounded-lg font-bold border border-sky-500/30">{{ budgetBucket?.department?.code || 'FT' }}</span>
          <ChevronRight class="w-3.5 h-3.5 text-slate-500 shrink-0" />
          <span class="text-slate-300 font-sans" :title="hierarchy?.program_name">Prog: <strong class="text-white">{{ hierarchy?.program_code }}</strong></span>
          <ChevronRight class="w-3.5 h-3.5 text-slate-500 shrink-0" />
          <span class="text-slate-300 font-sans" :title="hierarchy?.activity_name">Keg: <strong class="text-white">{{ hierarchy?.activity_code }}</strong></span>
          <ChevronRight class="w-3.5 h-3.5 text-slate-500 shrink-0" />
          <span class="text-slate-300 font-sans" :title="hierarchy?.kro_name">KRO: <strong class="text-white">{{ hierarchy?.kro_code }}</strong></span>
          <ChevronRight class="w-3.5 h-3.5 text-slate-500 shrink-0" />
          <span class="text-slate-300 font-sans">RO: <strong class="text-white">{{ hierarchy?.ro_code }}</strong></span>
          <ChevronRight class="w-3.5 h-3.5 text-slate-500 shrink-0" />
          <span class="text-slate-300 font-sans">Komp: <strong class="text-white">{{ hierarchy?.component_code }}</strong></span>
          <ChevronRight class="w-3.5 h-3.5 text-slate-500 shrink-0" />
          <span class="text-slate-300 font-sans">Subkomp: <strong class="text-white">{{ hierarchy?.subcomponent_code }}</strong></span>
          <ChevronRight class="w-3.5 h-3.5 text-slate-500 shrink-0" />
          <span class="bg-sky-600 text-white px-2.5 py-1 rounded-lg font-bold">Akun {{ hierarchy?.account_code }}</span>
        </div>
      </div>

      <!-- Grid 2 Columns: IDENTITAS ANGGARAN & STRUKTUR ANGGARAN -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- SECTION 1: IDENTITAS ANGGARAN -->
        <div class="bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
          <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
              <Building2 class="w-4 h-4 text-sky-600" />
              <span>Identitas Anggaran</span>
            </h2>
            <span class="text-[11px] font-bold text-slate-400">Unit Metadata</span>
          </div>

          <div class="grid grid-cols-2 gap-4 text-xs">
            <div>
              <span class="text-slate-400 text-[11px] block">Tahun Anggaran</span>
              <span class="font-bold text-slate-900 font-sans">{{ budgetBucket?.fiscal_year?.year || 2026 }}</span>
            </div>
            <div>
              <span class="text-slate-400 text-[11px] block">Sumber Dana</span>
              <span class="font-bold text-slate-900">{{ budgetBucket?.funding_source?.code }} &mdash; {{ budgetBucket?.funding_source?.name }}</span>
            </div>
            <div>
              <span class="text-slate-400 text-[11px] block">Versi Anggaran</span>
              <span class="font-bold text-slate-900">{{ budgetBucket?.budget_version?.revision_no || 'Rev 02' }} ({{ budgetBucket?.budget_version?.status || 'ACTIVE' }})</span>
            </div>
            <div>
              <span class="text-slate-400 text-[11px] block">Unit Fakultas</span>
              <span class="font-bold text-slate-900">Fakultas Teknik (FT)</span>
            </div>
            <div>
              <span class="text-slate-400 text-[11px] block">Subunit Pengelola</span>
              <span class="font-bold text-slate-900">Bagian Tata Usaha &amp; Keuangan FT</span>
            </div>
            <div>
              <span class="text-slate-400 text-[11px] block">Jurusan</span>
              <span class="font-bold text-slate-900">{{ budgetBucket?.department?.code }} &mdash; {{ budgetBucket?.department?.name }}</span>
            </div>
            <div class="col-span-2">
              <span class="text-slate-400 text-[11px] block">Program Studi Terkait (Jika Ada)</span>
              <span class="font-medium text-slate-600">Terbuka untuk seluruh prodi dalam lingkup {{ budgetBucket?.department?.name }}</span>
            </div>
          </div>
        </div>

        <!-- SECTION 2: STRUKTUR ANGGARAN (7 Segments CODE + NAME) -->
        <div class="bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
          <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
              <Layers class="w-4 h-4 text-sky-600" />
              <span>Struktur Anggaran (RKAKL)</span>
            </h2>
            <span class="text-[11px] font-bold text-slate-400">7 Segmen Resmi</span>
          </div>

          <div class="space-y-2.5 text-xs">
            <div class="flex items-start justify-between gap-2 p-2 rounded-xl bg-slate-50">
              <span class="text-slate-500 font-semibold shrink-0 w-24">Program:</span>
              <span class="font-bold text-slate-900 text-right">{{ hierarchy?.program_code }} &mdash; {{ hierarchy?.program_name }}</span>
            </div>
            <div class="flex items-start justify-between gap-2 p-2 rounded-xl bg-slate-50">
              <span class="text-slate-500 font-semibold shrink-0 w-24">Kegiatan:</span>
              <span class="font-bold text-slate-900 text-right">{{ hierarchy?.activity_code }} &mdash; {{ hierarchy?.activity_name }}</span>
            </div>
            <div class="flex items-start justify-between gap-2 p-2 rounded-xl bg-slate-50">
              <span class="text-slate-500 font-semibold shrink-0 w-24">KRO:</span>
              <span class="font-bold text-slate-900 text-right">{{ hierarchy?.kro_code }} &mdash; {{ hierarchy?.kro_name }}</span>
            </div>
            <div class="flex items-start justify-between gap-2 p-2 rounded-xl bg-slate-50">
              <span class="text-slate-500 font-semibold shrink-0 w-24">RO:</span>
              <span class="font-bold text-slate-900 text-right">{{ hierarchy?.ro_code }} &mdash; {{ hierarchy?.ro_name }}</span>
            </div>
            <div class="flex items-start justify-between gap-2 p-2 rounded-xl bg-slate-50">
              <span class="text-slate-500 font-semibold shrink-0 w-24">Komponen:</span>
              <span class="font-bold text-slate-900 text-right">{{ hierarchy?.component_code }} &mdash; {{ hierarchy?.component_name }}</span>
            </div>
            <div class="flex items-start justify-between gap-2 p-2 rounded-xl bg-slate-50">
              <span class="text-slate-500 font-semibold shrink-0 w-24">Subkomponen:</span>
              <span class="font-bold text-slate-900 text-right">{{ hierarchy?.subcomponent_code }} &mdash; {{ hierarchy?.subcomponent_name }}</span>
            </div>
            <div class="flex items-start justify-between gap-2 p-2 rounded-xl bg-sky-50 border border-sky-100">
              <span class="text-sky-800 font-bold shrink-0 w-24">Akun:</span>
              <span class="font-black text-sky-950 text-right">{{ hierarchy?.account_code }} &mdash; {{ hierarchy?.account_name }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- SECTION 3: FINANCIAL SUMMARY (Cards) -->
      <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3 sm:gap-4">
        <!-- 1. Pagu Awal -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
          <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Pagu Awal</div>
          <div class="text-base font-black text-slate-900 font-sans truncate" :title="formatRupiah(financialSummary?.initial_budget)">
            {{ formatRupiahCompact(financialSummary?.initial_budget) }}
          </div>
          <div class="text-[10px] text-slate-400">DIPA penetapan</div>
        </div>

        <!-- 2. Perubahan Revisi -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
          <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Perubahan</div>
          <div :class="['text-base font-black font-sans truncate', financialSummary?.revision_delta >= 0 ? 'text-sky-700' : 'text-rose-700']">
            {{ financialSummary?.revision_delta >= 0 ? '+' : '' }}{{ formatRupiahCompact(financialSummary?.revision_delta) }}
          </div>
          <div class="text-[10px] text-slate-400">Akumulasi revisi</div>
        </div>

        <!-- 3. Pagu Aktif -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
          <div class="text-[10px] font-bold text-sky-700 uppercase tracking-wider">Pagu Aktif</div>
          <div class="text-base font-black text-sky-950 font-sans truncate" :title="formatRupiah(financialSummary?.allocated_budget)">
            {{ formatRupiahCompact(financialSummary?.allocated_budget) }}
          </div>
          <div class="text-[10px] text-sky-700 font-semibold">Alokasi resmi</div>
        </div>

        <!-- 4. Dalam Proses -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
          <div class="text-[10px] font-bold text-indigo-700 uppercase tracking-wider">Dalam Proses</div>
          <div class="text-base font-black text-indigo-900 font-sans truncate" :title="formatRupiah(financialSummary?.reserved_budget)">
            {{ formatRupiahCompact(financialSummary?.reserved_budget) }}
          </div>
          <div class="text-[10px] text-indigo-600 font-semibold">Verifikasi SPJ</div>
        </div>

        <!-- 5. Realisasi -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
          <div class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider">Realisasi</div>
          <div class="text-base font-black text-emerald-900 font-sans truncate" :title="formatRupiah(financialSummary?.realized_budget)">
            {{ formatRupiahCompact(financialSummary?.realized_budget) }}
          </div>
          <div class="text-[10px] text-emerald-700 font-semibold">Belanja final</div>
        </div>

        <!-- 6. Saldo Tersedia -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
          <div class="text-[10px] font-bold text-slate-900 uppercase tracking-wider">Saldo Tersedia</div>
          <div class="text-base font-black text-slate-900 font-sans truncate" :title="formatRupiah(financialSummary?.available_balance)">
            {{ formatRupiahCompact(financialSummary?.available_balance) }}
          </div>
          <div class="text-[10px] text-slate-500 font-semibold">Sisa bebas</div>
        </div>

        <!-- 7. Serapan -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
          <div class="text-[10px] font-bold text-emerald-800 uppercase tracking-wider">Serapan</div>
          <div class="text-base font-black text-emerald-950 font-sans">
            {{ financialSummary?.serapan_rate }}%
          </div>
          <div class="text-[10px] text-emerald-700">Realisasi / Pagu</div>
        </div>

        <!-- 8. Utilization -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
          <div class="text-[10px] font-bold text-indigo-800 uppercase tracking-wider">Utilisasi</div>
          <div class="text-base font-black text-indigo-950 font-sans">
            {{ financialSummary?.utilization_rate }}%
          </div>
          <div class="text-[10px] text-indigo-700">Komitmen total</div>
        </div>
      </div>

      <!-- SECTION 4: INTERACTIVE TABS -->
      <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden space-y-4">
        
        <!-- Tab Navigation Bar -->
        <div class="border-b border-slate-200/80 px-6 pt-4 flex flex-wrap items-center gap-2 bg-slate-50/50">
          <button 
            @click="activeTab = 'source_lines'" 
            :class="[
              'px-4 py-2.5 text-xs font-bold border-b-2 transition flex items-center gap-1.5',
              activeTab === 'source_lines' ? 'border-sky-600 text-sky-600 bg-white rounded-t-xl' : 'border-transparent text-slate-500 hover:text-slate-900'
            ]"
          >
            <FileSpreadsheet class="w-4 h-4" />
            <span>Source Lines (Kertas Kerja DIPA)</span>
          </button>

          <button 
            @click="activeTab = 'transactions'" 
            :class="[
              'px-4 py-2.5 text-xs font-bold border-b-2 transition flex items-center gap-1.5',
              activeTab === 'transactions' ? 'border-sky-600 text-sky-600 bg-white rounded-t-xl' : 'border-transparent text-slate-500 hover:text-slate-900'
            ]"
          >
            <FileText class="w-4 h-4" />
            <span>Transaksi Belanja ({{ budgetBucket?.submissions?.length || 0 }})</span>
          </button>

          <button 
            @click="activeTab = 'revisions'" 
            :class="[
              'px-4 py-2.5 text-xs font-bold border-b-2 transition flex items-center gap-1.5',
              activeTab === 'revisions' ? 'border-sky-600 text-sky-600 bg-white rounded-t-xl' : 'border-transparent text-slate-500 hover:text-slate-900'
            ]"
          >
            <History class="w-4 h-4" />
            <span>Riwayat Revisi ({{ budgetBucket?.revisions?.length || 0 }})</span>
          </button>

          <button 
            @click="activeTab = 'warnings'" 
            :class="[
              'px-4 py-2.5 text-xs font-bold border-b-2 transition flex items-center gap-1.5',
              activeTab === 'warnings' ? 'border-sky-600 text-sky-600 bg-white rounded-t-xl' : 'border-transparent text-slate-500 hover:text-slate-900'
            ]"
          >
            <AlertTriangle class="w-4 h-4 text-amber-500" />
            <span>Deteksi Dini / EWS ({{ budgetBucket?.early_warnings?.length || 0 }})</span>
          </button>

          <button 
            @click="activeTab = 'calculation'" 
            :class="[
              'px-4 py-2.5 text-xs font-bold border-b-2 transition flex items-center gap-1.5',
              activeTab === 'calculation' ? 'border-sky-600 text-sky-600 bg-white rounded-t-xl' : 'border-transparent text-slate-500 hover:text-slate-900'
            ]"
          >
            <Calculator class="w-4 h-4 text-indigo-600" />
            <span>Simulasi &amp; Kalkulasi Saldo</span>
          </button>
        </div>

        <!-- Tab 1: Source Lines (Rincian Komponen Kertas Kerja DIPA) -->
        <div v-if="activeTab === 'source_lines'" class="p-6 space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-sm font-bold text-slate-900">Rincian Komponen &amp; Belanja Kertas Kerja DIPA</h3>
              <p class="text-xs text-slate-500">Daftar item rincian biaya acuan yang membentuk pagu akun {{ budgetBucket?.account_code }}.</p>
            </div>
            <span class="text-[11px] font-semibold text-slate-400">Kertas Kerja Resmi</span>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead>
                <tr class="border-b border-slate-200 text-slate-400 text-[11px] font-semibold uppercase">
                  <th class="pb-2.5">Header Kelompok Belanja</th>
                  <th class="pb-2.5">Uraian Rincian Biaya</th>
                  <th class="pb-2.5 text-center">Volume</th>
                  <th class="pb-2.5 text-center">Satuan</th>
                  <th class="pb-2.5 text-right">Harga Satuan</th>
                  <th class="pb-2.5 text-right">Jumlah (Rp)</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 font-sans">
                <tr v-for="(line, idx) in sourceLines" :key="idx" class="hover:bg-slate-50/70 transition">
                  <td class="py-3 font-semibold text-slate-900">{{ line.header }}</td>
                  <td class="py-3 text-slate-700">{{ line.description }}</td>
                  <td class="py-3 text-center font-bold">{{ line.volume }}</td>
                  <td class="py-3 text-center text-slate-500">{{ line.unit }}</td>
                  <td class="py-3 text-right font-medium text-slate-700">{{ formatRupiah(line.unit_price) }}</td>
                  <td class="py-3 text-right font-bold text-slate-900">{{ formatRupiah(line.total_price) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Tab 2: Transactions -->
        <div v-if="activeTab === 'transactions'" class="p-6 space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-sm font-bold text-slate-900">Transaksi Belanja Terkait</h3>
              <p class="text-xs text-slate-500">Seluruh pencatatan transaksi yang membebankan dana pada pos anggaran ini.</p>
            </div>
            <Link 
              v-if="['PTK', 'ADMIN'].includes(currentUser?.role)" 
              href="/submissions/create" 
              class="px-3 py-1.5 bg-sky-600 text-white rounded-xl text-xs font-bold transition flex items-center gap-1 shadow-sm"
            >
              + Catat Transaksi
            </Link>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-sans">
              <thead>
                <tr class="border-b border-slate-200 text-slate-400 text-[11px] font-semibold uppercase">
                  <th class="pb-2.5">No. Transaksi / Bukti</th>
                  <th class="pb-2.5">Tanggal</th>
                  <th class="pb-2.5">Judul Kegiatan / Uraian</th>
                  <th class="pb-2.5">Pembuat / Unit</th>
                  <th class="pb-2.5 text-right">Nominal</th>
                  <th class="pb-2.5 text-center">Status</th>
                  <th class="pb-2.5 text-center">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="sub in budgetBucket?.submissions" :key="sub.id" class="hover:bg-slate-50/70 transition">
                  <td class="py-3 font-bold text-slate-900">{{ sub.evidence_number || sub.submission_number }}</td>
                  <td class="py-3 text-slate-500">{{ sub.transaction_date || (new Date(sub.created_at).toISOString().split('T')[0]) }}</td>
                  <td class="py-3 font-medium text-slate-800 max-w-xs truncate" :title="sub.title">{{ sub.title }}</td>
                  <td class="py-3 text-slate-600">{{ sub.creator?.name || 'Operator PTK' }}</td>
                  <td class="py-3 text-right font-black text-slate-900">{{ formatRupiah(sub.amount) }}</td>
                  <td class="py-3 text-center">
                    <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-bold border inline-block uppercase', getStatusBadge(sub.status).class]">
                      {{ getStatusBadge(sub.status).label }}
                    </span>
                  </td>
                  <td class="py-3 text-center">
                    <Link :href="`/submissions/${sub.id}`" class="px-2.5 py-1 bg-slate-100 hover:bg-sky-50 text-slate-700 hover:text-sky-700 rounded-lg text-xs font-bold transition">
                      Detail
                    </Link>
                  </td>
                </tr>
                <tr v-if="!budgetBucket?.submissions || budgetBucket.submissions.length === 0">
                  <td colspan="7" class="py-8 text-center text-slate-400">
                    Belum ada transaksi yang memotong pos anggaran ini.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Tab 3: Revision History -->
        <div v-if="activeTab === 'revisions'" class="p-6 space-y-4">
          <div>
            <h3 class="text-sm font-bold text-slate-900">Histori Revisi Anggaran Resmi</h3>
            <p class="text-xs text-slate-500">Jejak perubahan dan pergeseran nominal pagu yang disetujui pimpinan.</p>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-sans">
              <thead>
                <tr class="border-b border-slate-200 text-slate-400 text-[11px] font-semibold uppercase">
                  <th class="pb-2.5">No. Dokumen Revisi</th>
                  <th class="pb-2.5 text-right">Pagu Sebelumnya</th>
                  <th class="pb-2.5 text-right">Pagu Baru</th>
                  <th class="pb-2.5 text-right">Perubahan (+/-)</th>
                  <th class="pb-2.5">Alasan Pergeseran</th>
                  <th class="pb-2.5">Disetujui Oleh</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="rev in budgetBucket?.revisions" :key="rev.id" class="hover:bg-slate-50/70 transition">
                  <td class="py-3 font-bold text-slate-900">{{ rev.revision_number }}</td>
                  <td class="py-3 text-right text-slate-600">{{ formatRupiah(rev.previous_amount) }}</td>
                  <td class="py-3 text-right font-black text-slate-900">{{ formatRupiah(rev.revised_amount) }}</td>
                  <td :class="['py-3 text-right font-bold', rev.difference >= 0 ? 'text-sky-700' : 'text-rose-600']">
                    {{ rev.difference >= 0 ? '+' : '' }}{{ formatRupiah(rev.difference) }}
                  </td>
                  <td class="py-3 text-slate-700 max-w-xs truncate" :title="rev.reason">{{ rev.reason }}</td>
                  <td class="py-3 font-medium text-slate-800">{{ rev.approver?.name || 'Pimpinan Fakultas' }}</td>
                </tr>
                <tr v-if="!budgetBucket?.revisions || budgetBucket.revisions.length === 0">
                  <td colspan="6" class="py-8 text-center text-slate-400">
                    Belum ada riwayat revisi atau pergeseran pada pos anggaran ini.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Tab 4: Warnings / Early Warning System -->
        <div v-if="activeTab === 'warnings'" class="p-6 space-y-4">
          <div>
            <h3 class="text-sm font-bold text-slate-900">Notifikasi &amp; Deteksi Dini (Early Warning System)</h3>
            <p class="text-xs text-slate-500">Peringatan otomatis atas ambang batas saldo, kepatuhan SBM, dan kelengkapan transaksi.</p>
          </div>

          <div v-if="budgetBucket?.early_warnings && budgetBucket.early_warnings.length > 0" class="space-y-3">
            <div 
              v-for="warn in budgetBucket.early_warnings" 
              :key="warn.id" 
              class="p-4 rounded-2xl border flex items-start justify-between gap-3"
              :class="warn.severity === 'CRITICAL' ? 'bg-rose-50 border-rose-200' : (warn.severity === 'HIGH' ? 'bg-orange-50 border-orange-200' : 'bg-amber-50 border-amber-200')"
            >
              <div class="flex items-start gap-3">
                <AlertTriangle class="w-5 h-5 shrink-0 mt-0.5" :class="warn.severity === 'CRITICAL' ? 'text-rose-600' : (warn.severity === 'HIGH' ? 'text-orange-600' : 'text-amber-600')" />
                <div>
                  <div class="flex items-center gap-2">
                    <span class="font-bold text-xs text-slate-900 font-sans">Rule {{ warn.rule_code }}</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase bg-white border">{{ warn.severity }}</span>
                  </div>
                  <p class="text-xs text-slate-800 mt-1">{{ warn.message }}</p>
                  <span class="text-[10px] text-slate-400 mt-1 block">Terdeteksi pada: {{ new Date(warn.created_at).toLocaleString('id-ID') }}</span>
                </div>
              </div>
              <span class="px-2.5 py-1 bg-white rounded-lg text-xs font-bold border">{{ warn.status }}</span>
            </div>
          </div>
          <div v-else class="p-6 rounded-2xl bg-emerald-50/50 border border-emerald-200 text-center text-xs text-emerald-800 flex items-center justify-center gap-2">
            <CheckCircle2 class="w-4 h-4 text-emerald-600" />
            <span>Kondisi Pos Anggaran Aman. Tidak ada peringatan saldo atau kepatuhan yang aktif.</span>
          </div>
        </div>

        <!-- Tab 5: Calculation & Simulation -->
        <div v-if="activeTab === 'calculation'" class="p-6 space-y-5">
          <div>
            <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
              <Calculator class="w-4 h-4 text-indigo-600" />
              <span>Simulasi Ketersediaan Anggaran &amp; Dampak Belanja</span>
            </h3>
            <p class="text-xs text-slate-500">Uji coba ketersediaan saldo sebelum mengajukan transaksi baru untuk menghindari penolakan sistem.</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-5 rounded-2xl bg-slate-50 border border-slate-200">
            <!-- Inputs -->
            <div class="space-y-4">
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Estimasi Nominal Belanja Baru (Rp)</label>
                <input 
                  v-model="simNewExpense" 
                  type="number" 
                  placeholder="Contoh: 15000000" 
                  class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-900 font-sans focus:ring-2 focus:ring-sky-500" 
                />
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Volume / Frekuensi</label>
                <input 
                  v-model="simVolume" 
                  type="number" 
                  min="1" 
                  class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-900 font-sans focus:ring-2 focus:ring-sky-500" 
                />
              </div>

              <div class="p-3 bg-white rounded-xl border border-slate-200 text-xs">
                <span class="text-slate-400 block text-[10px]">Total Estimasi Kebutuhan Dana</span>
                <span class="font-black text-slate-900 font-sans text-base">{{ formatRupiah(simulatedTotalExpense) }}</span>
              </div>
            </div>

            <!-- Outcomes & Solvency Check -->
            <div class="space-y-3 p-4 rounded-2xl" :class="isSolvent ? 'bg-emerald-50/70 border border-emerald-200' : 'bg-rose-50/70 border border-rose-200'">
              <div class="flex items-center gap-2">
                <span :class="['px-2.5 py-0.5 rounded-lg text-xs font-black uppercase', isSolvent ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white']">
                  {{ isSolvent ? 'DANA TERSEDIA' : 'SALDO DEFISIT / MELEBIHI PAGU' }}
                </span>
              </div>

              <div class="space-y-2 text-xs pt-2">
                <div class="flex justify-between">
                  <span class="text-slate-600">Saldo Tersedia Saat Ini:</span>
                  <span class="font-bold text-slate-900 font-sans">{{ formatRupiah(financialSummary?.available_balance) }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-slate-600">Estimasi Pengurangan:</span>
                  <span class="font-bold text-rose-700 font-sans">- {{ formatRupiah(simulatedTotalExpense) }}</span>
                </div>
                <div class="border-t border-slate-200 pt-2 flex justify-between">
                  <span class="font-bold text-slate-900">Simulasi Sisa Saldo Akhir:</span>
                  <span :class="['font-black font-sans text-sm', isSolvent ? 'text-emerald-900' : 'text-rose-700']">
                    {{ formatRupiah(simulatedAvailableBalance) }}
                  </span>
                </div>
                <div class="flex justify-between pt-1">
                  <span class="text-slate-600">Proyeksi Utilisasi Pagu:</span>
                  <span class="font-bold text-indigo-900 font-sans">{{ simulatedUtilization }}%</span>
                </div>
              </div>

              <p class="text-[11px] leading-relaxed pt-2" :class="isSolvent ? 'text-emerald-800' : 'text-rose-800'">
                {{ isSolvent 
                  ? 'Ketersediaan dana mencukupi untuk memproses pengajuan sebesar nilai estimasi tersebut.' 
                  : 'Pengajuan akan ditolak otomatis oleh sistem SIKARA karena melebihi saldo pagu aktif yang tersedia.' 
                }}
              </p>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Official Revision Modal Dialog -->
    <div v-if="isRevisionModalOpen" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl p-6 max-w-lg w-full shadow-2xl border border-slate-200 space-y-4">
        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
          <div>
            <h3 class="text-base font-bold text-slate-900">Revisi Pagu Anggaran Resmi</h3>
            <p class="text-xs text-slate-500">{{ budgetBucket?.account_code }} - {{ budgetBucket?.account_name }}</p>
          </div>
          <button @click="isRevisionModalOpen = false" class="text-slate-400 hover:text-slate-700 text-sm font-bold">&times;</button>
        </div>

        <form @submit.prevent="submitRevision" class="space-y-4 text-xs">
          <div class="p-3 bg-sky-50 border border-sky-200 rounded-xl text-sky-950">
            <strong class="font-bold block">Ketentuan Revisi:</strong>
            Perubahan nominal pagu aktif harus disertai alasan pergeseran yang sah dan akan tercatat permanen pada Log Audit Trail.
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Pagu Aktif Baru (Rp)</label>
            <input 
              type="text" 
              :value="displayRevisedAmount" 
              @input="onRevisedInput" 
              required 
              placeholder="Contoh: 150.000.000" 
              class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-xs font-bold text-slate-900 font-sans focus:ring-2 focus:ring-sky-500" 
            />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Alasan &amp; Skematik Pergeseran</label>
            <textarea 
              v-model="form.reason" 
              rows="3" 
              required 
              placeholder="Contoh: Pergeseran anggaran sisa pengadaan modul lab ke bahan praktikum..." 
              class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs text-slate-900 focus:ring-2 focus:ring-sky-500"
            ></textarea>
          </div>

          <div class="flex items-center justify-end gap-2 pt-2">
            <button 
              type="button" 
              @click="isRevisionModalOpen = false" 
              class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition"
            >
              Batal
            </button>
            <button 
              type="submit" 
              :disabled="form.processing" 
              class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white font-bold rounded-xl transition shadow-md shadow-sky-600/20 disabled:opacity-50"
            >
              Simpan &amp; Terapkan Revisi
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
