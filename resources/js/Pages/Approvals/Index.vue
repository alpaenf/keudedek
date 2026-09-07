<script setup>
import { ref } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { 
  FileCheck, 
  Search, 
  Building2, 
  RotateCcw, 
  XCircle, 
  Eye, 
  ShieldCheck, 
  X, 
  Clock, 
  Printer, 
  FileText, 
  Wallet, 
  Paperclip, 
  Check, 
  Layers, 
  AlertCircle,
  Tag
} from 'lucide-vue-next';

const props = defineProps({
  submissions: Object,
  departments: Array,
  activeTab: String,
  tabCounts: Object,
  filters: Object,
  userRole: String,
  canFinalize: Boolean,
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

// Action Modal State (KEMBALIKAN, TOLAK, SELESAI)
const isActionModalOpen = ref(false);
const currentAction = ref('SELESAI'); // 'KEMBALIKAN' | 'TOLAK' | 'SELESAI'
const actionForm = useForm({
  action: 'SELESAI',
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
      return { label: 'Selesai', class: 'bg-emerald-50 text-emerald-800 border-emerald-300' };
    case 'PROCESSING':
    case 'UNDER_REVIEW':
    case 'REVIEW':
    case 'APPROVED':
    case 'RESERVED':
    case 'SUBMITTED':
      return { label: 'Diajukan', class: 'bg-indigo-50 text-indigo-800 border-indigo-300' };
    case 'RETURNED':
    case 'REVISION_REQUIRED':
      return { label: 'Dikembalikan', class: 'bg-amber-50 text-amber-800 border-amber-300' };
    case 'REJECTED':
    case 'CANCELLED':
      return { label: 'Ditolak', class: 'bg-rose-50 text-rose-800 border-rose-300' };
    default:
      return { label: 'Draft', class: 'bg-slate-100 text-slate-700 border-slate-300' };
  }
};
</script>

<template>
  <AppLayout title="Pemeriksaan Transaksi &amp; SPJ">
    <div class="space-y-6 font-sans">
      
      <!-- Top Title & Context Bar -->
      <div class="bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <span class="px-2.5 py-0.5 bg-indigo-100 text-indigo-800 text-[10px] font-black rounded-lg uppercase tracking-wider">
              Pemeriksaan Transaksi
            </span>
            <span class="text-xs text-slate-500 font-semibold">&bull; PTU / Bendahara Fakultas</span>
          </div>
          <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight mt-1">
            Pemeriksaan Transaksi
          </h1>
          <p class="text-xs text-slate-500 mt-0.5">
            Pemeriksaan kepatuhan bukti belanja, kelengkapan SPJ PTK, pelepasan komitmen (Kembalikan/Tolak), dan pembukuan realisasi definitif.
          </p>
        </div>

        <div class="flex items-center gap-2 text-xs font-bold text-slate-600 bg-slate-50 px-4 py-2 rounded-2xl border border-slate-200">
          <ShieldCheck class="w-4 h-4 text-emerald-600" />
          <span>Rule-Based Budget Control (RBC) Aktif</span>
        </div>
      </div>

      <!-- Filter Tabs: DIAJUKAN (Default), SELESAI, DIKEMBALIKAN, DITOLAK, SEMUA -->
      <div class="bg-white p-2 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-1.5 overflow-x-auto text-xs font-bold">
        <!-- 1. DIAJUKAN (Default Queue) -->
        <button 
          @click="handleFilter('DIAJUKAN')"
          :class="[
            'px-4 py-2.5 rounded-xl transition flex items-center gap-2 whitespace-nowrap',
            activeTab === 'DIAJUKAN' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          ]"
        >
          <span>Diajukan</span>
          <span :class="['px-2 py-0.5 rounded-full text-[10px] font-extrabold', activeTab === 'DIAJUKAN' ? 'bg-indigo-700 text-white' : 'bg-slate-200 text-slate-700']">
            {{ tabCounts?.diajukan || 0 }}
          </span>
        </button>

        <!-- 2. SELESAI -->
        <button 
          @click="handleFilter('SELESAI')"
          :class="[
            'px-4 py-2.5 rounded-xl transition flex items-center gap-2 whitespace-nowrap',
            activeTab === 'SELESAI' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          ]"
        >
          <span>Selesai</span>
          <span :class="['px-2 py-0.5 rounded-full text-[10px] font-extrabold', activeTab === 'SELESAI' ? 'bg-emerald-700 text-white' : 'bg-slate-200 text-slate-700']">
            {{ tabCounts?.selesai || 0 }}
          </span>
        </button>

        <!-- 3. DIKEMBALIKAN -->
        <button 
          @click="handleFilter('DIKEMBALIKAN')"
          :class="[
            'px-4 py-2.5 rounded-xl transition flex items-center gap-2 whitespace-nowrap',
            activeTab === 'DIKEMBALIKAN' ? 'bg-amber-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          ]"
        >
          <span>Dikembalikan</span>
          <span :class="['px-2 py-0.5 rounded-full text-[10px] font-extrabold', activeTab === 'DIKEMBALIKAN' ? 'bg-amber-700 text-white' : 'bg-slate-200 text-slate-700']">
            {{ tabCounts?.dikembalikan || 0 }}
          </span>
        </button>

        <!-- 4. DITOLAK -->
        <button 
          @click="handleFilter('DITOLAK')"
          :class="[
            'px-4 py-2.5 rounded-xl transition flex items-center gap-2 whitespace-nowrap',
            activeTab === 'DITOLAK' ? 'bg-rose-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          ]"
        >
          <span>Ditolak</span>
          <span :class="['px-2 py-0.5 rounded-full text-[10px] font-extrabold', activeTab === 'DITOLAK' ? 'bg-rose-700 text-white' : 'bg-slate-200 text-slate-700']">
            {{ tabCounts?.ditolak || 0 }}
          </span>
        </button>

        <!-- 5. SEMUA -->
        <button 
          @click="handleFilter('ALL')"
          :class="[
            'px-4 py-2.5 rounded-xl transition flex items-center gap-2 whitespace-nowrap',
            activeTab === 'ALL' ? 'bg-slate-800 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          ]"
        >
          <span>Semua Transaksi</span>
          <span :class="['px-2 py-0.5 rounded-full text-[10px] font-extrabold', activeTab === 'ALL' ? 'bg-slate-900 text-white' : 'bg-slate-200 text-slate-700']">
            {{ tabCounts?.all || 0 }}
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
            placeholder="Cari No FRA, No RBA, uraian belanja, PTK, atau kode akun..." 
            class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none transition"
          />
        </div>

        <div class="flex items-center gap-2 w-full sm:w-auto">
          <Building2 class="w-4 h-4 text-slate-400 shrink-0" />
          <select 
            v-model="selectedDept" 
            @change="handleFilter(null)"
            class="w-full sm:w-auto px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:outline-none transition"
          >
            <option value="">Semua Jurusan</option>
            <option v-for="d in departments" :key="d.id" :value="d.id">
              {{ d.code }} &mdash; {{ d.name }}
            </option>
          </select>
        </div>
      </div>

      <!-- Main Queue Table: No FRA, Tanggal, PTK, Jurusan, No RBA, Uraian, Nominal, Aging, Tombol Periksa -->
      <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden space-y-4">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <FileCheck class="w-4 h-4 text-indigo-600" />
            <h3 class="text-sm font-bold text-slate-900">Antrean Pemeriksaan Transaksi</h3>
          </div>
          <span class="text-xs text-slate-400 font-semibold">Total {{ submissions.total || 0 }} Transaksi</span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs font-sans border-collapse">
            <thead class="bg-slate-50 text-slate-400 uppercase tracking-wider text-[10px] border-b border-slate-200">
              <tr>
                <th class="py-3 px-3.5 font-semibold">No FRA / Bukti</th>
                <th class="py-3 px-3 font-semibold">Tanggal</th>
                <th class="py-3 px-3 font-semibold">PTK</th>
                <th class="py-3 px-3 font-semibold">Jurusan</th>
                <th class="py-3 px-3 font-semibold">No RBA</th>
                <th class="py-3 px-3.5 font-semibold">Uraian Belanja</th>
                <th class="py-3 px-3 text-right font-semibold">Nominal (Rp)</th>
                <th class="py-3 px-3 font-semibold">Aging</th>
                <th class="py-3 px-3.5 text-center font-semibold">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr 
                v-for="sub in submissions.data" 
                :key="sub.id" 
                class="hover:bg-slate-50/70 transition cursor-pointer"
                @click="openDrawer(sub)"
              >
                <!-- 1. No FRA / Bukti -->
                <td class="py-3.5 px-3.5 whitespace-nowrap">
                  <span class="font-sans font-black text-slate-900 block text-xs">
                    {{ sub.evidence_number || sub.submission_number }}
                  </span>
                  <span v-if="sub.submission_number && sub.submission_number !== sub.evidence_number" class="text-[10px] text-slate-400 block font-mono">
                    {{ sub.submission_number }}
                  </span>
                </td>

                <!-- 2. Tanggal -->
                <td class="py-3.5 px-3 whitespace-nowrap text-slate-600 font-medium">
                  {{ sub.transaction_date ? new Date(sub.transaction_date).toLocaleDateString('id-ID') : '-' }}
                </td>

                <!-- 3. PTK -->
                <td class="py-3.5 px-3 whitespace-nowrap text-slate-800 font-bold">
                  {{ sub.creator?.name || 'Operator PTK' }}
                </td>

                <!-- 4. Jurusan -->
                <td class="py-3.5 px-3 whitespace-nowrap font-bold text-slate-900">
                  <span class="px-2 py-0.5 bg-slate-100 rounded text-slate-800 border border-slate-200">
                    {{ sub.department?.code || 'FT' }}
                  </span>
                </td>

                <!-- 5. No RBA -->
                <td class="py-3.5 px-3 whitespace-nowrap">
                  <span class="font-bold text-indigo-900 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-200 block text-center text-[11px]">
                    {{ sub.budget_context?.rba_sequence_no || sub.budget_line?.rba_sequence_no || '-' }}
                  </span>
                </td>

                <!-- 6. Uraian -->
                <td class="py-3.5 px-3.5 max-w-xs">
                  <div class="font-bold text-slate-900 line-clamp-1" :title="sub.title">
                    {{ sub.title }}
                  </div>
                  <div v-if="sub.notes" class="text-[10px] text-slate-400 line-clamp-1 mt-0.5">
                    {{ sub.notes }}
                  </div>
                </td>

                <!-- 7. Nominal -->
                <td class="py-3.5 px-3 text-right font-black text-slate-900 font-sans whitespace-nowrap">
                  {{ formatRupiah(sub.amount) }}
                </td>

                <!-- 8. Aging -->
                <td class="py-3.5 px-3 whitespace-nowrap text-slate-500 text-[11px] font-medium">
                  <span class="flex items-center gap-1">
                    <Clock class="w-3.5 h-3.5 text-slate-400" />
                    <span>{{ sub.age_human }}</span>
                  </span>
                </td>

                <!-- 9. Tombol Periksa -->
                <td class="py-3.5 px-3.5 text-center whitespace-nowrap" @click.stop>
                  <button 
                    type="button" 
                    @click="openDrawer(sub)"
                    class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-xl text-xs font-bold transition inline-flex items-center gap-1.5 shadow-xs"
                  >
                    <Eye class="w-3.5 h-3.5" />
                    <span>Periksa</span>
                  </button>
                </td>
              </tr>

              <tr v-if="!submissions.data || submissions.data.length === 0">
                <td colspan="9" class="py-12 text-center text-slate-400">
                  Tidak ada transaksi dalam antrean pemeriksaan ini.
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
                link.active ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-50 text-slate-700 hover:bg-slate-100 border border-slate-200',
                !link.url ? 'opacity-40 cursor-not-allowed' : ''
              ]"
            />
          </div>
        </div>
      </div>

      <!-- ================================================== -->
      <!-- DETAIL PEMERIKSAAN (SLIDE-OVER DRAWER)             -->
      <!-- 8 Point: Data Transaksi, Budget Line / No RBA,     -->
      <!-- Hierarchy Ringkas, Financial Snapshot, RBC Result, -->
      <!-- Lampiran, Timeline Status, Catatan                -->
      <!-- ================================================== -->
      <div v-if="isDrawerOpen && activeSubmission" class="fixed inset-0 z-50 overflow-hidden">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" @click="closeDrawer"></div>

        <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
          <div class="w-screen max-w-2xl bg-white shadow-2xl flex flex-col justify-between">
            
            <!-- Drawer Header -->
            <div class="p-6 border-b border-slate-200 flex items-center justify-between bg-slate-50/80">
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
                  Diajukan oleh: <strong class="text-slate-800">{{ activeSubmission.creator?.name }}</strong> &bull; Aging: {{ activeSubmission.age_human }}
                </p>
              </div>

              <button @click="closeDrawer" class="p-2 text-slate-400 hover:text-slate-700 rounded-xl hover:bg-slate-200 transition">
                <X class="w-5 h-5" />
              </button>
            </div>

            <!-- Drawer Body: 8 Points Detail Pemeriksaan -->
            <div class="p-6 space-y-5 overflow-y-auto flex-1 text-xs">
              
              <!-- 1. DATA TRANSAKSI -->
              <div class="bg-white rounded-2xl border border-slate-200 p-4 space-y-2 shadow-xs">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                  <h4 class="font-bold text-slate-900 flex items-center gap-1.5">
                    <FileText class="w-4 h-4 text-indigo-600" />
                    <span>1. Data Transaksi</span>
                  </h4>
                  <span class="font-black text-indigo-950 font-sans text-sm">{{ formatRupiah(activeSubmission.amount) }}</span>
                </div>
                <div class="grid grid-cols-2 gap-2 text-[11px] pt-1">
                  <div><span class="text-slate-400">No FRA / Bukti:</span> <strong class="text-slate-900 font-mono">{{ activeSubmission.evidence_number }}</strong></div>
                  <div><span class="text-slate-400">Tanggal:</span> <strong class="text-slate-900">{{ activeSubmission.transaction_date ? new Date(activeSubmission.transaction_date).toLocaleDateString('id-ID') : '-' }}</strong></div>
                  <div><span class="text-slate-400">Jurusan:</span> <strong class="text-slate-900">{{ activeSubmission.department?.name }}</strong></div>
                  <div><span class="text-slate-400">Program Studi:</span> <strong class="text-slate-900">{{ activeSubmission.study_program?.name || 'Level Jurusan' }}</strong></div>
                </div>
                <div class="pt-2 text-[11px] border-t border-slate-100">
                  <span class="text-slate-400 block">Uraian Transaksi:</span>
                  <span class="font-bold text-slate-800 text-xs mt-0.5 block">{{ activeSubmission.title }}</span>
                </div>
              </div>

              <!-- 2. BUDGET LINE / NO RBA -->
              <div class="bg-indigo-50/50 rounded-2xl border border-indigo-200/80 p-4 space-y-2">
                <div class="flex items-center justify-between border-b border-indigo-200/60 pb-2">
                  <h4 class="font-bold text-indigo-950 flex items-center gap-1.5">
                    <Tag class="w-4 h-4 text-indigo-600" />
                    <span>2. Budget Line &amp; No Urut RBA</span>
                  </h4>
                  <span class="px-2.5 py-0.5 bg-indigo-600 text-white text-[10px] font-black rounded-lg">
                    RBA #{{ activeSubmission.budget_context?.rba_sequence_no }}
                  </span>
                </div>
                <div class="text-[11px] space-y-1 pt-1">
                  <div class="text-indigo-900"><strong>Uraian RBA:</strong> {{ activeSubmission.budget_context?.rba_description }}</div>
                  <div class="text-indigo-900"><strong>Pagu Baris RBA:</strong> {{ formatRupiah(activeSubmission.financial_context?.line_budget) }}</div>
                  <div class="text-indigo-900"><strong>Sisa Saldo Baris RBA:</strong> {{ formatRupiah(activeSubmission.financial_context?.line_saldo) }}</div>
                </div>
              </div>

              <!-- 3. HIERARCHY RINGKAS -->
              <div class="bg-slate-50 rounded-2xl border border-slate-200 p-4 space-y-2">
                <h4 class="font-bold text-slate-900 flex items-center gap-1.5 border-b border-slate-200/60 pb-2">
                  <Layers class="w-4 h-4 text-slate-600" />
                  <span>3. Hierarchy Anggaran Ringkas</span>
                </h4>
                <div class="grid grid-cols-2 gap-1.5 text-[11px] pt-1">
                  <div><span class="text-slate-400">Tahun Anggaran:</span> <strong class="text-slate-800">{{ activeSubmission.budget_context?.ta }}</strong></div>
                  <div><span class="text-slate-400">Sumber Dana:</span> <strong class="text-slate-800">{{ activeSubmission.budget_context?.sumber_dana }}</strong></div>
                  <div><span class="text-slate-400">Versi Revisi:</span> <strong class="text-slate-800">{{ activeSubmission.budget_context?.revision }}</strong></div>
                  <div><span class="text-slate-400">Subkomponen:</span> <strong class="text-slate-800">[{{ activeSubmission.budget_context?.subcomponent_code }}] {{ activeSubmission.budget_context?.subcomponent_name }}</strong></div>
                </div>
                <div class="pt-1.5 border-t border-slate-200/60 text-[11px]">
                  <span class="text-indigo-950 font-bold">Akun Pengendali (Control Bucket):</span>
                  <div class="text-slate-800 font-bold mt-0.5">
                    [{{ activeSubmission.budget_context?.account_code }}] {{ activeSubmission.budget_context?.account_name }}
                  </div>
                </div>
              </div>

              <!-- 4. FINANCIAL SNAPSHOT (Single Source of Truth) -->
              <div class="bg-white rounded-2xl border border-slate-200 p-4 space-y-3 shadow-xs">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                  <h4 class="font-bold text-slate-900 flex items-center gap-1.5">
                    <Wallet class="w-4 h-4 text-emerald-600" />
                    <span>4. Financial Snapshot (Control Bucket)</span>
                  </h4>
                  <span :class="['px-2 py-0.5 rounded text-[10px] font-bold', activeSubmission.financial_context?.is_solvent ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800']">
                    {{ activeSubmission.financial_context?.is_solvent ? '✓ Saldo Tersedia Cukup' : '✕ Defisit / Overbudget' }}
                  </span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                  <div class="p-2.5 bg-slate-50 rounded-xl">
                    <span class="text-[10px] text-slate-400 uppercase font-bold block">Pagu Bucket</span>
                    <span class="font-black text-slate-900 font-sans text-xs">{{ formatRupiah(activeSubmission.financial_context?.allocated_budget) }}</span>
                  </div>
                  <div class="p-2.5 bg-slate-50 rounded-xl">
                    <span class="text-[10px] text-amber-700 uppercase font-bold block">Active Commitment</span>
                    <span class="font-black text-amber-950 font-sans text-xs">{{ formatRupiah(activeSubmission.financial_context?.reserved_budget) }}</span>
                  </div>
                  <div class="p-2.5 bg-slate-50 rounded-xl">
                    <span class="text-[10px] text-sky-700 uppercase font-bold block">Internal Realisasi</span>
                    <span class="font-black text-sky-950 font-sans text-xs">{{ formatRupiah(activeSubmission.financial_context?.realized_budget) }}</span>
                  </div>
                  <div class="p-2.5 bg-emerald-50 rounded-xl border border-emerald-200">
                    <span class="text-[10px] text-emerald-800 uppercase font-bold block">Saldo Bebas</span>
                    <span class="font-black text-emerald-950 font-sans text-xs">{{ formatRupiah(activeSubmission.financial_context?.available_balance) }}</span>
                  </div>
                </div>
              </div>

              <!-- 5. RBC RESULT -->
              <div class="bg-slate-50 rounded-2xl border border-slate-200 p-4 space-y-2">
                <h4 class="font-bold text-slate-900 flex items-center gap-1.5 border-b border-slate-200/60 pb-2">
                  <ShieldCheck class="w-4 h-4 text-indigo-600" />
                  <span>5. Hasil Rule-Based Budget Control (RBC)</span>
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px] pt-1">
                  <div class="p-2.5 bg-white rounded-xl border flex items-center justify-between">
                    <span class="text-slate-600">RBC-001 (Available Balance Check)</span>
                    <span :class="['font-bold', activeSubmission.rule_check?.rbc_001_solvency === 'PASSED' ? 'text-emerald-700' : 'text-rose-700']">
                      {{ activeSubmission.rule_check?.rbc_001_solvency }}
                    </span>
                  </div>
                  <div class="p-2.5 bg-white rounded-xl border flex items-center justify-between">
                    <span class="text-slate-600">RBC-006 (Duplicate Ref Check)</span>
                    <span :class="['font-bold', activeSubmission.rule_check?.rbc_006_duplicate === 'PASSED' ? 'text-emerald-700' : 'text-amber-700']">
                      {{ activeSubmission.rule_check?.rbc_006_duplicate }}
                    </span>
                  </div>
                </div>
                <div v-if="activeSubmission.rule_check?.duplicate_message" class="p-2 bg-amber-50 rounded-xl border border-amber-200 text-amber-800 text-[11px]">
                  {{ activeSubmission.rule_check?.duplicate_message }}
                </div>
              </div>

              <!-- 6. LAMPIRAN -->
              <div class="bg-white rounded-2xl border border-slate-200 p-4 space-y-2 shadow-xs">
                <h4 class="font-bold text-slate-900 flex items-center gap-1.5 border-b border-slate-100 pb-2">
                  <Paperclip class="w-4 h-4 text-slate-600" />
                  <span>6. Lampiran Kuitansi &amp; Dokumen Pendukung</span>
                </h4>
                <div v-if="activeSubmission.documents && activeSubmission.documents.length > 0" class="space-y-1.5 pt-1">
                  <div 
                    v-for="doc in activeSubmission.documents" 
                    :key="doc.id"
                    class="p-2.5 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between text-[11px]"
                  >
                    <span class="font-medium text-slate-800 truncate max-w-sm">{{ doc.original_filename || doc.document_type?.name || 'Berkas Lampiran' }}</span>
                    <a :href="`/submissions/documents/${doc.id}/download`" target="_blank" class="text-indigo-600 font-bold hover:underline shrink-0 ml-2">
                      Unduh Berkas
                    </a>
                  </div>
                </div>
                <div v-else class="text-slate-400 italic text-[11px] py-1">
                  Tidak ada berkas lampiran digital yang diunggah.
                </div>
              </div>

              <!-- 7. TIMELINE STATUS -->
              <div class="bg-white rounded-2xl border border-slate-200 p-4 space-y-3 shadow-xs">
                <h4 class="font-bold text-slate-900 flex items-center gap-1.5 border-b border-slate-100 pb-2">
                  <Clock class="w-4 h-4 text-slate-600" />
                  <span>7. Timeline Riwayat Status</span>
                </h4>
                <div class="space-y-2 border-l-2 border-slate-200 pl-3 ml-1 text-[11px]">
                  <div v-for="h in activeSubmission.status_histories" :key="h.id" class="space-y-0.5 relative">
                    <div class="font-bold text-slate-900">{{ h.to_status }} &bull; {{ h.actor?.name || 'Sistem' }} ({{ h.role }})</div>
                    <div class="text-slate-500">{{ h.notes }}</div>
                    <div class="text-[10px] text-slate-400">{{ new Date(h.created_at).toLocaleString('id-ID') }}</div>
                  </div>
                  <div v-if="!activeSubmission.status_histories || activeSubmission.status_histories.length === 0" class="text-slate-400 italic">
                    Belum ada riwayat transisi status.
                  </div>
                </div>
              </div>

              <!-- 8. CATATAN PEMERIKSAAN -->
              <div class="bg-slate-50 rounded-2xl border border-slate-200 p-4 space-y-2">
                <h4 class="font-bold text-slate-900 flex items-center gap-1.5 border-b border-slate-200/60 pb-2">
                  <AlertCircle class="w-4 h-4 text-slate-600" />
                  <span>8. Catatan Transaksi</span>
                </h4>
                <div class="text-slate-700 text-[11px] pt-1 leading-relaxed">
                  {{ activeSubmission.notes || 'Tidak ada catatan khusus pada transaksi ini.' }}
                </div>
              </div>

            </div>

            <!-- Drawer Footer: Aksi KEMBALIKAN, TOLAK, SELESAI -->
            <div class="p-6 border-t border-slate-200 bg-slate-50 flex items-center justify-between gap-3">
              <a 
                :href="`/submissions/${activeSubmission.id}/print`" 
                target="_blank"
                class="px-4 py-2.5 bg-white hover:bg-slate-100 border border-slate-300 text-slate-700 rounded-2xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs"
              >
                <Printer class="w-4 h-4" />
                <span>Cetak SPJ</span>
              </a>

              <!-- Action buttons for DIAJUKAN state -->
              <div v-if="['PROCESSING', 'SUBMITTED', 'UNDER_REVIEW', 'APPROVED', 'RESERVED'].includes(activeSubmission.status)" class="flex items-center gap-2">
                <!-- 1. KEMBALIKAN -->
                <button 
                  type="button" 
                  @click="openActionModal('KEMBALIKAN', activeSubmission)"
                  class="px-3.5 py-2.5 bg-amber-50 hover:bg-amber-100 border border-amber-300 text-amber-900 rounded-2xl text-xs font-bold transition flex items-center gap-1"
                >
                  <RotateCcw class="w-3.5 h-3.5" />
                  <span>Kembalikan</span>
                </button>

                <!-- 2. TOLAK -->
                <button 
                  type="button" 
                  @click="openActionModal('TOLAK', activeSubmission)"
                  class="px-3.5 py-2.5 bg-rose-50 hover:bg-rose-100 border border-rose-300 text-rose-900 rounded-2xl text-xs font-bold transition flex items-center gap-1"
                >
                  <XCircle class="w-3.5 h-3.5" />
                  <span>Tolak</span>
                </button>

                <!-- 3. SELESAI (Hanya Role / Permission Diizinkan) -->
                <button 
                  v-if="canFinalize"
                  type="button" 
                  @click="openActionModal('SELESAI', activeSubmission)"
                  class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-2xl text-xs font-bold transition flex items-center gap-1.5 shadow-md shadow-emerald-600/20"
                >
                  <Check class="w-4 h-4" />
                  <span>Selesai</span>
                </button>
              </div>

              <!-- Message if already finalized or closed -->
              <div v-else class="text-xs font-bold text-slate-400 italic">
                Status berkas: {{ getStatusBadge(activeSubmission.status).label }}
              </div>
            </div>

          </div>
        </div>
      </div>

      <!-- ================================================== -->
      <!-- MODAL AKSI (KEMBALIKAN / TOLAK / SELESAI)          -->
      <!-- ================================================== -->
      <div v-if="isActionModalOpen && activeSubmission" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="closeActionModal"></div>

        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-lg w-full p-6 space-y-4 relative z-10 text-xs font-sans">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
              <span v-if="currentAction === 'KEMBALIKAN'" class="text-amber-600">Kembalikan Transaksi ke PTK</span>
              <span v-else-if="currentAction === 'TOLAK'" class="text-rose-600">Tolak Transaksi</span>
              <span v-else class="text-emerald-600">Selesaikan Transaksi (Realisasi Definitif)</span>
            </h3>
            <button @click="closeActionModal" class="p-1 text-slate-400 hover:text-slate-700">
              <X class="w-4 h-4" />
            </button>
          </div>

          <!-- Transaction Summary -->
          <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200 space-y-1">
            <div class="font-bold text-slate-900 text-sm">{{ activeSubmission.evidence_number || activeSubmission.submission_number }}</div>
            <div class="text-slate-600">{{ activeSubmission.title }}</div>
            <div class="font-black font-sans text-indigo-950 pt-1">{{ formatRupiah(activeSubmission.amount) }}</div>
          </div>

          <!-- KEMBALIKAN Notice -->
          <div v-if="currentAction === 'KEMBALIKAN'" class="p-3 bg-amber-50 rounded-2xl border border-amber-200 text-amber-900 text-[11px] leading-relaxed">
            <strong>Aturan RBC-004:</strong> Status transaksi akan berubah menjadi <strong>DIKEMBALIKAN</strong>, komitmen saldo akan dilepaskan (dikembalikan ke saldo tersedia), dan berkas dapat diedit kembali oleh PTK. Anda <strong>wajib</strong> mengisi catatan alasan pengembalian di bawah.
          </div>

          <!-- TOLAK Notice -->
          <div v-if="currentAction === 'TOLAK'" class="p-3 bg-rose-50 rounded-2xl border border-rose-200 text-rose-900 text-[11px] leading-relaxed">
            <strong>Aturan RBC-004:</strong> Status transaksi akan berubah menjadi <strong>DITOLAK</strong>, komitmen saldo akan dilepaskan secara permanen, dan berkas diarsipkan. Anda <strong>wajib</strong> mengisi catatan alasan penolakan di bawah.
          </div>

          <!-- SELESAI Notice -->
          <div v-if="currentAction === 'SELESAI'" class="p-3 bg-emerald-50 rounded-2xl border border-emerald-200 text-emerald-950 text-[11px] leading-relaxed">
            <strong>Aturan RBC-005:</strong> Status transaksi akan berubah menjadi <strong>SELESAI</strong>. Komitmen akan dipindahkan menjadi <strong>Internal Realization</strong> secara atomik. Saldo tersedia tidak dikurangi dua kali.
          </div>

          <!-- Mandatory Reason Input -->
          <div>
            <label class="block font-bold text-slate-700 mb-1">
              Catatan / Alasan Keputusan 
              <span v-if="['KEMBALIKAN', 'TOLAK'].includes(currentAction)" class="text-rose-600">* (Wajib Diisi)</span>
              <span v-else class="text-slate-400 font-normal">(Opsional)</span>
            </label>
            <textarea 
              v-model="actionForm.comment" 
              rows="3" 
              :required="['KEMBALIKAN', 'TOLAK'].includes(currentAction)"
              :placeholder="['KEMBALIKAN', 'TOLAK'].includes(currentAction) ? 'Tuliskan alasan jelas mengapa berkas dikembalikan/ditolak...' : 'Catatan finalisasi (opsional)...'"
              class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            ></textarea>
            <div v-if="actionForm.errors.comment" class="text-rose-600 text-[11px] mt-1">{{ actionForm.errors.comment }}</div>
          </div>

          <!-- Modal Footer Buttons -->
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
              :disabled="actionForm.processing || (['KEMBALIKAN', 'TOLAK'].includes(currentAction) && !actionForm.comment.trim())"
              :class="[
                'px-5 py-2 rounded-xl font-bold transition text-white shadow-sm flex items-center gap-1.5 disabled:opacity-50',
                currentAction === 'KEMBALIKAN' ? 'bg-amber-600 hover:bg-amber-500' : currentAction === 'TOLAK' ? 'bg-rose-600 hover:bg-rose-500' : 'bg-emerald-600 hover:bg-emerald-500'
              ]"
            >
              <Check class="w-4 h-4" />
              <span>Konfirmasi {{ currentAction === 'KEMBALIKAN' ? 'Kembalikan' : currentAction === 'TOLAK' ? 'Tolak' : 'Selesaikan' }}</span>
            </button>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>
