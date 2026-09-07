<script setup>
import { onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { ShieldCheck, Printer, ArrowLeft, CheckCircle2, FileText } from 'lucide-vue-next';

const props = defineProps({
  submission: Object,
  signoffUser: Object,
  signoffDate: String,
  signers: Object,
});

const formatRupiah = (val) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(val || 0);
};

const triggerPrint = () => {
  window.print();
};

onMounted(() => {
  // Auto trigger print dialog after small render delay
  setTimeout(() => {
    window.print();
  }, 500);
});
</script>

<template>
  <Head :title="`Cetak Bukti Transaksi - ${submission.evidence_number || submission.submission_number}`" />

  <div class="min-h-screen bg-slate-100 p-4 sm:p-8 print:p-0 print:bg-white text-slate-900 font-sans">
    
    <!-- Top Floating Toolbar (Hidden on Print) -->
    <div class="max-w-4xl mx-auto mb-6 flex items-center justify-between print:hidden">
      <Link 
        :href="`/submissions/${submission.id}`" 
        class="px-4 py-2 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm"
      >
        <ArrowLeft class="w-4 h-4" /> Kembali ke Detail Transaksi
      </Link>

      <button 
        @click="triggerPrint" 
        class="px-5 py-2.5 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-md shadow-sky-600/20"
      >
        <Printer class="w-4 h-4" /> Cetak Lembar Transaksi
      </button>
    </div>

    <!-- Official A4 Document Sheet Container -->
    <div class="max-w-4xl mx-auto bg-white p-8 sm:p-12 rounded-2xl shadow-xl print:shadow-none print:rounded-none print:p-0 border print:border-0 border-slate-200">
      
      <!-- Kop Surat Resmi FT UNSOED -->
      <div class="border-b-4 border-double border-slate-900 pb-4 mb-6">
        <div class="flex items-center gap-4">
          <img src="/image/SIKARALOGO.png" alt="Logo UNSOED" class="w-20 h-20 object-contain shrink-0" />
          <div class="text-center flex-1 space-y-0.5">
            <h3 class="text-xs sm:text-sm font-bold uppercase tracking-wider text-slate-900">KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI</h3>
            <h2 class="text-sm sm:text-base font-black uppercase text-slate-950">UNIVERSITAS JENDERAL SOEDIRMAN</h2>
            <h1 class="text-base sm:text-lg font-black uppercase text-sky-900">FAKULTAS TEKNIK</h1>
            <p class="text-[11px] text-slate-600 leading-tight">
              Jl. Mayjen HR. Boenyamin No. 708 Purwokerto 53122 | Telp. (0281) 638792 | Web: ft.unsoed.ac.id
            </p>
          </div>
        </div>
      </div>

      <!-- Document Title -->
      <div class="text-center space-y-1 mb-6">
        <h2 class="text-base sm:text-lg font-black uppercase text-slate-900 tracking-wide underline underline-offset-4">
          LEMBAR BUKTI TRANSAKSI &amp; PEMERIKSAAN ANGGARAN
        </h2>
        <p class="text-xs font-sans font-bold text-slate-700">
          No FRA / Bukti: <span class="font-mono text-sky-900">{{ submission.evidence_number || submission.submission_number }}</span>
        </p>
        <div class="inline-block px-3 py-0.5 bg-slate-100 border border-slate-300 rounded text-[10px] text-slate-600 font-semibold mt-1">
          * Lembar pencatatan &amp; verifikasi internal SIKARA FT UNSOED (Bukan bukti/dokumen pencairan kas resmi KPPN)
        </div>
      </div>

      <!-- Transaction Data Grid (9 Mandatory Fields) -->
      <div class="grid grid-cols-2 gap-4 text-xs mb-6 p-4 bg-slate-50 border border-slate-200 rounded-xl">
        <div class="space-y-2">
          <!-- 1. No FRA / Bukti -->
          <div class="flex">
            <span class="w-36 text-slate-500 font-medium">1. No FRA / Bukti</span>
            <span class="font-bold text-slate-900">: {{ submission.evidence_number || submission.submission_number }}</span>
          </div>

          <!-- 2. Tanggal -->
          <div class="flex">
            <span class="w-36 text-slate-500 font-medium">2. Tanggal Transaksi</span>
            <span class="font-bold text-slate-900">: {{ submission.transaction_date ? new Date(submission.transaction_date).toLocaleDateString('id-ID', { dateStyle: 'long' }) : new Date(submission.created_at).toLocaleDateString('id-ID', { dateStyle: 'long' }) }}</span>
          </div>

          <!-- 3. PTK -->
          <div class="flex">
            <span class="w-36 text-slate-500 font-medium">3. Pengusul (PTK)</span>
            <span class="font-bold text-slate-900">: {{ submission.creator?.name || 'Operator PTK' }} ({{ submission.creator?.nip || '-' }})</span>
          </div>

          <!-- 4. Jurusan -->
          <div class="flex">
            <span class="w-36 text-slate-500 font-medium">4. Jurusan / Unit</span>
            <span class="font-bold text-slate-900">: {{ submission.department?.name }} ({{ submission.department?.code }})</span>
          </div>

          <!-- 5. Status -->
          <div class="flex">
            <span class="w-36 text-slate-500 font-medium">5. Status Transaksi</span>
            <span class="font-bold text-slate-900">: 
              <span class="px-2 py-0.5 rounded text-[11px] font-black uppercase border border-slate-300 bg-white">
                {{ submission.status }}
              </span>
            </span>
          </div>
        </div>

        <div class="space-y-2">
          <!-- 6. No RBA / Budget Line -->
          <div class="flex">
            <span class="w-36 text-slate-500 font-medium">6. No RBA / Pos Akun</span>
            <span class="font-mono font-bold text-slate-900">: 
              {{ submission.budget_line?.rba_line_no ? 'Line #' + submission.budget_line.rba_line_no : (submission.budget_bucket?.account_code || '-') }}
            </span>
          </div>

          <!-- 7. Uraian RBA -->
          <div class="flex">
            <span class="w-36 text-slate-500 font-medium">7. Uraian RBA</span>
            <span class="font-medium text-slate-900">: 
              {{ submission.budget_line?.account?.name || submission.budget_bucket?.account_name || 'Alokasi Pagu Jurusan' }}
            </span>
          </div>

          <!-- 8. Uraian Aktual -->
          <div class="flex">
            <span class="w-36 text-slate-500 font-medium">8. Uraian Aktual</span>
            <span class="font-bold text-slate-900 leading-snug">: {{ submission.title }}</span>
          </div>

          <!-- 9. Nominal -->
          <div class="flex">
            <span class="w-36 text-slate-500 font-medium">9. Nominal Pengajuan</span>
            <span class="font-black text-sky-950 font-sans text-sm">: {{ formatRupiah(submission.amount) }}</span>
          </div>

          <!-- Penerima Pembayaran -->
          <div class="flex">
            <span class="w-36 text-slate-500 font-medium">Penerima / Toko</span>
            <span class="font-medium text-slate-800">: {{ submission.beneficiary_name || '-' }}</span>
          </div>
        </div>
      </div>

      <!-- Items Table -->
      <div class="mb-6 space-y-2">
        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900">Rincian Komponen Belanja / Barang:</h4>
        <table class="w-full text-xs text-left border-collapse border border-slate-300">
          <thead>
            <tr class="bg-slate-100 text-slate-900 font-bold uppercase text-[10px]">
              <th class="py-2 px-3 border border-slate-300 text-center w-10">No</th>
              <th class="py-2 px-3 border border-slate-300">Uraian Spesifikasi / Nama Item</th>
              <th class="py-2 px-3 border border-slate-300 text-center w-16">Qty</th>
              <th class="py-2 px-3 border border-slate-300 text-right">Harga Satuan (Rp)</th>
              <th class="py-2 px-3 border border-slate-300 text-right">Total Harga (Rp)</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(itm, idx) in submission.items" :key="itm.id">
              <td class="py-2 px-3 border border-slate-300 text-center font-sans font-medium">{{ idx + 1 }}</td>
              <td class="py-2 px-3 border border-slate-300 font-medium">{{ itm.item_name }}</td>
              <td class="py-2 px-3 border border-slate-300 text-center font-sans font-bold">{{ itm.quantity }}</td>
              <td class="py-2 px-3 border border-slate-300 text-right font-sans">{{ formatRupiah(itm.unit_price) }}</td>
              <td class="py-2 px-3 border border-slate-300 text-right font-sans font-bold">{{ formatRupiah(itm.total_price) }}</td>
            </tr>
            <tr v-if="!submission.items || submission.items.length === 0">
              <td class="py-2 px-3 border border-slate-300 text-center font-sans font-medium">1</td>
              <td class="py-2 px-3 border border-slate-300 font-medium">{{ submission.title }}</td>
              <td class="py-2 px-3 border border-slate-300 text-center font-sans font-bold">1</td>
              <td class="py-2 px-3 border border-slate-300 text-right font-sans">{{ formatRupiah(submission.amount) }}</td>
              <td class="py-2 px-3 border border-slate-300 text-right font-sans font-bold">{{ formatRupiah(submission.amount) }}</td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="bg-slate-100 font-bold">
              <td colspan="4" class="py-2 px-3 border border-slate-300 text-right uppercase text-[10px]">Total Nilai Transaksi:</td>
              <td class="py-2 px-3 border border-slate-300 text-right font-sans text-sm text-slate-950 font-black">{{ formatRupiah(submission.amount) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Notes / Justification -->
      <div v-if="submission.notes" class="mb-8 p-3.5 bg-slate-50 border border-slate-200 rounded-xl text-xs space-y-1">
        <span class="font-bold text-slate-800 block text-[11px]">Justifikasi Urgensi / Catatan Transaksi:</span>
        <p class="text-slate-700 leading-relaxed">{{ submission.notes }}</p>
      </div>

      <!-- Configurable Signature Blocks (Blok Tanda Tangan Konfiguratif) -->
      <div class="mt-8 pt-4 border-t border-slate-200 grid grid-cols-2 gap-8 text-xs break-inside-avoid">
        
        <!-- Left Signer Block (Diusulkan oleh) -->
        <div class="text-center space-y-2">
          <p class="font-medium text-slate-700">
            Diusulkan oleh,<br>
            <strong>{{ signers?.submitter_title || 'Pengolah Transaksi Keuangan (PTK)' }}</strong>
          </p>
          <div class="h-16 flex items-center justify-center">
            <div class="p-2 border border-slate-200 bg-slate-50/50 rounded-xl text-[10px] text-slate-400 font-sans">
              [Tanda Tangan Pengusul Unit]
            </div>
          </div>
          <p class="font-bold text-slate-900 underline">{{ signers?.submitter_name || submission.creator?.name || 'Operator PTK' }}</p>
          <p class="text-[11px] text-slate-500">NIP. {{ signers?.submitter_nip || submission.creator?.nip || '..........................' }}</p>
          <p class="text-[10px] text-slate-400">Unit: {{ submission.department?.name }}</p>
        </div>

        <!-- Right Signer Block (Diverifikasi / Diperiksa oleh) -->
        <div class="text-center space-y-2">
          <p class="font-medium text-slate-700">
            {{ signers?.city || 'Purwokerto' }}, {{ signers?.print_date || signoffDate || new Date().toLocaleDateString('id-ID') }}<br>
            <strong>{{ signers?.verifier_title || 'Verifikator PTU / Bendahara' }}</strong>
          </p>
          
          <!-- Visual Signature Stamp Box -->
          <div class="py-2 px-4 border-2 border-sky-600 bg-sky-50/30 rounded-2xl mx-auto inline-block text-left shadow-sm relative overflow-hidden">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-sky-700 text-white flex items-center justify-center shrink-0">
                <ShieldCheck class="w-6 h-6" />
              </div>
              <div class="space-y-0.5 text-[10px]">
                <div class="font-black text-sky-950 text-xs uppercase tracking-tight">VERIFIKASI SISTEM SIKARA</div>
                <div class="font-bold text-slate-900">{{ signers?.verifier_name || signoffUser?.name || 'Verifikator Keuangan' }}</div>
                <div class="text-slate-600 font-sans">NIP. {{ signers?.verifier_nip || signoffUser?.nip || '..........................' }}</div>
                <div class="text-sky-800 font-sans text-[9px] font-semibold truncate max-w-[200px]" :title="submission.electronic_signoff_hash">
                  Hash: {{ submission.electronic_signoff_hash ? submission.electronic_signoff_hash.substring(0, 16) + '...' : 'RBC-VERIFIED' }}
                </div>
              </div>
            </div>
          </div>

          <p class="font-bold text-slate-900 underline mt-1">{{ signers?.verifier_name || signoffUser?.name || 'Verifikator Keuangan' }}</p>
          <p class="text-[11px] text-slate-500">Fakultas Teknik UNSOED</p>
        </div>
      </div>

      <!-- Disclaimer & Footer Security Note -->
      <div class="mt-8 pt-4 border-t border-slate-200 text-center text-[10px] text-slate-400 font-sans space-y-1">
        <div>Dokumen cetak ini adalah bukti penatausahaan anggaran internal Fakultas Teknik Universitas Jenderal Soedirman.</div>
        <div class="font-bold text-slate-500">PERHATIAN: Dokumen ini bukan merupakan Surat Perintah Pencairan Dana (SP2D) atau bukti pencairan kas resmi.</div>
      </div>
    </div>
  </div>
</template>

<style>
@media print {
  @page {
    size: A4 portrait;
    margin: 1.5cm;
  }
  body {
    background-color: #ffffff !important;
  }
}
</style>
