<script setup>
import { ref } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import { 
  Calendar, 
  Coins, 
  Layers, 
  Plus, 
  Edit3, 
  CheckCircle2, 
  XCircle, 
  X, 
  Clock, 
  AlertTriangle, 
  ShieldCheck, 
  FileText, 
  ExternalLink,
  Lock,
  Sparkles,
  Info,
  Trash2
} from 'lucide-vue-next';

const props = defineProps({
  fiscalYears: Array,
  fundingSources: Array,
  budgetVersions: Array,
  activeFiscalYear: Object,
  activeFundingSource: Object,
  activeBudgetVersion: Object,
  canManage: Boolean,
  activeTab: String,
  selectedFyId: Number,
  selectedFundId: Number,
});

const currentTab = ref(props.activeTab || 'fiscal-years');
const selectedFiscalYearId = ref(props.selectedFyId || props.fiscalYears[0]?.id || '');
const selectedFundingSourceId = ref(props.selectedFundId || props.fundingSources[0]?.id || '');

const switchTab = (tab) => {
  currentTab.value = tab;
  router.get('/master/fiscal-years', { 
    tab, 
    fiscal_year_id: selectedFiscalYearId.value || undefined,
    funding_source_id: selectedFundingSourceId.value || undefined,
  }, { preserveState: true, preserveScroll: true });
};

const filterVersionQuery = () => {
  router.get('/master/fiscal-years', { 
    tab: 'budget-versions', 
    fiscal_year_id: selectedFiscalYearId.value || undefined,
    funding_source_id: selectedFundingSourceId.value || undefined,
  }, { preserveState: true, preserveScroll: true });
};

// ==========================================
// 1. MODAL TAHUN ANGGARAN
// ==========================================
const isFyModalOpen = ref(false);
const editingFy = ref(null);
const fyForm = useForm({
  year: '',
  start_date: '',
  end_date: '',
  status: 'ACTIVE',
});

const openCreateFyModal = () => {
  editingFy.value = null;
  const nextYear = new Date().getFullYear();
  fyForm.year = String(nextYear);
  fyForm.start_date = `${nextYear}-01-01`;
  fyForm.end_date = `${nextYear}-12-31`;
  fyForm.status = 'ACTIVE';
  isFyModalOpen.value = true;
};

const openEditFyModal = (fy) => {
  editingFy.value = fy;
  fyForm.year = fy.year;
  fyForm.start_date = fy.start_date ? fy.start_date.split('T')[0] : '';
  fyForm.end_date = fy.end_date ? fy.end_date.split('T')[0] : '';
  fyForm.status = fy.status;
  isFyModalOpen.value = true;
};

const closeFyModal = () => {
  isFyModalOpen.value = false;
  editingFy.value = null;
  fyForm.reset();
};

const submitFyForm = () => {
  if (editingFy.value) {
    fyForm.put(`/master/fiscal-years/${editingFy.value.id}`, {
      onSuccess: () => closeFyModal(),
    });
  } else {
    fyForm.post('/master/fiscal-years', {
      onSuccess: () => closeFyModal(),
    });
  }
};

const setFiscalYearActive = (fy) => {
  if (confirm(`Jadikan Tahun Anggaran ${fy.year} sebagai tahun aktif sistem? Seluruh transaksi baru akan diarahkan ke tahun ini.`)) {
    router.post(`/master/fiscal-years/${fy.id}/set-active`);
  }
};

// ==========================================
// 2. SUMBER DANA ACTIONS
// ==========================================
const toggleFundingSource = (fs) => {
  if (fs.code === 'RM' && fs.is_active) {
    if (!confirm('Peringatan: Rupiah Murni (RM) adalah sumber dana MVP aktif utama. Yakin ingin menonaktifkan?')) {
      return;
    }
  }
  router.post(`/master/funding-sources/${fs.id}/toggle-active`, {}, { preserveScroll: true });
};

// ==========================================
// 3. MODAL VERSI PAGU
// ==========================================
const isVersionModalOpen = ref(false);
const editingVersion = ref(null);
const versionForm = useForm({
  fiscal_year_id: '',
  funding_source_id: '',
  revision_no: 'Rev 00',
  version_label: '',
  effective_at: '',
  source_reference: '',
  notes: '',
  status: 'DRAFT',
});

