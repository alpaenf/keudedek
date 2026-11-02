<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Filter, Calendar, Building2, Wallet, RefreshCw, ChevronDown, Layers } from 'lucide-vue-next';

const props = defineProps({
  departments: Array,
  fundingSources: Array,
  selectedDepartmentId: [String, Number],
  selectedFundingSourceId: [String, Number],
  activeFiscalYear: [String, Number],
  userRole: String,
  fundSourceCode: String,
  revisionNumber: String,
});

const selectedDept = ref(props.selectedDepartmentId || '');
const selectedYear = ref(props.activeFiscalYear || '2026');
const selectedFund = ref(props.selectedFundingSourceId || '');
const selectedRevision = ref(props.revisionNumber || 'Pagu Aktif');

watch(() => props.selectedFundingSourceId, (val) => {
  selectedFund.value = val || '';
});

watch(() => props.selectedDepartmentId, (val) => {
  selectedDept.value = val || '';
});

const currentFundLabel = computed(() => {
  if (!selectedFund.value) return 'Semua Sumber Dana';
  const found = props.fundingSources?.find(f => String(f.id) === String(selectedFund.value));
  if (!found) return 'Semua Sumber Dana';
  return found.code ? `${found.code} (${found.name})` : found.name;
});

const currentDeptLabel = computed(() => {
  if (!selectedDept.value) return 'Semua Jurusan';
  const found = props.departments?.find(d => String(d.id) === String(selectedDept.value));
  return found ? found.name : 'Semua Jurusan';
});

const applyFilter = () => {
  router.get('/dashboard', {
    department_id: selectedDept.value || undefined,
    funding_source_id: selectedFund.value || undefined,
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
    <div class="flex flex-wrap items-center gap-2 sm:gap-2.5 flex-1 min-w-0">
      <div class="flex items-center gap-1.5 text-slate-500 font-bold uppercase tracking-wider text-[10px] shrink-0 mr-1">
        <Filter class="w-3.5 h-3.5 text-sky-600" />
        <span>Filter:</span>
      </div>

      <!-- Fiscal Year Pill -->
      <div class="inline-flex items-center h-8 px-3 bg-slate-50 border border-slate-200 rounded-full font-semibold text-slate-700 text-xs shrink-0">
        <Calendar class="w-3.5 h-3.5 text-slate-400 shrink-0 mr-1.5" />
        <span>TA {{ selectedYear }}</span>
      </div>

      <!-- Revision Pill -->
      <div class="hidden md:inline-flex items-center h-8 px-3 bg-slate-50 border border-slate-200 rounded-full font-medium text-slate-600 text-xs shrink-0">
        <Layers class="w-3.5 h-3.5 text-slate-400 shrink-0 mr-1.5" />
        <span>{{ revisionNumber || 'Multi-Versi Aktif' }}</span>
      </div>

      <!-- Funding Source Selector Pill (Compact & Auto-fit) -->
      <div class="relative inline-flex items-center h-8 px-3 bg-slate-50 hover:bg-slate-100/70 border border-slate-200 rounded-full transition focus-within:ring-2 focus-within:ring-sky-500/20 focus-within:border-sky-500 shrink-0 cursor-pointer">
        <Wallet class="w-3.5 h-3.5 text-slate-400 shrink-0 pointer-events-none mr-1.5" />
        <span class="text-xs font-semibold text-slate-800 truncate max-w-[170px] pointer-events-none">
          {{ currentFundLabel }}
        </span>
        <ChevronDown class="w-3.5 h-3.5 text-slate-400 shrink-0 pointer-events-none ml-1.5" />
        <select 
          v-model="selectedFund" 
          @change="applyFilter"
          class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
        >
          <option value="">Semua Sumber Dana</option>
          <option v-for="fs in fundingSources" :key="fs.id" :value="fs.id">
            {{ fs.code }} &mdash; {{ fs.name }}
          </option>
        </select>
      </div>

      <!-- Scope Department Selector Pill (Compact & Auto-fit) -->
      <div 
        class="relative inline-flex items-center h-8 px-3 bg-slate-50 hover:bg-slate-100/70 border border-slate-200 rounded-full transition focus-within:ring-2 focus-within:ring-sky-500/20 focus-within:border-sky-500 shrink-0 cursor-pointer"
        :class="{ 'opacity-75 cursor-not-allowed': isScopeLocked }"
      >
        <Building2 class="w-3.5 h-3.5 text-slate-400 shrink-0 pointer-events-none mr-1.5" />
        <span class="text-xs font-semibold text-slate-800 truncate max-w-[170px] pointer-events-none">
          {{ currentDeptLabel }}
        </span>
        <ChevronDown v-if="!isScopeLocked" class="w-3.5 h-3.5 text-slate-400 shrink-0 pointer-events-none ml-1.5" />
        <select 
          v-if="!isScopeLocked"
          v-model="selectedDept" 
          @change="applyFilter"
          class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
        >
          <option value="">Semua Jurusan</option>
          <option v-for="d in departments" :key="d.id" :value="d.id">
            {{ d.name }}
          </option>
        </select>
      </div>
    </div>

    <!-- Right Reset & Status Badge Pill -->
    <div class="flex items-center gap-2 shrink-0">
      <span v-if="isScopeLocked" class="inline-flex items-center h-8 px-3 bg-amber-50 border border-amber-200 text-amber-800 text-[10px] font-bold rounded-full uppercase tracking-wider">
        Scope Terkunci
      </span>
      <span v-else class="inline-flex items-center h-8 px-3 bg-sky-50/80 border border-sky-200 text-sky-800 text-[10px] font-bold rounded-full uppercase tracking-wider">
        Scope Fakultas
      </span>
    </div>
  </div>
</template>
