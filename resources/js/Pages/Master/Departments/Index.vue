<script setup>
import { ref } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import { 
  Building2, 
  GraduationCap, 
  Plus, 
  Search, 
  Edit3, 
  Trash2, 
  CheckCircle2, 
  XCircle, 
  X, 
  Layers, 
  AlertCircle, 
  Info, 
  Lock, 
  ChevronRight,
  ShieldCheck,
  Calendar,
  FileText
} from 'lucide-vue-next';

const props = defineProps({
  departments: Object,
  studyPrograms: Object,
  parentDepartments: Array,
  selectableDepartments: Array,
  activeTab: String,
  filters: Object,
});

const currentTab = ref(props.activeTab || 'departments');
const search = ref(props.filters?.search || '');

// Department Modal State
const isDeptModalOpen = ref(false);
const editingDepartment = ref(null);
const deptForm = useForm({
  code: '',
  official_code: '',
  name: '',
  type: 'DEPARTMENT',
  parent_id: '',
  is_active: true,
  status: 'ACTIVE',
  source_type: 'INTERNAL',
  effective_year: 2026,
});

// Study Program Modal State
const isProdiModalOpen = ref(false);
const editingProdi = ref(null);
const prodiForm = useForm({
  code: '',
  official_code: '',
  name: '',
  department_id: '',
  is_active: true,
  status: 'ACTIVE',
  source_type: 'INTERNAL',
  effective_year: 2026,
});

const switchTab = (tab) => {
  currentTab.value = tab;
  router.get('/master/departments', { tab, search: search.value || undefined }, { preserveState: true, preserveScroll: true });
};

const handleSearch = () => {
  router.get('/master/departments', { tab: currentTab.value, search: search.value || undefined }, { preserveState: true });
};

// Department Modal Handlers
const openCreateDeptModal = () => {
  editingDepartment.value = null;
  deptForm.reset();
  deptForm.is_active = true;
  deptForm.status = 'ACTIVE';
  deptForm.type = 'DEPARTMENT';
  deptForm.source_type = 'INTERNAL';
  deptForm.effective_year = 2026;
  deptForm.parent_id = props.parentDepartments[0]?.id || '';
  isDeptModalOpen.value = true;
};

const openEditDeptModal = (dept) => {
  editingDepartment.value = dept;
  deptForm.code = dept.code;
  deptForm.official_code = dept.official_code || '';
  deptForm.name = dept.name;
  deptForm.type = dept.type || (dept.code.includes('FT') ? 'FACULTY' : 'DEPARTMENT');
  deptForm.parent_id = dept.parent_id || '';
  deptForm.is_active = dept.is_active;
  deptForm.status = dept.status || (dept.is_active ? 'ACTIVE' : 'INACTIVE');
  deptForm.source_type = dept.source_type || 'INTERNAL';
  deptForm.effective_year = dept.effective_year || 2026;
  isDeptModalOpen.value = true;
};

const closeDeptModal = () => {
  isDeptModalOpen.value = false;
  editingDepartment.value = null;
  deptForm.reset();
};

const submitDeptForm = () => {
  if (editingDepartment.value) {
    deptForm.put(`/master/departments/${editingDepartment.value.id}`, {
      onSuccess: () => closeDeptModal(),
    });
  } else {
    deptForm.post('/master/departments', {
      onSuccess: () => closeDeptModal(),
    });
  }
};

const toggleDeptActive = (dept) => {
  router.post(`/master/departments/${dept.id}/toggle-active`, {}, { preserveScroll: true });
};

const deleteDepartment = (dept) => {
  if (!dept.can_delete) {
    alert(`Unit "${dept.name}" tidak dapat dihapus:\n${dept.reference_reason}`);
    return;
  }
  if (confirm(`Yakin ingin menghapus unit / jurusan "${dept.name}" (${dept.code})?`)) {
    router.delete(`/master/departments/${dept.id}`);
  }
};

// Study Program Modal Handlers
const openCreateProdiModal = () => {
  editingProdi.value = null;
  prodiForm.reset();
  prodiForm.is_active = true;
  prodiForm.status = 'ACTIVE';
  prodiForm.source_type = 'INTERNAL';
  prodiForm.effective_year = 2026;
  prodiForm.department_id = props.selectableDepartments[0]?.id || '';
  isProdiModalOpen.value = true;
};

