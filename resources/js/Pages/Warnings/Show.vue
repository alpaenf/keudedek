<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { 
  AlertTriangle, 
  ArrowLeft, 
  Layers, 
  Building2, 
  Calendar, 
  Clock, 
  ShieldAlert, 
  CheckCircle2, 
  ExternalLink, 
  Wallet, 
  Receipt, 
  Check, 
  RotateCcw,
  Info,
  AlertOctagon,
  Percent,
  TrendingDown,
  Activity
} from 'lucide-vue-next';

const props = defineProps({
  warning: Object,
  ruleName: String,
  budgetContext: Object,
  calculation: Object,
  history: Object,
  relatedBudgetUrl: String,
  relatedTransactionUrl: String,
});

const isProcessing = ref(false);

const acknowledge = () => {
  isProcessing.value = true;
  router.post(`/warnings/${props.warning.id}/acknowledge`, {}, {
    preserveScroll: true,
    onFinish: () => isProcessing.value = false,
  });
};

const resolve = () => {
  isProcessing.value = true;
  router.post(`/warnings/${props.warning.id}/resolve`, {}, {
    preserveScroll: true,
    onFinish: () => isProcessing.value = false,
  });
};

const formatRupiah = (val) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(val || 0);
};

const getSeverityBadge = (sev) => {
  switch (sev) {
    case 'CRITICAL':
      return { label: 'CRITICAL', class: 'bg-rose-100 text-rose-900 border-rose-300 font-black' };
    case 'HIGH':
      return { label: 'HIGH', class: 'bg-orange-100 text-orange-900 border-orange-300 font-extrabold' };
    case 'WARNING':
      return { label: 'WARNING', class: 'bg-amber-100 text-amber-900 border-amber-300 font-bold' };
    default:
      return { label: 'INFO', class: 'bg-sky-100 text-sky-900 border-sky-300 font-semibold' };
  }
};

const getStateBadge = (st) => {
  switch (st) {
    case 'RESOLVED':
      return { label: 'RESOLVED (SELESAI)', class: 'bg-emerald-50 text-emerald-800 border-emerald-300' };
    case 'ACKNOWLEDGED':
      return { label: 'ACKNOWLEDGED (DIPROSES)', class: 'bg-indigo-50 text-indigo-800 border-indigo-300' };
    default:
      return { label: 'OPEN (AKTIF)', class: 'bg-amber-50 text-amber-900 border-amber-300 animate-pulse' };
  }
};
</script>

