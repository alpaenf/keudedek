<script setup>
import { ref } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import { Wallet, Plus, Search, Edit3, Trash2, X } from '@lucide/vue';

const props = defineProps({
  fundingSources: Object,
  filters: Object,
});

const search = ref(props.filters?.search || '');
const isModalOpen = ref(false);
const editingFundingSource = ref(null);

const form = useForm({
  code: '',
  name: '',
  description: '',
});

const handleSearch = () => {
  router.get('/master/funding-sources', { search: search.value }, { preserveState: true });
};

const openCreateModal = () => {
  editingFundingSource.value = null;
  form.reset();
  isModalOpen.value = true;
};

const openEditModal = (fs) => {
  editingFundingSource.value = fs;
  form.code = fs.code;
  form.name = fs.name;
  form.description = fs.description || '';
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
  editingFundingSource.value = null;
  form.reset();
};

const submitForm = () => {
  if (editingFundingSource.value) {
    form.put(`/master/funding-sources/${editingFundingSource.value.id}`, {
      onSuccess: () => closeModal(),
    });
  } else {
    form.post('/master/funding-sources', {
      onSuccess: () => closeModal(),
    });
  }
};

const deleteFundingSource = (fs) => {
  if (confirm(`Yakin ingin menghapus sumber dana "${fs.name}" (${fs.code})?`)) {
    router.delete(`/master/funding-sources/${fs.id}`);
  }
};
</script>

<template>
  <AppLayout title="Master Data Sumber Dana">
    <!-- Header & Action Bar -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm mb-6 flex flex-wrap items-center justify-between gap-4">
      <div>
        <h3 class="font-bold text-slate-900 text-base flex items-center gap-2">
          <Wallet class="w-5 h-5 text-sky-600" />
          Master Sumber Dana
        </h3>
        <p class="text-xs text-slate-500 mt-0.5">Kelola jenis dan sumber alokasi anggaran (e.g. BOPTN, PNBP, Rupiah Murni)</p>
      </div>

      <div class="flex items-center gap-3">
        <div class="relative">
          <input 
            v-model="search" 
            @keyup.enter="handleSearch" 
            type="text" 
            placeholder="Cari Kode / Nama Sumber Dana..." 
            class="pl-9 pr-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 w-64" 
          />
          <Search class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" />
        </div>

        <button 
          @click="openCreateModal" 
          class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-md shadow-sky-600/20"
        >
          <Plus class="w-4 h-4" /> Tambah Sumber Dana
        </button>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-200">
              <th class="py-3.5 px-6 whitespace-nowrap">Kode Sumber Dana</th>
              <th class="py-3.5 px-6 whitespace-nowrap">Nama Sumber Dana</th>
              <th class="py-3.5 px-6 whitespace-nowrap">Deskripsi / Keterangan</th>
              <th class="py-3.5 px-6 text-center whitespace-nowrap">Total Pagu Terhubung</th>
              <th class="py-3.5 px-6 text-center whitespace-nowrap">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-slate-900">
            <tr v-for="fs in fundingSources.data" :key="fs.id" class="hover:bg-slate-50/80 transition">
              <td class="py-4 px-6 font-sans font-bold text-sky-700 whitespace-nowrap">{{ fs.code }}</td>
              <td class="py-4 px-6 font-bold text-slate-900">{{ fs.name }}</td>
              <td class="py-4 px-6 text-slate-600 max-w-md truncate">{{ fs.description || '-' }}</td>
              <td class="py-4 px-6 text-center font-sans font-semibold text-slate-700">{{ fs.budget_buckets_count }} Pos Pagu</td>
              <td class="py-4 px-6 text-center whitespace-nowrap">
                <div class="flex items-center justify-center gap-1.5">
                  <button 
                    @click="openEditModal(fs)" 
                    class="p-1.5 text-slate-600 hover:text-sky-600 hover:bg-sky-50 rounded-lg transition"
                    title="Edit Sumber Dana"
                  >
                    <Edit3 class="w-4 h-4" />
                  </button>
                  <button 
                    @click="deleteFundingSource(fs)" 
                    class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition"
                    title="Hapus Sumber Dana"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="fundingSources.data.length === 0">
              <td colspan="5" class="py-10 text-center text-slate-500">
                Belum ada data sumber dana yang tersimpan.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="fundingSources.links && fundingSources.links.length > 3" class="p-4 border-t border-slate-100 flex items-center justify-between">
        <span class="text-xs text-slate-500">
          Menampilkan {{ fundingSources.from ?? 0 }} - {{ fundingSources.to ?? 0 }} dari {{ fundingSources.total }} total data
        </span>
        <div class="flex items-center gap-1">
          <Link
            v-for="(link, i) in fundingSources.links"
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
            {{ editingFundingSource ? 'Edit Sumber Dana' : 'Tambah Sumber Dana Baru' }}
          </h4>
          <button @click="closeModal" class="p-1.5 text-slate-400 hover:text-slate-700 rounded-lg transition">
            <X class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="submitForm" class="p-6 space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Sumber Dana (e.g. BOPTN, PNBP, RM)</label>
            <input 
              v-model="form.code" 
              type="text" 
              required 
              placeholder="Contoh: BOPTN" 
              class="w-full px-3.5 py-2 border border-slate-300 rounded-xl text-xs font-sans font-bold text-slate-900 focus:ring-2 focus:ring-sky-500" 
            />
            <div v-if="form.errors.code" class="text-rose-600 text-[11px] mt-1 font-medium">{{ form.errors.code }}</div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap Sumber Dana</label>
            <input 
              v-model="form.name" 
              type="text" 
              required 
              placeholder="Contoh: Bantuan Operasional Perguruan Tinggi Negeri" 
              class="w-full px-3.5 py-2 border border-slate-300 rounded-xl text-xs text-slate-900 focus:ring-2 focus:ring-sky-500" 
            />
            <div v-if="form.errors.name" class="text-rose-600 text-[11px] mt-1 font-medium">{{ form.errors.name }}</div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi / Peruntukan</label>
            <textarea 
              v-model="form.description" 
              rows="3" 
              placeholder="Contoh: Dana APBN untuk operasional perkuliahan dan laboratorium..." 
              class="w-full px-3.5 py-2 border border-slate-300 rounded-xl text-xs text-slate-900 focus:ring-2 focus:ring-sky-500"
            ></textarea>
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
              {{ editingFundingSource ? 'Simpan Perubahan' : 'Tambah Sumber Dana' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
