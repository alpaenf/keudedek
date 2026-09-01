<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Filter, Calendar, Building2, Wallet, RefreshCw } from 'lucide-vue-next';

const props = defineProps({
  departments: Array,
  fundingSources: Array,
  selectedDepartmentId: [String, Number],
  activeFiscalYear: [String, Number],
  userRole: String,
});

const selectedDept = ref(props.selectedDepartmentId || '');
const selectedYear = ref(props.activeFiscalYear || '2026');
const selectedFund = ref('');
const selectedRevision = ref('Rev 00 (Pagu Induk)');
const selectedPeriod = ref('Jan - Des');

const applyFilter = () => {
  router.get('/dashboard', {
    department_id: selectedDept.value || undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
  });
};

const isScopeLocked = ['PTK', 'KAJUR'].includes(props.userRole);
</script>

<template>
  <div class="bg-white border border-slate-200/80 rounded-2xl p-3 sm:p-4 shadow-sm mb-6 flex flex-wrap items-center justify-between gap-3 text-xs">
    <!-- Left Filter Items -->
    <div class="flex flex-wrap items-center gap-2 sm:gap-3 flex-1 min-w-0">
      <div class="flex items-center gap-1.5 text-slate-500 font-bold uppercase tracking-wider text-[10px] shrink-0 mr-1">
        <Filter class="w-3.5 h-3.5 text-sky-600" />
        <span>Filter:</span>
      </div>

      <!-- Fiscal Year Pill -->
      <div class="flex items-center gap-1.5 px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-700">
        <Calendar class="w-3.5 h-3.5 text-slate-400 shrink-0" />
        <span>TA {{ selectedYear }}</span>
      </div>

      <!-- Revision Pill -->
      <div class="hidden md:flex items-center gap-1.5 px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl font-medium text-slate-600">
        <span>{{ selectedRevision }}</span>
      </div>

      <!-- Funding Source Pill -->
      <div class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl font-medium text-slate-600">
        <Wallet class="w-3.5 h-3.5 text-slate-400 shrink-0" />
        <span>Rupiah Murni (RM)</span>
      </div>

      <!-- Scope Department Selector -->
      <div class="flex items-center gap-1.5">
        <Building2 class="w-3.5 h-3.5 text-slate-400 shrink-0" />
        <select 
          v-model="selectedDept" 
          @change="applyFilter"
          :disabled="isScopeLocked"
          class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none transition disabled:opacity-75 disabled:cursor-not-allowed cursor-pointer"
        >
          <option v-if="!isScopeLocked" value="">Semua Jurusan (Fakultas Teknik)</option>
          <option v-for="d in departments" :key="d.id" :value="d.id">
            {{ d.name }}
          </option>
        </select>
      </div>
    </div>

    <!-- Right Reset & Status Badge -->
    <div class="flex items-center gap-2 shrink-0">
      <span v-if="isScopeLocked" class="px-2.5 py-1 bg-amber-50 border border-amber-200 text-amber-800 text-[10px] font-bold rounded-lg uppercase">
        Scope Terkunci
      </span>
      <span v-else class="px-2.5 py-1 bg-sky-50 border border-sky-200 text-sky-800 text-[10px] font-bold rounded-lg uppercase">
        Scope Fakultas
      </span>
    </div>
  </div>
</template>
