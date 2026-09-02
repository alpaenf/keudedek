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
  X,
  FileSpreadsheet,
  FileCheck,
  Bell,
  Sliders,
  ChevronDown,
  PlusCircle,
  Settings,
  GitCompare,
  Layers
} from 'lucide-vue-next';

defineProps({
  title: String,
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const flash = computed(() => page.props.flash);
const unreadNotificationsCount = computed(() => page.props.unread_notifications_count || 0);

const role = computed(() => {
  if (user.value?.role === 'WD') return 'WAKIL_DEKAN';
  return user.value?.role || 'PTK';
});

const isMobileMenuOpen = ref(false);
const canCreateTransaction = computed(() => page.props.auth?.user?.can_create_transaction ?? true);
const canImportTransaction = computed(() => page.props.auth?.user?.can_import_transaction ?? false);
const canApproveFinancial = computed(() => page.props.auth?.user?.can_approve_financial);
const isAdmin = computed(() => role.value === 'ADMIN');

const logout = () => {
  router.post('/logout');
};
</script>

<template>
  <div class="bg-slate-50 antialiased min-h-screen flex font-sans">
    <!-- Mobile Drawer Backdrop -->
    <div 
      v-if="isMobileMenuOpen" 
      @click="isMobileMenuOpen = false" 
      class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm lg:hidden transition-opacity"
    ></div>

    <!-- Nested White Professional Sidebar -->
    <aside 
      :class="[
        'fixed lg:sticky top-0 left-0 h-screen w-64 bg-white text-slate-700 flex flex-col shrink-0 shadow-2xl lg:shadow-sm z-50 select-none border-r border-slate-200 transition-transform duration-300 ease-in-out',
        isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
      ]"
    >
      <!-- Brand Logo Header -->
      <div class="h-20 px-6 flex items-center justify-between border-b border-slate-100 shrink-0">
        <Link href="/" class="flex items-center gap-3 group" @click="isMobileMenuOpen = false">
          <img src="/image/SIKARALOGO.png" alt="Logo SIKARA FT UNSOED" class="w-10 h-10 object-contain rounded-xl shadow-sm group-hover:scale-105 transition" />
          <div>
            <h1 class="font-black text-slate-900 tracking-tight text-lg leading-tight">SIKARA</h1>
            <p class="text-[11px] text-sky-700 font-bold">FT UNSOED</p>
          </div>
        </Link>

        <!-- Close button on Mobile -->
        <button @click="isMobileMenuOpen = false" class="lg:hidden p-1.5 text-slate-400 hover:text-slate-700 rounded-lg">
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Nested Navigation Links -->
      <nav class="flex-1 px-4 py-5 space-y-5 overflow-y-auto custom-scrollbar text-xs">
        
        <!-- SECTION 1: WORKSPACE & TRANSAKSI -->
        <div>
          <div class="px-3 pb-1.5 text-[10px] font-bold tracking-wider text-slate-400 uppercase">WORKSPACE</div>
          <div class="space-y-0.5">
            <!-- 1. Dashboard (Semua Role) -->
            <Link 
              href="/dashboard" 
              @click="isMobileMenuOpen = false"
              :class="[
                'flex items-center gap-3 px-3 py-2 rounded-xl font-semibold transition group',
                $page.url.startsWith('/dashboard') || $page.url === '/' ? 'bg-sky-600 text-white shadow-md shadow-sky-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
              ]"
            >
              <LayoutDashboard :class="['w-4 h-4', ($page.url.startsWith('/dashboard') || $page.url === '/') ? 'text-white' : 'text-slate-400 group-hover:text-slate-600']" />
              <span>Dashboard</span>
            </Link>

            <!-- 2. Pemeriksaan Transaksi & SPJ (Khusus PTU) -->
            <Link 
              v-if="['PTU', 'BENDAHARA'].includes(role)"
              href="/approvals" 
              @click="isMobileMenuOpen = false"
              :class="[
                'flex items-center gap-3 px-3 py-2 rounded-xl font-semibold transition group',
                $page.url.startsWith('/approvals') ? 'bg-sky-600 text-white shadow-md shadow-sky-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
              ]"
            >
              <FileCheck :class="['w-4 h-4', $page.url.startsWith('/approvals') ? 'text-white' : 'text-emerald-600 group-hover:text-emerald-700']" />
              <span>Pemeriksaan Transaksi</span>
            </Link>

            <!-- 3. Catat Transaksi (Khusus PTK) -->
            <Link 
              v-if="role === 'PTK'"
              href="/submissions/create" 
              @click="isMobileMenuOpen = false"
              :class="[
                'flex items-center gap-3 px-3 py-2 rounded-xl font-semibold transition group',
                $page.url.startsWith('/submissions/create') ? 'bg-sky-600 text-white shadow-md shadow-sky-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
              ]"
            >
              <PlusCircle :class="['w-4 h-4', $page.url.startsWith('/submissions/create') ? 'text-white' : 'text-slate-400 group-hover:text-slate-600']" />
              <span>Catat Transaksi</span>
            </Link>

            <!-- 4. Daftar Transaksi (PTK, KAJUR, KAPRODI, PTU, KABAG) -->
            <Link 
              v-if="['PTK', 'KAJUR', 'KAPRODI', 'PTU', 'KABAG'].includes(role)"
              href="/submissions" 
              @click="isMobileMenuOpen = false"
              :class="[
                'flex items-center gap-3 px-3 py-2 rounded-xl font-semibold transition group',
                $page.url === '/submissions' ? 'bg-sky-600 text-white shadow-md shadow-sky-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
              ]"
            >
              <FileText :class="['w-4 h-4', $page.url === '/submissions' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600']" />
              <span>{{ role === 'KAJUR' ? 'Transaksi Jurusan' : role === 'KAPRODI' ? 'Transaksi Terkait Prodi' : 'Transaksi' }}</span>
            </Link>

            <!-- 5. Warning / EWS (KAJUR, PTU, KABAG, WD) -->
            <Link 
              v-if="['KAJUR', 'PTU', 'KABAG', 'WAKIL_DEKAN', 'WD'].includes(role)"
              href="/warnings" 
              @click="isMobileMenuOpen = false"
              :class="[
                'flex items-center gap-3 px-3 py-2 rounded-xl font-semibold transition group',
                $page.url.startsWith('/warnings') ? 'bg-sky-600 text-white shadow-md shadow-sky-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
              ]"
            >
              <AlertTriangle :class="['w-4 h-4', $page.url.startsWith('/warnings') ? 'text-white' : 'text-amber-500 group-hover:text-amber-600']" />
              <span>Warning</span>
            </Link>
          </div>
        </div>

        <!-- SECTION 2: ANGGARAN & MONITORING (KAJUR, KABAG, WD, DEKAN) -->
        <div v-if="['KAJUR', 'KABAG', 'WAKIL_DEKAN', 'WD', 'DEKAN'].includes(role)">
          <div class="px-3 pb-1.5 text-[10px] font-bold tracking-wider text-slate-400 uppercase">ANGGARAN</div>
          <div class="space-y-0.5">
            <Link 
              href="/budgets" 
              @click="isMobileMenuOpen = false"
              :class="[
                'flex items-center gap-3 px-3 py-2 rounded-xl font-semibold transition group',
                $page.url === '/budgets' ? 'bg-sky-600 text-white shadow-md shadow-sky-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
              ]"
            >
              <Wallet :class="['w-4 h-4', $page.url === '/budgets' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600']" />
              <span>{{ role === 'KAJUR' ? 'Anggaran Jurusan' : ['WAKIL_DEKAN', 'WD', 'DEKAN'].includes(role) ? 'Monitoring' : 'Anggaran' }}</span>
            </Link>

            <Link 
              v-if="['KABAG', 'WAKIL_DEKAN', 'WD', 'DEKAN'].includes(role)"
              href="/budget-versions" 
              @click="isMobileMenuOpen = false"
              :class="[
                'flex items-center gap-3 px-3 py-2 rounded-xl font-semibold transition group',
                $page.url.startsWith('/budget-versions') ? 'bg-sky-600 text-white shadow-md shadow-sky-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
              ]"
            >
              <GitCompare :class="['w-4 h-4', $page.url.startsWith('/budget-versions') ? 'text-white' : 'text-slate-400 group-hover:text-slate-600']" />
              <span>Versi &amp; Revisi Pagu</span>
            </Link>
          </div>
        </div>

        <!-- SECTION 3: LAPORAN (PTK, KAJUR, KAPRODI, KABAG, WD, DEKAN) -->
        <div v-if="['PTK', 'KAJUR', 'KAPRODI', 'KABAG', 'WAKIL_DEKAN', 'WD', 'DEKAN'].includes(role)">
          <div class="px-3 pb-1.5 text-[10px] font-bold tracking-wider text-slate-400 uppercase">LAPORAN</div>
          <div class="space-y-0.5">
            <Link 
              href="/reports" 
              @click="isMobileMenuOpen = false"
              :class="[
                'flex items-center gap-3 px-3 py-2 rounded-xl font-semibold transition group',
                $page.url.startsWith('/reports') ? 'bg-sky-600 text-white shadow-md shadow-sky-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
              ]"
            >
              <BarChart3 :class="['w-4 h-4', $page.url.startsWith('/reports') ? 'text-white' : 'text-slate-400 group-hover:text-slate-600']" />
              <span>Laporan</span>
            </Link>
          </div>
        </div>

        <!-- SECTION 4: ADMINISTRASI & MASTER DATA (Admin Only) -->
        <div v-if="isAdmin">
          <div class="px-3 pb-1.5 text-[10px] font-bold tracking-wider text-slate-400 uppercase">ADMINISTRASI</div>
          <div class="space-y-0.5">
            <Link 
              href="/budgets-import" 
              @click="isMobileMenuOpen = false"
              :class="[
                'flex items-center gap-3 px-3 py-2 rounded-xl font-semibold transition group',
                $page.url.startsWith('/budgets-import') ? 'bg-sky-600 text-white shadow-md shadow-sky-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
              ]"
            >
              <FileSpreadsheet :class="['w-4 h-4', $page.url.startsWith('/budgets-import') ? 'text-white' : 'text-slate-400 group-hover:text-slate-600']" />
              <span>Import Pagu Anggaran</span>
            </Link>

            <Link 
              href="/master/departments" 
              @click="isMobileMenuOpen = false"
              :class="[
                'flex items-center gap-3 px-3 py-2 rounded-xl font-semibold transition group',
                $page.url.startsWith('/master/departments') ? 'bg-sky-600 text-white shadow-md shadow-sky-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
              ]"
            >
              <Building2 :class="['w-4 h-4', $page.url.startsWith('/master/departments') ? 'text-white' : 'text-slate-400 group-hover:text-slate-600']" />
              <span>Master Organisasi</span>
            </Link>

            <Link 
              href="/master/fiscal-years" 
              @click="isMobileMenuOpen = false"
              :class="[
                'flex items-center gap-3 px-3 py-2 rounded-xl font-semibold transition group',
                $page.url.startsWith('/master/fiscal-years') ? 'bg-sky-600 text-white shadow-md shadow-sky-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
              ]"
            >
              <Calendar :class="['w-4 h-4', $page.url.startsWith('/master/fiscal-years') ? 'text-white' : 'text-slate-400 group-hover:text-slate-600']" />
              <span>Tahun &amp; Versi Pagu</span>
            </Link>

            <Link 
              href="/master/budget-structure" 
              @click="isMobileMenuOpen = false"
              :class="[
                'flex items-center gap-3 px-3 py-2 rounded-xl font-semibold transition group',
                $page.url.startsWith('/master/budget-structure') ? 'bg-sky-600 text-white shadow-md shadow-sky-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
              ]"
            >
              <Layers :class="['w-4 h-4', $page.url.startsWith('/master/budget-structure') ? 'text-white' : 'text-slate-400 group-hover:text-slate-600']" />
              <span>Master Struktur Anggaran</span>
            </Link>

            <Link 
              href="/users" 
              @click="isMobileMenuOpen = false"
              :class="[
                'flex items-center gap-3 px-3 py-2 rounded-xl font-semibold transition group',
                $page.url.startsWith('/users') ? 'bg-sky-600 text-white shadow-md shadow-sky-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
              ]"
            >
              <Users :class="['w-4 h-4', $page.url.startsWith('/users') ? 'text-white' : 'text-slate-400 group-hover:text-slate-600']" />
              <span>Pengguna &amp; Peran</span>
            </Link>

            <Link 
              href="/audit-logs" 
              @click="isMobileMenuOpen = false"
              :class="[
                'flex items-center gap-3 px-3 py-2 rounded-xl font-semibold transition group',
                $page.url.startsWith('/audit-logs') ? 'bg-sky-600 text-white shadow-md shadow-sky-600/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
              ]"
            >
              <ShieldCheck :class="['w-4 h-4', $page.url.startsWith('/audit-logs') ? 'text-white' : 'text-slate-400 group-hover:text-slate-600']" />
              <span>Log Audit Trail</span>
            </Link>
          </div>
        </div>
      </nav>

      <!-- User Profile Badge (Fixed Bottom) -->
      <div v-if="user" class="p-4 border-t border-slate-100 bg-slate-50/70 flex items-center justify-between shrink-0">
        <div class="overflow-hidden">
          <p class="text-xs font-bold text-slate-900 truncate">{{ user.name }}</p>
          <div class="flex flex-wrap items-center gap-1 mt-0.5">
            <template v-if="user.roles && user.roles.length > 0">
              <span v-for="r in user.roles" :key="r" class="px-1.5 py-0.5 bg-sky-100 text-sky-800 border border-sky-200 rounded text-[9px] font-black uppercase">
                {{ r }}
              </span>
            </template>
            <span v-else class="px-1.5 py-0.5 bg-sky-100 text-sky-800 border border-sky-200 rounded text-[9px] font-black uppercase">{{ role }}</span>
            <span class="text-[10px] text-slate-500 truncate ml-0.5">
              {{ user.study_program ? user.study_program.code : (user.department?.code ?? 'FT') }}
            </span>
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
      <!-- Topbar Header -->
      <header class="h-20 bg-white/85 backdrop-blur-md border-b border-slate-200 sticky top-0 z-20 px-4 sm:px-8 flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-3">
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
              <div v-if="user" class="hidden sm:flex items-center gap-1">
                <template v-if="user.roles && user.roles.length > 0">
                  <span v-for="r in user.roles" :key="r" class="px-2 py-0.5 bg-slate-100 border border-slate-200 text-slate-700 text-[10px] font-bold rounded-md uppercase">
                    {{ r }}
                  </span>
                </template>
                <span v-else class="px-2 py-0.5 bg-slate-100 border border-slate-200 text-slate-700 text-[10px] font-bold rounded-md uppercase">
                  {{ role }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <!-- Notification Bell -->
          <div class="relative">
            <Link 
              href="/warnings" 
              class="p-2 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-600 relative inline-flex transition"
              title="Notifikasi & Peringatan Dini"
            >
              <Bell class="w-5 h-5" />
              <span 
                v-if="unreadNotificationsCount > 0"
                class="absolute -top-1 -right-1 w-5 h-5 bg-rose-600 text-white rounded-full text-[10px] font-black flex items-center justify-center border-2 border-white animate-pulse"
              >
                {{ unreadNotificationsCount > 9 ? '9+' : unreadNotificationsCount }}
              </span>
            </Link>
          </div>

          <!-- Switch Role Demo Button -->
          <Link href="/login" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 border border-slate-300 rounded-xl text-xs font-bold text-slate-800 transition whitespace-nowrap shadow-sm">
            <span class="hidden sm:inline">Switch Role Demo</span>
            <span class="sm:hidden">Switch</span>
          </Link>
        </div>
      </header>

      <!-- Main Body -->
      <main class="flex-1 p-4 sm:p-6 lg:p-8">
        <!-- Flash Messages -->
        <div v-if="flash?.success" class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl flex items-start gap-3 shadow-sm animate-in fade-in">
          <CheckCircle2 class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" />
          <div class="text-xs sm:text-sm font-medium">{{ flash.success }}</div>
        </div>

        <div v-if="flash?.error" class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl flex items-start gap-3 shadow-sm animate-in fade-in">
          <XCircle class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" />
          <div class="text-xs sm:text-sm font-medium">{{ flash.error }}</div>
        </div>

        <slot />
      </main>

      <!-- Footer -->
      <footer class="py-4 px-4 sm:px-8 border-t border-slate-200 bg-white text-center text-xs text-slate-500">
        SIKARA &copy; 2026 Fakultas Teknik Universitas Jenderal Soedirman. All rights reserved.
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
