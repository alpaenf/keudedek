<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import LineTrendChart from '../../Components/Dashboard/LineTrendChart.vue';
import ReconciliationPanel from '../../Components/Dashboard/ReconciliationPanel.vue';
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
  totalAllocated: Number,
  totalReserved: Number,
  totalRealized: Number,
  totalAvailable: Number,
  serapanRate: Number,
  utilizationRate: Number,
  availableRate: Number,
  departmentSummaries: Array,
  activeWarnings: Array,
  criticalWarningsCount: Number,
  verificationQueue: Array,
  monthlyTrend: Object,
});

const formatRupiah = (value) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(value || 0);
};

const formatRupiahCompact = (val) => {
  const num = Number(val) || 0;
  if (Math.abs(num) >= 1_000_000_000_000) return 'Rp ' + (num / 1_000_000_000_000).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) + ' T';
  if (Math.abs(num) >= 1_000_000_000) return 'Rp ' + (num / 1_000_000_000).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) + ' M';
  if (Math.abs(num) >= 1_000_000) return 'Rp ' + (num / 1_000_000).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 1 }) + ' Jt';
  return formatRupiah(num);
};

const utilizationChartData = computed(() => {
  const depts = props.departmentSummaries || [];
  return {
    labels: depts.map(d => d.code || d.name),
    datasets: [
      {
        label: 'Serapan Realisasi (%)',
        data: depts.map(d => d.serapan),
        backgroundColor: '#059669',
        borderRadius: 4,
      },
      {
        label: 'Budget Utilization (%)',
        data: depts.map(d => d.utilization),
        backgroundColor: '#6366F1',
        borderRadius: 4,
      }
    ]
  };
});

const utilizationChartOptions = computed(() => ({
  indexAxis: 'y',
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
      callbacks: {
        label: (context) => ` ${context.dataset.label}: ${context.raw}%`
      }
    }
  },
  scales: {
    x: { border: { dash: [4, 4] }, ticks: { font: { family: 'Poppins', size: 10 }, callback: v => `${v}%` } },
    y: { grid: { display: false }, ticks: { font: { family: 'Poppins', size: 11, weight: '700' } } }
  }
}));
</script>

<template>
  <div class="space-y-6">
    <!-- Clean Section Header -->
    <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-slate-200/80">
      <div>
        <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Monitoring Kebijakan Keuangan Wakil Dekan II</h2>
        <p class="text-xs text-slate-500">Evaluasi efisiensi penyerapan anggaran 5 jurusan, kebijakan revisi pagu, dan persetujuan strategis.</p>
      </div>

      <div class="flex items-center gap-3">
        <Link 
          href="/approvals" 
          class="px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white rounded-xl text-xs font-bold transition flex items-center shadow-sm"
        >
          Persetujuan Strategis Pimpinan
        </Link>
      </div>
    </div>

    <!-- 5 Strategic KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">TOTAL PAGU FAKULTAS</div>
        <div class="text-xl font-extrabold text-slate-900 font-sans tracking-tight truncate" :title="formatRupiah(totalAllocated)">
          {{ formatRupiahCompact(totalAllocated) }}
        </div>
        <div class="text-xs text-slate-500">5 Jurusan TA 2026</div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider">REALISASI FINAL (LRA)</div>
        <div class="text-xl font-extrabold text-emerald-900 font-sans tracking-tight truncate" :title="formatRupiah(totalRealized)">
          {{ formatRupiahCompact(totalRealized) }}
        </div>
        <div class="text-xs text-emerald-700 font-bold">Serapan: {{ serapanRate }}%</div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-indigo-700 uppercase tracking-wider">KOMITMEN (RESERVED)</div>
        <div class="text-xl font-extrabold text-indigo-900 font-sans tracking-tight truncate" :title="formatRupiah(totalReserved)">
          {{ formatRupiahCompact(totalReserved) }}
        </div>
        <div class="text-xs text-indigo-700 font-semibold">Terkunci</div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-sky-800 uppercase tracking-wider">SALDO BEBAS (AVAILABLE)</div>
        <div class="text-xl font-extrabold text-sky-950 font-sans tracking-tight truncate" :title="formatRupiah(totalAvailable)">
          {{ formatRupiahCompact(totalAvailable) }}
        </div>
        <div class="text-xs text-sky-700 font-semibold">Sisa: {{ availableRate }}%</div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-rose-800 uppercase tracking-wider">CRITICAL WARNING</div>
        <div class="text-xl font-extrabold text-rose-950 font-sans tracking-tight">
          {{ criticalWarningsCount || 0 }}
        </div>
        <div class="text-xs text-rose-700 font-semibold">Risiko Tinggi</div>
      </div>
    </div>

    <!-- Panel Rekonsiliasi Saldo Keuangan -->
    <ReconciliationPanel 
      :totalAllocated="totalAllocated"
      :totalReserved="totalReserved"
      :totalRealized="totalRealized"
      :totalAvailable="totalAvailable"
    />

    <!-- 2 Strategic Charts for WD II -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <LineTrendChart 
        title="Tren Serapan Realisasi Fakultas (%)"
        description="Persentase akumulasi serapan LRA dari bulan ke bulan"
        mode="percentage"
        :labels="monthlyTrend?.labels || []"
        :serapanPctData="monthlyTrend?.serapanPct || []"
      />

      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-3">
        <div>
          <h3 class="text-sm font-bold text-slate-900">Perbandingan Utilization &amp; Serapan 5 Jurusan</h3>
          <p class="text-xs text-slate-500">Evaluasi efisiensi penggunaan pagu antar-jurusan</p>
        </div>
        <div class="h-64 sm:h-72 w-full relative">
          <Bar :data="utilizationChartData" :options="utilizationChartOptions" />
        </div>
      </div>
    </div>
  </div>
</template>
