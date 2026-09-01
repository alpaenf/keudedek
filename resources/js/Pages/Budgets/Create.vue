<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { Wallet, ArrowLeft, PlusCircle } from '@lucide/vue';

const props = defineProps({
  departments: Array,
  fundingSources: Array,
  fiscalYears: Array,
  activeFiscalYear: Object,
});

const form = useForm({
  fiscal_year_id: props.activeFiscalYear?.id || (props.fiscalYears[0]?.id || ''),
  department_id: props.departments[0]?.id || '',
  funding_source_id: props.fundingSources[0]?.id || '',
  account_code: '',
  account_name: '',
  initial_budget: 0,
});

const displayInitialBudget = ref('');

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

const onBudgetInput = (e) => {
  const raw = e.target.value.replace(/\D/g, '');
  form.initial_budget = raw ? parseInt(raw, 10) : 0;
  displayInitialBudget.value = formatDots(raw);
};

const submitForm = () => {
  form.post('/budgets');
};
</script>

<template>
  <AppLayout title="Tambah Pos Pagu Anggaran Baru">
    <div class="max-w-3xl mx-auto mb-8">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <Link href="/budgets" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-sky-600 mb-2 transition">
            <ArrowLeft class="w-4 h-4" /> Kembali ke Daftar Pagu
          </Link>
          <h3 class="text-xl font-bold text-slate-900 flex items-center gap-2">
            <Wallet class="w-6 h-6 text-sky-600" />
            Tambah Alokasi Pos Pagu Anggaran Baru
          </h3>
          <p class="text-xs text-slate-500 mt-1">Daftarkan mata anggaran baru untuk unit jurusan berdasarkan ketetapan DIPA / RKAT</p>
        </div>
      </div>

      <!-- Card Form -->
      <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm">
        <form @submit.prevent="submitForm" class="space-y-6">
          <!-- Row 1: Tahun & Jurusan -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tahun Anggaran</label>
              <select 
                v-model="form.fiscal_year_id" 
                required 
                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold text-slate-900 focus:bg-white focus:ring-2 focus:ring-sky-500"
              >
                <option v-for="fy in fiscalYears" :key="fy.id" :value="fy.id">
                  TA {{ fy.year }} ({{ fy.status }})
                </option>
              </select>
              <div v-if="form.errors.fiscal_year_id" class="text-rose-600 text-[11px] mt-1 font-medium">{{ form.errors.fiscal_year_id }}</div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Unit Kerja / Jurusan Pemilik Pagu</label>
              <select 
                v-model="form.department_id" 
                required 
                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold text-slate-900 focus:bg-white focus:ring-2 focus:ring-sky-500"
              >
                <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                  {{ dept.code }} &mdash; {{ dept.name }}
                </option>
              </select>
              <div v-if="form.errors.department_id" class="text-rose-600 text-[11px] mt-1 font-medium">{{ form.errors.department_id }}</div>
            </div>
          </div>

          <!-- Row 2: Sumber Dana & Kode Akun -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Sumber Dana</label>
              <select 
                v-model="form.funding_source_id" 
                required 
                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold text-slate-900 focus:bg-white focus:ring-2 focus:ring-sky-500"
              >
                <option v-for="fs in fundingSources" :key="fs.id" :value="fs.id">
                  {{ fs.code }} &mdash; {{ fs.name }}
                </option>
              </select>
              <div v-if="form.errors.funding_source_id" class="text-rose-600 text-[11px] mt-1 font-medium">{{ form.errors.funding_source_id }}</div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Kode Mata Anggaran (Akun)</label>
              <input 
                v-model="form.account_code" 
                type="text" 
                required 
                placeholder="Contoh: 521111 / 521211" 
                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-sans font-bold text-slate-900 focus:bg-white focus:ring-2 focus:ring-sky-500"
              />
              <div v-if="form.errors.account_code" class="text-rose-600 text-[11px] mt-1 font-medium">{{ form.errors.account_code }}</div>
            </div>
          </div>

          <!-- Row 3: Nama Akun / Pos -->
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Pos Belanja / Mata Anggaran</label>
            <input 
              v-model="form.account_name" 
              type="text" 
              required 
              placeholder="Contoh: Belanja Bahan Praktikum & Operasional Lab" 
              class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 focus:bg-white focus:ring-2 focus:ring-sky-500"
            />
            <div v-if="form.errors.account_name" class="text-rose-600 text-[11px] mt-1 font-medium">{{ form.errors.account_name }}</div>
          </div>

          <!-- Row 4: Nominal Pagu Awal -->
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Alokasi Pagu Awal (Rp)</label>
            <input 
              type="text" 
              :value="displayInitialBudget" 
              @input="onBudgetInput" 
              required 
              placeholder="Contoh: 850.000.000" 
              class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-sans font-bold text-slate-900 focus:bg-white focus:ring-2 focus:ring-sky-500"
            />
            <div v-if="form.initial_budget" class="mt-2 p-2.5 bg-sky-50 border border-sky-200 rounded-xl text-xs font-semibold text-sky-800 flex items-center justify-between">
              <span>{{ getTerbilang(form.initial_budget) }}</span>
            </div>
            <div v-if="form.errors.initial_budget" class="text-rose-600 text-[11px] mt-1 font-medium">{{ form.errors.initial_budget }}</div>
          </div>

          <!-- Submit Actions -->
          <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
            <Link 
              href="/budgets" 
              class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition"
            >
              Batal
            </Link>
            <button 
              type="submit" 
              :disabled="form.processing" 
              class="px-6 py-2.5 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-sky-600/25 disabled:opacity-50 flex items-center gap-2"
            >
              <PlusCircle class="w-4 h-4" /> Simpan Pos Pagu Baru
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
