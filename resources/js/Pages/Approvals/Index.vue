<script setup>
import { ref } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { 
  FileCheck, 
  Search, 
  Building2, 
  CheckCircle2, 
  RotateCcw, 
  XCircle, 
  Eye, 
  Lock, 
  KeyRound, 
  ShieldCheck, 
  X,
  AlertTriangle
} from 'lucide-vue-next';

const props = defineProps({
  submissions: Object,
  departments: Array,
  filters: Object,
});

const search = ref(props.filters?.search || '');
const selectedDept = ref(props.filters?.department_id || '');

const handleFilter = () => {
  router.get('/approvals', {
    search: search.value || undefined,
    department_id: selectedDept.value || undefined,
  }, { preserveState: true, preserveScroll: true });
};

const isSignModalOpen = ref(false);
const activeSubmission = ref(null);
const activeDecision = ref('APPROVED'); // APPROVED, RETURNED, REJECTED

const signForm = useForm({
  decision: 'APPROVED',
  comment: '',
  password: '',
  confirmed: false,
});

const openSignModal = (submission, decision) => {
  activeSubmission.value = submission;
  activeDecision.value = decision;
  signForm.reset();
  signForm.decision = decision;
  signForm.confirmed = false;
  isSignModalOpen.value = true;
};

const submitDecision = () => {
  if (!activeSubmission.value) return;

  signForm.post(`/approvals/${activeSubmission.value.id}/decide`, {
    onSuccess: () => {
      isSignModalOpen.value = false;
      signForm.reset();
    },
  });
};

const formatRupiah = (val) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(val || 0);
};
</script>