const openEditProdiModal = (prodi) => {
  editingProdi.value = prodi;
  prodiForm.code = prodi.code;
  prodiForm.official_code = prodi.official_code || '';
  prodiForm.name = prodi.name;
  prodiForm.department_id = prodi.department_id;
  prodiForm.is_active = prodi.is_active;
  prodiForm.status = prodi.status || (prodi.is_active ? 'ACTIVE' : 'INACTIVE');
  prodiForm.source_type = prodi.source_type || 'INTERNAL';
  prodiForm.effective_year = prodi.effective_year || 2026;
  isProdiModalOpen.value = true;
};

const closeProdiModal = () => {
  isProdiModalOpen.value = false;
  editingProdi.value = null;
  prodiForm.reset();
};

const submitProdiForm = () => {
  if (editingProdi.value) {
    prodiForm.put(`/master/study-programs/${editingProdi.value.id}`, {
      onSuccess: () => closeProdiModal(),
    });
  } else {
    prodiForm.post('/master/study-programs', {
      onSuccess: () => closeProdiModal(),
    });
  }
};

const toggleProdiActive = (prodi) => {
  router.post(`/master/study-programs/${prodi.id}/toggle-active`, {}, { preserveScroll: true });
};

const deleteStudyProgram = (prodi) => {
  if (!prodi.can_delete) {
    alert(`Program Studi "${prodi.name}" tidak dapat dihapus:\n${prodi.reference_reason}`);
    return;
  }
  if (confirm(`Yakin ingin menghapus Program Studi "${prodi.name}" (${prodi.code})?`)) {
    router.delete(`/master/study-programs/${prodi.id}`);
  }
};
</script>

