<script setup>
import { computed } from 'vue';
import { Line } from 'vue-chartjs';
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  LineElement,
  LinearScale,
  CategoryScale,
  PointElement,
  Filler
} from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, LineElement, LinearScale, CategoryScale, PointElement, Filler);

const props = defineProps({
  title: { type: String, default: 'Tren Realisasi Kumulatif' },
  description: { type: String, default: 'Perkembangan realisasi keuangan dari bulan ke bulan' },
  labels: { type: Array, default: () => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'] },
  realizedData: { type: Array, default: () => [] },
  reservedData: { type: Array, default: () => [] },
  serapanPctData: { type: Array, default: () => [] },
  mode: { type: String, default: 'amount' }, // 'amount' or 'percentage'
});

const formatCompactRupiah = (val) => {
  const num = Number(val) || 0;
  if (Math.abs(num) >= 1_000_000_000_000) return (num / 1_000_000_000_000).toFixed(1) + ' T';
  if (Math.abs(num) >= 1_000_000_000) return (num / 1_000_000_000).toFixed(1) + ' M';
  if (Math.abs(num) >= 1_000_000) return (num / 1_000_000).toFixed(0) + ' Jt';
  return num.toLocaleString('id-ID');
};

const chartData = computed(() => {
  if (props.mode === 'percentage') {
    return {
      labels: props.labels,
      datasets: [
        {
          label: 'Serapan Realisasi (%)',
          data: props.serapanPctData,
          borderColor: '#10B981',
          backgroundColor: 'rgba(16, 185, 129, 0.1)',
          fill: true,
          tension: 0.3,
          pointRadius: 4,
          pointBackgroundColor: '#10B981',
        }
      ]
    };
  }

  const datasets = [
    {
      label: 'Realisasi Final (LRA)',
      data: props.realizedData,
      borderColor: '#059669',
      backgroundColor: 'rgba(5, 150, 105, 0.1)',
      fill: true,
      tension: 0.3,
      pointRadius: 3.5,
      pointBackgroundColor: '#059669',
    }
  ];

  if (props.reservedData && props.reservedData.length > 0) {
    datasets.push({
      label: 'Komitmen (Reserved)',
      data: props.reservedData,
      borderColor: '#6366F1',
      backgroundColor: 'rgba(99, 102, 241, 0.05)',
      fill: false,
      borderDash: [4, 4],
      tension: 0.3,
      pointRadius: 3,
      pointBackgroundColor: '#6366F1',
    });
  }

  return {
    labels: props.labels,
    datasets,
  };
});

const chartOptions = computed(() => ({
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
          if (props.mode === 'percentage') {
            return ` ${context.dataset.label}: ${val}%`;
          }
          return ` ${context.dataset.label}: Rp ${val.toLocaleString('id-ID')}`;
        }
      }
    }
  },
  scales: {
    x: {
      grid: { display: false },
      ticks: { font: { family: 'Poppins', size: 10 } }
    },
    y: {
      border: { dash: [4, 4] },
      ticks: {
        font: { family: 'Poppins', size: 10 },
        callback: (value) => props.mode === 'percentage' ? `${value}%` : `Rp ${formatCompactRupiah(value)}`
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
      <Line :data="chartData" :options="chartOptions" />
    </div>
  </div>
</template>
