<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { 
  AlertTriangle, 
  Search, 
  Building2, 
  CheckCircle2, 
  RefreshCw, 
  ShieldAlert, 
  Clock, 
  Eye,
  Check
} from 'lucide-vue-next';

const props = defineProps({
  warnings: Object,
  departments: Array,
  stats: Object,
  filters: Object,
});

const search = ref(props.filters?.search || '');
const severity = ref(props.filters?.severity || '');
const lifecycleState = ref(props.filters?.lifecycle_state || '');
const selectedDept = ref(props.filters?.department_id || '');
const isScanning = ref(false);

const handleFilter = () => {
  router.get('/warnings', {
    search: search.value || undefined,
    severity: severity.value || undefined,
    lifecycle_state: lifecycleState.value || undefined,
    department_id: selectedDept.value || undefined,
  }, { preserveState: true, preserveScroll: true });
};

const acknowledge = (id) => {
  router.post(`/warnings/${id}/acknowledge`, {}, { preserveScroll: true });
};

const resolve = (id) => {
  router.post(`/warnings/${id}/resolve`, {}, { preserveScroll: true });
};

const triggerReevaluate = () => {
  isScanning.value = true;
  router.post('/warnings/reevaluate', {}, {
    onFinish: () => isScanning.value = false,
  });
};

const getSeverityBadge = (sev) => {
  switch (sev) {
    case 'CRITICAL': return 'bg-rose-100 text-rose-800 border-rose-200';
    case 'HIGH': return 'bg-orange-100 text-orange-800 border-orange-200';
    case 'WARNING': return 'bg-amber-100 text-amber-800 border-amber-200';
    default: return 'bg-sky-100 text-sky-800 border-sky-200';
  }
};
</script>

