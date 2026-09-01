<script setup>
import { computed } from 'vue';
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
  title: { type: String, default: 'Aging Pengajuan' },
  description: { type: String, default: 'Distribusi durasi pengajuan yang masih dalam proses verifikasi' },
  agingData: { type: Object, default: () => ({ under1: 0, oneToThree: 0, fourToSeven: 0, overSeven: 0 }) },
});

const chartData = computed(() => {
  const data = props.agingData || {};
  return {
    labels: ['< 1 Hari', '1–3 Hari', '4–7 Hari', '> 7 Hari'],
    datasets: [
      {
        label: 'Jumlah Pengajuan',
        data: [
          data.under1 || 0,
          data.oneToThree || 0,
          data.fourToSeven || 0,
          data.overSeven || 0,
        ],
        backgroundColor: [
          '#10B981', // < 1 hari (Green)
          '#3B82F6', // 1-3 hari (Blue)
          '#F59E0B', // 4-7 hari (Amber)
          '#EF4444', // > 7 hari (Red critical)
        ],
        borderRadius: 8,
      }
    ]
  };
});

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: '#0F172A',
      titleFont: { family: 'Poppins', size: 12, weight: '700' },
      bodyFont: { family: 'Poppins', size: 11 },
      padding: 10,
      cornerRadius: 10,
      callbacks: {
        label: (context) => ` ${context.raw} pengajuan`
      }
    }
  },
  scales: {
    x: {
      grid: { display: false },
      ticks: { font: { family: 'Poppins', size: 10, weight: '600' } }
    },
    y: {
      border: { dash: [4, 4] },
      ticks: {
        font: { family: 'Poppins', size: 10 },
        precision: 0
      }
    }
  }
}));
</script>

<template>
  <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-3">
    <div>
      <h3 class="text-sm font-bold text-slate-900">{{ title }}</h3>
      <p class="text-xs text-slate-500 font-medium">{{ description }}</p>
    </div>

    <div class="h-64 sm:h-72 w-full relative">
      <Bar :data="chartData" :options="chartOptions" />
    </div>
  </div>
</template>
