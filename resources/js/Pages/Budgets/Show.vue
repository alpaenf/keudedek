<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { FileText, Eye } from '@lucide/vue';

const props = defineProps({
  budgetBucket: Object,
});

const form = useForm({
  revised_amount: props.budgetBucket?.allocated_budget ?? 0,
  reason: '',
});

const displayRevisedAmount = ref('');

const formatDots = (val) => {
  if (!val && val !== 0) return '';
  const numStr = String(val).replace(/\D/g, '');
  return numStr.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
};

displayRevisedAmount.value = formatDots(props.budgetBucket?.allocated_budget ?? 0);

const getTerbilang = (val) => {
  const num = Number(String(val).replace(/\D/g, ''));
  if (!num) return '';
  if (num >= 1_000_000_000_000) {
    return `Rp ${num.toLocaleString('id-ID')} — ${(num / 1_000_000_000_000).toFixed(2)} Triliun Rupiah`;
  }
  if (num >= 1_000_000_000) {
    return `Rp ${num.toLocaleString('id-ID')} — ${(num / 1_000_000_000).toFixed(2)} Miliar Rupiah`;
  }
  if (num >= 1_000_000) {
    return `Rp ${num.toLocaleString('id-ID')} — ${(num / 1_000_000).toFixed(2)} Juta Rupiah`;
  }
  return `Rp ${num.toLocaleString('id-ID')} Rupiah`;
};

const onRevisedInput = (e) => {
  const raw = e.target.value.replace(/\D/g, '');
  form.revised_amount = raw ? parseInt(raw, 10) : 0;
  displayRevisedAmount.value = formatDots(raw);
};