<template>
  <AppLayout title="Early Warning System &amp; Monitoring Risiko">
    <div class="space-y-6 max-w-6xl mx-auto">
      <!-- Header -->
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
            <AlertTriangle class="w-6 h-6 text-amber-600" />
            Early Warning Center (EWS) &amp; Manajemen Risiko
          </h2>
          <p class="text-xs text-slate-500">Pusat pemantauan anomali belanja, saldo kritis, dan indikator kepatuhan pagu.</p>
        </div>

        <button 
          @click="triggerReevaluate"
          :disabled="isScanning"
          class="px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-sm disabled:opacity-50"
        >
          <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': isScanning }" />
          Pindai / Evaluasi EWS Sekarang
        </button>
      </div>

      <!-- 4 Metric KPI Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-1">
          <span class="text-xs font-bold text-slate-500 uppercase">TOTAL PERINGATAN TERBUKA</span>
          <div class="text-2xl sm:text-3xl font-black text-slate-900 font-sans">{{ stats?.total_open || 0 }}</div>
          <span class="text-[10px] text-slate-400">Open &amp; Acknowledged</span>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-rose-200 bg-rose-50/20 shadow-sm space-y-1">
          <span class="text-xs font-bold text-rose-700 uppercase">KRITIS (CRITICAL &lt; 5%)</span>
          <div class="text-2xl sm:text-3xl font-black text-rose-900 font-sans">{{ stats?.critical_count || 0 }}</div>
          <span class="text-[10px] text-rose-700 font-semibold">Tindakan darurat diperlukan</span>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-orange-200 bg-orange-50/20 shadow-sm space-y-1">
          <span class="text-xs font-bold text-orange-700 uppercase">RISIKO TINGGI (HIGH &lt; 15%)</span>
          <div class="text-2xl sm:text-3xl font-black text-orange-900 font-sans">{{ stats?.high_count || 0 }}</div>
          <span class="text-[10px] text-orange-700 font-semibold">Pengendalian belanja ketat</span>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-amber-200 bg-amber-50/20 shadow-sm space-y-1">
          <span class="text-xs font-bold text-amber-700 uppercase">PERLU PERHATIAN (WARNING)</span>
          <div class="text-2xl sm:text-3xl font-black text-amber-900 font-sans">{{ stats?.warning_count || 0 }}</div>
          <span class="text-[10px] text-amber-700 font-semibold">Utilisasi &gt; 85% / SLA</span>
        </div>
      </div>

      <!-- Search & Filter Bar -->
      <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-wrap items-center justify-between gap-3 text-xs">
        <div class="flex-1 min-w-[200px] relative">
          <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
          <input 
            v-model="search" 
            @input="handleFilter"
            type="text" 
            placeholder="Cari kode aturan atau isi pesan..." 
            class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none"
          >
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <select v-model="severity" @change="handleFilter" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-semibold focus:bg-white focus:outline-none">
            <option value="">Semua Tingkat Keparahan</option>
            <option value="CRITICAL">CRITICAL</option>
            <option value="HIGH">HIGH</option>
            <option value="WARNING">WARNING</option>
            <option value="INFO">INFO</option>
          </select>

          <select v-model="lifecycleState" @change="handleFilter" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-semibold focus:bg-white focus:outline-none">
            <option value="">Semua Siklus</option>
            <option value="OPEN">OPEN (Belum Direspon)</option>
            <option value="ACKNOWLEDGED">ACKNOWLEDGED (Direspon)</option>
            <option value="RESOLVED">RESOLVED (Selesai)</option>
          </select>

          <select v-model="selectedDept" @change="handleFilter" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-semibold focus:bg-white focus:outline-none">
            <option value="">Semua Jurusan</option>
            <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
          </select>
        </div>
      </div>

      <!-- Warning List Cards -->
      <div class="space-y-3">
        <div 
          v-for="warn in warnings.data" 
          :key="warn.id"
          class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4 transition hover:border-sky-300"
        >
          <div class="flex items-start gap-3.5">
            <div :class="['w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 font-bold', warn.severity === 'CRITICAL' ? 'bg-rose-100 text-rose-700' : (warn.severity === 'HIGH' ? 'bg-orange-100 text-orange-700' : 'bg-amber-100 text-amber-700')]">
              <AlertTriangle class="w-5 h-5" />
            </div>

            <div class="space-y-1">
              <div class="flex flex-wrap items-center gap-2">
                <span class="font-sans font-bold text-xs text-sky-800">{{ warn.rule_code }}</span>
                <span :class="['px-2 py-0.5 rounded-md text-[10px] font-bold border uppercase', getSeverityBadge(warn.severity)]">
                  {{ warn.severity }}
                </span>
                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-slate-100 text-slate-700">
                  {{ warn.lifecycle_state || warn.status }}
                </span>
              </div>

              <div class="font-bold text-slate-900 text-xs">
                {{ warn.department?.name }} ({{ warn.department?.code }})
                <span v-if="warn.budget_bucket" class="text-slate-500 font-sans font-normal">
                  &bull; Akun: {{ warn.budget_bucket?.account_code }} - {{ warn.budget_bucket?.budget_bucket_name || warn.budget_bucket?.account_name }}
                </span>
              </div>

              <p class="text-xs text-slate-600 leading-relaxed">{{ warn.message }}</p>

              <div v-if="warn.acknowledger" class="text-[10px] text-slate-400">
                Direspon oleh: <strong>{{ warn.acknowledger?.name }}</strong> pada {{ new Date(warn.acknowledged_at).toLocaleDateString('id-ID') }}
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-2 shrink-0 border-t md:border-t-0 pt-3 md:pt-0 w-full md:w-auto justify-end">
            <button 
              v-if="warn.lifecycle_state === 'OPEN' || warn.status === 'ACTIVE'"
              @click="acknowledge(warn.id)"
              class="px-3.5 py-2 bg-sky-50 hover:bg-sky-100 border border-sky-200 text-sky-700 rounded-xl text-xs font-bold transition flex items-center gap-1"
            >
              <Eye class="w-3.5 h-3.5" /> Tandai Direspon
            </button>
            <button 
              v-if="warn.lifecycle_state !== 'RESOLVED' && warn.status !== 'RESOLVED'"
              @click="resolve(warn.id)"
              class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-1 shadow-sm"
            >
              <Check class="w-3.5 h-3.5" /> Selesaikan (Resolve)
            </button>
            <span v-else class="text-emerald-700 text-xs font-bold flex items-center gap-1">
              <CheckCircle2 class="w-4 h-4" /> Telah Diselesaikan
            </span>
          </div>
        </div>

        <div v-if="warnings.data.length === 0" class="bg-white p-12 rounded-3xl border border-slate-200 text-center text-slate-400 text-xs">
          <CheckCircle2 class="w-10 h-10 text-emerald-500 mx-auto mb-2 opacity-60" />
          Tidak ada peringatan EWS aktif yang cocok dengan filter yang dipilih.
        </div>
      </div>
    </div>
  </AppLayout>
</template>
