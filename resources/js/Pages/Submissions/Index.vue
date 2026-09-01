<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { Plus, Eye, Search, Filter, FileText } from 'lucide-vue-next';

const props = defineProps({
  submissions: Object,
  departments: Array,
  studyPrograms: Array,
  canCreate: Boolean,
  filters: Object,
});

const status = ref(props.filters?.status || '');
const search = ref(props.filters?.search || '');
const departmentId = ref(props.filters?.department_id || '');

const applyFilters = () => {
  router.get('/submissions', { 
    status: status.value || undefined, 
    search: search.value || undefined,
    department_id: departmentId.value || undefined,
  }, { preserveState: true, replace: true });
};

const resetFilters = () => {
  status.value = '';
  search.value = '';
  departmentId.value = '';
  applyFilters();
};

const formatRupiah = (val) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0);
};

const getStatusBadge = (st) => {
  switch(st) {
    case 'FINAL':
    case 'COMPLETED': 
      return { label: 'Final / Realisasi', class: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
    case 'PROCESSING':
    case 'SUBMITTED':
    case 'UNDER_REVIEW':
    case 'REVIEW':
    case 'APPROVED':
    case 'RESERVED':
      return { label: 'Dalam Proses', class: 'bg-indigo-50 text-indigo-700 border-indigo-200' };
    case 'RETURNED': 
      return { label: 'Perlu Perbaikan', class: 'bg-amber-50 text-amber-700 border-amber-200' };
    case 'REJECTED':
    case 'CANCELLED':
      return { label: 'Dibatalkan', class: 'bg-rose-50 text-rose-700 border-rose-200' };
    default: 
      return { label: 'Draft', class: 'bg-slate-50 text-slate-600 border-slate-200' };
  }
};
</script>

<template>
  <AppLayout title="Transaksi Anggaran &amp; Realisasi">
    <div class="space-y-6">
      
      <!-- Top Action Bar -->
      <div class="bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="font-black text-slate-900 text-lg tracking-tight">Daftar Transaksi Anggaran</h2>
          <p class="text-xs text-slate-500">Pencatatan realisasi dan komitmen anggaran operasional Fakultas Teknik UNSOED.</p>
        </div>

        <div class="flex items-center gap-3">
          <Link 
            v-if="canCreate"
            href="/submissions/create" 
            class="px-4 py-2.5 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-bold flex items-center gap-2 transition whitespace-nowrap shadow-md shadow-sky-600/20"
          >
            <Plus class="w-4 h-4" />
            + Catat Transaksi Baru
          </Link>
        </div>
      </div>

      <!-- Filters Row -->
      <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-wrap items-center gap-3">
        <!-- Search Query -->
        <div class="relative flex-1 min-w-[200px]">
          <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-2.5" />
          <input 
            v-model="search" 
            @keyup.enter="applyFilters"
            type="text" 
            placeholder="Cari nomor bukti / uraian belanja..." 
            class="w-full pl-9 pr-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none"
          >
        </div>

        <!-- Status Filter -->
        <select 
          v-model="status" 
          @change="applyFilters" 
          class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 focus:ring-2 focus:ring-sky-500 focus:outline-none"
        >
          <option value="">Semua Status</option>
          <option value="DRAFT">Draft</option>
          <option value="PROCESSING">Dalam Proses</option>
          <option value="RETURNED">Perlu Perbaikan</option>
          <option value="FINAL">Final / Realisasi</option>
        </select>

        <!-- Department Filter (if multiple selectable) -->
        <select 
          v-if="departments && departments.length > 1"
          v-model="departmentId" 
          @change="applyFilters" 
          class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 focus:ring-2 focus:ring-sky-500 focus:outline-none"
        >
          <option value="">Semua Jurusan</option>
          <option v-for="d in departments" :key="d.id" :value="d.id">
            {{ d.code }}
          </option>
        </select>

        <button 
          @click="applyFilters" 
          class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition"
        >
          Filter
        </button>

        <button 
          v-if="status || search || departmentId"
          @click="resetFilters" 
          class="px-3 py-2 text-slate-400 hover:text-slate-600 text-xs transition"
        >
          Reset
        </button>
      </div>

      <!-- Data Table -->
      <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead>
              <tr class="bg-slate-50 text-slate-400 text-[11px] font-semibold border-b border-slate-200">
                <th class="py-3.5 px-5">Nomor Bukti</th>
                <th class="py-3.5 px-4">Tanggal</th>
                <th class="py-3.5 px-4">Uraian Transaksi</th>
                <th class="py-3.5 px-4">Unit / Jurusan</th>
                <th class="py-3.5 px-4">Kode Akun</th>
                <th class="py-3.5 px-4 text-right">Nominal</th>
                <th class="py-3.5 px-4 text-center">Status</th>
                <th class="py-3.5 px-5 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-900">
              <tr v-for="sub in submissions.data" :key="sub.id" class="hover:bg-slate-50/70 transition">
                <td class="py-3.5 px-5 font-sans font-bold text-slate-900 whitespace-nowrap">
                  {{ sub.evidence_number || sub.submission_number }}
                </td>
                <td class="py-3.5 px-4 text-slate-500 font-sans whitespace-nowrap">
                  {{ sub.transaction_date || (new Date(sub.created_at).toISOString().split('T')[0]) }}
                </td>
                <td class="py-3.5 px-4 font-medium text-slate-900 max-w-xs truncate">
                  {{ sub.title }}
                </td>
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span class="px-2 py-0.5 bg-slate-100 text-slate-700 font-bold text-[10px] rounded uppercase">
                    {{ sub.department?.code || 'FT' }}
                  </span>
                </td>
                <td class="py-3.5 px-4 font-sans text-slate-600 whitespace-nowrap">
                  {{ sub.budget_bucket?.account_code || '-' }}
                </td>
                <td class="py-3.5 px-4 text-right font-sans font-black text-slate-900 whitespace-nowrap">
                  {{ formatRupiah(sub.amount) }}
                </td>
                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                  <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-bold border inline-block', getStatusBadge(sub.status).class]">
                    {{ getStatusBadge(sub.status).label }}
                  </span>
                </td>
                <td class="py-3.5 px-5 text-center whitespace-nowrap">
                  <Link 
                    :href="`/submissions/${sub.id}`" 
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg text-[11px] transition shadow-sm"
                  >
                    <Eye class="w-3.5 h-3.5" /> Detail
                  </Link>
                </td>
              </tr>
              <tr v-if="submissions.data.length === 0">
                <td colspan="8" class="py-10 text-center text-slate-400">
                  Belum ada data transaksi yang sesuai filter.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="submissions.links && submissions.links.length > 3" class="p-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
          <div>
            Menampilkan {{ submissions.from || 0 }} - {{ submissions.to || 0 }} dari {{ submissions.total || 0 }} data
          </div>
          <div class="flex items-center gap-1">
            <template v-for="(link, i) in submissions.links" :key="i">
              <Link
                v-if="link.url"
                :href="link.url"
                :class="['px-3 py-1.5 rounded-lg font-semibold transition', link.active ? 'bg-sky-600 text-white' : 'hover:bg-slate-100 text-slate-700']"
                v-html="link.label"
              />
              <span v-else class="px-3 py-1.5 text-slate-300" v-html="link.label" />
            </template>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
