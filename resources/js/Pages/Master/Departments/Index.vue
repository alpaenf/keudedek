<script setup>
import { ref } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import { Building2, Plus, Search, Edit3, Trash2, CheckCircle, XCircle, X } from '@lucide/vue';

const props = defineProps({
  departments: Object,
  parentDepartments: Array,
  filters: Object,
});

const search = ref(props.filters?.search || '');
const isModalOpen = ref(false);
const editingDepartment = ref(null);

const form = useForm({
  code: '',
  name: '',
  parent_id: '',
  is_active: true,
});

const handleSearch = () => {
  router.get('/master/departments', { search: search.value }, { preserveState: true });
};

const openCreateModal = () => {
  editingDepartment.value = null;
  form.reset();
  form.is_active = true;
  form.parent_id = props.parentDepartments[0]?.id || '';
  isModalOpen.value = true;
};

const openEditModal = (dept) => {
  editingDepartment.value = dept;
  form.code = dept.code;
  form.name = dept.name;
  form.parent_id = dept.parent_id || '';
  form.is_active = dept.is_active;
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
  editingDepartment.value = null;
  form.reset();
};

const submitForm = () => {
  if (editingDepartment.value) {
    form.put(`/master/departments/${editingDepartment.value.id}`, {
      onSuccess: () => closeModal(),
    });
  } else {
    form.post('/master/departments', {
      onSuccess: () => closeModal(),
    });
  }
};

const toggleActive = (dept) => {
  router.post(`/master/departments/${dept.id}/toggle-active`, {}, { preserveScroll: true });
};

const deleteDepartment = (dept) => {
  if (confirm(`Yakin ingin menghapus unit / jurusan "${dept.name}" (${dept.code})?`)) {
    router.delete(`/master/departments/${dept.id}`);
  }
};
</script>

