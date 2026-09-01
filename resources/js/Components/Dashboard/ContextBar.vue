<script setup>
defineProps({
  activeFiscalYear: [String, Number],
  revisionNumber: { type: String, default: 'Rev 02' },
  fundSourceCode: { type: String, default: 'RM' },
  periodLabel: { type: String, default: 'Jan–Agu 2026' },
  scopeLabel: { type: String, default: 'FT-UNSOED' },
});
</script>

<template>
  <div class="bg-slate-900 text-slate-200 px-4 py-2.5 rounded-2xl border border-slate-800 text-xs font-sans shadow-sm flex flex-wrap items-center justify-between gap-3 mb-6">
    <div class="flex flex-wrap items-center gap-2 sm:gap-4">
      <div class="flex items-center gap-1.5 font-bold text-slate-100">
        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
        <span>TA {{ activeFiscalYear || 2026 }}</span>
      </div>
      <span class="text-slate-700 hidden sm:inline">&bull;</span>
      <span class="px-2 py-0.5 bg-slate-800 border border-slate-700 text-slate-300 rounded font-semibold text-[11px]">{{ revisionNumber }}</span>
      <span class="text-slate-700 hidden sm:inline">&bull;</span>
      <span class="px-2 py-0.5 bg-slate-800 border border-slate-700 text-sky-400 rounded font-bold text-[11px]">{{ fundSourceCode }}</span>
      <span class="text-slate-700 hidden sm:inline">&bull;</span>
      <span class="text-slate-400 font-medium">{{ periodLabel }}</span>
    </div>

    <div class="flex items-center gap-2 bg-slate-800/80 px-3 py-1 rounded-xl border border-slate-700/60">
      <span class="text-slate-400 text-[11px] uppercase tracking-wider font-semibold">Scope:</span>
      <span class="font-extrabold text-sky-300 font-sans uppercase">{{ scopeLabel }}</span>
    </div>
  </div>
</template>
