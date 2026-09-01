<script setup>
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { 
  Plus, 
  Eye, 
  Search, 
  Filter, 
  FileText, 
  Calendar, 
  Wallet, 
  Building2, 
  Layers, 
  CheckCircle2, 
  AlertTriangle, 
  RotateCcw, 
  Printer, 
  ShieldCheck, 
  Info,
  Clock,
  ArrowRight
} from 'lucide-vue-next';

const props = defineProps({
  submissions: Object,
  fiscalYears: Array,
  fundingSources: Array,
  departments: Array,
  studyPrograms: Array,
  accounts: Array,
  canCreate: Boolean,
  userRole: String,
  userDepartmentId: [String, Number],
  userStudyProgramId: [String, Number],
  filters: Object,
});

// Filter states
const search = ref(props.filters?.search || '');
const fiscalYearId = ref(props.filters?.fiscal_year_id || '');
const fundingSourceId = ref(props.filters?.funding_source_id || '');
const departmentId = ref(props.filters?.department_id || '');
const studyProgramId = ref(props.filters?.study_program_id || '');
const status = ref(props.filters?.status || '');
const accountCode = ref(props.filters?.account_code || '');
const startDate = ref(props.filters?.start_date || '');
const endDate = ref(props.filters?.end_date || '');

const isAdvancedFilterOpen = ref(false);

const applyFilters = () => {
  router.get('/submissions', { 
    search: search.value || undefined,
    fiscal_year_id: fiscalYearId.value || undefined,
    funding_source_id: fundingSourceId.value || undefined,
    department_id: departmentId.value || undefined,
    study_program_id: studyProgramId.value || undefined,
    status: status.value || undefined, 
    account_code: accountCode.value || undefined,
    start_date: startDate.value || undefined,
    end_date: endDate.value || undefined,
  }, { preserveState: true, replace: true });
};

const resetFilters = () => {
  search.value = '';
  fiscalYearId.value = '';
  fundingSourceId.value = '';
  departmentId.value = '';
  studyProgramId.value = '';
  status.value = '';
  accountCode.value = '';
  startDate.value = '';
  endDate.value = '';
  applyFilters();
};

const activeFilterCount = computed(() => {
  let count = 0;
  if (fiscalYearId.value) count++;
  if (fundingSourceId.value) count++;
  if (departmentId.value) count++;
  if (studyProgramId.value) count++;
  if (status.value) count++;
  if (accountCode.value) count++;
  if (startDate.value || endDate.value) count++;
  return count;
});

const formatRupiah = (val) => {
  return new Intl.NumberFormat('id-ID', { 
    style: 'currency', 
    currency: 'IDR', 
    maximumFractionDigits: 0 
  }).format(val || 0);
};

// 5 Core Business Statuses: Draft, Dalam Proses, Dikembalikan, Final, Dibatalkan
const getStatusBadge = (st) => {
  switch (st) {
    case 'FINAL':
    case 'COMPLETED': 
      return { label: 'Final', class: 'bg-emerald-50 text-emerald-800 border-emerald-300 font-bold' };
    case 'PROCESSING':
    case 'SUBMITTED':
    case 'UNDER_REVIEW':
    case 'REVIEW':
    case 'APPROVED':
    case 'RESERVED':
      return { label: 'Dalam Proses', class: 'bg-indigo-50 text-indigo-800 border-indigo-300 font-bold' };
    case 'RETURNED': 
    case 'REVISION_REQUIRED':
      return { label: 'Dikembalikan', class: 'bg-amber-50 text-amber-800 border-amber-300 font-bold' };
    case 'CANCELLED':
    case 'REJECTED':
      return { label: 'Dibatalkan', class: 'bg-rose-50 text-rose-800 border-rose-300 font-bold' };
    default: 
      return { label: 'Draft', class: 'bg-slate-100 text-slate-700 border-slate-300 font-medium' };
  }
};

const getRoleScopeDescription = computed(() => {
  if (props.userRole === 'PTK') return 'Lingkup Jurusan Sendiri (Operational PTK)';
  if (props.userRole === 'KAJUR') return 'Monitoring Read-Only Jurusan Sendiri';
  if (props.userRole === 'KAPRODI') return 'Monitoring Read-Only Transaksi Terkait Prodi Sendiri';
  return 'Lingkup Seluruh Fakultas Teknik (Fakultas Scope)';
});
</script>

