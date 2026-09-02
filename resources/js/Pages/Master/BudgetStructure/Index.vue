<script setup>
import { ref, computed } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import {
  BookOpen,
  Activity,
  Layers,
  GitBranch,
  Package,
  Box,
  Wallet,
  CreditCard,
  Search,
  Filter,
  Edit3,
  X,
  CheckCircle2,
  AlertTriangle,
  ChevronRight,
  BadgeCheck,
  CircleOff,
  Archive,
} from 'lucide-vue-next';

const props = defineProps({
  rows: Object,
  activeTab: String,
  canManage: Boolean,
  tabCounts: Object,
  availableYears: Array,
  filters: Object,
  parentCol: String,
});

// ==========================================
// TAB CONFIG
// ==========================================
const tabs = [
  { key: 'program',       label: 'Program',       icon: BookOpen,    color: 'sky' },
  { key: 'activity',      label: 'Kegiatan',      icon: Activity,    color: 'violet' },
  { key: 'kro',           label: 'KRO',           icon: Layers,      color: 'indigo' },
  { key: 'ro',            label: 'RO',            icon: GitBranch,   color: 'blue' },
  { key: 'component',     label: 'Komponen',      icon: Package,     color: 'amber' },
  { key: 'subcomponent',  label: 'Subkomponen',   icon: Box,         color: 'orange' },
  { key: 'account',       label: 'Akun',          icon: Wallet,      color: 'emerald' },
  { key: 'subaccount',    label: 'Subakun',       icon: CreditCard,  color: 'teal' },
];

const currentTab = ref(props.activeTab || 'program');

// ==========================================
// FILTERS
// ==========================================
const searchLocal = ref(props.filters?.search || '');
const filterYear = ref(props.filters?.year || '');
const filterSource = ref(props.filters?.source || '');
const filterStatus = ref(props.filters?.status || '');

const applyFilters = () => {
  router.get('/master/budget-structure', {
    tab: currentTab.value,
    search: searchLocal.value || undefined,
    year: filterYear.value || undefined,
    source: filterSource.value || undefined,
    status: filterStatus.value || undefined,
  }, { preserveState: true, preserveScroll: true });
};

const switchTab = (tab) => {
  currentTab.value = tab;
  searchLocal.value = '';
  filterYear.value = '';
  filterSource.value = '';
  filterStatus.value = '';
  router.get('/master/budget-structure', { tab }, { preserveState: true, preserveScroll: true });
};

const clearFilters = () => {
  searchLocal.value = '';
  filterYear.value = '';
  filterSource.value = '';
  filterStatus.value = '';
  applyFilters();
};

const hasFilters = computed(() =>
  searchLocal.value || filterYear.value || filterSource.value || filterStatus.value
);

// ==========================================
// SOURCE TYPE HELPERS
// ==========================================
const sourceTypeLabel = (src) => {
  const map = {
    OFFICIAL_IMPORT: 'Official Import',
    OFFICIAL_DOCUMENT: 'Official Document',
    INTERNAL: 'Internal',
    NEEDS_VALIDATION: 'Needs Validation',
  };
  return map[src] || src;
};

const sourceTypeBadge = (src) => {
  const map = {
    OFFICIAL_IMPORT: 'bg-sky-50 text-sky-700 border-sky-200',
    OFFICIAL_DOCUMENT: 'bg-blue-50 text-blue-700 border-blue-200',
    INTERNAL: 'bg-violet-50 text-violet-700 border-violet-200',
    NEEDS_VALIDATION: 'bg-amber-50 text-amber-700 border-amber-200',
  };
  return map[src] || 'bg-slate-50 text-slate-600 border-slate-200';
};

const statusBadge = (status) => {
  const map = {
    ACTIVE: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    INACTIVE: 'bg-slate-100 text-slate-500 border-slate-200',
    ARCHIVED: 'bg-rose-50 text-rose-700 border-rose-200',
  };
  return map[status] || 'bg-slate-50 text-slate-500 border-slate-200';
};

const statusIcon = (status) => {
  const map = { ACTIVE: CheckCircle2, INACTIVE: CircleOff, ARCHIVED: Archive };
  return map[status] || CircleOff;
};

