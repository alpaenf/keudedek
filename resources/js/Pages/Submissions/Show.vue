<script setup>
import { ref, computed } from 'vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { 
  Download, 
  FileText, 
  ArrowLeft, 
  CheckCircle2, 
  RotateCcw, 
  XCircle, 
  ShieldCheck, 
  Clock, 
  Printer, 
  Building2, 
  Wallet, 
  User, 
  Tag, 
  KeyRound,
  X,
  FileCheck
} from 'lucide-vue-next';

const props = defineProps({
  submission: Object,
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);

const formatRupiah = (val) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(val || 0);
};

const getStatusBadge = (status) => {
  const map = {
    DRAFT: 'bg-slate-100 text-slate-700 border-slate-300',
    SUBMITTED: 'bg-blue-100 text-blue-800 border-blue-300',
    UNDER_REVIEW: 'bg-amber-100 text-amber-800 border-amber-300',
    REVIEW: 'bg-amber-100 text-amber-800 border-amber-300',
    RETURNED: 'bg-orange-100 text-orange-800 border-orange-300',
    APPROVED: 'bg-indigo-100 text-indigo-800 border-indigo-300',
    RESERVED: 'bg-indigo-600 text-white border-indigo-700',
    PROCESSING: 'bg-cyan-100 text-cyan-800 border-cyan-300',
    FINAL: 'bg-emerald-600 text-white border-emerald-700',
    COMPLETED: 'bg-emerald-600 text-white border-emerald-700',
    REJECTED: 'bg-rose-100 text-rose-800 border-rose-300',
    CANCELLED: 'bg-slate-200 text-slate-700 border-slate-300',
  };
  return map[status] || 'bg-slate-100 text-slate-700 border-slate-300';
};

const isSignModalOpen = ref(false);
const activeDecision = ref('APPROVED');

const signForm = useForm({
  decision: 'APPROVED',
  comment: '',
  password: '',
  confirmed: false,
});

const openSignModal = (decision) => {
  activeDecision.value = decision;
  signForm.reset();
  signForm.decision = decision;
  signForm.confirmed = false;
  isSignModalOpen.value = true;
};

const submitDecision = () => {
  signForm.post(`/approvals/${props.submission.id}/decide`, {
    onSuccess: () => {
      isSignModalOpen.value = false;
      signForm.reset();
    },
  });
};

const canApprove = computed(() => {
  return page.props.auth?.user?.can_approve_financial;
});

const printDocument = () => {
  window.print();
};
</script>

