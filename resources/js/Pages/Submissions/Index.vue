<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { Plus, Eye } from '@lucide/vue';

const props = defineProps({
  submissions: Object,
  departments: Array,
  filters: Object,
});

const status = ref(props.filters?.status || '');

const filterStatus = () => {
  router.get('/submissions', { status: status.value }, { preserveState: true });
};

const formatRupiah = (val) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0);
};

const getBadgeClass = (st) => {
  switch(st) {
    case 'REJECTED': return 'bg-rose-100 text-rose-700 border-rose-300';
    case 'RETURNED': return 'bg-rose-50 text-rose-600 border-rose-200';
    case 'COMPLETED': return 'bg-sky-100 text-sky-800 border-sky-300';
    default: return 'bg-slate-100 text-slate-800 border-slate-300';
  }
};
</script>

<template>
  <AppLayout title="Daftar Pengajuan Anggaran">
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm mb-6 flex flex-wrap items-center justify-between gap-4">
      <div>
        <h3 class="font-bold text-slate-900 text-base">Monitoring Submissions</h3>
        <p class="text-xs text-slate-500">Kelola dan lacak alur status pengajuan anggaran dari PTK hingga Completed</p>
      </div>

      <div class="flex items-center gap-3">
        <select v-model="status" @change="filterStatus" class="px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-semibold text-slate-900">
          <option value="">Semua Status</option>
          <option value="DRAFT">DRAFT</option>
          <option value="SUBMITTED">SUBMITTED</option>
          <option value="REVIEW">REVIEW</option>
          <option value="APPROVED">APPROVED</option>
          <option value="RESERVED">RESERVED</option>
          <option value="COMPLETED">COMPLETED</option>
          <option value="RETURNED">RETURNED</option>
          <option value="REJECTED">REJECTED</option>
        </select>

        <Link href="/submissions/create" class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded-lg text-xs font-semibold flex items-center gap-2 transition whitespace-nowrap shadow-sm">
          <Plus class="w-4 h-4" />
          Buat Pengajuan Baru
        </Link>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-200">
              <th class="py-3.5 px-6 whitespace-nowrap">No. Pengajuan</th>
              <th class="py-3.5 px-6 whitespace-nowrap">Kegiatan / Judul</th>
              <th class="py-3.5 px-6 whitespace-nowrap">Jurusan</th>
              <th class="py-3.5 px-6 whitespace-nowrap">Mata Anggaran</th>
              <th class="py-3.5 px-6 text-right whitespace-nowrap">Nominal</th>
              <th class="py-3.5 px-6 text-center whitespace-nowrap">Status</th>
              <th class="py-3.5 px-6 text-center whitespace-nowrap">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-slate-900">
            <tr v-for="sub in submissions.data" :key="sub.id" class="hover:bg-slate-50/80 transition">
              <td class="py-4 px-6 font-mono font-bold text-sky-700 whitespace-nowrap">{{ sub.submission_number }}</td>
              <td class="py-4 px-6 font-medium text-slate-900">{{ sub.title }}</td>
              <td class="py-4 px-6 font-semibold text-slate-900 whitespace-nowrap">{{ sub.department?.code }}</td>
              <td class="py-4 px-6 font-mono text-slate-700 whitespace-nowrap">{{ sub.budget_bucket?.account_code }}</td>
              <td class="py-4 px-6 text-right font-bold text-slate-900 whitespace-nowrap">{{ formatRupiah(sub.amount) }}</td>
              <td class="py-4 px-6 text-center whitespace-nowrap">
                <span :class="['px-2.5 py-1 rounded-full text-[10px] font-extrabold border inline-block', getBadgeClass(sub.status)]">{{ sub.status }}</span>
              </td>
              <td class="py-4 px-6 text-center whitespace-nowrap">
                <Link :href="`/submissions/${sub.id}`" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-sky-50 text-sky-700 hover:bg-sky-100 border border-sky-200 rounded-lg text-xs font-semibold transition whitespace-nowrap shadow-sm">
                  <Eye class="w-3.5 h-3.5" /> Detail
                </Link>
              </td>
            </tr>
            <tr v-if="submissions.data.length === 0">
              <td colspan="7" class="py-8 text-center text-slate-500">Belum ada pengajuan anggaran yang tercatat.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>
