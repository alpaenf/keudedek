<script setup>
import { ref } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { 
  AlertTriangle, 
  Search, 
  Building2, 
  CheckCircle2, 
  RefreshCw, 
  ShieldAlert, 
  Clock, 
  Eye,
  Check,
  RotateCcw,
  ShieldCheck,
  Info,
  Calendar,
  Layers,
  Filter,
  ArrowRight,
  Sparkles,
  AlertOctagon,
  SlidersHorizontal,
  X,
  Shield
} from 'lucide-vue-next';

const props = defineProps({
  warnings: Object,
  departments: Array,
  fiscalYears: Array,
  accounts: Array,
  stats: Object,
  ruleConfigs: Array,
  canConfigureParameters: Boolean,
  isPolicyMaker: Boolean,
  filters: Object,
  userRole: String,
});

// 7 Filter States
const search = ref(props.filters?.search || '');
const fiscalYearId = ref(props.filters?.fiscal_year_id || '');
const departmentId = ref(props.filters?.department_id || '');
const accountCode = ref(props.filters?.account_code || '');
const ruleCode = ref(props.filters?.rule_code || '');
const severity = ref(props.filters?.severity || '');
const lifecycleState = ref(props.filters?.lifecycle_state || '');
const startDate = ref(props.filters?.start_date || '');
const endDate = ref(props.filters?.end_date || '');

const isScanning = ref(false);
const isAdvancedFilterOpen = ref(false);

const handleFilter = () => {
  router.get('/warnings', {
    search: search.value || undefined,
    fiscal_year_id: fiscalYearId.value || undefined,
    department_id: departmentId.value || undefined,
    account_code: accountCode.value || undefined,
    rule_code: ruleCode.value || undefined,
    severity: severity.value || undefined,
    lifecycle_state: lifecycleState.value || undefined,
    start_date: startDate.value || undefined,
    end_date: endDate.value || undefined,
  }, { preserveState: true, preserveScroll: true });
};

const resetFilters = () => {
  search.value = '';
  fiscalYearId.value = '';
  departmentId.value = '';
  accountCode.value = '';
  ruleCode.value = '';
  severity.value = '';
  lifecycleState.value = '';
  startDate.value = '';
  endDate.value = '';
  handleFilter();
};

const acknowledge = (id) => {
  router.post(`/warnings/${id}/acknowledge`, {}, { preserveScroll: true });
};

const resolve = (id) => {
  router.post(`/warnings/${id}/resolve`, {}, { preserveScroll: true });
};

const triggerReevaluate = () => {
  isScanning.value = true;
  router.post('/warnings/reevaluate', {}, {
    preserveScroll: true,
    onFinish: () => isScanning.value = false,
  });
};

// 4 Severity Badge Styles
const getSeverityBadge = (sev) => {
  switch (sev) {
    case 'CRITICAL':
      return { label: 'CRITICAL', class: 'bg-rose-100 text-rose-900 border-rose-300 font-black' };
    case 'HIGH':
      return { label: 'HIGH', class: 'bg-orange-100 text-orange-900 border-orange-300 font-extrabold' };
    case 'WARNING':
      return { label: 'WARNING', class: 'bg-amber-100 text-amber-900 border-amber-300 font-bold' };
    default:
      return { label: 'INFO', class: 'bg-sky-100 text-sky-900 border-sky-300 font-semibold' };
  }
};

// 3 Lifecycle State Badge Styles
const getStateBadge = (st) => {
  switch (st) {
    case 'RESOLVED':
      return { label: 'RESOLVED (SELESAI)', class: 'bg-emerald-50 text-emerald-800 border-emerald-300' };
    case 'ACKNOWLEDGED':
      return { label: 'ACKNOWLEDGED (DIPROSES)', class: 'bg-indigo-50 text-indigo-800 border-indigo-300' };
    default:
      return { label: 'OPEN (AKTIF)', class: 'bg-amber-50 text-amber-900 border-amber-300 animate-pulse' };
  }
};

