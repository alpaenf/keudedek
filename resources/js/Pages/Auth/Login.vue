<script setup>
import { ref, computed } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import { 
  ChevronRight, 
  LogIn, 
  Search,
  ArrowLeft, 
  Building2, 
  AlertCircle
} from 'lucide-vue-next';

const props = defineProps({
  users: Array,
  isDemoOrLocal: {
    type: Boolean,
    default: false,
  },
});

const activeTab = ref(props.isDemoOrLocal ? 'quick' : 'formal');
const selectedDeptTab = ref('ALL');
const searchQuery = ref('');

const loginForm = useForm({
  login: '',
  password: '',
  remember: false,
});

const submitFormalLogin = () => {
  loginForm.post('/login', {
    preserveScroll: true,
  });
};

const loginAs = (userId) => {
  router.post(`/login/${userId}`);
};

const filteredUsers = computed(() => {
  if (!props.users) return [];
  let list = props.users;

  if (selectedDeptTab.value === 'FAKULTAS') {
    list = list.filter(u => ['ADMIN', 'DEKAN', 'WAKIL_DEKAN', 'WD', 'KABAG', 'PTU', 'BENDAHARA', 'KETUA_PTK'].includes(u.role));
  } else if (selectedDeptTab.value !== 'ALL') {
    list = list.filter(u => u.department?.code === selectedDeptTab.value);
  }

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase().trim();
    list = list.filter(u => 
      u.name.toLowerCase().includes(q) || 
      u.role?.toLowerCase().includes(q) || 
      u.email?.toLowerCase().includes(q) ||
      u.study_program?.name?.toLowerCase().includes(q) ||
      u.department?.name?.toLowerCase().includes(q)
    );
  }

  return list;
});

const getRoleDisplay = (usr) => {
  if (usr.roles && usr.roles.length > 1) {
    return usr.roles.map(r => r.code).join(' & ');
  }
  return usr.role;
};

const getInitials = (name) => {
  if (!name) return 'U';
  const clean = name
    .replace(/\(.*?\)/g, '')
    .replace(/^(Prof\.|Dr\.|Ir\.|Bapak|Ibu)\s+/gi, '')
    .trim();
  const parts = clean.split(/\s+/).filter(Boolean);
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }
  return clean.substring(0, 2).toUpperCase();
};

const getUnitDisplay = (usr) => {
  if (usr.study_program?.name) return usr.study_program.name;
  if (usr.studyProgram?.name) return usr.studyProgram.name;
  if (usr.department?.code === 'FT-UNSOED') return 'Fakultas Teknik';
  return usr.department?.name || 'Fakultas Teknik';
};

const getRoleAvatarStyle = (usr) => {
  const r = usr.role || '';
  if (['ADMIN'].includes(r)) return 'bg-slate-100 text-slate-800 border-slate-200';
  if (['DEKAN', 'WAKIL_DEKAN'].includes(r)) return 'bg-purple-100 text-purple-800 border-purple-200';
  if (['KABAG', 'PTU', 'BENDAHARA'].includes(r)) return 'bg-amber-100 text-amber-800 border-amber-200';
  if (['PTK'].includes(r)) return 'bg-sky-100 text-sky-800 border-sky-200';
  if (['KAJUR'].includes(r)) return 'bg-blue-100 text-blue-800 border-blue-200';
  if (['KAPRODI'].includes(r)) return 'bg-emerald-100 text-emerald-800 border-emerald-200';
  return 'bg-slate-100 text-slate-700 border-slate-200';
};

