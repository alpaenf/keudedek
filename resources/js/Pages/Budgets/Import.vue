<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { Upload, Download, FileSpreadsheet, CheckCircle2, AlertTriangle, Clock } from '@lucide/vue';

const props = defineProps({
  histories: Object,
});

const form = useForm({
  file: null,
});

const onFileChange = (e) => {
  form.file = e.target.files[0] || null;
};

const submitUpload = () => {
  form.post('/budgets-import', {
    onSuccess: () => {
      form.reset('file');
    },
  });
};

const getStatusBadge = (st) => {
  switch (st) {
    case 'COMMITTED': return 'bg-emerald-100 text-emerald-800 border-emerald-300';
    case 'CANCELLED': return 'bg-rose-100 text-rose-800 border-rose-300';
    default: return 'bg-amber-100 text-amber-800 border-amber-300';
  }
};
</script>

<template>
  <AppLayout title="Import & Staging Pagu Anggaran">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
      <!-- Upload Card -->
      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm lg:col-span-1">
        <div class="flex items-center gap-3 mb-4">
          <div class="p-2.5 bg-sky-100 text-sky-700 rounded-xl">
            <FileSpreadsheet class="w-6 h-6" />
          </div>
          <div>
            <h3 class="font-bold text-slate-900 text-base">Unggah Berkas Pagu</h3>
            <p class="text-xs text-slate-500">Upload CSV / Excel pagu anggaran</p>
          </div>
        </div>

        <form @submit.prevent="submitUpload" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Berkas (.csv / .xlsx)</label>
            <input type="file" accept=".csv,.xlsx,.txt" @change="onFileChange" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs text-slate-900 bg-slate-50 focus:ring-2 focus:ring-sky-500">
          </div>

          <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs space-y-1">
            <span class="font-bold text-slate-900 block mb-1">Panduan Format File:</span>
            <p class="text-slate-600">Kolom wajib: <code class="bg-slate-200 px-1 py-0.5 rounded font-sans text-[10px]">KODE_JURUSAN, TAHUN, KODE_SUMBER, KODE_AKUN, NAMA_AKUN, PAGU_AWAL</code></p>
            <a href="/budgets-import/template" class="inline-flex items-center gap-1 font-bold text-sky-600 hover:text-sky-700 mt-2">
              <Download class="w-3.5 h-3.5" /> Unduh Format Template CSV
            </a>
          </div>

          <button type="submit" :disabled="form.processing || !form.file" class="w-full py-2.5 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-semibold flex items-center justify-center gap-2 transition disabled:opacity-50 shadow-sm">
            <Upload class="w-4 h-4" /> Unggah ke Staging
          </button>
        </form>
      </div>

      <!-- History Table -->
      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm lg:col-span-2">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
          <div>
            <h3 class="font-bold text-slate-900 text-base">Riwayat Import &amp; Staging</h3>
            <p class="text-xs text-slate-500">Daftar berkas pagu yang pernah diunggah &amp; diverifikasi</p>
          </div>
        </div>

        <div class="border border-slate-200 rounded-xl overflow-hidden text-xs">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-200">
                <th class="py-3 px-4">Nama Berkas</th>
                <th class="py-3 px-4 text-center">Total Baris</th>
                <th class="py-3 px-4 text-center">Valid / Error</th>
                <th class="py-3 px-4 text-center">Status</th>
                <th class="py-3 px-4 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-900">
              <tr v-for="h in histories.data" :key="h.id" class="hover:bg-slate-50/80 transition">
                <td class="py-3 px-4">
                  <span class="font-semibold block text-slate-900">{{ h.filename }}</span>
                  <span class="text-[10px] text-slate-500">{{ new Date(h.created_at).toLocaleString('id-ID') }} &bull; Oleh {{ h.user?.name }}</span>
                </td>
                <td class="py-3 px-4 text-center font-bold text-slate-900">{{ h.total_rows }}</td>
                <td class="py-3 px-4 text-center whitespace-nowrap">
                  <span class="text-emerald-700 font-bold">{{ h.valid_rows }} Valid</span>
                  <span v-if="h.invalid_rows > 0" class="text-rose-600 font-bold ml-1.5">/ {{ h.invalid_rows }} Invalid</span>
                </td>
                <td class="py-3 px-4 text-center">
                  <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border', getStatusBadge(h.status)]">{{ h.status }}</span>
                </td>
                <td class="py-3 px-4 text-right">
                  <Link :href="`/budgets-import/${h.id}`" class="px-3 py-1 bg-sky-50 text-sky-700 border border-sky-200 rounded-lg text-xs font-semibold hover:bg-sky-100 transition inline-block">
                    Detail Staging
                  </Link>
                </td>
              </tr>
              <tr v-if="!histories.data.length">
                <td colspan="5" class="py-8 text-center text-slate-500">Belum ada riwayat berkas import yang diunggah.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