const openCreateVersionModal = () => {
  editingVersion.value = null;
  versionForm.reset();
  versionForm.fiscal_year_id = selectedFiscalYearId.value || props.activeFiscalYear?.id || props.fiscalYears[0]?.id;
  versionForm.funding_source_id = selectedFundingSourceId.value || props.activeFundingSource?.id || props.fundingSources[0]?.id;
  versionForm.revision_no = 'Rev 03';
  versionForm.version_label = 'Revisi DIPA 2026';
  versionForm.effective_at = new Date().toISOString().split('T')[0];
  versionForm.status = 'DRAFT';
  isVersionModalOpen.value = true;
};

const openEditVersionModal = (bv) => {
  editingVersion.value = bv;
  versionForm.fiscal_year_id = bv.fiscal_year_id;
  versionForm.funding_source_id = bv.funding_source_id;
  versionForm.revision_no = bv.revision_no;
  versionForm.version_label = bv.version_label || '';
  versionForm.effective_at = bv.effective_at ? bv.effective_at.split('T')[0] : '';
  versionForm.source_reference = bv.source_reference || '';
  versionForm.notes = bv.notes || '';
  versionForm.status = bv.status;
  isVersionModalOpen.value = true;
};

const closeVersionModal = () => {
  isVersionModalOpen.value = false;
  editingVersion.value = null;
  versionForm.reset();
};

const submitVersionForm = () => {
  if (editingVersion.value) {
    versionForm.put(`/master/budget-versions/${editingVersion.value.id}`, {
      onSuccess: () => closeVersionModal(),
    });
  } else {
    versionForm.post('/master/budget-versions', {
      onSuccess: () => closeVersionModal(),
    });
  }
};

const activateBudgetVersion = (bv) => {
  let msg = `Aktifkan Versi Pagu "${bv.revision_no}" (${bv.funding_source?.code})?\nVersi aktif saat ini akan otomatis diarsipkan (ARCHIVED) secara transaksional di database.`;
  if (bv.is_conflict) {
    msg = `PERINGATAN KONFLIK REVISI:\n${bv.conflict_message}\n\nApakah Anda tetap ingin mengaktifkan versi ini secara definitif?`;
  }
  if (confirm(msg)) {
    router.post(`/master/budget-versions/${bv.id}/set-active`);
  }
};

const deleteBudgetVersion = (bv) => {
  if (confirm(`Yakin ingin menghapus draf versi "${bv.revision_no}"?`)) {
    router.delete(`/master/budget-versions/${bv.id}`);
  }
};

const formatRupiah = (val) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0);
};
</script>