const getRuleTitle = (code) => {
  switch (code) {
    case 'EWS-01':
    case 'EWS-001': return 'EWS-01: Saldo Tersedia Menipis';
    case 'EWS-02':
    case 'EWS-002': return 'EWS-02: Transaksi DP Terlalu Lama';
    case 'EWS-03':
    case 'EWS-003': return 'EWS-03: Realisasi di Bawah Target Kumulatif';
    case 'EX-01': return 'EX-01: Pengecualian Saldo Negatif';
    case 'EWS-004': return 'EWS-004: Data Belum Terpetakan';
    case 'EWS-005': return 'EWS-005: Pengembalian Berulang';
    default: return code;
  }
};

const isConfigModalOpen = ref(false);
const activeRuleConfigCode = ref('EWS-01');

const paramForm = useForm({
  threshold_ratio: 20,
  critical_ratio: 5,
  threshold_days: 30,
  critical_days: 45,
  notes: '',
});

const openConfigModal = () => {
  const ews01 = props.ruleConfigs?.find(r => r.rule_code === 'EWS-01' || r.rule_code === 'EWS-001');
  const ews02 = props.ruleConfigs?.find(r => r.rule_code === 'EWS-02' || r.rule_code === 'EWS-002');
  
  if (ews01?.parameters) {
    const tr = Number(ews01.parameters.threshold_ratio ?? ews01.parameters.warning_ratio ?? 0.20);
    const cr = Number(ews01.parameters.critical_ratio ?? 0.05);
    paramForm.threshold_ratio = tr > 1 ? tr : Math.round(tr * 100);
    paramForm.critical_ratio = cr > 1 ? cr : Math.round(cr * 100);
  }
  if (ews02?.parameters) {
    paramForm.threshold_days = Number(ews02.parameters.threshold_days ?? ews02.parameters.stale_days ?? 30);
    paramForm.critical_days = Number(ews02.parameters.critical_days ?? 45);
  }
  
  paramForm.notes = '';
  isConfigModalOpen.value = true;
};

const saveParameters = (ruleCodeToSave) => {
  let payload = {};
  if (ruleCodeToSave === 'EWS-01') {
    payload = {
      threshold_ratio: Number(paramForm.threshold_ratio) / 100,
      critical_ratio: Number(paramForm.critical_ratio) / 100,
      operator: '<=',
    };
  } else if (ruleCodeToSave === 'EWS-02') {
    payload = {
      threshold_days: Number(paramForm.threshold_days),
      critical_days: Number(paramForm.critical_days),
      operator: '>',
    };
  }
  
  paramForm.post(`/warnings/parameters/${ruleCodeToSave}`, {
    preserveScroll: true,
    data: {
      parameters: payload,
      notes: paramForm.notes,
    },
    onSuccess: () => {
      isConfigModalOpen.value = false;
    },
  });
};
</script>

