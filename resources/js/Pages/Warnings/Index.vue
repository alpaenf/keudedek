<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { AlertTriangle } from '@lucide/vue';

const props = defineProps({
  warnings: Object,
  departments: Array,
  filters: Object,
});

const severity = ref(props.filters?.severity || '');
const status = ref(props.filters?.status || '');

const handleFilter = () => {
  router.get('/warnings', {
    severity: severity.value,
    status: status.value,
  }, { preserveState: true });
};

const acknowledge = (id) => {
  router.post(`/warnings/${id}/acknowledge`);
};
</script>

<template>
  <AppLayout title="Early Warning System (EWS)">
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm mb-6 flex flex-wrap items-center justify-between gap-4">
      <div>
        <h3 class="font-bold text-slate-900 text-base">Monitoring Deteksi Dini Saldo Anggaran</h3>
        <p class="text-xs text-slate-500">Daftar Peringatan Dini otomatis berbasis Rule-Engine (EWS-001, EWS-002, EWS-003)</p>
      </div>

      <div class="flex items-center gap-3">
        <select v-model="severity" @change="handleFilter" class="px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-semibold text-slate-900">
          <option value="">Semua Severity</option>
          <option value="CRITICAL">CRITICAL</option>
          <option value="HIGH">HIGH</option>
          <option value="MEDIUM">MEDIUM</option>
        </select>

        <select v-model="status" @change="handleFilter" class="px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-semibold text-slate-900">
          <option value="">Semua Status</option>
          <option value="ACTIVE">ACTIVE</option>
          <option value="ACKNOWLEDGED">ACKNOWLEDGED</option>
          <option value="RESOLVED">RESOLVED</option>
        </select>
      </div>
    </div>

    <div class="space-y-4">
      <div v-for="warn in warnings.data" :key="warn.id" class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div class="flex items-start gap-4">
          <div class="w-12 h-12 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
            <AlertTriangle class="w-6 h-6" />
          </div>
          <div>
            <div class="flex items-center gap-2 mb-1">
              <span class="font-mono font-extrabold text-xs text-sky-700">{{ warn.rule_code }}</span>
              <span class="px-2 py-0.5 rounded text-[10px] font-extrabold text-white bg-rose-600">{{ warn.severity }}</span>
              <span :class="['px-2 py-0.5 rounded text-[10px] font-bold border', warn.status === 'ACTIVE' ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-sky-50 text-sky-800 border-sky-200']">{{ warn.status }}</span>
            </div>
            <h4 class="font-bold text-slate-900 text-sm">{{ warn.department?.name }} ({{ warn.department?.code }})</h4>
            <p class="text-xs text-slate-600 mt-1">{{ warn.message }}</p>
          </div>
        </div>

        <div class="flex items-center gap-3 w-full md:w-auto justify-between md:justify-end border-t md:border-0 pt-3 md:pt-0 border-slate-100">
          <button v-if="warn.status === 'ACTIVE'" @click="acknowledge(warn.id)" class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-semibold transition">
            Konfirmasi (Acknowledge)
          </button>
          <span v-else class="text-xs text-slate-500 italic">Dikonfirmasi oleh {{ warn.acknowledger?.name ?? 'User' }}</span>
        </div>
      </div>

      <div v-if="warnings.data.length === 0" class="bg-white p-12 rounded-2xl border border-slate-200 text-center text-slate-500">
        Tidak ada peringatan dini yang terdaftar saat ini.
      </div>
    </div>
  </AppLayout>
</template>
