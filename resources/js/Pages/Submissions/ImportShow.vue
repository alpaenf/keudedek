<script setup>
import { ref, computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { 
  ArrowLeft, 
  CheckCircle2, 
  XCircle, 
  AlertTriangle, 
  FileCheck,
  Building2,
  Clock,
  Layers,
  Sparkles,
  Search,
  Check,
  Copy,
  Info
} from 'lucide-vue-next';

const props = defineProps({
  batch: Object,
  summary: Object,
  stagings: Array,
});

const activeTab = ref('ALL'); // 'ALL' | 'VALID' | 'DUPLICATE' | 'ERROR'
const searchQuery = ref('');

const commitForm = useForm({
  target_status: 'PROCESSING', // 'DRAFT' or 'PROCESSING'
});

const executeCommit = (status = 'PROCESSING') => {
  commitForm.target_status = status;
  commitForm.post(`/submissions-import/${props.batch.id}/commit`);
};

const formatRupiah = (val) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(val || 0);
};

const filteredStagings = computed(() => {
  if (!props.stagings) return [];

  let list = props.stagings;

  if (activeTab.value === 'VALID') {
    list = list.filter(s => s.validation_status === 'VALID');
  } else if (activeTab.value === 'DUPLICATE') {
    list = list.filter(s => s.duplicate_status && s.duplicate_status !== 'NONE');
  } else if (activeTab.value === 'ERROR') {
    list = list.filter(s => s.validation_status === 'INVALID');
  }

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase();
    list = list.filter(s => 
      s.evidence_number?.toLowerCase().includes(q) ||
      s.title?.toLowerCase().includes(q) ||
      s.account_code?.toLowerCase().includes(q) ||
      s.department_code?.toLowerCase().includes(q)
    );
  }

  return list;
});
</script>

