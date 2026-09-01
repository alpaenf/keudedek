<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { 
  ArrowLeft, 
  CheckCircle2, 
  XCircle, 
  AlertTriangle, 
  FileCheck,
  Building2,
  Clock
} from 'lucide-vue-next';

const props = defineProps({
  batch: Object,
});

const commitForm = useForm({
  target_status: 'SUBMITTED', // 'DRAFT' or 'SUBMITTED'
});

const executeCommit = (status) => {
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
</script>

<template>
  <AppLayout :title="`Detail Staging Batch: ${batch.batch_number}`">
    <div class="max-w-5xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <Link href="/submissions-import" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-sky-600 transition mb-2">
            <ArrowLeft class="w-4 h-4" /> Kembali ke Riwayat Import
          </Link>
          <div class="flex items-center gap-3">
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">{{ batch.batch_number }}</h2>
            <span :class="['px-3 py-1 rounded-full text-xs font-bold border uppercase', batch.status === 'COMMITTED' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200']">
              {{ batch.status }}
            </span>
          </div>
        </div>

        <!-- Commit Actions -->
        <div v-if="batch.status === 'PENDING' && batch.valid_rows > 0" class="flex items-center gap-3">
          <button 
            @click="executeCommit('DRAFT')" 
            :disabled="commitForm.processing"
            class="px-4 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-800 rounded-xl text-xs font-bold transition disabled:opacity-50"
          >
            Commit Sebagai Draft
          </button>
          <button 
            @click="executeCommit('SUBMITTED')" 
            :disabled="commitForm.processing"
            class="px-5 py-2.5 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-md shadow-sky-600/20 disabled:opacity-50"
          >
            <CheckCircle2 class="w-4 h-4" />
            Commit &amp; Ajukan Langsung ({{ batch.valid_rows }} Baris)
          </button>
        </div>
      </div>

      <!-- Metric Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-1">
          <span class="text-xs font-bold text-slate-500 uppercase">Total Baris CSV</span>
          <div class="text-2xl font-black text-slate-900 font-sans">{{ batch.total_rows }}</div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-emerald-200 bg-emerald-50/20 shadow-sm space-y-1">
          <span class="text-xs font-bold text-emerald-700 uppercase">Baris Valid (Siap Commit)</span>
          <div class="text-2xl font-black text-emerald-900 font-sans">{{ batch.valid_rows }}</div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-rose-200 bg-rose-50/20 shadow-sm space-y-1">
          <span class="text-xs font-bold text-rose-700 uppercase">Baris Invalid (Ditolak)</span>
          <div class="text-2xl font-black text-rose-900 font-sans">{{ batch.invalid_rows }}</div>
        </div>
      </div>

      <!-- Staging Rows Table -->
      <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden p-6 space-y-4">
        <h3 class="text-sm font-bold text-slate-900">Tabel Preview Validasi Staging Data</h3>

        <div class="overflow-x-auto">
          <table class="w-full text-xs text-left">
            <thead class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider text-[10px] border-y border-slate-200">
              <tr>
                <th class="py-2.5 px-3 text-center">Baris</th>
                <th class="py-2.5 px-3">Jurusan</th>
                <th class="py-2.5 px-3">Judul Usulan Kegiatan</th>
                <th class="py-2.5 px-3">Akun</th>
                <th class="py-2.5 px-3 text-right">Nominal</th>
                <th class="py-2.5 px-3 text-center">Status</th>
                <th class="py-2.5 px-3">Hasil Evaluasi / Catatan</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="stg in batch.stagings" :key="stg.id" :class="stg.validation_status === 'VALID' ? 'hover:bg-slate-50/60' : 'bg-rose-50/30 hover:bg-rose-50/50'">
                <td class="py-3 px-3 text-center font-sans font-bold">{{ stg.row_number }}</td>
                <td class="py-3 px-3 font-semibold text-slate-800">{{ stg.department_code }}</td>
                <td class="py-3 px-3 font-bold text-slate-900">{{ stg.title }}</td>
                <td class="py-3 px-3 font-sans text-slate-600">{{ stg.account_code }}</td>
                <td class="py-3 px-3 text-right font-sans font-bold text-slate-900">{{ formatRupiah(stg.amount) }}</td>
                <td class="py-3 px-3 text-center">
                  <span :class="['px-2 py-0.5 rounded-md text-[10px] font-bold border uppercase', stg.validation_status === 'VALID' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200']">
                    {{ stg.validation_status }}
                  </span>
                </td>
                <td class="py-3 px-3 text-xs">
                  <div v-if="stg.validation_status === 'VALID'" class="text-emerald-700 font-semibold flex items-center gap-1">
                    <CheckCircle2 class="w-3.5 h-3.5" /> Lolos Validasi Master &amp; RBC
                  </div>
                  <div v-else class="space-y-1">
                    <div v-for="(err, eidx) in stg.error_messages" :key="eidx" class="text-rose-700 font-medium text-[11px] flex items-start gap-1">
                      <span>&bull;</span> <span>{{ err }}</span>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
