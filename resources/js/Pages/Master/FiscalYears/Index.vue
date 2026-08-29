<script setup>
import { ref } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import { Calendar, Plus, Edit3, CheckCircle, Clock, X } from '@lucide/vue';

const props = defineProps({
  fiscalYears: Object,
});

const isModalOpen = ref(false);
const editingFiscalYear = ref(null);

const form = useForm({
  year: '',
  start_date: '',
  end_date: '',
  status: 'ACTIVE',
});

const openCreateModal = () => {
  editingFiscalYear.value = null;
  const currentYear = new Date().getFullYear();
  form.year = String(currentYear);
  form.start_date = `${currentYear}-01-01`;
  form.end_date = `${currentYear}-12-31`;
  form.status = 'ACTIVE';
  isModalOpen.value = true;
};

const openEditModal = (fy) => {
  editingFiscalYear.value = fy;
  form.year = fy.year;
  form.start_date = fy.start_date ? fy.start_date.split('T')[0] : '';
  form.end_date = fy.end_date ? fy.end_date.split('T')[0] : '';
  form.status = fy.status;
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
  editingFiscalYear.value = null;
  form.reset();
};

const submitForm = () => {
  if (editingFiscalYear.value) {
    form.put(`/master/fiscal-years/${editingFiscalYear.value.id}`, {
      onSuccess: () => closeModal(),
    });
  } else {
    form.post('/master/fiscal-years', {
      onSuccess: () => closeModal(),
    });
  }
};

const setActive = (fy) => {
  if (confirm(`Jadikan Tahun Anggaran ${fy.year} sebagai tahun aktif sistem?`)) {
    router.post(`/master/fiscal-years/${fy.id}/set-active`);
  }
};

const getStatusBadge = (st) => {
  switch(st) {
    case 'ACTIVE': return 'bg-emerald-50 text-emerald-700 border-emerald-200';
    case 'CLOSED': return 'bg-slate-100 text-slate-500 border-slate-200';
    default: return 'bg-amber-50 text-amber-700 border-amber-200';
  }
};
</script>

