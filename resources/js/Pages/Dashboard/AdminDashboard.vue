<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Bar } from 'vue-chartjs';
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  LinearScale,
  CategoryScale
} from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, LinearScale, CategoryScale);

const props = defineProps({
  adminMetrics: Object,
  statusCounts: Object,
  departmentSummaries: Array,
});

const dataQualityChartData = computed(() => {
  const q = props.adminMetrics?.data_quality || {};
  return {
    labels: ['Valid Mapped', 'Warning', 'Import Error', 'Unmapped'],
    datasets: [
      {
        label: 'Jumlah Baris Data',
        data: [q.valid || 0, q.warning || 0, q.error || 0, q.unmapped || 0],
        backgroundColor: ['#10B981', '#F59E0B', '#EF4444', '#64748B'],
        borderRadius: 6,
      }
    ]
  };
});

const dataQualityChartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: '#0F172A',
      titleFont: { family: 'Poppins', size: 12, weight: '700' },
      bodyFont: { family: 'Poppins', size: 11 },
      padding: 10,
    }
  },
  scales: {
    x: { grid: { display: false }, ticks: { font: { family: 'Poppins', size: 10, weight: '600' } } },
    y: { border: { dash: [4, 4] }, ticks: { font: { family: 'Poppins', size: 10 }, precision: 0 } }
  }
}));

const mappingChartData = computed(() => {
  const valid = props.adminMetrics?.valid_mapping_count || 0;
  const unmapped = props.adminMetrics?.unmapped_count || 0;
  return {
    labels: ['Mapped (Valid)', 'Unmapped (Pending)'],
    datasets: [
      {
        label: 'Status Mapping Pos',
        data: [valid, unmapped],
        backgroundColor: ['#059669', '#F59E0B'],
        borderRadius: 6,
      }
    ]
  };
});

const mappingChartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: { backgroundColor: '#0F172A', padding: 10 }
  },
  scales: {
    x: { grid: { display: false }, ticks: { font: { family: 'Poppins', size: 10, weight: '600' } } },
    y: { border: { dash: [4, 4] }, ticks: { font: { family: 'Poppins', size: 10 }, precision: 0 } }
  }
}));
</script>

<template>
  <div class="space-y-6">
    <!-- Clean Section Header -->
    <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-slate-200/80">
      <div>
        <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Kesehatan Data &amp; Konfigurasi System Administrator</h2>
        <p class="text-xs text-slate-500">Monitoring validitas mapping pos anggaran, riwayat import data SIPEDA, dan audit trail log.</p>
      </div>

      <div class="flex flex-wrap items-center gap-3">
        <Link 
          href="/users" 
          class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold transition flex items-center shadow-sm"
        >
          Kelola Akun &amp; Peran
        </Link>
        <Link 
          href="/audit-logs" 
          class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-200 rounded-xl text-xs font-bold transition flex items-center shadow-sm"
        >
          Audit Trail Log
        </Link>
      </div>
    </div>

    <!-- 5 Clean Admin KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">TAHUN ANGGARAN</div>
        <div class="text-xl font-extrabold text-slate-900 font-sans">
          TA {{ adminMetrics?.active_fiscal_year || 2026 }}
        </div>
        <div class="text-xs text-emerald-700 font-bold">Status Aktif</div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">PENGGUNA TERDAFTAR</div>
        <div class="text-xl font-extrabold text-slate-900 font-sans">
          {{ adminMetrics?.active_users_count || 0 }}
        </div>
        <div class="text-xs text-slate-500 font-medium">Pengguna Aktif</div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-emerald-800 uppercase tracking-wider">MAPPED VALID</div>
        <div class="text-xl font-extrabold text-emerald-950 font-sans">
          {{ adminMetrics?.valid_mapping_count || 0 }}
        </div>
        <div class="text-xs text-emerald-700 font-bold">Pos Terhubung</div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-amber-800 uppercase tracking-wider">UNMAPPED</div>
        <div class="text-xl font-extrabold text-amber-950 font-sans">
          {{ adminMetrics?.unmapped_count || 0 }}
        </div>
        <div class="text-xs text-amber-700 font-semibold">Perlu Mapping</div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-slate-600 uppercase tracking-wider">IMPORT ERRORS</div>
        <div class="text-xl font-extrabold text-emerald-700 font-sans">
          0
        </div>
        <div class="text-xs text-emerald-700 font-bold">Sistem Sehat</div>
      </div>
    </div>

    <!-- 2 Admin Data Health Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-3">
        <div>
          <h3 class="text-sm font-bold text-slate-900">Kualitas &amp; Integritas Data Import</h3>
          <p class="text-xs text-slate-500">Status validasi baris data dari berkas SIPEDA / LRA</p>
        </div>
        <div class="h-64 sm:h-72 w-full relative">
          <Bar :data="dataQualityChartData" :options="dataQualityChartOptions" />
        </div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-3">
        <div>
          <h3 class="text-sm font-bold text-slate-900">Status Mapping Pos Anggaran</h3>
          <p class="text-xs text-slate-500">Ketersediaan pemetaan kode akun dengan struktur jurusan</p>
        </div>
        <div class="h-64 sm:h-72 w-full relative">
          <Bar :data="mappingChartData" :options="mappingChartOptions" />
        </div>
      </div>
    </div>

    <!-- Audit Log Summary -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
      <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <div>
          <h3 class="text-sm font-bold text-slate-900">Audit Trail Log Aktivitas Sistem Terakhir</h3>
          <p class="text-xs text-slate-500">Catatan aktivitas penting perubah data dan transaksi</p>
        </div>
        <Link href="/audit-logs" class="text-xs font-bold text-rose-600 hover:text-rose-700">Audit Log Lengkap &rarr;</Link>
      </div>

      <div v-if="adminMetrics?.recent_audit_logs && adminMetrics.recent_audit_logs.length > 0" class="overflow-x-auto">
        <table class="w-full text-left text-xs font-sans">
          <thead>
            <tr class="border-b border-slate-200 text-slate-500 uppercase tracking-wider text-[10px]">
              <th class="py-2.5 px-3">Waktu</th>
              <th class="py-2.5 px-3">Pengguna</th>
              <th class="py-2.5 px-3">Aksi</th>
              <th class="py-2.5 px-3">Modul</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="log in adminMetrics.recent_audit_logs" :key="log.id" class="hover:bg-slate-50">
              <td class="py-2.5 px-3 font-mono text-slate-600">{{ log.created_at }}</td>
              <td class="py-2.5 px-3 font-bold text-slate-900">{{ log.user?.name || 'Sistem' }}</td>
              <td class="py-2.5 px-3 font-medium text-slate-800">{{ log.action || log.event }}</td>
              <td class="py-2.5 px-3"><span class="px-2 py-0.5 bg-slate-100 rounded text-[10px] font-bold text-slate-700 uppercase">{{ log.auditable_type || 'SYSTEM' }}</span></td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="py-6 text-center text-xs text-slate-400">
        Belum ada log aktivitas tercatat.
      </div>
    </div>
  </div>
</template>