// ==========================================
// COLUMN LABELS per TAB
// ==========================================
const tabColumns = {
  program:      { parent: null,               yearLabel: 'Tahun' },
  activity:     { parent: 'Program',          yearLabel: 'Tahun' },
  kro:          { parent: 'Kegiatan',         yearLabel: 'Tahun' },
  ro:           { parent: 'KRO',              yearLabel: 'Tahun' },
  component:    { parent: 'RO',               yearLabel: 'Tahun' },
  subcomponent: { parent: 'Komponen',         yearLabel: 'Tahun' },
  account:      { parent: null,               yearLabel: 'Effective Year' },
  subaccount:   { parent: 'Akun Induk',       yearLabel: 'Effective Year' },
};

const currentTabConfig = computed(() => tabColumns[currentTab.value] || tabColumns.program);

// ==========================================
// EDIT MODAL
// ==========================================
const isEditModalOpen = ref(false);
const editingRow = ref(null);

const editForm = useForm({
  name: '',
  source_type: 'OFFICIAL_IMPORT',
  status: 'ACTIVE',
});

const openEditModal = (row) => {
  editingRow.value = row;
  editForm.name = row.name;
  editForm.source_type = row.source_type || 'OFFICIAL_IMPORT';
  editForm.status = row.status || 'ACTIVE';
  isEditModalOpen.value = true;
};

const closeEditModal = () => {
  isEditModalOpen.value = false;
  editingRow.value = null;
  editForm.reset();
};

const submitEdit = () => {
  editForm.put(`/master/budget-structure/${currentTab.value}/${editingRow.value.id}`, {
    onSuccess: () => closeEditModal(),
  });
};

// ==========================================
// TOGGLE STATUS
// ==========================================
const toggleStatus = (row) => {
  const action = row.status === 'ACTIVE' ? 'menonaktifkan' : 'mengaktifkan';
  if (confirm(`Yakin ${action} kode [${row.code}] — ${row.name}?`)) {
    router.post(`/master/budget-structure/${currentTab.value}/${row.id}/toggle-status`);
  }
};

// ==========================================
// PARENT VALUE RESOLUTION
// ==========================================
const parentValue = (row) => {
  if (!props.parentCol) return '-';
  return row[props.parentCol] || '-';
};

const yearValue = (row) => {
  if (['account', 'subaccount'].includes(currentTab.value)) {
    return row.effective_year || 'Cross-Year';
  }
  return row.fiscal_year || '-';
};

// ==========================================
// TAB COLOR BADGE FOR NAV
// ==========================================
const tabNavClass = (tab) => {
  const colorMap = {
    sky: 'hover:text-sky-700 data-[active=true]:border-sky-600 data-[active=true]:text-sky-700',
    violet: 'hover:text-violet-700 data-[active=true]:border-violet-600 data-[active=true]:text-violet-700',
    indigo: 'hover:text-indigo-700 data-[active=true]:border-indigo-600 data-[active=true]:text-indigo-700',
    blue: 'hover:text-blue-700 data-[active=true]:border-blue-600 data-[active=true]:text-blue-700',
    amber: 'hover:text-amber-700 data-[active=true]:border-amber-600 data-[active=true]:text-amber-700',
    orange: 'hover:text-orange-700 data-[active=true]:border-orange-600 data-[active=true]:text-orange-700',
    emerald: 'hover:text-emerald-700 data-[active=true]:border-emerald-600 data-[active=true]:text-emerald-700',
    teal: 'hover:text-teal-700 data-[active=true]:border-teal-600 data-[active=true]:text-teal-700',
  };
  return colorMap[tab.color] || '';
};
</script>