<template>
  <AppLayout title="Early Warning Center (EWS) &amp; Manajemen Risiko">
    <div class="space-y-6 font-sans">
      
      <!-- Top Header & Actions -->
      <div class="bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <span class="px-2.5 py-0.5 bg-rose-100 text-rose-800 text-[10px] font-black rounded-lg uppercase tracking-wider">
              Early Warning System (EWS)
            </span>
            <span class="text-xs text-slate-400 font-semibold">&bull; Monitoring Risiko &amp; Kepatuhan Pagu</span>
          </div>
          <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight mt-1">
            Early Warning Center (Page P22)
          </h1>
          <p class="text-xs text-slate-500 mt-0.5">
            Pusat pemantauan 5 MVP rule &bull; deteksi saldo kritis, lonjakan serapan, antrean tertahan, dan konflik revisi.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
          <button 
            v-if="canConfigureParameters"
            @click="openConfigModal"
            class="px-4 py-2.5 bg-white hover:bg-slate-50 text-slate-800 border border-slate-300 rounded-2xl text-xs font-bold transition flex items-center gap-2 shadow-xs"
            title="Kelola Parameter dan Ambang Batas EWS"
          >
            <SlidersHorizontal class="w-4 h-4 text-sky-600" />
            <span>Pengaturan Parameter EWS</span>
          </button>

          <button 
            @click="triggerReevaluate"
            :disabled="isScanning"
            class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-2xl text-xs font-bold transition flex items-center gap-2 shadow-md shadow-slate-900/20 disabled:opacity-50"
          >
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': isScanning }" />
            <span>Pindai / Evaluasi EWS Sekarang</span>
          </button>
        </div>
      </div>

      <!-- 4 Severity Metric Cards -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <!-- 1. CRITICAL -->
        <div class="bg-white p-4 rounded-2xl border border-rose-200 bg-rose-50/20 shadow-sm space-y-1">
          <span class="text-[10px] font-bold text-rose-700 uppercase tracking-wider block">CRITICAL (&lt; 5% / Overbudget)</span>
          <div class="text-2xl font-black text-rose-950 font-sans">{{ stats?.critical_count || 0 }}</div>
          <span class="text-[10px] text-rose-600 block">Tindakan darurat diperlukan</span>
        </div>

        <!-- 2. HIGH -->
        <div class="bg-white p-4 rounded-2xl border border-orange-200 bg-orange-50/20 shadow-sm space-y-1">
          <span class="text-[10px] font-bold text-orange-700 uppercase tracking-wider block">HIGH (&lt; 10% / Serapan Tinggi)</span>
          <div class="text-2xl font-black text-orange-950 font-sans">{{ stats?.high_count || 0 }}</div>
          <span class="text-[10px] text-orange-600 block">Pengendalian belanja ketat</span>
        </div>

        <!-- 3. WARNING -->
        <div class="bg-white p-4 rounded-2xl border border-amber-200 bg-amber-50/20 shadow-sm space-y-1">
          <span class="text-[10px] font-bold text-amber-700 uppercase tracking-wider block">WARNING (&gt; 85% / SLA Tertahan)</span>
          <div class="text-2xl font-black text-amber-950 font-sans">{{ stats?.warning_count || 0 }}</div>
          <span class="text-[10px] text-amber-600 block">Perlu perhatian verifikator</span>
        </div>

        <!-- 4. INFO / TOTAL AKTIF -->
        <div class="bg-white p-4 rounded-2xl border border-sky-200 bg-sky-50/20 shadow-sm space-y-1">
          <span class="text-[10px] font-bold text-sky-700 uppercase tracking-wider block">TOTAL TERBUKA (OPEN)</span>
          <div class="text-2xl font-black text-sky-950 font-sans">{{ stats?.total_open || 0 }}</div>
          <span class="text-[10px] text-sky-600 block">Menunggu tindak lanjut</span>
        </div>
      </div>

      <!-- 5 MVP Rules Reference Pill Bar -->
      <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm space-y-2 text-xs">
        <div class="flex items-center justify-between">
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">5 MVP Early Warning Rules</span>
          <span class="text-[10px] text-slate-400">Evaluasi Real-time</span>
        </div>
        <div class="flex flex-wrap gap-2">
          <button 
            @click="ruleCode = ruleCode === 'EWS-001' ? '' : 'EWS-001'; handleFilter();"
            :class="['px-3 py-1.5 rounded-xl font-bold transition flex items-center gap-1.5 border text-[11px]', ruleCode === 'EWS-001' ? 'bg-rose-600 text-white border-rose-600' : 'bg-slate-50 text-slate-700 hover:bg-slate-100 border-slate-200']"
          >
            <AlertOctagon class="w-3.5 h-3.5" />
            <span>EWS-001: Saldo Kritis</span>
          </button>

          <button 
            @click="ruleCode = ruleCode === 'EWS-002' ? '' : 'EWS-002'; handleFilter();"
            :class="['px-3 py-1.5 rounded-xl font-bold transition flex items-center gap-1.5 border text-[11px]', ruleCode === 'EWS-002' ? 'bg-amber-600 text-white border-amber-600' : 'bg-slate-50 text-slate-700 hover:bg-slate-100 border-slate-200']"
          >
            <Clock class="w-3.5 h-3.5" />
            <span>EWS-002: Stale Submission</span>
          </button>

          <button 
            @click="ruleCode = ruleCode === 'EWS-003' ? '' : 'EWS-003'; handleFilter();"
            :class="['px-3 py-1.5 rounded-xl font-bold transition flex items-center gap-1.5 border text-[11px]', ruleCode === 'EWS-003' ? 'bg-rose-700 text-white border-rose-700' : 'bg-slate-50 text-slate-700 hover:bg-slate-100 border-slate-200']"
          >
            <RotateCcw class="w-3.5 h-3.5" />
            <span>EWS-003: Revision Conflict</span>
          </button>

          <button 
            @click="ruleCode = ruleCode === 'EWS-004' ? '' : 'EWS-004'; handleFilter();"
            :class="['px-3 py-1.5 rounded-xl font-bold transition flex items-center gap-1.5 border text-[11px]', ruleCode === 'EWS-004' ? 'bg-sky-600 text-white border-sky-600' : 'bg-slate-50 text-slate-700 hover:bg-slate-100 border-slate-200']"
          >
            <Info class="w-3.5 h-3.5" />
            <span>EWS-004: Unmapped Data</span>
          </button>

          <button 
            @click="ruleCode = ruleCode === 'EWS-005' ? '' : 'EWS-005'; handleFilter();"
            :class="['px-3 py-1.5 rounded-xl font-bold transition flex items-center gap-1.5 border text-[11px]', ruleCode === 'EWS-005' ? 'bg-orange-600 text-white border-orange-600' : 'bg-slate-50 text-slate-700 hover:bg-slate-100 border-slate-200']"
          >
            <RefreshCw class="w-3.5 h-3.5" />
            <span>EWS-005: Repeated Return</span>
          </button>
        </div>
      </div>

      <!-- Multi-dimensional Filter Bar (7 Filters: TA, Jurusan, Akun, Rule, Severity, State, Date) -->
      <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm space-y-4 text-xs">
        <!-- Row 1: Primary Search & Quick Filters -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
          <!-- Search Box -->
          <div class="relative lg:col-span-2">
            <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
            <input 
              v-model="search" 
              @keyup.enter="handleFilter"
              type="text" 
              placeholder="Cari kode aturan (EWS-001), akun, atau isi pesan peringatan..." 
              class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none transition"
            />
          </div>

          <!-- Severity Filter (INFO, WARNING, HIGH, CRITICAL) -->
          <div>
            <select 
              v-model="severity" 
              @change="handleFilter" 
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-900 focus:ring-2 focus:ring-sky-500 focus:outline-none"
            >
              <option value="">Semua Tingkat Keparahan</option>
              <option value="CRITICAL">CRITICAL (Kritis)</option>
              <option value="HIGH">HIGH (Tinggi)</option>
              <option value="WARNING">WARNING (Peringatan)</option>
              <option value="INFO">INFO (Informasi)</option>
            </select>
          </div>

          <!-- State Filter (OPEN, ACKNOWLEDGED, RESOLVED) -->
          <div>
            <select 
              v-model="lifecycleState" 
              @change="handleFilter" 
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-900 focus:ring-2 focus:ring-sky-500 focus:outline-none"
            >
              <option value="">Semua Siklus (State)</option>
              <option value="OPEN">OPEN (Aktif / Belum Respon)</option>
              <option value="ACKNOWLEDGED">ACKNOWLEDGED (Sedang Diproses)</option>
              <option value="RESOLVED">RESOLVED (Telah Selesai)</option>
            </select>
          </div>
        </div>

        <!-- Row 2: Advanced Filters (TA, Jurusan, Akun, Rule, Date) -->
        <div class="pt-3 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
          <!-- 1. TA -->
          <div>
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Tahun Anggaran</label>
            <select v-model="fiscalYearId" @change="handleFilter" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-bold text-slate-900 focus:ring-2 focus:ring-sky-500">
              <option value="">Semua TA</option>
              <option v-for="fy in fiscalYears" :key="fy.id" :value="fy.id">TA {{ fy.year }}</option>
            </select>
          </div>

          <!-- 2. Jurusan -->
          <div>
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Jurusan</label>
            <select v-model="departmentId" @change="handleFilter" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-bold text-slate-900 focus:ring-2 focus:ring-sky-500">
              <option value="">Semua Jurusan</option>
              <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.code }} &mdash; {{ d.name }}</option>
            </select>
          </div>

          <!-- 3. Akun -->
          <div>
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Mata Anggaran (Akun)</label>
            <select v-model="accountCode" @change="handleFilter" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-bold text-slate-900 focus:ring-2 focus:ring-sky-500">
              <option value="">Semua Akun</option>
              <option v-for="acc in accounts" :key="acc.account_code" :value="acc.account_code">
                {{ acc.account_code }} &mdash; {{ acc.account_name }}
              </option>
            </select>
          </div>

          <!-- 4. Periode Tanggal -->
          <div class="lg:col-span-2">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Periode Terdeteksi</label>
            <div class="flex items-center gap-2">
              <input type="date" v-model="startDate" @change="handleFilter" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900" />
              <span class="text-slate-400">&ndash;</span>
              <input type="date" v-model="endDate" @change="handleFilter" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900" />
              <button @click="resetFilters" title="Reset Filter" class="p-2 bg-rose-50 text-rose-700 hover:bg-rose-100 rounded-xl border border-rose-200 transition">
                <RotateCcw class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Warning Table / Cards -->
      <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden space-y-4">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <ShieldAlert class="w-4 h-4 text-rose-600" />
            <h3 class="text-sm font-bold text-slate-900">Daftar Peringatan Dini Aktif</h3>
          </div>
          <span class="text-xs text-slate-400 font-semibold">Total {{ warnings.total || 0 }} Peringatan Terdaftar</span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs font-sans border-collapse">
            <thead class="bg-slate-50 text-slate-400 uppercase tracking-wider text-[10px] border-b border-slate-200">
              <tr>
                <th class="py-3 px-3.5 font-semibold">Aturan (Rule)</th>
                <th class="py-3 px-3 font-semibold">Objek Target</th>
                <th class="py-3 px-3 font-semibold">Tingkat Keparahan</th>
                <th class="py-3 px-3.5 font-semibold">Uraian Peringatan &amp; Alasan</th>
                <th class="py-3 px-3 text-center font-semibold">Status Siklus (State)</th>
                <th class="py-3 px-3 font-semibold">Waktu Deteksi</th>
                <th class="py-3 px-3.5 text-center font-semibold">Aksi Tindak Lanjut</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr 
                v-for="w in warnings.data" 
                :key="w.id" 
                :class="[
                  'transition',
                  w.severity === 'CRITICAL' ? 'bg-rose-50/20 hover:bg-rose-50/40' : w.severity === 'HIGH' ? 'bg-orange-50/20 hover:bg-orange-50/40' : 'hover:bg-slate-50/70'
                ]"
              >
                <!-- 1. Aturan (Rule) -->
                <td class="py-3.5 px-3.5 whitespace-nowrap">
                  <span class="font-mono font-black text-slate-900 text-xs block">
                    {{ w.rule_code }}
                  </span>
                  <span class="text-[10px] text-slate-500 block font-medium">
                    {{ getRuleTitle(w.rule_code) }}
                  </span>
                </td>

                <!-- 2. Objek Target -->
                <td class="py-3.5 px-3 whitespace-nowrap">
                  <span class="font-bold text-slate-900 block text-xs">
                    {{ w.target_object || (w.department?.code ? (w.department.code + ' ' + (w.budget_bucket ? '[' + w.budget_bucket.account_code + ']' : '')) : 'Fakultas Teknik') }}
                  </span>
                  <span v-if="w.budget_bucket" class="font-mono text-sky-800 text-[10px] block">
                    {{ w.budget_bucket?.account_name }}
                  </span>
                </td>

                <!-- 3. Severity -->
                <td class="py-3.5 px-3 whitespace-nowrap">
                  <span :class="['px-2.5 py-0.5 rounded-full text-[10px] border inline-block uppercase', getSeverityBadge(w.severity).class]">
                    {{ getSeverityBadge(w.severity).label }}
                  </span>
                </td>

                <!-- 4. Pesan Peringatan & Alasan -->
                <td class="py-3.5 px-3.5 max-w-md">
                  <div class="font-medium text-slate-800 leading-relaxed text-xs">
                    {{ w.message }}
                  </div>
                  <div v-if="w.reason" class="text-[11px] text-slate-500 mt-1 line-clamp-1 italic">
                    Alasan: {{ w.reason }}
                  </div>
                  <div v-if="w.acknowledger" class="text-[10px] text-indigo-700 mt-1 font-semibold flex items-center gap-1">
                    <CheckCircle2 class="w-3 h-3" /> Direspon oleh {{ w.acknowledger?.name }} ({{ new Date(w.acknowledged_at || w.updated_at).toLocaleString('id-ID') }})
                  </div>
                </td>

                <!-- 5. Status State (OPEN, ACKNOWLEDGED, RESOLVED) -->
                <td class="py-3.5 px-3 text-center whitespace-nowrap">
                  <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-bold border inline-block uppercase', getStateBadge(w.lifecycle_state).class]">
                    {{ getStateBadge(w.lifecycle_state).label }}
                  </span>
                </td>

                <!-- 6. Waktu Deteksi -->
                <td class="py-3.5 px-3 whitespace-nowrap text-slate-500 text-[10px]">
                  {{ new Date(w.created_at).toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' }) }}
                </td>

                <!-- 7. Action Buttons -->
                <td class="py-3.5 px-3.5 text-center whitespace-nowrap">
                  <div class="flex items-center justify-center gap-1.5">
                    <!-- View Detail Button (Page P23) -->
                    <Link 
                      :href="`/warnings/${w.id}`"
                      class="px-2.5 py-1 bg-sky-50 hover:bg-sky-100 text-sky-700 border border-sky-200 rounded-lg text-[11px] font-bold transition inline-flex items-center gap-1 shadow-xs"
                      title="Lihat Rincian Peringatan &amp; Kalkulasi"
                    >
                      <Eye class="w-3 h-3" />
                      <span>Detail</span>
                    </Link>

                    <!-- Acknowledge Button -->
                    <button 
                      v-if="w.lifecycle_state === 'OPEN'"
                      @click="acknowledge(w.id)"
                      class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-lg text-[11px] font-bold transition inline-flex items-center gap-1 shadow-sm"
                      title="Tandai telah direspon dan dipelajari"
                    >
                      <Check class="w-3 h-3" />
                      <span>Respon</span>
                    </button>

                    <!-- Resolve Button -->
                    <button 
                      v-if="w.lifecycle_state !== 'RESOLVED'"
                      @click="resolve(w.id)"
                      class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-[11px] font-bold transition inline-flex items-center gap-1 shadow-sm shadow-emerald-600/20"
                      title="Selesaikan peringatan ini"
                    >
                      <CheckCircle2 class="w-3 h-3" />
                      <span>Selesaikan</span>
                    </button>

                    <span v-if="w.lifecycle_state === 'RESOLVED'" class="text-emerald-700 font-bold text-[11px] flex items-center gap-1 justify-center">
                      <CheckCircle2 class="w-3.5 h-3.5" /> Selesai
                    </span>
                  </div>
                </td>
              </tr>

              <tr v-if="!warnings.data || warnings.data.length === 0">
                <td colspan="7" class="py-10 text-center text-slate-400">
                  Tidak ada data peringatan risiko yang sesuai dengan kriteria filter.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="warnings.links && warnings.links.length > 3" class="p-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
          <span class="text-slate-500 font-medium">
            Menampilkan {{ warnings.from ?? 0 }} - {{ warnings.to ?? 0 }} dari {{ warnings.total }} peringatan
          </span>

          <div class="flex items-center gap-1">
            <Link
              v-for="(link, i) in warnings.links"
              :key="i"
              :href="link.url || '#'"
              v-html="link.label"
              :class="[
                'px-3 py-1.5 rounded-xl text-xs font-bold transition',
                link.active ? 'bg-sky-600 text-white shadow-sm' : 'bg-slate-50 text-slate-700 hover:bg-slate-100 border border-slate-200',
                !link.url ? 'opacity-40 cursor-not-allowed' : ''
              ]"
            />
          </div>
        </div>
      </div>

    </div>

    <!-- EWS Parameter Configuration Modal (KF-07, F-11) -->
    <div v-if="isConfigModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="max-w-xl w-full bg-white rounded-3xl border border-slate-200 shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-150">
        <!-- Modal Header -->
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center font-bold text-white bg-sky-600">
              <SlidersHorizontal class="w-5 h-5" />
            </div>
            <div>
              <h3 class="text-base font-bold text-slate-900">Pengaturan Parameter &amp; Ambang Batas EWS</h3>
              <p class="text-xs text-slate-500 font-sans">
                {{ isPolicyMaker ? 'Kewenangan Penetapan Kebijakan: Pimpinan Fakultas (WD / Dekan)' : 'Kewenangan Operasional: Administrator Teknis Sistem' }}
              </p>
            </div>
          </div>
          <button @click="isConfigModalOpen = false" class="p-1 text-slate-400 hover:text-slate-700 rounded-lg">
            <X class="w-5 h-5" />
          </button>
        </div>

        <div class="p-6 space-y-5 text-xs">
          <!-- Authority Notice Banner -->
          <div :class="['p-3.5 rounded-2xl border flex items-start gap-2.5', isPolicyMaker ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-sky-50 border-sky-200 text-sky-900']">
            <Shield class="w-4 h-4 shrink-0 mt-0.5" :class="isPolicyMaker ? 'text-emerald-600' : 'text-sky-600'" />
            <div class="space-y-0.5">
              <span class="font-bold block">
                {{ isPolicyMaker ? 'Hak Penetapan Kebijakan Operasional Resmi' : 'Akses Konfigurasi Administrator Teknis' }}
              </span>
              <p class="text-[11px] leading-relaxed">
                Setiap pemutakhiran parameter akan menaikkan nomor versi parameter secara otomatis (versioning audit trail) dan dicatat ke dalam log aktivitas sistem.
              </p>
            </div>
          </div>

          <!-- Rule Switcher Tabs -->
          <div class="flex gap-2 border-b border-slate-200 pb-2">
            <button 
              type="button"
              @click="activeRuleConfigCode = 'EWS-01'"
              :class="['px-3 py-1.5 rounded-xl font-bold transition text-xs', activeRuleConfigCode === 'EWS-01' ? 'bg-sky-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200']"
            >
              EWS-01 (Saldo Menipis)
            </button>
            <button 
              type="button"
              @click="activeRuleConfigCode = 'EWS-02'"
              :class="['px-3 py-1.5 rounded-xl font-bold transition text-xs', activeRuleConfigCode === 'EWS-02' ? 'bg-sky-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200']"
            >
              EWS-02 (Durasi Transaksi DP)
            </button>
          </div>

          <!-- Form Tab: EWS-01 -->
          <form v-if="activeRuleConfigCode === 'EWS-01'" @submit.prevent="saveParameters('EWS-01')" class="space-y-4">
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1">
              <span class="font-bold text-slate-900 block">Indikator EWS-01 (Proposal Tabel 24 &amp; Persamaan 7-8)</span>
              <p class="text-slate-600 text-[11px] leading-relaxed">
                Kondisi terpicu: <code>0 &lt;= q &lt;= theta_s</code>, di mana <code>q = (S / P) * 100%</code>.
              </p>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block font-semibold text-slate-700 mb-1">Ambang Peringatan / Warning (&theta;<sub>s</sub> %)</label>
                <div class="relative">
                  <input 
                    v-model="paramForm.threshold_ratio" 
                    type="number" 
                    min="1" 
                    max="100" 
                    step="0.5" 
                    required
                    class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:bg-white focus:ring-2 focus:ring-sky-500 font-sans font-bold"
                  >
                  <span class="absolute right-3 top-2 text-slate-400 font-bold">%</span>
                </div>
                <span class="text-[10px] text-slate-400 mt-1 block">Default proposal: 20%</span>
              </div>

              <div>
                <label class="block font-semibold text-slate-700 mb-1">Ambang Kritis / Critical (%)</label>
                <div class="relative">
                  <input 
                    v-model="paramForm.critical_ratio" 
                    type="number" 
                    min="1" 
                    max="50" 
                    step="0.5" 
                    required
                    class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:bg-white focus:ring-2 focus:ring-sky-500 font-sans font-bold"
                  >
                  <span class="absolute right-3 top-2 text-slate-400 font-bold">%</span>
                </div>
                <span class="text-[10px] text-slate-400 mt-1 block">Default proposal: 5%</span>
              </div>
            </div>

            <div>
              <label class="block font-semibold text-slate-700 mb-1">Dasar Kebijakan / Catatan Perubahan Parameter</label>
              <textarea 
                v-model="paramForm.notes" 
                rows="2" 
                placeholder="Contoh: Penyesuaian ambang batas berdasarkan SK Pimpinan..."
                class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:bg-white focus:ring-2 focus:ring-sky-500"
              ></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
              <button type="button" @click="isConfigModalOpen = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-semibold transition">Batal</button>
              <button type="submit" :disabled="paramForm.processing" class="px-5 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded-xl font-bold transition flex items-center gap-1.5 shadow-md shadow-sky-600/20">
                <CheckCircle2 class="w-4 h-4" />
                Simpan Parameter EWS-01
              </button>
            </div>
          </form>

          <!-- Form Tab: EWS-02 -->
          <form v-if="activeRuleConfigCode === 'EWS-02'" @submit.prevent="saveParameters('EWS-02')" class="space-y-4">
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1">
              <span class="font-bold text-slate-900 block">Indikator EWS-02 (Proposal Tabel 24 &amp; Persamaan 9-10)</span>
              <p class="text-slate-600 text-[11px] leading-relaxed">
                Kondisi terpicu: <code>status = DP AND a_i &gt; tau</code>, di mana <code>a_i</code> adalah umur hari kalender transaksi dalam proses.
              </p>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block font-semibold text-slate-700 mb-1">Batas Waktu Peringatan (&tau; Hari Kalender)</label>
                <div class="relative">
                  <input 
                    v-model="paramForm.threshold_days" 
                    type="number" 
                    min="1" 
                    max="180" 
                    required
                    class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:bg-white focus:ring-2 focus:ring-sky-500 font-sans font-bold"
                  >
                  <span class="absolute right-3 top-2 text-slate-400 font-bold">Hari</span>
                </div>
                <span class="text-[10px] text-slate-400 mt-1 block">Default proposal: 30 hari</span>
              </div>

              <div>
                <label class="block font-semibold text-slate-700 mb-1">Batas Kritis / Critical (Hari)</label>
                <div class="relative">
                  <input 
                    v-model="paramForm.critical_days" 
                    type="number" 
                    min="1" 
                    max="365" 
                    required
                    class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:bg-white focus:ring-2 focus:ring-sky-500 font-sans font-bold"
                  >
                  <span class="absolute right-3 top-2 text-slate-400 font-bold">Hari</span>
                </div>
                <span class="text-[10px] text-slate-400 mt-1 block">Default proposal: 45 hari</span>
              </div>
            </div>

            <div>
              <label class="block font-semibold text-slate-700 mb-1">Dasar Kebijakan / Catatan Perubahan Parameter</label>
              <textarea 
                v-model="paramForm.notes" 
                rows="2" 
                placeholder="Contoh: Penyesuaian batas SLA berkas belanja berdasarkan rapat koordinasi..."
                class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:bg-white focus:ring-2 focus:ring-sky-500"
              ></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
              <button type="button" @click="isConfigModalOpen = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-semibold transition">Batal</button>
              <button type="submit" :disabled="paramForm.processing" class="px-5 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded-xl font-bold transition flex items-center gap-1.5 shadow-md shadow-sky-600/20">
                <CheckCircle2 class="w-4 h-4" />
                Simpan Parameter EWS-02
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
