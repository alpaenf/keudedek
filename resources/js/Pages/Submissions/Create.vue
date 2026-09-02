<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { 
  Search,
  ArrowLeft, 
  CheckCircle2, 
  Wallet, 
  FileText, 
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
  Paperclip,
  Loader2,
  ListOrdered
} from 'lucide-vue-next';

const props = defineProps({
  departments: Array,
  studyPrograms: Array,
  initialBudgetLines: Array,
  transactionTypes: Array,
  documentTypes: Array,
  activeFiscalYear: [String, Number],
  activeVersion: Object,
  activeFundingSource: Object,
  userDepartmentId: [String, Number],
  userStudyProgramId: [String, Number],
  userRole: String,
});

// Autocomplete State
const searchInput = ref('');
const isSearching = ref(false);
const searchResults = ref(props.initialBudgetLines || []);
const isDropdownOpen = ref(false);
const selectedBudgetLine = ref(props.initialBudgetLines?.[0] || null);
const isHierarchyExpanded = ref(false);

const today = new Date().toISOString().split('T')[0];

const form = useForm({
  budget_line_id: selectedBudgetLine.value?.id ?? '',
  budget_bucket_id: selectedBudgetLine.value?.control_bucket?.id ?? '',
  evidence_number: '',
  transaction_date: today,
  title: '',
  amount: null,
  department_id: props.userDepartmentId || '',
  study_program_id: props.userStudyProgramId || '',
  notes: '',
  submit_action: 'PROCESSING', // 'DRAFT' | 'PROCESSING'
  documents: {}, // document_type_id -> File
});

// Search Debounce Logic
let debounceTimeout = null;
const performSearch = () => {
  if (debounceTimeout) clearTimeout(debounceTimeout);

  debounceTimeout = setTimeout(async () => {
    isSearching.value = true;
    try {
      const params = new URLSearchParams();
      if (searchInput.value.trim()) {
        params.append('q', searchInput.value.trim());
      }
      if (props.activeVersion?.id) {
        params.append('budget_version_id', props.activeVersion.id);
      }
      if (props.userDepartmentId) {
        params.append('department_id', props.userDepartmentId);
      }

      const res = await fetch(`/api/budget-lines/search?${params.toString()}`);
      if (res.ok) {
        const json = await res.json();
        searchResults.value = json.data || [];
        isDropdownOpen.value = true;
      }
    } catch (err) {
      console.error('Error searching budget lines:', err);
    } finally {
      isSearching.value = false;
    }
  }, 250);
};

// Select Budget Line from Autocomplete
const selectBudgetLine = (line) => {
  selectedBudgetLine.value = line;
  form.budget_line_id = line.id;
  form.budget_bucket_id = line.control_bucket?.id || '';
  isDropdownOpen.value = false;
  searchInput.value = `${line.rba_sequence_no} - ${line.description}`;
};

// Keyboard navigation
const onInputFocus = () => {
  if (searchResults.value.length > 0) {
    isDropdownOpen.value = true;
  }
};

// Real-time Solvency & Budget Check (Controlled by Control Bucket)
const currentAvailableBalance = computed(() => {
  return Number(selectedBudgetLine.value?.financial_snapshot?.saldo_tersedia) || 0;
});

const inputAmountNumber = computed(() => {
  return Number(form.amount) || 0;
});

const projectedBalance = computed(() => {
  return currentAvailableBalance.value - inputAmountNumber.value;
});

const isSolvent = computed(() => {
  if (!selectedBudgetLine.value) return false;
  if (inputAmountNumber.value <= 0) return true;
  return projectedBalance.value >= 0;
});

