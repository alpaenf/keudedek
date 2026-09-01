<script setup>
import { ref } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { 
  FileCheck, 
  Search, 
  Building2, 
  CheckCircle2, 
  RotateCcw, 
  XCircle, 
  Eye, 
  Lock, 
  ShieldCheck, 
  X,
  AlertTriangle,
  Clock,
  Printer,
  FileText,
  Wallet,
  Paperclip,
  Check,
  ChevronRight,
  Info,
  Calendar,
  Layers,
  ArrowRight
} from 'lucide-vue-next';

const props = defineProps({
  submissions: Object,
  departments: Array,
  activeTab: String,
  tabCounts: Object,
  filters: Object,
  userRole: String,
});

const search = ref(props.filters?.search || '');
const selectedDept = ref(props.filters?.department_id || '');

const handleFilter = (newTab = null) => {
  router.get('/approvals', {
    tab: newTab !== null ? newTab : props.activeTab,
    search: search.value || undefined,
    department_id: selectedDept.value || undefined,
  }, { preserveState: true, preserveScroll: true });
};

// Detail Drawer State
const isDrawerOpen = ref(false);
const activeSubmission = ref(null);

const openDrawer = (sub) => {
  activeSubmission.value = sub;
  isDrawerOpen.value = true;
};

const closeDrawer = () => {
  isDrawerOpen.value = false;
  activeSubmission.value = null;
};

// Action Modal State (Verify, Return, Finalize)
const isActionModalOpen = ref(false);
const currentAction = ref('VERIFY'); // 'VERIFY' | 'RETURN' | 'FINALIZE'
const actionForm = useForm({
  action: 'VERIFY',
  comment: '',
});

const openActionModal = (actionType, submission = null) => {
  if (submission) {
    activeSubmission.value = submission;
  }
  currentAction.value = actionType;
  actionForm.reset();
  actionForm.action = actionType;
  isActionModalOpen.value = true;
};

const closeActionModal = () => {
  isActionModalOpen.value = false;
  actionForm.reset();
};

const submitActionDecision = () => {
  if (!activeSubmission.value) return;

  actionForm.post(`/approvals/${activeSubmission.value.id}/decide`, {
    preserveScroll: true,
    onSuccess: () => {
      closeActionModal();
      closeDrawer();
    },
  });
};

const formatRupiah = (val) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(val || 0);
};

const getStatusBadge = (st) => {
  switch (st) {
    case 'FINAL':
    case 'COMPLETED':
      return { label: 'Final', class: 'bg-emerald-50 text-emerald-800 border-emerald-300' };
    case 'PROCESSING':
    case 'UNDER_REVIEW':
    case 'REVIEW':
    case 'APPROVED':
    case 'RESERVED':
      return { label: 'Dalam Proses', class: 'bg-indigo-50 text-indigo-800 border-indigo-300' };
    case 'RETURNED':
    case 'REVISION_REQUIRED':
      return { label: 'Dikembalikan', class: 'bg-amber-50 text-amber-800 border-amber-300' };
    case 'REJECTED':
    case 'CANCELLED':
      return { label: 'Dibatalkan', class: 'bg-rose-50 text-rose-800 border-rose-300' };
    default:
      return { label: 'Baru / Draft', class: 'bg-slate-100 text-slate-700 border-slate-300' };
  }
};
</script>

