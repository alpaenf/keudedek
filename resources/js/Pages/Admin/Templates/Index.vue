<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import { 
  FileText, 
  Plus, 
  Layers, 
  CheckCircle2, 
  Settings2, 
  Tag, 
  X,
  ArrowRight
} from 'lucide-vue-next';

const props = defineProps({
  templates: Object,
  transactionTypes: Array,
});

const isCreateModalOpen = ref(false);

const form = useForm({
  code: '',
  name: '',
  transaction_type_id: '',
  version: 'v1.0',
});

const submitTemplate = () => {
  form.post('/admin/submission-templates', {
    onSuccess: () => {
      isCreateModalOpen.value = false;
      form.reset();
    },
  });
};
</script>

<template>
  <AppLayout title="Format &amp; Template Pengajuan">
    <div class="space-y-6 max-w-6xl mx-auto">
      <!-- Header -->
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
            <FileText class="w-6 h-6 text-sky-600" />
            Format Pengajuan &amp; Template Form
          </h2>
          <p class="text-xs text-slate-500">Konfigurasi dinamis skema field usulan belanja dan jenis transaksi belanja.</p>
        </div>

        <button 
          @click="isCreateModalOpen = true" 
          class="px-4 py-2.5 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-md shadow-sky-600/20"
        >
          <Plus class="w-4 h-4" />
          Tambah Format Baru
        </button>
      </div>

      <!-- Templates Table -->
      <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden p-6 space-y-4">
        <div class="overflow-x-auto">
          <table class="w-full text-xs text-left">
            <thead class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider text-[10px] border-y border-slate-200">
              <tr>
                <th class="py-2.5 px-3">Kode Format</th>
                <th class="py-2.5 px-3">Nama Template Pengajuan</th>
                <th class="py-2.5 px-3">Mekanisme Transaksi</th>
                <th class="py-2.5 px-3 text-center">Versi</th>
                <th class="py-2.5 px-3 text-center">Jumlah Field</th>
                <th class="py-2.5 px-3 text-center">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="t in templates.data" :key="t.id" class="hover:bg-slate-50/60 transition">
                <td class="py-3.5 px-3 font-sans font-bold text-sky-700">{{ t.code }}</td>
                <td class="py-3.5 px-3 font-bold text-slate-900">{{ t.name }}</td>
                <td class="py-3.5 px-3 font-semibold text-slate-700">{{ t.transaction_type?.name || 'Semua Transaksi' }}</td>
                <td class="py-3.5 px-3 text-center font-sans">{{ t.version }}</td>
                <td class="py-3.5 px-3 text-center font-sans font-bold text-slate-800">{{ t.fields?.length || 0 }} Field</td>
                <td class="py-3.5 px-3 text-center">
                  <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-bold border uppercase', t.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200']">
                    {{ t.is_active ? 'Aktif' : 'Non-Aktif' }}
                  </span>
                </td>
              </tr>
              <tr v-if="templates.data.length === 0">
                <td colspan="6" class="py-8 text-center text-slate-400 text-xs">Belum ada format template yang dikonfigurasi.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <div v-if="isCreateModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="max-w-md w-full bg-white rounded-3xl border border-slate-200 shadow-2xl overflow-hidden p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="font-bold text-slate-900 text-base">Tambah Format Pengajuan</h3>
          <button @click="isCreateModalOpen = false" class="p-1 text-slate-400 hover:text-slate-700 rounded-lg">
            <X class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="submitTemplate" class="space-y-4 text-xs">
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Kode Template (Unik)</label>
            <input v-model="form.code" type="text" required placeholder="TPL_BELANJA_LS" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl font-sans uppercase focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none">
          </div>
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Nama Format Pengajuan</label>
            <input v-model="form.name" type="text" required placeholder="Format Pengajuan Belanja LS..." class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none">
          </div>
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Jenis Transaksi</label>
            <select v-model="form.transaction_type_id" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none">
              <option value="">Semua Transaksi (Universal)</option>
              <option v-for="tt in transactionTypes" :key="tt.id" :value="tt.id">{{ tt.code }} - {{ tt.name }}</option>
            </select>
          </div>
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Versi</label>
            <input v-model="form.version" type="text" placeholder="v1.0" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl font-sans focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none">
          </div>

          <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
            <button type="button" @click="isCreateModalOpen = false" class="px-4 py-2 bg-slate-100 rounded-xl font-semibold">Batal</button>
            <button type="submit" :disabled="form.processing" class="px-5 py-2 bg-sky-600 text-white font-bold rounded-xl shadow-md">Simpan Format</button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