<template>
  <AppLayout title="Detail Peringatan Dini (Warning Detail)">
    <div class="space-y-6 max-w-6xl mx-auto font-sans">
      
      <!-- Back Navigation & Breadcrumbs -->
      <div class="flex items-center justify-between">
        <Link 
          href="/warnings" 
          class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-slate-900 transition bg-white px-3.5 py-2 rounded-xl border border-slate-200 shadow-xs"
        >
          <ArrowLeft class="w-4 h-4" />
          <span>Kembali ke Early Warning Center</span>
        </Link>

        <div class="flex items-center gap-2">
          <span :class="['px-3 py-1 rounded-full text-xs font-bold border', getStateBadge(warning.lifecycle_state).class]">
            {{ getStateBadge(warning.lifecycle_state).label }}
          </span>
          <span :class="['px-3 py-1 rounded-full text-xs border', getSeverityBadge(warning.severity).class]">
            {{ getSeverityBadge(warning.severity).label }}
          </span>
        </div>
      </div>

      <!-- Main Header Card -->
      <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-sm space-y-4 relative overflow-hidden">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div class="space-y-1">
            <div class="flex items-center gap-2">
              <span class="font-mono text-sm font-black text-rose-600 bg-rose-50 px-2.5 py-0.5 rounded-lg border border-rose-200">
                {{ warning.rule_code }}
              </span>
              <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                {{ ruleName }}
              </h1>
            </div>
            <p class="text-xs text-slate-500 flex items-center gap-2 mt-1">
              <Building2 class="w-3.5 h-3.5 text-slate-400" />
              <span>Unit: <strong>{{ budgetContext.jurusan_code }} &mdash; {{ budgetContext.jurusan_name }}</strong></span>
              <span>&bull;</span>
              <Clock class="w-3.5 h-3.5 text-slate-400" />
              <span>Terdeteksi: {{ new Date(warning.created_at).toLocaleString('id-ID') }}</span>
            </p>
          </div>

          <!-- Fast Action Buttons (Acknowledge & Resolve) -->
          <div class="flex items-center gap-2.5">
            <button 
              v-if="warning.lifecycle_state === 'OPEN'"
              @click="acknowledge"
              :disabled="isProcessing"
              class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-2xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm disabled:opacity-50"
            >
              <Check class="w-4 h-4" />
              <span>Respon (Acknowledge)</span>
            </button>

            <button 
              v-if="warning.lifecycle_state !== 'RESOLVED'"
              @click="resolve"
              :disabled="isProcessing"
              class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-2xl text-xs font-bold transition flex items-center gap-1.5 shadow-md shadow-emerald-600/20 disabled:opacity-50"
            >
              <CheckCircle2 class="w-4 h-4" />
              <span>Selesaikan (Resolve)</span>
            </button>
          </div>
        </div>

        <!-- Warning Reason Banner -->
        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 text-xs font-medium text-slate-800 flex items-start gap-3">
          <AlertTriangle class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
          <div class="space-y-1">
            <span class="font-bold text-slate-900 block text-sm">Pesan Evaluator Sistem (Reason)</span>
            <p class="leading-relaxed">{{ calculation.reason }}</p>
          </div>
        </div>
      </div>

      <!-- Grid: Calculation Engine & Budget Context -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- LEFT 2 COLS: CALCULATION ENGINE & METRICS -->
        <div class="lg:col-span-2 space-y-6">
          
          <!-- Calculation Breakdown Card -->
          <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-5">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
              <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <Activity class="w-4 h-4 text-sky-600" />
                <span>Kalkulasi &amp; Parameter Ambang Batas (Calculation Engine)</span>
              </h2>
              <span class="text-[10px] font-bold text-slate-400 uppercase">Snapshot Finansial</span>
            </div>

            <!-- Metric Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
              <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 space-y-0.5">
                <span class="text-[10px] font-bold text-slate-400 uppercase block">Total Pagu (Pagu)</span>
                <span class="font-black text-slate-900 font-sans text-sm block">
                  {{ formatRupiah(calculation.allocated_budget) }}
                </span>
              </div>

              <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 space-y-0.5">
                <span class="text-[10px] font-bold text-emerald-700 uppercase block">Saldo Bebas (Available)</span>
                <span class="font-black text-emerald-950 font-sans text-sm block">
                  {{ formatRupiah(calculation.available_balance) }}
                </span>
              </div>

              <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 space-y-0.5">
                <span class="text-[10px] font-bold text-sky-700 uppercase block">Rasio Saldo (Available Ratio)</span>
                <span class="font-black text-sky-950 font-sans text-sm block">
                  {{ calculation.available_ratio }}%
                </span>
              </div>

              <div class="p-3.5 bg-rose-50 rounded-2xl border border-rose-200 space-y-0.5">
                <span class="text-[10px] font-bold text-rose-700 uppercase block">Threshold</span>
                <span class="font-black text-rose-950 font-sans text-xs block">
                  {{ calculation.threshold }}
                </span>
              </div>
            </div>

            <!-- Visual Serapan Bar -->
            <div class="space-y-1.5 pt-2">
              <div class="flex justify-between text-xs font-bold">
                <span class="text-slate-600">Utilisasi Anggaran (Realisasi + Komitmen)</span>
                <span class="text-slate-900 font-sans">{{ calculation.utilization_ratio }}% Terpakai</span>
              </div>
              <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden flex">
                <div 
                  class="h-full bg-sky-500 transition-all"
                  :style="{ width: `${Math.min(100, (calculation.realized_budget / (calculation.allocated_budget || 1)) * 100)}%` }"
                  title="Realisasi Belanja"
                ></div>
                <div 
                  class="h-full bg-amber-400 transition-all"
                  :style="{ width: `${Math.min(100, (calculation.reserved_budget / (calculation.allocated_budget || 1)) * 100)}%` }"
                  title="Dalam Proses (Reserved)"
                ></div>
              </div>
              <div class="flex items-center gap-4 text-[10px] text-slate-400 font-semibold pt-1">
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-sky-500"></span> Realisasi: {{ formatRupiah(calculation.realized_budget) }}</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-400"></span> Dalam Proses: {{ formatRupiah(calculation.reserved_budget) }}</span>
              </div>
            </div>

          </div>

          <!-- RELATED NAVIGATION (OPEN BUDGET & OPEN TRANSACTION) -->
          <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-4">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
              <ExternalLink class="w-4 h-4 text-indigo-600" />
              <span>Navigasi Entitas Terkait (Related Entities)</span>
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <!-- 1. Open Budget -->
              <Link 
                :href="relatedBudgetUrl"
                class="p-4 rounded-2xl border border-slate-200 bg-slate-50 hover:bg-sky-50/50 hover:border-sky-300 transition flex items-center justify-between group"
              >
                <div class="space-y-1">
                  <div class="flex items-center gap-1.5 text-xs font-bold text-slate-900 group-hover:text-sky-700">
                    <Wallet class="w-4 h-4 text-sky-600" />
                    <span>Open Budget (Detail Pagu)</span>
                  </div>
                  <p class="text-[11px] text-slate-500">
                    Lihat detail pagu [{{ budgetContext.account_code }}] {{ budgetContext.account_name }}
                  </p>
                </div>
                <ArrowLeft class="w-4 h-4 text-slate-400 group-hover:text-sky-600 rotate-180 transition" />
              </Link>

              <!-- 2. Open Transaction -->
              <Link 
                :href="relatedTransactionUrl"
                class="p-4 rounded-2xl border border-slate-200 bg-slate-50 hover:bg-indigo-50/50 hover:border-indigo-300 transition flex items-center justify-between group"
              >
                <div class="space-y-1">
                  <div class="flex items-center gap-1.5 text-xs font-bold text-slate-900 group-hover:text-indigo-700">
                    <Receipt class="w-4 h-4 text-indigo-600" />
                    <span>Open Transaction (Daftar Transaksi)</span>
                  </div>
                  <p class="text-[11px] text-slate-500">
                    Periksa seluruh transaksi aktif yang membebani pos anggaran ini
                  </p>
                </div>
                <ArrowLeft class="w-4 h-4 text-slate-400 group-hover:text-indigo-600 rotate-180 transition" />
              </Link>
            </div>
          </div>

        </div>

        <!-- RIGHT 1 COL: BUDGET CONTEXT & HISTORY TIMELINE -->
        <div class="space-y-6">
          
          <!-- 7-Segment Budget Context Card -->
          <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-4 text-xs">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
              <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <Layers class="w-4 h-4 text-sky-600" />
                <span>Budget Context (7 Segmen)</span>
              </h2>
              <span class="text-[10px] font-bold text-slate-400 uppercase">RKAKL</span>
            </div>

            <div class="space-y-2 text-[11px]">
              <div class="flex justify-between py-1 border-b border-slate-50">
                <span class="text-slate-400">Tahun Anggaran:</span>
                <strong class="text-slate-900">TA {{ budgetContext.ta }}</strong>
              </div>
              <div class="flex justify-between py-1 border-b border-slate-50">
                <span class="text-slate-400">Sumber Dana:</span>
                <strong class="text-slate-900">{{ budgetContext.sumber_dana }}</strong>
              </div>
              <div class="flex justify-between py-1 border-b border-slate-50">
                <span class="text-slate-400">Versi Revisi:</span>
                <strong class="text-sky-800">{{ budgetContext.revision }}</strong>
              </div>
              <div class="flex justify-between py-1 border-b border-slate-50">
                <span class="text-slate-400">Jurusan:</span>
                <strong class="text-slate-900">{{ budgetContext.jurusan_code }}</strong>
              </div>
            </div>

            <div class="space-y-1.5 text-[11px] pt-1 text-slate-600">
              <div><strong>Program:</strong> {{ budgetContext.program_code }} &mdash; {{ budgetContext.program_name }}</div>
              <div><strong>Kegiatan:</strong> {{ budgetContext.activity_code }} &mdash; {{ budgetContext.activity_name }}</div>
              <div><strong>KRO / RO:</strong> {{ budgetContext.kro_code }} / {{ budgetContext.ro_code }}</div>
              <div><strong>Subkomponen:</strong> {{ budgetContext.subcomponent_code }} &mdash; {{ budgetContext.subcomponent_name }}</div>
              <div class="text-sky-950 font-black pt-1">
                <strong>Akun:</strong> [{{ budgetContext.account_code }}] {{ budgetContext.account_name }}
              </div>
            </div>
          </div>

          <!-- History Lifecycle Timeline Card -->
          <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-4 text-xs">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
              <Clock class="w-4 h-4 text-slate-600" />
              <span>Siklus Hidup Peringatan (History)</span>
            </h2>

            <div class="space-y-4 border-l-2 border-slate-200 pl-4 ml-1 text-[11px]">
              
              <!-- 1. Opened -->
              <div class="space-y-0.5 relative">
                <div class="w-2.5 h-2.5 rounded-full bg-amber-500 absolute -left-[21px] top-1"></div>
                <div class="font-bold text-slate-900 flex items-center justify-between">
                  <span>Opened (Terdeteksi)</span>
                  <span class="text-[10px] text-slate-400 font-normal">{{ history.opened?.human }}</span>
                </div>
                <div class="text-slate-600">{{ history.opened?.notes }}</div>
                <div class="text-[10px] text-slate-400">{{ new Date(history.opened?.timestamp).toLocaleString('id-ID') }}</div>
              </div>

              <!-- 2. Acknowledged -->
              <div class="space-y-0.5 relative">
                <div :class="['w-2.5 h-2.5 rounded-full absolute -left-[21px] top-1', history.acknowledged ? 'bg-indigo-500' : 'bg-slate-300']"></div>
                <div class="font-bold text-slate-900 flex items-center justify-between">
                  <span>Acknowledged (Direspon)</span>
                  <span v-if="history.acknowledged" class="text-[10px] text-slate-400 font-normal">{{ history.acknowledged?.human }}</span>
                </div>
                <div v-if="history.acknowledged" class="text-indigo-800 font-medium">
                  {{ history.acknowledged?.actor }} &bull; {{ history.acknowledged?.notes }}
                </div>
                <div v-else class="text-slate-400 italic">
                  Belum ada konfirmasi respon dari unit verifikator.
                </div>
              </div>

              <!-- 3. Resolved -->
              <div class="space-y-0.5 relative">
                <div :class="['w-2.5 h-2.5 rounded-full absolute -left-[21px] top-1', history.resolved ? 'bg-emerald-500' : 'bg-slate-300']"></div>
                <div class="font-bold text-slate-900 flex items-center justify-between">
                  <span>Resolved (Selesai)</span>
                  <span v-if="history.resolved" class="text-[10px] text-slate-400 font-normal">{{ history.resolved?.human }}</span>
                </div>
                <div v-if="history.resolved" class="text-emerald-800 font-medium">
                  {{ history.resolved?.actor }} &bull; {{ history.resolved?.notes }}
                </div>
                <div v-else class="text-slate-400 italic">
                  Belum diselesaikan.
                </div>
              </div>

            </div>
          </div>

        </div>

      </div>

    </div>
  </AppLayout>
</template>
