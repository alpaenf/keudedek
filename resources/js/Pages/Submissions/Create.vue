<script setup>
import { ref, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { Plus, Trash2 } from '@lucide/vue';

const props = defineProps({
  departments: Array,
  buckets: Array,
});

const form = useForm({
  title: '',
  department_id: props.departments[0]?.id || '',
  budget_bucket_id: props.buckets[0]?.id || '',
  amount: 0,
  notes: '',
  items: [
    { item_name: '', quantity: 1, unit_price: 0, display_price: '' },
  ],
});

// String display for total nominal input with dots
const displayAmount = ref('');

const formatDots = (val) => {
  if (!val && val !== 0) return '';
  const numStr = String(val).replace(/\D/g, '');
  return numStr.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
};

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

const onAmountInput = (e) => {
  const raw = e.target.value.replace(/\D/g, '');
  form.amount = raw ? parseInt(raw, 10) : 0;
  displayAmount.value = formatDots(raw);
};

const onItemPriceInput = (item, e) => {
  const raw = e.target.value.replace(/\D/g, '');
  item.unit_price = raw ? parseInt(raw, 10) : 0;
  item.display_price = formatDots(raw);
  recalculateTotalFromItems();
};

const recalculateTotalFromItems = () => {
  const total = form.items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
  if (total > 0) {
    form.amount = total;
    displayAmount.value = formatDots(total);
  }
};

const addItem = () => {
  form.items.push({ item_name: '', quantity: 1, unit_price: 0, display_price: '' });
};

const removeItem = (index) => {
  if (form.items.length > 1) {
    form.items.splice(index, 1);
    recalculateTotalFromItems();
  }
};

const submitForm = () => {
  form.post('/submissions');
};

const formatRupiah = (val) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0);
};
</script>

<template>
  <AppLayout title="Buat Pengajuan Baru">
    <div class="max-w-4xl mx-auto bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
      <div class="border-b border-slate-100 pb-4 mb-6 flex items-center justify-between">
        <div>
          <h3 class="text-lg font-bold text-slate-900">Form Pengajuan Kegiatan & Belanja</h3>
          <p class="text-xs text-slate-500">Lengkapi data pengajuan dan rincian belanja di bawah ini</p>
        </div>
        <Link href="/submissions" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition">
          Batal
        </Link>
      </div>

      <form @submit.prevent="submitForm" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Judul / Nama Kegiatan</label>
            <input v-model="form.title" type="text" required placeholder="Contoh: Pengadaan Bahan Uji Laboratorium..." class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs text-slate-900 focus:ring-2 focus:ring-sky-500">
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Unit / Jurusan Penanggung Jawab</label>
            <select v-model="form.department_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs text-slate-900 focus:ring-2 focus:ring-sky-500">
              <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                {{ dept.code }} - {{ dept.name }}
              </option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Pos Pagu Anggaran (Budget Bucket)</label>
            <select v-model="form.budget_bucket_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs text-slate-900 focus:ring-2 focus:ring-sky-500">
              <option v-for="bucket in buckets" :key="bucket.id" :value="bucket.id">
                [{{ bucket.account_code }}] {{ bucket.account_name }} (Saldo: {{ formatRupiah(bucket.available_balance) }})
              </option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Total Nominal Pengajuan (Rp)</label>
            <!-- Real-time thousand dot separator input -->
            <input 
              type="text" 
              :value="displayAmount" 
              @input="onAmountInput" 
              required 
              placeholder="Contoh: 45.000.000" 
              class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs font-bold text-sky-700 focus:ring-2 focus:ring-sky-500 font-mono"
            >
            <!-- Terbilang / Real-time human-readable helper -->
            <div v-if="form.amount" class="mt-1 text-[11px] font-semibold text-sky-700 bg-sky-50 px-2.5 py-1 rounded-md border border-sky-200 flex items-center justify-between">
              <span>{{ getTerbilang(form.amount) }}</span>
            </div>
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan / Keterangan Pendukung</label>
          <textarea v-model="form.notes" rows="2" placeholder="Catatan tambahan..." class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs text-slate-900 focus:ring-2 focus:ring-sky-500"></textarea>
        </div>

        <!-- Rincian Item Belanja -->
        <div class="border-t border-slate-100 pt-6">
          <div class="flex items-center justify-between mb-4">
            <h4 class="font-bold text-slate-900 text-sm">Rincian Item Belanja</h4>
            <button type="button" @click="addItem" class="px-3 py-1 bg-sky-50 text-sky-700 border border-sky-200 rounded-lg text-xs font-semibold flex items-center gap-1 hover:bg-sky-100">
              <Plus class="w-3.5 h-3.5" /> Tambah Item
            </button>
          </div>
          
          <div class="space-y-3">
            <div v-for="(item, idx) in form.items" :key="idx" class="grid grid-cols-12 gap-2.5 sm:gap-3 items-center bg-slate-50 p-3 rounded-xl border border-slate-200">
              <div class="col-span-12 sm:col-span-5">
                <label class="block sm:hidden text-[10px] font-semibold text-slate-500 mb-1">Nama Item</label>
                <input v-model="item.item_name" type="text" required placeholder="Nama item barang/jasa" class="w-full px-3 py-1.5 border border-slate-300 rounded-lg text-xs bg-white text-slate-900">
              </div>
              <div class="col-span-4 sm:col-span-2">
                <label class="block sm:hidden text-[10px] font-semibold text-slate-500 mb-1">Qty</label>
                <input v-model.number="item.quantity" @input="recalculateTotalFromItems" type="number" min="1" required class="w-full px-3 py-1.5 border border-slate-300 rounded-lg text-xs text-center bg-white text-slate-900">
              </div>
              <div class="col-span-6 sm:col-span-4">
                <label class="block sm:hidden text-[10px] font-semibold text-slate-500 mb-1">Harga Satuan (Rp)</label>
                <input 
                  type="text" 
                  :value="item.display_price" 
                  @input="e => onItemPriceInput(item, e)" 
                  required 
                  placeholder="Harga satuan" 
                  class="w-full px-3 py-1.5 border border-slate-300 rounded-lg text-xs bg-white text-slate-900 font-mono font-semibold"
                >
                <div v-if="item.unit_price" class="text-[10px] text-slate-500 mt-0.5 truncate">
                  Subtotal: {{ formatRupiah(item.quantity * item.unit_price) }}
                </div>
              </div>
              <div class="col-span-2 sm:col-span-1 text-center pt-4 sm:pt-0">
                <button type="button" @click="removeItem(idx)" class="p-1.5 text-rose-600 hover:text-rose-800 hover:bg-rose-50 rounded transition" title="Hapus Item">
                  <Trash2 class="w-4 h-4 mx-auto" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="pt-4 flex justify-end">
          <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-semibold transition disabled:opacity-50">
            Simpan Draft Pengajuan
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
