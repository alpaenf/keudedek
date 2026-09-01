<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import LineTrendChart from '../../Components/Dashboard/LineTrendChart.vue';
import StackedBarChart from '../../Components/Dashboard/StackedBarChart.vue';
import StatusBarChart from '../../Components/Dashboard/StatusBarChart.vue';
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
  statusCounts: Object,
  departmentSummaries: Array,
  activeWarnings: Array,
  activeWarningsCount: Number,
  criticalWarningsCount: Number,
  warningSeverityCounts: Object,
  monthlyTrend: Object,
  verificationQueue: Array,
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

const warningChartData = computed(() => {
  const w = props.warningSeverityCounts || {};
  return {
    labels: ['Critical', 'High', 'Warning', 'Info'],
    datasets: [
      {
        label: 'Jumlah EWS Warning',
        data: [w.CRITICAL || 0, w.HIGH || 0, w.WARNING || 0, w.INFO || 0],
        backgroundColor: ['#EF4444', '#F97316', '#F59E0B', '#3B82F6'],
        borderRadius: 6,
      }
    ]
  };
});

const warningChartOptions = computed(() => ({
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
</script>

<template>
  <div class="space-y-6">
    <!-- Clean Section Header -->
    <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-slate-200/80">
      <div>
        <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Pengendalian Analytical &amp; Likuiditas Kabag Keuangan</h2>
        <p class="text-xs text-slate-500">Pengawasan terpusat posisi pagu 5 jurusan, rekonsiliasi keseimbangan saldo, dan mitigasi risiko EWS.</p>
      </div>

      <div class="flex flex-wrap items-center gap-3">
        <Link 
          href="/approvals" 
          class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 rounded-xl text-xs font-bold transition flex items-center shadow-sm"
        >
          Eksekusi Komitmen Saldo
        </Link>
        <Link 
          href="/reports" 
          class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-200 rounded-xl text-xs font-bold transition flex items-center shadow-sm"
        >
          Laporan Rekonsiliasi
        </Link>
      </div>
    </div>

    <!-- 5 Clean Financial KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">TOTAL PAGU FAKULTAS</div>
        <div class="text-xl font-extrabold text-slate-900 font-sans tracking-tight truncate" :title="formatRupiah(totalAllocated)">
          {{ formatRupiahCompact(totalAllocated) }}
        </div>
        <div class="text-xs text-slate-500">5 Jurusan TA 2026</div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-indigo-700 uppercase tracking-wider">KOMITMEN (RESERVED)</div>
        <div class="text-xl font-extrabold text-indigo-900 font-sans tracking-tight truncate" :title="formatRupiah(totalReserved)">
          {{ formatRupiahCompact(totalReserved) }}
        </div>
        <div class="text-xs text-indigo-700 font-semibold">Utilization: {{ utilizationRate }}%</div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider">REALISASI FINAL (LRA)</div>
        <div class="text-xl font-extrabold text-emerald-900 font-sans tracking-tight truncate" :title="formatRupiah(totalRealized)">
          {{ formatRupiahCompact(totalRealized) }}
        </div>
        <div class="text-xs text-emerald-700 font-bold">Serapan: {{ serapanRate }}%</div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-sky-800 uppercase tracking-wider">SALDO BEBAS (AVAILABLE)</div>
        <div class="text-xl font-extrabold text-sky-950 font-sans tracking-tight truncate" :title="formatRupiah(totalAvailable)">
          {{ formatRupiahCompact(totalAvailable) }}
        </div>
        <div class="text-xs text-sky-700 font-semibold">Sisa: {{ availableRate }}%</div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-amber-800 uppercase tracking-wider">OPEN WARNING EWS</div>
        <div class="text-xl font-extrabold text-amber-950 font-sans tracking-tight">
          {{ activeWarningsCount || 0 }}
        </div>
        <div class="text-xs text-amber-700 font-semibold">Peringatan Aktif</div>
      </div>
    </div>

    <!-- Panel Rekonsiliasi Saldo Keuangan -->
    <ReconciliationPanel 
      :totalAllocated="totalAllocated"
      :totalReserved="totalReserved"
      :totalRealized="totalRealized"
      :totalAvailable="totalAvailable"
    />

    <!-- 4 Analytical Charts for KABAG -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <LineTrendChart 
        title="Tren Realisasi &amp; Komitmen Fakultas"
        description="Perbandingan serapan realisasi LRA dan akumulasi komitmen reserved (Jan–Des)"
        :labels="monthlyTrend?.labels || []"
        :realizedData="monthlyTrend?.realized || []"
        :reservedData="monthlyTrend?.reserved || []"
      />

      <StackedBarChart 
        title="Kondisi Anggaran 5 Jurusan"
        description="Komposisi Realisasi Final, Reserved, dan Saldo Bebas per Jurusan"
        :departmentSummaries="departmentSummaries || []"
      />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <StatusBarChart 
        title="Pengajuan Berdasarkan Status"
        description="Distribusi usulan belanja pada setiap siklus alokasi keuangan"
        :statusCounts="statusCounts"
      />

      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-3">
        <div>
          <h3 class="text-sm font-bold text-slate-900">Distribusi Peringatan Dini (Early Warning Severity)</h3>
          <p class="text-xs text-slate-500">Klasifikasi tingkat keparahan risiko anggaran fakultas</p>
        </div>
        <div class="h-64 sm:h-72 w-full relative">
          <Bar :data="warningChartData" :options="warningChartOptions" />
        </div>
      </div>
    </div>
  </div>
</template>