<template>
  <AppLayout title="Master Struktur Anggaran">
    <div class="space-y-5 max-w-full">

      <!-- =================== HEADER =================== -->
      <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <span class="px-2.5 py-0.5 bg-sky-100 text-sky-800 text-[10px] font-black rounded-lg uppercase tracking-wider">
              Administrasi Sistem
            </span>
            <span class="text-xs text-slate-400 font-semibold">&bull; Nomenklatur &amp; Kode Anggaran DIPA</span>
          </div>
          <h1 class="text-xl font-black text-slate-900 tracking-tight flex items-center gap-2">
            <Layers class="w-5 h-5 text-sky-600" />
            Master Struktur Anggaran
          </h1>
          <p class="text-xs text-slate-500 mt-0.5">
            Referensi Program, Kegiatan, KRO, RO, Komponen, Subkomponen, dan Akun yang berasal dari Import Pagu DIPA.
            Data hanya dapat diubah nama &amp; status — kode resmi tidak diubah manual.
          </p>
        </div>

        <!-- Hierarchy breadcrumb -->
        <div class="hidden md:flex items-center gap-1 text-[10px] font-bold text-slate-500 flex-wrap max-w-md">
          <span class="px-2 py-0.5 bg-sky-100 text-sky-700 rounded">Program</span>
          <ChevronRight class="w-3 h-3" />
          <span class="px-2 py-0.5 bg-violet-100 text-violet-700 rounded">Kegiatan</span>
          <ChevronRight class="w-3 h-3" />
          <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded">KRO</span>
          <ChevronRight class="w-3 h-3" />
          <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded">RO</span>
          <ChevronRight class="w-3 h-3" />
          <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded">Komponen</span>
          <ChevronRight class="w-3 h-3" />
          <span class="px-2 py-0.5 bg-orange-100 text-orange-700 rounded">Subkomponen</span>
          <span class="ml-1 text-slate-300">|</span>
          <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded">Akun</span>
          <ChevronRight class="w-3 h-3" />
          <span class="px-2 py-0.5 bg-teal-100 text-teal-700 rounded">Subakun</span>
        </div>
      </div>

      <!-- =================== TAB NAVIGATION =================== -->
      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-x-auto">
        <div class="flex items-center gap-0 border-b border-slate-200 min-w-max px-2">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            @click="switchTab(tab.key)"
            :data-active="currentTab === tab.key"
            :class="[
              'flex items-center gap-1.5 px-4 py-3.5 text-[11px] font-bold border-b-2 border-transparent transition whitespace-nowrap text-slate-500 -mb-px',
              tabNavClass(tab),
              currentTab === tab.key ? '' : ''
            ]"
          >
            <component :is="tab.icon" class="w-3.5 h-3.5" />
            {{ tab.label }}
            <span class="px-1.5 py-0.5 bg-slate-100 text-slate-600 rounded-full text-[9px] font-black leading-none">
              {{ tabCounts[tab.key] || 0 }}
            </span>
          </button>
        </div>

        <!-- =================== FILTER BAR =================== -->
        <div class="flex flex-wrap items-center gap-3 px-5 py-3 border-b border-slate-100 bg-slate-50/60">
          <!-- Search -->
          <div class="relative flex-1 min-w-[200px] max-w-sm">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" />
            <input
              v-model="searchLocal"
              type="text"
              placeholder="Cari kode atau nama..."
              class="w-full pl-9 pr-3 py-1.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:ring-2 focus:ring-sky-500 font-medium"
              @keyup.enter="applyFilters"
            />
          </div>

          <!-- Year filter -->
          <select
            v-if="availableYears && availableYears.length > 0"
            v-model="filterYear"
            @change="applyFilters"
            class="px-3 py-1.5 bg-white border border-slate-300 rounded-xl text-xs font-semibold text-slate-900 focus:ring-2 focus:ring-sky-500"
          >
            <option value="">Semua Tahun</option>
            <option v-for="yr in availableYears" :key="yr" :value="yr">{{ yr }}</option>
          </select>

          <!-- Source filter -->
          <select
            v-model="filterSource"
            @change="applyFilters"
            class="px-3 py-1.5 bg-white border border-slate-300 rounded-xl text-xs font-semibold text-slate-900 focus:ring-2 focus:ring-sky-500"
          >
            <option value="">Semua Sumber</option>
            <option value="OFFICIAL_IMPORT">Official Import</option>
            <option value="OFFICIAL_DOCUMENT">Official Document</option>
            <option value="INTERNAL">Internal</option>
            <option value="NEEDS_VALIDATION">Needs Validation</option>
          </select>

          <!-- Status filter -->
          <select
            v-model="filterStatus"
            @change="applyFilters"
            class="px-3 py-1.5 bg-white border border-slate-300 rounded-xl text-xs font-semibold text-slate-900 focus:ring-2 focus:ring-sky-500"
          >
            <option value="">Semua Status</option>
            <option value="ACTIVE">ACTIVE</option>
            <option value="INACTIVE">INACTIVE</option>
            <option value="ARCHIVED">ARCHIVED</option>
          </select>

          <button @click="applyFilters" class="px-3 py-1.5 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5">
            <Filter class="w-3.5 h-3.5" /> Terapkan
          </button>

          <button v-if="hasFilters" @click="clearFilters" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition flex items-center gap-1.5">
            <X class="w-3.5 h-3.5" /> Hapus Filter
          </button>

          <div class="ml-auto text-[11px] text-slate-500 font-medium">
            <span class="font-bold text-slate-900">{{ rows.total }}</span> entri
          </div>
        </div>

        <!-- =================== TABLE =================== -->
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200">
                <th class="py-3.5 px-5 whitespace-nowrap">Code</th>
                <th class="py-3.5 px-5">Name (Official)</th>
                <th v-if="currentTabConfig.parent" class="py-3.5 px-5 whitespace-nowrap">{{ currentTabConfig.parent }}</th>
                <th class="py-3.5 px-5 text-center whitespace-nowrap">{{ currentTabConfig.yearLabel }}</th>
                <th class="py-3.5 px-5 whitespace-nowrap">Source</th>
                <th class="py-3.5 px-5 text-center whitespace-nowrap">Status</th>
                <th class="py-3.5 px-5 text-center whitespace-nowrap">Digunakan</th>
                <th class="py-3.5 px-5 whitespace-nowrap">Last Updated</th>
                <th v-if="canManage" class="py-3.5 px-5 text-center whitespace-nowrap">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="row in rows.data"
                :key="row.id"
                class="hover:bg-slate-50/80 transition"
                :class="{ 'opacity-60': row.status === 'INACTIVE' }"
              >
                <!-- Code -->
                <td class="py-3.5 px-5 whitespace-nowrap">
                  <span class="font-mono font-black text-[11px] text-slate-900 bg-slate-100 px-2 py-0.5 rounded-md">
                    {{ row.code }}
                  </span>
                </td>

                <!-- Name -->
                <td class="py-3.5 px-5 max-w-xs">
                  <div class="font-semibold text-slate-900 leading-snug">{{ row.name }}</div>
                  <div v-if="row.full_code && row.full_code !== row.code" class="font-mono text-[10px] text-slate-400 mt-0.5">
                    {{ row.full_code }}
                  </div>
                </td>

                <!-- Parent / Context -->
                <td v-if="currentTabConfig.parent" class="py-3.5 px-5 whitespace-nowrap">
                  <span class="font-mono text-[10px] px-2 py-0.5 bg-slate-100 text-slate-700 rounded-md font-bold">
                    {{ parentValue(row) }}
                  </span>
                </td>

                <!-- Year -->
                <td class="py-3.5 px-5 text-center whitespace-nowrap">
                  <span class="font-bold font-mono text-slate-700 text-[11px]">{{ yearValue(row) }}</span>
                </td>

                <!-- Source Type -->
                <td class="py-3.5 px-5 whitespace-nowrap">
                  <span :class="['px-2 py-0.5 rounded-full text-[10px] font-black border', sourceTypeBadge(row.source_type)]">
                    {{ sourceTypeLabel(row.source_type || 'OFFICIAL_IMPORT') }}
                  </span>
                </td>

                <!-- Status -->
                <td class="py-3.5 px-5 text-center whitespace-nowrap">
                  <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border flex items-center justify-center gap-1 w-fit mx-auto', statusBadge(row.status)]">
                    <component :is="statusIcon(row.status)" class="w-3 h-3" />
                    {{ row.status || 'ACTIVE' }}
                  </span>
                </td>

                <!-- Used By Budget -->
                <td class="py-3.5 px-5 text-center whitespace-nowrap">
                  <span v-if="row.used_by_budget_count > 0" class="px-2 py-0.5 bg-sky-50 text-sky-700 border border-sky-200 rounded-full text-[10px] font-black">
                    {{ row.used_by_budget_count }} Pos
                  </span>
                  <span v-else class="text-slate-300 font-bold text-[10px]">—</span>
                </td>

                <!-- Last Updated -->
                <td class="py-3.5 px-5 whitespace-nowrap">
                  <span class="text-slate-500 text-[11px]">
                    {{ new Date(row.updated_at).toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: '2-digit' }) }}
                  </span>
                </td>

                <!-- Actions -->
                <td v-if="canManage" class="py-3.5 px-5 text-center whitespace-nowrap">
                  <div class="flex items-center justify-center gap-1.5">
                    <button
                      @click="openEditModal(row)"
                      class="p-1.5 text-slate-500 hover:text-sky-600 hover:bg-sky-50 rounded-lg transition"
                      title="Edit Nama &amp; Source"
                    >
                      <Edit3 class="w-4 h-4" />
                    </button>

                    <button
                      @click="toggleStatus(row)"
                      :class="[
                        'p-1.5 rounded-lg transition',
                        row.status === 'ACTIVE'
                          ? 'text-slate-400 hover:text-rose-600 hover:bg-rose-50'
                          : 'text-slate-400 hover:text-emerald-600 hover:bg-emerald-50',
                        !row.can_delete && row.status === 'ACTIVE' ? 'opacity-40 cursor-not-allowed' : ''
                      ]"
                      :title="row.status === 'ACTIVE' ? 'Nonaktifkan' : 'Aktifkan'"
                      :disabled="!row.can_delete && row.status === 'ACTIVE'"
                    >
                      <component :is="row.status === 'ACTIVE' ? CircleOff : CheckCircle2" class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="rows.data.length === 0">
                <td :colspan="canManage ? 9 : 8" class="py-14 text-center">
                  <div class="flex flex-col items-center gap-2 text-slate-400">
                    <AlertTriangle class="w-8 h-8 text-slate-300" />
                    <p class="font-bold text-sm text-slate-600">Tidak ada data ditemukan</p>
                    <p class="text-xs">Coba ubah filter atau cari dengan kata kunci lain.</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- =================== PAGINATION =================== -->
        <div v-if="rows.last_page > 1" class="flex items-center justify-between px-5 py-4 border-t border-slate-100">
          <span class="text-xs text-slate-500 font-medium">
            Halaman {{ rows.current_page }} dari {{ rows.last_page }} &bull; {{ rows.total }} entri
          </span>
          <div class="flex gap-2">
            <Link
              v-if="rows.prev_page_url"
              :href="rows.prev_page_url"
              class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition"
            >
              &larr; Sebelumnya
            </Link>
            <Link
              v-if="rows.next_page_url"
              :href="rows.next_page_url"
              class="px-3 py-1.5 bg-sky-600 hover:bg-sky-500 text-white rounded-lg text-xs font-bold transition"
            >
              Berikutnya &rarr;
            </Link>
          </div>
        </div>
      </div>
    </div>

    <!-- =================== EDIT MODAL =================== -->
    <div v-if="isEditModalOpen" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
          <div>
            <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
              <Edit3 class="w-4 h-4 text-sky-600" /> Edit Metadata Master
            </h3>
            <p class="text-[10px] text-slate-500 mt-0.5">
              Kode <span class="font-mono font-black text-slate-800">{{ editingRow?.code }}</span> tidak dapat diubah (berasal dari sumber resmi).
            </p>
          </div>
          <button @click="closeEditModal" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
            <X class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="submitEdit" class="space-y-4 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Nama Official *</label>
            <input
              v-model="editForm.name"
              type="text"
              class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 font-medium focus:ring-2 focus:ring-sky-500"
              required
            />
            <p class="text-[10px] text-slate-400 mt-1">
              Gunakan nama resmi dari DIPA/Buku III. Jangan masukkan deskripsi internal.
            </p>
            <span v-if="editForm.errors.name" class="text-rose-600 text-[10px]">{{ editForm.errors.name }}</span>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Tipe Sumber *</label>
            <select
              v-model="editForm.source_type"
              class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-bold text-slate-900 focus:ring-2 focus:ring-sky-500"
            >
              <option value="OFFICIAL_IMPORT">Official Import (dari Import Pagu)</option>
              <option value="OFFICIAL_DOCUMENT">Official Document (dari dokumen resmi)</option>
              <option value="INTERNAL">Internal (kebutuhan lokal fakultas)</option>
              <option value="NEEDS_VALIDATION">Needs Validation (belum terverifikasi)</option>
            </select>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Status *</label>
            <select
              v-model="editForm.status"
              class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-bold text-slate-900 focus:ring-2 focus:ring-sky-500"
            >
              <option value="ACTIVE">ACTIVE — Aktif digunakan dalam transaksi</option>
              <option value="INACTIVE">INACTIVE — Tidak aktif (dapat diaktifkan lagi)</option>
              <option value="ARCHIVED">ARCHIVED — Diarsipkan (kode lama)</option>
            </select>
          </div>

          <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
            <button type="button" @click="closeEditModal" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition">
              Batal
            </button>
            <button
              type="submit"
              :disabled="editForm.processing"
              class="px-5 py-2 bg-sky-600 hover:bg-sky-500 text-white font-bold rounded-xl transition shadow-md shadow-sky-600/20 disabled:opacity-50"
            >
              {{ editForm.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
            </button>
          </div>
        </form>
      </div>
    </div>

  </AppLayout>
</template>
