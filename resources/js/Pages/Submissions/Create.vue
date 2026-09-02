<script setup>
import { ref, computed, onMounted } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { 
  Search,
  ArrowLeft, 
  CheckCircle2, 
  Wallet, 
  FileText, 
  UploadCloud, 
  ShieldCheck, 
  AlertTriangle, 
  Building2,
  Calendar,
  Layers,
  ChevronDown,
  ChevronUp,
  Info,
  Check,
  XCircle,
  Clock,
  Sparkles,
  Paperclip
} from 'lucide-vue-next';

const props = defineProps({
  departments: Array,
  studyPrograms: Array,
  buckets: Array,
  transactionTypes: Array,
  documentTypes: Array,
  activeFiscalYear: [String, Number],
  activeVersion: Object,
  activeFundingSource: Object,
  userDepartmentId: [String, Number],
  userStudyProgramId: [String, Number],
  userRole: String,
});

const searchQuery = ref('');
const isStructureExpanded = ref(true);

const today = new Date().toISOString().split('T')[0];

const form = useForm({
  budget_bucket_id: props.buckets?.[0]?.id ?? '',
  evidence_number: '',
  transaction_date: today,
  title: '',
  amount: null,
  department_id: props.userDepartmentId || (props.departments?.[0]?.id ?? ''),
  study_program_id: props.userStudyProgramId || '',
  notes: '',
  submit_action: 'PROCESSING', // 'DRAFT' | 'PROCESSING'
  documents: {}, // document_type_id -> File
});

// Auto-fill evidence number prefix on mount
onMounted(() => {
  const currentDept = props.departments?.find(d => d.id === form.department_id);
  const deptCode = currentDept ? currentDept.code : 'FT';
  const monthStr = String(new Date().getMonth() + 1).padStart(2, '0');
  const yearStr = new Date().getFullYear();
  const randNo = String(Math.floor(Math.random() * 900) + 100);
  form.evidence_number = `BKT/${deptCode}/${yearStr}/${monthStr}/${randNo}`;
});

// Filtered buckets based on quick search query across codes, names, activities, subcomponents
const filteredBuckets = computed(() => {
  if (!props.buckets) return [];
  if (!searchQuery.value.trim()) return props.buckets;

  const q = searchQuery.value.toLowerCase();
  return props.buckets.filter(b => 
    b.account_code?.toLowerCase().includes(q) ||
    b.account_name?.toLowerCase().includes(q) ||
    b.activity_name?.toLowerCase().includes(q) ||
    b.activity_code?.toLowerCase().includes(q) ||
    b.kro_code?.toLowerCase().includes(q) ||
    b.kro_name?.toLowerCase().includes(q) ||
    b.ro_code?.toLowerCase().includes(q) ||
    b.subcomponent_code?.toLowerCase().includes(q) ||
    b.subcomponent_name?.toLowerCase().includes(q) ||
    b.department_code?.toLowerCase().includes(q)
  );
});

// Selected bucket object with full context
const selectedBucket = computed(() => {
  return props.buckets?.find(b => b.id === Number(form.budget_bucket_id));
});

const selectBucket = (bucketId) => {
  form.budget_bucket_id = bucketId;
};

// Real-time Solvency & Budget Check
const currentAvailableBalance = computed(() => {
  return Number(selectedBucket.value?.available_balance) || 0;
});

const inputAmountNumber = computed(() => {
  return Number(form.amount) || 0;
});

const projectedBalance = computed(() => {
  return currentAvailableBalance.value - inputAmountNumber.value;
});

const isSolvent = computed(() => {
  if (!selectedBucket.value) return false;
  if (inputAmountNumber.value <= 0) return true;
  return projectedBalance.value >= 0;
});

const isOverbudget = computed(() => {
  if (!selectedBucket.value || inputAmountNumber.value <= 0) return false;
  return projectedBalance.value < 0;
});

const shortfallAmount = computed(() => {
  if (!isOverbudget.value) return 0;
  return Math.abs(projectedBalance.value);
});

// File upload handling
const uploadedFileNames = ref([]);
const onFileSelected = (e) => {
  const files = e.target.files;
  if (files && files.length > 0) {
    form.documents[1] = files[0];
    uploadedFileNames.value = [files[0].name];
  }
};

