<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { Download, FileText, Printer } from '@lucide/vue';

const props = defineProps({
  buckets: Array,
  departments: Array,
  selectedDepartmentId: [String, Number],
  totalAllocated: Number,
  totalReserved: Number,
  totalRealized: Number,
  totalAvailable: Number,
});

const deptId = ref(props.selectedDepartmentId || '');

const filterDept = () => {
  router.get('/reports', deptId.value ? { department_id: deptId.value } : {});
};

const exportPdf = () => {
  const url = '/reports/export-pdf' + (deptId.value ? `?department_id=${deptId.value}` : '');
  window.open(url, '_blank');
};

const exportCsv = () => {
  const url = '/reports/export-csv' + (deptId.value ? `?department_id=${deptId.value}` : '');
  window.open(url, '_blank');
};

const printReport = () => {
  window.print();
};

const formatRupiah = (val) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0);
};

const formatRupiahCompact = (val) => {
  const num = Number(val || 0);
  if (Math.abs(num) >= 1_000_000_000_000) {
    return 'Rp ' + (num / 1_000_000_000_000).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) + ' T';
  }
  if (Math.abs(num) >= 1_000_000_000) {
    return 'Rp ' + (num / 1_000_000_000).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) + ' M';
  }
  if (Math.abs(num) >= 100_000_000) {
    return 'Rp ' + (num / 1_000_000).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 1 }) + ' Jt';
  }
  return formatRupiah(num);
};

const getRate = (allocated, realized, reserved) => {
  return allocated > 0 ? (((realized + reserved) / allocated) * 100).toFixed(1) : 0;
};
</script>

<template>
  <AppLayout title="Laporan Realisasi & Serapan Anggaran">
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm mb-6 flex flex-wrap items-center justify-between gap-4">
      <div>
        <h3 class="font-bold text-slate-900 text-base">Rekapitulasi LRA Fakultas Teknik</h3>
        <p class="text-xs text-slate-500">Laporan realisasi anggaran komprehensif tingkat unit & akun</p>
      </div>

      <div class="flex flex-wrap items-center gap-3">
        <select v-model="deptId" @change="filterDept" class="px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-semibold text-slate-900">
          <option value="">Semua Jurusan / Unit</option>
          <option v-for="dept in departments" :key="dept.id" :value="dept.id">
            {{ dept.code }} - {{ dept.name }}
          </option>
        </select>
        <button @click="exportPdf" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-lg text-xs font-semibold flex items-center gap-2 transition shadow-sm">
          <FileText class="w-4 h-4" />
          Ekspor PDF
        </button>
        <button @click="exportCsv" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-semibold flex items-center gap-2 transition shadow-sm">
          <Download class="w-4 h-4" />
          Ekspor Excel / CSV
        </button>
        <button @click="printReport" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold flex items-center gap-2 transition border border-slate-300">
          <Printer class="w-4 h-4" />
          Cetak
        </button>
      </div>
    </div>

    <!-- Financial Totals Banner -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
      <div class="bg-white p-4 rounded-xl border border-slate-200">
        <span class="text-xs text-slate-500 block">Total Pagu Aktif</span>
        <span class="font-bold text-sky-700 text-base truncate block" :title="formatRupiah(totalAllocated)">{{ formatRupiahCompact(totalAllocated) }}</span>
      </div>
      <div class="bg-white p-4 rounded-xl border border-slate-200">
        <span class="text-xs text-slate-500 block">Total Komitmen (Reserved)</span>
        <span class="font-bold text-slate-900 text-base truncate block" :title="formatRupiah(totalReserved)">{{ formatRupiahCompact(totalReserved) }}</span>
      </div>
      <div class="bg-white p-4 rounded-xl border border-slate-200">
        <span class="text-xs text-slate-500 block">Total Realisasi</span>
        <span class="font-bold text-slate-900 text-base truncate block" :title="formatRupiah(totalRealized)">{{ formatRupiahCompact(totalRealized) }}</span>
      </div>
      <div class="bg-white p-4 rounded-xl border border-slate-200">
        <span class="text-xs text-slate-500 block">Total Saldo Tersedia</span>
        <span class="font-bold text-sky-700 text-base truncate block" :title="formatRupiah(totalAvailable)">{{ formatRupiahCompact(totalAvailable) }}</span>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-200">
              <th class="py-3.5 px-6 whitespace-nowrap">Kode & Nama Akun</th>
              <th class="py-3.5 px-6 whitespace-nowrap">Jurusan</th>
              <th class="py-3.5 px-6 text-right whitespace-nowrap">Pagu Aktif (Rp)</th>
              <th class="py-3.5 px-6 text-right whitespace-nowrap">Reserved (Rp)</th>
              <th class="py-3.5 px-6 text-right whitespace-nowrap">Realisasi (Rp)</th>
              <th class="py-3.5 px-6 text-right whitespace-nowrap">Available (Rp)</th>
              <th class="py-3.5 px-6 text-center whitespace-nowrap">% Serapan</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-slate-900">
            <tr v-for="bucket in buckets" :key="bucket.id" class="hover:bg-slate-50/80 transition">
              <td class="py-4 px-6">
                <span class="font-sans font-bold text-sky-700 block whitespace-nowrap">{{ bucket.account_code }}</span>
                <span class="font-medium text-slate-900">{{ bucket.account_name }}</span>
              </td>
              <td class="py-4 px-6 font-semibold text-slate-900 whitespace-nowrap">{{ bucket.department?.code }}</td>
              <td class="py-4 px-6 text-right font-medium text-slate-900 whitespace-nowrap">{{ formatRupiah(bucket.allocated_budget) }}</td>
              <td class="py-4 px-6 text-right font-medium text-slate-900 whitespace-nowrap">{{ formatRupiah(bucket.reserved_budget) }}</td>
              <td class="py-4 px-6 text-right font-medium text-slate-900 whitespace-nowrap">{{ formatRupiah(bucket.realized_budget) }}</td>
              <td class="py-4 px-6 text-right font-bold text-sky-700 whitespace-nowrap">{{ formatRupiah(bucket.available_balance) }}</td>
              <td class="py-4 px-6 text-center font-bold text-slate-900 whitespace-nowrap">{{ getRate(parseFloat(bucket.allocated_budget), parseFloat(bucket.realized_budget), parseFloat(bucket.reserved_budget)) }}%</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>
