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
  title: { type: String, default: 'Komposisi Anggaran' },
  description: { type: String, default: 'Proporsi Realisasi Final, Komitmen (Reserved), dan Saldo Bebas' },
  departmentSummaries: { type: Array, default: () => [] },
  isSingleDepartment: { type: Boolean, default: false },
  singleAllocated: { type: Number, default: 0 },
  singleReserved: { type: Number, default: 0 },
  singleRealized: { type: Number, default: 0 },
  singleAvailable: { type: Number, default: 0 },
});

const formatCompactRupiah = (val) => {
  const num = Number(val) || 0;
  if (Math.abs(num) >= 1_000_000_000_000) return (num / 1_000_000_000_000).toFixed(1) + ' T';
  if (Math.abs(num) >= 1_000_000_000) return (num / 1_000_000_000).toFixed(1) + ' M';
  if (Math.abs(num) >= 1_000_000) return (num / 1_000_000).toFixed(0) + ' Jt';
  return num.toLocaleString('id-ID');
};

const chartData = computed(() => {
  if (props.isSingleDepartment) {
    return {
      labels: ['Anggaran Jurusan'],
      datasets: [
        {
          label: 'Realisasi Final',
          data: [props.singleRealized],
          backgroundColor: '#059669',
          borderRadius: 6,
        },
        {
          label: 'Komitmen (Reserved)',
          data: [props.singleReserved],
          backgroundColor: '#6366F1',
          borderRadius: 6,
        },
        {
          label: 'Saldo Bebas (Available)',
          data: [props.singleAvailable],
          backgroundColor: '#0EA5E9',
          borderRadius: 6,
        }
      ]
    };
  }

  const labels = props.departmentSummaries.map(d => d.code || d.name);
  const realized = props.departmentSummaries.map(d => d.realized);
  const reserved = props.departmentSummaries.map(d => d.reserved);
  const available = props.departmentSummaries.map(d => d.available);

  return {
    labels,
    datasets: [
      {
        label: 'Realisasi Final',
        data: realized,
        backgroundColor: '#059669',
        borderRadius: 4,
      },
      {
        label: 'Komitmen (Reserved)',
        data: reserved,
        backgroundColor: '#6366F1',
        borderRadius: 4,
      },
      {
        label: 'Saldo Bebas (Available)',
        data: available,
        backgroundColor: '#0EA5E9',
        borderRadius: 4,
      }
    ]
  };
});

const chartOptions = computed(() => ({
  indexAxis: 'y', // Horizontal stacked bar
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top',
      align: 'end',
      labels: {
        usePointStyle: true,
        font: { family: 'Poppins', size: 11, weight: '600' },
        boxWidth: 8,
      }
    },
    tooltip: {
      backgroundColor: '#0F172A',
      titleFont: { family: 'Poppins', size: 12, weight: '700' },
      bodyFont: { family: 'Poppins', size: 11 },
      padding: 10,
      cornerRadius: 10,
      callbacks: {
        label: (context) => {
          const val = context.raw;
          return ` ${context.dataset.label}: Rp ${val.toLocaleString('id-ID')}`;
        }
      }
    }
  },
  scales: {
    x: {
      stacked: true,
      border: { dash: [4, 4] },
      ticks: {
        font: { family: 'Poppins', size: 10 },
        callback: (val) => `Rp ${formatCompactRupiah(val)}`
      }
    },
    y: {
      stacked: true,
      grid: { display: false },
      ticks: { font: { family: 'Poppins', size: 11, weight: '700' } }
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

    <div :class="[isSingleDepartment ? 'h-32' : 'h-64 sm:h-72', 'w-full relative']">
      <Bar :data="chartData" :options="chartOptions" />
    </div>
  </div>
</template>
