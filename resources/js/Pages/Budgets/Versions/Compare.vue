<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import { 
  GitCompare, 
  ArrowLeft, 
  AlertTriangle, 
  CheckCircle2, 
  Calendar, 
  Wallet, 
  TrendingUp, 
  TrendingDown, 
  Minus, 
  Info,
  AlertOctagon,
  Search,
  Filter
} from 'lucide-vue-next';

const props = defineProps({
  fiscalYears: Array,
  fundingSources: Array,
  versions: Array,
  baseVersion: Object,
  targetVersion: Object,
  comparisonItems: Array,
  summary: Object,
});

const baseVersionId = ref(props.baseVersion?.id || '');
const targetVersionId = ref(props.targetVersion?.id || '');

const onVersionChange = () => {
  router.get('/budget-versions/compare', {
    base_version_id: baseVersionId.value,
    target_version_id: targetVersionId.value,
  }, { preserveState: true });
};

// Filter & Search
const searchQuery = ref('');
const statusFilter = ref('ALL'); // 'ALL' | 'CONFLICT' | 'CHANGED'

const filteredItems = computed(() => {
  return props.comparisonItems.filter((item) => {
    // Conflict / Change Filter
    if (statusFilter.value === 'CONFLICT' && !item.is_conflict) return false;
    if (statusFilter.value === 'CHANGED' && item.delta === 0) return false;

    // Search Query
    if (!searchQuery.value) return true;
    const q = searchQuery.value.toLowerCase();
    return (
      item.account_code?.toLowerCase().includes(q) ||
      item.account_name?.toLowerCase().includes(q) ||
      item.department_code?.toLowerCase().includes(q) ||
      item.subcomponent_name?.toLowerCase().includes(q)
    );
  });
});

const formatRupiah = (val) => {
  return new Intl.NumberFormat('id-ID', { 
    style: 'currency', 
    currency: 'IDR', 
    maximumFractionDigits: 0 
  }).format(val || 0);
};

const formatDelta = (val) => {
  if (val > 0) return `+${formatRupiah(val)}`;
  if (val < 0) return formatRupiah(val);
  return 'Rp 0 (Tetap)';
};
</script>

