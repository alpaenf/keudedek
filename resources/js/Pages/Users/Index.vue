<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { Plus, Search, UserPlus, Trash2, Edit } from '@lucide/vue';

const props = defineProps({
  users: Object,
  departments: Array,
  roles: Array,
  filters: Object,
});

const search = ref(props.filters?.search || '');
const role = ref(props.filters?.role || '');
const departmentId = ref(props.filters?.department_id || '');

const handleFilter = () => {
  router.get('/users', {
    search: search.value,
    role: role.value,
    department_id: departmentId.value,
  }, { preserveState: true });
};

const deleteUser = (user) => {
  if (confirm(`Apakah Anda yakin ingin menghapus akun pengguna "${user.name}"?`)) {
    router.delete(`/users/${user.id}`);
  }
};

const getRoleBadge = (r) => {
  switch(r) {
    case 'ADMIN': return 'bg-slate-900 text-white border-slate-900';
    case 'DEKAN': return 'bg-purple-50 text-purple-700 border-purple-200';
    case 'WD': return 'bg-indigo-50 text-indigo-700 border-indigo-200';
    case 'KABAG': return 'bg-sky-100 text-sky-800 border-sky-300';
    case 'PTU': return 'bg-emerald-50 text-emerald-700 border-emerald-200';
    case 'KAJUR': return 'bg-amber-50 text-amber-700 border-amber-200';
    default: return 'bg-slate-100 text-slate-800 border-slate-300';
  }
};
</script>

<template>
  <AppLayout title="Kelola Pengguna Sistem">
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm mb-6 flex flex-wrap items-center justify-between gap-4">
      <div>
        <h3 class="font-bold text-slate-900 text-base">User Management Module</h3>
        <p class="text-xs text-slate-500">Kelola akun dan perizinan akses per role (PTK, KAJUR, PTU, KABAG, WD, DEKAN)</p>
      </div>

      <div class="flex items-center gap-3">
        <div class="relative">
          <input v-model="search" @keyup.enter="handleFilter" type="text" placeholder="Cari Nama / Email..." class="pl-9 pr-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500 w-56" />
          <Search class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" />
        </div>

        <select v-model="role" @change="handleFilter" class="px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700">
          <option value="">Semua Role</option>
          <option value="ADMIN">ADMIN</option>
          <option v-for="r in roles" :key="r" :value="r">{{ r }}</option>
        </select>

        <select v-model="departmentId" @change="handleFilter" class="px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700">
          <option value="">Semua Unit</option>
          <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.code }}</option>
        </select>

        <Link href="/users/create" class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded-lg text-xs font-semibold flex items-center gap-2 transition shadow-sm">
          <UserPlus class="w-4 h-4" />
          Tambah Akun User
        </Link>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
              <th class="py-3.5 px-6">Nama Pengguna</th>
              <th class="py-3.5 px-6">Email / Username</th>
              <th class="py-3.5 px-6">Role Akses</th>
              <th class="py-3.5 px-6">Unit / Jurusan</th>
              <th class="py-3.5 px-6 text-center">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-slate-700">
            <tr v-for="usr in users.data" :key="usr.id" class="hover:bg-slate-50/80 transition">
              <td class="py-4 px-6 font-bold text-slate-900">{{ usr.name }}</td>
              <td class="py-4 px-6 font-mono text-sky-700">{{ usr.email }}</td>
              <td class="py-4 px-6">
                <span :class="['px-2.5 py-1 rounded-full text-[10px] font-extrabold border', getRoleBadge(usr.role)]">{{ usr.role }}</span>
              </td>
              <td class="py-4 px-6 font-medium text-slate-800">{{ usr.department?.name ?? 'Fakultas Teknik' }} ({{ usr.department?.code ?? 'FT' }})</td>
              <td class="py-4 px-6 text-center">
                <div class="flex items-center justify-center gap-2">
                  <Link :href="`/users/${usr.id}/edit`" class="p-1.5 bg-sky-50 hover:bg-sky-100 border border-sky-200 text-sky-700 rounded-lg transition" title="Edit Akun">
                    <Edit class="w-4 h-4" />
                  </Link>
                  <!-- Red reserved for delete user action -->
                  <button v-if="usr.id !== $page.props.auth?.user?.id" @click="deleteUser(usr)" class="p-1.5 bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-600 rounded-lg transition" title="Hapus Akun">
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="users.data.length === 0">
              <td colspan="5" class="py-8 text-center text-slate-400">Tidak ada pengguna yang ditemukan.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="users.links && users.links.length > 3" class="p-4 border-t border-slate-100 flex items-center justify-between">
        <span class="text-xs text-slate-500">
          Menampilkan {{ users.from ?? 0 }} - {{ users.to ?? 0 }} dari {{ users.total }} total data
        </span>
        <div class="flex items-center gap-1">
          <Link
            v-for="(link, i) in users.links"
            :key="i"
            :href="link.url || '#'"
            v-html="link.label"
            :class="[
              'px-3 py-1.5 rounded-lg text-xs font-semibold transition',
              link.active ? 'bg-sky-600 text-white' : 'bg-slate-50 text-slate-700 hover:bg-slate-100 border border-slate-200',
              !link.url ? 'opacity-40 cursor-not-allowed' : ''
            ]"
          />
        </div>
      </div>
    </div>
  </AppLayout>
</template>
