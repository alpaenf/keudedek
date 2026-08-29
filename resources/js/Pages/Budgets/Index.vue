<script setup>
import { ref } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { Search, Eye, Plus } from '@lucide/vue';

const props = defineProps({
  buckets: Object,
  departments: Array,
  filters: Object,
});

const page = usePage();
const currentUser = page.props.auth?.user;
const canCreate = ['ADMIN', 'KABAG'].includes(currentUser?.role);

const search = ref(props.filters?.search || '');
const departmentId = ref(props.filters?.department_id || '');

const handleFilter = () => {
  router.get('/budgets', {
    search: search.value,
    department_id: departmentId.value,
  }, { preserveState: true });
};

const formatRupiah = (val) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0);
};
</script>

<template>
  <AppLayout title="Daftar Pos Pagu Anggaran">
    <!-- Header & Filter Bar -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm mb-6 flex flex-wrap items-center justify-between gap-4">
      <div>
        <h3 class="font-bold text-slate-900 text-base">Budget Buckets Overview</h3>
        <p class="text-xs text-slate-500">Daftar alokasi pagu, komitmen reservasi, dan ketersediaan saldo per mata anggaran</p>
      </div>

      <div class="flex flex-wrap items-center gap-3">
        <div class="relative">
          <input 
            v-model="search" 
            @keyup.enter="handleFilter" 
            type="text" 
            placeholder="Cari Kode / Nama Akun..." 
            class="pl-9 pr-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 w-56" 
          />
          <Search class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" />
        </div>
        
        <select v-model="departmentId" @change="handleFilter" class="px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-semibold text-slate-900">
          <option value="">Semua Jurusan</option>
          <option v-for="dept in departments" :key="dept.id" :value="dept.id">
            {{ dept.code }} - {{ dept.name }}
          </option>
        </select>
        
        <button @click="handleFilter" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-semibold transition whitespace-nowrap">
          Filter
        </button>

        <Link 
          v-if="canCreate" 
          href="/budgets/create" 
          class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-md shadow-sky-600/20 whitespace-nowrap"
        >
          <Plus class="w-4 h-4" /> Tambah Pagu Baru
        </Link>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-200">
              <th class="py-3.5 px-6 whitespace-nowrap">Mata Anggaran (Akun)</th>
              <th class="py-3.5 px-6 whitespace-nowrap">Jurusan</th>
              <th class="py-3.5 px-6 whitespace-nowrap">Sumber Dana</th>
              <th class="py-3.5 px-6 text-right whitespace-nowrap">Pagu Aktif</th>
              <th class="py-3.5 px-6 text-right whitespace-nowrap">Reserved</th>
              <th class="py-3.5 px-6 text-right whitespace-nowrap">Realisasi</th>
              <th class="py-3.5 px-6 text-right whitespace-nowrap">Saldo Tersedia</th>
              <th class="py-3.5 px-6 text-center whitespace-nowrap">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-slate-900">
            <tr v-for="bucket in buckets.data" :key="bucket.id" class="hover:bg-slate-50/80 transition">
              <td class="py-4 px-6">
                <span class="font-mono font-bold text-sky-700 block whitespace-nowrap">{{ bucket.account_code }}</span>
                <span class="font-medium text-slate-900">{{ bucket.account_name }}</span>
              </td>
              <td class="py-4 px-6 font-semibold text-slate-900 whitespace-nowrap">{{ bucket.department?.code ?? 'FT' }}</td>
              <td class="py-4 px-6 whitespace-nowrap">
                <span class="px-2 py-0.5 bg-slate-100 border border-slate-200 rounded text-[11px] font-mono text-slate-700">
                  {{ bucket.funding_source?.code ?? bucket.fundingSource?.code ?? 'BOPTN' }}
                </span>
              </td>
              <td class="py-4 px-6 text-right font-medium text-slate-900 whitespace-nowrap">{{ formatRupiah(bucket.allocated_budget) }}</td>
              <td class="py-4 px-6 text-right font-medium text-slate-900 whitespace-nowrap">{{ formatRupiah(bucket.reserved_budget) }}</td>
              <td class="py-4 px-6 text-right font-medium text-slate-900 whitespace-nowrap">{{ formatRupiah(bucket.realized_budget) }}</td>
              <td class="py-4 px-6 text-right font-bold text-sky-700 whitespace-nowrap">{{ formatRupiah(bucket.available_balance) }}</td>
              <td class="py-4 px-6 text-center whitespace-nowrap">
                <Link :href="`/budgets/${bucket.id}`" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-sky-50 text-sky-700 hover:bg-sky-100 border border-sky-200 rounded-lg font-semibold text-xs transition whitespace-nowrap shadow-sm">
                  <Eye class="w-3.5 h-3.5" /> Detail
                </Link>
              </td>
            </tr>
            <tr v-if="buckets.data.length === 0">
              <td colspan="8" class="py-8 text-center text-slate-500">Tidak ada data pagu anggaran yang ditemukan.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="buckets.links && buckets.links.length > 3" class="p-4 border-t border-slate-100 flex items-center justify-between">
        <span class="text-xs text-slate-500">
          Menampilkan {{ buckets.from ?? 0 }} - {{ buckets.to ?? 0 }} dari {{ buckets.total }} total data
        </span>
        <div class="flex items-center gap-1">
          <Link
            v-for="(link, i) in buckets.links"
            :key="i"
            :href="link.url || '#'"
            v-html="link.label"
            :class="[
              'px-3 py-1.5 rounded-lg text-xs font-semibold transition',
              link.active ? 'bg-sky-600 text-white' : 'bg-slate-50 text-slate-700 hover:bg-slate-100 border border-slate-200',
              !link.url ? 'opacity-40 cursor-not-allowed' : ''
            ]"
          />
        </div>
      </div>
    </div>
  </AppLayout>
</template>