<template>
  <AppLayout title="Antrean Persetujuan & Electronic Sign-off">
    <!-- Header -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
      <div>
        <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
          <FileCheck class="w-6 h-6 text-sky-600" />
          Antrean Persetujuan (Approval Queue)
        </h2>
        <p class="text-xs text-slate-500 mt-0.5">
          Daftar berkas pengajuan anggaran yang menunggu verifikasi dan tanda tangan elektronik Anda.
        </p>
      </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm mb-6 flex flex-wrap items-center justify-between gap-3">
      <div class="flex-1 min-w-[240px] relative">
        <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
        <input 
          v-model="search" 
          @input="handleFilter"
          type="text" 
          placeholder="Cari nomor pengajuan atau nama kegiatan..." 
          class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none transition"
        >
      </div>

      <div class="flex items-center gap-2">
        <Building2 class="w-4 h-4 text-slate-400 shrink-0" />
        <select 
          v-model="selectedDept" 
          @change="handleFilter"
          class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none transition"
        >
          <option value="">Semua Jurusan</option>
          <option v-for="d in departments" :key="d.id" :value="d.id">
            {{ d.name }}
          </option>
        </select>
      </div>
    </div>

    <!-- Approval Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6">
      <div class="overflow-x-auto">
        <table class="w-full text-xs text-left">
          <thead class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200">
            <tr>
              <th class="py-3.5 px-4">No. Pengajuan</th>
              <th class="py-3.5 px-4">Nama Kegiatan</th>
              <th class="py-3.5 px-4">Unit Jurusan</th>
              <th class="py-3.5 px-4">Pos Anggaran</th>
              <th class="py-3.5 px-4 text-right">Nominal Pengajuan</th>
              <th class="py-3.5 px-4 text-center">Status</th>
              <th class="py-3.5 px-4 text-center">Aksi Otorisasi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="sub in submissions.data" :key="sub.id" class="hover:bg-slate-50/70 transition">
              <td class="py-3.5 px-4 font-sans font-bold text-sky-700 whitespace-nowrap">
                <Link :href="`/submissions/${sub.id}`" class="hover:underline">
                  {{ sub.submission_number }}
                </Link>
                <div v-if="sub.reference_no" class="text-[10px] text-slate-400 font-sans">{{ sub.reference_no }}</div>
              </td>
              <td class="py-3.5 px-4 max-w-xs">
                <div class="font-bold text-slate-900">{{ sub.title }}</div>
                <div class="text-[10px] text-slate-500">Oleh: {{ sub.creator?.name }}</div>
              </td>
              <td class="py-3.5 px-4 font-medium text-slate-700 whitespace-nowrap">{{ sub.department?.name }}</td>
              <td class="py-3.5 px-4 text-slate-600">
                <div class="font-sans text-[11px] font-semibold">{{ sub.budget_bucket?.account_code }}</div>
                <div class="text-[10px] text-slate-500 truncate max-w-[200px]">{{ sub.budget_bucket?.budget_bucket_name || sub.budget_bucket?.account_name }}</div>
              </td>
              <td class="py-3.5 px-4 text-right font-sans font-bold text-slate-900 whitespace-nowrap">
                {{ formatRupiah(sub.amount) }}
              </td>
              <td class="py-3.5 px-4 text-center whitespace-nowrap">
                <span class="px-2.5 py-1 bg-amber-50 border border-amber-200 text-amber-800 text-[10px] font-bold rounded-lg uppercase">
                  {{ sub.status }}
                </span>
              </td>
              <td class="py-3.5 px-4 text-center whitespace-nowrap">
                <div class="flex items-center justify-center gap-1.5">
                  <Link 
                    :href="`/submissions/${sub.id}`" 
                    class="p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition" 
                    title="Lihat Rincian"
                  >
                    <Eye class="w-4 h-4" />
                  </Link>
                  <button 
                    @click="openSignModal(sub, 'APPROVED')" 
                    class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-[11px] font-bold flex items-center gap-1 transition shadow-sm"
                  >
                    <CheckCircle2 class="w-3.5 h-3.5" /> Setujui &amp; Sign
                  </button>
                  <button 
                    @click="openSignModal(sub, 'RETURNED')" 
                    class="px-2 py-1.5 bg-orange-50 hover:bg-orange-100 text-orange-700 border border-orange-200 rounded-lg text-[11px] font-semibold transition"
                    title="Kembalikan untuk Perbaikan"
                  >
                    <RotateCcw class="w-3.5 h-3.5" />
                  </button>
                  <button 
                    @click="openSignModal(sub, 'REJECTED')" 
                    class="px-2 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-[11px] font-semibold transition"
                    title="Tolak Pengajuan"
                  >
                    <XCircle class="w-3.5 h-3.5" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="submissions.data.length === 0">
              <td colspan="7" class="py-12 text-center text-slate-400 text-xs">
                <CheckCircle2 class="w-10 h-10 text-emerald-500 mx-auto mb-2 opacity-60" />
                Tidak ada pengajuan yang membutuhkan persetujuan Anda saat ini.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Electronic Approval Sign-off Modal -->
    <div v-if="isSignModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="max-w-lg w-full bg-white rounded-3xl border border-slate-200 shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-150">
        <!-- Modal Header -->
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <div :class="['w-9 h-9 rounded-xl flex items-center justify-center font-bold text-white', activeDecision === 'APPROVED' ? 'bg-emerald-600' : (activeDecision === 'RETURNED' ? 'bg-orange-600' : 'bg-rose-600')]">
              <ShieldCheck v-if="activeDecision === 'APPROVED'" class="w-5 h-5" />
              <RotateCcw v-else-if="activeDecision === 'RETURNED'" class="w-5 h-5" />
              <XCircle v-else class="w-5 h-5" />
            </div>
            <div>
              <h3 class="text-base font-bold text-slate-900">
                {{ activeDecision === 'APPROVED' ? 'Konfirmasi Electronic Approval Sign-off' : (activeDecision === 'RETURNED' ? 'Kembalikan Pengajuan (Perlu Perbaikan)' : 'Tolak Pengajuan Anggaran') }}
              </h3>
              <p class="text-xs text-slate-500 font-sans">{{ activeSubmission?.submission_number }}</p>
            </div>
          </div>
          <button @click="isSignModalOpen = false" class="p-1 text-slate-400 hover:text-slate-700 rounded-lg">
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Modal Body -->
        <form @submit.prevent="submitDecision" class="p-6 space-y-4 text-xs">
          <!-- Summary Box -->
          <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-2">
            <div class="flex justify-between">
              <span class="text-slate-500">Judul Kegiatan:</span>
              <span class="font-bold text-slate-900 max-w-[240px] text-right truncate">{{ activeSubmission?.title }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Unit / Jurusan:</span>
              <span class="font-semibold text-slate-800">{{ activeSubmission?.department?.name }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Pos Anggaran:</span>
              <span class="font-sans text-slate-800">{{ activeSubmission?.budget_bucket?.account_code }}</span>
            </div>
            <div class="flex justify-between border-t border-slate-200 pt-2">
              <span class="text-slate-700 font-bold">Nominal Pengajuan:</span>
              <span class="font-black text-slate-900 font-sans text-sm">{{ formatRupiah(activeSubmission?.amount) }}</span>
            </div>
          </div>

          <!-- Comment Textarea -->
          <div>
            <label class="block font-semibold text-slate-700 mb-1">
              Catatan / Alasan Keputusan 
              <span v-if="activeDecision !== 'APPROVED'" class="text-rose-600">* (Wajib)</span>
            </label>
            <textarea 
              v-model="signForm.comment" 
              rows="3" 
              :required="activeDecision !== 'APPROVED'"
              :placeholder="activeDecision === 'APPROVED' ? 'Catatan persetujuan (opsional)...' : 'Tuliskan catatan perbaikan atau alasan penolakan...'"
              class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none"
            ></textarea>
            <div v-if="signForm.errors.comment" class="text-rose-600 text-[11px] mt-1">{{ signForm.errors.comment }}</div>
          </div>

          <!-- Re-authentication Password Check for APPROVED -->
          <div v-if="activeDecision === 'APPROVED'" class="space-y-3 pt-1">
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Konfirmasi Kata Sandi (Re-Authentication Sign-off)</label>
              <input 
                v-model="signForm.password" 
                type="password" 
                placeholder="Masukkan kata sandi Anda untuk verifikasi identitas..."
                class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none"
              >
              <div v-if="signForm.errors.password" class="text-rose-600 text-[11px] mt-1">{{ signForm.errors.password }}</div>
            </div>

            <label class="flex items-start gap-2.5 cursor-pointer select-none p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-900">
              <input v-model="signForm.confirmed" type="checkbox" required class="mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
              <span class="text-[11px] leading-relaxed">
                Saya menyatakan telah memverifikasi kelayakan usulan ini secara sadar dan menandatangani persetujuan secara elektronik (Electronic Approval Sign-off).
              </span>
            </label>
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
            <button 
              type="button" 
              @click="isSignModalOpen = false" 
              class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-semibold transition"
            >
              Batal
            </button>
            <button 
              type="submit" 
              :disabled="signForm.processing || (activeDecision === 'APPROVED' && !signForm.confirmed)"
              :class="[
                'px-5 py-2.5 rounded-xl font-bold text-white transition flex items-center gap-1.5 shadow-md disabled:opacity-50',
                activeDecision === 'APPROVED' ? 'bg-emerald-600 hover:bg-emerald-500 shadow-emerald-600/20' : (activeDecision === 'RETURNED' ? 'bg-orange-600 hover:bg-orange-500' : 'bg-rose-600 hover:bg-rose-500')
              ]"
            >
              <ShieldCheck class="w-4 h-4" />
              {{ activeDecision === 'APPROVED' ? 'Konfirmasi & Tanda Tangan' : (activeDecision === 'RETURNED' ? 'Kembalikan Berkas' : 'Tolak Berkas') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
