<script setup>
import { ref, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { 
  LayoutDashboard, 
  FileText, 
  AlertTriangle, 
  Wallet, 
  BarChart3, 
  ShieldCheck, 
  LogOut, 
  UserCheck, 
  CheckCircle2, 
  XCircle,
  Building2,
  Users,
  Home,
  Calendar,
  Menu,
  X
} from '@lucide/vue';

defineProps({
  title: String,
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const flash = computed(() => page.props.flash);

const role = computed(() => user.value?.role || 'PTK');
const isMobileMenuOpen = ref(false);

/**
 * Pengecekan Otorisasi Akses Menu Sidebar Per Role
 */
const canAccess = (menuKey) => {
  const currentRole = role.value;
  if (currentRole === 'ADMIN') return true;

  switch (menuKey) {
    case 'dashboard':
      return true; // Seluruh role dapat melihat dashboard
    case 'submissions':
      return ['PTK', 'KAJUR', 'PTU', 'KABAG', 'WD', 'DEKAN'].includes(currentRole);
    case 'warnings':
      return ['KAJUR', 'KABAG', 'WD', 'DEKAN'].includes(currentRole);
    case 'budgets':
      return true; // Seluruh role dapat melihat pagu anggaran
    case 'reports':
      return ['KAJUR', 'PTU', 'KABAG', 'WD', 'DEKAN'].includes(currentRole);
    case 'master':
      return currentRole === 'ADMIN'; // Khusus Admin
    case 'users':
      return currentRole === 'ADMIN'; // Khusus Admin
    case 'audit-logs':
      return ['KABAG', 'WD', 'DEKAN', 'ADMIN'].includes(currentRole);
    default:
      return true;
  }
};

const logout = () => {
  router.post('/logout');
};
</script>

<template>
  <div class="bg-slate-50 antialiased min-h-screen flex font-sans">
    <!-- Mobile Drawer Overlay Backdrop -->
    <div 
      v-if="isMobileMenuOpen" 
      @click="isMobileMenuOpen = false" 
      class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm lg:hidden transition-opacity"
    ></div>

    <!-- Responsive Clean White Sidebar (Drawer on Mobile, Sticky on Desktop) -->
    <aside 
      :class="[
        'fixed lg:sticky top-0 left-0 h-screen w-64 bg-white text-slate-700 flex flex-col shrink-0 shadow-2xl lg:shadow-sm z-50 select-none border-r border-slate-200 transition-transform duration-300 ease-in-out',
        isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
      ]"
    >
      <!-- Brand Logo Header (Fixed Top) -->
      <div class="h-20 px-6 flex items-center justify-between border-b border-slate-100 shrink-0">
        <Link href="/" class="flex items-center gap-3 group" @click="isMobileMenuOpen = false">
          <img src="/image/logo.webp" alt="Logo SIPEDA FT UNSOED" class="w-10 h-10 object-contain rounded-xl shadow-sm group-hover:scale-105 transition" />
          <div>
            <h1 class="font-black text-slate-900 tracking-tight text-lg leading-tight">SIPEDA</h1>
            <p class="text-xs text-sky-700 font-bold">FT UNSOED</p>
          </div>
        </Link>

        <!-- Close button on Mobile Drawer -->
        <button @click="isMobileMenuOpen = false" class="lg:hidden p-1.5 text-slate-400 hover:text-slate-700 rounded-lg">
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Navigation Links (Inner Scrollable) -->
      <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto custom-scrollbar">
        <div class="px-3 pb-2 text-[10px] font-bold tracking-wider text-slate-400 uppercase">WORKSPACE</div>
        
        <Link 
          v-if="canAccess('dashboard')" 
          href="/dashboard" 
          @click="isMobileMenuOpen = false"
          :class="[
            'flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition group',
            $page.url.startsWith('/dashboard') ? 'bg-sky-600 text-white shadow-md shadow-sky-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
          ]"
        >
          <LayoutDashboard :class="['w-4 h-4', $page.url.startsWith('/dashboard') ? 'text-white' : 'text-slate-400 group-hover:text-slate-600']" />
          Dashboard
        </Link>

        <Link 
          v-if="canAccess('submissions')" 
          href="/submissions" 
          @click="isMobileMenuOpen = false"
          :class="[
            'flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition group',
            $page.url.startsWith('/submissions') ? 'bg-sky-600 text-white shadow-md shadow-sky-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
          ]"
        >
          <FileText :class="['w-4 h-4', $page.url.startsWith('/submissions') ? 'text-white' : 'text-slate-400 group-hover:text-slate-600']" />
          Pengajuan
        </Link>

        <Link 
          v-if="canAccess('warnings')" 
          href="/warnings" 
          @click="isMobileMenuOpen = false"
          :class="[
            'flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition group',
            $page.url.startsWith('/warnings') ? 'bg-sky-600 text-white shadow-md shadow-sky-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
          ]"
        >
          <AlertTriangle :class="['w-4 h-4', $page.url.startsWith('/warnings') ? 'text-white' : 'text-amber-500 group-hover:text-amber-600']" />
          Early Warning (EWS)
        </Link>

        <div v-if="canAccess('budgets') || canAccess('reports')" class="px-3 pt-6 pb-2 text-[10px] font-bold tracking-wider text-slate-400 uppercase">ANGGARAN & LAPORAN</div>

        <Link 
          v-if="canAccess('budgets')" 
          href="/budgets" 
          @click="isMobileMenuOpen = false"
          :class="[
            'flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition group',
            $page.url.startsWith('/budgets') ? 'bg-sky-600 text-white shadow-md shadow-sky-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
          ]"
        >
          <Wallet :class="['w-4 h-4', $page.url.startsWith('/budgets') ? 'text-white' : 'text-slate-400 group-hover:text-slate-600']" />
          Pagu Anggaran
        </Link>

        <Link 
          v-if="canAccess('reports')" 
          href="/reports" 
          @click="isMobileMenuOpen = false"
          :class="[
            'flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition group',
            $page.url.startsWith('/reports') ? 'bg-sky-600 text-white shadow-md shadow-sky-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
          ]"
        >
          <BarChart3 :class="['w-4 h-4', $page.url.startsWith('/reports') ? 'text-white' : 'text-slate-400 group-hover:text-slate-600']" />
          Laporan Realisasi
        </Link>

        <!-- Master Data Menu for Admin -->
        <div v-if="canAccess('master')" class="px-3 pt-6 pb-2 text-[10px] font-bold tracking-wider text-slate-400 uppercase">MASTER DATA</div>

        <Link 
          v-if="canAccess('master')" 
          href="/master/departments" 
          @click="isMobileMenuOpen = false"
          :class="[
            'flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition group',
            $page.url.startsWith('/master/departments') ? 'bg-sky-600 text-white shadow-md shadow-sky-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
          ]"
        >
          <Building2 :class="['w-4 h-4', $page.url.startsWith('/master/departments') ? 'text-white' : 'text-slate-400 group-hover:text-slate-600']" />
          Master Unit & Jurusan
        </Link>

        <Link 
          v-if="canAccess('master')" 
          href="/master/funding-sources" 
          @click="isMobileMenuOpen = false"
          :class="[
            'flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition group',
            $page.url.startsWith('/master/funding-sources') ? 'bg-sky-600 text-white shadow-md shadow-sky-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
          ]"
        >
          <Wallet :class="['w-4 h-4', $page.url.startsWith('/master/funding-sources') ? 'text-white' : 'text-slate-400 group-hover:text-slate-600']" />
          Master Sumber Dana
        </Link>

        <Link 
          v-if="canAccess('master')" 
          href="/master/fiscal-years" 
          @click="isMobileMenuOpen = false"
          :class="[
            'flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition group',
            $page.url.startsWith('/master/fiscal-years') ? 'bg-sky-600 text-white shadow-md shadow-sky-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
          ]"
        >
          <Calendar :class="['w-4 h-4', $page.url.startsWith('/master/fiscal-years') ? 'text-white' : 'text-slate-400 group-hover:text-slate-600']" />
          Master Tahun Anggaran
        </Link>

        <div v-if="canAccess('users') || canAccess('audit-logs')" class="px-3 pt-6 pb-2 text-[10px] font-bold tracking-wider text-slate-400 uppercase">SISTEM & ADMIN</div>

        <Link 
          v-if="canAccess('users')" 
          href="/users" 
          @click="isMobileMenuOpen = false"
          :class="[
            'flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition group',
            $page.url.startsWith('/users') ? 'bg-sky-600 text-white shadow-md shadow-sky-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
          ]"
        >
          <Users :class="['w-4 h-4', $page.url.startsWith('/users') ? 'text-white' : 'text-slate-400 group-hover:text-slate-600']" />
          Kelola Pengguna
        </Link>

        <Link 
          v-if="canAccess('audit-logs')" 
          href="/audit-logs" 
          @click="isMobileMenuOpen = false"
          :class="[
            'flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition group',
            $page.url.startsWith('/audit-logs') ? 'bg-sky-600 text-white shadow-md shadow-sky-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
          ]"
        >
          <ShieldCheck :class="['w-4 h-4', $page.url.startsWith('/audit-logs') ? 'text-white' : 'text-slate-400 group-hover:text-slate-600']" />
          Audit Trail Log
        </Link>

        <div class="px-3 pt-6 pb-2 text-[10px] font-bold tracking-wider text-slate-400 uppercase">INFORMASI</div>

        <Link 
          href="/" 
          @click="isMobileMenuOpen = false"
          class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition group"
        >
          <Home class="w-4 h-4 text-slate-400 group-hover:text-sky-600" />
          Landing Page
        </Link>
      </nav>

      <!-- Current User Badge (Fixed Bottom) -->
      <div v-if="user" class="p-4 border-t border-slate-100 bg-slate-50/70 flex items-center justify-between shrink-0">
        <div class="overflow-hidden">
          <p class="text-xs font-bold text-slate-900 truncate">{{ user.name }}</p>
          <div class="flex items-center gap-1.5 mt-0.5">
            <span class="px-1.5 py-0.2 bg-sky-100 text-sky-800 border border-sky-200 rounded text-[10px] font-extrabold uppercase">{{ user.role }}</span>
            <span class="text-[11px] text-slate-500 truncate">{{ user.department?.code ?? 'FT' }}</span>
          </div>
        </div>
        <button @click="logout" title="Logout" class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition">
          <LogOut class="w-4 h-4" />
        </button>
      </div>
      <div v-else class="p-4 border-t border-slate-100 shrink-0">
        <Link href="/login" class="w-full text-center py-2.5 px-4 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-bold block transition shadow-md shadow-sky-600/20">
          Pilih Role / Login
        </Link>
      </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 min-h-screen">
      <!-- Topbar Header (Fixed Top & Responsive) -->
      <header class="h-20 bg-white/85 backdrop-blur-md border-b border-slate-200 sticky top-0 z-20 px-4 sm:px-8 flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-3">
          <!-- Mobile Hamburger Menu Button -->
          <button 
            @click="isMobileMenuOpen = true" 
            class="lg:hidden p-2 rounded-xl text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition"
            title="Buka Menu"
          >
            <Menu class="w-5 h-5" />
          </button>

          <div>
            <div class="flex items-center gap-2">
              <h2 class="text-lg sm:text-xl font-bold text-slate-900 truncate">{{ title || 'Dashboard' }}</h2>
              <span v-if="user" class="hidden sm:inline-block px-2 py-0.5 bg-slate-100 border border-slate-200 text-slate-700 text-[10px] font-bold rounded-md uppercase">
                Role: {{ user.role }}
              </span>
            </div>
            <p class="hidden sm:block text-xs text-slate-500">Sistem Monitoring & Pengendalian Anggaran FT UNSOED</p>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <Link href="/login" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 border border-slate-300 rounded-lg text-xs font-medium text-slate-700 flex items-center gap-1.5 transition whitespace-nowrap shadow-sm">
            <UserCheck class="w-4 h-4 text-sky-600" />
            <span class="hidden sm:inline">Switch Role Demo</span>
            <span class="sm:hidden">Switch</span>
          </Link>
        </div>
      </header>

      <!-- Main Body (Responsive Padding) -->
      <main class="flex-1 p-4 sm:p-6 lg:p-8">
        <!-- Flash Messages -->
        <div v-if="flash?.success" class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl flex items-start gap-3 shadow-sm">
          <CheckCircle2 class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" />
          <div class="text-sm font-medium">{{ flash.success }}</div>
        </div>

        <div v-if="flash?.error" class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl flex items-start gap-3 shadow-sm">
          <XCircle class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" />
          <div class="text-sm font-medium">{{ flash.error }}</div>
        </div>

        <slot />
      </main>

      <!-- Footer -->
      <footer class="py-4 px-4 sm:px-8 border-t border-slate-200 bg-white text-center text-xs text-slate-500">
        SIPEDA &copy; 2026 Fakultas Teknik Universitas Jenderal Soedirman. All rights reserved.
      </footer>
    </div>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}
</style>