<template>
  <AppLayout :title="`Pengajuan: ${submission.submission_number}`">
    <div class="space-y-6 max-w-6xl mx-auto">
      
      <!-- Top Navigation & Action Toolbar -->
      <div class="flex flex-wrap items-center justify-between gap-4 pb-2">
        <div>
          <Link href="/submissions" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-sky-600 transition mb-1">
            <ArrowLeft class="w-4 h-4" /> Kembali ke Daftar Pengajuan
          </Link>
          <div class="flex items-center gap-3">
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">{{ submission.submission_number }}</h2>
            <span :class="['px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider border shadow-sm', getStatusBadge(submission.status)]">
              {{ submission.status }}
            </span>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
          <a 
            :href="`/submissions/${submission.id}/print`"
            target="_blank"
            class="px-4 py-2.5 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm"
          >
            <Printer class="w-4 h-4 text-slate-500" />
            Cetak Dokumen Resmi Berkop
          </a>

          <!-- Approver Action Buttons -->
          <template v-if="canApprove">
            <button 
              @click="openSignModal('APPROVED')"
              class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-md shadow-emerald-600/20"
            >
              <ShieldCheck class="w-4 h-4" />
              Approve &amp; Sign
            </button>
            <button 
              @click="openSignModal('RETURNED')"
              class="px-3.5 py-2.5 bg-orange-50 hover:bg-orange-100 text-orange-800 border border-orange-200 rounded-xl text-xs font-bold transition"
            >
              <RotateCcw class="w-4 h-4" /> Kembalikan
            </button>
            <button 
              @click="openSignModal('REJECTED')"
              class="px-3.5 py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-800 border border-rose-200 rounded-xl text-xs font-bold transition"
            >
              <XCircle class="w-4 h-4" /> Tolak
            </button>
          </template>
        </div>
      </div>

      <!-- Main 2-Column Layout -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- LEFT: Primary Submission Details & Items (8 Cols) -->
        <div class="lg:col-span-8 space-y-6">
          
          <!-- Summary Header Card -->
          <div class="bg-white p-6 sm:p-7 rounded-3xl border border-slate-200 shadow-sm space-y-5">
            <div>
              <span class="text-xs font-bold text-sky-700 font-sans block mb-1">
                {{ submission.reference_no || 'DOKUMEN PENGAJUAN BELANJA FT UNSOED' }}
              </span>
              <h3 class="text-lg sm:text-xl font-bold text-slate-900 leading-snug">{{ submission.title }}</h3>
              <p class="text-xs text-slate-500 mt-1">
                Diusulkan oleh: <strong>{{ submission.creator?.name }}</strong> &bull; Unit: <strong>{{ submission.department?.name }}</strong>
              </p>
            </div>

            <!-- Meta Data Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs pt-3 border-t border-slate-100">
              <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                <span class="text-slate-500 block text-[10px]">Mata Anggaran</span>
                <span class="font-sans font-bold text-slate-900 text-xs">{{ submission.budget_bucket?.account_code }}</span>
              </div>
              <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                <span class="text-slate-500 block text-[10px]">Sumber Dana</span>
                <span class="font-bold text-slate-900 text-xs">{{ submission.budget_bucket?.funding_source?.code }}</span>
              </div>
              <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                <span class="text-slate-500 block text-[10px]">Mekanisme Transaksi</span>
                <span class="font-bold text-slate-900 text-xs">{{ submission.transaction_type?.code || 'LS' }}</span>
              </div>
              <div class="p-3 bg-sky-50 rounded-xl border border-sky-200 bg-sky-50/50">
                <span class="text-sky-800 block text-[10px] font-bold">Total Nominal</span>
                <span class="font-black text-sky-950 font-sans text-sm">{{ formatRupiah(submission.amount) }}</span>
              </div>
            </div>

            <div v-if="submission.beneficiary_name" class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs flex justify-between">
              <span class="text-slate-500">Penerima / Rekanan:</span>
              <span class="font-bold text-slate-900">{{ submission.beneficiary_name }}</span>
            </div>

            <div v-if="submission.notes" class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl text-xs space-y-1">
              <span class="font-bold text-slate-700 block text-[11px]">Catatan Pengajuan:</span>
              <p class="text-slate-600 leading-relaxed">{{ submission.notes }}</p>
            </div>
          </div>

          <!-- Items Breakdown Card -->
          <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-4">
            <h4 class="font-bold text-slate-900 text-sm flex items-center gap-2">
              <Tag class="w-4 h-4 text-sky-600" />
              Rincian Barang &amp; Jasa Belanja
            </h4>

            <div class="overflow-x-auto">
              <table class="w-full text-xs text-left border-collapse">
                <thead>
                  <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider text-[10px] border-y border-slate-200">
                    <th class="py-2.5 px-4">Nama Item / Uraian Belanja</th>
                    <th class="py-2.5 px-4 text-center">Volume</th>
                    <th class="py-2.5 px-4 text-right">Harga Satuan</th>
                    <th class="py-2.5 px-4 text-right">Total Harga</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-sans">
                  <tr v-for="item in submission.items" :key="item.id" class="hover:bg-slate-50/60 transition font-sans">
                    <td class="py-3 px-4 font-medium text-slate-900">{{ item.item_name }}</td>
                    <td class="py-3 px-4 text-center font-sans font-bold text-slate-800">{{ item.quantity }}</td>
                    <td class="py-3 px-4 text-right font-sans text-slate-700">{{ formatRupiah(item.unit_price) }}</td>
                    <td class="py-3 px-4 text-right font-sans font-bold text-slate-900">{{ formatRupiah(item.total_price) }}</td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr class="bg-slate-50 border-t border-slate-200 font-sans">
                    <td colspan="3" class="py-3 px-4 font-bold text-right text-slate-700 uppercase text-[11px]">Total Keseluruhan:</td>
                    <td class="py-3 px-4 font-sans font-black text-right text-sm text-sky-700">{{ formatRupiah(submission.amount) }}</td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>

          <!-- Documents List Card -->
          <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-4">
            <h4 class="font-bold text-slate-900 text-sm flex items-center gap-2">
              <FileText class="w-4 h-4 text-sky-600" />
              Berkas Lampiran Dokumen
            </h4>

            <div v-if="submission.documents && submission.documents.length > 0" class="space-y-2.5">
              <div 
                v-for="doc in submission.documents" 
                :key="doc.id" 
                class="p-3.5 bg-slate-50 border border-slate-200 rounded-2xl flex items-center justify-between text-xs hover:border-sky-300 transition"
              >
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center font-bold">
                    <FileText class="w-4 h-4" />
                  </div>
                  <div>
                    <span class="font-bold text-slate-900 block">{{ doc.document_type?.name || doc.original_filename }}</span>
                    <span class="text-[10px] text-slate-500 font-sans">{{ doc.original_filename }} &bull; {{ (doc.file_size / 1024).toFixed(0) }} KB</span>
                  </div>
                </div>
                <a 
                  :href="`/submissions/documents/${doc.id}/download`" 
                  class="px-3 py-1.5 bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 rounded-xl text-xs font-semibold flex items-center gap-1 transition shadow-sm"
                >
                  <Download class="w-3.5 h-3.5" /> Unduh
                </a>
              </div>
            </div>
            <div v-else-if="submission.attachment_path" class="p-3.5 bg-slate-50 border border-slate-200 rounded-2xl flex items-center justify-between text-xs">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center font-bold">
                  <FileText class="w-4 h-4" />
                </div>
                <div>
                  <span class="font-bold text-slate-900 block">Lampiran Dokumen PDF Pengajuan</span>
                  <span class="text-[10px] text-slate-500 font-sans">TOR / Proposal / RAB</span>
                </div>
              </div>
              <a 
                :href="`/storage/${submission.attachment_path}`" 
                target="_blank" 
                download 
                class="px-3 py-1.5 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-semibold flex items-center gap-1 transition shadow-sm"
              >
                <Download class="w-3.5 h-3.5" /> Unduh PDF
              </a>
            </div>
            <div v-else class="py-6 text-center text-slate-400 text-xs">
              Tidak ada berkas lampiran pendukung yang diunggah.
            </div>
          </div>
        </div>

        <!-- RIGHT: Interactive Vertical Timeline & Electronic Sign-off (4 Cols) -->
        <div class="lg:col-span-4 space-y-6">
          
          <!-- Electronic Sign-off Verification Badge -->
          <div v-if="submission.electronic_signoff_hash" class="p-5 bg-emerald-50 border border-emerald-200 rounded-3xl space-y-2 text-xs">
            <div class="flex items-center gap-2 text-emerald-900 font-bold text-sm">
              <ShieldCheck class="w-5 h-5 text-emerald-600" />
              Electronic Approval Sign-off Active
            </div>
            <p class="text-emerald-800 text-[11px] leading-relaxed">
              Berkas ini telah diverifikasi dan ditandatangani secara elektronik dengan hash integritas:
            </p>
            <div class="p-2 bg-white/80 rounded-xl border border-emerald-300 font-sans text-[10px] text-slate-800 break-all">
              {{ submission.electronic_signoff_hash }}
            </div>
          </div>

          <!-- Vertical Timeline Card -->
          <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-4">
            <h4 class="font-bold text-slate-900 text-sm flex items-center gap-2">
              <Clock class="w-4 h-4 text-sky-600" />
              Riwayat Perjalanan Berkas (Timeline)
            </h4>

            <div class="relative pl-6 space-y-6 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
              <div 
                v-for="hist in submission.status_histories" 
                :key="hist.id" 
                class="relative text-xs space-y-1"
              >
                <!-- Dot Indicator -->
                <div class="absolute -left-6 top-1 w-4 h-4 rounded-full bg-sky-600 border-2 border-white shadow-sm"></div>
                
                <div class="flex items-center justify-between font-bold">
                  <span class="text-slate-900">{{ hist.to_status }}</span>
                  <span class="text-[10px] text-slate-400 font-sans">{{ new Date(hist.created_at).toLocaleDateString('id-ID') }}</span>
                </div>
                <div class="text-[11px] text-slate-500">
                  Oleh: <span class="font-semibold text-slate-700">{{ hist.actor?.name || 'System' }}</span> ({{ hist.role }})
                </div>
                <p v-if="hist.notes" class="text-[11px] text-slate-600 p-2 bg-slate-50 rounded-lg border border-slate-100 mt-1">
                  {{ hist.notes }}
                </p>
              </div>

              <div v-if="!submission.status_histories || submission.status_histories.length === 0" class="text-slate-400 text-xs py-4">
                Belum ada log pergerakan status.
              </div>
            </div>
          </div>

          <!-- Approval Records List -->
          <div v-if="submission.approvals && submission.approvals.length > 0" class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-4">
            <h4 class="font-bold text-slate-900 text-sm flex items-center gap-2">
              <FileCheck class="w-4 h-4 text-emerald-600" />
              Daftar Otorisasi Pejabat
            </h4>

            <div class="space-y-3 text-xs">
              <div v-for="appr in submission.approvals" :key="appr.id" class="p-3 bg-slate-50 border border-slate-200 rounded-2xl space-y-1.5">
                <div class="flex items-center justify-between font-bold">
                  <span class="text-slate-900">{{ appr.user?.name }}</span>
                  <span class="text-[10px] px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-md uppercase font-bold">{{ appr.decision }}</span>
                </div>
                <div class="text-[10px] text-slate-500">Peran: {{ appr.role }} &bull; IP: {{ appr.ip_address || '127.0.0.1' }}</div>
                <p v-if="appr.comment" class="text-[11px] text-slate-700 italic">"{{ appr.comment }}"</p>
              </div>
            </div>
          </div>
        </div>
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
              <p class="text-xs text-slate-500 font-sans">{{ submission.submission_number }}</p>
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
              <span class="font-bold text-slate-900 max-w-[240px] text-right truncate">{{ submission.title }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Unit / Jurusan:</span>
              <span class="font-semibold text-slate-800">{{ submission.department?.name }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Pos Anggaran:</span>
              <span class="font-sans text-slate-800">{{ submission.budget_bucket?.account_code }}</span>
            </div>
            <div class="flex justify-between border-t border-slate-200 pt-2">
              <span class="text-slate-700 font-bold">Nominal Pengajuan:</span>
              <span class="font-black text-slate-900 font-sans text-sm">{{ formatRupiah(submission.amount) }}</span>
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
