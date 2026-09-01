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
  title: { type: String, default: 'Pengajuan Berdasarkan Status' },
  description: { type: String, default: 'Jumlah usulan kegiatan pada setiap tahapan alur kerja' },
  statusCounts: { type: Object, default: () => ({}) },
});

const chartData = computed(() => {
  const counts = props.statusCounts || {};
  return {
    labels: ['Draft', 'Diajukan', 'Review', 'Returned', 'Reserved', 'Processing', 'Final'],
    datasets: [
      {
        label: 'Jumlah Pengajuan',
        data: [
          counts.DRAFT || 0,
          counts.SUBMITTED || 0,
          counts.UNDER_REVIEW || 0,
          counts.RETURNED || 0,
          counts.RESERVED || 0,
          counts.PROCESSING || 0,
          counts.FINAL || 0,
        ],
        backgroundColor: [
          '#64748B', // Draft
          '#3B82F6', // Diajukan
          '#F59E0B', // Review
          '#F97316', // Returned
          '#6366F1', // Reserved
          '#06B6D4', // Processing
          '#10B981', // Final
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