<template>
  <AppLayout title="Tahun &amp; Versi Pagu">
    <div class="space-y-6 font-sans max-w-7xl mx-auto">
      
      <!-- Top Header -->
      <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <span class="px-2.5 py-0.5 bg-sky-100 text-sky-800 text-[10px] font-black rounded-lg uppercase tracking-wider">
              Administrasi Sistem
            </span>
            <span class="text-xs text-slate-400 font-semibold">&bull; Konfigurasi Siklus Anggaran</span>
          </div>
          <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight mt-1 flex items-center gap-2">
            <Calendar class="w-6 h-6 text-sky-600" />
            Tahun &amp; Versi Pagu
          </h1>
          <p class="text-xs text-slate-500 mt-0.5">
            Kontrol referensi tahun pembukuan, master sumber dana, dan metadata versi pagu aktif untuk seluruh sistem.
          </p>
        </div>

        <div v-if="canManage" class="flex items-center gap-3">
          <button 
            v-if="currentTab === 'fiscal-years'"
            @click="openCreateFyModal" 
            class="px-4 py-2.5 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-md shadow-sky-600/20"
          >
            <Plus class="w-4 h-4" /> Tambah Tahun Anggaran
          </button>

          <button 
            v-if="currentTab === 'budget-versions'"
            @click="openCreateVersionModal" 
            class="px-4 py-2.5 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-md shadow-sky-600/20"
          >
            <Plus class="w-4 h-4" /> Tambah Versi Pagu
          </button>
        </div>
      </div>

      <!-- Smart Active Context Banner (3 Badges) -->
      <div class="bg-gradient-to-r from-sky-900 to-indigo-950 p-5 rounded-3xl text-white shadow-lg flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="space-y-1">
          <span class="text-[10px] font-bold text-sky-300 uppercase tracking-widest flex items-center gap-1.5">
            <Sparkles class="w-3.5 h-3.5 text-amber-400" /> Konteks Sistem Aktif (Smart Default)
          </span>
          <p class="text-xs text-sky-100/90 leading-relaxed">
            Digunakan otomatis pada Dashboard, Master Pagu, Pencatatan Transaksi PTK, Pelaporan LRA, dan EWS.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 text-xs">
          <!-- Active TA -->
          <div class="bg-white/10 backdrop-blur-md px-3.5 py-2 rounded-2xl border border-white/15 flex items-center gap-2">
            <Calendar class="w-4 h-4 text-sky-300" />
            <div>
              <div class="text-[9px] text-sky-300 font-semibold uppercase">Tahun Anggaran</div>
              <div class="font-black text-white text-sm font-sans">{{ activeFiscalYear?.year || '2026' }}</div>
            </div>
          </div>

          <!-- Active Fund Source -->
          <div class="bg-white/10 backdrop-blur-md px-3.5 py-2 rounded-2xl border border-white/15 flex items-center gap-2">
            <Coins class="w-4 h-4 text-amber-300" />
            <div>
              <div class="text-[9px] text-amber-300 font-semibold uppercase">Sumber Dana Utama</div>
              <div class="font-black text-white text-sm font-sans">{{ activeFundingSource?.code || 'RM' }} (Rupiah Murni)</div>
            </div>
          </div>

          <!-- Active Version -->
          <div class="bg-white/10 backdrop-blur-md px-3.5 py-2 rounded-2xl border border-white/15 flex items-center gap-2">
            <Layers class="w-4 h-4 text-emerald-300" />
            <div>
              <div class="text-[9px] text-emerald-300 font-semibold uppercase">Versi Pagu Aktif</div>
              <div class="font-black text-white text-sm font-sans">{{ activeBudgetVersion ? `${activeBudgetVersion.funding_source?.code} ${activeBudgetVersion.revision_no}` : 'RM Rev 02' }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Navigation Tabs (3 Sections) -->
      <div class="flex items-center gap-2 border-b border-slate-200">
        <button 
          @click="switchTab('fiscal-years')"
          :class="[
            'px-5 py-3 text-xs font-bold flex items-center gap-2 border-b-2 transition -mb-px',
            currentTab === 'fiscal-years' 
              ? 'border-sky-600 text-sky-700 bg-white rounded-t-xl' 
              : 'border-transparent text-slate-500 hover:text-slate-900 hover:border-slate-300'
          ]"
        >
          <Calendar class="w-4 h-4" />
          <span>Tahun Anggaran</span>
          <span class="px-2 py-0.5 bg-slate-100 text-slate-700 rounded-full text-[10px] font-black">
            {{ fiscalYears.length }}
          </span>
        </button>

        <button 
          @click="switchTab('funding-sources')"
          :class="[
            'px-5 py-3 text-xs font-bold flex items-center gap-2 border-b-2 transition -mb-px',
            currentTab === 'funding-sources' 
              ? 'border-sky-600 text-sky-700 bg-white rounded-t-xl' 
              : 'border-transparent text-slate-500 hover:text-slate-900 hover:border-slate-300'
          ]"
        >
          <Coins class="w-4 h-4" />
          <span>Sumber Dana</span>
          <span class="px-2 py-0.5 bg-slate-100 text-slate-700 rounded-full text-[10px] font-black">
            {{ fundingSources.length }}
          </span>
        </button>

        <button 
          @click="switchTab('budget-versions')"
          :class="[
            'px-5 py-3 text-xs font-bold flex items-center gap-2 border-b-2 transition -mb-px',
            currentTab === 'budget-versions' 
              ? 'border-sky-600 text-sky-700 bg-white rounded-t-xl' 
              : 'border-transparent text-slate-500 hover:text-slate-900 hover:border-slate-300'
          ]"
        >
          <Layers class="w-4 h-4" />
          <span>Versi &amp; Revisi Pagu</span>
          <span class="px-2 py-0.5 bg-slate-100 text-slate-700 rounded-full text-[10px] font-black">
            {{ budgetVersions.length }}
          </span>
        </button>
      </div>

      <!-- ========================================== -->
      <!-- SECTION 1: TAHUN ANGGARAN TABLE -->
      <!-- ========================================== -->
      <div v-if="currentTab === 'fiscal-years'" class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-50 text-slate-700 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200">
                <th class="py-3.5 px-6 whitespace-nowrap">Tahun</th>
                <th class="py-3.5 px-6 whitespace-nowrap">Tanggal Mulai</th>
                <th class="py-3.5 px-6 whitespace-nowrap">Tanggal Selesai</th>
                <th class="py-3.5 px-6 text-center whitespace-nowrap">Status</th>
                <th class="py-3.5 px-6 whitespace-nowrap">Versi Aktif Terpasang</th>
                <th class="py-3.5 px-6 whitespace-nowrap">Last Updated</th>
                <th v-if="canManage" class="py-3.5 px-6 text-center whitespace-nowrap">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-900">
              <tr v-for="fy in fiscalYears" :key="fy.id" class="hover:bg-slate-50/80 transition">
                <td class="py-4 px-6 whitespace-nowrap">
                  <span class="font-sans font-black text-sm text-slate-900">{{ fy.year }}</span>
                </td>
                <td class="py-4 px-6 font-sans text-slate-600 whitespace-nowrap">
                  {{ new Date(fy.start_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                </td>
                <td class="py-4 px-6 font-sans text-slate-600 whitespace-nowrap">
                  {{ new Date(fy.end_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                </td>
                <td class="py-4 px-6 text-center whitespace-nowrap">
                  <span :class="[
                    'px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border',
                    fy.status === 'ACTIVE' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200'
                  ]">
                    {{ fy.status }}
                  </span>
                </td>
                <td class="py-4 px-6 whitespace-nowrap">
                  <span class="px-2.5 py-1 bg-sky-50 text-sky-800 border border-sky-200 rounded-lg text-xs font-bold">
                    {{ fy.active_version_label }}
                  </span>
                </td>
                <td class="py-4 px-6 font-sans text-slate-500 text-[11px] whitespace-nowrap">
                  {{ new Date(fy.updated_at).toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: '2-digit' }) }}
                </td>
                <td v-if="canManage" class="py-4 px-6 text-center whitespace-nowrap">
                  <div class="flex items-center justify-center gap-2">
                    <button 
                      v-if="fy.status !== 'ACTIVE'"
                      @click="setFiscalYearActive(fy)" 
                      class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-lg text-[10px] font-bold transition flex items-center gap-1"
                      title="Tetapkan sebagai Tahun Aktif"
                    >
                      <CheckCircle2 class="w-3.5 h-3.5" /> Aktifkan
                    </button>
                    <button 
                      @click="openEditFyModal(fy)" 
                      class="p-1.5 text-slate-600 hover:text-sky-600 hover:bg-sky-50 rounded-lg transition"
                      title="Edit Periode"
                    >
                      <Edit3 class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ========================================== -->
      <!-- SECTION 2: SUMBER DANA TABLE -->
      <!-- ========================================== -->
      <div v-if="currentTab === 'funding-sources'" class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-50 text-slate-700 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200">
                <th class="py-3.5 px-6 whitespace-nowrap">Code</th>
                <th class="py-3.5 px-6 whitespace-nowrap">Name</th>
                <th class="py-3.5 px-6">Description</th>
                <th class="py-3.5 px-6 text-center whitespace-nowrap">MVP Enabled</th>
                <th class="py-3.5 px-6 text-center whitespace-nowrap">Status</th>
                <th class="py-3.5 px-6 whitespace-nowrap">External System</th>
                <th class="py-3.5 px-6 whitespace-nowrap">Last Updated</th>
                <th v-if="canManage" class="py-3.5 px-6 text-center whitespace-nowrap">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-900">
              <tr v-for="fs in fundingSources" :key="fs.id" class="hover:bg-slate-50/80 transition">
                <td class="py-4 px-6 font-sans font-black text-sky-700 whitespace-nowrap text-sm">
                  {{ fs.code }}
                </td>
                <td class="py-4 px-6 font-bold text-slate-900 whitespace-nowrap">
                  {{ fs.name }}
                </td>
                <td class="py-4 px-6 text-slate-600 max-w-xs">
                  {{ fs.description || '-' }}
                </td>
                <td class="py-4 px-6 text-center whitespace-nowrap">
                  <span :class="[
                    'px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border',
                    fs.is_mvp_enabled ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200'
                  ]">
                    {{ fs.is_mvp_enabled ? 'TRUE (ACTIVE MVP)' : 'FALSE (PREPARED)' }}
                  </span>
                </td>
                <td class="py-4 px-6 text-center whitespace-nowrap">
                  <span :class="[
                    'px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border',
                    fs.is_active ? 'bg-sky-50 text-sky-700 border-sky-200' : 'bg-slate-100 text-slate-500 border-slate-200'
                  ]">
                    {{ fs.status || (fs.is_active ? 'ACTIVE' : 'INACTIVE') }}
                  </span>
                </td>
                <td class="py-4 px-6 whitespace-nowrap">
                  <span class="px-2 py-0.5 bg-slate-100 text-slate-700 rounded-md font-mono text-[10px] font-bold">
                    {{ fs.external_system || 'MANUAL' }}
                  </span>
                </td>
                <td class="py-4 px-6 font-sans text-slate-500 text-[11px] whitespace-nowrap">
                  {{ new Date(fs.updated_at).toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: '2-digit' }) }}
                </td>
                <td v-if="canManage" class="py-4 px-6 text-center whitespace-nowrap">
                  <button 
                    @click="toggleFundingSource(fs)"
                    :class="[
                      'px-2.5 py-1 rounded-full text-[10px] font-extrabold border transition',
                      fs.is_active ? 'bg-slate-100 text-slate-600 hover:bg-slate-200' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                    ]"
                  >
                    {{ fs.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ========================================== -->
      <!-- SECTION 3: BUDGET VERSIONS TABLE -->
      <!-- ========================================== -->
      <div v-if="currentTab === 'budget-versions'" class="space-y-4">
        
        <!-- Filter Toolbar -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-wrap items-center justify-between gap-3">
          <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2">
              <label class="text-xs font-bold text-slate-600">Tahun Anggaran:</label>
              <select 
                v-model="selectedFiscalYearId" 
                @change="filterVersionQuery"
                class="px-3 py-1.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 font-semibold focus:ring-2 focus:ring-sky-500"
              >
                <option v-for="fy in fiscalYears" :key="fy.id" :value="fy.id">TA {{ fy.year }} ({{ fy.status }})</option>
              </select>
            </div>

            <div class="flex items-center gap-2">
              <label class="text-xs font-bold text-slate-600">Sumber Dana:</label>
              <select 
                v-model="selectedFundingSourceId" 
                @change="filterVersionQuery"
                class="px-3 py-1.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 font-semibold focus:ring-2 focus:ring-sky-500"
              >
                <option v-for="fs in fundingSources" :key="fs.id" :value="fs.id">{{ fs.code }} &mdash; {{ fs.name }}</option>
              </select>
            </div>
          </div>

          <div class="text-xs text-slate-500 font-medium">
            Menampilkan <span class="font-bold text-slate-900">{{ budgetVersions.length }}</span> versi DIPA
          </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
              <thead>
                <tr class="bg-slate-50 text-slate-700 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200">
                  <th class="py-3.5 px-6 whitespace-nowrap">Revision</th>
                  <th class="py-3.5 px-6 whitespace-nowrap">Source Fund</th>
                  <th class="py-3.5 px-6 whitespace-nowrap">Status</th>
                  <th class="py-3.5 px-6 whitespace-nowrap">Effective Date</th>
                  <th class="py-3.5 px-6 whitespace-nowrap">Source File / Reference</th>
                  <th class="py-3.5 px-6 whitespace-nowrap">Import Batch</th>
                  <th class="py-3.5 px-6 whitespace-nowrap">Created By</th>
                  <th class="py-3.5 px-6 whitespace-nowrap">Created At</th>
                  <th v-if="canManage" class="py-3.5 px-6 text-center whitespace-nowrap">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 text-slate-900">
                <tr v-for="bv in budgetVersions" :key="bv.id" class="hover:bg-slate-50/80 transition">
                  <td class="py-4 px-6 whitespace-nowrap">
                    <div class="font-sans font-black text-sm text-sky-800">{{ bv.revision_no }}</div>
                    <div class="text-[11px] text-slate-500 font-medium">{{ bv.version_label || '-' }}</div>
                  </td>
                  <td class="py-4 px-6 whitespace-nowrap">
                    <span class="px-2 py-0.5 bg-amber-50 text-amber-800 border border-amber-200 rounded-md font-bold text-[10px]">
                      {{ bv.funding_source?.code }} &bull; {{ bv.funding_source?.name }}
                    </span>
                  </td>
                  <td class="py-4 px-6 whitespace-nowrap">
                    <div class="flex items-center gap-1.5">
                      <span :class="[
                        'px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border',
                        bv.status === 'ACTIVE' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : (bv.status === 'DRAFT' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-slate-100 text-slate-500 border-slate-200')
                      ]">
                        {{ bv.status }}
                      </span>

                      <!-- Conflict Warning Badge -->
                      <span 
                        v-if="bv.is_conflict" 
                        class="px-2 py-0.5 bg-rose-100 text-rose-800 border border-rose-300 rounded-md text-[9px] font-black flex items-center gap-1 cursor-help"
                        :title="bv.conflict_message"
                      >
                        <AlertTriangle class="w-3 h-3 text-rose-600" /> CONFLICT
                      </span>
                    </div>
                  </td>
                  <td class="py-4 px-6 font-sans text-slate-600 whitespace-nowrap">
                    {{ bv.effective_at ? new Date(bv.effective_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-' }}
                  </td>
                  <td class="py-4 px-6 font-mono text-[11px] text-slate-700 max-w-xs truncate">
                    {{ bv.source_reference || bv.source_filename || 'DIPA Standar Kementerian' }}
                  </td>
                  <td class="py-4 px-6 whitespace-nowrap">
                    <Link 
                      v-if="bv.import_history_id"
                      :href="`/budgets-import`" 
                      class="text-sky-600 hover:text-sky-800 font-bold inline-flex items-center gap-1"
                    >
                      <span>Batch #{{ bv.import_history_id }}</span>
                      <ExternalLink class="w-3 h-3" />
                    </Link>
                    <span v-else class="text-slate-400 font-medium">Inisialisasi Sistem</span>
                  </td>
                  <td class="py-4 px-6 text-slate-700 font-medium whitespace-nowrap">
                    {{ bv.creator?.name || 'Administrator' }}
                  </td>
                  <td class="py-4 px-6 font-sans text-slate-500 text-[11px] whitespace-nowrap">
                    {{ new Date(bv.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: '2-digit' }) }}
                  </td>
                  <td v-if="canManage" class="py-4 px-6 text-center whitespace-nowrap">
                    <div class="flex items-center justify-center gap-1.5">
                      <button 
                        v-if="bv.status !== 'ACTIVE'"
                        @click="activateBudgetVersion(bv)" 
                        class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-lg text-[10px] font-bold transition flex items-center gap-1 shadow-sm"
                        title="Aktifkan Versi Pagu Ini"
                      >
                        <CheckCircle2 class="w-3.5 h-3.5" /> Aktifkan
                      </button>

                      <button 
                        @click="openEditVersionModal(bv)" 
                        class="p-1.5 text-slate-600 hover:text-sky-600 hover:bg-sky-50 rounded-lg transition"
                        title="Edit Metadata Versi"
                      >
                        <Edit3 class="w-4 h-4" />
                      </button>

                      <button 
                        v-if="bv.can_delete"
                        @click="deleteBudgetVersion(bv)" 
                        class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition"
                        title="Hapus Draf Versi"
                      >
                        <Trash2 class="w-4 h-4" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>

    </div>

    <!-- ========================================== -->
    <!-- MODAL 1: TAHUN ANGGARAN -->
    <!-- ========================================== -->
    <div v-if="isFyModalOpen" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
          <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
            <Calendar class="w-4 h-4 text-sky-600" />
            {{ editingFy ? 'Edit Tahun Anggaran' : 'Tambah Tahun Anggaran Baru' }}
          </h3>
          <button @click="closeFyModal" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
            <X class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="submitFyForm" class="space-y-3.5 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Tahun Anggaran *</label>
            <input 
              v-model="fyForm.year" 
              type="text" 
              placeholder="Contoh: 2027" 
              class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-mono text-sm font-black text-slate-900 focus:ring-2 focus:ring-sky-500" 
              required 
            />
            <span v-if="fyForm.errors.year" class="text-rose-600 text-[10px]">{{ fyForm.errors.year }}</span>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Tanggal Mulai *</label>
              <input 
                v-model="fyForm.start_date" 
                type="date" 
                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-sans text-slate-900 focus:ring-2 focus:ring-sky-500" 
                required 
              />
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Tanggal Selesai *</label>
              <input 
                v-model="fyForm.end_date" 
                type="date" 
                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-sans text-slate-900 focus:ring-2 focus:ring-sky-500" 
                required 
              />
            </div>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Status Tahun *</label>
            <select 
              v-model="fyForm.status" 
              class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-bold text-slate-900 focus:ring-2 focus:ring-sky-500"
            >
              <option value="ACTIVE">ACTIVE (Tahun Aktif Berjalan)</option>
              <option value="PLANNING">PLANNING (Perencanaan Tahun Depan)</option>
              <option value="CLOSED">CLOSED (Tutup Buku / Diarsipkan)</option>
            </select>
          </div>

          <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
            <button 
              type="button" 
              @click="closeFyModal" 
              class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition"
            >
              Batal
            </button>
            <button 
              type="submit" 
              :disabled="fyForm.processing" 
              class="px-5 py-2 bg-sky-600 hover:bg-sky-500 text-white font-bold rounded-xl transition shadow-md shadow-sky-600/20 disabled:opacity-50"
            >
              {{ fyForm.processing ? 'Menyimpan...' : 'Simpan Tahun' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL 2: VERSI PAGU METADATA -->
    <!-- ========================================== -->
    <div v-if="isVersionModalOpen" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
          <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
            <Layers class="w-4 h-4 text-sky-600" />
            {{ editingVersion ? 'Edit Metadata Versi Pagu' : 'Tambah Versi Pagu Baru' }}
          </h3>
          <button @click="closeVersionModal" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
            <X class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="submitVersionForm" class="space-y-3.5 text-xs">
          <div class="grid grid-cols-2 gap-3" v-if="!editingVersion">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Tahun Anggaran *</label>
              <select 
                v-model="versionForm.fiscal_year_id" 
                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 font-bold focus:ring-2 focus:ring-sky-500"
                required
              >
                <option v-for="fy in fiscalYears" :key="fy.id" :value="fy.id">TA {{ fy.year }}</option>
              </select>
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Sumber Dana *</label>
              <select 
                v-model="versionForm.funding_source_id" 
                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 font-bold focus:ring-2 focus:ring-sky-500"
                required
              >
                <option v-for="fs in fundingSources" :key="fs.id" :value="fs.id">{{ fs.code }} &mdash; {{ fs.name }}</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Nomor Revisi *</label>
              <input 
                v-model="versionForm.revision_no" 
                type="text" 
                placeholder="Contoh: Rev 03" 
                :disabled="!!editingVersion"
                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-bold text-slate-900 focus:ring-2 focus:ring-sky-500 disabled:bg-slate-200" 
                required 
              />
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Tanggal Efektif</label>
              <input 
                v-model="versionForm.effective_at" 
                type="date" 
                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 focus:ring-2 focus:ring-sky-500 font-sans" 
              />
            </div>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Label / Nama Versi</label>
            <input 
              v-model="versionForm.version_label" 
              type="text" 
              placeholder="Contoh: DIPA Revisi 02 (Penambahan Alokasi Praktikum)" 
              class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 focus:ring-2 focus:ring-sky-500 font-medium" 
            />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Referensi Dokumen / SK DIPA</label>
            <input 
              v-model="versionForm.source_reference" 
              type="text" 
              placeholder="Contoh: SP DIPA-023.17.2.693420/2026 Tgl 20 Agt 2026" 
              class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 font-mono text-[11px] focus:ring-2 focus:ring-sky-500" 
            />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Catatan Perubahan</label>
            <textarea 
              v-model="versionForm.notes" 
              rows="2" 
              placeholder="Keterangan pergeseran anggaran..." 
              class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 focus:ring-2 focus:ring-sky-500"
            ></textarea>
          </div>

          <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
            <button 
              type="button" 
              @click="closeVersionModal" 
              class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition"
            >
              Batal
            </button>
            <button 
              type="submit" 
              :disabled="versionForm.processing" 
              class="px-5 py-2 bg-sky-600 hover:bg-sky-500 text-white font-bold rounded-xl transition shadow-md shadow-sky-600/20 disabled:opacity-50"
            >
              {{ versionForm.processing ? 'Menyimpan...' : 'Simpan Versi' }}
            </button>
          </div>
        </form>
      </div>
    </div>

  </AppLayout>
</template>
