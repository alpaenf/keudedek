<script setup>
import { ref, computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { 
  ArrowLeft, 
  CheckCircle2, 
  AlertTriangle, 
  Check, 
  FileSpreadsheet, 
  Layers, 
  Database, 
  Calendar, 
  Info,
  ShieldCheck,
  Building2,
  ListTree,
  AlertOctagon,
  Copy,
  FolderTree,
  ChevronRight,
  Search
} from 'lucide-vue-next';

const props = defineProps({
  history: Object,
  stagings: Object,
  activeVersion: Object,
  summaryCards: Object,
  masterExtractions: Object,
  deptMappings: Array,
  accountMappings: Array,
  errorItems: Array,
});

const formCommit = useForm({});
const isCommitModalOpen = ref(false);

// Active Tab: 'rows' | 'new_master' | 'department_mapping' | 'account_mapping' | 'errors'
const activeTab = ref('rows');

// Search & Row Filter
const rowSearch = ref('');
const rowStatusFilter = ref('ALL'); // 'ALL' | 'VALID' | 'INVALID'

const filteredStagings = computed(() => {
  if (!props.stagings?.data) return [];
  return props.stagings.data.filter((item) => {
    const matchesStatus = rowStatusFilter.value === 'ALL' || item.status === rowStatusFilter.value;
    const matchesQuery = !rowSearch.value || 
      item.account_code?.toLowerCase().includes(rowSearch.value.toLowerCase()) ||
      item.account_name?.toLowerCase().includes(rowSearch.value.toLowerCase()) ||
      item.department_code?.toLowerCase().includes(rowSearch.value.toLowerCase()) ||
      item.error_message?.toLowerCase().includes(rowSearch.value.toLowerCase());
    return matchesStatus && matchesQuery;
  });
});

const commitImport = () => {
  formCommit.post(`/budgets-import/${props.history.id}/commit`, {
    onSuccess: () => {
      isCommitModalOpen.value = false;
    }
  });
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

const getMasterStatusBadge = (st) => {
  switch (st) {
    case 'EXISTING':
      return { label: 'EXISTING', class: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
    case 'NEW':
      return { label: 'NEW (Akan Dibuat)', class: 'bg-sky-50 text-sky-700 border-sky-200 font-black' };
    default:
      return { label: 'UNMAPPED', class: 'bg-rose-50 text-rose-700 border-rose-200' };
  }
};
</script>

<template>
  <AppLayout :title="`Validation & Mapping: ${history.import_batch_id || history.filename}`">
    <div class="space-y-6 font-sans">
      
      <!-- Top Context Banner -->
      <div class="bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <Link href="/budgets-import" class="p-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-2xl transition">
            <ArrowLeft class="w-5 h-5" />
          </Link>
          <div>
            <div class="flex items-center gap-2">
              <span class="px-2.5 py-0.5 bg-sky-100 text-sky-800 text-[10px] font-black rounded-lg uppercase tracking-wider font-sans">
                {{ history.import_batch_id || ('BATCH-' + history.id) }}
              </span>
              <span class="text-xs text-slate-400 font-semibold">&bull; {{ history.filename }}</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight mt-1">
              Validation, Extraction &amp; Mapping Staging Pagu
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
              Diunggah oleh <strong class="text-slate-800">{{ history.user?.name || 'Administrator' }}</strong> pada {{ new Date(history.created_at).toLocaleString('id-ID') }}
            </p>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <button 
            v-if="history.status === 'PENDING'" 
            @click="isCommitModalOpen = true" 
            :disabled="formCommit.processing || (summaryCards?.error_rows > 0)" 
            class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-2xl text-xs font-bold flex items-center gap-2 transition disabled:opacity-50 shadow-md shadow-emerald-600/20"
          >
            <Check class="w-4 h-4" />
            <span>Commit ke Pagu Aktif (Step 6)</span>
          </button>

          <span v-else class="px-4 py-2 bg-emerald-50 text-emerald-800 border border-emerald-300 rounded-2xl text-xs font-bold flex items-center gap-1.5">
            <ShieldCheck class="w-4 h-4 text-emerald-600" />
            <span>Batch Berhasil Di-Commit</span>
          </span>
        </div>
      </div>

      <!-- 6 Key Summary Metric Cards -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
        <!-- 1. Total Row -->
        <div class="bg-white p-4 sm:p-5 rounded-3xl border border-slate-200/80 shadow-sm space-y-1">
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Row</span>
          <span class="font-sans font-black text-slate-900 text-xl block">{{ summaryCards?.total_rows || 0 }} <span class="text-xs font-normal text-slate-400">Baris</span></span>
          <span class="text-[10px] text-slate-400">Data terunggah</span>
        </div>

        <!-- 2. Valid -->
        <div class="bg-white p-4 sm:p-5 rounded-3xl border border-slate-200/80 shadow-sm space-y-1">
          <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider block">Valid</span>
          <span class="font-sans font-black text-emerald-900 text-xl block">{{ summaryCards?.valid_rows || 0 }} <span class="text-xs font-normal text-slate-400">Baris</span></span>
          <span class="text-[10px] text-emerald-700 font-semibold">Siap di-commit</span>
        </div>

        <!-- 3. Warning -->
        <div class="bg-white p-4 sm:p-5 rounded-3xl border border-slate-200/80 shadow-sm space-y-1">
          <span class="text-[10px] font-bold text-amber-700 uppercase tracking-wider block">Warning</span>
          <span class="font-sans font-black text-amber-900 text-xl block">{{ summaryCards?.warning_rows || 0 }} <span class="text-xs font-normal text-slate-400">Baris</span></span>
          <span class="text-[10px] text-amber-700">Perlu perhatian</span>
        </div>

        <!-- 4. Error -->
        <div :class="['bg-white p-4 sm:p-5 rounded-3xl border shadow-sm space-y-1', (summaryCards?.error_rows > 0) ? 'border-rose-200 bg-rose-50/30' : 'border-slate-200/80']">
          <span :class="['text-[10px] font-bold uppercase tracking-wider block', (summaryCards?.error_rows > 0) ? 'text-rose-700' : 'text-slate-400']">Error</span>
          <span :class="['font-sans font-black text-xl block', (summaryCards?.error_rows > 0) ? 'text-rose-900' : 'text-slate-900']">{{ summaryCards?.error_rows || 0 }} <span class="text-xs font-normal text-slate-400">Baris</span></span>
          <span :class="['text-[10px]', (summaryCards?.error_rows > 0) ? 'text-rose-700 font-bold' : 'text-slate-400']">
            {{ (summaryCards?.error_rows > 0) ? 'Memblokir commit' : 'Nol kesalahan' }}
          </span>
        </div>

        <!-- 5. Duplicate -->
        <div class="bg-white p-4 sm:p-5 rounded-3xl border border-slate-200/80 shadow-sm space-y-1">
          <span class="text-[10px] font-bold text-indigo-700 uppercase tracking-wider block">Duplicate</span>
          <span class="font-sans font-black text-indigo-900 text-xl block">{{ summaryCards?.duplicate_rows || 0 }} <span class="text-xs font-normal text-slate-400">Baris</span></span>
          <span class="text-[10px] text-indigo-600">Kombinasi ganda</span>
        </div>

        <!-- 6. Unmapped -->
        <div class="bg-white p-4 sm:p-5 rounded-3xl border border-slate-200/80 shadow-sm space-y-1">
          <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Unmapped</span>
          <span class="font-sans font-black text-slate-800 text-xl block">{{ summaryCards?.unmapped_rows || 0 }} <span class="text-xs font-normal text-slate-400">Baris</span></span>
          <span class="text-[10px] text-slate-400">Tidak ada mapping</span>
        </div>
      </div>

      <!-- Notice on Auto-Master Creation -->
      <div class="p-4 bg-sky-50/70 border border-sky-200 rounded-2xl flex items-start gap-3 text-xs text-sky-950">
        <Info class="w-4 h-4 text-sky-600 shrink-0 mt-0.5" />
        <p class="leading-relaxed">
          <strong class="font-bold">Ketentuan Ekstraksi Master Data:</strong> Master data baru (Program, Kegiatan, KRO, RO, Komponen, Subkomponen, dan Akun) bertanda <strong class="text-sky-800">[NEW]</strong> yang lolos validasi akan dibuat otomatis ke dalam basis data sistem saat batch ini di-commit. Row yang berstatus error <strong class="text-rose-700">[INVALID]</strong> tidak akan pernah dibuatkan master secara otomatis.
        </p>
      </div>

      <!-- 5 Interactive Tabs Container -->
      <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden space-y-4">
        
        <!-- Tab Navigation Bar -->
        <div class="border-b border-slate-200/80 px-6 pt-4 flex flex-wrap items-center gap-2 bg-slate-50/50">
          <!-- Tab 1: Rows -->
          <button 
            @click="activeTab = 'rows'" 
            :class="[
              'px-4 py-2.5 text-xs font-bold border-b-2 transition flex items-center gap-1.5',
              activeTab === 'rows' ? 'border-sky-600 text-sky-600 bg-white rounded-t-xl' : 'border-transparent text-slate-500 hover:text-slate-900'
            ]"
          >
            <FileSpreadsheet class="w-4 h-4" />
            <span>Rows ({{ history.total_rows }})</span>
          </button>

          <!-- Tab 2: New Master -->
          <button 
            @click="activeTab = 'new_master'" 
            :class="[
              'px-4 py-2.5 text-xs font-bold border-b-2 transition flex items-center gap-1.5',
              activeTab === 'new_master' ? 'border-sky-600 text-sky-600 bg-white rounded-t-xl' : 'border-transparent text-slate-500 hover:text-slate-900'
            ]"
          >
            <ListTree class="w-4 h-4 text-sky-600" />
            <span>New Master Extraction</span>
          </button>

          <!-- Tab 3: Department Mapping -->
          <button 
            @click="activeTab = 'department_mapping'" 
            :class="[
              'px-4 py-2.5 text-xs font-bold border-b-2 transition flex items-center gap-1.5',
              activeTab === 'department_mapping' ? 'border-sky-600 text-sky-600 bg-white rounded-t-xl' : 'border-transparent text-slate-500 hover:text-slate-900'
            ]"
          >
            <Building2 class="w-4 h-4" />
            <span>Department Mapping ({{ deptMappings?.length || 0 }})</span>
          </button>

          <!-- Tab 4: Account Mapping -->
          <button 
            @click="activeTab = 'account_mapping'" 
            :class="[
              'px-4 py-2.5 text-xs font-bold border-b-2 transition flex items-center gap-1.5',
              activeTab === 'account_mapping' ? 'border-sky-600 text-sky-600 bg-white rounded-t-xl' : 'border-transparent text-slate-500 hover:text-slate-900'
            ]"
          >
            <Layers class="w-4 h-4" />
            <span>Account Mapping ({{ accountMappings?.length || 0 }})</span>
          </button>

          <!-- Tab 5: Errors -->
          <button 
            @click="activeTab = 'errors'" 
            :class="[
              'px-4 py-2.5 text-xs font-bold border-b-2 transition flex items-center gap-1.5',
              activeTab === 'errors' ? 'border-rose-600 text-rose-600 bg-white rounded-t-xl' : 'border-transparent text-slate-500 hover:text-slate-900'
            ]"
          >
            <AlertOctagon class="w-4 h-4 text-rose-500" />
            <span>Errors ({{ summaryCards?.error_rows || 0 }})</span>
          </button>
        </div>

        <!-- TAB 1: ROWS -->
        <div v-if="activeTab === 'rows'" class="p-6 space-y-4">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="relative w-full sm:w-72">
              <input 
                v-model="rowSearch" 
                type="text" 
                placeholder="Cari Kode Akun / Nama / Jurusan..." 
                class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:ring-2 focus:ring-sky-500 focus:bg-white transition" 
              />
              <Search class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" />
            </div>

            <div class="flex items-center gap-2">
              <button 
                @click="rowStatusFilter = 'ALL'" 
                :class="['px-3 py-1.5 rounded-xl text-xs font-bold transition', rowStatusFilter === 'ALL' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700']"
              >
                Semua ({{ history.total_rows }})
              </button>
              <button 
                @click="rowStatusFilter = 'VALID'" 
                :class="['px-3 py-1.5 rounded-xl text-xs font-bold transition', rowStatusFilter === 'VALID' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700']"
              >
                Valid ({{ history.valid_rows }})
              </button>
              <button 
                @click="rowStatusFilter = 'INVALID'" 
                :class="['px-3 py-1.5 rounded-xl text-xs font-bold transition', rowStatusFilter === 'INVALID' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-700']"
              >
                Invalid ({{ history.invalid_rows }})
              </button>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-sans border-collapse">
              <thead>
                <tr class="border-b border-slate-200 text-slate-400 uppercase tracking-wider text-[10px]">
                  <th class="py-2.5 px-3 text-center">Status</th>
                  <th class="py-2.5 px-3">Jurusan</th>
                  <th class="py-2.5 px-3">Tahun</th>
                  <th class="py-2.5 px-3">Sumber</th>
                  <th class="py-2.5 px-3">Kode Akun</th>
                  <th class="py-2.5 px-3">Nama Akun</th>
                  <th class="py-2.5 px-3 text-right">Pagu Awal (Rp)</th>
                  <th class="py-2.5 px-3">Keterangan / Error Message</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="stg in filteredStagings" :key="stg.id" :class="stg.status === 'INVALID' ? 'bg-rose-50/40' : 'hover:bg-slate-50/70 transition'">
                  <td class="py-3 px-3 text-center whitespace-nowrap">
                    <span v-if="stg.status === 'VALID'" class="inline-flex items-center gap-1 text-emerald-700 font-bold bg-emerald-100/80 px-2.5 py-0.5 rounded-full text-[10px] border border-emerald-200">
                      <CheckCircle2 class="w-3 h-3" /> VALID
                    </span>
                    <span v-else class="inline-flex items-center gap-1 text-rose-700 font-bold bg-rose-100/80 px-2.5 py-0.5 rounded-full text-[10px] border border-rose-200">
                      <AlertTriangle class="w-3 h-3" /> INVALID
                    </span>
                  </td>
                  <td class="py-3 px-3 font-bold text-slate-900">{{ stg.department_code }}</td>
                  <td class="py-3 px-3 text-slate-600">{{ stg.fiscal_year }}</td>
                  <td class="py-3 px-3 font-semibold text-slate-800">{{ stg.funding_source_code }}</td>
                  <td class="py-3 px-3">
                    <span class="font-sans font-bold text-sky-800 bg-sky-50 px-2 py-0.5 rounded border border-sky-200">
                      {{ stg.account_code }}
                    </span>
                  </td>
                  <td class="py-3 px-3 font-medium text-slate-800 max-w-xs truncate" :title="stg.account_name">
                    {{ stg.account_name }}
                  </td>
                  <td class="py-3 px-3 text-right font-black text-slate-900 font-sans whitespace-nowrap">
                    {{ formatRupiah(stg.initial_budget) }}
                  </td>
                  <td class="py-3 px-3">
                    <span v-if="stg.error_message" class="text-rose-600 font-bold text-[11px]">
                      {{ stg.error_message }}
                    </span>
                    <span v-else class="text-emerald-700 font-semibold text-[11px]">
                      Lolos validasi, siap commit
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- TAB 2: NEW MASTER EXTRACTION -->
        <div v-if="activeTab === 'new_master'" class="p-6 space-y-6">
          <div>
            <h3 class="text-sm font-bold text-slate-900">Ekstraksi Master Data 7 Segmen RKAKL</h3>
            <p class="text-xs text-slate-500">Elemen hierarki yang dideteksi dari berkas import beserta status keberadaannya di basis data master.</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- 1. Program -->
            <div class="bg-slate-50/60 p-4 rounded-2xl border border-slate-200 space-y-2">
              <span class="text-xs font-bold text-slate-700 block">1. Program Ekstraksi</span>
              <div class="space-y-1.5">
                <div v-for="item in masterExtractions?.programs" :key="item.code" class="p-2.5 bg-white rounded-xl border border-slate-200 flex items-center justify-between text-xs">
                  <div>
                    <strong class="text-slate-900 font-sans">{{ item.code }}</strong> &mdash; {{ item.name }}
                  </div>
                  <span :class="['px-2 py-0.5 rounded text-[10px] font-extrabold border', getMasterStatusBadge(item.status).class]">
                    {{ getMasterStatusBadge(item.status).label }}
                  </span>
                </div>
              </div>
            </div>

            <!-- 2. Kegiatan -->
            <div class="bg-slate-50/60 p-4 rounded-2xl border border-slate-200 space-y-2">
              <span class="text-xs font-bold text-slate-700 block">2. Kegiatan Ekstraksi</span>
              <div class="space-y-1.5">
                <div v-for="item in masterExtractions?.activities" :key="item.code" class="p-2.5 bg-white rounded-xl border border-slate-200 flex items-center justify-between text-xs">
                  <div>
                    <strong class="text-slate-900 font-sans">{{ item.code }}</strong> &mdash; {{ item.name }}
                  </div>
                  <span :class="['px-2 py-0.5 rounded text-[10px] font-extrabold border', getMasterStatusBadge(item.status).class]">
                    {{ getMasterStatusBadge(item.status).label }}
                  </span>
                </div>
              </div>
            </div>

            <!-- 3. KRO -->
            <div class="bg-slate-50/60 p-4 rounded-2xl border border-slate-200 space-y-2">
              <span class="text-xs font-bold text-slate-700 block">3. KRO (Klasifikasi Rincian Output)</span>
              <div class="space-y-1.5">
                <div v-for="item in masterExtractions?.kros" :key="item.code" class="p-2.5 bg-white rounded-xl border border-slate-200 flex items-center justify-between text-xs">
                  <div>
                    <strong class="text-slate-900 font-sans">{{ item.code }}</strong> &mdash; {{ item.name }}
                  </div>
                  <span :class="['px-2 py-0.5 rounded text-[10px] font-extrabold border', getMasterStatusBadge(item.status).class]">
                    {{ getMasterStatusBadge(item.status).label }}
                  </span>
                </div>
              </div>
            </div>

            <!-- 4. RO -->
            <div class="bg-slate-50/60 p-4 rounded-2xl border border-slate-200 space-y-2">
              <span class="text-xs font-bold text-slate-700 block">4. RO (Rincian Output)</span>
              <div class="space-y-1.5">
                <div v-for="item in masterExtractions?.ros" :key="item.code" class="p-2.5 bg-white rounded-xl border border-slate-200 flex items-center justify-between text-xs">
                  <div>
                    <strong class="text-slate-900 font-sans">{{ item.code }}</strong> &mdash; {{ item.name }}
                  </div>
                  <span :class="['px-2 py-0.5 rounded text-[10px] font-extrabold border', getMasterStatusBadge(item.status).label]">
                    {{ getMasterStatusBadge(item.status).label }}
                  </span>
                </div>
              </div>
            </div>

            <!-- 5. Komponen & Subkomponen -->
            <div class="bg-slate-50/60 p-4 rounded-2xl border border-slate-200 space-y-2">
              <span class="text-xs font-bold text-slate-700 block">5 &amp; 6. Komponen &amp; Subkomponen</span>
              <div class="space-y-1.5">
                <div v-for="item in masterExtractions?.components" :key="item.code" class="p-2.5 bg-white rounded-xl border border-slate-200 flex items-center justify-between text-xs">
                  <div>
                    <strong class="text-slate-900 font-sans">Komp {{ item.code }}</strong> &mdash; {{ item.name }}
                  </div>
                  <span :class="['px-2 py-0.5 rounded text-[10px] font-extrabold border', getMasterStatusBadge(item.status).class]">
                    {{ getMasterStatusBadge(item.status).label }}
                  </span>
                </div>
                <div v-for="item in masterExtractions?.subcomponents" :key="item.code" class="p-2.5 bg-white rounded-xl border border-slate-200 flex items-center justify-between text-xs">
                  <div>
                    <strong class="text-slate-900 font-sans">Subkomp {{ item.code }}</strong> &mdash; {{ item.name }}
                  </div>
                  <span :class="['px-2 py-0.5 rounded text-[10px] font-extrabold border', getMasterStatusBadge(item.status).class]">
                    {{ getMasterStatusBadge(item.status).label }}
                  </span>
                </div>
              </div>
            </div>

            <!-- 7. Akun -->
            <div class="bg-slate-50/60 p-4 rounded-2xl border border-slate-200 space-y-2">
              <span class="text-xs font-bold text-slate-700 block">7. Mata Anggaran (Akun 6-Digit)</span>
              <div class="space-y-1.5 max-h-56 overflow-y-auto pr-1 custom-scrollbar">
                <div v-for="item in masterExtractions?.accounts" :key="item.code" class="p-2.5 bg-white rounded-xl border border-slate-200 flex items-center justify-between text-xs">
                  <div>
                    <strong class="text-sky-800 font-sans font-bold">{{ item.code }}</strong> &mdash; {{ item.name }}
                  </div>
                  <span :class="['px-2 py-0.5 rounded text-[10px] font-extrabold border', getMasterStatusBadge(item.status).class]">
                    {{ getMasterStatusBadge(item.status).label }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB 3: DEPARTMENT MAPPING -->
        <div v-if="activeTab === 'department_mapping'" class="p-6 space-y-4">
          <div>
            <h3 class="text-sm font-bold text-slate-900">Pemetaan Kode Unit / Jurusan</h3>
            <p class="text-xs text-slate-500">Kecocokan unit yang tertera pada berkas dengan master Department Fakultas Teknik.</p>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-sans">
              <thead>
                <tr class="border-b border-slate-200 text-slate-400 uppercase tracking-wider text-[10px]">
                  <th class="pb-2.5">Kode Sumber Berkas</th>
                  <th class="pb-2.5">Nama Jurusan Terpetakan</th>
                  <th class="pb-2.5">Scope Unit</th>
                  <th class="pb-2.5 text-center">Jumlah Baris</th>
                  <th class="pb-2.5 text-right">Total Alokasi (Rp)</th>
                  <th class="pb-2.5 text-center">Status Mapping</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="dept in deptMappings" :key="dept.import_code" class="hover:bg-slate-50/70 transition">
                  <td class="py-3 font-bold text-slate-900">{{ dept.import_code }}</td>
                  <td class="py-3 font-medium text-slate-800">{{ dept.department_name }}</td>
                  <td class="py-3 text-slate-500">{{ dept.faculty_scope }}</td>
                  <td class="py-3 text-center font-bold text-slate-900">{{ dept.row_count }} Baris</td>
                  <td class="py-3 text-right font-black text-slate-900">{{ formatRupiah(dept.total_amount) }}</td>
                  <td class="py-3 text-center">
                    <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-bold border inline-block uppercase', dept.status === 'MAPPED' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200']">
                      {{ dept.status }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- TAB 4: ACCOUNT MAPPING -->
        <div v-if="activeTab === 'account_mapping'" class="p-6 space-y-4">
          <div>
            <h3 class="text-sm font-bold text-slate-900">Pemetaan Mata Anggaran (Akun Belanja)</h3>
            <p class="text-xs text-slate-500">Ringkasan kode akun yang teridentifikasi beserta total alokasi nominalnya.</p>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-sans">
              <thead>
                <tr class="border-b border-slate-200 text-slate-400 uppercase tracking-wider text-[10px]">
                  <th class="pb-2.5">Kode Akun</th>
                  <th class="pb-2.5">Nama Akun Belanja</th>
                  <th class="pb-2.5 text-center">Jumlah Baris</th>
                  <th class="pb-2.5 text-right">Total Pagu (Rp)</th>
                  <th class="pb-2.5 text-center">Status Master</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="acc in accountMappings" :key="acc.account_code" class="hover:bg-slate-50/70 transition">
                  <td class="py-3 font-bold text-sky-800">{{ acc.account_code }}</td>
                  <td class="py-3 font-medium text-slate-800">{{ acc.account_name }}</td>
                  <td class="py-3 text-center font-bold text-slate-900">{{ acc.row_count }}</td>
                  <td class="py-3 text-right font-black text-slate-900">{{ formatRupiah(acc.total_amount) }}</td>
                  <td class="py-3 text-center">
                    <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-bold border inline-block uppercase', getMasterStatusBadge(acc.status).class]">
                      {{ getMasterStatusBadge(acc.status).label }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- TAB 5: ERRORS -->
        <div v-if="activeTab === 'errors'" class="p-6 space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2 text-rose-700">
                <AlertOctagon class="w-4 h-4 text-rose-600" />
                <span>Daftar Baris Bermasalah (Blocking Validation Errors)</span>
              </h3>
              <p class="text-xs text-slate-500">Seluruh baris di bawah ini wajib diperbaiki pada file sumber sebelum data dapat dicommit.</p>
            </div>
            <span class="px-2.5 py-1 bg-rose-100 text-rose-800 rounded-xl text-xs font-bold">
              {{ errorItems?.length || 0 }} Baris Error
            </span>
          </div>

          <div v-if="errorItems && errorItems.length > 0" class="overflow-x-auto">
            <table class="w-full text-left text-xs font-sans">
              <thead>
                <tr class="border-b border-slate-200 text-slate-400 uppercase tracking-wider text-[10px]">
                  <th class="pb-2.5">Jurusan</th>
                  <th class="pb-2.5">Tahun</th>
                  <th class="pb-2.5">Kode Akun</th>
                  <th class="pb-2.5">Nama Akun</th>
                  <th class="pb-2.5 text-right">Pagu</th>
                  <th class="pb-2.5 text-rose-700">Penyebab Kesalahan &amp; Panduan Solusi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="err in errorItems" :key="err.id" class="bg-rose-50/40 hover:bg-rose-50/70 transition">
                  <td class="py-3 font-bold text-slate-900">{{ err.department_code }}</td>
                  <td class="py-3 text-slate-600">{{ err.fiscal_year }}</td>
                  <td class="py-3 font-bold text-slate-900">{{ err.account_code || '-' }}</td>
                  <td class="py-3 text-slate-800">{{ err.account_name }}</td>
                  <td class="py-3 text-right font-bold text-slate-900">{{ formatRupiah(err.initial_budget) }}</td>
                  <td class="py-3 text-rose-700 font-bold">
                    {{ err.error_message }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="p-8 rounded-2xl bg-emerald-50 border border-emerald-200 text-center text-xs text-emerald-800 flex items-center justify-center gap-2">
            <CheckCircle2 class="w-5 h-5 text-emerald-600" />
            <span class="font-bold">Selamat! Tidak ada baris bermasalah dalam batch import ini. Seluruh data valid dan siap dicommit.</span>
          </div>
        </div>

      </div>

    </div>

    <!-- Step 6: Confirmation Modal Dialog -->
    <div v-if="isCommitModalOpen" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl border border-slate-200 space-y-4">
        <div class="flex items-center gap-3">
          <div class="p-3 bg-emerald-100 text-emerald-700 rounded-2xl">
            <Database class="w-6 h-6" />
          </div>
          <div>
            <h3 class="text-base font-bold text-slate-900">Konfirmasi Commit Pagu Aktif</h3>
            <p class="text-xs text-slate-500">{{ history.import_batch_id }}</p>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-2 text-xs">
          <div class="flex justify-between">
            <span class="text-slate-500">Nama Berkas:</span>
            <span class="font-bold text-slate-900">{{ history.filename }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Pengunggah:</span>
            <span class="font-bold text-slate-900">{{ history.user?.name }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Total Pagu Siap Masuk:</span>
            <span class="font-bold text-emerald-800">{{ summaryCards?.valid_rows || history.valid_rows }} Pos Anggaran</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Versi Target:</span>
            <span class="font-bold text-sky-800">{{ activeVersion?.revision_no || 'Rev 02' }}</span>
          </div>
          <div class="flex justify-between border-t border-slate-200 pt-1.5">
            <span class="text-slate-500">Ekstraksi Master Baru:</span>
            <span class="font-bold text-sky-700">Dibuat Otomatis ke Master</span>
          </div>
        </div>

        <p class="text-xs text-slate-600 leading-relaxed">
          Tindakan ini akan menginjeksi pos pagu anggaran aktif dan menyinkronkan master data baru ke basis data SIKARA, serta dicatat permanen pada Log Audit Trail.
        </p>

        <div class="flex items-center justify-end gap-2 pt-2">
          <button 
            type="button" 
            @click="isCommitModalOpen = false" 
            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition"
          >
            Batal
          </button>
          <button 
            type="button" 
            @click="commitImport" 
            :disabled="formCommit.processing" 
            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl text-xs transition shadow-md shadow-emerald-600/20 disabled:opacity-50"
          >
            Ya, Commit ke Pagu Aktif
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
