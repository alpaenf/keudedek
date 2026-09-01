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
  Layers,
  Sparkles,
  Info,
  Building2,
  FileCheck,
  ShieldCheck,
  Check
} from 'lucide-vue-next';

const props = defineProps({
  batches: Object,
  departments: Array,
  activeFiscalYear: [String, Number],
  activeFundingSource: Object,
  userDepartmentId: [String, Number],
  userRole: String,
});

const form = useForm({
  file: null,
  fiscal_year_id: '',
  department_id: props.userDepartmentId || '',
});

const fileName = ref('');
const isDragging = ref(false);

const onFileChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    form.file = file;
    fileName.value = file.name;
  }
};

const onDrop = (e) => {
  isDragging.value = false;
  const file = e.dataTransfer.files[0];
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
  <AppLayout title="Import Transaksi Belanja Masal (Bulk Ingestion)">
    <div class="max-w-5xl mx-auto space-y-6 font-sans">
      
      <!-- Top Title & Context Bar -->
      <div class="bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <Link href="/submissions" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-sky-600 transition mb-1.5">
            <ArrowLeft class="w-4 h-4" /> Kembali ke Daftar Transaksi
          </Link>
          <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
            <FileSpreadsheet class="w-6 h-6 text-sky-600" />
            Import Transaksi Belanja (Bulk Ingestion)
          </h1>
          <p class="text-xs text-slate-500 mt-0.5">
            Unggah spreadsheet transaksi existing PTK &bull; Pemetaan otomatis ke pos pagu master &bull; Validasi saldo &amp; deteksi duplikasi.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
          <a 
            href="/submissions-import/template" 
            class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 border border-slate-300 text-slate-800 rounded-2xl text-xs font-bold transition flex items-center gap-2 shadow-sm"
          >
            <Download class="w-4 h-4 text-slate-600" />
            <span>Unduh Template CSV / Excel</span>
          </a>
        </div>
      </div>

      <!-- 7-Step Pipeline Visual Progress Bar -->
      <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm">
        <div class="flex items-center justify-between overflow-x-auto gap-2 text-xs py-1">
          <div class="flex items-center gap-2 shrink-0">
            <span class="w-6 h-6 rounded-full bg-sky-600 text-white font-black text-[11px] flex items-center justify-center shadow-sm">1</span>
            <span class="font-bold text-sky-900">Upload</span>
          </div>
          <ArrowRight class="w-3.5 h-3.5 text-slate-300 shrink-0" />
          <div class="flex items-center gap-2 shrink-0 text-slate-400">
            <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-600 font-bold text-[11px] flex items-center justify-center">2</span>
            <span>Staging</span>
          </div>
          <ArrowRight class="w-3.5 h-3.5 text-slate-300 shrink-0" />
          <div class="flex items-center gap-2 shrink-0 text-slate-400">
            <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-600 font-bold text-[11px] flex items-center justify-center">3</span>
            <span>Budget Matching</span>
          </div>
          <ArrowRight class="w-3.5 h-3.5 text-slate-300 shrink-0" />
          <div class="flex items-center gap-2 shrink-0 text-slate-400">
            <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-600 font-bold text-[11px] flex items-center justify-center">4</span>
            <span>Validation</span>
          </div>
          <ArrowRight class="w-3.5 h-3.5 text-slate-300 shrink-0" />
          <div class="flex items-center gap-2 shrink-0 text-slate-400">
            <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-600 font-bold text-[11px] flex items-center justify-center">5</span>
            <span>Duplicate Detection</span>
          </div>
          <ArrowRight class="w-3.5 h-3.5 text-slate-300 shrink-0" />
          <div class="flex items-center gap-2 shrink-0 text-slate-400">
            <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-600 font-bold text-[11px] flex items-center justify-center">6</span>
            <span>Preview</span>
          </div>
          <ArrowRight class="w-3.5 h-3.5 text-slate-300 shrink-0" />
          <div class="flex items-center gap-2 shrink-0 text-slate-400">
            <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-600 font-bold text-[11px] flex items-center justify-center">7</span>
            <span>Commit</span>
          </div>
        </div>
      </div>

      <!-- Upload Zone Card & Minimal Mapping Info -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Upload Form (2 Cols) -->
        <div class="lg:col-span-2 bg-white p-6 sm:p-7 rounded-3xl border border-slate-200/80 shadow-sm space-y-5">
          <div class="border-b border-slate-100 pb-3">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
              <UploadCloud class="w-4 h-4 text-sky-600" />
              <span>Unggah Berkas Spreadsheet Transaksi</span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">Mendukung format .xlsx, .xls, .csv, .ods, dan .tsv.</p>
          </div>

          <form @submit.prevent="submitUpload" class="space-y-5">
            <!-- Drag & Drop Zone -->
            <div 
              @dragover.prevent="isDragging = true"
              @dragleave.prevent="isDragging = false"
              @drop.prevent="onDrop"
              :class="[
                'border-2 border-dashed rounded-3xl p-8 text-center transition group cursor-pointer relative',
                isDragging ? 'border-sky-500 bg-sky-50/60 ring-2 ring-sky-500/20' : 'border-slate-300 hover:border-sky-500 bg-slate-50/60 hover:bg-sky-50/30'
              ]"
            >
              <input 
                type="file" 
                accept=".csv,.txt,.tsv,.xlsx,.xls,.ods"
                @change="onFileChange"
                required
                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
              />
              <div class="w-12 h-12 rounded-2xl bg-sky-100 text-sky-600 flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition shadow-sm">
                <UploadCloud class="w-6 h-6" />
              </div>
              <div class="text-sm font-bold text-slate-900">
                {{ fileName || 'Klik atau tarik berkas spreadsheet transaksi ke area ini' }}
              </div>
              <p class="text-xs text-slate-500 mt-1">Ukuran maksimal berkas 20MB.</p>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between text-xs pt-2">
              <span class="text-slate-400 font-medium">Safe Staging: Data tidak langsung memotong saldo sebelum di-commit.</span>
              <button 
                type="submit" 
                :disabled="form.processing || !form.file"
                class="px-6 py-2.5 bg-sky-600 hover:bg-sky-500 text-white font-bold rounded-2xl transition flex items-center gap-2 shadow-md shadow-sky-600/20 disabled:opacity-50"
              >
                <UploadCloud class="w-4 h-4" />
                <span>Unggah &amp; Proses Staging</span>
              </button>
            </div>
          </form>
        </div>

        <!-- Smart Minimal Mapping & Auto Hierarchy Explanation (1 Col) -->
        <div class="bg-sky-50/70 p-6 rounded-3xl border border-sky-200/80 shadow-sm space-y-4 text-xs">
          <div class="flex items-center gap-2 text-sky-950 font-bold">
            <Sparkles class="w-4 h-4 text-sky-600" />
            <span>Smart Auto-Hierarchy</span>
          </div>

          <div class="space-y-2 text-slate-600">
            <p class="font-medium text-[11px] leading-relaxed">
              SIKARA secara cerdas melengkapi hierarki anggaran <strong>RKAKL DIPA</strong> langsung dari Master Data tanpa mengharuskan spreadsheet membawa seluruh nama parent.
            </p>

            <div class="p-3 bg-white rounded-2xl border border-sky-200 space-y-1.5">
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Kolom Wajib (Minimal Mapping)</span>
              <ul class="space-y-1 text-slate-800 font-bold text-[11px]">
                <li class="flex items-center gap-1.5"><Check class="w-3.5 h-3.5 text-emerald-600" /> nomor_bukti</li>
                <li class="flex items-center gap-1.5"><Check class="w-3.5 h-3.5 text-emerald-600" /> tanggal</li>
                <li class="flex items-center gap-1.5"><Check class="w-3.5 h-3.5 text-emerald-600" /> uraian</li>
                <li class="flex items-center gap-1.5"><Check class="w-3.5 h-3.5 text-emerald-600" /> nominal</li>
                <li class="flex items-center gap-1.5"><Check class="w-3.5 h-3.5 text-emerald-600" /> kode_akun (misal: 521211)</li>
                <li class="flex items-center gap-1.5"><Check class="w-3.5 h-3.5 text-emerald-600" /> jurusan (misal: JTIF)</li>
              </ul>
            </div>

            <div class="p-3 bg-white/70 rounded-2xl border border-sky-100 space-y-1">
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Kolom Opsional (Jika Tersedia)</span>
              <div class="text-[11px] text-slate-700 font-medium">
                subkomponen &bull; budget_control_key &bull; prodi &bull; catatan
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Batch Import History Table -->
      <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
            <Clock class="w-4 h-4 text-sky-600" />
            <span>Riwayat Batch Import Transaksi</span>
          </h3>
          <span class="text-xs text-slate-400 font-semibold">Total {{ batches.total || 0 }} Batch</span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-xs text-left font-sans border-collapse">
            <thead class="bg-slate-50 text-slate-400 uppercase tracking-wider text-[10px] border-y border-slate-200">
              <tr>
                <th class="py-3 px-3.5 font-semibold">Batch ID</th>
                <th class="py-3 px-3 font-semibold">Pengunggah</th>
                <th class="py-3 px-3 text-center font-semibold">Total Baris</th>
                <th class="py-3 px-3 text-center font-semibold">Valid</th>
                <th class="py-3 px-3 text-center font-semibold">Invalid / Duplikat</th>
                <th class="py-3 px-3 text-center font-semibold">Status</th>
                <th class="py-3 px-3 text-center font-semibold">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="b in batches.data" :key="b.id" class="hover:bg-slate-50/70 transition">
                <td class="py-3.5 px-3.5 font-bold text-sky-900">{{ b.batch_number }}</td>
                <td class="py-3.5 px-3 text-slate-700 font-medium">{{ b.user?.name || 'Operator PTK' }}</td>
                <td class="py-3.5 px-3 text-center font-black text-slate-900">{{ b.total_rows }}</td>
                <td class="py-3.5 px-3 text-center font-black text-emerald-700">{{ b.valid_rows }}</td>
                <td class="py-3.5 px-3 text-center font-black text-rose-700">{{ b.invalid_rows }}</td>
                <td class="py-3.5 px-3 text-center">
                  <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-bold border uppercase', b.status === 'COMMITTED' ? 'bg-emerald-50 text-emerald-800 border-emerald-300' : 'bg-amber-50 text-amber-800 border-amber-300']">
                    {{ b.status === 'COMMITTED' ? 'COMMITTED' : 'PENDING STAGING' }}
                  </span>
                </td>
                <td class="py-3.5 px-3 text-center whitespace-nowrap">
                  <Link 
                    :href="`/submissions-import/${b.id}`" 
                    class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-xl text-xs font-bold inline-flex items-center gap-1 transition shadow-sm"
                  >
                    <span>Buka Preview &amp; Commit</span>
                    <ArrowRight class="w-3 h-3" />
                  </Link>
                </td>
              </tr>
              <tr v-if="!batches.data || batches.data.length === 0">
                <td colspan="7" class="py-8 text-center text-slate-400 text-xs">Belum ada riwayat batch import transaksi.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </AppLayout>
</template>
