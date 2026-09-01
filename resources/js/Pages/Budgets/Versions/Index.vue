<script setup>
import { ref } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import { 
  GitCompare, 
  Eye, 
  CheckCircle2, 
  Archive, 
  Plus, 
  Calendar, 
  Wallet, 
  ShieldCheck, 
  Info,
  ArrowRight,
  Clock,
  FileSpreadsheet
} from 'lucide-vue-next';

const props = defineProps({
  fiscalYears: Array,
  fundingSources: Array,
  selectedYear: Object,
  selectedFunding: Object,
  versions: Array,
  canManageVersions: Boolean,
});

const yearFilter = ref(props.selectedYear?.id || '');
const fundingFilter = ref(props.selectedFunding?.id || '');

const onFilterChange = () => {
  router.get('/budget-versions', {
    fiscal_year_id: yearFilter.value,
    funding_source_id: fundingFilter.value,
  }, { preserveState: true });
};

// Modal & Forms
const isNewDraftModalOpen = ref(false);
const formDraft = useForm({
  fiscal_year_id: props.selectedYear?.id || 1,
  funding_source_id: props.selectedFunding?.id || 1,
  revision_no: '',
  version_label: '',
  source_reference: '',
  notes: '',
});

const submitDraft = () => {
  formDraft.post('/budget-versions', {
    onSuccess: () => {
      isNewDraftModalOpen.value = false;
      formDraft.reset();
    }
  });
};

const activateForm = useForm({});
const archiveForm = useForm({});

const activateVersion = (v) => {
  if (confirm(`Apakah Anda yakin ingin mengaktifkan [${v.revision_no}] (${v.version_label}) sebagai versi pagu aktif berjalan? Versi aktif sebelumnya akan otomatis diarsipkan (ARCHIVED) tanpa menghapus riwayat masa lalu.`)) {
    activateForm.post(`/budget-versions/${v.id}/activate`);
  }
};

const archiveVersion = (v) => {
  if (confirm(`Apakah Anda yakin ingin mengarsipkan [${v.revision_no}]?`)) {
    archiveForm.post(`/budget-versions/${v.id}/archive`);
  }
};

const formatRupiah = (val) => {
  return new Intl.NumberFormat('id-ID', { 
    style: 'currency', 
    currency: 'IDR', 
    maximumFractionDigits: 0 
  }).format(val || 0);
};

const getStatusBadge = (st) => {
  switch (st) {
    case 'ACTIVE':
      return { label: 'ACTIVE', class: 'bg-emerald-100 text-emerald-800 border-emerald-300 font-black' };
    case 'DRAFT':
      return { label: 'DRAFT', class: 'bg-sky-100 text-sky-800 border-sky-300 font-bold' };
    default:
      return { label: 'ARCHIVED', class: 'bg-slate-100 text-slate-600 border-slate-300 font-medium' };
  }
};
</script>

