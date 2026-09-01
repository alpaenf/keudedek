<script setup>
import { ref, watch } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { 
  Search, 
  Eye, 
  Plus, 
  RotateCcw, 
  Filter, 
  ChevronRight, 
  Layers, 
  Building2, 
  Wallet,
  AlertTriangle,
  FileSpreadsheet
} from 'lucide-vue-next';

const props = defineProps({
  buckets: Object,
  departments: Array,
  fiscalYears: Array,
  fundingSources: Array,
  budgetVersions: Array,
  activeFiscalYear: Object,
  activeVersion: Object,
  programs: Array,
  activities: Array,
  kros: Array,
  ros: Array,
  components: Array,
  subcomponents: Array,
  accounts: Array,
  filters: Object,
});

const page = usePage();
const currentUser = page.props.auth?.user;
const canCreate = ['ADMIN', 'KABAG'].includes(currentUser?.role);
const canImport = ['ADMIN', 'KABAG', 'WAKIL_DEKAN', 'WD', 'DEKAN'].includes(currentUser?.role);

// Filter states
const search = ref(props.filters?.search || '');
const departmentId = ref(props.filters?.department_id || '');
const fiscalYearId = ref(props.filters?.fiscal_year_id || props.activeFiscalYear?.id || '');
const fundingSourceId = ref(props.filters?.funding_source_id || '');
const budgetVersionId = ref(props.filters?.budget_version_id || props.activeVersion?.id || '');
const programCode = ref(props.filters?.program_code || '');
const activityCode = ref(props.filters?.activity_code || '');
const kroCode = ref(props.filters?.kro_code || '');
const roCode = ref(props.filters?.ro_code || '');
const componentCode = ref(props.filters?.component_code || '');
const subcomponentCode = ref(props.filters?.subcomponent_code || '');
const accountCode = ref(props.filters?.account_code || '');

const isFilterExpanded = ref(false);

const handleFilter = () => {
  router.get('/budgets', {
    search: search.value || undefined,
    department_id: departmentId.value || undefined,
    fiscal_year_id: fiscalYearId.value || undefined,
    funding_source_id: fundingSourceId.value || undefined,
    budget_version_id: budgetVersionId.value || undefined,
    program_code: programCode.value || undefined,
    activity_code: activityCode.value || undefined,
    kro_code: kroCode.value || undefined,
    ro_code: roCode.value || undefined,
    component_code: componentCode.value || undefined,
    subcomponent_code: subcomponentCode.value || undefined,
    account_code: accountCode.value || undefined,
  }, { 
    preserveState: true,
    preserveScroll: true,
    replace: true 
  });
};

const resetFilter = () => {
  search.value = '';
  departmentId.value = '';
  fundingSourceId.value = '';
  programCode.value = '';
  activityCode.value = '';
  kroCode.value = '';
  roCode.value = '';
  componentCode.value = '';
  subcomponentCode.value = '';
  accountCode.value = '';
  fiscalYearId.value = props.activeFiscalYear?.id || '';
  budgetVersionId.value = props.activeVersion?.id || '';
  handleFilter();
};

const goToDetail = (bucketId) => {
  router.visit(`/budgets/${bucketId}`);
};

const formatRupiah = (val) => {
  return new Intl.NumberFormat('id-ID', { 
    style: 'currency', 
    currency: 'IDR', 
    maximumFractionDigits: 0 
  }).format(val || 0);
};

const formatRupiahCompact = (val) => {
  const num = Number(val) || 0;
  if (Math.abs(num) >= 1_000_000_000_000) return 'Rp ' + (num / 1_000_000_000_000).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) + ' T';
  if (Math.abs(num) >= 1_000_000_000) return 'Rp ' + (num / 1_000_000_000).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) + ' M';
  if (Math.abs(num) >= 1_000_000) return 'Rp ' + (num / 1_000_000).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 1 }) + ' Jt';
  return formatRupiah(num);
};
</script>

