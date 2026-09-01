<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { 
  FileSpreadsheet, 
  UploadCloud, 
  Download, 
  ArrowLeft, 
  CheckCircle2, 
  Clock, 
  ArrowRight,
  Layers
} from 'lucide-vue-next';

const props = defineProps({
  batches: Object,
});

const form = useForm({
  file: null,
});

const fileName = ref('');

const onFileChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    form.file = file;
    fileName.value = file.name;
  }
};

const submitUpload = () => {
  form.post('/submissions-import', {
    onSuccess: () => {
      form.reset();
      fileName.value = '';
    },
  });
};
</script>

<template>
  <AppLayout title="Import Pengajuan Masal (Bulk Upload)">
    <div class="max-w-4xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <Link href="/submissions" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-sky-600 transition mb-2">
            <ArrowLeft class="w-4 h-4" /> Kembali ke Daftar Pengajuan
          </Link>
          <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
            <FileSpreadsheet class="w-6 h-6 text-sky-600" />
            Import Pengajuan Masal (Staging Pipeline)
          </h2>
          <p class="text-xs text-slate-500">Unggah berkas CSV usulan kegiatan belanja untuk diproses melalui validasi bertingkat.</p>
        </div>

        <a 
          href="/submissions-import/template" 
          class="px-4 py-2.5 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-sm"
        >
          <Download class="w-4 h-4 text-slate-500" />
          Unduh Format Template CSV
        </a>
      </div>

      <!-- Upload Form Card -->
      <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-sm space-y-6">
        <form @submit.prevent="submitUpload" class="space-y-6">
          <div class="border-2 border-dashed border-slate-300 hover:border-sky-500 rounded-3xl p-8 text-center bg-slate-50/60 hover:bg-sky-50/40 transition group cursor-pointer relative">
            <input 
              type="file" 
              accept=".csv,.txt"
              @change="onFileChange"
              required
              class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
            >
            <div class="w-12 h-12 rounded-2xl bg-sky-100 text-sky-600 flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition">
              <UploadCloud class="w-6 h-6" />
            </div>
            <div class="text-sm font-bold text-slate-900">
              {{ fileName || 'Klik atau tarik berkas CSV pengajuan ke sini' }}
            </div>
            <p class="text-xs text-slate-500 mt-1">Mendukung berkas format .CSV (UTF-8) dengan ukuran maksimal 10MB.</p>
          </div>

          <div class="flex items-center justify-between text-xs">
            <span class="text-slate-400">Pastikan kode jurusan & kode akun sesuai master data</span>
            <button 
              type="submit" 
              :disabled="form.processing || !form.file"
              class="px-6 py-2.5 bg-sky-600 hover:bg-sky-500 text-white font-bold rounded-xl transition flex items-center gap-1.5 shadow-md shadow-sky-600/20 disabled:opacity-50"
            >
              <UploadCloud class="w-4 h-4" /> Unggah &amp; Validasi Staging
            </button>
          </div>
        </form>
      </div>

      <!-- Import History Table -->
      <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden p-6 space-y-4">
        <h3 class="text-sm font-bold text-slate-900">Riwayat Batch Import Pengajuan</h3>

        <div class="overflow-x-auto">
          <table class="w-full text-xs text-left">
            <thead class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider text-[10px] border-y border-slate-200">
              <tr>
                <th class="py-2.5 px-3">Batch Number</th>
                <th class="py-2.5 px-3">Pengunggah</th>
                <th class="py-2.5 px-3 text-center">Total Baris</th>
                <th class="py-2.5 px-3 text-center">Valid</th>
                <th class="py-2.5 px-3 text-center">Invalid</th>
                <th class="py-2.5 px-3 text-center">Status</th>
                <th class="py-2.5 px-3 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-sans">
              <tr v-for="b in batches.data" :key="b.id" class="hover:bg-slate-50/60 transition font-sans">
                <td class="py-3 px-3 font-bold font-sans text-sky-700">{{ b.batch_number }}</td>
                <td class="py-3 px-3">{{ b.user?.name }}</td>
                <td class="py-3 px-3 text-center font-sans font-bold">{{ b.total_rows }}</td>
                <td class="py-3 px-3 text-center font-sans font-bold text-emerald-700">{{ b.valid_rows }}</td>
                <td class="py-3 px-3 text-center font-sans font-bold text-rose-700">{{ b.invalid_rows }}</td>
                <td class="py-3 px-3 text-center">
                  <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-bold border uppercase', b.status === 'COMMITTED' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200']">
                    {{ b.status }}
                  </span>
                </td>
                <td class="py-3 px-3 text-center">
                  <Link 
                    :href="`/submissions-import/${b.id}`" 
                    class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-lg text-xs font-semibold inline-flex items-center gap-1 transition"
                  >
                    Detail &rarr;
                  </Link>
                </td>
              </tr>
              <tr v-if="batches.data.length === 0">
                <td colspan="7" class="py-8 text-center text-slate-400 text-xs">Belum ada riwayat batch import pengajuan.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