<template>
  <AppLayout title="Master Organisasi">
    <div class="space-y-6 font-sans max-w-7xl mx-auto">
      
      <!-- Header & Navigation Tabs -->
      <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <span class="px-2.5 py-0.5 bg-sky-100 text-sky-800 text-[10px] font-black rounded-lg uppercase tracking-wider">
              Struktur Organisasi
            </span>
            <span class="text-xs text-slate-400 font-semibold">&bull; Fakultas Teknik UNSOED</span>
          </div>
          <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight mt-1 flex items-center gap-2">
            <Building2 class="w-6 h-6 text-sky-600" />
            Master Organisasi
          </h1>
          <p class="text-xs text-slate-500 mt-0.5">
            Kelola data struktur fakultas, 5 jurusan induk, dan 11 program studi dengan integrasi otorisasi &amp; kendali anggaran.
          </p>
        </div>

        <!-- Search & Add Action -->
        <div class="flex items-center gap-3">
          <div class="relative">
            <input 
              v-model="search" 
              @keyup.enter="handleSearch" 
              type="text" 
              :placeholder="currentTab === 'departments' ? 'Cari Unit / Jurusan...' : 'Cari Program Studi...'" 
              class="pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 w-64 shadow-sm" 
            />
            <Search class="w-4 h-4 text-slate-400 absolute left-3 top-3" />
          </div>

          <button 
            v-if="currentTab === 'departments'"
            @click="openCreateDeptModal" 
            class="px-4 py-2.5 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-md shadow-sky-600/20"
          >
            <Plus class="w-4 h-4" /> Tambah Unit
          </button>

          <button 
            v-else
            @click="openCreateProdiModal" 
            class="px-4 py-2.5 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-md shadow-sky-600/20"
          >
            <Plus class="w-4 h-4" /> Tambah Prodi
          </button>
        </div>
      </div>

      <!-- Navigation Tabs: [Unit & Jurusan] | [Program Studi] -->
      <div class="flex items-center gap-2 border-b border-slate-200">
        <button 
          @click="switchTab('departments')"
          :class="[
            'px-5 py-3 text-xs font-bold flex items-center gap-2 border-b-2 transition -mb-px',
            currentTab === 'departments' 
              ? 'border-sky-600 text-sky-700 bg-white rounded-t-xl' 
              : 'border-transparent text-slate-500 hover:text-slate-900 hover:border-slate-300'
          ]"
        >
          <Building2 class="w-4 h-4" />
          <span>Unit &amp; Jurusan</span>
          <span class="px-2 py-0.5 bg-slate-100 text-slate-700 rounded-full text-[10px] font-black">
            {{ departments.total }}
          </span>
        </button>

        <button 
          @click="switchTab('study-programs')"
          :class="[
            'px-5 py-3 text-xs font-bold flex items-center gap-2 border-b-2 transition -mb-px',
            currentTab === 'study-programs' 
              ? 'border-sky-600 text-sky-700 bg-white rounded-t-xl' 
              : 'border-transparent text-slate-500 hover:text-slate-900 hover:border-slate-300'
          ]"
        >
          <GraduationCap class="w-4 h-4" />
          <span>Program Studi</span>
          <span class="px-2 py-0.5 bg-slate-100 text-slate-700 rounded-full text-[10px] font-black">
            {{ studyPrograms.total }}
          </span>
        </button>
      </div>

      <!-- ========================================== -->
      <!-- TAB 1: UNIT & JURUSAN TABLE -->
      <!-- ========================================== -->
      <div v-if="currentTab === 'departments'" class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-50 text-slate-700 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200">
                <th class="py-3.5 px-6 whitespace-nowrap">Kode Unit</th>
                <th class="py-3.5 px-6 whitespace-nowrap">Nama Unit Kerja / Jurusan</th>
                <th class="py-3.5 px-6 whitespace-nowrap">Tipe</th>
                <th class="py-3.5 px-6 whitespace-nowrap">Induk Unit</th>
                <th class="py-3.5 px-6 text-center whitespace-nowrap">Total Pagu Pos</th>
                <th class="py-3.5 px-6 text-center whitespace-nowrap">Pengguna</th>
                <th class="py-3.5 px-6 text-center whitespace-nowrap">Status Aktif</th>
                <th class="py-3.5 px-6 text-center whitespace-nowrap">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-900">
              <tr v-for="dept in departments.data" :key="dept.id" class="hover:bg-slate-50/80 transition">
                <td class="py-4 px-6 whitespace-nowrap">
                  <div class="font-sans font-bold text-sky-700">{{ dept.code }}</div>
                  <div v-if="dept.official_code" class="text-[10px] text-slate-400 font-sans">
                    Resmi: {{ dept.official_code }}
                  </div>
                </td>
                <td class="py-4 px-6 font-bold text-slate-900">
                  {{ dept.name }}
                  <div v-if="dept.source_type && dept.source_type !== 'INTERNAL'" class="text-[10px] font-normal text-slate-500">
                    Sumber: {{ dept.source_type }} ({{ dept.effective_year || 2026 }})
                  </div>
                </td>
                <td class="py-4 px-6 whitespace-nowrap">
                  <span :class="[
                    'px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border',
                    dept.type === 'FACULTY' 
                      ? 'bg-purple-50 text-purple-700 border-purple-200' 
                      : 'bg-sky-50 text-sky-700 border-sky-200'
                  ]">
                    {{ dept.type || (dept.code.includes('FT') ? 'FACULTY' : 'DEPARTMENT') }}
                  </span>
                </td>
                <td class="py-4 px-6 text-slate-600 whitespace-nowrap">
                  {{ dept.parent?.name ?? 'Tingkat Fakultas (Root)' }}
                </td>
                <td class="py-4 px-6 text-center font-sans font-semibold text-slate-700 whitespace-nowrap">
                  {{ dept.budget_buckets_count }} Pos
                </td>
                <td class="py-4 px-6 text-center font-sans font-semibold text-slate-700 whitespace-nowrap">
                  {{ dept.users_count }} Akun
                </td>
                <td class="py-4 px-6 text-center whitespace-nowrap">
                  <button 
                    @click="toggleDeptActive(dept)" 
                    :class="[
                      'px-2.5 py-1 rounded-full text-[10px] font-extrabold border transition',
                      dept.is_active 
                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' 
                        : 'bg-slate-100 text-slate-500 border-slate-200 hover:bg-slate-200'
                    ]"
                  >
                    {{ dept.is_active ? 'AKTIF' : 'NONAKTIF' }}
                  </button>
                </td>
                <td class="py-4 px-6 text-center whitespace-nowrap">
                  <div class="flex items-center justify-center gap-1.5">
                    <button 
                      @click="openEditDeptModal(dept)" 
                      class="p-1.5 text-slate-600 hover:text-sky-600 hover:bg-sky-50 rounded-lg transition"
                      title="Edit Unit"
                    >
                      <Edit3 class="w-4 h-4" />
                    </button>
                    
                    <button 
                      v-if="dept.can_delete"
                      @click="deleteDepartment(dept)" 
                      class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition"
                      title="Hapus Unit"
                    >
                      <Trash2 class="w-4 h-4" />
                    </button>
                    <span 
                      v-else
                      class="p-1.5 text-slate-300 cursor-not-allowed"
                      :title="`Tidak dapat dihapus: ${dept.reference_reason}`"
                    >
                      <Lock class="w-4 h-4 text-slate-300" />
                    </span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ========================================== -->
      <!-- TAB 2: PROGRAM STUDI TABLE -->
      <!-- ========================================== -->
      <div v-else class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-50 text-slate-700 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200">
                <th class="py-3.5 px-6 whitespace-nowrap">Kode Internal</th>
                <th class="py-3.5 px-6 whitespace-nowrap">Nama Program Studi</th>
                <th class="py-3.5 px-6 whitespace-nowrap">Jurusan Induk</th>
                <th class="py-3.5 px-6 whitespace-nowrap">Kaprodi (Otorisasi Scope)</th>
                <th class="py-3.5 px-6 text-center whitespace-nowrap">Transaksi Terkait</th>
                <th class="py-3.5 px-6 text-center whitespace-nowrap">Status</th>
                <th class="py-3.5 px-6 text-center whitespace-nowrap">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-900">
              <tr v-for="prodi in studyPrograms.data" :key="prodi.id" class="hover:bg-slate-50/80 transition">
                <td class="py-4 px-6 whitespace-nowrap">
                  <div class="font-sans font-bold text-sky-700">{{ prodi.code }}</div>
                  <div v-if="prodi.official_code" class="text-[10px] text-slate-400 font-sans">
                    Resmi: {{ prodi.official_code }}
                  </div>
                </td>
                <td class="py-4 px-6 font-bold text-slate-900">
                  {{ prodi.name }}
                  <div v-if="prodi.source_type && prodi.source_type !== 'INTERNAL'" class="text-[10px] font-normal text-slate-500">
                    Sumber: {{ prodi.source_type }} ({{ prodi.effective_year || 2026 }})
                  </div>
                </td>
                <td class="py-4 px-6 whitespace-nowrap">
                  <div class="font-semibold text-slate-800">{{ prodi.department?.name }}</div>
                  <div class="text-[10px] font-mono text-slate-400">{{ prodi.department?.code }}</div>
                </td>
                <td class="py-4 px-6 whitespace-nowrap">
                  <div class="font-bold text-slate-900">{{ prodi.kaprodi_name }}</div>
                  <div class="text-[10px] text-slate-500 font-medium">Role: [KAPRODI]</div>
                </td>
                <td class="py-4 px-6 text-center font-sans font-semibold text-slate-700 whitespace-nowrap">
                  {{ prodi.submissions_count }} Transaksi
                </td>
                <td class="py-4 px-6 text-center whitespace-nowrap">
                  <button 
                    @click="toggleProdiActive(prodi)" 
                    :class="[
                      'px-2.5 py-1 rounded-full text-[10px] font-extrabold border transition',
                      prodi.is_active 
                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' 
                        : 'bg-slate-100 text-slate-500 border-slate-200 hover:bg-slate-200'
                    ]"
                  >
                    {{ prodi.is_active ? 'AKTIF' : 'NONAKTIF' }}
                  </button>
                </td>
                <td class="py-4 px-6 text-center whitespace-nowrap">
                  <div class="flex items-center justify-center gap-1.5">
                    <button 
                      @click="openEditProdiModal(prodi)" 
                      class="p-1.5 text-slate-600 hover:text-sky-600 hover:bg-sky-50 rounded-lg transition"
                      title="Edit Program Studi"
                    >
                      <Edit3 class="w-4 h-4" />
                    </button>
                    
                    <button 
                      v-if="prodi.can_delete"
                      @click="deleteStudyProgram(prodi)" 
                      class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition"
                      title="Hapus Program Studi"
                    >
                      <Trash2 class="w-4 h-4" />
                    </button>
                    <span 
                      v-else
                      class="p-1.5 text-slate-300 cursor-not-allowed"
                      :title="`Tidak dapat dihapus: ${prodi.reference_reason}`"
                    >
                      <Lock class="w-4 h-4 text-slate-300" />
                    </span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>

    <!-- ========================================== -->
    <!-- MODAL 1: CREATE / EDIT UNIT & JURUSAN -->
    <!-- ========================================== -->
    <div v-if="isDeptModalOpen" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
          <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
            <Building2 class="w-4 h-4 text-sky-600" />
            {{ editingDepartment ? 'Edit Unit Kerja / Jurusan' : 'Tambah Unit Kerja Baru' }}
          </h3>
          <button @click="closeDeptModal" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
            <X class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="submitDeptForm" class="space-y-3.5 text-xs">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Kode Internal Unit *</label>
              <input 
                v-model="deptForm.code" 
                type="text" 
                placeholder="Contoh: JTIF" 
                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-mono uppercase font-bold text-slate-900 focus:ring-2 focus:ring-sky-500" 
                required 
              />
              <span v-if="deptForm.errors.code" class="text-rose-600 text-[10px]">{{ deptForm.errors.code }}</span>
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Kode Resmi (DIPA/Dikti)</label>
              <input 
                v-model="deptForm.official_code" 
                type="text" 
                placeholder="Contoh: 023.17.WA" 
                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-mono text-slate-900 focus:ring-2 focus:ring-sky-500" 
              />
            </div>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Nama Unit Kerja / Jurusan *</label>
            <input 
              v-model="deptForm.name" 
              type="text" 
              placeholder="Contoh: Jurusan Informatika" 
              class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 font-medium focus:ring-2 focus:ring-sky-500" 
              required 
            />
            <span v-if="deptForm.errors.name" class="text-rose-600 text-[10px]">{{ deptForm.errors.name }}</span>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Tipe Unit *</label>
              <select 
                v-model="deptForm.type" 
                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 font-semibold focus:ring-2 focus:ring-sky-500"
              >
                <option value="DEPARTMENT">DEPARTMENT (Jurusan)</option>
                <option value="FACULTY">FACULTY (Fakultas)</option>
              </select>
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Induk Unit</label>
              <select 
                v-model="deptForm.parent_id" 
                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 focus:ring-2 focus:ring-sky-500"
              >
                <option value="">Tingkat Fakultas (Root)</option>
                <option v-for="p in parentDepartments" :key="p.id" :value="p.id">{{ p.name }} ({{ p.code }})</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Sumber Data</label>
              <select 
                v-model="deptForm.source_type" 
                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 focus:ring-2 focus:ring-sky-500"
              >
                <option value="INTERNAL">INTERNAL (Sistem SIKARA)</option>
                <option value="OFFICIAL_IMPORT">OFFICIAL_IMPORT (Impor RKAKL)</option>
                <option value="OFFICIAL_DOCUMENT">OFFICIAL_DOCUMENT (SK Dekan/Rektor)</option>
                <option value="NEEDS_VALIDATION">NEEDS_VALIDATION (Perlu Validasi)</option>
              </select>
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Tahun Berlaku</label>
              <input 
                v-model="deptForm.effective_year" 
                type="number" 
                min="2020" 
                max="2030" 
                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 focus:ring-2 focus:ring-sky-500 font-mono" 
              />
            </div>
          </div>

          <div class="pt-1">
            <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
              <input type="checkbox" v-model="deptForm.is_active" class="w-4 h-4 text-sky-600 rounded border-slate-300 focus:ring-sky-500" />
              <span>Unit Aktif untuk Otorisasi &amp; Transaksi Belanja</span>
            </label>
          </div>

          <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
            <button 
              type="button" 
              @click="closeDeptModal" 
              class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition"
            >
              Batal
            </button>
            <button 
              type="submit" 
              :disabled="deptForm.processing" 
              class="px-5 py-2 bg-sky-600 hover:bg-sky-500 text-white font-bold rounded-xl transition shadow-md shadow-sky-600/20 disabled:opacity-50"
            >
              {{ deptForm.processing ? 'Menyimpan...' : 'Simpan Data Unit' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL 2: CREATE / EDIT PROGRAM STUDI -->
    <!-- ========================================== -->
    <div v-if="isProdiModalOpen" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
          <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
            <GraduationCap class="w-4 h-4 text-sky-600" />
            {{ editingProdi ? 'Edit Program Studi' : 'Tambah Program Studi Baru' }}
          </h3>
          <button @click="closeProdiModal" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
            <X class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="submitProdiForm" class="space-y-3.5 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Jurusan Induk * (FK Relation)</label>
            <select 
              v-model="prodiForm.department_id" 
              class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 font-semibold focus:ring-2 focus:ring-sky-500" 
              required
            >
              <option value="" disabled>-- Pilih Jurusan Induk --</option>
              <option v-for="d in selectableDepartments" :key="d.id" :value="d.id">
                {{ d.code }} &mdash; {{ d.name }}
              </option>
            </select>
            <span v-if="prodiForm.errors.department_id" class="text-rose-600 text-[10px]">{{ prodiForm.errors.department_id }}</span>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Kode Internal Prodi *</label>
              <input 
                v-model="prodiForm.code" 
                type="text" 
                placeholder="Contoh: PRODI-S1-IF" 
                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-mono uppercase font-bold text-slate-900 focus:ring-2 focus:ring-sky-500" 
                required 
              />
              <span v-if="prodiForm.errors.code" class="text-rose-600 text-[10px]">{{ prodiForm.errors.code }}</span>
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Kode Resmi (PDDikti)</label>
              <input 
                v-model="prodiForm.official_code" 
                type="text" 
                placeholder="Contoh: 55201" 
                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-mono text-slate-900 focus:ring-2 focus:ring-sky-500" 
              />
            </div>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Nama Program Studi *</label>
            <input 
              v-model="prodiForm.name" 
              type="text" 
              placeholder="Contoh: S1 Informatika" 
              class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 font-medium focus:ring-2 focus:ring-sky-500" 
              required 
            />
            <span v-if="prodiForm.errors.name" class="text-rose-600 text-[10px]">{{ prodiForm.errors.name }}</span>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Sumber Data</label>
              <select 
                v-model="prodiForm.source_type" 
                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 focus:ring-2 focus:ring-sky-500"
              >
                <option value="INTERNAL">INTERNAL (Sistem SIKARA)</option>
                <option value="OFFICIAL_IMPORT">OFFICIAL_IMPORT (Impor RKAKL)</option>
                <option value="OFFICIAL_DOCUMENT">OFFICIAL_DOCUMENT (SK Akreditasi)</option>
                <option value="NEEDS_VALIDATION">NEEDS_VALIDATION (Perlu Validasi)</option>
              </select>
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Tahun Berlaku</label>
              <input 
                v-model="prodiForm.effective_year" 
                type="number" 
                min="2020" 
                max="2030" 
                class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 focus:ring-2 focus:ring-sky-500 font-mono" 
              />
            </div>
          </div>

          <div class="pt-1">
            <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
              <input type="checkbox" v-model="prodiForm.is_active" class="w-4 h-4 text-sky-600 rounded border-slate-300 focus:ring-sky-500" />
              <span>Program Studi Aktif untuk Pemetaan Transaksi &amp; Otorisasi Kaprodi</span>
            </label>
          </div>

          <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
            <button 
              type="button" 
              @click="closeProdiModal" 
              class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition"
            >
              Batal
            </button>
            <button 
              type="submit" 
              :disabled="prodiForm.processing" 
              class="px-5 py-2 bg-sky-600 hover:bg-sky-500 text-white font-bold rounded-xl transition shadow-md shadow-sky-600/20 disabled:opacity-50"
            >
              {{ prodiForm.processing ? 'Menyimpan...' : 'Simpan Program Studi' }}
            </button>
          </div>
        </form>
      </div>
    </div>

  </AppLayout>
</template>
