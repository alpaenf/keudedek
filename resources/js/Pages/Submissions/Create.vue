<script setup>
import { ref, computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { 
  Plus, 
  Trash2, 
  ArrowLeft, 
  ArrowRight, 
  CheckCircle2, 
  Wallet, 
  FileText, 
  UploadCloud, 
  ShieldCheck, 
  AlertTriangle, 
  Sparkles,
  Layers,
  Building2,
  Calendar,
  DollarSign,
  Tag
} from 'lucide-vue-next';

const props = defineProps({
  departments: Array,
  buckets: Array,
  transactionTypes: Array,
  documentTypes: Array,
  activeFiscalYear: [String, Number],
  performanceIndicators: Array,
  subcomponents: Array,
  userDepartmentId: [String, Number],
  userRole: String,
});

const currentStep = ref(1);

const form = useForm({
  department_id: props.userDepartmentId || (props.departments[0]?.id ?? ''),
  fiscal_year: props.activeFiscalYear || '2026',
  transaction_type_id: props.transactionTypes?.[0]?.id ?? 1,
  budget_bucket_id: props.buckets?.[0]?.id ?? '',
  title: '',
  reference_no: '',
  beneficiary_name: '',
  amount: 0,
  notes: '',
  background_narrative: '',
  objective_narrative: '',
  target_output: '',
  performance_indicator_code: '',
  performance_indicator_name: '',
  subcomponent_full_code: '',
  submit_action: 'SUBMITTED', // 'DRAFT' or 'SUBMITTED'
  items: [
    { item_name: '', quantity: 1, unit: 'Paket', unit_price: 0, total_price: 0 }
  ],
  documents: {}, // document_type_id -> File
});

// Auto calculate item total price
const updateItemTotal = (index) => {
  const itm = form.items[index];
  itm.total_price = (Number(itm.quantity) || 0) * (Number(itm.unit_price) || 0);
  calculateTotalFromItems();
};

const addItem = () => {
  form.items.push({ item_name: '', quantity: 1, unit: 'Unit', unit_price: 0, total_price: 0 });
};

const removeItem = (index) => {
  if (form.items.length > 1) {
    form.items.splice(index, 1);
    calculateTotalFromItems();
  }
};

const calculateTotalFromItems = () => {
  const sum = form.items.reduce((acc, curr) => acc + (Number(curr.total_price) || 0), 0);
  form.amount = sum;
};

// Selected bucket detail
const selectedBucket = computed(() => {
  return props.buckets?.find(b => b.id === Number(form.budget_bucket_id));
});

// Projected available calculation
const projectedAvailable = computed(() => {
  if (!selectedBucket.value) return 0;
  return Number(selectedBucket.value.available_balance) - Number(form.amount || 0);
});

const isOverbudget = computed(() => {
  return projectedAvailable.value < 0;
});

const onIndicatorChange = (event) => {
  const code = event.target.value;
  const ind = props.performanceIndicators?.find(i => i.code === code);
  form.performance_indicator_name = ind ? ind.name : '';
};

const handleFileChange = (docTypeId, event) => {
  const file = event.target.files[0];
  if (file) {
    form.documents[docTypeId] = file;
  }
};

const nextStep = () => {
  if (currentStep.value < 6) {
    currentStep.value++;
  }
};

const prevStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--;
  }
};