<template>
  <AppLayout title="Daftar Transaksi Realisasi Anggaran">
    <div class="space-y-6 font-sans">
      
      <!-- Top Title & Action Bar -->
      <div class="bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <span class="px-2.5 py-0.5 bg-sky-100 text-sky-800 text-[10px] font-black rounded-lg uppercase tracking-wider">
              Buku Transaksi Realisasi
            </span>
            <span class="text-xs text-slate-400 font-semibold">&bull; {{ getRoleScopeDescription }}</span>
          </div>
          <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight mt-1">
            Daftar Transaksi &amp; SPJ Belanja
          </h1>
          <p class="text-xs text-slate-500 mt-0.5">
            Log mutasi belanja anggaran operasional, nomor bukti, status pemeriksaan, dan penyerapan dana.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
          <Link 
            v-if="canCreate"
            href="/submissions/create" 
            class="px-5 py-2.5 bg-sky-600 hover:bg-sky-500 text-white rounded-2xl text-xs font-bold flex items-center gap-2 transition whitespace-nowrap shadow-md shadow-sky-600/20"
          >
            <Plus class="w-4 h-4" />
            <span>+ Catat Transaksi Baru</span>
          </Link>
        </div>
      </div>

      <!-- Multi-dimensional Filter Bar (TA, Sumber Dana, Jurusan, Prodi, Status, Akun, Periode, Search) -->
      <div class="bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
        <!-- Row 1: Primary Search & Quick Filters -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
          <!-- Search Box (Nomor Bukti, Uraian, Kode Akun) -->
          <div class="relative lg:col-span-2">
            <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
            <input 
              v-model="search" 
              @keyup.enter="applyFilters"
              type="text" 
              placeholder="Cari nomor bukti, uraian, atau kode akun..." 
              class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none transition"
            />
          </div>

          <!-- Status Filter (5 Core Workflow Statuses) -->
          <div>
            <select 
              v-model="status" 
              @change="applyFilters" 
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 focus:ring-2 focus:ring-sky-500 focus:outline-none"
            >
              <option value="">Semua Status Transaksi</option>
              <option value="DRAFT">Draft</option>
              <option value="PROCESSING">Dalam Proses</option>
              <option value="RETURNED">Dikembalikan</option>
              <option value="FINAL">Final</option>
              <option value="CANCELLED">Dibatalkan</option>
            </select>
          </div>

          <!-- Filter Trigger & Reset Button -->
          <div class="flex items-center gap-2">
            <button 
              @click="isAdvancedFilterOpen = !isAdvancedFilterOpen" 
              class="flex-1 px-3.5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5"
            >
              <Filter class="w-3.5 h-3.5" />
              <span>Filter Lengkap</span>
              <span v-if="activeFilterCount > 0" class="w-5 h-5 rounded-full bg-sky-600 text-white text-[10px] flex items-center justify-center">
                {{ activeFilterCount }}
              </span>
            </button>

            <button 
              @click="applyFilters" 
              class="px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition shadow-sm"
            >
              Terapkan
            </button>

            <button 
              v-if="activeFilterCount > 0 || search"
              @click="resetFilters" 
              title="Reset Filter"
              class="p-2.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-xl transition border border-rose-200"
            >
              <RotateCcw class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Row 2: Advanced Hierarchical Filters (TA, Sumber Dana, Jurusan, Prodi, Akun, Periode) -->
        <div v-show="isAdvancedFilterOpen" class="pt-4 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3 text-xs">
          <!-- 1. TA -->
          <div>
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Tahun Anggaran</label>
            <select v-model="fiscalYearId" @change="applyFilters" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:ring-2 focus:ring-sky-500">
              <option value="">Semua TA</option>
              <option v-for="fy in fiscalYears" :key="fy.id" :value="fy.id">TA {{ fy.year }}</option>
            </select>
          </div>

          <!-- 2. Sumber Dana -->
          <div>
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Sumber Dana</label>
            <select v-model="fundingSourceId" @change="applyFilters" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:ring-2 focus:ring-sky-500">
              <option value="">Semua Sumber</option>
              <option v-for="fs in fundingSources" :key="fs.id" :value="fs.id">{{ fs.code }}</option>
            </select>
          </div>

          <!-- 3. Jurusan (Faculty Scope / Admin / PTU / Bendahara) -->
          <div>
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Jurusan</label>
            <select 
              v-model="departmentId" 
              @change="applyFilters" 
              :disabled="['PTK', 'KAJUR'].includes(userRole)"
              class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:ring-2 focus:ring-sky-500 disabled:opacity-60"
            >
              <option value="">Semua Jurusan</option>
              <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.code }} &mdash; {{ d.name }}</option>
            </select>
          </div>

          <!-- 4. Prodi -->
          <div>
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Program Studi</label>
            <select 
              v-model="studyProgramId" 
              @change="applyFilters" 
              :disabled="userRole === 'KAPRODI'"
              class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-900 focus:ring-2 focus:ring-sky-500 disabled:opacity-60"
            >
              <option value="">Semua Prodi</option>
              <option v-for="sp in studyPrograms" :key="sp.id" :value="sp.id">{{ sp.name }}</option>
            </select>
          </div>

          <!-- 5. Kode Akun -->
          <div>
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Mata Anggaran (Akun)</label>
            <select v-model="accountCode" @change="applyFilters" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:ring-2 focus:ring-sky-500">
              <option value="">Semua Akun</option>
              <option v-for="acc in accounts" :key="acc.account_code" :value="acc.account_code">
                {{ acc.account_code }} &mdash; {{ acc.account_name }}
              </option>
            </select>
          </div>

          <!-- 6. Periode (Tanggal Awal & Akhir) -->
          <div>
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Periode Tanggal</label>
            <div class="flex items-center gap-1">
              <input type="date" v-model="startDate" @change="applyFilters" class="w-full px-2 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-[10px] text-slate-900" />
              <span class="text-slate-400">&ndash;</span>
              <input type="date" v-model="endDate" @change="applyFilters" class="w-full px-2 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-[10px] text-slate-900" />
            </div>
          </div>
        </div>
      </div>

      <!-- 11 Columns Comprehensive Transaction Table -->
      <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden space-y-4">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <FileText class="w-4 h-4 text-sky-600" />
            <h3 class="text-sm font-bold text-slate-900">Tabel Mutasi Transaksi Belanja</h3>
          </div>
          <span class="text-xs text-slate-400 font-semibold">
            Total {{ submissions.total || 0 }} Transaksi Terdaftar
          </span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs font-sans border-collapse">
            <thead>
              <tr class="border-b border-slate-200 text-slate-400 uppercase tracking-wider text-[10px] bg-slate-50/50">
                <!-- 1. Nomor Bukti -->
                <th class="py-3 px-3.5 font-semibold">Nomor Bukti</th>
                <!-- 2. Tanggal -->
                <th class="py-3 px-3 font-semibold">Tanggal</th>
                <!-- 3. Jurusan -->
                <th class="py-3 px-3 font-semibold">Jurusan</th>
                <!-- 4. Prodi -->
                <th class="py-3 px-3 font-semibold">Prodi</th>
                <!-- 5. Kode Akun -->
                <th class="py-3 px-3 font-semibold">Kode Akun</th>
                <!-- 6. Uraian -->
                <th class="py-3 px-3.5 font-semibold">Uraian Belanja</th>
                <!-- 7. Nominal -->
                <th class="py-3 px-3 text-right font-semibold">Nominal (Rp)</th>
                <!-- 8. Status -->
                <th class="py-3 px-3 text-center font-semibold">Status</th>
                <!-- 9. PTK -->
                <th class="py-3 px-3 font-semibold">PTK / Pembuat</th>
                <!-- 10. Last Update -->
                <th class="py-3 px-3 font-semibold">Last Update</th>
                <!-- 11. Action -->
                <th class="py-3 px-3.5 text-center font-semibold">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr 
                v-for="sub in submissions.data" 
                :key="sub.id" 
                class="hover:bg-slate-50/70 transition"
              >
                <!-- 1. Nomor Bukti -->
                <td class="py-3.5 px-3.5 whitespace-nowrap">
                  <span class="font-sans font-black text-slate-900 block text-xs">
                    {{ sub.evidence_number || sub.submission_number }}
                  </span>
                  <span v-if="sub.evidence_number && sub.submission_number" class="text-[10px] text-slate-400 block font-mono">
                    {{ sub.submission_number }}
                  </span>
                </td>

                <!-- 2. Tanggal -->
                <td class="py-3.5 px-3 whitespace-nowrap text-slate-600 font-medium">
                  {{ new Date(sub.transaction_date || sub.created_at).toLocaleDateString('id-ID') }}
                </td>

                <!-- 3. Jurusan -->
                <td class="py-3.5 px-3 whitespace-nowrap font-bold text-slate-800">
                  {{ sub.department?.code || 'FT' }}
                </td>

                <!-- 4. Prodi -->
                <td class="py-3.5 px-3 whitespace-nowrap">
                  <span v-if="sub.study_program" class="px-2 py-0.5 bg-slate-100 text-slate-700 rounded text-[10px] font-semibold">
                    {{ sub.study_program?.name }}
                  </span>
                  <span v-else class="text-slate-400 text-[10px] italic">
                    Level Jurusan
                  </span>
                </td>

                <!-- 5. Kode Akun -->
                <td class="py-3.5 px-3 whitespace-nowrap">
                  <span class="font-sans font-bold text-sky-800 bg-sky-50 px-2 py-0.5 rounded border border-sky-200 block text-center">
                    {{ sub.budget_bucket?.account_code || '-' }}
                  </span>
                  <span class="text-[10px] text-slate-500 block truncate max-w-[120px] mt-0.5" :title="sub.budget_bucket?.account_name">
                    {{ sub.budget_bucket?.account_name }}
                  </span>
                </td>

                <!-- 6. Uraian Belanja -->
                <td class="py-3.5 px-3.5 max-w-xs">
                  <div class="font-bold text-slate-900 line-clamp-1" :title="sub.title">
                    {{ sub.title }}
                  </div>
                  <div v-if="sub.notes" class="text-[10px] text-slate-400 line-clamp-1 mt-0.5">
                    {{ sub.notes }}
                  </div>
                </td>

                <!-- 7. Nominal (Rp) -->
                <td class="py-3.5 px-3 text-right font-black text-slate-900 font-sans whitespace-nowrap">
                  {{ formatRupiah(sub.amount) }}
                </td>

                <!-- 8. Status -->
                <td class="py-3.5 px-3 text-center whitespace-nowrap">
                  <span :class="['px-2.5 py-0.5 rounded-full text-[10px] border inline-block uppercase', getStatusBadge(sub.status).class]">
                    {{ getStatusBadge(sub.status).label }}
                  </span>
                </td>

                <!-- 9. PTK / Pembuat -->
                <td class="py-3.5 px-3 whitespace-nowrap text-slate-700 font-medium">
                  {{ sub.creator?.name || 'PTK Operator' }}
                </td>

                <!-- 10. Last Update -->
                <td class="py-3.5 px-3 whitespace-nowrap text-slate-400 text-[10px]">
                  {{ new Date(sub.updated_at || sub.created_at).toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' }) }}
                </td>

                <!-- 11. Action Buttons -->
                <td class="py-3.5 px-3.5 text-center whitespace-nowrap">
                  <div class="flex items-center justify-center gap-1.5">
                    <!-- Detail View -->
                    <Link 
                      :href="`/submissions/${sub.id}`" 
                      class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[11px] font-bold transition inline-flex items-center gap-1 shadow-sm"
                      title="Lihat Rincian Transaksi"
                    >
                      <Eye class="w-3 h-3" />
                      <span>Detail</span>
                    </Link>

                    <!-- Print Document -->
                    <a 
                      :href="`/submissions/${sub.id}/print`" 
                      target="_blank"
                      class="p-1 text-slate-400 hover:text-slate-700 transition"
                      title="Cetak Lembar SPJ / Kuitansi"
                    >
                      <Printer class="w-3.5 h-3.5" />
                    </a>
                  </div>
                </td>
              </tr>

              <tr v-if="!submissions.data || submissions.data.length === 0">
                <td colspan="11" class="py-10 text-center text-slate-400">
                  Tidak ada data transaksi yang sesuai dengan filter pencarian.
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
    </div>
  </AppLayout>
</template>