<template>
  <AppLayout title="Master Data Unit & Jurusan">
    <!-- Header & Action Bar -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm mb-6 flex flex-wrap items-center justify-between gap-4">
      <div>
        <h3 class="font-bold text-slate-900 text-base flex items-center gap-2">
          <Building2 class="w-5 h-5 text-sky-600" />
          Master Unit Kerja & Jurusan
        </h3>
        <p class="text-xs text-slate-500 mt-0.5">Kelola kode unit, nama jurusan, dan struktur organisasi Fakultas Teknik</p>
      </div>

      <div class="flex items-center gap-3">
        <div class="relative">
          <input 
            v-model="search" 
            @keyup.enter="handleSearch" 
            type="text" 
            placeholder="Cari Kode / Nama Unit..." 
            class="pl-9 pr-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 w-56" 
          />
          <Search class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" />
        </div>

        <button 
          @click="openCreateModal" 
          class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-md shadow-sky-600/20"
        >
          <Plus class="w-4 h-4" /> Tambah Unit
        </button>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-200">
              <th class="py-3.5 px-6 whitespace-nowrap">Kode Unit</th>
              <th class="py-3.5 px-6 whitespace-nowrap">Nama Unit Kerja / Jurusan</th>
              <th class="py-3.5 px-6 whitespace-nowrap">Induk Unit</th>
              <th class="py-3.5 px-6 text-center whitespace-nowrap">Total Pagu Pos</th>
              <th class="py-3.5 px-6 text-center whitespace-nowrap">Pengguna</th>
              <th class="py-3.5 px-6 text-center whitespace-nowrap">Status Aktif</th>
              <th class="py-3.5 px-6 text-center whitespace-nowrap">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-slate-900">
            <tr v-for="dept in departments.data" :key="dept.id" class="hover:bg-slate-50/80 transition">
              <td class="py-4 px-6 font-mono font-bold text-sky-700 whitespace-nowrap">{{ dept.code }}</td>
              <td class="py-4 px-6 font-bold text-slate-900">{{ dept.name }}</td>
              <td class="py-4 px-6 text-slate-600">{{ dept.parent?.name ?? 'Tingkat Fakultas (Root)' }}</td>
              <td class="py-4 px-6 text-center font-mono font-semibold text-slate-700">{{ dept.budget_buckets_count }} Pos</td>
              <td class="py-4 px-6 text-center font-mono font-semibold text-slate-700">{{ dept.users_count }} Akun</td>
              <td class="py-4 px-6 text-center whitespace-nowrap">
                <button 
                  @click="toggleActive(dept)" 
                  :class="['px-2.5 py-1 rounded-full text-[10px] font-extrabold border transition', dept.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 border-slate-200 hover:bg-slate-200']"
                >
                  {{ dept.is_active ? 'AKTIF' : 'NONAKTIF' }}
                </button>
              </td>
              <td class="py-4 px-6 text-center whitespace-nowrap">
                <div class="flex items-center justify-center gap-1.5">
                  <button 
                    @click="openEditModal(dept)" 
                    class="p-1.5 text-slate-600 hover:text-sky-600 hover:bg-sky-50 rounded-lg transition"
                    title="Edit Unit"
                  >
                    <Edit3 class="w-4 h-4" />
                  </button>
                  <button 
                    @click="deleteDepartment(dept)" 
                    class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition"
                    title="Hapus Unit"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="departments.data.length === 0">
              <td colspan="7" class="py-10 text-center text-slate-500">
                Belum ada data unit kerja yang tersimpan.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="departments.links && departments.links.length > 3" class="p-4 border-t border-slate-100 flex items-center justify-between">
        <span class="text-xs text-slate-500">
          Menampilkan {{ departments.from ?? 0 }} - {{ departments.to ?? 0 }} dari {{ departments.total }} total data
        </span>
        <div class="flex items-center gap-1">
          <Link
            v-for="(link, i) in departments.links"
            :key="i"
            :href="link.url || '#'"
            v-html="link.label"
            :class="[
              'px-3 py-1.5 rounded-lg text-xs font-semibold transition',
              link.active ? 'bg-sky-600 text-white' : 'bg-slate-50 text-slate-700 hover:bg-slate-100 border border-slate-200',
              !link.url ? 'opacity-40 cursor-not-allowed' : ''
            ]"
          />
        </div>
      </div>
    </div>

    <!-- Modal Form (Tambah / Edit) -->
    <div v-if="isModalOpen" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-lg w-full overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
          <h4 class="font-bold text-slate-900 text-base">
            {{ editingDepartment ? 'Edit Unit / Jurusan' : 'Tambah Unit / Jurusan Baru' }}
          </h4>
          <button @click="closeModal" class="p-1.5 text-slate-400 hover:text-slate-700 rounded-lg transition">
            <X class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="submitForm" class="p-6 space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Unit (e.g. IF, TE, TS, TM, TG, DEK)</label>
            <input 
              v-model="form.code" 
              type="text" 
              required 
              placeholder="Contoh: IF" 
              class="w-full px-3.5 py-2 border border-slate-300 rounded-xl text-xs font-mono font-bold text-slate-900 focus:ring-2 focus:ring-sky-500" 
            />
            <div v-if="form.errors.code" class="text-rose-600 text-[11px] mt-1 font-medium">{{ form.errors.code }}</div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap Unit Kerja / Jurusan</label>
            <input 
              v-model="form.name" 
              type="text" 
              required 
              placeholder="Contoh: Jurusan Teknik Informatika" 
              class="w-full px-3.5 py-2 border border-slate-300 rounded-xl text-xs text-slate-900 focus:ring-2 focus:ring-sky-500" 
            />
            <div v-if="form.errors.name" class="text-rose-600 text-[11px] mt-1 font-medium">{{ form.errors.name }}</div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Induk Unit (Parent)</label>
            <select 
              v-model="form.parent_id" 
              class="w-full px-3.5 py-2 border border-slate-300 rounded-xl text-xs text-slate-900 focus:ring-2 focus:ring-sky-500"
            >
              <option value="">-- Tidak Ada Induk (Tingkat Fakultas / Root) --</option>
              <option v-for="p in parentDepartments" :key="p.id" :value="p.id">
                {{ p.code }} - {{ p.name }}
              </option>
            </select>
          </div>

          <div class="flex items-center gap-2 pt-1">
            <input v-model="form.is_active" type="checkbox" id="is_active" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
            <label for="is_active" class="text-xs font-semibold text-slate-700 select-none cursor-pointer">Unit Aktif (Dapat digunakan untuk pagu dan pengajuan)</label>
          </div>

          <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
            <button 
              type="button" 
              @click="closeModal" 
              class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition"
            >
              Batal
            </button>
            <button 
              type="submit" 
              :disabled="form.processing" 
              class="px-5 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-bold transition shadow-md shadow-sky-600/20 disabled:opacity-50"
            >
              {{ editingDepartment ? 'Simpan Perubahan' : 'Tambah Unit' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