<template>
  <AppLayout title="Komparasi Antar Versi Revisi Pagu">
    <div class="space-y-6 font-sans">
      
      <!-- Top Header & Breadcrumb -->
      <div class="bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <Link href="/budget-versions" class="p-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-2xl transition">
            <ArrowLeft class="w-5 h-5" />
          </Link>
          <div>
            <div class="flex items-center gap-2">
              <span class="px-2.5 py-0.5 bg-sky-100 text-sky-800 text-[10px] font-black rounded-lg uppercase tracking-wider font-sans">
                Budget Comparison Engine
              </span>
              <span class="text-xs text-slate-400 font-semibold">&bull; {{ baseVersion?.revision_no }} vs {{ targetVersion?.revision_no }}</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight mt-1">
              Komparasi &amp; Analisis Dampak Revisi Pagu
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
              Evaluasi selisih pergeseran anggaran antar versi, ketersediaan saldo, serta deteksi dini potensi konflik defisit alokasi.
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <Link 
            href="/budget-versions" 
            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-2xl transition shadow-sm"
          >
            Daftar Versi Anggaran
          </Link>
        </div>
      </div>

      <!-- Selector Bar: Base Version vs Comparison Target Version -->
      <div class="bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Base Version Selector -->
        <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-2">
          <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider">
            Versi Acuan / Asal (Old Pagu)
          </label>
          <select 
            v-model="baseVersionId" 
            @change="onVersionChange" 
            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-900 focus:ring-2 focus:ring-sky-500"
          >
            <option v-for="v in versions" :key="v.id" :value="v.id">
              {{ v.revision_no }} &mdash; {{ v.version_label }} ({{ v.status }})
            </option>
          </select>
          <span class="text-[11px] text-slate-500 block">
            Nomor SK: {{ baseVersion?.source_reference || '-' }} &bull; Status: {{ baseVersion?.status }}
          </span>
        </div>

        <!-- Target Comparison Version Selector -->
        <div class="p-4 bg-sky-50/60 border border-sky-200 rounded-2xl space-y-2">
          <label class="block text-xs font-bold text-sky-800 uppercase tracking-wider">
            Versi Target Pembanding (New Pagu)
          </label>
          <select 
            v-model="targetVersionId" 
            @change="onVersionChange" 
            class="w-full px-3 py-2 bg-white border border-sky-300 rounded-xl text-xs font-bold text-sky-950 focus:ring-2 focus:ring-sky-500"
          >
            <option v-for="v in versions" :key="v.id" :value="v.id">
              {{ v.revision_no }} &mdash; {{ v.version_label }} ({{ v.status }})
            </option>
          </select>
          <span class="text-[11px] text-sky-700 block">
            Nomor SK: {{ targetVersion?.source_reference || '-' }} &bull; Status: {{ targetVersion?.status }}
          </span>
        </div>
      </div>

      <!-- Revision Conflict Alert Banner (If Conflicts Exist) -->
      <div 
        v-if="summary?.conflict_count > 0" 
        class="p-5 bg-rose-50 border-2 border-rose-300 rounded-3xl flex items-start gap-4 text-xs text-rose-950 shadow-sm"
      >
        <div class="p-2.5 bg-rose-100 text-rose-700 rounded-2xl shrink-0 mt-0.5">
          <AlertOctagon class="w-6 h-6" />
        </div>
        <div class="space-y-1">
          <h3 class="text-sm font-black text-rose-900 tracking-tight">
            PERINGATAN SISTEM: Terdeteksi {{ summary.conflict_count }} Pos Anggaran Mengalami REVISION CONFLICT!
          </h3>
          <p class="leading-relaxed text-rose-800">
            Terdapat pos alokasi di mana nilai pagu baru (<strong class="font-bold">New Pagu</strong>) lebih kecil daripada total belanja aktif berjalan (<strong class="font-bold">Dalam Proses + Realisasi SPJ</strong>). Jika versi ini diaktifkan tanpa penyesuaian nominal, sistem akan mengalami defisit anggaran operasional pada unit terkait.
          </p>
        </div>
      </div>

      <!-- Financial Comparison Metrics Cards -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
        <!-- 1. Old Pagu -->
        <div class="bg-white p-4 sm:p-5 rounded-3xl border border-slate-200/80 shadow-sm space-y-1">
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Old Pagu (Acuan)</span>
          <span class="font-sans font-black text-slate-800 text-sm sm:text-base block">{{ formatRupiah(summary?.total_old_pagu) }}</span>
          <span class="text-[10px] text-slate-400">{{ baseVersion?.revision_no }}</span>
        </div>

        <!-- 2. New Pagu -->
        <div class="bg-white p-4 sm:p-5 rounded-3xl border border-slate-200/80 shadow-sm space-y-1">
          <span class="text-[10px] font-bold text-sky-800 uppercase tracking-wider block">Total New Pagu (Target)</span>
          <span class="font-sans font-black text-sky-950 text-sm sm:text-base block">{{ formatRupiah(summary?.total_new_pagu) }}</span>
          <span class="text-[10px] text-sky-700 font-semibold">{{ targetVersion?.revision_no }}</span>
        </div>

        <!-- 3. Net Delta -->
        <div class="bg-white p-4 sm:p-5 rounded-3xl border border-slate-200/80 shadow-sm space-y-1">
          <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Net Pergeseran (Delta)</span>
          <span :class="['font-sans font-black text-sm sm:text-base block', summary?.total_delta >= 0 ? 'text-emerald-700' : 'text-amber-700']">
            {{ formatDelta(summary?.total_delta) }}
          </span>
          <span class="text-[10px] text-slate-400">Selisih Kumulatif</span>
        </div>

        <!-- 4. Dalam Proses -->
        <div class="bg-white p-4 sm:p-5 rounded-3xl border border-slate-200/80 shadow-sm space-y-1">
          <span class="text-[10px] font-bold text-amber-700 uppercase tracking-wider block">Dalam Proses</span>
          <span class="font-sans font-black text-amber-950 text-sm sm:text-base block">{{ formatRupiah(summary?.total_in_process) }}</span>
          <span class="text-[10px] text-amber-700 font-semibold">Komitmen Antrean</span>
        </div>

        <!-- 5. Realisasi -->
        <div class="bg-white p-4 sm:p-5 rounded-3xl border border-slate-200/80 shadow-sm space-y-1">
          <span class="text-[10px] font-bold text-sky-700 uppercase tracking-wider block">Realisasi Final</span>
          <span class="font-sans font-black text-sky-950 text-sm sm:text-base block">{{ formatRupiah(summary?.total_realized) }}</span>
          <span class="text-[10px] text-sky-700 font-semibold">SPJ Selesai</span>
        </div>

        <!-- 6. Conflicts -->
        <div :class="['bg-white p-4 sm:p-5 rounded-3xl border shadow-sm space-y-1', (summary?.conflict_count > 0) ? 'border-rose-300 bg-rose-50/40 ring-2 ring-rose-500/20' : 'border-slate-200/80']">
          <span :class="['text-[10px] font-bold uppercase tracking-wider block', (summary?.conflict_count > 0) ? 'text-rose-700 font-black' : 'text-slate-400']">
            Revision Conflicts
          </span>
          <span :class="['font-sans font-black text-base sm:text-lg block', (summary?.conflict_count > 0) ? 'text-rose-900' : 'text-slate-900']">
            {{ summary?.conflict_count || 0 }} <span class="text-xs font-normal text-slate-400">Pos</span>
          </span>
          <span :class="['text-[10px]', (summary?.conflict_count > 0) ? 'text-rose-700 font-bold' : 'text-slate-400']">
            {{ (summary?.conflict_count > 0) ? 'Harus Disesuaikan' : 'Aman / Tidak Ada Konflik' }}
          </span>
        </div>
      </div>

      <!-- Comparative Table Section -->
      <div class="bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
        
        <!-- Table Search & Filter Controls -->
        <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100">
          <div class="relative w-full sm:w-80">
            <input 
              v-model="searchQuery" 
              type="text" 
              placeholder="Cari Kode Akun / Nama / Jurusan / Subkomponen..." 
              class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:ring-2 focus:ring-sky-500 focus:bg-white transition" 
            />
            <Search class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" />
          </div>

          <div class="flex items-center gap-2">
            <button 
              @click="statusFilter = 'ALL'" 
              :class="['px-3 py-1.5 rounded-xl text-xs font-bold transition', statusFilter === 'ALL' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700']"
            >
              Semua Pos ({{ comparisonItems.length }})
            </button>
            <button 
              @click="statusFilter = 'CONFLICT'" 
              :class="['px-3 py-1.5 rounded-xl text-xs font-bold transition', statusFilter === 'CONFLICT' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-700']"
            >
              Hanya Konflik ({{ summary?.conflict_count || 0 }})
            </button>
            <button 
              @click="statusFilter = 'CHANGED'" 
              :class="['px-3 py-1.5 rounded-xl text-xs font-bold transition', statusFilter === 'CHANGED' ? 'bg-sky-600 text-white' : 'bg-sky-50 text-sky-700']"
            >
              Ada Perubahan (+/-)
            </button>
          </div>
        </div>

        <!-- Comparative Data Table -->
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs font-sans border-collapse">
            <thead>
              <tr class="border-b border-slate-200 text-slate-400 uppercase tracking-wider text-[10px]">
                <th class="py-2.5 px-3">Budget Context (Jurusan &amp; Akun)</th>
                <th class="py-2.5 px-3 text-right">Old Pagu ({{ baseVersion?.revision_no }})</th>
                <th class="py-2.5 px-3 text-right text-sky-900">New Pagu ({{ targetVersion?.revision_no }})</th>
                <th class="py-2.5 px-3 text-right">Delta (+/-)</th>
                <th class="py-2.5 px-3 text-right text-amber-800">Dalam Proses</th>
                <th class="py-2.5 px-3 text-right text-sky-800">Realisasi</th>
                <th class="py-2.5 px-3 text-right">Projected Saldo</th>
                <th class="py-2.5 px-3 text-center">Impact Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr 
                v-for="item in filteredItems" 
                :key="item.id" 
                :class="[
                  'transition',
                  item.is_conflict ? 'bg-rose-50/60 hover:bg-rose-50/90' : 'hover:bg-slate-50/70'
                ]"
              >
                <!-- Budget Context -->
                <td class="py-3 px-3">
                  <div class="flex items-center gap-1.5">
                    <span class="px-1.5 py-0.5 bg-slate-100 text-slate-700 font-bold text-[10px] rounded">
                      {{ item.department_code }}
                    </span>
                    <span class="font-sans font-bold text-sky-800">
                      {{ item.account_code }}
                    </span>
                  </div>
                  <div class="font-medium text-slate-900 mt-0.5 line-clamp-1" :title="item.account_name">
                    {{ item.account_name }}
                  </div>
                  <div class="text-[10px] text-slate-400 mt-0.5 line-clamp-1">
                    {{ item.subcomponent_name }}
                  </div>
                </td>

                <!-- Old Pagu -->
                <td class="py-3 px-3 text-right font-semibold text-slate-700 font-sans whitespace-nowrap">
                  {{ formatRupiah(item.old_pagu) }}
                </td>

                <!-- New Pagu -->
                <td class="py-3 px-3 text-right font-black text-sky-950 font-sans whitespace-nowrap">
                  {{ formatRupiah(item.new_pagu) }}
                </td>

                <!-- Delta -->
                <td class="py-3 px-3 text-right font-bold font-sans whitespace-nowrap">
                  <span :class="item.delta > 0 ? 'text-emerald-700' : (item.delta < 0 ? 'text-amber-700' : 'text-slate-400')">
                    {{ formatDelta(item.delta) }}
                  </span>
                </td>

                <!-- Dalam Proses -->
                <td class="py-3 px-3 text-right font-semibold text-amber-900 font-sans whitespace-nowrap">
                  {{ formatRupiah(item.in_process) }}
                </td>

                <!-- Realisasi -->
                <td class="py-3 px-3 text-right font-semibold text-sky-900 font-sans whitespace-nowrap">
                  {{ formatRupiah(item.realized) }}
                </td>

                <!-- Projected Saldo -->
                <td class="py-3 px-3 text-right font-black font-sans whitespace-nowrap">
                  <span :class="item.projected_saldo < 0 ? 'text-rose-700 underline font-black' : 'text-slate-900'">
                    {{ formatRupiah(item.projected_saldo) }}
                  </span>
                </td>

                <!-- Impact Status Badge -->
                <td class="py-3 px-3 text-center whitespace-nowrap">
                  <div v-if="item.is_conflict" class="space-y-0.5">
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black border bg-rose-100 text-rose-800 border-rose-300 inline-flex items-center gap-1 shadow-sm">
                      <AlertOctagon class="w-3 h-3 text-rose-600" />
                      REVISION CONFLICT
                    </span>
                    <span class="block text-[9px] text-rose-700 font-bold">
                      Defisit: {{ formatRupiah(item.deficit_amount) }}
                    </span>
                  </div>

                  <span 
                    v-else 
                    :class="['px-2.5 py-0.5 rounded-full text-[10px] font-bold border inline-block uppercase', item.impact_class]"
                  >
                    {{ item.impact_status }}
                  </span>
                </td>
              </tr>

              <tr v-if="filteredItems.length === 0">
                <td colspan="8" class="py-8 text-center text-slate-400">
                  Tidak ada data komparasi yang sesuai dengan filter pencarian.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
