<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { Search, ShieldCheck, Activity, User, Calendar, Database } from '@lucide/vue';

const props = defineProps({
  logs: Object,
  filters: Object,
});

const search = ref(props.filters?.search || '');
const selectedAction = ref(props.filters?.action || '');

const handleFilter = () => {
  router.get('/audit-logs', { 
    search: search.value,
    action: selectedAction.value 
  }, { preserveState: true });
};

const resetFilter = () => {
  search.value = '';
  selectedAction.value = '';
  router.get('/audit-logs');
};

const formatCurrency = (val) => {
  const num = Number(val);
  if (!isNaN(num)) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(num);
  }
  return val;
};

const getReadablePayload = (val) => {
  if (!val) return [];
  const entries = typeof val === 'object' ? Object.entries(val) : [];
  return entries.map(([key, value]) => {
    let label = key;
    let formattedValue = value;

    if (key === 'status') {
      label = 'Status Baru';
    } else if (key === 'notes') {
      label = 'Catatan';
      formattedValue = value ? `"${value}"` : '-';
    } else if (key === 'amount') {
      label = 'Nominal';
      formattedValue = formatCurrency(value);
    } else if (key === 'reserved_budget') {
      label = 'Komitmen (Reserved)';
      formattedValue = formatCurrency(value);
    } else if (key === 'realized_budget') {
      label = 'Realisasi';
      formattedValue = formatCurrency(value);
    } else if (key === 'allocated_budget') {
      label = 'Pagu Aktif';
      formattedValue = formatCurrency(value);
    } else if (key === 'reason') {
      label = 'Alasan Revisi';
      formattedValue = value ? `"${value}"` : '-';
    }

    return { label, value: formattedValue, rawKey: key };
  });
};

const getActionBadgeClass = (action) => {
  switch (action) {
    case 'TRANSITION_SUBMISSION_STATUS':
      return 'bg-sky-50 text-sky-700 border-sky-200';
    case 'RESERVE_BUDGET':
      return 'bg-amber-50 text-amber-700 border-amber-200';
    case 'FINALIZE_REALIZATION':
      return 'bg-emerald-50 text-emerald-700 border-emerald-200';
    case 'APPLY_REVISION':
      return 'bg-indigo-50 text-indigo-700 border-indigo-200';
    case 'RELEASE_RESERVATION':
      return 'bg-rose-50 text-rose-700 border-rose-200';
    default:
      return 'bg-slate-100 text-slate-700 border-slate-200';
  }
};
</script>

