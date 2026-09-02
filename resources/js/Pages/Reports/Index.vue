<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { 
  Download, 
  FileText, 
  Printer, 
  Building2, 
  Layers, 
  Wallet, 
  Activity, 
  Calendar, 
  Filter, 
  RotateCcw, 
  ShieldAlert, 
  CheckCircle2, 
  AlertTriangle, 
  Percent, 
  TrendingUp, 
  Receipt,
  BookOpen,
  PieChart,
  BarChart3,
  SlidersHorizontal,
  ChevronDown
} from 'lucide-vue-next';

const props = defineProps({
  activeReport: String,
  reportByDept: Array,
  reportPaguVsReal: Object,
  reportByAccount: Array,
  reportByActivity: Array,
  reportByProdi: Array,
  reportTransactions: Array,
  reportBudgetBalances: Array,
  reportEwsSummary: Object,
  reportRevisionComp: Array,
  totalAllocated: Number,
  totalReserved: Number,
  totalRealized: Number,
  totalAvailable: Number,
  serapanRate: Number,
  utilizationRate: Number,
  departments: Array,
  fiscalYears: Array,
  fundingSources: Array,
  budgetVersions: Array,
  studyPrograms: Array,
  accounts: Array,
  filters: Object,
});

const currentReport = ref(props.activeReport || 'REALISASI_JURUSAN');

// 13 Filters State
const fiscalYearId = ref(props.filters?.fiscal_year_id || '');
const budgetVersionId = ref(props.filters?.budget_version_id || '');
const fundingSourceId = ref(props.filters?.funding_source_id || '');
const departmentId = ref(props.filters?.department_id || '');
const studyProgramId = ref(props.filters?.study_program_id || '');
const programCode = ref(props.filters?.program_code || '');
const activityCode = ref(props.filters?.activity_code || '');
const kroCode = ref(props.filters?.kro_code || '');
const roCode = ref(props.filters?.ro_code || '');
const subcomponentCode = ref(props.filters?.subcomponent_code || '');
const accountCode = ref(props.filters?.account_code || '');
const startDate = ref(props.filters?.start_date || '');
const endDate = ref(props.filters?.end_date || '');
const status = ref(props.filters?.status || '');

const isFilterExpanded = ref(false);

const handleFilter = (targetReport = null) => {
  if (targetReport) {
    currentReport.value = targetReport;
  }

  router.get('/reports', {
    report: currentReport.value,
    fiscal_year_id: fiscalYearId.value || undefined,
    budget_version_id: budgetVersionId.value || undefined,
    funding_source_id: fundingSourceId.value || undefined,
    department_id: departmentId.value || undefined,
    study_program_id: studyProgramId.value || undefined,
    program_code: programCode.value || undefined,
    activity_code: activityCode.value || undefined,
    kro_code: kroCode.value || undefined,
    ro_code: roCode.value || undefined,
    subcomponent_code: subcomponentCode.value || undefined,
    account_code: accountCode.value || undefined,
    start_date: startDate.value || undefined,
    end_date: endDate.value || undefined,
    status: status.value || undefined,
  }, { preserveState: true, preserveScroll: true });
};

const resetFilters = () => {
  fiscalYearId.value = '';
  budgetVersionId.value = '';
  fundingSourceId.value = '';
  departmentId.value = '';
  studyProgramId.value = '';
  programCode.value = '';
  activityCode.value = '';
  kroCode.value = '';
  roCode.value = '';
  subcomponentCode.value = '';
  accountCode.value = '';
  startDate.value = '';
  endDate.value = '';
  status.value = '';
  handleFilter(currentReport.value);
};

const exportPdf = () => {
  const params = new URLSearchParams({
    department_id: departmentId.value || '',
    fiscal_year_id: fiscalYearId.value || '',
  });
  window.open(`/reports/export-pdf?${params.toString()}`, '_blank');
};

const exportXlsx = () => {
  const params = new URLSearchParams({
    department_id: departmentId.value || '',
    fiscal_year_id: fiscalYearId.value || '',
  });
  window.open(`/reports/export-xlsx?${params.toString()}`, '_blank');
};

const exportCsv = () => {
  const params = new URLSearchParams({
    department_id: departmentId.value || '',
    fiscal_year_id: fiscalYearId.value || '',
  });
  window.open(`/reports/export-csv?${params.toString()}`, '_blank');
};

const exportDocx = () => {
  const params = new URLSearchParams({
    department_id: departmentId.value || '',
    fiscal_year_id: fiscalYearId.value || '',
  });
  window.open(`/reports/export-docx?${params.toString()}`, '_blank');
};

const printReport = () => {
  window.print();
};

const formatRupiah = (val) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0);
};