const submitRevision = () => {
  if (props.budgetBucket?.id) {
    form.post(`/budgets/${props.budgetBucket.id}/revise`);
  }
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
</script>

<template>
  <AppLayout :title="`Detail Pagu: ${budgetBucket?.account_code ?? '-'}`">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
      <!-- Header Details Card -->
      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm lg:col-span-2">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
          <div>
            <span class="px-2.5 py-1 bg-sky-100 text-sky-800 rounded font-mono text-xs font-bold">{{ budgetBucket?.account_code }}</span>
            <h3 class="text-xl font-bold text-slate-900 mt-2">{{ budgetBucket?.account_name }}</h3>
            <p class="text-xs text-slate-500 mt-1">
              {{ budgetBucket?.department?.name ?? 'Fakultas Teknik' }} ({{ budgetBucket?.department?.code ?? 'FT' }}) &bull; Sumber Dana: {{ budgetBucket?.funding_source?.name ?? budgetBucket?.fundingSource?.name ?? 'BOPTN' }}
            </p>
          </div>
          <Link href="/budgets" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition">
            Kembali
          </Link>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-center">
          <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
            <span class="text-slate-500 text-[11px] block">Pagu Awal</span>
            <span class="font-bold text-slate-900 text-sm">{{ formatRupiah(budgetBucket?.initial_budget) }}</span>
          </div>
          <div class="p-3 bg-sky-50 rounded-xl border border-sky-100">
            <span class="text-sky-700 text-[11px] block">Pagu Aktif</span>
            <span class="font-bold text-sky-900 text-sm">{{ formatRupiah(budgetBucket?.allocated_budget) }}</span>
          </div>
          <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
            <span class="text-slate-500 text-[11px] block">Komitmen (Reserved)</span>
            <span class="font-bold text-slate-900 text-sm">{{ formatRupiah(budgetBucket?.reserved_budget) }}</span>
          </div>
          <div class="p-3 bg-sky-50 rounded-xl border border-sky-100">
            <span class="text-sky-700 text-[11px] block">Saldo Tersedia</span>
            <span class="font-bold text-sky-900 text-sm">{{ formatRupiah(budgetBucket?.available_balance) }}</span>
          </div>
        </div>
      </div>

      <!-- Apply Revision Form Card -->
      <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <h4 class="font-bold text-slate-900 text-sm mb-2">Revisi Pagu Anggaran</h4>
        <p class="text-xs text-slate-500 mb-4">Lakukan pergeseran/revisi nominal pagu aktif untuk pos anggaran ini.</p>

        <form @submit.prevent="submitRevision" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Pagu Aktif Baru (Rp)</label>
            <input 
              type="text" 
              :value="displayRevisedAmount" 
              @input="onRevisedInput" 
              required 
              placeholder="Contoh: 850.000.000" 
              class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs font-bold text-slate-900 font-mono focus:ring-2 focus:ring-sky-500"
            >
            <div v-if="form.revised_amount" class="mt-1 text-[11px] font-semibold text-sky-700 bg-sky-50 px-2.5 py-1 rounded-md border border-sky-200 flex items-center justify-between">
              <span>{{ getTerbilang(form.revised_amount) }}</span>
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Alasan / Skematik Revisi</label>
            <textarea v-model="form.reason" rows="3" required placeholder="Contoh: Pergeseran sisa operasional lab ke riset..." class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs text-slate-900 focus:ring-2 focus:ring-sky-500"></textarea>
          </div>

          <button type="submit" :disabled="form.processing" class="w-full py-2.5 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-semibold transition disabled:opacity-50 shadow-sm">
            Simpan & Terapkan Revisi
          </button>
        </form>
      </div>
    </div>

    <!-- Submissions Linked to this Budget Account -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-8">
      <div class="p-6 border-b border-slate-200 flex items-center justify-between">
        <div>
          <h3 class="font-bold text-slate-900 text-base flex items-center gap-2">
            <FileText class="w-5 h-5 text-sky-600" />
            Pengajuan Terkait Mata Anggaran Ini
          </h3>
          <p class="text-xs text-slate-500">Daftar seluruh pengajuan kegiatan yang memotong/mengunci pagu pos ini</p>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-200">
              <th class="py-3 px-6 whitespace-nowrap">No. Pengajuan</th>
              <th class="py-3 px-6 whitespace-nowrap">Judul Kegiatan</th>
              <th class="py-3 px-6 whitespace-nowrap">Pembuat / Unit</th>
              <th class="py-3 px-6 text-right whitespace-nowrap">Nominal</th>
              <th class="py-3 px-6 text-center whitespace-nowrap">Status Alur</th>
              <th class="py-3 px-6 text-center whitespace-nowrap">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-slate-900">
            <tr v-for="sub in budgetBucket?.submissions" :key="sub.id" class="hover:bg-slate-50/80 transition">
              <td class="py-3.5 px-6 font-mono font-bold text-sky-700 whitespace-nowrap">{{ sub.submission_number }}</td>
              <td class="py-3.5 px-6 font-medium text-slate-900">{{ sub.title }}</td>
              <td class="py-3.5 px-6 font-medium text-slate-700 whitespace-nowrap">{{ sub.creator?.name ?? 'PTK Unit' }}</td>
              <td class="py-3.5 px-6 text-right font-bold text-slate-900 whitespace-nowrap">{{ formatRupiah(sub.amount) }}</td>
              <td class="py-3.5 px-6 text-center whitespace-nowrap">
                <span :class="['px-2.5 py-1 rounded-full text-[10px] font-extrabold border inline-block', getBadgeClass(sub.status)]">{{ sub.status }}</span>
              </td>
              <td class="py-3.5 px-6 text-center whitespace-nowrap">
                <Link :href="`/submissions/${sub.id}`" class="inline-flex items-center gap-1 px-2.5 py-1 bg-sky-50 text-sky-700 hover:bg-sky-100 border border-sky-200 rounded-lg text-xs font-semibold transition">
                  <Eye class="w-3.5 h-3.5" /> Detail
                </Link>
              </td>
            </tr>
            <tr v-if="!budgetBucket?.submissions || budgetBucket.submissions.length === 0">
              <td colspan="6" class="py-8 text-center text-slate-500">Belum ada pengajuan kegiatan yang terhubung ke mata anggaran ini.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- History Revisions Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-8">
      <div class="p-6 border-b border-slate-200">
        <h3 class="font-bold text-slate-900 text-base">Histori Revisi Anggaran</h3>
        <p class="text-xs text-slate-500">Jejak perubahan dan pergeseran nominal pagu aktif pada pos ini</p>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-200">
              <th class="py-3 px-6 whitespace-nowrap">No. Revisi</th>
              <th class="py-3 px-6 text-right whitespace-nowrap">Pagu Sebelumnya</th>
              <th class="py-3 px-6 text-right whitespace-nowrap">Pagu Baru</th>
              <th class="py-3 px-6 text-right whitespace-nowrap">Selisih (+/-)</th>
              <th class="py-3 px-6 whitespace-nowrap">Alasan</th>
              <th class="py-3 px-6 whitespace-nowrap">Disetujui Oleh</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-slate-900">
            <tr v-for="rev in budgetBucket?.revisions" :key="rev.id" class="hover:bg-slate-50/80 transition">
              <td class="py-3.5 px-6 font-mono font-bold text-sky-700 whitespace-nowrap">{{ rev.revision_number }}</td>
              <td class="py-3.5 px-6 text-right whitespace-nowrap">{{ formatRupiah(rev.previous_amount) }}</td>
              <td class="py-3.5 px-6 text-right font-bold whitespace-nowrap">{{ formatRupiah(rev.revised_amount) }}</td>
              <td :class="['py-3.5 px-6 text-right font-bold whitespace-nowrap', rev.difference < 0 ? 'text-rose-600' : 'text-sky-700']">
                {{ rev.difference >= 0 ? '+' : '' }}{{ formatRupiah(rev.difference) }}
              </td>
              <td class="py-3.5 px-6">{{ rev.reason }}</td>
              <td class="py-3.5 px-6 font-medium whitespace-nowrap">{{ rev.approver?.name }}</td>
            </tr>
            <tr v-if="!budgetBucket?.revisions || budgetBucket.revisions.length === 0">
              <td colspan="6" class="py-8 text-center text-slate-500">Belum ada riwayat revisi untuk pos anggaran ini.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>