<template>
  <AppLayout title="Workbench Pemeriksaan Transaksi &amp; SPJ">
    <div class="space-y-6 font-sans">
      
      <!-- Top Title & Context Bar -->
      <div class="bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <span class="px-2.5 py-0.5 bg-indigo-100 text-indigo-800 text-[10px] font-black rounded-lg uppercase tracking-wider">
              Workbench Verifikasi
            </span>
            <span class="text-xs text-slate-400 font-semibold">&bull; PTU / Bendahara Pengeluaran Pembantu</span>
          </div>
          <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight mt-1">
            Antrean Pemeriksaan Transaksi &amp; SPJ (Page P21)
          </h1>
          <p class="text-xs text-slate-500 mt-0.5">
            Verifikasi fisik bukti kuitansi, kepatuhan pagu anggaran, pengembalian revisi, dan finalisasi realisasi definitif.
          </p>
        </div>

        <div class="flex items-center gap-2 text-xs font-bold text-slate-600 bg-slate-50 px-4 py-2 rounded-2xl border border-slate-200">
          <ShieldCheck class="w-4 h-4 text-emerald-600" />
          <span>Aturan RBC-001 &amp; SBM Aktif</span>
        </div>
      </div>

      <!-- 5 Interactive Tabs (Baru, Dalam Proses, Dikembalikan, Final, Issue) -->
      <div class="bg-white p-2 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-1.5 overflow-x-auto text-xs font-bold">
        <!-- 1. Baru -->
        <button 
          @click="handleFilter('NEW')"
          :class="[
            'px-4 py-2.5 rounded-xl transition flex items-center gap-2 whitespace-nowrap',
            activeTab === 'NEW' ? 'bg-sky-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          ]"
        >
          <span>Baru</span>
          <span :class="['px-2 py-0.5 rounded-full text-[10px] font-extrabold', activeTab === 'NEW' ? 'bg-sky-700 text-white' : 'bg-slate-200 text-slate-700']">
            {{ tabCounts?.new || 0 }}
          </span>
        </button>

        <!-- 2. Dalam Proses -->
        <button 
          @click="handleFilter('PROCESSING')"
          :class="[
            'px-4 py-2.5 rounded-xl transition flex items-center gap-2 whitespace-nowrap',
            activeTab === 'PROCESSING' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          ]"
        >
          <span>Dalam Proses</span>
          <span :class="['px-2 py-0.5 rounded-full text-[10px] font-extrabold', activeTab === 'PROCESSING' ? 'bg-indigo-700 text-white' : 'bg-slate-200 text-slate-700']">
            {{ tabCounts?.processing || 0 }}
          </span>
        </button>

        <!-- 3. Dikembalikan -->
        <button 
          @click="handleFilter('RETURNED')"
          :class="[
            'px-4 py-2.5 rounded-xl transition flex items-center gap-2 whitespace-nowrap',
            activeTab === 'RETURNED' ? 'bg-amber-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          ]"
        >
          <span>Dikembalikan</span>
          <span :class="['px-2 py-0.5 rounded-full text-[10px] font-extrabold', activeTab === 'RETURNED' ? 'bg-amber-700 text-white' : 'bg-slate-200 text-slate-700']">
            {{ tabCounts?.returned || 0 }}
          </span>
        </button>

        <!-- 4. Final -->
        <button 
          @click="handleFilter('FINAL')"
          :class="[
            'px-4 py-2.5 rounded-xl transition flex items-center gap-2 whitespace-nowrap',
            activeTab === 'FINAL' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          ]"
        >
          <span>Final</span>
          <span :class="['px-2 py-0.5 rounded-full text-[10px] font-extrabold', activeTab === 'FINAL' ? 'bg-emerald-700 text-white' : 'bg-slate-200 text-slate-700']">
            {{ tabCounts?.final || 0 }}
          </span>
        </button>

        <!-- 5. Issue -->
        <button 
          @click="handleFilter('ISSUE')"
          :class="[
            'px-4 py-2.5 rounded-xl transition flex items-center gap-2 whitespace-nowrap',
            activeTab === 'ISSUE' ? 'bg-rose-600 text-white shadow-sm' : 'text-rose-700 hover:bg-rose-50 hover:text-rose-900'
          ]"
        >
          <AlertTriangle class="w-3.5 h-3.5" />
          <span>Issue &amp; Peringatan</span>
          <span :class="['px-2 py-0.5 rounded-full text-[10px] font-extrabold', activeTab === 'ISSUE' ? 'bg-rose-700 text-white' : 'bg-rose-100 text-rose-800']">
            {{ tabCounts?.issue || 0 }}
          </span>
        </button>
      </div>

      <!-- Search & Department Filter Bar -->
      <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
        <div class="flex-1 w-full relative">
          <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-2.5" />
          <input 
            v-model="search" 
            @input="handleFilter(null)"
            type="text" 
            placeholder="Cari nomor bukti, nama PTK, judul kegiatan, atau kode akun..." 
            class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none transition"
          />
        </div>

        <div class="flex items-center gap-2 w-full sm:w-auto">
          <Building2 class="w-4 h-4 text-slate-400 shrink-0" />
          <select 
            v-model="selectedDept" 
            @change="handleFilter(null)"
            class="w-full sm:w-auto px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:ring-2 focus:ring-sky-500 focus:outline-none transition"
          >
            <option value="">Semua Jurusan</option>
            <option v-for="d in departments" :key="d.id" :value="d.id">
              {{ d.code }} &mdash; {{ d.name }}
            </option>
          </select>
        </div>
      </div>

      <!-- 9 Columns PTU / Bendahara Examination Table -->
      <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden space-y-4">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <FileText class="w-4 h-4 text-sky-600" />
            <h3 class="text-sm font-bold text-slate-900">Tabel Antrean Transaksi Masuk</h3>
          </div>
          <span class="text-xs text-slate-400 font-semibold">Total {{ submissions.total || 0 }} Transaksi</span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs font-sans border-collapse">
            <thead class="bg-slate-50 text-slate-400 uppercase tracking-wider text-[10px] border-b border-slate-200">
              <tr>
                <!-- 1. Nomor Bukti -->
                <th class="py-3 px-3.5 font-semibold">Nomor Bukti</th>
                <!-- 2. PTK -->
                <th class="py-3 px-3 font-semibold">PTK / Pembuat</th>
                <!-- 3. Jurusan -->
                <th class="py-3 px-3 font-semibold">Jurusan</th>
                <!-- 4. Akun -->
                <th class="py-3 px-3 font-semibold">Akun</th>
                <!-- 5. Uraian -->
                <th class="py-3 px-3.5 font-semibold">Uraian Belanja</th>
                <!-- 6. Nominal -->
                <th class="py-3 px-3 text-right font-semibold">Nominal (Rp)</th>
                <!-- 7. Status -->
                <th class="py-3 px-3 text-center font-semibold">Status</th>
                <!-- 8. Age -->
                <th class="py-3 px-3 font-semibold">Age (Umur Berkas)</th>
                <!-- 9. Action -->
                <th class="py-3 px-3.5 text-center font-semibold">Aksi Pemeriksaan</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr 
                v-for="sub in submissions.data" 
                :key="sub.id" 
                class="hover:bg-slate-50/70 transition cursor-pointer"
                @click="openDrawer(sub)"
              >
                <!-- 1. Nomor Bukti -->
                <td class="py-3.5 px-3.5 whitespace-nowrap">
                  <span class="font-sans font-black text-slate-900 block text-xs">
                    {{ sub.evidence_number || sub.submission_number }}
                  </span>
                  <span v-if="sub.submission_number" class="text-[10px] text-slate-400 block font-mono">
                    {{ sub.submission_number }}
                  </span>
                </td>

                <!-- 2. PTK -->
                <td class="py-3.5 px-3 whitespace-nowrap text-slate-700 font-bold">
                  {{ sub.creator?.name || 'Operator PTK' }}
                </td>

                <!-- 3. Jurusan -->
                <td class="py-3.5 px-3 whitespace-nowrap font-bold text-slate-900">
                  {{ sub.department?.code || 'FT' }}
                </td>

                <!-- 4. Akun -->
                <td class="py-3.5 px-3 whitespace-nowrap">
                  <span class="font-bold text-sky-900 bg-sky-50 px-2 py-0.5 rounded border border-sky-200 block text-center text-[11px]">
                    {{ sub.budget_bucket?.account_code || '-' }}
                  </span>
                  <span class="text-[10px] text-slate-500 block truncate max-w-[120px] mt-0.5">
                    {{ sub.budget_bucket?.account_name }}
                  </span>
                </td>

                <!-- 5. Uraian Belanja -->
                <td class="py-3.5 px-3.5 max-w-xs">
                  <div class="font-bold text-slate-900 line-clamp-1" :title="sub.title">
                    {{ sub.title }}
                  </div>
                  <div v-if="sub.notes" class="text-[10px] text-slate-400 line-clamp-1 mt-0.5">
                    {{ sub.notes }}
                  </div>
                </td>

                <!-- 6. Nominal (Rp) -->
                <td class="py-3.5 px-3 text-right font-black text-slate-900 font-sans whitespace-nowrap">
                  {{ formatRupiah(sub.amount) }}
                </td>

                <!-- 7. Status -->
                <td class="py-3.5 px-3 text-center whitespace-nowrap">
                  <span :class="['px-2.5 py-0.5 rounded-full text-[10px] border inline-block uppercase', getStatusBadge(sub.status).class]">
                    {{ getStatusBadge(sub.status).label }}
                  </span>
                </td>

                <!-- 8. Age -->
                <td class="py-3.5 px-3 whitespace-nowrap text-slate-500 text-[11px] font-medium">
                  <span class="flex items-center gap-1">
                    <Clock class="w-3.5 h-3.5 text-slate-400" />
                    <span>{{ sub.age_human }}</span>
                  </span>
                </td>

                <!-- 9. Action Buttons -->
                <td class="py-3.5 px-3.5 text-center whitespace-nowrap" @click.stop>
                  <div class="flex items-center justify-center gap-1.5">
                    <!-- Open Drawer Detail -->
                    <button 
                      type="button" 
                      @click="openDrawer(sub)"
                      class="px-2.5 py-1 bg-sky-50 hover:bg-sky-100 text-sky-700 border border-sky-200 rounded-lg text-[11px] font-bold transition inline-flex items-center gap-1"
                    >
                      <Eye class="w-3 h-3" />
                      <span>Periksa</span>
                    </button>

                    <!-- Quick Finalize (If in Processing) -->
                    <button 
                      v-if="['PROCESSING', 'UNDER_REVIEW', 'SUBMITTED'].includes(sub.status)"
                      type="button" 
                      @click="openActionModal('FINALIZE', sub)"
                      class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-[11px] font-bold transition inline-flex items-center gap-1 shadow-sm"
                      title="Finalisasi Transaksi"
                    >
                      <Check class="w-3 h-3" />
                      <span>Final</span>
                    </button>

                    <!-- Quick Return -->
                    <button 
                      v-if="['PROCESSING', 'UNDER_REVIEW', 'SUBMITTED'].includes(sub.status)"
                      type="button" 
                      @click="openActionModal('RETURN', sub)"
                      class="p-1 text-amber-600 hover:bg-amber-50 rounded-lg transition"
                      title="Kembalikan untuk Perbaikan"
                    >
                      <RotateCcw class="w-3.5 h-3.5" />
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="!submissions.data || submissions.data.length === 0">
                <td colspan="9" class="py-10 text-center text-slate-400">
                  Tidak ada data transaksi pada antrean ini.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="submissions.links && submissions.links.length > 3" class="p-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
          <span class="text-slate-500 font-medium">
            Menampilkan {{ submissions.from ?? 0 }} - {{ submissions.to ?? 0 }} dari {{ submissions.total }} transaksi
          </span>

          <div class="flex items-center gap-1">
            <Link
              v-for="(link, i) in submissions.links"
              :key="i"
              :href="link.url || '#'"
              v-html="link.label"
              :class="[
                'px-3 py-1.5 rounded-xl text-xs font-bold transition',
                link.active ? 'bg-sky-600 text-white shadow-sm' : 'bg-slate-50 text-slate-700 hover:bg-slate-100 border border-slate-200',
                !link.url ? 'opacity-40 cursor-not-allowed' : ''
              ]"
            />
          </div>
        </div>
      </div>

      <!-- ================================================== -->
      <!-- DETAIL DRAWER (SLIDE-OVER PANEL)                   -->
      <!-- ================================================== -->
      <div v-if="isDrawerOpen && activeSubmission" class="fixed inset-0 z-50 overflow-hidden">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" @click="closeDrawer"></div>

        <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
          <div class="w-screen max-w-2xl bg-white shadow-2xl flex flex-col justify-between">
            
            <!-- Drawer Header -->
            <div class="p-6 border-b border-slate-200 flex items-center justify-between bg-slate-50/70">
              <div>
                <div class="flex items-center gap-2">
                  <span class="font-mono font-black text-slate-900 text-base">
                    {{ activeSubmission.evidence_number || activeSubmission.submission_number }}
                  </span>
                  <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-bold border uppercase', getStatusBadge(activeSubmission.status).class]">
                    {{ getStatusBadge(activeSubmission.status).label }}
                  </span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                  Diajukan oleh: <strong class="text-slate-800">{{ activeSubmission.creator?.name }}</strong> &bull; {{ activeSubmission.age_human }}
                </p>
              </div>

              <button @click="closeDrawer" class="p-2 text-slate-400 hover:text-slate-700 rounded-xl hover:bg-slate-200 transition">
                <X class="w-5 h-5" />
              </button>
            </div>

            <!-- Drawer Body Scrollable Content -->
            <div class="p-6 space-y-6 overflow-y-auto flex-1 text-xs">
              
              <!-- 1. BUDGET CONTEXT (7 Segments RKAKL) -->
              <div class="bg-slate-50 rounded-2xl border border-slate-200/80 p-4 space-y-3">
                <div class="flex items-center justify-between border-b border-slate-200/60 pb-2">
                  <h4 class="font-bold text-slate-900 flex items-center gap-1.5">
                    <Layers class="w-4 h-4 text-sky-600" />
                    <span>Struktur Anggaran (Budget Context)</span>
                  </h4>
                  <span class="text-[10px] text-slate-400 font-bold uppercase">Master RKAKL</span>
                </div>

                <div class="grid grid-cols-2 gap-2 text-[11px]">
                  <div><span class="text-slate-400">Tahun Anggaran:</span> <strong class="text-slate-900">{{ activeSubmission.budget_context?.ta }}</strong></div>
                  <div><span class="text-slate-400">Sumber Dana:</span> <strong class="text-slate-900">{{ activeSubmission.budget_context?.sumber_dana }}</strong></div>
                  <div><span class="text-slate-400">Versi Revisi:</span> <strong class="text-sky-800">{{ activeSubmission.budget_context?.revision }}</strong></div>
                  <div><span class="text-slate-400">Jurusan:</span> <strong class="text-slate-900">{{ activeSubmission.budget_context?.jurusan_code }}</strong></div>
                </div>

                <div class="space-y-1 text-[11px] pt-2 border-t border-slate-200/60">
                  <div class="text-slate-600"><strong>Program:</strong> {{ activeSubmission.budget_context?.program_code }} &mdash; {{ activeSubmission.budget_context?.program_name }}</div>
                  <div class="text-slate-600"><strong>Kegiatan:</strong> {{ activeSubmission.budget_context?.activity_code }} &mdash; {{ activeSubmission.budget_context?.activity_name }}</div>
                  <div class="text-slate-600"><strong>KRO / RO:</strong> {{ activeSubmission.budget_context?.kro_code }} / {{ activeSubmission.budget_context?.ro_code }}</div>
                  <div class="text-slate-600"><strong>Subkomponen:</strong> {{ activeSubmission.budget_context?.subcomponent_code }} &mdash; {{ activeSubmission.budget_context?.subcomponent_name }}</div>
                  <div class="text-sky-950 font-bold"><strong>Akun:</strong> [{{ activeSubmission.budget_context?.account_code }}] {{ activeSubmission.budget_context?.account_name }}</div>
                </div>
              </div>

              <!-- 2. FINANCIAL CONTEXT SNAPSHOT -->
              <div class="bg-white rounded-2xl border border-slate-200 p-4 space-y-3 shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                  <h4 class="font-bold text-slate-900 flex items-center gap-1.5">
                    <Wallet class="w-4 h-4 text-emerald-600" />
                    <span>Konteks Finansial &amp; Sisa Saldo (Financial Context)</span>
                  </h4>
                  <span :class="['px-2 py-0.5 rounded text-[10px] font-bold', activeSubmission.financial_context?.is_solvent ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800']">
                    {{ activeSubmission.financial_context?.is_solvent ? '✓ Solven (Cukup)' : '✕ Overbudget' }}
                  </span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                  <div class="p-2.5 bg-slate-50 rounded-xl">
                    <span class="text-[10px] text-slate-400 uppercase font-bold block">Pagu Aktif</span>
                    <span class="font-black text-slate-900 font-sans text-xs">{{ formatRupiah(activeSubmission.financial_context?.allocated_budget) }}</span>
                  </div>
                  <div class="p-2.5 bg-slate-50 rounded-xl">
                    <span class="text-[10px] text-amber-700 uppercase font-bold block">Dalam Proses</span>
                    <span class="font-black text-amber-950 font-sans text-xs">{{ formatRupiah(activeSubmission.financial_context?.reserved_budget) }}</span>
                  </div>
                  <div class="p-2.5 bg-slate-50 rounded-xl">
                    <span class="text-[10px] text-sky-700 uppercase font-bold block">Realisasi</span>
                    <span class="font-black text-sky-950 font-sans text-xs">{{ formatRupiah(activeSubmission.financial_context?.realized_budget) }}</span>
                  </div>
                  <div class="p-2.5 bg-slate-50 rounded-xl">
                    <span class="text-[10px] text-emerald-700 uppercase font-bold block">Saldo Bebas</span>
                    <span class="font-black text-emerald-950 font-sans text-xs">{{ formatRupiah(activeSubmission.financial_context?.available_balance) }}</span>
                  </div>
                  <div class="p-2.5 bg-sky-50 rounded-xl border border-sky-200">
                    <span class="text-[10px] text-sky-800 uppercase font-bold block">Nominal Transaksi</span>
                    <span class="font-black text-sky-950 font-sans text-xs">{{ formatRupiah(activeSubmission.amount) }}</span>
                  </div>
                  <div class="p-2.5 rounded-xl border" :class="activeSubmission.financial_context?.is_solvent ? 'bg-emerald-50 border-emerald-200' : 'bg-rose-50 border-rose-200'">
                    <span class="text-[10px] uppercase font-bold block" :class="activeSubmission.financial_context?.is_solvent ? 'text-emerald-700' : 'text-rose-700'">Projected Sisa</span>
                    <span class="font-black font-sans text-xs" :class="activeSubmission.financial_context?.is_solvent ? 'text-emerald-950' : 'text-rose-950'">
                      {{ formatRupiah(activeSubmission.financial_context?.projected_balance) }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- 3. RULE CHECK (EWS / RBC) -->
              <div class="bg-slate-50 rounded-2xl border border-slate-200 p-4 space-y-2">
                <h4 class="font-bold text-slate-900 flex items-center gap-1.5">
                  <ShieldCheck class="w-4 h-4 text-indigo-600" />
                  <span>Hasil Evaluasi Aturan Sistem (Rule Check)</span>
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px] pt-1">
                  <div class="p-2 bg-white rounded-xl border flex items-center justify-between">
                    <span class="text-slate-600">RBC-001 (Kecukupan Saldo)</span>
                    <span :class="['font-bold', activeSubmission.rule_check?.rbc_001_solvency === 'PASSED' ? 'text-emerald-700' : 'text-rose-700']">
                      {{ activeSubmission.rule_check?.rbc_001_solvency }}
                    </span>
                  </div>
                  <div class="p-2 bg-white rounded-xl border flex items-center justify-between">
                    <span class="text-slate-600">RBC-006 (Uji Duplikasi)</span>
                    <span class="font-bold text-emerald-700">PASSED</span>
                  </div>
                </div>
              </div>

              <!-- 4. ATTACHMENTS (Berkas SPJ / Kuitansi) -->
              <div class="bg-white rounded-2xl border border-slate-200 p-4 space-y-2">
                <h4 class="font-bold text-slate-900 flex items-center gap-1.5">
                  <Paperclip class="w-4 h-4 text-slate-600" />
                  <span>Lampiran Berkas Transaksi (Attachments)</span>
                </h4>
                <div v-if="activeSubmission.documents && activeSubmission.documents.length > 0" class="space-y-1.5 pt-1">
                  <div 
                    v-for="doc in activeSubmission.documents" 
                    :key="doc.id"
                    class="p-2.5 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between text-[11px]"
                  >
                    <span class="font-medium text-slate-800">{{ doc.original_filename || doc.document_type?.name || 'Berkas Lampiran Kuitansi' }}</span>
                    <a :href="`/submissions/documents/${doc.id}/download`" target="_blank" class="text-sky-600 font-bold hover:underline">
                      Unduh Berkas
                    </a>
                  </div>
                </div>
                <div v-else class="text-slate-400 italic text-[11px]">
                  Tidak ada berkas lampiran digital yang diunggah.
                </div>
              </div>

              <!-- 5. HISTORY & AUDIT TRAIL TIMELINE -->
              <div class="bg-white rounded-2xl border border-slate-200 p-4 space-y-3">
                <h4 class="font-bold text-slate-900 flex items-center gap-1.5">
                  <Clock class="w-4 h-4 text-slate-600" />
                  <span>Riwayat Status &amp; Timeline Audit (History)</span>
                </h4>
                <div class="space-y-2 border-l-2 border-slate-200 pl-3 ml-1 text-[11px]">
                  <div v-for="h in activeSubmission.status_histories" :key="h.id" class="space-y-0.5 relative">
                    <div class="font-bold text-slate-900">{{ h.to_status }} &bull; {{ h.actor?.name || 'Sistem' }}</div>
                    <div class="text-slate-500">{{ h.notes }}</div>
                    <div class="text-[10px] text-slate-400">{{ new Date(h.created_at).toLocaleString('id-ID') }}</div>
                  </div>
                  <div v-if="!activeSubmission.status_histories || activeSubmission.status_histories.length === 0" class="text-slate-400 italic">
                    Belum ada riwayat transisi status.
                  </div>
                </div>
              </div>

            </div>

            <!-- Drawer Footer Action Buttons -->
            <div class="p-6 border-t border-slate-200 bg-slate-50 flex items-center justify-between gap-3">
              <a 
                :href="`/submissions/${activeSubmission.id}/print`" 
                target="_blank"
                class="px-4 py-2.5 bg-white hover:bg-slate-100 border border-slate-300 text-slate-700 rounded-2xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm"
              >
                <Printer class="w-4 h-4" />
                <span>Cetak Lembar SPJ</span>
              </a>

              <div class="flex items-center gap-2">
                <!-- Action: Return -->
                <button 
                  v-if="['PROCESSING', 'UNDER_REVIEW', 'SUBMITTED'].includes(activeSubmission.status)"
                  type="button" 
                  @click="openActionModal('RETURN', activeSubmission)"
                  class="px-4 py-2.5 bg-amber-50 hover:bg-amber-100 border border-amber-300 text-amber-800 rounded-2xl text-xs font-bold transition"
                >
                  Return (Kembalikan)
                </button>

                <!-- Action: Verify -->
                <button 
                  v-if="['SUBMITTED', 'DRAFT'].includes(activeSubmission.status)"
                  type="button" 
                  @click="openActionModal('VERIFY', activeSubmission)"
                  class="px-4 py-2.5 bg-sky-600 hover:bg-sky-500 text-white rounded-2xl text-xs font-bold transition shadow-sm"
                >
                  Verify (Verifikasi)
                </button>

                <!-- Action: Finalize -->
                <button 
                  v-if="['PROCESSING', 'UNDER_REVIEW', 'SUBMITTED', 'APPROVED'].includes(activeSubmission.status)"
                  type="button" 
                  @click="openActionModal('FINALIZE', activeSubmission)"
                  class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-2xl text-xs font-bold transition flex items-center gap-1.5 shadow-md shadow-emerald-600/20"
                >
                  <Check class="w-4 h-4" />
                  <span>Finalize (Realisasi)</span>
                </button>
              </div>
            </div>

          </div>
        </div>
      </div>

      <!-- ================================================== -->
      <!-- ACTION CONFIRMATION MODAL (VERIFY / RETURN / FINAL)-->
      <!-- ================================================== -->
      <div v-if="isActionModalOpen && activeSubmission" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="closeActionModal"></div>

        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-lg w-full p-6 space-y-4 relative z-10 text-xs font-sans">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
              <span v-if="currentAction === 'VERIFY'" class="text-sky-600">Verifikasi Berkas Transaksi</span>
              <span v-else-if="currentAction === 'RETURN'" class="text-amber-600">Kembalikan Transaksi ke PTK</span>
              <span v-else class="text-emerald-600">Finalisasi Pencairan &amp; Realisasi Anggaran</span>
            </h3>
            <button @click="closeActionModal" class="p-1 text-slate-400 hover:text-slate-700">
              <X class="w-4 h-4" />
            </button>
          </div>

          <!-- Transaction Summary -->
          <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200 space-y-1">
            <div class="font-bold text-slate-900 text-sm">{{ activeSubmission.evidence_number || activeSubmission.submission_number }}</div>
            <div class="text-slate-600">{{ activeSubmission.title }}</div>
            <div class="font-black font-sans text-sky-950 pt-1">{{ formatRupiah(activeSubmission.amount) }}</div>
          </div>

          <!-- Return Warning (Mandatory Reason) -->
          <div v-if="currentAction === 'RETURN'" class="p-3 bg-amber-50 rounded-2xl border border-amber-200 text-amber-900 text-[11px]">
            <strong>Perhatian:</strong> Berkas akan dikembalikan ke status <strong>DIKEMBALIKAN</strong> dan reservasi saldo akan dibebaskan kembali. Anda <strong>wajib</strong> mengisi catatan alasan pengembalian di bawah.
          </div>

          <!-- Finalize Warning (Backend Transactional) -->
          <div v-if="currentAction === 'FINALIZE'" class="p-3 bg-emerald-50 rounded-2xl border border-emerald-200 text-emerald-950 text-[11px]">
            <strong>Backend Transactional Realization:</strong> Nominal transaksi akan dipotong definitif dari pagu (masuk ke Realisasi) secara atomik dan status transaksi berubah menjadi <strong>FINAL</strong>.
          </div>

          <!-- Comment Input -->
          <div>
            <label class="block font-bold text-slate-700 mb-1">
              Catatan / Alasan Keputusan <span v-if="currentAction === 'RETURN'" class="text-rose-600">* (Wajib)</span>
            </label>
            <textarea 
              v-model="actionForm.comment" 
              rows="3" 
              :required="currentAction === 'RETURN'"
              placeholder="Tuliskan catatan pemeriksaan atau alasan pengembalian..."
              class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none"
            ></textarea>
            <div v-if="actionForm.errors.comment" class="text-rose-600 text-[11px] mt-1">{{ actionForm.errors.comment }}</div>
          </div>

          <!-- Modal Action Buttons -->
          <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100">
            <button 
              type="button" 
              @click="closeActionModal" 
              class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold transition"
            >
              Batal
            </button>

            <button 
              type="button" 
              @click="submitActionDecision"
              :disabled="actionForm.processing || (currentAction === 'RETURN' && !actionForm.comment.trim())"
              :class="[
                'px-5 py-2 rounded-xl font-bold transition text-white shadow-sm flex items-center gap-1.5 disabled:opacity-50',
                currentAction === 'RETURN' ? 'bg-amber-600 hover:bg-amber-500' : currentAction === 'FINALIZE' ? 'bg-emerald-600 hover:bg-emerald-500' : 'bg-sky-600 hover:bg-sky-500'
              ]"
            >
              <Check class="w-4 h-4" />
              <span>Konfirmasi {{ currentAction === 'RETURN' ? 'Kembalikan' : currentAction === 'FINALIZE' ? 'Finalisasi' : 'Verifikasi' }}</span>
            </button>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>
