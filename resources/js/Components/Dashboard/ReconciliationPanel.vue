<script setup>
import { computed } from 'vue';

const props = defineProps({
  totalAllocated: { type: Number, default: 0 },
  totalReserved: { type: Number, default: 0 },
  totalRealized: { type: Number, default: 0 },
  totalAvailable: { type: Number, default: 0 },
});

const formatRupiah = (val) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(val || 0);
};

const isBalanced = computed(() => {
  const sum = props.totalReserved + props.totalRealized + props.totalAvailable;
  return Math.abs(props.totalAllocated - sum) < 1;
});
</script>

<template>
  <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
      <div>
        <h3 class="text-sm font-bold text-slate-900">Panel Rekonsiliasi Saldo Keuangan</h3>
        <p class="text-xs text-slate-500">Prinsip Akuntabilitas: Pagu Alokasi = Reserved + Realisasi Final + Saldo Bebas</p>
      </div>

      <span 
        :class="[
          'px-3 py-1 text-xs font-bold rounded-full border uppercase tracking-wider',
          isBalanced 
            ? 'bg-emerald-50 text-emerald-700 border-emerald-200' 
            : 'bg-rose-50 text-rose-700 border-rose-200'
        ]"
      >
        {{ isBalanced ? 'Balance / Seimbang' : 'Selisih / Unbalanced' }}
      </span>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-center font-sans">
      <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1">
        <span class="text-[11px] text-slate-500 font-bold uppercase block">Pagu Alokasi</span>
        <span class="text-sm sm:text-base font-black text-slate-900 truncate block" :title="formatRupiah(totalAllocated)">
          {{ formatRupiah(totalAllocated) }}
        </span>
      </div>

      <div class="p-3 bg-indigo-50/60 border border-indigo-200 rounded-xl space-y-1">
        <span class="text-[11px] text-indigo-700 font-bold uppercase block">Komitmen (Reserved)</span>
        <span class="text-sm sm:text-base font-black text-indigo-900 truncate block" :title="formatRupiah(totalReserved)">
          {{ formatRupiah(totalReserved) }}
        </span>
      </div>

      <div class="p-3 bg-emerald-50/60 border border-emerald-200 rounded-xl space-y-1">
        <span class="text-[11px] text-emerald-700 font-bold uppercase block">Realisasi Final (LRA)</span>
        <span class="text-sm sm:text-base font-black text-emerald-900 truncate block" :title="formatRupiah(totalRealized)">
          {{ formatRupiah(totalRealized) }}
        </span>
      </div>

      <div class="p-3 bg-sky-50/60 border border-sky-200 rounded-xl space-y-1">
        <span class="text-[11px] text-sky-800 font-bold uppercase block">Saldo Bebas (Available)</span>
        <span class="text-sm sm:text-base font-black text-sky-950 truncate block" :title="formatRupiah(totalAvailable)">
          {{ formatRupiah(totalAvailable) }}
        </span>
      </div>
    </div>
  </div>
</template>