const formatRupiahCompact = (val) => {
  const num = Number(val || 0);
  if (Math.abs(num) >= 1_000_000_000_000) {
    return 'Rp ' + (num / 1_000_000_000_000).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) + ' T';
  }
  if (Math.abs(num) >= 1_000_000_000) {
    return 'Rp ' + (num / 1_000_000_000).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) + ' M';
  }
  if (Math.abs(num) >= 100_000_000) {
    return 'Rp ' + (num / 1_000_000).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 1 }) + ' Jt';
  }
  return formatRupiah(num);
};

// 9 Report Navigation Tabs Definitions
const reportTabs = [
  { key: 'REALISASI_JURUSAN', label: 'Realisasi per Jurusan' },
  { key: 'PAGU_VS_REALISASI', label: 'Pagu vs Dalam Proses vs Realisasi' },
  { key: 'REALISASI_AKUN', label: 'Realisasi per Akun' },
  { key: 'REALISASI_KEGIATAN', label: 'Realisasi per Kegiatan' },
  { key: 'REALISASI_PRODI', label: 'Realisasi per Prodi' },
  { key: 'TRANSAKSI_PERIODE', label: 'Transaksi per Periode' },
  { key: 'SALDO_ANGGARAN', label: 'Saldo Anggaran' },
  { key: 'EWS_SUMMARY', label: 'Early Warning Summary' },
  { key: 'REVISION_COMP', label: 'Revision Comparison' },
];
</script>