<template>
  <AppLayout title="Audit Trail Log & Security">
    <!-- Header & Filter Bar -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm mb-6 flex flex-wrap items-center justify-between gap-4">
      <div>
        <h3 class="font-bold text-slate-900 text-base flex items-center gap-2">
          <ShieldCheck class="w-5 h-5 text-sky-600" />
          Security & Activity Audit Trail Logs
        </h3>
        <p class="text-xs text-slate-500 mt-0.5">Jejak rekaman transparan untuk seluruh event perubahan data, reservasi, dan mutasi anggaran</p>
      </div>

      <div class="flex flex-wrap items-center gap-3">
        <!-- Filter Action -->
        <select 
          v-model="selectedAction" 
          @change="handleFilter" 
          class="px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500"
        >
          <option value="">-- Seluruh Jenis Action --</option>
          <option value="TRANSITION_SUBMISSION_STATUS">Transisi Status Pengajuan</option>
          <option value="RESERVE_BUDGET">Reservasi Pagu (Lock)</option>
          <option value="FINALIZE_REALIZATION">Pencairan Realisasi</option>
          <option value="APPLY_REVISION">Revisi Pagu Anggaran</option>
          <option value="RELEASE_RESERVATION">Pelepasan Komitmen</option>
        </select>

        <!-- Search Box -->
        <div class="relative">
          <input 
            v-model="search" 
            @keyup.enter="handleFilter" 
            type="text" 
            placeholder="Cari User / Model / ID..." 
            class="pl-9 pr-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 w-56" 
          />
          <Search class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" />
        </div>

        <button @click="handleFilter" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-semibold transition">
          Filter
        </button>

        <button v-if="search || selectedAction" @click="resetFilter" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition">
          Reset
        </button>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-200">
              <th class="py-3.5 px-6 whitespace-nowrap">Waktu & Tanggal</th>
              <th class="py-3.5 px-6 whitespace-nowrap">Pelaku (User)</th>
              <th class="py-3.5 px-6 whitespace-nowrap">Action Event</th>
              <th class="py-3.5 px-6 whitespace-nowrap">Target Model</th>
              <th class="py-3.5 px-6 whitespace-nowrap">IP Address</th>
              <th class="py-3.5 px-6 whitespace-nowrap">Rincian Perubahan Data</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-slate-900">
            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-slate-50/80 transition">
              <!-- Timestamp -->
              <td class="py-4 px-6 text-slate-600 font-mono text-[11px] whitespace-nowrap">
                {{ new Date(log.created_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) }}
              </td>

              <!-- User -->
              <td class="py-4 px-6 whitespace-nowrap">
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 rounded-full bg-slate-100 text-slate-700 border border-slate-200 flex items-center justify-center font-bold text-[10px]">
                    {{ log.user?.name ? log.user.name.charAt(0) : 'S' }}
                  </div>
                  <div>
                    <span class="font-bold text-slate-900 block">{{ log.user?.name ?? 'System Automated' }}</span>
                    <span v-if="log.user?.role" class="text-[10px] text-slate-400 font-semibold uppercase">{{ log.user.role }}</span>
                  </div>
                </div>
              </td>

              <!-- Action Badge -->
              <td class="py-4 px-6 whitespace-nowrap">
                <span :class="['px-2.5 py-1 rounded-md text-[10px] font-mono font-extrabold border inline-block', getActionBadgeClass(log.action)]">
                  {{ log.action }}
                </span>
              </td>

              <!-- Target Model -->
              <td class="py-4 px-6 font-mono text-slate-700 whitespace-nowrap">
                <span class="font-semibold text-slate-900">{{ log.model_type ? log.model_type.split('\\').pop() : '-' }}</span>
                <span class="text-slate-400 ml-1">#{{ log.model_id }}</span>
              </td>

              <!-- IP Address -->
              <td class="py-4 px-6 font-mono text-slate-500 text-[11px] whitespace-nowrap">
                {{ log.ip_address ?? '127.0.0.1' }}
              </td>

              <!-- Human-Readable Changed Data Chips (No Raw JSON) -->
              <td class="py-4 px-6">
                <div v-if="log.new_values && Object.keys(log.new_values).length > 0" class="flex flex-wrap gap-1.5 max-w-md">
                  <div 
                    v-for="item in getReadablePayload(log.new_values)" 
                    :key="item.rawKey"
                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-slate-50 border border-slate-200 rounded-lg text-[11px]"
                  >
                    <span class="font-semibold text-slate-500">{{ item.label }}:</span>
                    <span :class="['font-bold', item.rawKey === 'status' ? 'text-sky-700' : 'text-slate-900']">
                      {{ item.value }}
                    </span>
                  </div>
                </div>
                <span v-else class="text-slate-400 italic text-xs">-</span>
              </td>
            </tr>

            <tr v-if="logs.data.length === 0">
              <td colspan="6" class="py-12 text-center text-slate-500">
                Tidak ada log aktivitas yang sesuai dengan filter pencarian.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="logs.links && logs.links.length > 3" class="p-4 border-t border-slate-100 flex items-center justify-between">
        <span class="text-xs text-slate-500">
          Menampilkan {{ logs.from ?? 0 }} - {{ logs.to ?? 0 }} dari {{ logs.total }} total rekaman
        </span>
        <div class="flex items-center gap-1">
          <Link
            v-for="(link, i) in logs.links"
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