const getRoleBadgeStyle = (usr) => {
  const r = usr.role || '';
  if (['ADMIN'].includes(r)) return 'bg-slate-50 text-slate-700 border-slate-200';
  if (['DEKAN', 'WAKIL_DEKAN'].includes(r)) return 'bg-purple-50 text-purple-700 border-purple-200';
  if (['KABAG', 'PTU', 'BENDAHARA'].includes(r)) return 'bg-amber-50 text-amber-700 border-amber-200';
  if (['PTK'].includes(r)) return 'bg-sky-50 text-sky-700 border-sky-200';
  if (['KAJUR'].includes(r)) return 'bg-blue-50 text-blue-700 border-blue-200';
  if (['KAPRODI'].includes(r)) return 'bg-emerald-50 text-emerald-700 border-emerald-200';
  return 'bg-slate-50 text-slate-700 border-slate-200';
};
</script>

<template>
  <div class="bg-slate-50/90 min-h-screen flex items-center justify-center p-4 sm:p-6 text-slate-800 font-sans antialiased selection:bg-sky-600 selection:text-white">
    <!-- Fixed Consistent Modal Container (Always max-w-3xl) -->
    <div class="max-w-3xl w-full bg-white p-6 sm:p-8 rounded-3xl border border-slate-200/90 shadow-xl">
      
      <!-- Top Navigation & Tab Switcher -->
      <div class="flex flex-wrap items-center justify-between gap-3 mb-6 pb-4 border-b border-slate-100">
        <Link href="/" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-sky-600 transition">
          <ArrowLeft class="w-4 h-4" /> Kembali ke Beranda
        </Link>

        <!-- Tab Switcher: Form Login & Quick Switcher -->
        <div class="flex bg-slate-100 p-1 rounded-xl border border-slate-200 text-xs">
          <button 
            type="button"
            @click="activeTab = 'formal'" 
            :class="['px-3.5 py-1.5 rounded-lg font-semibold transition', activeTab === 'formal' ? 'bg-white text-slate-900 shadow-xs border border-slate-200/60' : 'text-slate-600 hover:text-slate-900']"
          >
            Form Login
          </button>
          <button 
            v-if="isDemoOrLocal"
            type="button"
            @click="activeTab = 'quick'" 
            :class="['px-3.5 py-1.5 rounded-lg font-semibold transition', activeTab === 'quick' ? 'bg-[#0080d8] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900']"
          >
            Pilih Role ({{ users?.length || 0 }})
          </button>
        </div>
      </div>

      <!-- Header -->
      <div class="flex items-center gap-3.5 mb-6">
        <img src="/image/SIKARALOGO.png" alt="Logo SIKARA FT UNSOED" class="w-11 h-11 object-contain rounded-xl shadow-xs shrink-0" />
        <div>
          <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-tight">SIKARA FT UNSOED</h1>
          <p class="text-xs text-sky-700 font-semibold mt-0.5">Sistem Informasi Kendali Anggaran dan Realisasi — Fakultas Teknik UNSOED</p>
        </div>
      </div>

      <!-- Error Alert Box -->
      <div v-if="loginForm.errors.login || loginForm.errors.password" class="mb-5 p-3.5 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl flex items-start gap-2.5 text-xs">
        <AlertCircle class="w-4 h-4 text-rose-600 shrink-0 mt-0.5" />
        <span class="font-medium">{{ loginForm.errors.login || loginForm.errors.password }}</span>
      </div>

      <!-- HORIZONTAL SLIDER CONTAINER (Clean Slide Transition between Form Login and Quick Role Switcher) -->
      <div class="overflow-hidden relative w-full">
        <div 
          class="flex w-[200%] transition-slider"
          :style="{ transform: activeTab === 'quick' ? 'translateX(-50%)' : 'translateX(0%)' }"
        >
          <!-- SLIDE 1: FORMAL LOGIN (Left) -->
          <div 
            class="w-1/2 shrink-0 transition-opacity duration-300"
            :class="activeTab === 'formal' ? 'opacity-100' : 'opacity-0 pointer-events-none'"
          >
            <form @submit.prevent="submitFormalLogin" class="space-y-4">
              <div class="space-y-3.5">
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">Email / Username Akun</label>
                  <input 
                    v-model="loginForm.login" 
                    type="text" 
                    required 
                    autofocus
                    placeholder="nama@ft.unsoed.ac.id atau admin / dekan / ptk_if" 
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none"
                  >
                </div>

                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-1.5">Password</label>
                  <input 
                    v-model="loginForm.password" 
                    type="password" 
                    required 
                    placeholder="••••••••" 
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-sky-500 focus:outline-none"
                  >
                </div>
              </div>

              <div class="flex items-center justify-between text-xs text-slate-600 pt-1">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                  <input v-model="loginForm.remember" type="checkbox" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                  <span class="font-medium text-xs">Ingat saya di perangkat ini</span>
                </label>
                
                <span v-if="isDemoOrLocal" class="text-slate-400 text-[11px]">
                  Password bawaan demo: <span class="font-mono font-semibold text-slate-700 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">password</span>
                </span>
              </div>

              <button 
                type="submit" 
                :disabled="loginForm.processing" 
                class="w-full py-3 bg-[#0080d8] hover:bg-[#0070be] text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 shadow-md shadow-blue-500/20 disabled:opacity-50 mt-3"
              >
                <LogIn class="w-4 h-4" /> Masuk ke Aplikasi SIKARA
              </button>
            </form>

            <!-- Shortcut info to Quick Switcher -->
            <div v-if="isDemoOrLocal && users && users.length > 0" class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500 bg-slate-50 p-3 rounded-xl border border-slate-200">
              <span>Ingin langsung mencoba dengan peran spesifik tanpa mengetik password?</span>
              <button 
                type="button" 
                @click="activeTab = 'quick'" 
                class="font-bold text-sky-600 hover:text-sky-700 text-xs shrink-0 ml-2 transition"
              >
                Gunakan Quick Role Switcher &rarr;
              </button>
            </div>
          </div>

          <!-- SLIDE 2: QUICK ROLE SWITCHER (Right) -->
          <div 
            class="w-1/2 shrink-0 space-y-3.5 transition-opacity duration-300"
            :class="activeTab === 'quick' ? 'opacity-100' : 'opacity-0 pointer-events-none'"
          >
            <!-- Search & Quick Navigation Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
              <!-- Search Bar -->
              <div class="relative flex-1 max-w-xs sm:max-w-sm">
                <Search class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                <input 
                  v-model="searchQuery" 
                  type="text" 
                  placeholder="Cari nama akun, peran, atau unit..." 
                  class="w-full pl-8 pr-7 py-1.5 bg-slate-50 border border-slate-200/90 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-sky-500 focus:border-sky-500 focus:outline-none transition"
                />
                <button 
                  v-if="searchQuery" 
                  @click="searchQuery = ''" 
                  type="button" 
                  class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs px-1"
                >
                  ✕
                </button>
              </div>

              <!-- Total Count / Quick Status -->
              <span class="text-[11px] font-semibold text-slate-500 self-end sm:self-center">
                Menampilkan <span class="font-bold text-slate-900">{{ filteredUsers.length }}</span> akun
              </span>
            </div>

            <!-- Clean Department / Scope Pills -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 custom-scroll">
              <button 
                type="button"
                @click="selectedDeptTab = 'ALL'"
                :class="[
                  'px-2.5 py-1 rounded-lg font-bold transition text-[11px] whitespace-nowrap',
                  selectedDeptTab === 'ALL' 
                    ? 'bg-slate-900 text-white shadow-xs' 
                    : 'bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200/80'
                ]"
              >
                Semua ({{ users?.length || 0 }})
              </button>
              <button 
                type="button"
                @click="selectedDeptTab = 'FAKULTAS'"
                :class="[
                  'px-2.5 py-1 rounded-lg font-bold transition text-[11px] whitespace-nowrap',
                  selectedDeptTab === 'FAKULTAS' 
                    ? 'bg-purple-700 text-white shadow-xs' 
                    : 'bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200/80'
                ]"
              >
                Level Fakultas
              </button>
              <button 
                type="button"
                v-for="code in ['JTIF', 'JTS', 'JTE', 'JTG', 'JTI']" 
                :key="code"
                @click="selectedDeptTab = code"
                :class="[
                  'px-2.5 py-1 rounded-lg font-bold transition text-[11px] whitespace-nowrap',
                  selectedDeptTab === code 
                    ? 'bg-sky-600 text-white shadow-xs' 
                    : 'bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200/80'
                ]"
              >
                {{ code }}
              </button>
            </div>

            <!-- Fixed Height Account Container (Prevents Container Resizing when filtering) -->
            <div class="h-[290px]">
              <!-- Account List Grid -->
              <div v-if="filteredUsers.length > 0" class="h-full overflow-y-auto pr-1 custom-scroll">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                  <button 
                    v-for="usr in filteredUsers" 
                    :key="usr.id" 
                    type="button"
                    @click="loginAs(usr.id)" 
                    class="p-2.5 sm:p-3 bg-white hover:bg-sky-50/50 border border-slate-200/90 hover:border-sky-300 rounded-xl flex items-center justify-between transition-all duration-150 group text-left shadow-2xs hover:shadow-xs"
                  >
                    <div class="flex items-center gap-2.5 min-w-0 flex-1 pr-2">
                      <!-- Initial Avatar Badge -->
                      <div :class="['w-8 h-8 rounded-lg flex items-center justify-center shrink-0 font-black text-[10.5px] border', getRoleAvatarStyle(usr)]">
                        {{ getInitials(usr.name) }}
                      </div>

                      <!-- Details -->
                      <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-1 mb-0.5">
                          <span class="font-bold text-xs text-slate-900 group-hover:text-sky-700 truncate block leading-snug" :title="usr.name">
                            {{ usr.name }}
                          </span>
                        </div>
                        <div class="flex items-center gap-1.5 text-[10.5px] text-slate-500">
                          <span :class="['px-1.5 py-0.5 rounded text-[8.5px] font-black uppercase border shrink-0', getRoleBadgeStyle(usr)]">
                            {{ getRoleDisplay(usr) }}
                          </span>
                          <span class="truncate text-[10px]" :title="getUnitDisplay(usr)">
                            {{ getUnitDisplay(usr) }}
                          </span>
                        </div>
                      </div>
                    </div>

                    <ChevronRight class="w-3.5 h-3.5 text-slate-300 group-hover:text-sky-600 group-hover:translate-x-0.5 transition shrink-0" />
                  </button>
                </div>
              </div>

              <!-- Empty State if no users found (Fixed in same height) -->
              <div v-else class="h-full flex flex-col items-center justify-center text-center p-6 bg-slate-50/70 rounded-2xl border border-dashed border-slate-200 text-slate-400 text-xs">
                <p class="font-medium">Tidak ada akun yang cocok dengan filter atau pencarian Anda.</p>
                <button 
                  v-if="searchQuery || selectedDeptTab !== 'ALL'" 
                  @click="searchQuery = ''; selectedDeptTab = 'ALL'" 
                  class="mt-2 text-sky-600 font-bold hover:underline text-xs"
                >
                  Reset Filter
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="text-center text-[11px] text-slate-400 mt-6 pt-4 border-t border-slate-100">
        SIKARA FT UNSOED &copy; 2026 &bull; Sistem Informasi Kendali Anggaran dan Realisasi
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Smooth Horizontal Sliding Track */
.transition-slider {
  transition: transform 0.32s cubic-bezier(0.16, 1, 0.3, 1);
}

.custom-scroll::-webkit-scrollbar {
  width: 5px;
  height: 5px;
}
.custom-scroll::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scroll::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 9999px;
}
.custom-scroll::-webkit-scrollbar-thumb:hover {
  background: #cbd5e1;
}
</style>