<template>
  <AppLayout title="Pusat Pelaporan &amp; Analisis Realisasi Anggaran">
    <div class="space-y-6 font-sans">
      
      <!-- Header & Export Action Bar -->
      <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <span class="px-2.5 py-0.5 bg-sky-100 text-sky-800 text-[10px] font-black rounded-lg uppercase tracking-wider">
              Laporan Realisasi Anggaran (LRA)
            </span>
            <span class="text-xs text-slate-400 font-semibold">&bull; Fakultas Teknik UNSOED</span>
          </div>
          <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight mt-1">
            Pusat Pelaporan &amp; Analisis Anggaran (Page P24)
          </h1>
          <p class="text-xs text-slate-500 mt-0.5">
            Menyajikan 9 tipe laporan komprehensif, multi-dimensi filter, dan format resmi MASTER CODE + NAME.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <!-- 1. PDF -->
          <button 
            @click="exportPdf" 
            class="px-3.5 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 transition shadow-sm shadow-rose-600/20"
            title="Ekspor Laporan Format Resmi PDF"
          >
            <FileText class="w-4 h-4" />
            <span>PDF</span>
          </button>

          <!-- 2. XLSX -->
          <button 
            @click="exportXlsx" 
            class="px-3.5 py-2 bg-emerald-700 hover:bg-emerald-600 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 transition shadow-sm shadow-emerald-700/20"
            title="Ekspor Laporan Format Microsoft Excel (XLSX)"
          >
            <Download class="w-4 h-4" />
            <span>XLSX</span>
          </button>

          <!-- 3. CSV -->
          <button 
            @click="exportCsv" 
            class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 transition shadow-sm shadow-emerald-600/20"
            title="Ekspor Laporan Tabular CSV (UTF-8)"
          >
            <Download class="w-4 h-4" />
            <span>CSV</span>
          </button>

          <!-- 4. DOCX -->
          <button 
            @click="exportDocx" 
            class="px-3.5 py-2 bg-sky-700 hover:bg-sky-600 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 transition shadow-sm shadow-sky-700/20"
            title="Ekspor Laporan Naratif Editable Microsoft Word (DOCX)"
          >
            <FileText class="w-4 h-4" />
            <span>DOCX</span>
          </button>

          <!-- 5. PRINT -->
          <button 
            @click="printReport" 
            class="px-3.5 py-2 bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold flex items-center gap-1.5 transition"
            title="Cetak Lembar Dokumen Langsung"
          >
            <Printer class="w-4 h-4" />
            <span>Cetak</span>
          </button>
        </div>
      </div>

      <!-- Financial Totals KPI Banner (4 Cards) -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm space-y-0.5">
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Pagu Alokasi</span>
          <div class="text-xl sm:text-2xl font-black text-slate-900 font-sans truncate" :title="formatRupiah(totalAllocated)">
            {{ formatRupiahCompact(totalAllocated) }}
          </div>
          <span class="text-[10px] text-slate-400 font-medium">100% Pagu Terdistribusi</span>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-amber-200 bg-amber-50/20 shadow-sm space-y-0.5">
          <span class="text-[10px] font-bold text-amber-700 uppercase tracking-wider block">Dalam Proses (Reserved)</span>
          <div class="text-xl sm:text-2xl font-black text-amber-950 font-sans truncate" :title="formatRupiah(totalReserved)">
            {{ formatRupiahCompact(totalReserved) }}
          </div>
          <span class="text-[10px] text-amber-700 font-semibold">{{ ((totalReserved / (totalAllocated || 1)) * 100).toFixed(1) }}% Komitmen</span>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-sky-200 bg-sky-50/20 shadow-sm space-y-0.5">
          <span class="text-[10px] font-bold text-sky-700 uppercase tracking-wider block">Realisasi (Definitif)</span>
          <div class="text-xl sm:text-2xl font-black text-sky-950 font-sans truncate" :title="formatRupiah(totalRealized)">
            {{ formatRupiahCompact(totalRealized) }}
          </div>
          <span class="text-[10px] text-sky-700 font-semibold">{{ serapanRate }}% Serapan Riil</span>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-emerald-200 bg-emerald-50/20 shadow-sm space-y-0.5">
          <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider block">Saldo Bebas (Available)</span>
          <div class="text-xl sm:text-2xl font-black text-emerald-950 font-sans truncate" :title="formatRupiah(totalAvailable)">
            {{ formatRupiahCompact(totalAvailable) }}
          </div>
          <span class="text-[10px] text-emerald-700 font-semibold">{{ ((totalAvailable / (totalAllocated || 1)) * 100).toFixed(1) }}% Sisa Saldo</span>
        </div>
      </div>

      <!-- 9 Report Navigation Tabs (Baru, Realisasi per Jurusan, dll.) -->
      <div class="bg-white p-2 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-1.5 overflow-x-auto text-xs font-bold">
        <button 
          v-for="t in reportTabs" 
          :key="t.key"
          @click="handleFilter(t.key)"
          :class="[
            'px-3.5 py-2.5 rounded-xl transition whitespace-nowrap',
            currentReport === t.key ? 'bg-sky-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          ]"
        >
          {{ t.label }}
        </button>
      </div>

      <!-- 13 Cascading Multi-Dimensional Filter Bar -->
      <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm space-y-4 text-xs">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <Filter class="w-4 h-4 text-sky-600" />
            <h3 class="font-bold text-slate-900">Filter Multi-Dimensi (13 Parameter)</h3>
          </div>
          
          <div class="flex items-center gap-2">
            <button 
              @click="isFilterExpanded = !isFilterExpanded"
              class="text-sky-600 font-bold hover:underline flex items-center gap-1 text-[11px]"
            >
              <span>{{ isFilterExpanded ? 'Sederhanakan Filter' : 'Tampilkan Seluruh 13 Filter' }}</span>
              <ChevronDown class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': isFilterExpanded }" />
            </button>

            <button 
              @click="resetFilters" 
              class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-bold transition flex items-center gap-1 text-[11px]"
            >
              <RotateCcw class="w-3 h-3" />
              <span>Reset</span>
            </button>
          </div>
        </div>

        <!-- Primary 4 Quick Filters -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
          <!-- 1. TA -->
          <div>
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Tahun Anggaran (TA)</label>
            <select v-model="fiscalYearId" @change="handleFilter(null)" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-bold text-slate-900">
              <option value="">Semua TA</option>
              <option v-for="fy in fiscalYears" :key="fy.id" :value="fy.id">TA {{ fy.year }}</option>
            </select>
          </div>

          <!-- 2. Revision -->
          <div>
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Versi Revisi (Revision)</label>
            <select v-model="budgetVersionId" @change="handleFilter(null)" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-bold text-slate-900">
              <option value="">Semua Revisi</option>
              <option v-for="bv in budgetVersions" :key="bv.id" :value="bv.id">{{ bv.revision_no }} ({{ bv.status }})</option>
            </select>
          </div>

          <!-- 3. Fund (Sumber Dana) -->
          <div>
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Sumber Dana (Fund)</label>
            <select v-model="fundingSourceId" @change="handleFilter(null)" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-bold text-slate-900">
              <option value="">Semua Sumber Dana</option>
              <option v-for="fs in fundingSources" :key="fs.id" :value="fs.id">{{ fs.code }} &mdash; {{ fs.name }}</option>
            </select>
          </div>

          <!-- 4. Jurusan -->
          <div>
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Unit Jurusan</label>
            <select v-model="departmentId" @change="handleFilter(null)" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-bold text-slate-900">
              <option value="">Semua Jurusan</option>
              <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.code }} &mdash; {{ d.name }}</option>
            </select>
          </div>
        </div>

        <!-- Expanded 9 Secondary Filters (Hierarchy & Scope) -->
        <div v-if="isFilterExpanded" class="pt-3 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-3 animate-fade-in">
          <!-- 5. Prodi -->
          <div>
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Program Studi (Prodi)</label>
            <select v-model="studyProgramId" @change="handleFilter(null)" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-medium text-slate-900">
              <option value="">Semua Prodi</option>
              <option v-for="p in studyPrograms" :key="p.id" :value="p.id">{{ p.code }} &mdash; {{ p.name }}</option>
            </select>
          </div>

          <!-- 6. Akun -->
          <div>
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Mata Anggaran (Akun)</label>
            <select v-model="accountCode" @change="handleFilter(null)" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-medium text-slate-900">
              <option value="">Semua Akun</option>
              <option v-for="a in accounts" :key="a.account_code" :value="a.account_code">
                [{{ a.account_code }}] {{ a.account_name }}
              </option>
            </select>
          </div>

          <!-- 7. Subkomponen -->
          <div>
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Subkomponen</label>
            <select v-model="subcomponentCode" @change="handleFilter(null)" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-medium text-slate-900">
              <option value="">Semua Subkomponen</option>
              <option value="AA">AA &mdash; Operasional Jurusan</option>
              <option value="AB">AB &mdash; Praktikum &amp; Laboratorium</option>
              <option value="AC">AC &mdash; Kegiatan Akademik &amp; MBKM</option>
            </select>
          </div>

          <!-- 8. Status Transaksi -->
          <div>
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Status Belanja</label>
            <select v-model="status" @change="handleFilter(null)" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-medium text-slate-900">
              <option value="">Semua Status</option>
              <option value="FINAL">FINAL (Selesai)</option>
              <option value="PROCESSING">PROCESSING (Dalam Proses)</option>
              <option value="RETURNED">RETURNED (Dikembalikan)</option>
              <option value="DRAFT">DRAFT</option>
            </select>
          </div>

          <!-- 9. Periode Tanggal -->
          <div class="sm:col-span-2">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Periode Tanggal Transaksi</label>
            <div class="flex items-center gap-2">
              <input type="date" v-model="startDate" @change="handleFilter(null)" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs" />
              <span class="text-slate-400">&ndash;</span>
              <input type="date" v-model="endDate" @change="handleFilter(null)" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs" />
            </div>
          </div>

          <!-- 10. Program / Kegiatan Info -->
          <div class="sm:col-span-2 p-2.5 bg-slate-50 rounded-xl border border-slate-200 text-[11px] text-slate-600">
            <strong>Hierarki DIPA:</strong> Program WA &bull; Kegiatan 4257 &bull; KRO 7734.EBA &bull; RO 994
          </div>
        </div>
      </div>

      <!-- ================================================== -->
      <!-- DATA PRESENTATION SECTION (9 REPORTS)              -->
      <!-- ================================================== -->
      <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden space-y-4">
        
        <!-- ================================================== -->
        <!-- 1. REPORT: REALISASI PER JURUSAN                   -->
        <!-- ================================================== -->
        <div v-if="currentReport === 'REALISASI_JURUSAN'" class="p-6 space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
              <h3 class="text-sm font-bold text-slate-900">Laporan Realisasi per Jurusan (MASTER CODE + NAME)</h3>
              <p class="text-xs text-slate-500">Agregasi penyerapan anggaran per unit pengelola jurusan</p>
            </div>
            <span class="text-xs font-bold text-sky-800 bg-sky-50 px-3 py-1 rounded-xl border border-sky-200">
              5 Jurusan + Fakultas
            </span>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-sans border-collapse">
              <thead class="bg-slate-50 text-slate-400 uppercase tracking-wider text-[10px] border-b border-slate-200">
                <tr>
                  <th class="py-3.5 px-4 font-semibold">Kode &amp; Nama Jurusan</th>
                  <th class="py-3.5 px-4 text-right font-semibold">Pagu Alokasi (Rp)</th>
                  <th class="py-3.5 px-4 text-right font-semibold">Dalam Proses (Rp)</th>
                  <th class="py-3.5 px-4 text-right font-semibold">Realisasi (Rp)</th>
                  <th class="py-3.5 px-4 text-right font-semibold">Saldo Bebas (Rp)</th>
                  <th class="py-3.5 px-4 text-center font-semibold">% Serapan</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="d in reportByDept" :key="d.department_id" class="hover:bg-slate-50/70 transition">
                  <td class="py-3.5 px-4 font-bold text-slate-900" v-html="d.code_name"></td>
                  <td class="py-3.5 px-4 text-right font-medium text-slate-900">{{ formatRupiah(d.allocated_budget) }}</td>
                  <td class="py-3.5 px-4 text-right font-medium text-amber-900">{{ formatRupiah(d.reserved_budget) }}</td>
                  <td class="py-3.5 px-4 text-right font-bold text-sky-900">{{ formatRupiah(d.realized_budget) }}</td>
                  <td class="py-3.5 px-4 text-right font-bold text-emerald-700">{{ formatRupiah(d.available_balance) }}</td>
                  <td class="py-3.5 px-4 text-center">
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-black" :class="d.serapan_rate >= 80 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-800'">
                      {{ d.serapan_rate }}%
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ================================================== -->
        <!-- 2. REPORT: PAGU VS DALAM PROSES VS REALISASI       -->
        <!-- ================================================== -->
        <div v-else-if="currentReport === 'PAGU_VS_REALISASI'" class="p-6 space-y-6">
          <div class="border-b border-slate-100 pb-3">
            <h3 class="text-sm font-bold text-slate-900">Analisis Komparatif: Pagu vs Dalam Proses vs Realisasi</h3>
            <p class="text-xs text-slate-500">Evaluasi efektivitas dan kecepatan serapan pagu total</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="p-5 bg-sky-50 rounded-2xl border border-sky-200 space-y-2">
              <span class="text-xs font-bold text-sky-800 uppercase block">Realisasi Definitif</span>
              <div class="text-2xl font-black text-sky-950 font-sans">{{ formatRupiah(reportPaguVsReal.total_realized) }}</div>
              <div class="text-xs font-bold text-sky-700">{{ reportPaguVsReal.serapan_rate }}% dari Total Pagu</div>
            </div>

            <div class="p-5 bg-amber-50 rounded-2xl border border-amber-200 space-y-2">
              <span class="text-xs font-bold text-amber-800 uppercase block">Dalam Proses (Komitmen)</span>
              <div class="text-2xl font-black text-amber-950 font-sans">{{ formatRupiah(reportPaguVsReal.total_reserved) }}</div>
              <div class="text-xs font-bold text-amber-700">{{ ((reportPaguVsReal.total_reserved / (reportPaguVsReal.total_allocated || 1)) * 100).toFixed(1) }}% dari Total Pagu</div>
            </div>

            <div class="p-5 bg-emerald-50 rounded-2xl border border-emerald-200 space-y-2">
              <span class="text-xs font-bold text-emerald-800 uppercase block">Sisa Saldo Bebas</span>
              <div class="text-2xl font-black text-emerald-950 font-sans">{{ formatRupiah(reportPaguVsReal.total_available) }}</div>
              <div class="text-xs font-bold text-emerald-700">{{ reportPaguVsReal.sisa_rate }}% Sisa Ketersediaan</div>
            </div>
          </div>
        </div>

        <!-- ================================================== -->
        <!-- 3. REPORT: REALISASI PER AKUN (MASTER CODE + NAME) -->
        <!-- ================================================== -->
        <div v-else-if="currentReport === 'REALISASI_AKUN'" class="p-6 space-y-4">
          <div class="border-b border-slate-100 pb-3">
            <h3 class="text-sm font-bold text-slate-900">Laporan Realisasi per Kode Mata Anggaran (MASTER CODE + NAME)</h3>
            <p class="text-xs text-slate-500">Breakdown serapan per jenis belanja (Bahan, Honor, Jasa, Modal)</p>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-sans border-collapse">
              <thead class="bg-slate-50 text-slate-400 uppercase tracking-wider text-[10px] border-b border-slate-200">
                <tr>
                  <th class="py-3.5 px-4 font-semibold">Mata Anggaran (Akun)</th>
                  <th class="py-3.5 px-4 text-right font-semibold">Pagu Alokasi (Rp)</th>
                  <th class="py-3.5 px-4 text-right font-semibold">Dalam Proses (Rp)</th>
                  <th class="py-3.5 px-4 text-right font-semibold">Realisasi (Rp)</th>
                  <th class="py-3.5 px-4 text-right font-semibold">Saldo Bebas (Rp)</th>
                  <th class="py-3.5 px-4 text-center font-semibold">% Serapan</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="a in reportByAccount" :key="a.account_code" class="hover:bg-slate-50/70 transition">
                  <td class="py-3.5 px-4 font-bold text-slate-900">{{ a.code_name }}</td>
                  <td class="py-3.5 px-4 text-right font-medium text-slate-900">{{ formatRupiah(a.allocated_budget) }}</td>
                  <td class="py-3.5 px-4 text-right font-medium text-amber-900">{{ formatRupiah(a.reserved_budget) }}</td>
                  <td class="py-3.5 px-4 text-right font-bold text-sky-900">{{ formatRupiah(a.realized_budget) }}</td>
                  <td class="py-3.5 px-4 text-right font-bold text-emerald-700">{{ formatRupiah(a.available_balance) }}</td>
                  <td class="py-3.5 px-4 text-center font-black text-slate-900">{{ a.serapan_rate }}%</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ================================================== -->
        <!-- 4. REPORT: REALISASI PER KEGIATAN                  -->
        <!-- ================================================== -->
        <div v-else-if="currentReport === 'REALISASI_KEGIATAN'" class="p-6 space-y-4">
          <div class="border-b border-slate-100 pb-3">
            <h3 class="text-sm font-bold text-slate-900">Laporan Realisasi per Kegiatan RKAKL DIPA (MASTER CODE + NAME)</h3>
            <p class="text-xs text-slate-500">Agregasi penyerapan per kode kegiatan dan program resmi</p>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-sans border-collapse">
              <thead class="bg-slate-50 text-slate-400 uppercase tracking-wider text-[10px] border-b border-slate-200">
                <tr>
                  <th class="py-3.5 px-4 font-semibold">Kode &amp; Nama Kegiatan</th>
                  <th class="py-3.5 px-4 font-semibold">Program / Output KRO</th>
                  <th class="py-3.5 px-4 text-right font-semibold">Alokasi Pagu (Rp)</th>
                  <th class="py-3.5 px-4 text-right font-semibold">Realisasi (Rp)</th>
                  <th class="py-3.5 px-4 text-right font-semibold">Saldo Bebas (Rp)</th>
                  <th class="py-3.5 px-4 text-center font-semibold">% Serapan</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="(act, i) in reportByActivity" :key="i" class="hover:bg-slate-50/70 transition">
                  <td class="py-3.5 px-4 font-bold text-slate-900" v-html="act.code_name"></td>
                  <td class="py-3.5 px-4 text-slate-600 font-medium" v-html="act.kro"></td>
                  <td class="py-3.5 px-4 text-right font-medium text-slate-900">{{ formatRupiah(act.allocated_budget) }}</td>
                  <td class="py-3.5 px-4 text-right font-bold text-sky-900">{{ formatRupiah(act.realized_budget) }}</td>
                  <td class="py-3.5 px-4 text-right font-bold text-emerald-700">{{ formatRupiah(act.available_balance) }}</td>
                  <td class="py-3.5 px-4 text-center font-black text-slate-900">{{ act.serapan_rate }}%</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ================================================== -->
        <!-- 5. REPORT: REALISASI PER PRODI                     -->
        <!-- ================================================== -->
        <div v-else-if="currentReport === 'REALISASI_PRODI'" class="p-6 space-y-4">
          <div class="border-b border-slate-100 pb-3">
            <h3 class="text-sm font-bold text-slate-900">Laporan Realisasi per Program Studi (11 Prodi FT)</h3>
            <p class="text-xs text-slate-500">Pemetaan aktivitas belanja operasional tingkat program studi</p>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-sans border-collapse">
              <thead class="bg-slate-50 text-slate-400 uppercase tracking-wider text-[10px] border-b border-slate-200">
                <tr>
                  <th class="py-3.5 px-4 font-semibold">Kode &amp; Nama Program Studi</th>
                  <th class="py-3.5 px-4 font-semibold">Jurusan Induk</th>
                  <th class="py-3.5 px-4 text-center font-semibold">Jumlah Transaksi</th>
                  <th class="py-3.5 px-4 text-right font-semibold">Dalam Proses (Rp)</th>
                  <th class="py-3.5 px-4 text-right font-semibold">Realisasi (Rp)</th>
                  <th class="py-3.5 px-4 text-right font-semibold">Total Aktivitas (Rp)</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="p in reportByProdi" :key="p.prodi_id" class="hover:bg-slate-50/70 transition">
                  <td class="py-3.5 px-4 font-bold text-slate-900" v-html="p.code_name"></td>
                  <td class="py-3.5 px-4 font-semibold text-slate-700">{{ p.department_code }}</td>
                  <td class="py-3.5 px-4 text-center font-mono font-bold text-slate-800">{{ p.transaction_count }}</td>
                  <td class="py-3.5 px-4 text-right font-medium text-amber-900">{{ formatRupiah(p.processing_amount) }}</td>
                  <td class="py-3.5 px-4 text-right font-bold text-sky-900">{{ formatRupiah(p.realized_amount) }}</td>
                  <td class="py-3.5 px-4 text-right font-black text-slate-900">{{ formatRupiah(p.total_activity_amount) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ================================================== -->
        <!-- 6. REPORT: TRANSAKSI PER PERIODE                   -->
        <!-- ================================================== -->
        <div v-else-if="currentReport === 'TRANSAKSI_PERIODE'" class="p-6 space-y-4">
          <div class="border-b border-slate-100 pb-3">
            <h3 class="text-sm font-bold text-slate-900">Laporan Riwayat Transaksi per Periode</h3>
            <p class="text-xs text-slate-500">Daftar transaksi belanja terfilter rentang tanggal</p>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-sans border-collapse">
              <thead class="bg-slate-50 text-slate-400 uppercase tracking-wider text-[10px] border-b border-slate-200">
                <tr>
                  <th class="py-3.5 px-4 font-semibold">Nomor Bukti</th>
                  <th class="py-3.5 px-4 font-semibold">Tanggal</th>
                  <th class="py-3.5 px-4 font-semibold">Jurusan / Prodi</th>
                  <th class="py-3.5 px-4 font-semibold">Mata Anggaran (Akun)</th>
                  <th class="py-3.5 px-4 font-semibold">Uraian Transaksi</th>
                  <th class="py-3.5 px-4 text-right font-semibold">Nominal (Rp)</th>
                  <th class="py-3.5 px-4 text-center font-semibold">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="t in reportTransactions" :key="t.id" class="hover:bg-slate-50/70 transition">
                  <td class="py-3.5 px-4 font-mono font-bold text-slate-900">{{ t.evidence_number }}</td>
                  <td class="py-3.5 px-4 text-slate-600">{{ t.transaction_date }}</td>
                  <td class="py-3.5 px-4 font-bold text-slate-800">{{ t.department_code }}</td>
                  <td class="py-3.5 px-4 text-sky-900 font-semibold">{{ t.account_code_name }}</td>
                  <td class="py-3.5 px-4 text-slate-900 font-medium max-w-xs truncate">{{ t.title }}</td>
                  <td class="py-3.5 px-4 text-right font-black text-slate-900">{{ formatRupiah(t.amount) }}</td>
                  <td class="py-3.5 px-4 text-center">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase" :class="t.status === 'FINAL' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-800'">
                      {{ t.status }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ================================================== -->
        <!-- 7. REPORT: SALDO ANGGARAN                          -->
        <!-- ================================================== -->
        <div v-else-if="currentReport === 'SALDO_ANGGARAN'" class="p-6 space-y-4">
          <div class="border-b border-slate-100 pb-3">
            <h3 class="text-sm font-bold text-slate-900">Laporan Rincian Saldo Anggaran Komprehensif</h3>
            <p class="text-xs text-slate-500">Tabel audit saldo per unit dan subkomponen RKAKL</p>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-sans border-collapse">
              <thead class="bg-slate-50 text-slate-400 uppercase tracking-wider text-[10px] border-b border-slate-200">
                <tr>
                  <th class="py-3.5 px-4 font-semibold">Jurusan</th>
                  <th class="py-3.5 px-4 font-semibold">Akun (Mata Anggaran)</th>
                  <th class="py-3.5 px-4 font-semibold">Subkomponen</th>
                  <th class="py-3.5 px-4 text-right font-semibold">Pagu (Rp)</th>
                  <th class="py-3.5 px-4 text-right font-semibold">Reserved (Rp)</th>
                  <th class="py-3.5 px-4 text-right font-semibold">Realisasi (Rp)</th>
                  <th class="py-3.5 px-4 text-right font-semibold">Saldo Bebas (Rp)</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="b in reportBudgetBalances" :key="b.id" class="hover:bg-slate-50/70 transition">
                  <td class="py-3.5 px-4 font-bold text-slate-900" v-html="b.jurusan_code_name"></td>
                  <td class="py-3.5 px-4 text-sky-900 font-semibold">{{ b.account_code_name }}</td>
                  <td class="py-3.5 px-4 text-slate-600">{{ b.subcomponent_code_name }}</td>
                  <td class="py-3.5 px-4 text-right font-medium text-slate-900">{{ formatRupiah(b.allocated_budget) }}</td>
                  <td class="py-3.5 px-4 text-right font-medium text-amber-900">{{ formatRupiah(b.reserved_budget) }}</td>
                  <td class="py-3.5 px-4 text-right font-bold text-sky-900">{{ formatRupiah(b.realized_budget) }}</td>
                  <td class="py-3.5 px-4 text-right font-black text-emerald-700">{{ formatRupiah(b.available_balance) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ================================================== -->
        <!-- 8. REPORT: EARLY WARNING SUMMARY                   -->
        <!-- ================================================== -->
        <div v-else-if="currentReport === 'EWS_SUMMARY'" class="p-6 space-y-4">
          <div class="border-b border-slate-100 pb-3">
            <h3 class="text-sm font-bold text-slate-900">Rekapitulasi Early Warning &amp; Mitigasi Risiko</h3>
            <p class="text-xs text-slate-500">Ringkasan evaluasi saldo kritis, SLA antrean, dan peringatan kepatuhan</p>
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pb-2">
            <div class="p-3.5 bg-rose-50 rounded-2xl border border-rose-200">
              <span class="text-[10px] font-bold text-rose-700 uppercase block">Kritis (Critical)</span>
              <span class="text-xl font-black text-rose-950 font-sans">{{ reportEwsSummary.critical }}</span>
            </div>
            <div class="p-3.5 bg-orange-50 rounded-2xl border border-orange-200">
              <span class="text-[10px] font-bold text-orange-700 uppercase block">Tinggi (High)</span>
              <span class="text-xl font-black text-orange-950 font-sans">{{ reportEwsSummary.high }}</span>
            </div>
            <div class="p-3.5 bg-amber-50 rounded-2xl border border-amber-200">
              <span class="text-[10px] font-bold text-amber-700 uppercase block">Peringatan (Warning)</span>
              <span class="text-xl font-black text-amber-950 font-sans">{{ reportEwsSummary.warning }}</span>
            </div>
            <div class="p-3.5 bg-sky-50 rounded-2xl border border-sky-200">
              <span class="text-[10px] font-bold text-sky-700 uppercase block">Informasi (Info)</span>
              <span class="text-xl font-black text-sky-950 font-sans">{{ reportEwsSummary.info }}</span>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-sans border-collapse">
              <thead class="bg-slate-50 text-slate-400 uppercase tracking-wider text-[10px] border-b border-slate-200">
                <tr>
                  <th class="py-3 px-3 font-semibold">Aturan</th>
                  <th class="py-3 px-3 font-semibold">Tingkat</th>
                  <th class="py-3 px-3 font-semibold">Jurusan / Akun</th>
                  <th class="py-3 px-3 font-semibold">Uraian Peringatan</th>
                  <th class="py-3 px-3 text-center font-semibold">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="w in reportEwsSummary.items" :key="w.id" class="hover:bg-slate-50/70 transition">
                  <td class="py-3 px-3 font-mono font-bold text-slate-900">{{ w.rule_code }}</td>
                  <td class="py-3 px-3">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold" :class="w.severity === 'CRITICAL' ? 'bg-rose-100 text-rose-900' : 'bg-amber-100 text-amber-900'">
                      {{ w.severity }}
                    </span>
                  </td>
                  <td class="py-3 px-3 font-medium text-slate-800" v-html="w.department_code_name"></td>
                  <td class="py-3 px-3 text-slate-700 max-w-md">{{ w.message }}</td>
                  <td class="py-3 px-3 text-center font-bold text-slate-800">{{ w.lifecycle_state }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ================================================== -->
        <!-- 9. REPORT: REVISION COMPARISON                     -->
        <!-- ================================================== -->
        <div v-else-if="currentReport === 'REVISION_COMP'" class="p-6 space-y-4">
          <div class="border-b border-slate-100 pb-3">
            <h3 class="text-sm font-bold text-slate-900">Laporan Komparasi Versi Revisi Anggaran (Revision Comparison)</h3>
            <p class="text-xs text-slate-500">Analisis delta revisi, belanja berjalan, dan deteksi konflik pagu</p>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-sans border-collapse">
              <thead class="bg-slate-50 text-slate-400 uppercase tracking-wider text-[10px] border-b border-slate-200">
                <tr>
                  <th class="py-3.5 px-4 font-semibold">Nomor Revisi</th>
                  <th class="py-3.5 px-4 font-semibold">Jurusan / Akun</th>
                  <th class="py-3.5 px-4 text-right font-semibold">Pagu Lama (Rp)</th>
                  <th class="py-3.5 px-4 text-right font-semibold">Pagu Baru (Rp)</th>
                  <th class="py-3.5 px-4 text-right font-semibold">Delta (+/-)</th>
                  <th class="py-3.5 px-4 text-right font-semibold">Belanja Berjalan</th>
                  <th class="py-3.5 px-4 font-semibold">Dampak / Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="(r, i) in reportRevisionComp" :key="i" class="hover:bg-slate-50/70 transition">
                  <td class="py-3.5 px-4 font-mono font-bold text-slate-900">{{ r.revision_number }}</td>
                  <td class="py-3.5 px-4 font-bold text-slate-800" v-html="r.department_code_name"></td>
                  <td class="py-3.5 px-4 text-right font-medium text-slate-700">{{ formatRupiah(r.old_pagu) }}</td>
                  <td class="py-3.5 px-4 text-right font-black text-sky-900">{{ formatRupiah(r.new_pagu) }}</td>
                  <td class="py-3.5 px-4 text-right font-bold" :class="r.delta >= 0 ? 'text-emerald-700' : 'text-rose-700'">
                    {{ r.delta >= 0 ? '+' : '' }}{{ formatRupiah(r.delta) }}
                  </td>
                  <td class="py-3.5 px-4 text-right font-medium text-slate-800">{{ formatRupiah(r.dalam_proses + r.realisasi) }}</td>
                  <td class="py-3.5 px-4">
                    <span :class="['px-2.5 py-0.5 rounded text-[10px] font-bold block text-center', r.is_conflict ? 'bg-rose-100 text-rose-900 border border-rose-300' : 'bg-slate-100 text-slate-800']">
                      {{ r.impact_note }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>

    </div>
  </AppLayout>
</template>
