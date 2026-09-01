<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AgingBarChart from '../../Components/Dashboard/AgingBarChart.vue';
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
  ptkWorkload: Array,
  statusCounts: Object,
  agingDistribution: Object,
  recentSubmissions: Array,
});

const workloadChartData = computed(() => {
  const workload = props.ptkWorkload || [];
  return {
    labels: workload.map(w => w.department_code),
    datasets: [
      {
        label: 'Pengajuan Aktif',
        data: workload.map(w => w.active_count),
        backgroundColor: '#0EA5E9',
        borderRadius: 6,
      },
      {
        label: 'Returned (Perlu Perbaikan)',
        data: workload.map(w => w.returned_count),
        backgroundColor: '#F97316',
        borderRadius: 6,
      },
      {
        label: 'Stale (> 7 Hari)',
        data: workload.map(w => w.stale_count),
        backgroundColor: '#EF4444',
        borderRadius: 6,
      }
    ]
  };
});

const workloadChartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top',
      align: 'end',
      labels: { font: { family: 'Poppins', size: 11, weight: '600' }, boxWidth: 8 }
    },
    tooltip: {
      backgroundColor: '#0F172A',
      titleFont: { family: 'Poppins', size: 12, weight: '700' },
      bodyFont: { family: 'Poppins', size: 11 },
      padding: 10,
      cornerRadius: 10,
    }
  },
  scales: {
    x: { grid: { display: false }, ticks: { font: { family: 'Poppins', size: 11, weight: '700' } } },
    y: { border: { dash: [4, 4] }, ticks: { font: { family: 'Poppins', size: 10 }, precision: 0 } }
  }
}));

const totalActiveSubmissions = computed(() => {
  const c = props.statusCounts || {};
  return (c.SUBMITTED || 0) + (c.UNDER_REVIEW || 0) + (c.RETURNED || 0) + (c.RESERVED || 0) + (c.PROCESSING || 0);
});
</script>

<template>
  <div class="space-y-6">
    <!-- Clean Section Header -->
    <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-slate-200/80">
      <div>
        <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Monitoring &amp; Koordinasi Workload Ketua PTK</h2>
        <p class="text-xs text-slate-500">Pemantauan distribusi beban kerja usulan kegiatan, percepatan pengajuan returned, dan retensi berkas.</p>
      </div>

      <div class="flex items-center gap-3">
        <Link 
          href="/submissions" 
          class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-bold transition flex items-center shadow-sm"
        >
          Semua Pengajuan Fakultas
        </Link>
      </div>
    </div>

    <!-- 5 Clean KPI Cards for Ketua PTK -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-sky-800 uppercase tracking-wider">PENGAJUAN AKTIF</div>
        <div class="text-xl font-extrabold text-sky-950 font-sans">
          {{ totalActiveSubmissions }}
        </div>
        <div class="text-xs text-sky-700 font-medium">Seluruh 5 jurusan</div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-blue-700 uppercase tracking-wider">MENUNGGU PROSES</div>
        <div class="text-xl font-extrabold text-blue-950 font-sans">
          {{ (statusCounts?.SUBMITTED || 0) + (statusCounts?.UNDER_REVIEW || 0) }}
        </div>
        <div class="text-xs text-blue-700 font-medium">Antrean review</div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-orange-800 uppercase tracking-wider">RETURNED</div>
        <div class="text-xl font-extrabold text-orange-950 font-sans">
          {{ statusCounts?.RETURNED || 0 }}
        </div>
        <div class="text-xs text-orange-700 font-medium">Perlu perbaikan PTK</div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-emerald-800 uppercase tracking-wider">FINAL BULAN INI</div>
        <div class="text-xl font-extrabold text-emerald-950 font-sans">
          {{ statusCounts?.FINAL || 0 }}
        </div>
        <div class="text-xs text-emerald-700 font-medium">Pencairan selesai</div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-rose-800 uppercase tracking-wider">STALE (&gt; 7 HARI)</div>
        <div class="text-xl font-extrabold text-rose-950 font-sans">
          {{ agingDistribution?.overSeven || 0 }}
        </div>
        <div class="text-xs text-rose-700 font-semibold">Terhambat lama</div>
      </div>
    </div>

    <!-- 2 Workload & Aging Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-3">
        <div>
          <h3 class="text-sm font-bold text-slate-900">Workload Pengajuan Aktif per Jurusan</h3>
          <p class="text-xs text-slate-500">Perbandingan jumlah usulan aktif, returned, dan stale across 5 jurusan</p>
        </div>
        <div class="h-64 sm:h-72 w-full relative">
          <Bar :data="workloadChartData" :options="workloadChartOptions" />
        </div>
      </div>

      <AgingBarChart 
        title="Aging Pengajuan Fakultas"
        description="Lama waktu mengendap usulan kegiatan sebelum penyelesaian final"
        :agingData="agingDistribution"
      />
    </div>

    <!-- Workload Monitoring Table per Department -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
      <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <div>
          <h3 class="text-sm font-bold text-slate-900">Rekapitulasi Workload Operator PTK 5 Jurusan</h3>
          <p class="text-xs text-slate-500">Monitoring distribusi usulan kegiatan untuk memastikan kelancaran operasional</p>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs font-sans">
          <thead>
            <tr class="border-b border-slate-200 text-slate-500 uppercase tracking-wider text-[10px]">
              <th class="py-2.5 px-3">Jurusan</th>
              <th class="py-2.5 px-3 text-center">Pengajuan Aktif</th>
              <th class="py-2.5 px-3 text-center">Returned</th>
              <th class="py-2.5 px-3 text-center">Stale (&gt; 7 Hari)</th>
              <th class="py-2.5 px-3 text-center">Status Workload</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="item in ptkWorkload" :key="item.department_code" class="hover:bg-slate-50">
              <td class="py-3 px-3 font-bold text-slate-900">
                <span>{{ item.department_code }}</span>
                <div class="text-[10px] text-slate-500 font-normal truncate max-w-[180px]">{{ item.department_name }}</div>
              </td>
              <td class="py-3 px-3 text-center font-bold font-sans text-sky-900">{{ item.active_count }}</td>
              <td class="py-3 px-3 text-center font-bold font-sans text-orange-700">{{ item.returned_count }}</td>
              <td class="py-3 px-3 text-center font-bold font-sans text-rose-700">{{ item.stale_count }}</td>
              <td class="py-3 px-3 text-center">
                <span :class="['px-2 py-0.5 rounded text-[10px] font-bold uppercase', item.stale_count > 0 ? 'bg-rose-50 text-rose-700 border border-rose-200' : (item.returned_count > 2 ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200')]">
                  {{ item.stale_count > 0 ? 'Perlu Dorongan' : (item.returned_count > 2 ? 'Perhatian' : 'Lancar') }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
