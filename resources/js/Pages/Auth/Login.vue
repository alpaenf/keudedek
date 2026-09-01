<script setup>
import { ref, computed } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import { ChevronRight, LogIn, Sparkles, KeyRound, ArrowLeft, Building2, ShieldCheck, UserCheck } from 'lucide-vue-next';

const props = defineProps({
  users: Array,
});

const activeTab = ref('quick'); // default to quick role switcher for convenience
const selectedDeptTab = ref('ALL');

const loginForm = useForm({
  email: '',
  password: '',
  remember: false,
});

const submitFormalLogin = () => {
  loginForm.post('/login');
};

const loginAs = (userId) => {
  router.post(`/login/${userId}`);
};

const filteredUsers = computed(() => {
  if (!props.users) return [];
  if (selectedDeptTab.value === 'ALL') return props.users;
  if (selectedDeptTab.value === 'FAKULTAS') {
    return props.users.filter(u => u.role === 'ADMIN' || u.role === 'DEKAN' || u.role === 'WAKIL_DEKAN' || u.role === 'KABAG' || u.role === 'PTU' || u.role === 'KETUA_PTK');
  }
  return props.users.filter(u => u.department?.code === selectedDeptTab.value);
});
</script>

<template>
  <div class="bg-slate-50 min-h-screen flex items-center justify-center p-4 sm:p-6 text-slate-800 font-sans antialiased selection:bg-sky-600 selection:text-white">
    <!-- Wide & Compact Landscape Card -->
    <div class="max-w-3xl w-full bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-xl relative">
      
      <!-- Top Navigation & Tab Bar -->
      <div class="flex flex-wrap items-center justify-between gap-3 mb-6 pb-4 border-b border-slate-100">
        <Link href="/" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-sky-600 transition">
          <ArrowLeft class="w-4 h-4" /> Kembali ke Beranda
        </Link>

        <!-- Compact Tab Switcher -->
        <div class="flex bg-slate-100 p-1 rounded-xl border border-slate-200">
          <button 
            @click="activeTab = 'quick'" 
            :class="['px-3.5 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1.5 transition', activeTab === 'quick' ? 'bg-sky-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900']"
          >
            <Sparkles class="w-3.5 h-3.5" /> Switch Demo Role (16 Akun)
          </button>
          <button 
            @click="activeTab = 'formal'" 
            :class="['px-3.5 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1.5 transition', activeTab === 'formal' ? 'bg-sky-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900']"
          >
            <KeyRound class="w-3.5 h-3.5" /> Login Form Email
          </button>
        </div>
      </div>

      <!-- Header -->
      <div class="flex items-center gap-4 mb-6">
        <img src="/image/SIKARALOGO.png" alt="Logo SIKARA FT UNSOED" class="w-12 h-12 object-contain rounded-xl shadow-sm shrink-0" />
        <div>
          <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-tight">SIKARA FT UNSOED</h1>
          <p class="text-xs text-sky-700 font-semibold mt-0.5">Sistem Informasi Kendali Anggaran dan Realisasi — Fakultas Teknik UNSOED</p>
        </div>
      </div>

      <!-- TAB 1: QUICK ROLE SWITCHER -->
      <div v-if="activeTab === 'quick'" class="space-y-4">
        <!-- Sub-filter Pills for Faculty & 5 Jurusan -->
        <div class="flex flex-wrap items-center gap-1.5 bg-slate-50 p-1.5 rounded-2xl border border-slate-200 text-xs">
          <button 
            @click="selectedDeptTab = 'ALL'"
            :class="['px-3 py-1.5 rounded-xl font-bold transition text-[11px]', selectedDeptTab === 'ALL' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-200']"
          >
            Semua (16 Akun)
          </button>
          <button 
            @click="selectedDeptTab = 'FAKULTAS'"
            :class="['px-3 py-1.5 rounded-xl font-bold transition text-[11px]', selectedDeptTab === 'FAKULTAS' ? 'bg-purple-700 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-200']"
          >
            Level Fakultas (6 Role)
          </button>
          <button 
            v-for="code in ['JTIF', 'JTS', 'JTE', 'JTG', 'JTI']" 
            :key="code"
            @click="selectedDeptTab = code"
            :class="['px-3 py-1.5 rounded-xl font-bold transition text-[11px]', selectedDeptTab === code ? 'bg-sky-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-200']"
          >
            {{ code }}
          </button>
        </div>

        <!-- 2-Column User Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 max-h-80 overflow-y-auto pr-1">
          <button 
            v-for="usr in filteredUsers" 
            :key="usr.id" 
            @click="loginAs(usr.id)" 
            class="p-3 bg-slate-50 hover:bg-sky-50 border border-slate-200 hover:border-sky-300 rounded-2xl flex items-center justify-between transition group text-left shadow-sm"
          >
            <div class="min-w-0 pr-2">
              <div class="flex items-center gap-1.5">
                <span class="font-bold text-xs text-slate-900 group-hover:text-sky-700 truncate block">{{ usr.name }}</span>
              </div>
              <div class="flex items-center gap-1.5 mt-1">
                <span class="px-2 py-0.5 bg-sky-100 text-sky-800 border border-sky-200 text-[9px] font-black rounded uppercase">
                  {{ usr.role }}
                </span>
                <span class="text-[10px] text-slate-500 truncate block">{{ usr.department?.code ?? 'FAKULTAS' }}</span>
              </div>
            </div>
            <ChevronRight class="w-4 h-4 text-slate-400 group-hover:text-sky-600 group-hover:translate-x-0.5 transition shrink-0" />
          </button>
        </div>
      </div>

      <!-- TAB 2: FORMAL LOGIN FORM -->
      <div v-else>
        <form @submit.prevent="submitFormalLogin" class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Email Pengguna</label>
              <input 
                v-model="loginForm.email" 
                type="email" 
                required 
                placeholder="admin@ft.unsoed.ac.id" 
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none"
              >
              <div v-if="loginForm.errors.email" class="text-rose-600 text-[11px] mt-1 font-medium">{{ loginForm.errors.email }}</div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-1.5">Kata Sandi (Password)</label>
              <input 
                v-model="loginForm.password" 
                type="password" 
                required 
                placeholder="••••••••" 
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none"
              >
              <div v-if="loginForm.errors.password" class="text-rose-600 text-[11px] mt-1 font-medium">{{ loginForm.errors.password }}</div>
            </div>
          </div>

          <div class="flex items-center justify-between text-xs text-slate-600 pt-0.5">
            <label class="flex items-center gap-2 cursor-pointer select-none">
              <input v-model="loginForm.remember" type="checkbox" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
              <span class="font-medium">Ingat saya di perangkat ini</span>
            </label>
            <span class="text-slate-400 text-[11px]">Default password: <span class="font-sans font-semibold text-slate-600">password</span></span>
          </div>

          <button 
            type="submit" 
            :disabled="loginForm.processing" 
            class="w-full py-3 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 shadow-md shadow-sky-600/20 disabled:opacity-50 mt-2"
          >
            <LogIn class="w-4 h-4" /> Masuk ke Aplikasi
          </button>
        </form>
      </div>

      <!-- Footer -->
      <div class="text-center text-[11px] text-slate-400 mt-6 pt-3 border-t border-slate-100">
        SIKARA FT UNSOED &copy; 2026 &bull; Sistem Informasi Kendali Anggaran dan Realisasi
      </div>
    </div>
  </div>
</template>
