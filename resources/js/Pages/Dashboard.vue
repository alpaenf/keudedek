<script setup>
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';
import { 
  DollarSign, 
  Lock, 
  CheckCircle, 
  Scale, 
  Filter, 
  AlertTriangle,
  BarChart3,
  FileText,
  Eye
} from '@lucide/vue';

const props = defineProps({
  totalAllocated: Number,
  totalReserved: Number,
  totalRealized: Number,
  totalAvailable: Number,
  absorptionRate: Number,
  activeWarningsCount: Number,
  recentSubmissions: Array,
  activeWarnings: Array,
  departments: Array,
  selectedDepartmentId: [String, Number],
  departmentSummaries: Array,
});

const filterDepartment = (e) => {
  const deptId = e.target.value;
  router.get('/', deptId ? { department_id: deptId } : {});
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

const getPercent = (amount) => {
  return props.totalAllocated > 0 ? ((amount / props.totalAllocated) * 100).toFixed(1) : 0;
};

const getBadgeClass = (st) => {
  switch(st) {
    case 'REJECTED': return 'bg-rose-100 text-rose-700 border-rose-300';
    case 'RETURNED': return 'bg-rose-50 text-rose-600 border-rose-200';
    case 'COMPLETED': return 'bg-sky-100 text-sky-800 border-sky-300';
    case 'APPROVED': return 'bg-sky-50 text-sky-700 border-sky-300';
    default: return 'bg-slate-100 text-slate-800 border-slate-300';
  }
};
</script>

<template>
  <AppLayout title="Dashboard Overview Keuangan">
    <!-- Filter Bar -->
    <div class="mb-8 bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-wrap items-center justify-between gap-4">
      <div class="flex items-center gap-2">
        <Filter class="w-5 h-5 text-sky-600" />
        <span class="text-sm font-semibold text-slate-900">Filter Unit / Jurusan:</span>
      </div>

      <div class="flex items-center gap-3">
        <select :value="selectedDepartmentId || ''" @change="filterDepartment" class="px-4 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500">
          <option value="">-- Seluruh Fakultas Teknik --</option>
          <option v-for="dept in departments" :key="dept.id" :value="dept.id">
            {{ dept.code }} - {{ dept.name }}
          </option>
        </select>

        <Link v-if="selectedDepartmentId" href="/" class="px-3 py-2 bg-slate-200 hover:bg-slate-300 text-slate-900 rounded-lg text-xs font-medium transition">
          Reset Filter
        </Link>
      </div>
    </div>

    <!-- KPI Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <!-- Pagu Aktif -->
      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden">
        <div class="flex items-center justify-between mb-3">
          <span class="text-xs font-semibold text-slate-700 uppercase tracking-wider">Pagu Aktif</span>
          <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
            <DollarSign class="w-6 h-6" />
          </div>
        </div>
        <div class="text-xl xl:text-2xl font-bold text-sky-700 mb-1 tracking-tight truncate" :title="formatRupiah(totalAllocated)">
          {{ formatRupiahCompact(totalAllocated) }}
        </div>
        <p class="text-xs text-slate-500 truncate" :title="formatRupiah(totalAllocated)">Detail: {{ formatRupiah(totalAllocated) }}</p>
      </div>

      <!-- Komitmen (Reserved) -->
      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden">
        <div class="flex items-center justify-between mb-3">
          <span class="text-xs font-semibold text-slate-700 uppercase tracking-wider">Komitmen (Reserved)</span>
          <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center shrink-0">
            <Lock class="w-6 h-6" />
          </div>
        </div>
        <div class="text-xl xl:text-2xl font-bold text-slate-900 mb-1 tracking-tight truncate" :title="formatRupiah(totalReserved)">
          {{ formatRupiahCompact(totalReserved) }}
        </div>
        <p class="text-xs text-slate-500 truncate" :title="formatRupiah(totalReserved)">Detail: {{ formatRupiah(totalReserved) }}</p>
      </div>

      <!-- Realisasi -->
      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden">
        <div class="flex items-center justify-between mb-3">
          <span class="text-xs font-semibold text-slate-700 uppercase tracking-wider">Realisasi</span>
          <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center shrink-0">
            <CheckCircle class="w-6 h-6" />
          </div>
        </div>
        <div class="text-xl xl:text-2xl font-bold text-slate-900 mb-1 tracking-tight truncate" :title="formatRupiah(totalRealized)">
          {{ formatRupiahCompact(totalRealized) }}
        </div>
        <p class="text-xs text-slate-500 truncate" :title="formatRupiah(totalRealized)">Detail: {{ formatRupiah(totalRealized) }}</p>
      </div>

      <!-- Saldo Tersedia -->
      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm relative overflow-hidden">
        <div class="flex items-center justify-between mb-3">
          <span class="text-xs font-semibold text-slate-700 uppercase tracking-wider">Saldo Tersedia</span>
          <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
            <Scale class="w-6 h-6" />
          </div>
        </div>
        <div class="text-xl xl:text-2xl font-bold text-sky-700 mb-1 tracking-tight truncate" :title="formatRupiah(totalAvailable)">
          {{ formatRupiahCompact(totalAvailable) }}
        </div>
        <p class="text-xs text-slate-500 truncate" :title="formatRupiah(totalAvailable)">Detail: {{ formatRupiah(totalAvailable) }}</p>
      </div>
    </div>

    <!-- Serapan Bar Chart & Warning Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
      <!-- Modern Vertical Bar Chart Component -->
      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm lg:col-span-2 flex flex-col justify-between">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h3 class="font-bold text-slate-900 text-base flex items-center gap-2">
              <BarChart3 class="w-5 h-5 text-sky-600" />
              Grafik Penyerapan & Distibusi Anggaran Total
            </h3>
            <p class="text-xs text-slate-500">Perbandingan Visual Komponen Realisasi, Komitmen, dan Saldo Bebas</p>
          </div>
          <div class="text-right">
            <span class="text-xl font-black text-sky-700 block leading-tight">{{ absorptionRate.toFixed(1) }}%</span>
            <span class="text-[10px] text-slate-400 font-semibold uppercase">Total Serapan</span>
          </div>
        </div>

        <!-- Vertical Bar Chart Visualization -->
        <div class="bg-slate-50/70 p-6 rounded-2xl border border-slate-200/80 mb-6">
          <div class="h-44 flex items-end justify-around gap-6 pt-6 border-b border-slate-200">
            <!-- Bar 1: Realisasi -->
            <div class="flex-1 flex flex-col items-center h-full justify-end group">
              <div class="text-[11px] font-bold text-slate-700 mb-1 opacity-0 group-hover:opacity-100 transition">
                {{ getPercent(totalRealized) }}%
              </div>
              <div 
                class="w-full max-w-[64px] bg-slate-900 rounded-t-xl transition-all duration-500 relative flex items-start justify-center pt-2 shadow-sm"
                :style="{ height: Math.max(12, getPercent(totalRealized)) + '%' }"
              >
                <span class="text-[10px] font-bold text-white tracking-wider">{{ getPercent(totalRealized) }}%</span>
              </div>
            </div>

            <!-- Bar 2: Komitmen (Reserved) -->
            <div class="flex-1 flex flex-col items-center h-full justify-end group">
              <div class="text-[11px] font-bold text-slate-700 mb-1 opacity-0 group-hover:opacity-100 transition">
                {{ getPercent(totalReserved) }}%
              </div>
              <div 
                class="w-full max-w-[64px] bg-sky-600 rounded-t-xl transition-all duration-500 relative flex items-start justify-center pt-2 shadow-sm"
                :style="{ height: Math.max(12, getPercent(totalReserved)) + '%' }"
              >
                <span class="text-[10px] font-bold text-white tracking-wider">{{ getPercent(totalReserved) }}%</span>
              </div>
            </div>

            <!-- Bar 3: Saldo Bebas (Available) -->
            <div class="flex-1 flex flex-col items-center h-full justify-end group">
              <div class="text-[11px] font-bold text-slate-700 mb-1 opacity-0 group-hover:opacity-100 transition">
                {{ getPercent(totalAvailable) }}%
              </div>
              <div 
                class="w-full max-w-[64px] bg-sky-200 border border-sky-300 rounded-t-xl transition-all duration-500 relative flex items-start justify-center pt-2 shadow-sm"
                :style="{ height: Math.max(12, getPercent(totalAvailable)) + '%' }"
              >
                <span class="text-[10px] font-bold text-sky-900 tracking-wider">{{ getPercent(totalAvailable) }}%</span>
              </div>
            </div>
          </div>

          <!-- X-Axis Labels -->
          <div class="flex justify-around text-center text-xs font-semibold pt-3 text-slate-700">
            <div class="flex-1">
              <span class="block font-bold text-slate-900">Realisasi</span>
              <span class="text-[11px] font-medium text-slate-500" :title="formatRupiah(totalRealized)">{{ formatRupiahCompact(totalRealized) }}</span>
            </div>
            <div class="flex-1">
              <span class="block font-bold text-sky-700">Komitmen (Reserved)</span>
              <span class="text-[11px] font-medium text-slate-500" :title="formatRupiah(totalReserved)">{{ formatRupiahCompact(totalReserved) }}</span>
            </div>
            <div class="flex-1">
              <span class="block font-bold text-slate-900">Saldo Bebas</span>
              <span class="text-[11px] font-medium text-slate-500" :title="formatRupiah(totalAvailable)">{{ formatRupiahCompact(totalAvailable) }}</span>
            </div>
          </div>
        </div>

        <!-- Legend Footer -->
        <div class="flex items-center justify-center gap-6 text-xs text-slate-600 border-t border-slate-100 pt-3">
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-sm bg-slate-900"></div>
            <span>Realisasi Dicairkan</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-sm bg-sky-600"></div>
            <span>Komitmen Locked</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-sm bg-sky-200 border border-sky-300"></div>
            <span>Sisa Saldo Bebas</span>
          </div>
        </div>
      </div>

      <!-- Active EWS Alert Card -->
      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-slate-900 text-base flex items-center gap-2">
              <AlertTriangle class="w-5 h-5 text-rose-600" />
              Peringatan Dini (EWS)
            </h3>
            <span class="px-2.5 py-1 bg-rose-100 text-rose-700 border border-rose-200 rounded-full text-xs font-bold">{{ activeWarningsCount }} Aktif</span>
          </div>
          <p class="text-xs text-slate-500 mb-4">Indikator deteksi dini batas ketersediaan saldo di bawah threshold.</p>
        </div>

        <div>
          <div v-if="activeWarnings.length > 0" class="space-y-2 mb-4">
            <div v-for="warn in activeWarnings.slice(0, 2)" :key="warn.id" class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs">
              <div class="flex items-center justify-between font-bold text-rose-900 mb-1">
                <span>{{ warn.rule_code }} - {{ warn.department?.code ?? 'FT' }}</span>
                <span :class="['uppercase text-[10px] px-2 py-0.5 rounded font-extrabold text-white', warn.severity === 'CRITICAL' ? 'bg-rose-600' : 'bg-rose-500']">{{ warn.severity }}</span>
              </div>
              <p class="text-rose-900 text-[11px] line-clamp-2">{{ warn.message }}</p>
            </div>
          </div>
          <div v-else class="p-4 bg-sky-50 border border-sky-200 text-sky-900 rounded-xl text-xs text-center font-medium mb-4">
            Seluruh pos anggaran dalam batas aman.
          </div>

          <Link href="/warnings" class="w-full py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-semibold text-center block transition">
            Lihat Seluruh Early Warning
          </Link>
        </div>
      </div>
    </div>

    <!-- Recent Submissions Section (Directly linked to Submissions created by PTK/Users) -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-8">
      <div class="p-6 border-b border-slate-200 flex items-center justify-between">
        <div>
          <h3 class="font-bold text-slate-900 text-base flex items-center gap-2">
            <FileText class="w-5 h-5 text-sky-600" />
            Pengajuan Anggaran Terbaru
          </h3>
          <p class="text-xs text-slate-500">Daftar kegiatan & pengajuan terbaru yang baru diinput oleh PTK / Unit</p>
        </div>
        <Link href="/submissions" class="px-3 py-1.5 bg-sky-50 text-sky-700 hover:bg-sky-100 border border-sky-200 rounded-lg text-xs font-semibold transition">
          Lihat Semua Pengajuan
        </Link>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-200">
              <th class="py-3.5 px-6 whitespace-nowrap">No. Pengajuan</th>
              <th class="py-3.5 px-6 whitespace-nowrap">Judul Kegiatan</th>
              <th class="py-3.5 px-6 whitespace-nowrap">Pembuat / Unit</th>
              <th class="py-3.5 px-6 text-right whitespace-nowrap">Nominal</th>
              <th class="py-3.5 px-6 text-center whitespace-nowrap">Status Alur</th>
              <th class="py-3.5 px-6 text-center whitespace-nowrap">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-slate-900">
            <tr v-for="sub in recentSubmissions" :key="sub.id" class="hover:bg-slate-50/80 transition">
              <td class="py-4 px-6 font-mono font-bold text-sky-700 whitespace-nowrap">{{ sub.submission_number }}</td>
              <td class="py-4 px-6 font-medium text-slate-900">{{ sub.title }}</td>
              <td class="py-4 px-6 font-medium text-slate-800 whitespace-nowrap">
                {{ sub.creator?.name }} ({{ sub.department?.code }})
              </td>
              <td class="py-4 px-6 text-right font-bold text-slate-900 whitespace-nowrap">{{ formatRupiah(sub.amount) }}</td>
              <td class="py-4 px-6 text-center whitespace-nowrap">
                <span :class="['px-2.5 py-1 rounded-full text-[10px] font-extrabold border inline-block', getBadgeClass(sub.status)]">{{ sub.status }}</span>
              </td>
              <td class="py-4 px-6 text-center whitespace-nowrap">
                <Link :href="`/submissions/${sub.id}`" class="inline-flex items-center gap-1 px-2.5 py-1 bg-sky-50 text-sky-700 hover:bg-sky-100 border border-sky-200 rounded-lg text-xs font-semibold transition">
                  <Eye class="w-3.5 h-3.5" /> Detail
                </Link>
              </td>
            </tr>
            <tr v-if="recentSubmissions.length === 0">
              <td colspan="6" class="py-8 text-center text-slate-500">Belum ada pengajuan anggaran terbaru.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Department Comparison Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-8">
      <div class="p-6 border-b border-slate-200 flex items-center justify-between">
        <div>
          <h3 class="font-bold text-slate-900 text-base">Rekapitulasi Anggaran Antar Jurusan</h3>
          <p class="text-xs text-slate-500">Perbandingan Pagu, Komitmen, Realisasi, dan Saldo per Unit</p>
        </div>
        <Link href="/reports" class="px-3 py-1.5 bg-sky-50 text-sky-700 hover:bg-sky-100 border border-sky-200 rounded-lg text-xs font-semibold transition">
          Lihat Laporan Lengkap
        </Link>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-200">
              <th class="py-3.5 px-6 whitespace-nowrap">Jurusan / Unit</th>
              <th class="py-3.5 px-6 text-right whitespace-nowrap">Pagu Aktif</th>
              <th class="py-3.5 px-6 text-right whitespace-nowrap">Reserved (Komitmen)</th>
              <th class="py-3.5 px-6 text-right whitespace-nowrap">Realisasi</th>
              <th class="py-3.5 px-6 text-right whitespace-nowrap">Saldo Tersedia</th>
              <th class="py-3.5 px-6 text-center whitespace-nowrap">Status Saldo</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-slate-900">
            <tr v-for="dept in departmentSummaries" :key="dept.id" class="hover:bg-slate-50/80 transition">
              <td class="py-4 px-6 font-semibold text-slate-900 whitespace-nowrap">{{ dept.name }} ({{ dept.code }})</td>
              <td class="py-4 px-6 text-right font-medium text-sky-700 whitespace-nowrap">{{ formatRupiah(dept.budget_buckets.reduce((acc, b) => acc + parseFloat(b.allocated_budget), 0)) }}</td>
              <td class="py-4 px-6 text-right font-medium text-slate-900 whitespace-nowrap">{{ formatRupiah(dept.budget_buckets.reduce((acc, b) => acc + parseFloat(b.reserved_budget), 0)) }}</td>
              <td class="py-4 px-6 text-right font-medium text-slate-900 whitespace-nowrap">{{ formatRupiah(dept.budget_buckets.reduce((acc, b) => acc + parseFloat(b.realized_budget), 0)) }}</td>
              <td class="py-4 px-6 text-right font-bold text-sky-700 whitespace-nowrap">{{ formatRupiah(dept.budget_buckets.reduce((acc, b) => acc + parseFloat(b.available_balance), 0)) }}</td>
              <td class="py-4 px-6 text-center whitespace-nowrap">
                <span class="px-2.5 py-1 bg-sky-50 text-sky-800 border border-sky-200 rounded-full font-bold text-[10px]">Aman</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>
