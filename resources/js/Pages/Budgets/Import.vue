<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { 
  Upload, 
  Download, 
  FileSpreadsheet, 
  CheckCircle2, 
  AlertTriangle, 
  Clock, 
  ArrowRight,
  FileCheck,
  Layers,
  Database,
  Info,
  Calendar
} from 'lucide-vue-next';

const props = defineProps({
  histories: Object,
  fiscalYears: Array,
  fundingSources: Array,
  budgetVersions: Array,
  activeFiscalYear: Object,
  activeVersion: Object,
  departments: Array,
});

const form = useForm({
  file: null,
  fiscal_year: props.activeFiscalYear?.year || 2026,
  funding_source_code: 'RM',
  revision_no: props.activeVersion?.revision_no || 'Rev 02',
  notes: '',
});

const isDragging = ref(false);
const selectedFileName = ref('');

const onFileChange = (e) => {
  const file = e.target.files[0] || null;
  form.file = file;
  selectedFileName.value = file ? file.name : '';
};

const onDrop = (e) => {
  isDragging.value = false;
  const file = e.dataTransfer.files[0] || null;
  if (file) {
    form.file = file;
    selectedFileName.value = file.name;
  }
};

const submitUpload = () => {
  form.post('/budgets-import', {
    onSuccess: () => {
      form.reset('file');
      selectedFileName.value = '';
    },
  });
};