const submitForm = (action = 'SUBMITTED') => {
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

const isDeptLocked = ['PTK', 'KAJUR'].includes(props.userRole);
</script>

<template>
  <AppLayout title="Buat Pengajuan Baru (Wizard)">
    <div class="max-w-4xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <Link href="/submissions" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-sky-600 transition mb-2">
            <ArrowLeft class="w-4 h-4" /> Kembali ke Daftar Pengajuan
          </Link>
          <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Formulir Pengajuan Belanja Anggaran</h2>
          <p class="text-xs text-slate-500">Panduan wizard 6 langkah untuk input usulan kegiatan dan rincian belanja.</p>
        </div>
      </div>

      <!-- 6-Step Progress Bar Component -->
      <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-sm">
        <div class="grid grid-cols-6 gap-2 text-center text-xs">
          <div 
            v-for="(st, idx) in ['1. Konteks', '2. Pagu', '3. TOR & IKU', '4. Item', '5. Dokumen', '6. Review']" 
            :key="idx"
            @click="currentStep = idx + 1"
            :class="[
              'cursor-pointer py-2 px-1 rounded-xl transition font-bold text-[11px] truncate',
              currentStep === idx + 1 ? 'bg-sky-600 text-white shadow-md shadow-sky-600/20' : (currentStep > idx + 1 ? 'bg-sky-50 text-sky-700' : 'bg-slate-50 text-slate-400')
            ]"
          >
            {{ st }}
          </div>
        </div>
      </div>

      <!-- Main Step Container -->
      <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-sm space-y-6">
        
        <!-- STEP 1: CONTEXT -->
        <div v-show="currentStep === 1" class="space-y-5 animate-in fade-in duration-200">
          <div class="border-b border-slate-100 pb-3">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
              <Layers class="w-5 h-5 text-sky-600" />
              Langkah 1: Konteks Pengajuan &amp; Mekanisme Belanja
            </h3>
            <p class="text-xs text-slate-500">Pilih unit pengusul, tahun anggaran, dan jenis transaksi belanja.</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Unit / Jurusan Pengusul</label>
              <select 
                v-model="form.department_id" 
                :disabled="isDeptLocked"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none disabled:opacity-75"
              >
                <option v-for="d in departments" :key="d.id" :value="d.id">
                  {{ d.name }} ({{ d.code }})
                </option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tahun Anggaran</label>
              <input 
                v-model="form.fiscal_year" 
                type="text" 
                readonly 
                class="w-full px-3.5 py-2.5 bg-slate-100 border border-slate-300 rounded-xl text-xs text-slate-600 font-sans font-bold cursor-not-allowed"
              >
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Jenis Transaksi (Transaction Type)</label>
              <select 
                v-model="form.transaction_type_id" 
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none"
              >
                <option v-for="tt in transactionTypes" :key="tt.id" :value="tt.id">
                  {{ tt.code }} - {{ tt.name }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Sumber Dana</label>
              <input 
                type="text" 
                value="Rupiah Murni (RM) / UKT FT" 
                readonly 
                class="w-full px-3.5 py-2.5 bg-slate-100 border border-slate-300 rounded-xl text-xs text-slate-600 font-medium cursor-not-allowed"
              >
            </div>
          </div>
        </div>

        <!-- STEP 2: BUDGET SELECTION -->
        <div v-show="currentStep === 2" class="space-y-5 animate-in fade-in duration-200">
          <div class="border-b border-slate-100 pb-3">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
              <Wallet class="w-5 h-5 text-sky-600" />
              Langkah 2: Pemilihan Pos Anggaran (Budget Bucket)
            </h3>
            <p class="text-xs text-slate-500">Pilih mata anggaran belanja yang akan dibebankan untuk kegiatan ini.</p>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Pilih Pos Anggaran Aktif</label>
            <select 
              v-model="form.budget_bucket_id" 
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none"
            >
              <option v-for="b in buckets" :key="b.id" :value="b.id">
                [{{ b.account_code }}] {{ b.budget_bucket_name || b.account_name }} &bull; Sisa Saldo: {{ formatRupiah(b.available_balance) }}
              </option>
            </select>
          </div>

          <!-- Real-time Balance Inspection Card -->
          <div v-if="selectedBucket" class="p-5 bg-sky-50/50 border border-sky-200 rounded-2xl space-y-4">
            <div class="font-bold text-xs text-sky-900 uppercase tracking-wider">Status Ketersediaan Pos Terpilih</div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
              <div class="p-3 bg-white rounded-xl border border-sky-100">
                <span class="text-slate-500 block text-[10px]">Pagu Alokasi</span>
                <span class="font-black text-slate-900 font-sans text-sm">{{ formatRupiah(selectedBucket.allocated_budget) }}</span>
              </div>
              <div class="p-3 bg-white rounded-xl border border-sky-100">
                <span class="text-indigo-700 block text-[10px]">Reserved</span>
                <span class="font-black text-indigo-900 font-sans text-sm">{{ formatRupiah(selectedBucket.reserved_budget) }}</span>
              </div>
              <div class="p-3 bg-white rounded-xl border border-sky-100">
                <span class="text-emerald-700 block text-[10px]">Realisasi</span>
                <span class="font-black text-emerald-900 font-sans text-sm">{{ formatRupiah(selectedBucket.realized_budget) }}</span>
              </div>
              <div class="p-3 bg-white rounded-xl border border-sky-200 bg-sky-50/30">
                <span class="text-sky-800 block text-[10px] font-bold">Saldo Bebas (Available)</span>
                <span class="font-black text-sky-950 font-sans text-sm">{{ formatRupiah(selectedBucket.available_balance) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- STEP 3: SUBMISSION DETAIL & TOR NARRATIVES & SAKIP IKU -->
        <div v-show="currentStep === 3" class="space-y-5 animate-in fade-in duration-200">
          <div class="border-b border-slate-100 pb-3">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
              <FileText class="w-5 h-5 text-sky-600" />
              Langkah 3: Rincian Pengajuan, TOR, &amp; Indikator SAKIP/IKU
            </h3>
            <p class="text-xs text-slate-500">Penyusunan Term of Reference (TOR) usulan dan pemetaan indikator ketercapaian kinerja.</p>
          </div>

          <div class="space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama / Judul Usulan Kegiatan <span class="text-rose-600">*</span></label>
              <input 
                v-model="form.title" 
                type="text" 
                required 
                placeholder="Contoh: Pengadaan Bahan Praktikum Laboratorium Komputer Semester Ganjil"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none"
              >
            </div>

            <!-- SAKIP / IKU Indicator Selection -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Indikator Kinerja SAKIP / IKU Terkait</label>
                <select 
                  v-model="form.performance_indicator_code" 
                  @change="onIndicatorChange"
                  class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none"
                >
                  <option value="">-- Pilih Indikator IKU/IKK SAKIP --</option>
                  <option v-for="pi in performanceIndicators" :key="pi.code" :value="pi.code">
                    [{{ pi.code }}] {{ pi.name }}
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Subkomponen Anggaran (Header Hijau)</label>
                <select 
                  v-model="form.subcomponent_full_code" 
                  class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none"
                >
                  <option value="">-- Pilih Subkomponen Header --</option>
                  <option v-for="sc in subcomponents" :key="sc.id" :value="sc.full_code">
                    {{ sc.code }} - {{ sc.name }}
                  </option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nomor Surat / Referensi Pengantar (Opsional)</label>
                <input 
                  v-model="form.reference_no" 
                  type="text" 
                  placeholder="Contoh: UN23.FT.IF/KU/2026/102"
                  class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none font-sans"
                >
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Rekanan / Penerima Pembayaran <span class="text-rose-600">*</span></label>
                <input 
                  v-model="form.beneficiary_name" 
                  type="text" 
                  required 
                  placeholder="Contoh: CV Mandiri Komputer / Panitia Workshop"
                  class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none"
                >
              </div>
            </div>

            <!-- TOR Narratives -->
            <div class="space-y-3 pt-2 border-t border-slate-100">
              <div>
                <label class="block text-xs font-bold text-slate-800 mb-1">Latar Belakang Kegiatan (TOR Narrative)</label>
                <textarea 
                  v-model="form.background_narrative" 
                  rows="2" 
                  placeholder="Uraian latar belakang urgensi pengajuan..."
                  class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none"
                ></textarea>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-800 mb-1">Maksud &amp; Tujuan (TOR Purpose)</label>
                <textarea 
                  v-model="form.objective_narrative" 
                  rows="2" 
                  placeholder="Tujuan spesifik yang ingin dicapai..."
                  class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none"
                ></textarea>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-800 mb-1">Target Output / Luaran Kegiatan</label>
                <input 
                  v-model="form.target_output" 
                  type="text" 
                  placeholder="Contoh: Terlaksananya praktikum 150 mahasiswa dan terbitnya 3 modul laboratorium"
                  class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none"
                >
              </div>
            </div>
          </div>
        </div>

        <!-- STEP 4: ITEMS BREAKDOWN -->
        <div v-show="currentStep === 4" class="space-y-5 animate-in fade-in duration-200">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
              <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <Tag class="w-5 h-5 text-sky-600" />
                Langkah 4: Rincian Item Belanja Barang / Jasa
              </h3>
              <p class="text-xs text-slate-500">Tambahkan daftar spesifikasi, volume, dan harga satuan.</p>
            </div>
            <button 
              type="button" 
              @click="addItem" 
              class="px-3 py-1.5 bg-sky-50 hover:bg-sky-100 text-sky-700 border border-sky-200 rounded-xl text-xs font-bold flex items-center gap-1 transition"
            >
              <Plus class="w-3.5 h-3.5" /> Tambah Baris
            </button>
          </div>

          <div class="space-y-3">
            <div 
              v-for="(itm, idx) in form.items" 
              :key="idx" 
              class="p-4 bg-slate-50 border border-slate-200 rounded-2xl grid grid-cols-12 gap-3 items-end"
            >
              <div class="col-span-12 sm:col-span-4">
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Uraian Barang / Jasa</label>
                <input 
                  v-model="itm.item_name" 
                  type="text" 
                  required 
                  placeholder="Nama item..."
                  class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-sky-500 focus:outline-none"
                >
              </div>

              <div class="col-span-4 sm:col-span-2">
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Volume</label>
                <input 
                  v-model="itm.quantity" 
                  @input="updateItemTotal(idx)"
                  type="number" 
                  min="1" 
                  required 
                  class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-sans focus:ring-2 focus:ring-sky-500 focus:outline-none"
                >
              </div>

              <div class="col-span-8 sm:col-span-3">
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Harga Satuan (Rp)</label>
                <input 
                  v-model="itm.unit_price" 
                  @input="updateItemTotal(idx)"
                  type="number" 
                  min="0" 
                  step="1000" 
                  required 
                  class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-sans focus:ring-2 focus:ring-sky-500 focus:outline-none"
                >
              </div>

              <div class="col-span-10 sm:col-span-2">
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Total</label>
                <div class="py-2 px-3 bg-slate-100 rounded-xl text-xs font-sans font-bold text-slate-900 truncate">
                  {{ formatRupiah(itm.total_price) }}
                </div>
              </div>

              <div class="col-span-2 sm:col-span-1 text-center">
                <button 
                  type="button" 
                  @click="removeItem(idx)" 
                  class="p-2 text-rose-500 hover:bg-rose-50 rounded-xl transition"
                  title="Hapus baris"
                >
                  <Trash2 class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>

          <!-- Total Calculation Card -->
          <div class="p-4 bg-slate-900 text-white rounded-2xl flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-wider">Total Nominal Pengajuan:</span>
            <span class="text-xl font-black font-sans tracking-tight text-sky-400">{{ formatRupiah(form.amount) }}</span>
          </div>
        </div>

        <!-- STEP 5: DOCUMENTS ATTACHMENT -->
        <div v-show="currentStep === 5" class="space-y-5 animate-in fade-in duration-200">
          <div class="border-b border-slate-100 pb-3">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
              <UploadCloud class="w-5 h-5 text-sky-600" />
              Langkah 5: Upload Dokumen Lampiran Pendukung
            </h3>
            <p class="text-xs text-slate-500">Mendukung berkas PDF, DOCX, XLSX, JPEG, PNG (Maks 15MB per file).</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div v-for="dt in documentTypes" :key="dt.id" class="p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-2">
              <div class="flex items-center justify-between">
                <span class="font-bold text-xs text-slate-900">{{ dt.name }}</span>
                <span v-if="dt.is_required" class="text-[10px] px-2 py-0.5 bg-rose-100 text-rose-700 font-bold rounded-md uppercase">Wajib</span>
                <span v-else class="text-[10px] text-slate-400">Opsional</span>
              </div>
              <input 
                type="file" 
                @change="e => handleFileChange(dt.id, e)"
                accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png"
                class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100"
              >
            </div>
          </div>
        </div>

        <!-- STEP 6: REVIEW & RBC PRE-CHECK -->
        <div v-show="currentStep === 6" class="space-y-5 animate-in fade-in duration-200">
          <div class="border-b border-slate-100 pb-3">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
              <ShieldCheck class="w-5 h-5 text-sky-600" />
              Langkah 6: Review &amp; Simulasi Aturan Anggaran (RBC)
            </h3>
            <p class="text-xs text-slate-500">Verifikasi kalkulasi sebelum menyimpan draft atau mengajukan ke verifikator.</p>
          </div>

          <!-- RBC Pre-Check Simulation Status Banner -->
          <div :class="['p-4 rounded-2xl border flex items-start gap-3', isOverbudget ? 'bg-rose-50 border-rose-200 text-rose-900' : 'bg-emerald-50 border-emerald-200 text-emerald-900']">
            <AlertTriangle v-if="isOverbudget" class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" />
            <CheckCircle2 v-else class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" />
            <div class="text-xs space-y-1">
              <div class="font-bold text-sm">
                {{ isOverbudget ? 'Peringatan RBC-001: Usulan Melebihi Sisa Saldo Bebas (Overbudget)' : 'Simulasi RBC-001 Lolos: Saldo Anggaran Mencukupi' }}
              </div>
              <p class="leading-relaxed">
                {{ isOverbudget 
                  ? 'Nominal pengajuan (' + formatRupiah(form.amount) + ') melebihi ketersediaan saldo (' + formatRupiah(selectedBucket?.available_balance) + '). Pengajuan akan diblokir oleh sistem saat persetujuan komitmen.'
                  : 'Pos anggaran memiliki ketersediaan saldo yang valid untuk mengunci komitmen dana sebesar ' + formatRupiah(form.amount) + '.' }}
              </p>
            </div>
          </div>

          <!-- Summary Breakdown Card -->
          <div class="p-5 bg-slate-50 border border-slate-200 rounded-2xl space-y-3 text-xs">
            <div class="flex justify-between">
              <span class="text-slate-500">Nama Kegiatan:</span>
              <span class="font-bold text-slate-900 text-right">{{ form.title || '-' }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Mata Anggaran:</span>
              <span class="font-sans text-slate-800">{{ selectedBucket?.account_code }} - {{ selectedBucket?.account_name }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Rekanan / Penerima:</span>
              <span class="font-semibold text-slate-800">{{ form.beneficiary_name || '-' }}</span>
            </div>
            <div class="flex justify-between border-t border-slate-200 pt-2">
              <span class="text-slate-500">Saldo Bebas Saat Ini:</span>
              <span class="font-sans font-bold text-slate-900">{{ formatRupiah(selectedBucket?.available_balance) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-700 font-bold">Total Nominal Usulan:</span>
              <span class="font-sans font-black text-sky-700 text-sm">{{ formatRupiah(form.amount) }}</span>
            </div>
            <div class="flex justify-between border-t border-slate-200 pt-2">
              <span class="text-slate-500 font-semibold">Proyeksi Sisa Saldo Bebas:</span>
              <span :class="['font-sans font-black text-sm', projectedAvailable < 0 ? 'text-rose-600' : 'text-emerald-700']">
                {{ formatRupiah(projectedAvailable) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="flex items-center justify-between pt-6 border-t border-slate-100 text-xs">
          <button 
            type="button" 
            @click="prevStep" 
            :disabled="currentStep === 1"
            class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl transition disabled:opacity-40 disabled:cursor-not-allowed"
          >
            &larr; Sebelumnya
          </button>

          <div class="flex items-center gap-3">
            <button 
              v-if="currentStep < 6" 
              type="button" 
              @click="nextStep" 
              class="px-6 py-2.5 bg-sky-600 hover:bg-sky-500 text-white font-bold rounded-xl transition flex items-center gap-1.5 shadow-md shadow-sky-600/20"
            >
              Lanjutkan &rarr;
            </button>

            <template v-else>
              <button 
                type="button" 
                @click="submitForm('DRAFT')"
                :disabled="form.processing"
                class="px-4 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold rounded-xl transition"
              >
                Simpan Draft
              </button>
              <button 
                type="button" 
                @click="submitForm('SUBMITTED')"
                :disabled="form.processing || isOverbudget"
                class="px-6 py-2.5 bg-sky-600 hover:bg-sky-500 text-white font-bold rounded-xl transition flex items-center gap-1.5 shadow-md shadow-sky-600/20 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <CheckCircle2 class="w-4 h-4" />
                Ajukan Sekarang
              </button>
            </template>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