const submitTransaction = (action = 'PROCESSING') => {
  form.submit_action = action;
  form.post('/submissions', {
    preserveScroll: true,
  });
};

const formatRupiah = (val) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(val || 0);
};
</script>

<template>
  <AppLayout title="Catat Transaksi Belanja PTK">
    <div class="max-w-4xl mx-auto space-y-6 font-sans">
      
      <!-- Top Title & Context Bar -->
      <div class="bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <Link href="/submissions" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-sky-600 transition mb-1.5">
            <ArrowLeft class="w-4 h-4" /> Kembali ke Daftar Transaksi
          </Link>
          <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
            Catat Transaksi Belanja PTK
          </h1>
          <p class="text-xs text-slate-500 mt-0.5">
            Pencatatan realisasi dengan input minimum. Pilih pos anggaran aktif &bull; struktur kode otomatis terisi.
          </p>
        </div>

        <!-- Automatic Context Badge -->
        <div class="flex items-center gap-2 bg-sky-50 border border-sky-200/80 px-4 py-2 rounded-2xl text-xs shadow-sm shrink-0">
          <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
          <span class="font-extrabold text-sky-950">
            TA {{ activeFiscalYear }} &bull; {{ activeFundingSource?.code || 'RM' }} &bull; {{ activeVersion?.revision_no || 'Rev 02' }} &bull; {{ selectedBucket?.department_code || 'JTIF' }}
          </span>
        </div>
      </div>

      <form @submit.prevent="submitTransaction('PROCESSING')" class="space-y-6">

        <!-- ================================================== -->
        <!-- STEP 1 — PILIH POS ANGGARAN                        -->
        <!-- ================================================== -->
        <div class="bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
              <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <span class="px-2 py-0.5 bg-sky-100 text-sky-800 text-[10px] font-black rounded-md">STEP 1</span>
                <span>Pilih Pos Pagu Anggaran</span>
              </h2>
              <p class="text-xs text-slate-500 mt-0.5">Pilih pos belanja yang akan dipotong tanpa mengetik kode satu-satu.</p>
            </div>
            <span class="text-[11px] font-bold text-slate-400">{{ filteredBuckets.length }} Pos Tersedia</span>
          </div>

          <!-- Search Box -->
          <div class="relative">
            <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
            <input 
              v-model="searchQuery"
              type="text" 
              placeholder="Cari kode akun, nama akun, kegiatan, subkomponen, atau uraian..." 
              class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-300 rounded-2xl text-xs text-slate-900 focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none transition shadow-sm"
            />
          </div>

          <!-- Result Cards List -->
          <div class="grid grid-cols-1 gap-3 max-h-72 overflow-y-auto pr-1">
            <div 
              v-for="b in filteredBuckets" 
              :key="b.id"
              @click="selectBucket(b.id)"
              :class="[
                'p-4 rounded-2xl border transition cursor-pointer flex flex-col md:flex-row md:items-center justify-between gap-4 text-xs',
                form.budget_bucket_id === b.id 
                  ? 'bg-sky-50/90 border-sky-500 shadow-sm ring-2 ring-sky-500/20' 
                  : 'bg-slate-50/50 hover:bg-slate-100/80 border-slate-200'
              ]"
            >
              <!-- Account & RKAKL Hierarchy Line -->
              <div class="space-y-1.5 min-w-0">
                <div class="flex items-center gap-2">
                  <span class="font-black text-sky-950 font-sans text-xs bg-sky-100/80 px-2 py-0.5 rounded border border-sky-200">
                    [{{ b.account_code }}]
                  </span>
                  <span class="font-bold text-slate-900 text-sm truncate">{{ b.account_name }}</span>
                  <span class="px-2 py-0.5 bg-slate-200 text-slate-700 font-bold text-[10px] rounded uppercase">
                    {{ b.department_code }}
                  </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-0.5 text-[11px] text-slate-500">
                  <div class="truncate">
                    <strong class="text-slate-700">Kegiatan:</strong> {{ b.activity_code }} &mdash; {{ b.activity_name }}
                  </div>
                  <div class="truncate">
                    <strong class="text-slate-700">KRO:</strong> {{ b.kro_code }}
                  </div>
                  <div class="truncate">
                    <strong class="text-slate-700">RO:</strong> {{ b.ro_code }} &mdash; {{ b.ro_name }}
                  </div>
                  <div class="truncate">
                    <strong class="text-slate-700">Subkomponen:</strong> {{ b.subcomponent_code }} &mdash; {{ b.subcomponent_name }}
                  </div>
                </div>
              </div>

              <!-- Financial Snapshot Cards & Select Button -->
              <div class="flex items-center justify-between md:justify-end gap-3 sm:gap-4 shrink-0 border-t md:border-t-0 pt-2 md:pt-0 border-slate-200">
                <div class="text-right">
                  <span class="text-[9px] text-slate-400 uppercase font-bold block">Pagu</span>
                  <span class="font-bold text-slate-700 font-sans text-[11px]">{{ formatRupiah(b.allocated_budget) }}</span>
                </div>

                <div class="text-right">
                  <span class="text-[9px] text-amber-700 uppercase font-bold block">Dalam Proses</span>
                  <span class="font-bold text-amber-950 font-sans text-[11px]">{{ formatRupiah(b.reserved_budget) }}</span>
                </div>

                <div class="text-right">
                  <span class="text-[9px] text-sky-700 uppercase font-bold block">Realisasi</span>
                  <span class="font-bold text-sky-950 font-sans text-[11px]">{{ formatRupiah(b.realized_budget) }}</span>
                </div>

                <div class="text-right">
                  <span class="text-[9px] text-emerald-700 uppercase font-bold block">Saldo</span>
                  <span class="font-black text-emerald-800 font-sans text-xs sm:text-sm">{{ formatRupiah(b.available_balance) }}</span>
                </div>

                <!-- Select CTA -->
                <button 
                  type="button" 
                  @click.stop="selectBucket(b.id)"
                  :class="[
                    'px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1 shadow-sm',
                    form.budget_bucket_id === b.id 
                      ? 'bg-sky-600 text-white' 
                      : 'bg-white hover:bg-slate-200 text-slate-700 border border-slate-300'
                  ]"
                >
                  <Check v-if="form.budget_bucket_id === b.id" class="w-3.5 h-3.5" />
                  <span>{{ form.budget_bucket_id === b.id ? 'Terpilih' : 'Pilih' }}</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- ================================================== -->
        <!-- STEP 2 — TAMPILKAN DETAIL KODE (Expandable Section)-->
        <!-- ================================================== -->
        <div v-if="selectedBucket" class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
          <div 
            @click="isStructureExpanded = !isStructureExpanded" 
            class="p-5 sm:p-6 bg-slate-50/70 border-b border-slate-200 flex items-center justify-between cursor-pointer hover:bg-slate-100/70 transition"
          >
            <div>
              <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-black rounded-md">STEP 2</span>
                <h3 class="text-sm font-bold text-slate-900">Detail Struktur Anggaran (Master Data RKAKL DIPA)</h3>
              </div>
              <p class="text-xs text-slate-500 mt-0.5">Semua data berikut bersifat <strong class="text-slate-800">READ-ONLY</strong> dan diambil otomatis dari Master Data resmi.</p>
            </div>
            
            <button type="button" class="p-2 text-slate-500 hover:text-slate-900">
              <ChevronUp v-if="isStructureExpanded" class="w-5 h-5" />
              <ChevronDown v-else class="w-5 h-5" />
            </button>
          </div>

          <div v-show="isStructureExpanded" class="p-6 space-y-4 text-xs">
            <!-- Top Identity Badges -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-white p-4 rounded-2xl border border-slate-200/80">
              <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Tahun Anggaran (TA)</span>
                <span class="font-black text-slate-900 text-sm">{{ selectedBucket.fiscal_year }}</span>
              </div>
              <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Sumber Dana</span>
                <span class="font-black text-slate-900 text-sm">{{ selectedBucket.funding_source_code }} &mdash; Rupiah Murni</span>
              </div>
              <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Versi Revisi</span>
                <span class="font-black text-sky-800 text-sm">{{ selectedBucket.budget_version }}</span>
              </div>
              <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Jurusan Pengelola</span>
                <span class="font-black text-slate-900 text-sm">{{ selectedBucket.department_code }} &mdash; {{ selectedBucket.department_name }}</span>
              </div>
            </div>

            <!-- 7 Segments Structure List (Read-Only) -->
            <div class="space-y-2 border border-slate-200/80 rounded-2xl p-4 bg-slate-50/50">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between py-1 border-b border-slate-200/60">
                <span class="text-slate-500 font-medium">Program:</span>
                <span class="font-bold text-slate-900 font-sans">{{ selectedBucket.program_code }} &mdash; {{ selectedBucket.program_name }}</span>
              </div>
              <div class="flex flex-col sm:flex-row sm:items-center justify-between py-1 border-b border-slate-200/60">
                <span class="text-slate-500 font-medium">Kegiatan:</span>
                <span class="font-bold text-slate-900 font-sans">{{ selectedBucket.activity_code }} &mdash; {{ selectedBucket.activity_name }}</span>
              </div>
              <div class="flex flex-col sm:flex-row sm:items-center justify-between py-1 border-b border-slate-200/60">
                <span class="text-slate-500 font-medium">KRO (Klasifikasi Rincian Output):</span>
                <span class="font-bold text-slate-900 font-sans">{{ selectedBucket.kro_code }} &mdash; {{ selectedBucket.kro_name }}</span>
              </div>
              <div class="flex flex-col sm:flex-row sm:items-center justify-between py-1 border-b border-slate-200/60">
                <span class="text-slate-500 font-medium">RO (Rincian Output):</span>
                <span class="font-bold text-slate-900 font-sans">{{ selectedBucket.ro_code }} &mdash; {{ selectedBucket.ro_name }}</span>
              </div>
              <div class="flex flex-col sm:flex-row sm:items-center justify-between py-1 border-b border-slate-200/60">
                <span class="text-slate-500 font-medium">Komponen:</span>
                <span class="font-bold text-slate-900 font-sans">{{ selectedBucket.component_code }} &mdash; {{ selectedBucket.component_name }}</span>
              </div>
              <div class="flex flex-col sm:flex-row sm:items-center justify-between py-1 border-b border-slate-200/60">
                <span class="text-slate-500 font-medium">Subkomponen:</span>
                <span class="font-bold text-slate-900 font-sans">{{ selectedBucket.subcomponent_code }} &mdash; {{ selectedBucket.subcomponent_name }}</span>
              </div>
              <div class="flex flex-col sm:flex-row sm:items-center justify-between py-1 border-b border-slate-200/60">
                <span class="text-sky-800 font-bold">Akun Belanja:</span>
                <span class="font-black text-sky-950 font-sans text-xs bg-sky-100/80 px-2 py-0.5 rounded">{{ selectedBucket.account_code }} &mdash; {{ selectedBucket.account_name }}</span>
              </div>
              <div class="flex flex-col sm:flex-row sm:items-center justify-between py-1">
                <span class="text-slate-500 font-medium">Subakun:</span>
                <span class="font-bold text-slate-700 font-sans">{{ selectedBucket.subaccount_code }} &mdash; {{ selectedBucket.subaccount_name }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- ================================================== -->
        <!-- STEP 3 — INPUT PTK (MINIMUM INPUT)                 -->
        <!-- ================================================== -->
        <div class="bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
          <div class="border-b border-slate-100 pb-3">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
              <span class="px-2 py-0.5 bg-sky-100 text-sky-800 text-[10px] font-black rounded-md">STEP 3</span>
              <span>Input Rincian Transaksi PTK (Minimum Input)</span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">Isi rincian transaksi riil belanja kuitansi / SPJ.</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <!-- 1. Nomor Bukti * -->
            <div>
              <label class="block font-bold text-slate-700 mb-1.5">
                Nomor Bukti / Kuitansi <span class="text-rose-600">*</span>
              </label>
              <input 
                v-model="form.evidence_number" 
                type="text" 
                required 
                placeholder="Contoh: BKT/IF/2026/08/001"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold text-slate-900 font-sans focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none transition shadow-sm"
              />
              <div v-if="form.errors.evidence_number" class="text-rose-600 text-[11px] mt-1">{{ form.errors.evidence_number }}</div>
            </div>

            <!-- 2. Tanggal Transaksi * -->
            <div>
              <label class="block font-bold text-slate-700 mb-1.5">
                Tanggal Transaksi <span class="text-rose-600">*</span>
              </label>
              <input 
                v-model="form.transaction_date" 
                type="date" 
                required 
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold text-slate-900 font-sans focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none transition shadow-sm"
              />
              <div v-if="form.errors.transaction_date" class="text-rose-600 text-[11px] mt-1">{{ form.errors.transaction_date }}</div>
            </div>

            <!-- 3. Uraian Transaksi * (Full width) -->
            <div class="sm:col-span-2">
              <label class="block font-bold text-slate-700 mb-1.5">
                Uraian Belanja / Keterangan Transaksi <span class="text-rose-600">*</span>
              </label>
              <input 
                v-model="form.title" 
                type="text" 
                required 
                placeholder="Contoh: Belanja bahan praktikum algoritma pemrograman semester genap"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none transition shadow-sm"
              />
              <div v-if="form.errors.title" class="text-rose-600 text-[11px] mt-1">{{ form.errors.title }}</div>
            </div>

            <!-- 4. Nominal Transaksi * -->
            <div>
              <label class="block font-bold text-slate-700 mb-1.5">
                Nominal Transaksi (Rp) <span class="text-rose-600">*</span>
              </label>
              <input 
                v-model="form.amount" 
                type="number" 
                required 
                min="1000" 
                step="500" 
                placeholder="Contoh: 15000000"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-black text-slate-900 font-sans focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none transition shadow-sm text-base"
              />
              <span class="text-[11px] font-bold text-sky-700 mt-1 block">
                {{ formatRupiah(form.amount) }}
              </span>
              <div v-if="form.errors.amount" class="text-rose-600 text-[11px] mt-1">{{ form.errors.amount }}</div>
            </div>

            <!-- 5. Program Studi (Optional) -->
            <div>
              <label class="block font-semibold text-slate-700 mb-1.5">
                Program Studi Terkait <span class="text-slate-400 font-normal">(Opsional)</span>
              </label>
              <select 
                v-model="form.study_program_id" 
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none transition shadow-sm"
              >
                <option value="">-- Tidak Terikat Prodi Khusus (Level Jurusan) --</option>
                <option v-for="sp in studyPrograms" :key="sp.id" :value="sp.id">
                  {{ sp.name }}
                </option>
              </select>
              <span class="text-[10px] text-slate-400 mt-1 block">
                Kosongkan jika belanja operasional tingkat jurusan secara umum.
              </span>
            </div>

            <!-- 6. Catatan Tambahan (Optional) -->
            <div class="sm:col-span-2">
              <label class="block font-semibold text-slate-700 mb-1.5">
                Catatan Tambahan <span class="text-slate-400 font-normal">(Opsional)</span>
              </label>
              <textarea 
                v-model="form.notes" 
                rows="2" 
                placeholder="Catatan pendukung untuk pemeriksa PTU / Bendahara..."
                class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none transition shadow-sm"
              ></textarea>
            </div>

            <!-- 7. Lampiran Berkas (Optional) -->
            <div class="sm:col-span-2">
              <label class="block font-semibold text-slate-700 mb-1.5">
                Lampiran Berkas / Kuitansi / SPJ <span class="text-slate-400 font-normal">(Opsional)</span>
              </label>
              <div class="flex items-center gap-3">
                <label class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold cursor-pointer transition flex items-center gap-1.5 border border-slate-300">
                  <Paperclip class="w-4 h-4" />
                  <span>Pilih Berkas Lampiran</span>
                  <input type="file" @change="onFileSelected" class="hidden" accept=".pdf,.png,.jpg,.jpeg,.zip" />
                </label>
                <span class="text-xs text-slate-500 font-medium">
                  {{ uploadedFileNames.length > 0 ? uploadedFileNames.join(', ') : 'Belum ada berkas dipilih' }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- ================================================== -->
        <!-- STEP 4 — BUDGET CHECK & REAL-TIME SOLVENCY         -->
        <!-- ================================================== -->
        <div class="bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
              <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-black rounded-md">STEP 4</span>
              <span>Budget Check &amp; Simulasi Saldo Real-Time</span>
            </h2>
            <span class="text-xs text-slate-400 font-semibold">Aturan RBC-001</span>
          </div>

          <!-- Financial Snapshot 4-Col Grid -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
            <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200">
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Pagu Aktif</span>
              <span class="font-black text-slate-900 font-sans text-sm mt-0.5 block">
                {{ formatRupiah(selectedBucket?.allocated_budget) }}
              </span>
            </div>

            <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200">
              <span class="text-[10px] font-bold text-amber-700 uppercase tracking-wider block">Dalam Proses</span>
              <span class="font-black text-amber-950 font-sans text-sm mt-0.5 block">
                {{ formatRupiah(selectedBucket?.reserved_budget) }}
              </span>
            </div>

            <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200">
              <span class="text-[10px] font-bold text-sky-700 uppercase tracking-wider block">Realisasi</span>
              <span class="font-black text-sky-950 font-sans text-sm mt-0.5 block">
                {{ formatRupiah(selectedBucket?.realized_budget) }}
              </span>
            </div>

            <div class="p-3 bg-emerald-50/60 rounded-2xl border border-emerald-200">
              <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider block">Saldo Saat Ini</span>
              <span class="font-black text-emerald-950 font-sans text-sm mt-0.5 block">
                {{ formatRupiah(currentAvailableBalance) }}
              </span>
            </div>
          </div>

          <!-- Real-Time Solvency Banner -->
          <div 
            :class="[
              'p-4 rounded-2xl border flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs transition',
              isOverbudget 
                ? 'bg-rose-50 border-rose-300 text-rose-950 ring-2 ring-rose-500/20' 
                : 'bg-emerald-50 border-emerald-300 text-emerald-950'
            ]"
          >
            <div class="flex items-center gap-3">
              <div :class="['p-2 rounded-xl text-white font-bold', isOverbudget ? 'bg-rose-600' : 'bg-emerald-600']">
                <XCircle v-if="isOverbudget" class="w-5 h-5" />
                <CheckCircle2 v-else class="w-5 h-5" />
              </div>
              <div>
                <div class="font-black text-sm">
                  {{ isOverbudget ? '✕ Anggaran tidak mencukupi (Overbudget)' : '✓ Anggaran mencukupi' }}
                </div>
                <div class="text-[11px] mt-0.5" :class="isOverbudget ? 'text-rose-800' : 'text-emerald-800'">
                  <span v-if="isOverbudget">
                    Nominal transaksi melebihi saldo tersedia. Defisit kekurangan: <strong class="font-bold">{{ formatRupiah(shortfallAmount) }}</strong>. Transaksi tidak dapat diproses.
                  </span>
                  <span v-else>
                    Pos anggaran memiliki saldo yang cukup untuk memproses transaksi ini.
                  </span>
                </div>
              </div>
            </div>

            <!-- Projected Balance Result -->
            <div class="text-right shrink-0">
              <span class="text-[10px] uppercase font-bold tracking-wider block" :class="isOverbudget ? 'text-rose-700' : 'text-emerald-700'">
                Projected Sisa Saldo
              </span>
              <span :class="['font-black font-sans text-base block', isOverbudget ? 'text-rose-900 underline' : 'text-emerald-950']">
                {{ formatRupiah(projectedBalance) }}
              </span>
            </div>
          </div>
        </div>

        <!-- ================================================== -->
        <!-- STEP 5 — SAVE (ACTION BUTTONS)                     -->
        <!-- ================================================== -->
        <div class="bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="text-xs text-slate-500">
            <span class="font-bold text-slate-700">Protokol Simpan:</span> Transaksi akan masuk antrean pemeriksaan PTU (Penguji Tagihan Unit BLU) dan memotong saldo (reserved) secara aman.
          </div>

          <div class="flex items-center gap-3">
            <!-- CTA: Simpan Draft -->
            <button 
              type="button" 
              @click="submitTransaction('DRAFT')" 
              :disabled="form.processing"
              class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-2xl text-xs font-bold transition disabled:opacity-50 shadow-sm"
            >
              Simpan Draft
            </button>

            <!-- CTA: Simpan & Proses (Blocked if Overbudget) -->
            <button 
              type="button" 
              @click="submitTransaction('PROCESSING')" 
              :disabled="form.processing || isOverbudget || !form.amount || form.amount <= 0 || !form.title"
              :class="[
                'px-6 py-2.5 rounded-2xl text-xs font-bold flex items-center gap-2 transition shadow-md',
                isOverbudget 
                  ? 'bg-slate-300 text-slate-500 cursor-not-allowed shadow-none' 
                  : 'bg-sky-600 hover:bg-sky-500 text-white shadow-sky-600/20'
              ]"
            >
              <Check class="w-4 h-4" />
              <span>Simpan &amp; Proses</span>
            </button>
          </div>
        </div>

      </form>
    </div>
  </AppLayout>
</template>