const getStatusBadge = (st) => {
  switch (st) {
    case 'COMMITTED': return { label: 'Committed', class: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
    case 'CANCELLED': return { label: 'Dibatalkan', class: 'bg-rose-50 text-rose-700 border-rose-200' };
    default: return { label: 'Staging / Pending', class: 'bg-amber-50 text-amber-700 border-amber-200' };
  }
};
</script>

<template>
  <AppLayout title="Import Master Pagu Anggaran (DIPA FT UNSOED)">
    <div class="space-y-6 font-sans">
      
      <!-- Top Title Bar -->
      <div class="bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <span class="px-2.5 py-0.5 bg-sky-100 text-sky-800 text-[10px] font-black rounded-lg uppercase tracking-wider">
              Pipeline Import Pagu
            </span>
            <span class="text-xs text-slate-400 font-semibold">&bull; 6-Step Ingestion Pipeline</span>
          </div>
          <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight mt-1">
            Import Master Pagu Anggaran
          </h1>
          <p class="text-xs text-slate-500 mt-0.5">
            Mekanisme utama ingest &amp; staging alokasi pagu resmi DIPA RKAKL ke basis data SIKARA Fakultas Teknik.
          </p>
        </div>

        <div class="flex items-center gap-2">
          <Link 
            href="/budgets" 
            class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-2xl transition shadow-sm"
          >
            Lihat Master Pagu Aktif
          </Link>
        </div>
      </div>

      <!-- 6-Step Pipeline Visual Overview -->
      <div class="bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">
          Alur 6 Langkah Import Pagu (Safe Staging Protocol)
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
          <!-- Step 1 -->
          <div class="p-3.5 rounded-2xl bg-sky-50 border border-sky-200 space-y-1">
            <div class="flex items-center justify-between">
              <span class="text-[10px] font-black text-sky-700 uppercase">Step 1</span>
              <Upload class="w-4 h-4 text-sky-600" />
            </div>
            <div class="text-xs font-bold text-slate-900">Upload Berkas</div>
            <div class="text-[10px] text-slate-500">xlsx, csv, ods, pdf</div>
          </div>

          <!-- Step 2 -->
          <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-1">
            <div class="flex items-center justify-between">
              <span class="text-[10px] font-black text-slate-400 uppercase">Step 2</span>
              <Calendar class="w-4 h-4 text-slate-400" />
            </div>
            <div class="text-xs font-bold text-slate-900">File Info</div>
            <div class="text-[10px] text-slate-500">TA, Sumber, Revisi</div>
          </div>

          <!-- Step 3 -->
          <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-1">
            <div class="flex items-center justify-between">
              <span class="text-[10px] font-black text-slate-400 uppercase">Step 3</span>
              <FileSpreadsheet class="w-4 h-4 text-slate-400" />
            </div>
            <div class="text-xs font-bold text-slate-900">Preview Data</div>
            <div class="text-[10px] text-slate-500">Inspeksi Baris Berkas</div>
          </div>

          <!-- Step 4 -->
          <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-1">
            <div class="flex items-center justify-between">
              <span class="text-[10px] font-black text-slate-400 uppercase">Step 4</span>
              <FileCheck class="w-4 h-4 text-slate-400" />
            </div>
            <div class="text-xs font-bold text-slate-900">Validation</div>
            <div class="text-[10px] text-slate-500">Uji Akun &amp; Jurusan</div>
          </div>

          <!-- Step 5 -->
          <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-1">
            <div class="flex items-center justify-between">
              <span class="text-[10px] font-black text-slate-400 uppercase">Step 5</span>
              <Layers class="w-4 h-4 text-slate-400" />
            </div>
            <div class="text-xs font-bold text-slate-900">Mapping</div>
            <div class="text-[10px] text-slate-500">7 Segmen RKAKL</div>
          </div>

          <!-- Step 6 -->
          <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-1">
            <div class="flex items-center justify-between">
              <span class="text-[10px] font-black text-slate-400 uppercase">Step 6</span>
              <Database class="w-4 h-4 text-slate-400" />
            </div>
            <div class="text-xs font-bold text-slate-900">Commit Batch</div>
            <div class="text-[10px] text-slate-500">Masuk Pagu Aktif</div>
          </div>
        </div>
      </div>

      <!-- Upload Form & Template Cards -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Upload & File Info Form (Steps 1 & 2) -->
        <div class="bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4 lg:col-span-1">
          <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
              <Upload class="w-4 h-4 text-sky-600" />
              <span>Unggah Berkas Pagu (Step 1 &amp; 2)</span>
            </h3>
          </div>

          <form @submit.prevent="submitUpload" class="space-y-4 text-xs">
            <!-- Step 1: File Upload Drag & Drop Area -->
            <div>
              <label class="block font-bold text-slate-700 mb-1.5">Pilih Berkas Spreadsheet / Dokumen</label>
              
              <div 
                @dragover.prevent="isDragging = true" 
                @dragleave.prevent="isDragging = false" 
                @drop.prevent="onDrop" 
                :class="[
                  'p-5 border-2 border-dashed rounded-2xl text-center cursor-pointer transition flex flex-col items-center justify-center gap-2 relative',
                  isDragging ? 'border-sky-500 bg-sky-50/50' : 'border-slate-300 hover:border-sky-400 bg-slate-50/50'
                ]"
              >
                <input 
                  type="file" 
                  accept=".xlsx,.xls,.csv,.ods,.pdf,.doc,.docx,.txt" 
                  @change="onFileChange" 
                  required 
                  class="absolute inset-0 opacity-0 cursor-pointer w-full h-full" 
                />
                
                <div class="p-2.5 bg-white shadow-sm border border-slate-200 rounded-xl text-sky-600">
                  <FileSpreadsheet class="w-6 h-6" />
                </div>
                
                <div>
                  <p class="font-bold text-slate-800 text-xs">
                    {{ selectedFileName || 'Klik atau seret berkas ke sini' }}
                  </p>
                  <p class="text-[10px] text-slate-400 mt-0.5">Format didukung: xlsx, xls, csv, ods, pdf, docs</p>
                </div>
              </div>
            </div>

            <!-- Supported Badges -->
            <div class="flex flex-wrap items-center gap-1">
              <span class="px-2 py-0.5 bg-slate-100 text-slate-700 text-[10px] font-bold rounded-md">.XLSX</span>
              <span class="px-2 py-0.5 bg-slate-100 text-slate-700 text-[10px] font-bold rounded-md">.XLS</span>
              <span class="px-2 py-0.5 bg-slate-100 text-slate-700 text-[10px] font-bold rounded-md">.CSV</span>
              <span class="px-2 py-0.5 bg-slate-100 text-slate-700 text-[10px] font-bold rounded-md">.ODS</span>
              <span class="px-2 py-0.5 bg-slate-100 text-slate-700 text-[10px] font-bold rounded-md">.PDF / .DOCS</span>
            </div>

            <!-- Step 2: File Information Parameters -->
            <div class="space-y-3 pt-3 border-t border-slate-100">
              <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                Step 2: File Metadata
              </div>

              <!-- TA -->
              <div>
                <label class="block font-semibold text-slate-700 mb-1">Tahun Anggaran (TA)</label>
                <select v-model="form.fiscal_year" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:ring-2 focus:ring-sky-500">
                  <option v-for="fy in fiscalYears" :key="fy.id" :value="fy.year">
                    TA {{ fy.year }}
                  </option>
                </select>
              </div>

              <!-- Sumber Dana -->
              <div>
                <label class="block font-semibold text-slate-700 mb-1">Sumber Dana</label>
                <select v-model="form.funding_source_code" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:ring-2 focus:ring-sky-500">
                  <option v-for="fs in fundingSources" :key="fs.id" :value="fs.code">
                    {{ fs.code }} &mdash; {{ fs.name }}
                  </option>
                </select>
              </div>

              <!-- Revision -->
              <div>
                <label class="block font-semibold text-slate-700 mb-1">Versi Revisi Pagu</label>
                <select v-model="form.revision_no" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:ring-2 focus:ring-sky-500">
                  <option v-for="bv in budgetVersions" :key="bv.id" :value="bv.revision_no">
                    {{ bv.revision_no }} ({{ bv.status }})
                  </option>
                </select>
              </div>
            </div>

            <button 
              type="submit" 
              :disabled="form.processing || !form.file" 
              class="w-full py-2.5 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-bold flex items-center justify-center gap-2 transition disabled:opacity-50 shadow-md shadow-sky-600/20"
            >
              <Upload class="w-4 h-4" />
              <span>Unggah ke Staging Batch</span>
            </button>
          </form>
        </div>

        <!-- Template Downloads & Safe Staging Rules -->
        <div class="bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4 lg:col-span-2 flex flex-col justify-between">
          <div class="space-y-4">
            <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
              <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <FileSpreadsheet class="w-4 h-4 text-sky-600" />
                <span>Template Resmi &amp; Pedoman Struktur</span>
              </h3>
              <span class="text-[11px] text-slate-400 font-semibold">Acuan SIMAPAN / RKAKL</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
              <!-- Template 1: SIMAPAN 23 Kolom -->
              <div class="p-4 rounded-2xl border border-sky-200 bg-sky-50/50 space-y-2">
                <div class="flex items-center justify-between">
                  <span class="font-bold text-sky-900">Format SIMAPAN 23-Kolom</span>
                  <span class="px-2 py-0.5 bg-sky-100 text-sky-800 text-[10px] font-bold rounded">Resmi</span>
                </div>
                <p class="text-slate-600 text-[11px]">
                  Format lengkap ekspor RKAKL / SIMAPAN dengan 7 segmen hierarki (Program, Kegiatan, KRO, RO, Komponen, Subkomponen, Akun).
                </p>
                <a 
                  href="/budgets-import/template?schema=simapan" 
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-[11px] font-bold transition shadow-sm"
                >
                  <Download class="w-3.5 h-3.5" />
                  <span>Unduh Template SIMAPAN (.CSV)</span>
                </a>
              </div>

              <!-- Template 2: Compact Standard -->
              <div class="p-4 rounded-2xl border border-slate-200 bg-slate-50/50 space-y-2">
                <div class="flex items-center justify-between">
                  <span class="font-bold text-slate-900">Format Standar 6-Kolom</span>
                  <span class="px-2 py-0.5 bg-slate-200 text-slate-700 text-[10px] font-bold rounded">Ringkas</span>
                </div>
                <p class="text-slate-600 text-[11px]">
                  Format cepat untuk update pagu per unit jurusan dengan kolom: <code class="font-sans font-bold">KODE_JURUSAN, TAHUN, KODE_SUMBER, KODE_AKUN, NAMA_AKUN, PAGU_AWAL</code>.
                </p>
                <a 
                  href="/budgets-import/template?schema=compact" 
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-[11px] font-bold transition shadow-sm"
                >
                  <Download class="w-3.5 h-3.5" />
                  <span>Unduh Template Compact (.CSV)</span>
                </a>
              </div>
            </div>

            <!-- Staging Safety Notice -->
            <div class="p-4 bg-amber-50/70 border border-amber-200 rounded-2xl flex items-start gap-3 text-xs text-amber-950">
              <Info class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" />
              <p class="leading-relaxed">
                <strong class="font-bold">Protokol Keamanan Data Staging:</strong> Berkas yang diunggah tidak akan langsung masuk ke data pagu aktif. Sistem akan melakukan validasi kode mata anggaran, kecocokan jurusan, dan memetakan segmen RKAKL terlebih dahulu pada tabel staging batch.
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Staging Batch Histories Table (Metadata Display) -->
      <div class="bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
          <div>
            <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
              <History class="w-4 h-4 text-sky-600" />
              <span>Riwayat Batch Staging &amp; Import Pagu</span>
            </h3>
            <p class="text-xs text-slate-500">Daftar batch upload, identifikasi import_batch_id, status validasi, dan log commit.</p>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs font-sans">
            <thead>
              <tr class="border-b border-slate-200 text-slate-400 uppercase tracking-wider text-[10px]">
                <th class="py-2.5 px-3 font-semibold">Import Batch ID</th>
                <th class="py-2.5 px-3 font-semibold">Nama File</th>
                <th class="py-2.5 px-3 font-semibold">Pengunggah (Uploader)</th>
                <th class="py-2.5 px-3 font-semibold">Tanggal Upload</th>
                <th class="py-2.5 px-3 text-center font-semibold">Total Rows</th>
                <th class="py-2.5 px-3 text-center font-semibold">Valid / Invalid</th>
                <th class="py-2.5 px-3 text-center font-semibold">Status</th>
                <th class="py-2.5 px-3 text-center font-semibold">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="h in histories.data" :key="h.id" class="hover:bg-slate-50/70 transition">
                <!-- Batch ID -->
                <td class="py-3 px-3 font-bold text-sky-800 font-sans">
                  {{ h.import_batch_id || ('BATCH-' + h.id) }}
                </td>

                <!-- Nama File -->
                <td class="py-3 px-3 font-medium text-slate-900 max-w-xs truncate" :title="h.filename">
                  {{ h.filename }}
                </td>

                <!-- Uploader -->
                <td class="py-3 px-3 text-slate-700">
                  {{ h.user?.name || 'Administrator' }}
                </td>

                <!-- Tanggal Upload -->
                <td class="py-3 px-3 text-slate-500 whitespace-nowrap">
                  {{ new Date(h.created_at).toLocaleString('id-ID') }}
                </td>

                <!-- Total Rows -->
                <td class="py-3 px-3 text-center font-bold text-slate-900">
                  {{ h.total_rows }}
                </td>

                <!-- Valid / Invalid Rows -->
                <td class="py-3 px-3 text-center whitespace-nowrap">
                  <span class="text-emerald-700 font-bold">{{ h.valid_rows }} Valid</span>
                  <span v-if="h.invalid_rows > 0" class="text-rose-600 font-bold ml-1.5">/ {{ h.invalid_rows }} Invalid</span>
                </td>

                <!-- Status -->
                <td class="py-3 px-3 text-center">
                  <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-bold border inline-block uppercase', getStatusBadge(h.status).class]">
                    {{ getStatusBadge(h.status).label }}
                  </span>
                </td>

                <!-- Aksi -->
                <td class="py-3 px-3 text-center">
                  <Link 
                    :href="`/budgets-import/${h.id}`" 
                    class="px-3 py-1.5 bg-sky-50 hover:bg-sky-100 text-sky-700 border border-sky-200 rounded-xl text-[11px] font-bold transition inline-flex items-center gap-1 shadow-sm"
                  >
                    <span>Detail Staging</span>
                    <ArrowRight class="w-3 h-3" />
                  </Link>
                </td>
              </tr>
              <tr v-if="!histories.data || histories.data.length === 0">
                <td colspan="8" class="py-8 text-center text-slate-400">
                  Belum ada berkas pagu anggaran yang diunggah.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