<template>
  <AppLayout :title="`Preview Staging &amp; Budget Matching: ${batch.batch_number}`">
    <div class="max-w-7xl mx-auto space-y-6 font-sans">
      
      <!-- Top Title & Action Bar -->
      <div class="bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <Link href="/submissions-import" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-sky-600 transition mb-1.5">
            <ArrowLeft class="w-4 h-4" /> Kembali ke Riwayat Import
          </Link>
          <div class="flex items-center gap-3">
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">{{ batch.batch_number }}</h1>
            <span :class="['px-3 py-1 rounded-full text-xs font-bold border uppercase', batch.status === 'COMMITTED' ? 'bg-emerald-50 text-emerald-800 border-emerald-300' : 'bg-amber-50 text-amber-800 border-amber-300']">
              {{ batch.status === 'COMMITTED' ? 'COMMITTED (SELESAI)' : 'PENDING STAGING' }}
            </span>
          </div>
          <p class="text-xs text-slate-500 mt-0.5">
            Pengunggah: <strong class="text-slate-800">{{ batch.user?.name || 'Operator PTK' }}</strong> &bull; Waktu: {{ new Date(batch.created_at).toLocaleString('id-ID') }}
          </p>
        </div>

        <!-- Commit Actions -->
        <div v-if="batch.status === 'PENDING' && summary?.valid > 0" class="flex flex-wrap items-center gap-3">
          <button 
            type="button"
            @click="executeCommit('DRAFT')" 
            :disabled="commitForm.processing"
            class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-2xl text-xs font-bold transition disabled:opacity-50 shadow-sm"
          >
            Commit Sebagai Draft
          </button>

          <button 
            type="button"
            @click="executeCommit('PROCESSING')" 
            :disabled="commitForm.processing"
            class="px-5 py-2.5 bg-sky-600 hover:bg-sky-500 text-white rounded-2xl text-xs font-bold transition flex items-center gap-2 shadow-md shadow-sky-600/20 disabled:opacity-50"
          >
            <CheckCircle2 class="w-4 h-4" />
            <span>Commit &amp; Proses ({{ summary?.valid }} Baris Valid)</span>
          </button>
        </div>
      </div>

      <!-- 5 Summary Metric Cards -->
      <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-5 gap-3">
        <!-- 1. Total Baris -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Baris</span>
          <span class="text-xl font-black text-slate-900 font-sans block mt-1">{{ summary?.total || batch.total_rows }}</span>
        </div>

        <!-- 2. Valid & Matched -->
        <div class="bg-white p-4 rounded-2xl border border-emerald-200 bg-emerald-50/20 shadow-sm">
          <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider block">Valid &amp; Matched</span>
          <span class="text-xl font-black text-emerald-950 font-sans block mt-1">{{ summary?.valid || batch.valid_rows }}</span>
        </div>

        <!-- 3. Duplikat Terdeteksi -->
        <div class="bg-white p-4 rounded-2xl border border-amber-200 bg-amber-50/20 shadow-sm">
          <span class="text-[10px] font-bold text-amber-700 uppercase tracking-wider block">Duplikat Terdeteksi</span>
          <span class="text-xl font-black text-amber-950 font-sans block mt-1">{{ summary?.duplicate || 0 }}</span>
        </div>

        <!-- 4. Unmatched / Overbudget -->
        <div class="bg-white p-4 rounded-2xl border border-rose-200 bg-rose-50/20 shadow-sm">
          <span class="text-[10px] font-bold text-rose-700 uppercase tracking-wider block">Invalid / Overbudget</span>
          <span class="text-xl font-black text-rose-950 font-sans block mt-1">{{ summary?.invalid || batch.invalid_rows }}</span>
        </div>

        <!-- 5. Kesiapan Commit -->
        <div class="bg-white p-4 rounded-2xl border border-sky-200 bg-sky-50/20 shadow-sm col-span-2 sm:col-span-4 lg:col-span-1">
          <span class="text-[10px] font-bold text-sky-700 uppercase tracking-wider block">Kesiapan Commit</span>
          <span class="text-xs font-bold text-sky-950 block mt-2 flex items-center gap-1">
            <CheckCircle2 v-if="summary?.valid > 0" class="w-4 h-4 text-emerald-600" />
            <span>{{ summary?.valid > 0 ? 'Siap Di-Commit' : 'Perlu Koreksi' }}</span>
          </span>
        </div>
      </div>

      <!-- Interactive Tabs & Search Filter -->
      <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden space-y-4 p-5 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
          <!-- Tab Buttons -->
          <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-2xl text-xs font-bold">
            <button 
              @click="activeTab = 'ALL'"
              :class="['px-3.5 py-1.5 rounded-xl transition', activeTab === 'ALL' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900']"
            >
              Semua Baris ({{ summary?.total || 0 }})
            </button>
            <button 
              @click="activeTab = 'VALID'"
              :class="['px-3.5 py-1.5 rounded-xl transition', activeTab === 'VALID' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900']"
            >
              Valid ({{ summary?.valid || 0 }})
            </button>
            <button 
              @click="activeTab = 'DUPLICATE'"
              :class="['px-3.5 py-1.5 rounded-xl transition', activeTab === 'DUPLICATE' ? 'bg-amber-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900']"
            >
              Duplikat ({{ summary?.duplicate || 0 }})
            </button>
            <button 
              @click="activeTab = 'ERROR'"
              :class="['px-3.5 py-1.5 rounded-xl transition', activeTab === 'ERROR' ? 'bg-rose-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900']"
            >
              Error ({{ summary?.invalid || 0 }})
            </button>
          </div>

          <!-- Quick Search in Table -->
          <div class="relative w-full sm:w-64">
            <Search class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
            <input 
              v-model="searchQuery"
              type="text" 
              placeholder="Cari bukti / uraian / akun..." 
              class="w-full pl-9 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none"
            />
          </div>
        </div>

        <!-- Staging Table with Auto-Resolved Master Data -->
        <div class="overflow-x-auto">
          <table class="w-full text-xs text-left font-sans border-collapse">
            <thead class="bg-slate-50 text-slate-400 uppercase tracking-wider text-[10px] border-y border-slate-200">
              <tr>
                <th class="py-3 px-2 text-center font-semibold">#</th>
                <th class="py-3 px-3 font-semibold">Nomor Bukti</th>
                <th class="py-3 px-3 font-semibold">Tanggal</th>
                <th class="py-3 px-3 font-semibold">Jurusan / Prodi</th>
                <th class="py-3 px-3 font-semibold">Akun &amp; Uraian</th>
                <th class="py-3 px-3 text-right font-semibold">Nominal (Rp)</th>
                <th class="py-3 px-3.5 font-semibold">Auto-Resolved Hierarchy (Master)</th>
                <th class="py-3 px-3 text-center font-semibold">Status Validasi</th>
                <th class="py-3 px-3 font-semibold">Catatan / Evaluasi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr 
                v-for="stg in filteredStagings" 
                :key="stg.id" 
                :class="[
                  'transition',
                  stg.validation_status === 'VALID' ? 'hover:bg-slate-50/70' : 'bg-rose-50/30 hover:bg-rose-50/50'
                ]"
              >
                <!-- 1. Row No -->
                <td class="py-3.5 px-2 text-center font-bold text-slate-400 font-sans">
                  {{ stg.row_number }}
                </td>

                <!-- 2. Nomor Bukti -->
                <td class="py-3.5 px-3 whitespace-nowrap">
                  <span class="font-bold text-slate-900 font-sans block text-xs">
                    {{ stg.evidence_number || stg.reference_no }}
                  </span>
                  <span v-if="stg.duplicate_status !== 'NONE'" class="text-[9px] font-bold text-amber-700 bg-amber-100 px-1.5 py-0.5 rounded mt-0.5 inline-block">
                    {{ stg.duplicate_status === 'DUPLICATE_IN_FILE' ? 'Duplikat Berkas' : 'Duplikat Database' }}
                  </span>
                </td>

                <!-- 3. Tanggal -->
                <td class="py-3.5 px-3 whitespace-nowrap text-slate-600 font-medium">
                  {{ stg.transaction_date ? new Date(stg.transaction_date).toLocaleDateString('id-ID') : '-' }}
                </td>

                <!-- 4. Jurusan / Prodi -->
                <td class="py-3.5 px-3 whitespace-nowrap">
                  <span class="font-black text-slate-900 block text-xs">{{ stg.department_code }}</span>
                  <span class="text-[10px] text-slate-500 block">{{ stg.study_program_code || 'Level Jurusan' }}</span>
                </td>

                <!-- 5. Akun & Uraian -->
                <td class="py-3.5 px-3 max-w-xs">
                  <span class="font-bold text-sky-900 font-sans bg-sky-50 px-2 py-0.5 rounded border border-sky-200 text-[11px]">
                    [{{ stg.account_code }}]
                  </span>
                  <div class="font-bold text-slate-900 mt-1 line-clamp-1" :title="stg.title">
                    {{ stg.title }}
                  </div>
                </td>

                <!-- 6. Nominal -->
                <td class="py-3.5 px-3 text-right font-black text-slate-900 font-sans whitespace-nowrap">
                  {{ formatRupiah(stg.amount) }}
                </td>

                <!-- 7. Auto-Resolved Master Hierarchy -->
                <td class="py-3.5 px-3.5 max-w-sm">
                  <div v-if="stg.matched_hierarchy" class="space-y-1 text-[10px]">
                    <div class="flex items-center gap-1.5 text-emerald-800 font-bold">
                      <Sparkles class="w-3 h-3 text-emerald-600 shrink-0" />
                      <span>Terpetakan ke Master Pagu</span>
                    </div>
                    <div class="text-slate-600 truncate">
                      <strong>Keg:</strong> {{ stg.matched_hierarchy.activity_code }} &bull; <strong>KRO:</strong> {{ stg.matched_hierarchy.kro_code }} &bull; <strong>Subkomp:</strong> {{ stg.matched_hierarchy.subcomponent_code }}
                    </div>
                    <div class="text-slate-500 truncate">
                      <strong>Saldo Tersedia:</strong> <span class="font-bold text-emerald-700">{{ formatRupiah(stg.matched_hierarchy.available_balance) }}</span>
                    </div>
                  </div>
                  <div v-else class="text-[11px] text-rose-600 italic">
                    Belum terpetakan ke pos pagu master
                  </div>
                </td>

                <!-- 8. Status Validasi -->
                <td class="py-3.5 px-3 text-center whitespace-nowrap">
                  <span :class="[
                    'px-2.5 py-0.5 rounded-full text-[10px] font-bold border uppercase',
                    stg.validation_status === 'VALID' ? 'bg-emerald-50 text-emerald-800 border-emerald-300' : 'bg-rose-50 text-rose-800 border-rose-300'
                  ]">
                    {{ stg.validation_status }}
                  </span>
                </td>

                <!-- 9. Catatan / Evaluasi -->
                <td class="py-3.5 px-3 text-xs">
                  <div v-if="stg.validation_status === 'VALID'" class="text-emerald-700 font-semibold flex items-center gap-1">
                    <CheckCircle2 class="w-3.5 h-3.5" /> Siap di-commit
                  </div>
                  <div v-else class="space-y-0.5">
                    <div v-for="(err, eidx) in stg.error_messages" :key="eidx" class="text-rose-700 font-medium text-[11px] flex items-start gap-1">
                      <span>&bull;</span> <span>{{ err }}</span>
                    </div>
                  </div>
                </td>
              </tr>

              <tr v-if="filteredStagings.length === 0">
                <td colspan="9" class="py-10 text-center text-slate-400 text-xs">
                  Tidak ada baris data transaksi pada tab filter ini.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </AppLayout>
</template>