<template>
  <AppLayout title="Master Pagu Anggaran (DIPA FT UNSOED)">
    <div class="space-y-6">
      
      <!-- Top Title & Quick Actions Bar -->
      <div class="bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <span class="px-2.5 py-0.5 bg-sky-100 text-sky-800 text-[10px] font-black rounded-lg uppercase tracking-wider">
              DIPA FT UNSOED
            </span>
            <span class="text-xs text-slate-400 font-semibold">&bull; TA {{ activeFiscalYear?.year || 2026 }} {{ activeVersion?.revision_no || 'Rev 02' }}</span>
          </div>
          <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight mt-1">
            Master Pagu Anggaran
          </h1>
          <p class="text-xs text-slate-500 mt-0.5">
            Daftar seluruh alokasi pos anggaran belanja resmi berdasar hierarki 7 segmen RKAKL Fakultas Teknik.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
          <Link 
            v-if="canImport" 
            href="/budgets-import" 
            class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-2xl transition flex items-center gap-1.5 shadow-sm"
          >
            <FileSpreadsheet class="w-4 h-4 text-slate-500" />
            <span>Import Pagu</span>
          </Link>

          <Link 
            v-if="canCreate" 
            href="/budgets/create" 
            class="px-4 py-2.5 bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold rounded-2xl transition flex items-center gap-1.5 shadow-md shadow-sky-600/20"
          >
            <Plus class="w-4 h-4" />
            <span>+ Pos Pagu Baru</span>
          </Link>
        </div>
      </div>

      <!-- Comprehensive Global Filter & Hierarchical Filters -->
      <div class="bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
        <!-- Row 1: Primary Search & Key Dimension Filters -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
          <!-- 1. Search Box -->
          <div class="lg:col-span-2 relative">
            <input 
              v-model="search" 
              @keyup.enter="handleFilter" 
              type="text" 
              placeholder="Cari Kode Akun, Nama, Uraian, Kegiatan..." 
              class="w-full pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:bg-white transition" 
            />
            <Search class="w-4 h-4 text-slate-400 absolute left-3 top-3" />
          </div>

          <!-- 2. Jurusan Filter -->
          <div>
            <select 
              v-model="departmentId" 
              @change="handleFilter" 
              class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500"
            >
              <option value="">Semua Jurusan</option>
              <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                {{ dept.code }} - {{ dept.name }}
              </option>
            </select>
          </div>

          <!-- 3. Sumber Dana -->
          <div>
            <select 
              v-model="fundingSourceId" 
              @change="handleFilter" 
              class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500"
            >
              <option value="">Semua Sumber Dana</option>
              <option v-for="fs in fundingSources" :key="fs.id" :value="fs.id">
                {{ fs.code }} - {{ fs.name }}
              </option>
            </select>
          </div>

          <!-- 4. Revision Filter -->
          <div>
            <select 
              v-model="budgetVersionId" 
              @change="handleFilter" 
              class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500"
            >
              <option value="">Semua Revisi</option>
              <option v-for="bv in budgetVersions" :key="bv.id" :value="bv.id">
                {{ bv.revision_no }} ({{ bv.status }})
              </option>
            </select>
          </div>
        </div>

        <!-- Toggle Button for Advanced Hierarchy Filters -->
        <div class="flex items-center justify-between pt-1">
          <button 
            type="button" 
            @click="isFilterExpanded = !isFilterExpanded" 
            class="text-xs font-bold text-sky-600 hover:text-sky-700 transition flex items-center gap-1.5"
          >
            <Filter class="w-3.5 h-3.5" />
            <span>{{ isFilterExpanded ? 'Sembunyikan Filter Hierarki RKAKL' : 'Filter Hierarki RKAKL (Program, KRO, RO, Komponen, Akun)' }}</span>
          </button>

          <div class="flex items-center gap-2">
            <button 
              @click="resetFilter" 
              class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition flex items-center gap-1"
            >
              <RotateCcw class="w-3.5 h-3.5" />
              <span>Reset Filter</span>
            </button>
            <button 
              @click="handleFilter" 
              class="px-4 py-1.5 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-bold transition shadow-sm"
            >
              Terapkan
            </button>
          </div>
        </div>

        <!-- Row 2: Hierarchical RKAKL Dropdowns (Collapsible) -->
        <div v-show="isFilterExpanded" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 pt-3 border-t border-slate-100">
          <!-- Program -->
          <div>
            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Program</label>
            <select v-model="programCode" @change="handleFilter" class="w-full px-2.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800">
              <option value="">Semua Program</option>
              <option value="WA">WA — Dukungan Manajemen</option>
              <option value="DK">DK — Pendidikan Tinggi</option>
            </select>
          </div>

          <!-- Kegiatan -->
          <div>
            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Kegiatan</label>
            <select v-model="activityCode" @change="handleFilter" class="w-full px-2.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800">
              <option value="">Semua Kegiatan</option>
              <option value="4257">4257 — Dukungan Manajemen</option>
              <option value="7730">7730 — Kapasitas PT</option>
              <option value="7729">7729 — BOPTN</option>
            </select>
          </div>

          <!-- KRO -->
          <div>
            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">KRO</label>
            <select v-model="kroCode" @change="handleFilter" class="w-full px-2.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800">
              <option value="">Semua KRO</option>
              <option value="DBA">7730.DBA — Pendidikan Tinggi</option>
              <option value="EBA">7734.EBA — Layanan Manajemen</option>
              <option value="BEI">7729.BEI — Bantuan Lembaga</option>
            </select>
          </div>

          <!-- RO -->
          <div>
            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">RO</label>
            <select v-model="roCode" @change="handleFilter" class="w-full px-2.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800">
              <option value="">Semua RO</option>
              <option value="994">994 — Layanan Perkantoran</option>
              <option value="001">001 — Operasional</option>
              <option value="002">002 — Pembelajaran</option>
            </select>
          </div>

          <!-- Komponen -->
          <div>
            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Komponen</label>
            <select v-model="componentCode" @change="handleFilter" class="w-full px-2.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800">
              <option value="">Semua Komponen</option>
              <option value="001">001 — Operasional &amp; Pemeliharaan</option>
              <option value="051">051 — Operasional Pembelajaran</option>
            </select>
          </div>

          <!-- Akun 6 Digit -->
          <div>
            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Mata Anggaran (Akun)</label>
            <select v-model="accountCode" @change="handleFilter" class="w-full px-2.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800">
              <option value="">Semua Akun</option>
              <option value="521111">521111 — Belanja Keperluan Kantor</option>
              <option value="521211">521211 — Belanja Bahan</option>
              <option value="521811">521811 — Belanja Barang Konsumsi</option>
              <option value="522141">522141 — Belanja Sewa Gedung</option>
              <option value="522151">522151 — Belanja Jasa Profesi</option>
              <option value="524111">524111 — Belanja Perjalanan Dinas</option>
              <option value="532111">532111 — Belanja Modal Peralatan</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Master Pagu Table (15 Comprehensive Columns) -->
      <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse font-sans">
            <thead>
              <tr class="bg-slate-50/80 text-slate-500 font-semibold border-b border-slate-200/80 text-[10px] uppercase tracking-wider">
                <th class="py-3 px-4 whitespace-nowrap">Jurusan</th>
                <th class="py-3 px-4 whitespace-nowrap">Kode Program</th>
                <th class="py-3 px-4 whitespace-nowrap">Kode Kegiatan</th>
                <th class="py-3 px-4 whitespace-nowrap">KRO</th>
                <th class="py-3 px-4 whitespace-nowrap">RO</th>
                <th class="py-3 px-4 whitespace-nowrap">Komponen</th>
                <th class="py-3 px-4 whitespace-nowrap">Subkomponen</th>
                <th class="py-3 px-4 whitespace-nowrap">Kode Akun</th>
                <th class="py-3 px-4 whitespace-nowrap">Nama Akun</th>
                <th class="py-3 px-4 text-right whitespace-nowrap">Pagu Aktif</th>
                <th class="py-3 px-4 text-right whitespace-nowrap">Dalam Proses</th>
                <th class="py-3 px-4 text-right whitespace-nowrap">Realisasi</th>
                <th class="py-3 px-4 text-right whitespace-nowrap">Saldo</th>
                <th class="py-3 px-4 text-center whitespace-nowrap">Serapan</th>
                <th class="py-3 px-4 text-center whitespace-nowrap">Warning</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-900">
              <tr 
                v-for="bucket in buckets.data" 
                :key="bucket.id" 
                @click="goToDetail(bucket.id)"
                class="hover:bg-sky-50/60 cursor-pointer transition group"
                title="Klik baris untuk melihat detail pagu anggaran"
              >
                <!-- 1. Jurusan -->
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span class="font-bold text-slate-900 block group-hover:text-sky-700 transition">
                    {{ bucket.department?.code ?? 'FT' }}
                  </span>
                  <span class="text-[10px] text-slate-400 truncate max-w-[100px] block">
                    {{ bucket.funding_source?.code ?? 'RM' }}
                  </span>
                </td>

                <!-- 2. Kode Program -->
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span class="font-bold text-slate-900">{{ bucket.hierarchy?.program_code }}</span>
                  <span class="text-[10px] text-slate-500 block truncate max-w-[130px]" :title="bucket.hierarchy?.program_label">
                    {{ bucket.hierarchy?.program_label?.split(' — ')[1] || '-' }}
                  </span>
                </td>

                <!-- 3. Kode Kegiatan -->
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span class="font-bold text-slate-900">{{ bucket.hierarchy?.activity_code }}</span>
                  <span class="text-[10px] text-slate-500 block truncate max-w-[130px]" :title="bucket.hierarchy?.activity_label">
                    {{ bucket.hierarchy?.activity_label?.split(' — ')[1] || '-' }}
                  </span>
                </td>

                <!-- 4. KRO -->
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span class="font-bold text-slate-900">{{ bucket.hierarchy?.kro_code }}</span>
                  <span class="text-[10px] text-slate-500 block truncate max-w-[130px]" :title="bucket.hierarchy?.kro_label">
                    {{ bucket.hierarchy?.kro_label?.split(' — ')[1] || '-' }}
                  </span>
                </td>

                <!-- 5. RO -->
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span class="font-bold text-slate-900">{{ bucket.hierarchy?.ro_code }}</span>
                </td>

                <!-- 6. Komponen -->
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span class="font-bold text-slate-900">{{ bucket.hierarchy?.component_code }}</span>
                </td>

                <!-- 7. Subkomponen -->
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span class="font-bold text-slate-900">{{ bucket.hierarchy?.subcomponent_code }}</span>
                  <span class="text-[10px] text-slate-500 block truncate max-w-[140px]" :title="bucket.subcomponent_name">
                    {{ bucket.subcomponent_name || '-' }}
                  </span>
                </td>

                <!-- 8. Kode Akun -->
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span class="font-sans font-bold text-sky-800 bg-sky-50 px-2 py-0.5 rounded-lg border border-sky-200">
                    {{ bucket.account_code }}
                  </span>
                </td>

                <!-- 9. Nama Akun -->
                <td class="py-3.5 px-4 max-w-xs truncate" :title="bucket.account_name">
                  <span class="font-medium text-slate-800">{{ bucket.account_name }}</span>
                  <span class="text-[10px] text-slate-400 block truncate max-w-[150px]" :title="bucket.budget_bucket_name">
                    {{ bucket.budget_bucket_name }}
                  </span>
                </td>

                <!-- 10. Pagu Aktif -->
                <td class="py-3.5 px-4 text-right font-sans font-bold text-slate-900 whitespace-nowrap" :title="formatRupiah(bucket.allocated_budget)">
                  {{ formatRupiahCompact(bucket.allocated_budget) }}
                </td>

                <!-- 11. Dalam Proses -->
                <td class="py-3.5 px-4 text-right font-sans font-medium text-indigo-700 whitespace-nowrap" :title="formatRupiah(bucket.reserved_budget)">
                  {{ formatRupiahCompact(bucket.reserved_budget) }}
                </td>

                <!-- 12. Realisasi -->
                <td class="py-3.5 px-4 text-right font-sans font-bold text-emerald-800 whitespace-nowrap" :title="formatRupiah(bucket.realized_budget)">
                  {{ formatRupiahCompact(bucket.realized_budget) }}
                </td>

                <!-- 13. Saldo -->
                <td class="py-3.5 px-4 text-right font-sans font-black text-slate-900 whitespace-nowrap" :title="formatRupiah(bucket.available_balance)">
                  {{ formatRupiahCompact(bucket.available_balance) }}
                </td>

                <!-- 14. Serapan -->
                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                  <span class="font-sans font-bold text-slate-800 text-[11px]">
                    {{ bucket.serapan_rate }}%
                  </span>
                  <div class="w-12 bg-slate-100 h-1.5 rounded-full mx-auto mt-1 overflow-hidden">
                    <div 
                      class="h-full bg-emerald-500 rounded-full" 
                      :style="{ width: `${Math.min(bucket.serapan_rate, 100)}%` }"
                    ></div>
                  </div>
                </td>

                <!-- 15. Warning -->
                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                  <span :class="['px-2 py-0.5 rounded-full text-[10px] font-bold border inline-block uppercase', bucket.warning_badge]">
                    {{ bucket.warning_label }}
                  </span>
                </td>
              </tr>

              <tr v-if="!buckets.data || buckets.data.length === 0">
                <td colspan="15" class="py-12 text-center text-slate-400">
                  Tidak ada pos pagu anggaran yang sesuai dengan kriteria filter. Silakan reset filter untuk melihat semua pagu.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="buckets.links && buckets.links.length > 3" class="p-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 bg-slate-50/50">
          <span class="text-xs text-slate-500 font-medium">
            Menampilkan {{ buckets.from ?? 0 }} - {{ buckets.to ?? 0 }} dari {{ buckets.total }} total pos pagu anggaran
          </span>
          <div class="flex items-center gap-1">
            <Link
              v-for="(link, i) in buckets.links"
              :key="i"
              :href="link.url || '#'"
              v-html="link.label"
              :class="[
                'px-3 py-1.5 rounded-xl text-xs font-bold transition',
                link.active ? 'bg-sky-600 text-white shadow-sm' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200',
                !link.url ? 'opacity-40 cursor-not-allowed' : ''
              ]"
            />
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
