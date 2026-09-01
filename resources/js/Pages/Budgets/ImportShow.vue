<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { ArrowLeft, CheckCircle2, AlertTriangle, Check } from '@lucide/vue';

const props = defineProps({
  history: Object,
  stagings: Object,
});

const formCommit = useForm({});

const commitImport = () => {
  if (confirm(`Apakah Anda yakin ingin me-commit ${props.history.valid_rows} pos pagu anggaran ke basis data aktif?`)) {
    formCommit.post(`/budgets-import/${props.history.id}/commit`);
  }
};

const formatRupiah = (val) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0);
};
</script>

<template>
  <AppLayout :title="`Detail Staging: ${history.filename}`">
    <div class="max-w-6xl mx-auto space-y-6">
      <!-- Header Banner -->
      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <Link href="/budgets-import" class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition">
            <ArrowLeft class="w-5 h-5" />
          </Link>
          <div>
            <h3 class="font-bold text-slate-900 text-base">Hasil Verifikasi Staging: {{ history.filename }}</h3>
            <p class="text-xs text-slate-500">Diunggah oleh {{ history.user?.name }} &bull; {{ new Date(history.created_at).toLocaleString('id-ID') }}</p>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <button v-if="history.status === 'PENDING'" @click="commitImport" :disabled="formCommit.processing || history.invalid_rows > 0" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-semibold flex items-center gap-2 transition disabled:opacity-50 shadow-sm">
            <Check class="w-4 h-4" /> Commit ke Pagu Aktif
          </button>
          <span v-else class="px-4 py-2 bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-xl text-xs font-bold">
            Sudah Di-Commit
          </span>
        </div>
      </div>

      <!-- Stat Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200">
          <span class="text-xs text-slate-500 block">Total Baris Data</span>
          <span class="font-bold text-slate-900 text-lg">{{ history.total_rows }} Baris</span>
        </div>
        <div class="bg-emerald-50 p-4 rounded-xl border border-emerald-200">
          <span class="text-xs text-emerald-700 block">Baris Valid (Siap Commit)</span>
          <span class="font-bold text-emerald-900 text-lg">{{ history.valid_rows }} Baris</span>
        </div>
        <div :class="['p-4 rounded-xl border', history.invalid_rows > 0 ? 'bg-rose-50 border-rose-200 text-rose-900' : 'bg-slate-50 border-slate-200 text-slate-600']">
          <span class="text-xs block">Baris Bermasalah (Invalid)</span>
          <span class="font-bold text-lg">{{ history.invalid_rows }} Baris</span>
        </div>
      </div>

      <!-- Staging Data Table -->
      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100 font-bold text-slate-900 text-sm">
          Preview Baris Staging Validation
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-200">
                <th class="py-3 px-4">Status Validation</th>
                <th class="py-3 px-4">Kode Jurusan</th>
                <th class="py-3 px-4">Tahun</th>
                <th class="py-3 px-4">Sumber</th>
                <th class="py-3 px-4">Kode &amp; Nama Akun</th>
                <th class="py-3 px-4 text-right">Pagu Awal (Rp)</th>
                <th class="py-3 px-4">Keterangan / Error</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-900">
              <tr v-for="stg in stagings.data" :key="stg.id" :class="stg.status === 'INVALID' ? 'bg-rose-50/50' : 'hover:bg-slate-50/80'">
                <td class="py-3 px-4 whitespace-nowrap">
                  <span v-if="stg.status === 'VALID'" class="inline-flex items-center gap-1 text-emerald-700 font-bold bg-emerald-100 px-2 py-0.5 rounded-full text-[10px]">
                    <CheckCircle2 class="w-3 h-3" /> VALID
                  </span>
                  <span v-else class="inline-flex items-center gap-1 text-rose-700 font-bold bg-rose-100 px-2 py-0.5 rounded-full text-[10px]">
                    <AlertTriangle class="w-3 h-3" /> INVALID
                  </span>
                </td>
                <td class="py-3 px-4 font-bold text-slate-900">{{ stg.department_code }}</td>
                <td class="py-3 px-4 text-slate-700">{{ stg.fiscal_year }}</td>
                <td class="py-3 px-4 font-semibold text-slate-800">{{ stg.funding_source_code }}</td>
                <td class="py-3 px-4">
                  <span class="font-sans font-bold text-sky-700 block">{{ stg.account_code }}</span>
                  <span class="text-slate-900">{{ stg.account_name }}</span>
                </td>
                <td class="py-3 px-4 text-right font-bold text-slate-900 font-sans">{{ formatRupiah(stg.initial_budget) }}</td>
                <td class="py-3 px-4">
                  <span v-if="stg.error_message" class="text-rose-600 font-semibold">{{ stg.error_message }}</span>
                  <span v-else class="text-slate-400 font-normal">Siap di-commit</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
