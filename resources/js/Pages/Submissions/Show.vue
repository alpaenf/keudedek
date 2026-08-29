<script setup>
import { computed } from 'vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
  submission: Object,
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);

const form = useForm({
  status: props.submission.status,
  notes: props.submission.notes || '',
});

const updateStatus = () => {
  form.post(`/submissions/${props.submission.id}/status`);
};

const formatRupiah = (val) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0);
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

/**
 * Daftar opsi status workflow yang tersedia secara reaktif berdasarkan role pengguna dan status pengajuan
 */
const availableStatuses = computed(() => {
  const role = currentUser.value?.role || 'PTK';
  const currentSt = props.submission.status;

  const allStatuses = [
    { value: 'DRAFT', label: 'DRAFT — Konsep Pengajuan' },
    { value: 'SUBMITTED', label: 'SUBMITTED — Kirim Pengajuan' },
    { value: 'REVIEW', label: 'REVIEW — Verifikasi & Pemeriksaan' },
    { value: 'APPROVED', label: 'APPROVED — Setujui & Reserve Budget' },
    { value: 'RETURNED', label: 'RETURNED — Kembalikan ke Operator PTK' },
    { value: 'COMPLETED', label: 'COMPLETED — Realisasi & Pencairan Selesai' },
    { value: 'REJECTED', label: 'REJECTED — Tolak Pengajuan' },
  ];

  // Admin memiliki akses penuh ke seluruh alur status
  if (role === 'ADMIN') return allStatuses;

  // KAJUR (Ketua Jurusan)
  if (role === 'KAJUR') {
    return [
      { value: 'DRAFT', label: 'DRAFT — Konsep' },
      { value: 'SUBMITTED', label: 'SUBMITTED — Kirim ke Verifikasi Fakultas' },
      { value: 'REVIEW', label: 'REVIEW — Dalam Proses Verifikasi' },
      { value: 'APPROVED', label: 'APPROVED — Setujui Pengajuan' },
      { value: 'RETURNED', label: 'RETURNED — Kembalikan ke Operator PTK' },
      { value: 'REJECTED', label: 'REJECTED — Tolak Pengajuan' },
    ];
  }

  // PTK (Operator Unit)
  if (role === 'PTK') {
    return [
      { value: 'DRAFT', label: 'DRAFT — Konsep' },
      { value: 'SUBMITTED', label: 'SUBMITTED — Kirim Pengajuan ke KAJUR/Fakultas' },
    ];
  }

  // PTU (Reviewer Keuangan Fakultas)
  if (role === 'PTU') {
    return [
      { value: 'SUBMITTED', label: 'SUBMITTED — Baru Diterima' },
      { value: 'REVIEW', label: 'REVIEW — Verifikasi Berkas SPJ' },
      { value: 'APPROVED', label: 'APPROVED — Rekomendasi Disetujui' },
      { value: 'RETURNED', label: 'RETURNED — Kembalikan untuk Revisi' },
    ];
  }

  // DEKAN, WD & KABAG Keuangan (Pengambil Keputusan & Reservasi Budget)
  if (['KABAG', 'WD', 'DEKAN'].includes(role)) {
    return [
      { value: 'REVIEW', label: 'REVIEW — Pemeriksaan Final' },
      { value: 'APPROVED', label: 'APPROVED — Setujui & Reservasi Pagu' },
      { value: 'COMPLETED', label: 'COMPLETED — Realisasi Selesai' },
      { value: 'RETURNED', label: 'RETURNED — Kembalikan ke PTK' },
      { value: 'REJECTED', label: 'REJECTED — Tolak Pengajuan' },
    ];
  }

  return allStatuses;
});
</script>

<template>
  <AppLayout :title="`Detail Pengajuan: ${submission.submission_number}`">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
      <!-- Main Submission Details -->
      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm lg:col-span-2">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
          <div>
            <span class="font-mono text-xs font-bold text-sky-700">{{ submission.submission_number }}</span>
            <h3 class="text-xl font-bold text-slate-900 mt-1">{{ submission.title }}</h3>
            <p class="text-xs text-slate-500 mt-1">Pembuat: {{ submission.creator?.name }} &bull; {{ submission.department?.name }}</p>
          </div>
          
          <span :class="['px-3 py-1 rounded-full text-xs font-extrabold border', getBadgeClass(submission.status)]">{{ submission.status }}</span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-6">
          <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
            <span class="text-slate-500 text-[11px] block">Mata Anggaran</span>
            <span class="font-mono font-bold text-slate-900 text-xs">{{ submission.budget_bucket?.account_code }}</span>
          </div>
          <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
            <span class="text-slate-500 text-[11px] block">Sumber Dana</span>
            <span class="font-bold text-slate-900 text-xs">{{ submission.budget_bucket?.funding_source?.code }}</span>
          </div>
          <div class="p-3 bg-sky-50 rounded-xl border border-sky-100">
            <span class="text-sky-700 text-[11px] block">Total Nominal</span>
            <span class="font-bold text-sky-900 text-sm">{{ formatRupiah(submission.amount) }}</span>
          </div>
        </div>

        <div v-if="submission.notes" class="mb-6 p-4 bg-slate-50 border border-slate-200 rounded-xl text-xs">
          <span class="font-bold text-slate-900 block mb-1">Catatan Workflow:</span>
          <p class="text-slate-700">{{ submission.notes }}</p>
        </div>

        <!-- Rincian Items Table -->
        <h4 class="font-bold text-slate-900 text-sm mb-3">Rincian Barang / Belanja</h4>
        <div class="border border-slate-200 rounded-xl overflow-hidden text-xs">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-200">
                <th class="py-2.5 px-4 whitespace-nowrap">Nama Item</th>
                <th class="py-2.5 px-4 text-center whitespace-nowrap">Qty</th>
                <th class="py-2.5 px-4 text-right whitespace-nowrap">Harga Satuan</th>
                <th class="py-2.5 px-4 text-right whitespace-nowrap">Total</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-900">
              <tr v-for="item in submission.items" :key="item.id">
                <td class="py-3 px-4 font-medium text-slate-900">{{ item.item_name }}</td>
                <td class="py-3 px-4 text-center text-slate-900">{{ item.quantity }}</td>
                <td class="py-3 px-4 text-right text-slate-900 font-mono">{{ formatRupiah(item.unit_price) }}</td>
                <td class="py-3 px-4 text-right font-bold text-slate-900 font-mono">{{ formatRupiah(item.total_price) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Workflow Action Card -->
      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <h4 class="font-bold text-slate-900 text-sm mb-2">Aksi Status Workflow</h4>
        <p class="text-xs text-slate-500 mb-4">Ubah status pengajuan sesuai alur persetujuan & reservasi anggaran.</p>

        <form @submit.prevent="updateStatus" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Status Baru (Role: {{ currentUser?.role }})</label>
            <select v-model="form.status" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs font-bold text-slate-900 focus:ring-2 focus:ring-sky-500">
              <option v-for="opt in availableStatuses" :key="opt.value" :value="opt.value">
                {{ opt.label }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Perubahan Status</label>
            <textarea v-model="form.notes" rows="3" placeholder="Catatan verifikasi / persetujuan..." class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs text-slate-900 focus:ring-2 focus:ring-sky-500"></textarea>
          </div>

          <button type="submit" :disabled="form.processing" class="w-full py-2.5 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-semibold transition disabled:opacity-50 shadow-sm">
            Update Status Pengajuan
          </button>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