<template>
  <AppLayout title="Budget Version &amp; Revision Management">
    <div class="space-y-6 font-sans">
      
      <!-- Top Title Bar -->
      <div class="bg-white p-5 sm:p-6 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <span class="px-2.5 py-0.5 bg-sky-100 text-sky-800 text-[10px] font-black rounded-lg uppercase tracking-wider">
              Budget Version Control
            </span>
            <span class="text-xs text-slate-400 font-semibold">&bull; DIPA RKAKL FT UNSOED</span>
          </div>
          <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight mt-1">
            Versi &amp; Riwayat Revisi Anggaran
          </h1>
          <p class="text-xs text-slate-500 mt-0.5">
            Kendali versi alokasi anggaran, pelacakan pergeseran pos pagu, komparasi antar revisi, dan deteksi konflik.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
          <Link 
            href="/budget-versions/compare" 
            class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-2xl transition flex items-center gap-1.5 shadow-sm"
          >
            <GitCompare class="w-4 h-4 text-slate-600" />
            <span>Komparasi Antar Revisi</span>
          </Link>

          <button 
            v-if="canManageVersions"
            @click="isNewDraftModalOpen = true" 
            class="px-4 py-2.5 bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold rounded-2xl transition flex items-center gap-1.5 shadow-md shadow-sky-600/20"
          >
            <Plus class="w-4 h-4" />
            <span>+ Buat Usulan Revisi (Draft)</span>
          </button>
        </div>
      </div>

      <!-- Context Bar Filters: TA 2026 & RM -->
      <div class="bg-white p-4 sm:p-5 rounded-3xl border border-slate-200/80 shadow-sm flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
          <div class="flex items-center gap-2">
            <Calendar class="w-4 h-4 text-sky-600" />
            <span class="text-xs font-bold text-slate-700">Tahun Anggaran:</span>
            <select 
              v-model="yearFilter" 
              @change="onFilterChange" 
              class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:ring-2 focus:ring-sky-500"
            >
              <option v-for="y in fiscalYears" :key="y.id" :value="y.id">
                TA {{ y.year }} {{ y.status === 'ACTIVE' ? '(Aktif)' : '' }}
              </option>
            </select>
          </div>

          <div class="flex items-center gap-2">
            <Wallet class="w-4 h-4 text-sky-600" />
            <span class="text-xs font-bold text-slate-700">Sumber Dana:</span>
            <select 
              v-model="fundingFilter" 
              @change="onFilterChange" 
              class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:ring-2 focus:ring-sky-500"
            >
              <option v-for="fs in fundingSources" :key="fs.id" :value="fs.id">
                {{ fs.code }} &mdash; {{ fs.name }}
              </option>
            </select>
          </div>
        </div>

        <div class="flex items-center gap-2 text-xs text-slate-500">
          <span>Konteks Anggaran Terpilih:</span>
          <span class="px-2.5 py-1 bg-sky-50 text-sky-800 font-extrabold rounded-lg border border-sky-200">
            TA {{ selectedYear?.year || 2026 }} &bull; {{ selectedFunding?.code || 'RM' }}
          </span>
        </div>
      </div>

      <!-- Version Evolution Cards List -->
      <div class="space-y-4">
        <div 
          v-for="v in versions" 
          :key="v.id" 
          :class="[
            'p-6 rounded-3xl border transition shadow-sm space-y-4 bg-white',
            v.status === 'ACTIVE' ? 'border-emerald-300 ring-2 ring-emerald-500/20' : 'border-slate-200/80 hover:border-slate-300'
          ]"
        >
          <!-- Version Header -->
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div class="flex items-center gap-3">
              <div :class="['p-3 rounded-2xl text-base font-black font-sans border', v.status === 'ACTIVE' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-slate-50 text-slate-700 border-slate-200']">
                {{ v.revision_no }}
              </div>
              <div>
                <div class="flex items-center gap-2">
                  <h3 class="text-base font-bold text-slate-900">{{ v.version_label }}</h3>
                  <span :class="['px-2.5 py-0.5 rounded-full text-[10px] border uppercase', getStatusBadge(v.status).class]">
                    {{ getStatusBadge(v.status).label }}
                  </span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                  Referensi: <code class="font-sans font-semibold text-slate-700">{{ v.source_reference || '-' }}</code> 
                  &bull; Berlaku: {{ v.effective_at || 'Belum Diberlakukan' }}
                </p>
              </div>
            </div>

            <!-- Actions per version: Compare, View, Activate (if authorized), Archive -->
            <div class="flex flex-wrap items-center gap-2">
              <!-- Compare Action -->
              <Link 
                :href="`/budget-versions/compare?target_version_id=${v.id}`" 
                class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition flex items-center gap-1.5 shadow-sm"
              >
                <GitCompare class="w-3.5 h-3.5 text-slate-600" />
                <span>Compare</span>
              </Link>

              <!-- View Action -->
              <Link 
                :href="`/budgets?budget_version_id=${v.id}`" 
                class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition flex items-center gap-1.5 shadow-sm"
              >
                <Eye class="w-3.5 h-3.5 text-slate-600" />
                <span>View Pagu</span>
              </Link>

              <!-- Activate Action (Authorized) -->
              <button 
                v-if="canManageVersions && v.status !== 'ACTIVE'"
                @click="activateVersion(v)"
                :disabled="activateForm.processing"
                class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl transition flex items-center gap-1.5 shadow-sm disabled:opacity-50"
              >
                <CheckCircle2 class="w-3.5 h-3.5" />
                <span>Activate</span>
              </button>

              <!-- Archive Action -->
              <button 
                v-if="canManageVersions && v.status !== 'ARCHIVED'"
                @click="archiveVersion(v)"
                :disabled="archiveForm.processing || v.status === 'ACTIVE'"
                :title="v.status === 'ACTIVE' ? 'Versi aktif tidak dapat diarsipkan langsung' : 'Arsipkan versi ini'"
                class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 text-xs font-bold rounded-xl transition flex items-center gap-1.5 disabled:opacity-40 disabled:cursor-not-allowed"
              >
                <Archive class="w-3.5 h-3.5" />
                <span>Archive</span>
              </button>
            </div>
          </div>

          <!-- Financial Context Metrics Grid -->
          <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-5 gap-3 text-xs">
            <div class="p-3 rounded-2xl bg-slate-50/60 border border-slate-100">
              <span class="text-[10px] text-slate-400 block font-semibold">Total Pagu Alokasi</span>
              <span class="font-sans font-black text-slate-900 text-sm mt-0.5 block">{{ formatRupiah(v.total_allocated) }}</span>
              <span class="text-[10px] text-slate-400">{{ v.bucket_count }} Pos Anggaran</span>
            </div>

            <div class="p-3 rounded-2xl bg-slate-50/60 border border-slate-100">
              <span class="text-[10px] text-amber-700 block font-semibold">Dalam Proses (Reserved)</span>
              <span class="font-sans font-black text-amber-950 text-sm mt-0.5 block">{{ formatRupiah(v.total_reserved) }}</span>
              <span class="text-[10px] text-amber-600 font-semibold">Komitmen Belanja</span>
            </div>

            <div class="p-3 rounded-2xl bg-slate-50/60 border border-slate-100">
              <span class="text-[10px] text-sky-700 block font-semibold">Realisasi (SPJ Final)</span>
              <span class="font-sans font-black text-sky-950 text-sm mt-0.5 block">{{ formatRupiah(v.total_realized) }}</span>
              <span class="text-[10px] text-sky-600 font-semibold">Serapan: {{ v.serapan_percentage }}%</span>
            </div>

            <div class="p-3 rounded-2xl bg-slate-50/60 border border-slate-100">
              <span class="text-[10px] text-emerald-700 block font-semibold">Sisa Saldo Tersedia</span>
              <span class="font-sans font-black text-emerald-950 text-sm mt-0.5 block">{{ formatRupiah(v.total_available) }}</span>
              <span class="text-[10px] text-emerald-700 font-semibold">Bebas Digunakan</span>
            </div>

            <div class="p-3 rounded-2xl bg-slate-50/60 border border-slate-100 col-span-2 sm:col-span-4 lg:col-span-1">
              <span class="text-[10px] text-slate-400 block font-semibold">Catatan Revisi</span>
              <p class="text-[11px] text-slate-600 mt-0.5 line-clamp-2 leading-relaxed">
                {{ v.notes || 'Tidak ada catatan khusus.' }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Invariant Notice -->
      <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl flex items-start gap-3 text-xs text-slate-600">
        <Info class="w-4 h-4 text-sky-600 shrink-0 mt-0.5" />
        <p class="leading-relaxed">
          <strong class="font-bold text-slate-900">Ketentuan Versi &amp; Audit Trail:</strong> Sistem SIKARA menganut prinsip *immutable revision history*. Setiap perubahan pagu dicatat ke versi terpisah (Rev 00, Rev 01, Rev 02, dst.) dan versi lama tidak pernah di-overwrite guna menjamin transparansi serta auditabilitas laporan realisasi keuangan.
        </p>
      </div>

    </div>

    <!-- Modal Form Draft Usulan Revisi Baru -->
    <div v-if="isNewDraftModalOpen" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl border border-slate-200 space-y-4">
        <div class="flex items-center gap-3">
          <div class="p-3 bg-sky-100 text-sky-700 rounded-2xl">
            <GitCompare class="w-6 h-6" />
          </div>
          <div>
            <h3 class="text-base font-bold text-slate-900">Buat Draft Usulan Revisi Baru</h3>
            <p class="text-xs text-slate-500">TA {{ selectedYear?.year }} &bull; {{ selectedFunding?.code }}</p>
          </div>
        </div>

        <form @submit.prevent="submitDraft" class="space-y-3 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Nomor Revisi</label>
            <input 
              v-model="formDraft.revision_no" 
              type="text" 
              required 
              placeholder="Contoh: Rev 04" 
              class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-bold text-slate-900 focus:ring-2 focus:ring-sky-500" 
            />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Label / Nama Revisi</label>
            <input 
              v-model="formDraft.version_label" 
              type="text" 
              required 
              placeholder="Contoh: Revisi 04 Optimalisasi Belanja Lab" 
              class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:ring-2 focus:ring-sky-500" 
            />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Nomor Surat / Referensi SK</label>
            <input 
              v-model="formDraft.source_reference" 
              type="text" 
              placeholder="Contoh: DIPA-023.17.2.677558/2026-04" 
              class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:ring-2 focus:ring-sky-500" 
            />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Catatan &amp; Alasan Revisi</label>
            <textarea 
              v-model="formDraft.notes" 
              rows="3" 
              placeholder="Jelaskan dasar pertimbangan dan usulan pergeseran alokasi..." 
              class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:ring-2 focus:ring-sky-500"
            ></textarea>
          </div>

          <div class="flex items-center justify-end gap-2 pt-2">
            <button 
              type="button" 
              @click="isNewDraftModalOpen = false" 
              class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition"
            >
              Batal
            </button>
            <button 
              type="submit" 
              :disabled="formDraft.processing" 
              class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white font-bold rounded-xl text-xs transition shadow-md shadow-sky-600/20 disabled:opacity-50"
            >
              Simpan Draft Revisi
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
