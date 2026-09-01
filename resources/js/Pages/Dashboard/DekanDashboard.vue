<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import LineTrendChart from '../../Components/Dashboard/LineTrendChart.vue';
import StackedBarChart from '../../Components/Dashboard/StackedBarChart.vue';

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

const pendingAuthorizationCount = computed(() => {
  return props.verificationQueue ? props.verificationQueue.length : 0;
});
</script>

<template>
  <div class="space-y-6">
    <!-- Clean Section Header -->
    <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-slate-200/80">
      <div>
        <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Ringkasan Eksekutif Keuangan Dekan (KPA)</h2>
        <p class="text-xs text-slate-500">Ikhtisar strategis kinerja serapan anggaran, likuiditas 5 jurusan, dan pengawasan pimpinan.</p>
      </div>

      <div class="flex flex-wrap items-center gap-3">
        <a 
          href="/reports/export-pdf" 
          class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition flex items-center shadow-sm"
        >
          Unduh Laporan Eksekutif (PDF)
        </a>
      </div>
    </div>

    <!-- 5 Clean Executive KPI Cards -->
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
        <div class="text-xs text-rose-700 font-semibold">Butuh Atensi KPA</div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-1">
        <div class="text-[11px] font-bold text-blue-800 uppercase tracking-wider">PENDING AUTHORIZATION</div>
        <div class="text-xl font-extrabold text-blue-950 font-sans tracking-tight">
          {{ pendingAuthorizationCount }}
        </div>
        <div class="text-xs text-blue-700 font-medium">Antrean Persetujuan</div>
      </div>
    </div>

    <!-- 2 Executive Charts + Critical Attention Side Panel -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- 2/3 Charts Area -->
      <div class="lg:col-span-2 space-y-6">
        <LineTrendChart 
          title="Tren Realisasi Fakultas (Jan–Des 2026)"
          description="Pergerakan kumulatif pencairan belanja LRA Fakultas Teknik"
          :labels="monthlyTrend?.labels || []"
          :realizedData="monthlyTrend?.realized || []"
          :reservedData="monthlyTrend?.reserved || []"
        />

        <StackedBarChart 
          title="Kondisi Anggaran Lima Jurusan"
          description="Proporsi Realisasi Final, Reserved, dan Saldo Bebas (Available) per Jurusan"
          :departmentSummaries="departmentSummaries || []"
        />
      </div>

      <!-- 1/3 Executive Critical Attention Panel -->
      <div class="space-y-6">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
          <div class="border-b border-slate-100 pb-3">
            <h3 class="text-sm font-bold text-slate-900">Critical Attention KPA</h3>
            <p class="text-xs text-slate-500">Isu strategis yang membutuhkan keputusan Dekan</p>
          </div>

          <div class="space-y-3">
            <div class="p-3.5 bg-rose-50 border border-rose-200 rounded-xl space-y-1">
              <div class="flex items-center justify-between text-xs font-bold text-rose-900">
                <span>Critical Warning EWS</span>
                <span class="px-2 py-0.5 bg-rose-600 text-white rounded-full text-[10px] font-black">{{ criticalWarningsCount || 0 }}</span>
              </div>
              <p class="text-[11px] text-rose-800 leading-relaxed">
                Peringatan dini dengan dampak risiko tinggi terhadap serapan atau batas pagu.
              </p>
            </div>

            <div class="p-3.5 bg-blue-50 border border-blue-200 rounded-xl space-y-1">
              <div class="flex items-center justify-between text-xs font-bold text-blue-900">
                <span>Pending Otorisasi</span>
                <span class="px-2 py-0.5 bg-blue-600 text-white rounded-full text-[10px] font-black">{{ pendingAuthorizationCount }}</span>
              </div>
              <p class="text-[11px] text-blue-800 leading-relaxed">
                Pengajuan belanja komitmen tinggi yang membutuhkan penandatanganan pimpinan.
              </p>
            </div>

            <div class="p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl space-y-1">
              <div class="flex items-center justify-between text-xs font-bold text-emerald-900">
                <span>Laporan Rekonsiliasi</span>
                <span class="text-[10px] font-bold text-emerald-700">OK / Valid</span>
              </div>
              <p class="text-[11px] text-emerald-800 leading-relaxed">
                Keseimbangan saldo alokasi pagu 5 jurusan berada dalam status seimbang.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
