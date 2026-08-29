<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { UserPlus } from '@lucide/vue';

const props = defineProps({
  departments: Array,
  roles: Array,
});

const form = useForm({
  name: '',
  email: '',
  password: '',
  department_id: props.departments[0]?.id || '',
  role: props.roles[0] || 'PTK',
});

const getRoleLabel = (r) => {
  switch (r) {
    case 'PTK': return 'Operator Unit (PTK)';
    case 'KAJUR': return 'Ketua Jurusan (KAJUR)';
    case 'PTU': return 'Reviewer Keuangan (PTU)';
    case 'KABAG': return 'Kepala Bagian Keuangan (KABAG)';
    case 'WD': return 'Wakil Dekan II (WD)';
    case 'DEKAN': return 'Dekan Fakultas Teknik (Pimpinan Utama)';
    case 'ADMIN': return 'Super Administrator';
    default: return r;
  }
};

const submitForm = () => {
  form.post('/users');
};
</script>

<template>
  <AppLayout title="Tambah Akun Pengguna Baru">
    <div class="max-w-2xl mx-auto bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
      <div class="border-b border-slate-100 pb-4 mb-6 flex items-center justify-between">
        <div>
          <h3 class="text-lg font-bold text-slate-900">Form Tambah Akun Pengguna Per Role</h3>
          <p class="text-xs text-slate-500">Buat akun untuk PTK, KAJUR, PTU, KABAG, Wakil Dekan, atau Dekan</p>
        </div>
        <Link href="/users" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition">
          Batal
        </Link>
      </div>

      <form @submit.prevent="submitForm" class="space-y-5">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap Pengguna</label>
          <input v-model="form.name" type="text" required placeholder="Contoh: Prof. Dr. Ir. Ahmad Subagyo, M.T." class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs text-slate-800 focus:ring-2 focus:ring-sky-500">
          <div v-if="form.errors.name" class="text-rose-600 text-[11px] mt-1">{{ form.errors.name }}</div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Email (Username Login)</label>
          <input v-model="form.email" type="email" required placeholder="dekan@ft.unsoed.ac.id" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs text-slate-800 focus:ring-2 focus:ring-sky-500">
          <div v-if="form.errors.email" class="text-rose-600 text-[11px] mt-1">{{ form.errors.email }}</div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Kata Sandi (Password)</label>
          <input v-model="form.password" type="password" required placeholder="Minimal 6 karakter" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs text-slate-800 focus:ring-2 focus:ring-sky-500">
          <div v-if="form.errors.password" class="text-rose-600 text-[11px] mt-1">{{ form.errors.password }}</div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Role Perizinan Akses</label>
            <select v-model="form.role" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:ring-2 focus:ring-sky-500">
              <option v-for="r in roles" :key="r" :value="r">
                {{ r }} - {{ getRoleLabel(r) }}
              </option>
            </select>
            <p class="text-[11px] text-slate-400 mt-1 italic">* Role Admin tidak dapat dibuat secara manual melalui form ini.</p>
            <div v-if="form.errors.role" class="text-rose-600 text-[11px] mt-1">{{ form.errors.role }}</div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Unit / Jurusan Penugasan</label>
            <select v-model="form.department_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs text-slate-800 focus:ring-2 focus:ring-sky-500">
              <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                {{ dept.code }} - {{ dept.name }}
              </option>
            </select>
            <div v-if="form.errors.department_id" class="text-rose-600 text-[11px] mt-1">{{ form.errors.department_id }}</div>
          </div>
        </div>

        <div class="pt-4 flex justify-end">
          <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-semibold flex items-center gap-2 transition disabled:opacity-50">
            <UserPlus class="w-4 h-4" />
            Simpan & Buat Akun
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
