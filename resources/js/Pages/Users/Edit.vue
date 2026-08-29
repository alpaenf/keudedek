<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { Save } from '@lucide/vue';

const props = defineProps({
  user: Object,
  departments: Array,
  roles: Array,
});

const form = useForm({
  name: props.user.name,
  email: props.user.email,
  password: '',
  department_id: props.user.department_id,
  role: props.user.role === 'ADMIN' ? 'KABAG' : props.user.role,
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
  form.put(`/users/${props.user.id}`);
};
</script>

<template>
  <AppLayout :title="`Edit Akun: ${user.name}`">
    <div class="max-w-2xl mx-auto bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
      <div class="border-b border-slate-100 pb-4 mb-6 flex items-center justify-between">
        <div>
          <h3 class="text-lg font-bold text-slate-900">Edit Data Pengguna</h3>
          <p class="text-xs text-slate-500">Perbarui informasi akun, role, atau reset password pengguna</p>
        </div>
        <Link href="/users" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition">
          Batal
        </Link>
      </div>

      <form @submit.prevent="submitForm" class="space-y-5">
        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap Pengguna</label>
          <input v-model="form.name" type="text" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs text-slate-800 focus:ring-2 focus:ring-sky-500">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Email (Username Login)</label>
          <input v-model="form.email" type="email" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs text-slate-800 focus:ring-2 focus:ring-sky-500">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Ubah Kata Sandi (Kosongkan jika tidak diubah)</label>
          <input v-model="form.password" type="password" placeholder="Isi untuk reset password" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs text-slate-800 focus:ring-2 focus:ring-sky-500">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Role Perizinan Akses</label>
            <select v-model="form.role" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:ring-2 focus:ring-sky-500">
              <option v-for="r in roles" :key="r" :value="r">
                {{ r }} - {{ getRoleLabel(r) }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Unit / Jurusan Penugasan</label>
            <select v-model="form.department_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs text-slate-800 focus:ring-2 focus:ring-sky-500">
              <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                {{ dept.code }} - {{ dept.name }}
              </option>
            </select>
          </div>
        </div>

        <div class="pt-4 flex justify-end">
          <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-semibold flex items-center gap-2 transition disabled:opacity-50">
            <Save class="w-4 h-4" />
            Simpan Perubahan
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