<template>
  <AppLayout title="Master Data Tahun Anggaran">
    <!-- Header & Action Bar -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm mb-6 flex flex-wrap items-center justify-between gap-4">
      <div>
        <h3 class="font-bold text-slate-900 text-base flex items-center gap-2">
          <Calendar class="w-5 h-5 text-sky-600" />
          Master Periode Tahun Anggaran
        </h3>
        <p class="text-xs text-slate-500 mt-0.5">Kelola siklus tahun pembukuan dan status aktif tahun anggaran</p>
      </div>

      <button 
        @click="openCreateModal" 
        class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-md shadow-sky-600/20"
      >
        <Plus class="w-4 h-4" /> Tambah Tahun Anggaran
      </button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-200">
              <th class="py-3.5 px-6 whitespace-nowrap">Tahun Anggaran</th>
              <th class="py-3.5 px-6 whitespace-nowrap">Periode Mulai - Selesai</th>
              <th class="py-3.5 px-6 text-center whitespace-nowrap">Status Periode</th>
              <th class="py-3.5 px-6 text-center whitespace-nowrap">Total Pos Pagu</th>
              <th class="py-3.5 px-6 text-center whitespace-nowrap">Total Pengajuan</th>
              <th class="py-3.5 px-6 text-center whitespace-nowrap">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-slate-900">
            <tr v-for="fy in fiscalYears.data" :key="fy.id" class="hover:bg-slate-50/80 transition">
              <td class="py-4 px-6 font-mono font-black text-base text-slate-900 whitespace-nowrap">
                {{ fy.year }}
              </td>
              <td class="py-4 px-6 font-mono text-slate-600">
                {{ new Date(fy.start_date).toLocaleDateString('id-ID', { dateStyle: 'medium' }) }} &mdash; {{ new Date(fy.end_date).toLocaleDateString('id-ID', { dateStyle: 'medium' }) }}
              </td>
              <td class="py-4 px-6 text-center whitespace-nowrap">
                <span :class="['px-2.5 py-1 rounded-full text-[10px] font-extrabold border inline-block', getStatusBadge(fy.status)]">
                  {{ fy.status }}
                </span>
              </td>
              <td class="py-4 px-6 text-center font-mono font-semibold text-slate-700">{{ fy.budget_buckets_count }} Pos</td>
              <td class="py-4 px-6 text-center font-mono font-semibold text-slate-700">{{ fy.submissions_count }} Pengajuan</td>
              <td class="py-4 px-6 text-center whitespace-nowrap">
                <div class="flex items-center justify-center gap-2">
                  <button 
                    v-if="fy.status !== 'ACTIVE'" 
                    @click="setActive(fy)" 
                    class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-lg text-[10px] font-bold transition flex items-center gap-1"
                    title="Jadikan Tahun Aktif"
                  >
                    <CheckCircle class="w-3.5 h-3.5" /> Aktifkan
                  </button>
                  <button 
                    @click="openEditModal(fy)" 
                    class="p-1.5 text-slate-600 hover:text-sky-600 hover:bg-sky-50 rounded-lg transition"
                    title="Edit Periode"
                  >
                    <Edit3 class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="fiscalYears.data.length === 0">
              <td colspan="6" class="py-10 text-center text-slate-500">
                Belum ada data tahun anggaran.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="fiscalYears.links && fiscalYears.links.length > 3" class="p-4 border-t border-slate-100 flex items-center justify-between">
        <span class="text-xs text-slate-500">
          Menampilkan {{ fiscalYears.from ?? 0 }} - {{ fiscalYears.to ?? 0 }} dari {{ fiscalYears.total }} total data
        </span>
        <div class="flex items-center gap-1">
          <Link
            v-for="(link, i) in fiscalYears.links"
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
            {{ editingFiscalYear ? 'Edit Tahun Anggaran' : 'Tambah Tahun Anggaran Baru' }}
          </h4>
          <button @click="closeModal" class="p-1.5 text-slate-400 hover:text-slate-700 rounded-lg transition">
            <X class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="submitForm" class="p-6 space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Tahun Anggaran (4 Digit)</label>
            <input 
              v-model="form.year" 
              type="text" 
              required 
              placeholder="Contoh: 2026" 
              class="w-full px-3.5 py-2 border border-slate-300 rounded-xl text-xs font-mono font-bold text-slate-900 focus:ring-2 focus:ring-sky-500" 
            />
            <div v-if="form.errors.year" class="text-rose-600 text-[11px] mt-1 font-medium">{{ form.errors.year }}</div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Mulai</label>
              <input 
                v-model="form.start_date" 
                type="date" 
                required 
                class="w-full px-3.5 py-2 border border-slate-300 rounded-xl text-xs text-slate-900 focus:ring-2 focus:ring-sky-500" 
              />
              <div v-if="form.errors.start_date" class="text-rose-600 text-[11px] mt-1 font-medium">{{ form.errors.start_date }}</div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Selesai</label>
              <input 
                v-model="form.end_date" 
                type="date" 
                required 
                class="w-full px-3.5 py-2 border border-slate-300 rounded-xl text-xs text-slate-900 focus:ring-2 focus:ring-sky-500" 
              />
              <div v-if="form.errors.end_date" class="text-rose-600 text-[11px] mt-1 font-medium">{{ form.errors.end_date }}</div>
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Status Tahun Anggaran</label>
            <select 
              v-model="form.status" 
              class="w-full px-3.5 py-2 border border-slate-300 rounded-xl text-xs font-bold text-slate-900 focus:ring-2 focus:ring-sky-500"
            >
              <option value="ACTIVE">ACTIVE (Tahun Berjalan / Digunakan Sistem)</option>
              <option value="PLANNING">PLANNING (Tahap Perencanaan)</option>
              <option value="CLOSED">CLOSED (Tutup Buku / Arsip)</option>
            </select>
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
              {{ editingFiscalYear ? 'Simpan Perubahan' : 'Tambah Tahun Anggaran' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