const isOverbudget = computed(() => {
  if (!selectedBudgetLine.value || inputAmountNumber.value <= 0) return false;
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

// Close dropdown on outside click
onMounted(() => {
  if (selectedBudgetLine.value) {
    searchInput.value = `${selectedBudgetLine.value.rba_sequence_no} - ${selectedBudgetLine.value.description}`;
  }
  
  const handleClickOutside = (e) => {
    const el = document.getElementById('budget-line-autocomplete-wrapper');
    if (el && !el.contains(e.target)) {
      isDropdownOpen.value = false;
    }
  };
  document.addEventListener('click', handleClickOutside);
});
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
            Pencatatan realisasi belanja pola ringkas &bull; Cari No RBA &bull; Struktur kode &amp; saldo otomatis tersinkronisasi.
          </p>
        </div>

        <!-- Automatic Context Badge (Read-Only) -->
        <div class="flex items-center gap-2 bg-sky-50 border border-sky-200/80 px-4 py-2 rounded-2xl text-xs shadow-sm shrink-0">
          <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
          <span class="font-extrabold text-sky-950">
            TA {{ activeFiscalYear }} &bull; {{ activeFundingSource?.code || 'RM' }} &bull; {{ activeVersion?.revision_no || 'Rev 00' }} &bull; {{ selectedBudgetLine?.department?.code || 'FT' }}
          </span>
        </div>
      </div>

      <form @submit.prevent="submitTransaction('PROCESSING')" class="space-y-6">

        <!-- ================================================== -->
        <!-- 1. AUTOCOMPLETE POS ANGGARAN / NO URUT RBA        -->
        <!-- ================================================== -->
        <div id="budget-line-autocomplete-wrapper" class="bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4 relative">
          <div class="border-b border-slate-100 pb-3">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
              <span class="px-2 py-0.5 bg-sky-100 text-sky-800 text-[10px] font-black rounded-md">1</span>
              <span>Pilih Pos Anggaran / Nomor Urut RBA</span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">Ketik No Urut RBA, uraian belanja, atau kode akun untuk memilih.</p>
          </div>

          <!-- Autocomplete Input -->
          <div class="relative">
            <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
            <input 
              v-model="searchInput"
              @input="performSearch"
              @focus="onInputFocus"
              type="text" 
              placeholder="Ketik No Urut RBA (contoh: 001), uraian kegiatan, atau kode akun..." 
              class="w-full pl-10 pr-10 py-2.5 bg-slate-50 border border-slate-300 rounded-2xl text-xs text-slate-900 focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none transition shadow-sm font-medium"
            />
            <Loader2 v-if="isSearching" class="w-4 h-4 text-sky-600 animate-spin absolute right-3.5 top-3" />
          </div>
          <div v-if="form.errors.budget_line_id" class="text-rose-600 text-[11px] font-bold">{{ form.errors.budget_line_id }}</div>

          <!-- Dropdown Results -->
          <div 
            v-if="isDropdownOpen && searchResults.length > 0" 
            class="absolute left-6 right-6 top-[110px] z-30 bg-white rounded-2xl border border-slate-200 shadow-2xl max-h-64 overflow-y-auto divide-y divide-slate-100"
          >
            <div 
              v-for="line in searchResults" 
              :key="line.id"
              @click="selectBudgetLine(line)"
              class="p-3 hover:bg-sky-50/70 cursor-pointer transition flex items-center justify-between gap-3 text-xs"
            >
              <div class="space-y-1 min-w-0">
                <div class="flex items-center gap-2">
                  <span class="font-black text-sky-950 bg-sky-100 px-2 py-0.5 rounded text-[11px]">
                    No. {{ line.rba_sequence_no }}
                  </span>
                  <span class="font-bold text-slate-900 truncate">{{ line.description }}</span>
                  <span class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-bold uppercase">
                    {{ line.department?.code }}
                  </span>
                </div>
                <div class="text-[11px] text-slate-500 truncate">
                  Akun: <strong>{{ line.hierarchy?.account?.code }}</strong> &bull; Subkomponen: {{ line.hierarchy?.subcomponent?.name || '-' }}
                </div>
              </div>

              <div class="text-right shrink-0">
                <span class="text-[9px] text-slate-400 block font-bold">Saldo Tersedia</span>
                <span class="font-black text-emerald-800 font-sans text-xs">
                  {{ formatRupiah(line.financial_snapshot?.saldo_tersedia) }}
                </span>
              </div>
            </div>
          </div>

          <!-- Empty state dropdown -->
          <div 
            v-if="isDropdownOpen && !isSearching && searchResults.length === 0" 
            class="absolute left-6 right-6 top-[110px] z-30 bg-white rounded-2xl border border-slate-200 shadow-xl p-4 text-center text-xs text-slate-500"
          >
            Tidak ada baris anggaran RBA yang cocok dengan kata kunci.
          </div>

          <!-- ================================================== -->
          <!-- 2. DETAIL OTOMATIS & METRIK FINANSIAL SNAPSHOT     -->
          <!-- ================================================== -->
          <div v-if="selectedBudgetLine" class="space-y-4 pt-2">
            <!-- Snapshot Header Info -->
            <div class="bg-slate-50 border border-slate-200/80 rounded-2xl p-4 space-y-2">
              <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                  <span class="px-2.5 py-1 bg-sky-600 text-white font-black rounded-lg text-xs tracking-wide">
                    No. Urut RBA: {{ selectedBudgetLine.rba_sequence_no }}
                  </span>
                  <span class="text-xs font-bold text-slate-900 font-sans">
                    [{{ selectedBudgetLine.hierarchy?.account?.code }}] {{ selectedBudgetLine.hierarchy?.account?.name }}
                  </span>
                </div>
                <span class="text-[11px] font-bold text-slate-500">
                  Subkomponen: {{ selectedBudgetLine.hierarchy?.subcomponent?.full_code || selectedBudgetLine.hierarchy?.subcomponent?.name }}
                </span>
              </div>
              <p class="text-xs text-slate-700 font-medium pt-1">
                <strong>Uraian RBA:</strong> {{ selectedBudgetLine.description }}
              </p>
            </div>

            <!-- 4-Card Unified Financial Snapshot -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
              <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Pagu Pos (Bucket)</span>
                <span class="font-black text-slate-900 font-sans text-sm mt-0.5 block">
                  {{ formatRupiah(selectedBudgetLine.financial_snapshot?.pagu_bucket) }}
                </span>
              </div>

              <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200">
                <span class="text-[10px] font-bold text-amber-700 uppercase tracking-wider block">Total DIAJUKAN</span>
                <span class="font-black text-amber-950 font-sans text-sm mt-0.5 block">
                  {{ formatRupiah(selectedBudgetLine.financial_snapshot?.diajukan_bucket) }}
                </span>
              </div>

              <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200">
                <span class="text-[10px] font-bold text-sky-700 uppercase tracking-wider block">Realisasi SELESAI</span>
                <span class="font-black text-sky-950 font-sans text-sm mt-0.5 block">
                  {{ formatRupiah(selectedBudgetLine.financial_snapshot?.realisasi_bucket) }}
                </span>
              </div>

              <div class="p-3 bg-emerald-50/70 rounded-2xl border border-emerald-200">
                <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider block">Saldo Tersedia</span>
                <span class="font-black text-emerald-950 font-sans text-sm mt-0.5 block">
                  {{ formatRupiah(selectedBudgetLine.financial_snapshot?.saldo_tersedia) }}
                </span>
              </div>
            </div>

            <!-- Accordion Read-Only Hierarchy (Optional Expand) -->
            <div class="border border-slate-200/80 rounded-2xl overflow-hidden text-xs">
              <button 
                type="button" 
                @click="isHierarchyExpanded = !isHierarchyExpanded"
                class="w-full px-4 py-2.5 bg-slate-50 hover:bg-slate-100 flex items-center justify-between text-slate-600 font-bold transition"
              >
                <div class="flex items-center gap-2">
                  <Layers class="w-4 h-4 text-sky-600" />
                  <span>Lihat Hierarki Nomenklatur Lengkap APBN (Read-Only)</span>
                </div>
                <ChevronUp v-if="isHierarchyExpanded" class="w-4 h-4" />
                <ChevronDown v-else class="w-4 h-4" />
              </button>

              <div v-show="isHierarchyExpanded" class="p-4 bg-white space-y-1.5 border-t border-slate-100 text-[11px] text-slate-600">
                <div><strong>Program:</strong> {{ selectedBudgetLine.hierarchy?.program?.code }} &mdash; {{ selectedBudgetLine.hierarchy?.program?.name }}</div>
                <div><strong>Kegiatan:</strong> {{ selectedBudgetLine.hierarchy?.activity?.code }} &mdash; {{ selectedBudgetLine.hierarchy?.activity?.name }}</div>
                <div><strong>KRO:</strong> {{ selectedBudgetLine.hierarchy?.kro?.code }} &mdash; {{ selectedBudgetLine.hierarchy?.kro?.name }}</div>
                <div><strong>RO:</strong> {{ selectedBudgetLine.hierarchy?.ro?.code }} &mdash; {{ selectedBudgetLine.hierarchy?.ro?.name }}</div>
                <div><strong>Komponen:</strong> {{ selectedBudgetLine.hierarchy?.component?.code }} &mdash; {{ selectedBudgetLine.hierarchy?.component?.name }}</div>
                <div><strong>Subkomponen:</strong> {{ selectedBudgetLine.hierarchy?.subcomponent?.full_code }} &mdash; {{ selectedBudgetLine.hierarchy?.subcomponent?.name }}</div>
                <div><strong>Akun:</strong> {{ selectedBudgetLine.hierarchy?.account?.code }} &mdash; {{ selectedBudgetLine.hierarchy?.account?.name }}</div>
              </div>
            </div>

          </div>
        </div>

        <!-- ================================================== -->
        <!-- 3. FIELD MANUAL UTAMA & OPSIONAL                   -->
        <!-- ================================================== -->
        <div class="bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
          <div class="border-b border-slate-100 pb-3">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
              <span class="px-2 py-0.5 bg-sky-100 text-sky-800 text-[10px] font-black rounded-md">2</span>
              <span>Input Rincian Transaksi Belanja</span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">Isi rincian transaksi riil sesuai bukti kuitansi / FRA.</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <!-- 1. Tanggal Transaksi * -->
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

            <!-- 2. Nomor Bukti / No FRA * (Manual input - no auto generate) -->
            <div>
              <label class="block font-bold text-slate-700 mb-1.5">
                No. FRA / Nomor Bukti Kuitansi <span class="text-rose-600">*</span>
              </label>
              <input 
                v-model="form.evidence_number" 
                type="text" 
                required 
                placeholder="Masukkan nomor bukti fisik kuitansi..."
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold text-slate-900 font-sans focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none transition shadow-sm"
              />
              <div v-if="form.errors.evidence_number" class="text-rose-600 text-[11px] mt-1">{{ form.errors.evidence_number }}</div>
            </div>

            <!-- 3. Uraian Aktual * (Full width) -->
            <div class="sm:col-span-2">
              <label class="block font-bold text-slate-700 mb-1.5">
                Uraian Aktual Belanja <span class="text-rose-600">*</span>
              </label>
              <input 
                v-model="form.title" 
                type="text" 
                required 
                placeholder="Tuliskan uraian belanja riil yang dilakukan..."
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
                placeholder="0"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-black text-slate-900 font-sans focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none transition shadow-sm text-base"
              />
              <span class="text-[11px] font-bold text-sky-700 mt-1 block">
                {{ formatRupiah(form.amount) }}
              </span>
              <div v-if="form.errors.amount" class="text-rose-600 text-[11px] mt-1">{{ form.errors.amount }}</div>
            </div>

            <!-- 5. Program Studi (Opsional) -->
            <div>
              <label class="block font-semibold text-slate-700 mb-1.5">
                Program Studi Terkait <span class="text-slate-400 font-normal">(Opsional)</span>
              </label>
              <select 
                v-model="form.study_program_id" 
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none transition shadow-sm"
              >
                <option value="">-- Tidak Terikat Prodi (Tingkat Jurusan) --</option>
                <option v-for="sp in studyPrograms" :key="sp.id" :value="sp.id">
                  {{ sp.name }}
                </option>
              </select>
            </div>

            <!-- 6. Catatan Tambahan (Opsional) -->
            <div class="sm:col-span-2">
              <label class="block font-semibold text-slate-700 mb-1.5">
                Catatan Tambahan <span class="text-slate-400 font-normal">(Opsional)</span>
              </label>
              <textarea 
                v-model="form.notes" 
                rows="2" 
                placeholder="Catatan pelengkap untuk verifikator PTU..."
                class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none transition shadow-sm"
              ></textarea>
            </div>

            <!-- 7. Lampiran Berkas (Opsional) -->
            <div class="sm:col-span-2">
              <label class="block font-semibold text-slate-700 mb-1.5">
                Lampiran Berkas / Bukti Kuitansi <span class="text-slate-400 font-normal">(Opsional)</span>
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
        <!-- 4. REAL-TIME SOLVENCY CHECK BANNER                 -->
        <!-- ================================================== -->
        <div 
          v-if="selectedBudgetLine"
          :class="[
            'p-4 rounded-3xl border flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs transition shadow-sm',
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
                {{ isOverbudget ? '✕ Melebihi Saldo Tersedia (Overbudget)' : '✓ Saldo Tersedia Mencukupi' }}
              </div>
              <div class="text-[11px] mt-0.5" :class="isOverbudget ? 'text-rose-800' : 'text-emerald-800'">
                <span v-if="isOverbudget">
                  Nominal transaksi melebihi sisa pagu Control Bucket. Defisit: <strong class="font-bold">{{ formatRupiah(shortfallAmount) }}</strong>.
                </span>
                <span v-else>
                  Pos anggaran memiliki saldo yang cukup untuk diproses ke verifikasi.
                </span>
              </div>
            </div>
          </div>

          <div class="text-right shrink-0">
            <span class="text-[10px] uppercase font-bold tracking-wider block" :class="isOverbudget ? 'text-rose-700' : 'text-emerald-700'">
              Projected Sisa Saldo
            </span>
            <span :class="['font-black font-sans text-base block', isOverbudget ? 'text-rose-900 underline' : 'text-emerald-950']">
              {{ formatRupiah(projectedBalance) }}
            </span>
          </div>
        </div>

        <!-- ================================================== -->
        <!-- 5. ACTION BUTTONS (DRAFT / AJUKAN)                 -->
        <!-- ================================================== -->
        <div class="bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="text-xs text-slate-500">
            <span class="font-bold text-slate-700">Protokol:</span> Transaksi yang diajukan akan masuk antrean pemeriksaan PTU (Penguji Tagihan Unit BLU).
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

            <!-- CTA: Ajukan -->
            <button 
              type="button" 
              @click="submitTransaction('PROCESSING')" 
              :disabled="form.processing || isOverbudget || !form.amount || form.amount <= 0 || !form.title || !form.evidence_number || !form.budget_line_id"
              :class="[
                'px-6 py-2.5 rounded-2xl text-xs font-bold flex items-center gap-2 transition shadow-md',
                (isOverbudget || !form.amount || form.amount <= 0 || !form.title || !form.evidence_number || !form.budget_line_id)
                  ? 'bg-slate-300 text-slate-500 cursor-not-allowed shadow-none' 
                  : 'bg-sky-600 hover:bg-sky-500 text-white shadow-sky-600/20'
              ]"
            >
              <Check class="w-4 h-4" />
              <span>Ajukan Transaksi</span>
            </button>
          </div>
        </div>

      </form>
    </div>
  </AppLayout>
</template>
