<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import { 
  Building2, 
  Users, 
  CheckCircle2, 
  ShieldCheck, 
  AlertTriangle, 
  FileText, 
  Wallet, 
  BarChart3, 
  ArrowRight, 
  Lock, 
  LogIn,
  KeyRound,
  Sparkles,
  UserCheck,
  ChevronRight
} from '@lucide/vue';

defineProps({
  fiscalYear: [String, Number],
  departmentsCount: Number,
  user: Object,
});

const isScrolled = ref(false);

const handleScroll = () => {
  isScrolled.value = window.scrollY > 40;
};

onMounted(() => {
  window.addEventListener('scroll', handleScroll, { passive: true });
  handleScroll();

  // Modern IntersectionObserver for On-Scroll Scroll-Reveal Animations
  const observerOptions = {
    root: null,
    rootMargin: '0px 0px -40px 0px',
    threshold: 0.1,
  };

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-revealed');
        observer.unobserve(entry.target);
      }
    });
  }, observerOptions);

  document.querySelectorAll('.reveal-on-scroll').forEach((el) => {
    observer.observe(el);
  });
});

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
});

const selectedRoleTab = ref('PTK');

const rolesDetail = [
  {
    key: 'PTK',
    title: 'PTK Unit (Operator Jurusan)',
    badge: 'Level Operasional',
    badgeColor: 'bg-sky-100 text-sky-800 border-sky-300',
    description: 'Petugas Teknis Kegiatan yang bertanggung jawab mengusulkan kegiatan dan menginput rincian item belanja barang/jasa pada tingkat unit jurusan.',
    responsibilities: [
      'Membuat dan menyimpan draft pengajuan kegiatan (Status: DRAFT).',
      'Mengisi rincian belanja, volume barang/jasa, dan harga satuan.',
      'Mengirimkan pengajuan ke tahap verifikasi jurusan & fakultas (Status: SUBMITTED).',
      'Melakukan revisi jika terdapat catatan perbaikan dari KAJUR / PTU (Status: RETURNED).'
    ],
    scope: 'Terisolasi khusus untuk unit kerja yang ditugaskan (department_id).'
  },
  {
    key: 'KAJUR',
    title: 'Ketua Jurusan (KAJUR)',
    badge: 'Level Persetujuan Jurusan',
    badgeColor: 'bg-indigo-100 text-indigo-800 border-indigo-300',
    description: 'Pejabat penanggung jawab unit jurusan yang bertugas mereview dan menyetujui prioritas pengajuan kegiatan jurusan.',
    responsibilities: [
      'Memeriksa kelayakan dan urgensi pengajuan dari operator PTK.',
      'Menyetujui pengajuan kegiatan untuk diteruskan ke fakultas (Status: APPROVED).',
      'Mengembalikan pengajuan ke operator jika terdapat kekurangan rincian (Status: RETURNED).',
      'Memantau indikator Early Warning System (EWS) dan penyerapan pagu jurusan.'
    ],
    scope: 'Akses penuh terhadap data pagu, pengajuan, dan LRA jurusan bersangkutan.'
  },
  {
    key: 'PTU',
    title: 'PTU (Reviewer Keuangan Fakultas)',
    badge: 'Level Verifikasi SPJ',
    badgeColor: 'bg-blue-100 text-blue-800 border-blue-300',
    description: 'Tim pemeriksa keuangan fakultas yang melakukan verifikasi administrasi SPJ dan kepatuhan akun belanja.',
    responsibilities: [
      'Verifikasi kesesuaian kode akun belanja dengan Standar Biaya Masukan (SBM).',
      'Memeriksa fisik dan kelengkapan dokumen pendukung SPJ (Status: REVIEW).',
      'Memberikan rekomendasi persetujuan ke Kabag Keuangan atau catatan revisi.'
    ],
    scope: 'Akses pemeriksaan seluruh pengajuan dari seluruh unit di Fakultas Teknik.'
  },
  {
    key: 'KABAG',
    title: 'Kabag Keuangan (Eksekutor Anggaran)',
    badge: 'Eksekutif Keuangan',
    badgeColor: 'bg-amber-100 text-amber-800 border-amber-300',
    description: 'Eksekutor puncak pengelolaan anggaran fakultas yang berwenang mengunci reservasi dana, mencairkan anggaran, dan melakukan revisi pagu.',
    responsibilities: [
      'Menyetujui pengajuan final & mengunci komitmen saldo (Status: RESERVED / APPROVED).',
      'Mencairkan dana realisasi setelah SPJ diverifikasi lengkap (Status: COMPLETED).',
      'Menerapkan pergeseran dan revisi pagu aktif anggaran (Budget Revision).',
      'Memantau Audit Log Security dan histori transaksi fakultas.'
    ],
    scope: 'Akses eksekutif pengelolaan finansial seluruh unit Fakultas Teknik.'
  },
  {
    key: 'WD',
    title: 'Wakil Dekan II (Pimpinan / Executive)',
    badge: 'Level Strategis Eksekutif',
    badgeColor: 'bg-purple-100 text-purple-800 border-purple-300',
    description: 'Pimpinan fakultas yang membutuhkan informasi keuangan strategis dan indikator pengendalian anggaran untuk pengambilan keputusan.',
    responsibilities: [
      'Memantau Executive Dashboard realisasi dan penyerapan anggaran fakultas.',
      'Menerima notifikasi Early Warning System (EWS) untuk ketersediaan saldo kritis (< 15%).',
      'Mengevaluasi Laporan Realisasi Anggaran (LRA) komprehensif per semester / tahun.'
    ],
    scope: 'Akses eksekutif read-only & decision support seluruh Fakultas Teknik.'
  },
  {
    key: 'DEKAN',
    title: 'Dekan Fakultas Teknik (Pimpinan Utama)',
    badge: 'Pimpinan Tertinggi Fakultas',
    badgeColor: 'bg-emerald-100 text-emerald-800 border-emerald-300',
    description: 'Pimpinan tertinggi Fakultas Teknik (KPA) yang memegang otoritas kebijakan strategis, pengawasan penyerapan anggaran, dan persetujuan belanja institusi.',
    responsibilities: [
      'Memantau performa serapan anggaran seluruh jurusan secara komprehensif.',
      'Menerima peringatan dini risiko defisit / under-spending dari Early Warning System.',
      'Mengevaluasi laporan akuntabilitas keuangan dan audit trail fakultas.',
      'Memberikan persetujuan kebijakan belanja strategis fakultas.'
    ],
    scope: 'Akses pimpinan tertinggi seluruh unit kerja di lingkungan Fakultas Teknik.'
  },
  {
    key: 'ADMIN',
    title: 'Super Admin (Administrator Sistem)',
    badge: 'Administrator Sistem',
    badgeColor: 'bg-rose-100 text-rose-800 border-rose-300',
    description: 'Pengelola teknis aplikasi yang bertanggung jawab menjaga integritas sistem, akun pengguna, dan konfigurasi master data.',
    responsibilities: [
      'Mengelola akun pengguna dan perizinan akses per role (PTK, KAJUR, PTU, KABAG, WD, DEKAN).',
      'Memastikan isolasi data unit kerja (department_id) dikonfigurasi secara benar.',
      'Memantau jejak Audit Trail Log seluruh aktivitas pengguna.',
      'Mengonfigurasi master data tahun anggaran, sumber dana, unit kerja, dan alokasi pagu.'
    ],
    scope: 'Akses penuh ke modul administrasi, master data, user management, dan audit trail.'
  }
];

const workflowSteps = [
  {
    step: '01',
    title: 'Penyusunan Rincian',
    role: 'PTK Unit',
    desc: 'Pengusulan belanja kegiatan dan pengisian rincian barang/jasa.'
  },
  {
    step: '02',
    title: 'Verifikasi & Validasi',
    role: 'KAJUR & PTU',
    desc: 'Pemeriksaan kesesuaian pagu jurusan dan kepatuhan SPJ administrasi.'
  },
  {
    step: '03',
    title: 'Persetujuan & Reservasi',
    role: 'Kabag Keuangan & Pimpinan',
    desc: 'Persetujuan final diterbitkan & sistem mengunci saldo komitmen (Reserved).'
  },
  {
    step: '04',
    title: 'Pencairan & Realisasi',
    role: 'Kabag Keuangan',
    desc: 'SPJ selesai diverifikasi murni, saldo komitmen dicairkan menjadi Realisasi.'
  },
  {
    step: '05',
    title: 'Monitoring & Audit',
    role: 'Dekan, WD II & Admin',
    desc: 'Pimpinan memantau LRA, indikator EWS, dan jejak transaksi Audit Trail Log.'
  }
];
</script>

<template>
  <div class="bg-slate-50 text-slate-800 min-h-screen font-sans antialiased selection:bg-sky-600 selection:text-white">
    <!-- Floating Capsule Animated Navbar -->
    <header 
      :class="[
        'fixed top-0 left-0 right-0 z-50 transition-all duration-500 ease-in-out',
        isScrolled ? 'pt-3 sm:pt-4 px-4 sm:px-6' : 'pt-0 px-0'
      ]"
    >
      <div 
        :class="[
          'transition-all duration-500 ease-in-out flex items-center justify-between',
          isScrolled 
            ? 'max-w-5xl mx-auto bg-white/75 backdrop-blur-2xl border border-white/80 shadow-2xl shadow-sky-950/10 rounded-full px-6 sm:px-8 h-16 ring-1 ring-slate-900/5' 
            : 'w-full max-w-7xl mx-auto bg-transparent border-b border-transparent shadow-none px-6 sm:px-10 lg:px-12 h-20'
        ]"
      >
        <!-- Logo Brand -->
        <div class="flex items-center gap-3">
          <img 
            src="/image/logo.webp" 
            alt="Logo SIPEDA FT UNSOED" 
            :class="['object-contain rounded-xl shadow-sm transition-all duration-300', isScrolled ? 'w-8 h-8' : 'w-10 h-10']" 
          />
          <div>
            <span :class="['font-black text-slate-900 tracking-tight block leading-tight transition-all duration-300', isScrolled ? 'text-lg' : 'text-xl']">SIPEDA</span>
            <span class="text-[10px] sm:text-xs text-sky-700 font-bold block">FT UNSOED</span>
          </div>
        </div>

        <!-- Navigation Links -->
        <nav class="hidden md:flex items-center gap-6 lg:gap-8 text-xs font-semibold text-slate-700">
          <a href="#about" class="hover:text-sky-600 transition">Tentang SIPEDA</a>
          <a href="#simulation" class="hover:text-sky-600 transition">Simulasi Live</a>
          <a href="#roles" class="hover:text-sky-600 transition">Peran & Role</a>
          <a href="#workflow" class="hover:text-sky-600 transition">Siklus Workflow</a>
          <a href="#features" class="hover:text-sky-600 transition">Fitur Unggulan</a>
        </nav>

        <!-- Action Button -->
        <div class="flex items-center gap-3">
          <Link 
            v-if="user" 
            href="/dashboard" 
            :class="[
              'bg-sky-600 hover:bg-sky-500 text-white font-bold transition flex items-center gap-1.5 shadow-md shadow-sky-600/20',
              isScrolled ? 'px-4 py-2 text-xs rounded-full' : 'px-5 py-2.5 text-xs rounded-xl'
            ]"
          >
            <UserCheck class="w-4 h-4" /> 
            <span class="hidden sm:inline">Dashboard</span> ({{ user.role }})
          </Link>
          <Link 
            v-else 
            href="/login" 
            :class="[
              'bg-sky-600 hover:bg-sky-500 text-white font-bold transition flex items-center gap-1.5 shadow-md shadow-sky-600/20',
              isScrolled ? 'px-4 py-2 text-xs rounded-full' : 'px-6 py-2.5 text-xs rounded-xl'
            ]"
          >
            <LogIn class="w-4 h-4" /> 
            <span>Login Aplikasi</span>
          </Link>
        </div>
      </div>
    </header>

    <!-- SECTION 1: ULTRA CLEAN MINIMALIST HERO (Full Viewport Height) -->
    <section id="about" class="min-h-screen pt-28 pb-20 sm:pt-32 sm:pb-24 px-6 bg-gradient-to-b from-sky-50/70 via-white to-slate-50 border-b border-slate-200 flex items-center justify-center">
      <div class="max-w-4xl mx-auto text-center space-y-7 -mt-6 sm:-mt-10">
        <h1 class="reveal-on-scroll text-3xl sm:text-5xl lg:text-6xl font-black text-slate-900 tracking-tight leading-tight">
          Sistem Informasi Pagu & Pengendalian Anggaran
        </h1>

        <p class="reveal-on-scroll delay-100 text-slate-600 text-base sm:text-xl leading-relaxed max-w-2xl mx-auto">
          Transparansi alokasi dana, otomatisasi reservasi komitmen pagu, dan pemantauan real-time LRA Fakultas Teknik Universitas Jenderal Soedirman.
        </p>

        <div class="reveal-on-scroll delay-200 flex flex-wrap justify-center items-center gap-4 pt-4">
          <Link href="/login" class="px-8 py-3.5 bg-sky-600 hover:bg-sky-500 text-white rounded-xl font-bold text-xs flex items-center gap-2 transition shadow-lg shadow-sky-600/25 hover:scale-105">
            <KeyRound class="w-4 h-4" />
            Mulai Sesi Demo & Login
          </Link>
          <a href="#roles" class="px-8 py-3.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-xl font-bold text-xs flex items-center gap-2 transition shadow-sm hover:scale-105">
            Pelajari Alur Sistem
            <ArrowRight class="w-4 h-4 text-sky-600" />
          </a>
        </div>
      </div>
    </section>

    <!-- SECTION 2: LIVE SIMULATION WITH VERTICAL 3-BAR VISUALIZATION CHART -->
    <section id="simulation" class="py-20 px-6 bg-white border-b border-slate-200">
      <div class="max-w-5xl mx-auto">
        <div class="reveal-on-scroll text-center max-w-2xl mx-auto mb-12">
          <span class="text-xs font-bold text-sky-600 uppercase tracking-widest block mb-2">SIMULASI LIVE & MONITORING</span>
          <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
            Kendali Anggaran Real-Time & Transparan
          </h2>
          <p class="text-slate-600 text-xs sm:text-sm mt-2">
            Gambaran visual bagaimana SIPEDA secara otomatis mengunci komitmen dan menghitung sisa saldo bebas.
          </p>
        </div>

        <div class="reveal-on-scroll delay-100 bg-slate-50 border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm">
          <!-- Simulation Status Header -->
          <div class="flex flex-wrap items-center justify-between gap-4 pb-6 border-b border-slate-200">
            <div>
              <span class="text-[11px] font-mono font-bold text-sky-700 block">SIMULATION STATE ACTIVE &bull; TA {{ fiscalYear }}</span>
              <h3 class="text-lg font-bold text-slate-900">Mata Anggaran: 521111 - Belanja Bahan Operasional Lab</h3>
            </div>
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
              <span class="text-xs font-bold text-slate-700">Live Simulation Status</span>
            </div>
          </div>

          <!-- Total Pagu Banner -->
          <div class="mt-6 p-4 bg-white rounded-2xl border border-slate-200 flex flex-wrap items-center justify-between gap-4 shadow-sm">
            <div>
              <span class="text-xs text-slate-500 font-semibold block">Total Pagu Alokasi Aktif (Allocated)</span>
              <span class="text-2xl font-black text-slate-900 tracking-tight font-mono">Rp 12.500.000.000</span>
            </div>
            <span class="px-3 py-1.5 bg-sky-100 text-sky-800 text-xs font-extrabold rounded-xl border border-sky-200">
              Formula: Pagu Aktif = Realisasi + Komitmen + Saldo Bebas
            </span>
          </div>

          <!-- Vertical 3-Bar Chart Visualization Component -->
          <div class="mt-6 p-6 bg-white rounded-2xl border border-slate-200 shadow-sm">
            <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-6 text-center">
              Distribusi Alokasi Anggaran (Visualisasi Komparasi)
            </h4>

            <!-- 3 Vertical Bars Container -->
            <div class="h-44 flex items-end justify-around gap-6 sm:gap-12 border-b border-slate-200 pb-2 px-4">
              <!-- Bar 1: Realisasi -->
              <div class="flex-1 flex flex-col items-center h-full justify-end group">
                <div class="text-[10px] font-bold text-slate-700 mb-1 opacity-0 group-hover:opacity-100 transition">
                  41.6%
                </div>
                <div 
                  class="w-full max-w-[56px] bg-slate-900 rounded-t-xl transition-all duration-500 relative flex items-start justify-center pt-1.5 shadow-sm"
                  style="height: 65%;"
                >
                  <span class="text-[9px] font-bold text-white tracking-wider">41.6%</span>
                </div>
              </div>

              <!-- Bar 2: Komitmen (Reserved) -->
              <div class="flex-1 flex flex-col items-center h-full justify-end group">
                <div class="text-[10px] font-bold text-slate-700 mb-1 opacity-0 group-hover:opacity-100 transition">
                  16.8%
                </div>
                <div 
                  class="w-full max-w-[56px] bg-sky-600 rounded-t-xl transition-all duration-500 relative flex items-start justify-center pt-1.5 shadow-sm"
                  style="height: 35%;"
                >
                  <span class="text-[9px] font-bold text-white tracking-wider">16.8%</span>
                </div>
              </div>

              <!-- Bar 3: Saldo Bebas (Available) -->
              <div class="flex-1 flex flex-col items-center h-full justify-end group">
                <div class="text-[10px] font-bold text-slate-700 mb-1 opacity-0 group-hover:opacity-100 transition">
                  41.6%
                </div>
                <div 
                  class="w-full max-w-[56px] bg-sky-200 border border-sky-300 rounded-t-xl transition-all duration-500 relative flex items-start justify-center pt-1.5 shadow-sm"
                  style="height: 65%;"
                >
                  <span class="text-[9px] font-bold text-sky-900 tracking-wider">41.6%</span>
                </div>
              </div>
            </div>

            <!-- X-Axis Labels -->
            <div class="flex justify-around text-center text-[11px] font-semibold pt-3 text-slate-700">
              <div class="flex-1">
                <span class="block font-bold text-slate-900">Realisasi</span>
                <span class="text-[10px] font-medium text-slate-500">Rp 5,2 M</span>
              </div>
              <div class="flex-1">
                <span class="block font-bold text-sky-700">Komitmen Locked</span>
                <span class="text-[10px] font-medium text-slate-500">Rp 2,1 M</span>
              </div>
              <div class="flex-1">
                <span class="block font-bold text-slate-900">Saldo Bebas</span>
                <span class="text-[10px] font-medium text-slate-500">Rp 5,2 M</span>
              </div>
            </div>

            <!-- Legend Footer -->
            <div class="flex items-center justify-center gap-5 text-[10px] text-slate-600 border-t border-slate-100 pt-3 mt-3">
              <div class="flex items-center gap-1.5">
                <div class="w-2.5 h-2.5 rounded-sm bg-slate-900"></div>
                <span>Realisasi Dicairkan</span>
              </div>
              <div class="flex items-center gap-1.5">
                <div class="w-2.5 h-2.5 rounded-sm bg-sky-600"></div>
                <span>Komitmen Locked</span>
              </div>
              <div class="flex items-center gap-1.5">
                <div class="w-2.5 h-2.5 rounded-sm bg-sky-200 border border-sky-300"></div>
                <span>Saldo Bebas</span>
              </div>
            </div>
          </div>

          <!-- Live Activity Log Simulation Cards -->
          <div class="mt-6 space-y-3">
            <span class="text-xs font-bold text-slate-700 block">Simulasi Aktivitas Pengajuan Terbaru:</span>
            
            <div class="reveal-on-scroll delay-200 flex items-center justify-between p-3 bg-white rounded-xl border border-slate-200 text-xs shadow-sm hover:border-sky-300 transition">
              <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center font-bold">
                  <FileText class="w-3.5 h-3.5" />
                </div>
                <div>
                  <span class="font-bold text-slate-900 block text-[11px]">Pengadaan Perangkat Lab Informatika</span>
                  <span class="text-[10px] text-slate-500">PTK Informatika &bull; Rp 45.000.000</span>
                </div>
              </div>
              <span class="px-2 py-0.5 bg-sky-100 text-sky-800 text-[10px] font-extrabold rounded-md">APPROVED</span>
            </div>

            <div class="reveal-on-scroll delay-300 flex items-center justify-between p-3 bg-white rounded-xl border border-slate-200 text-xs shadow-sm hover:border-emerald-300 transition">
              <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">
                  <CheckCircle2 class="w-3.5 h-3.5" />
                </div>
                <div>
                  <span class="font-bold text-slate-900 block text-[11px]">Seminar Nasional Teknik Elektro</span>
                  <span class="text-[10px] text-slate-500">PTK Elektro &bull; Rp 28.500.000</span>
                </div>
              </div>
              <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-extrabold rounded-md">COMPLETED</span>
            </div>
          </div>

          <!-- EWS Alert Simulation -->
          <div class="reveal-on-scroll delay-400 mt-4 p-3 bg-rose-50 border border-rose-200 rounded-xl flex items-center gap-2 text-xs text-rose-800 font-medium">
            <AlertTriangle class="w-4 h-4 text-rose-600 shrink-0" />
            <span><strong>EWS-001:</strong> Pagu Akun 521111 Jurusan Mesin tersisa 14.2% (&lt; 15%).</span>
          </div>
        </div>
      </div>
    </section>

    <!-- SECTION 3: INTERACTIVE ROLE JOURNEY HUB (7 Roles) -->
    <section id="roles" class="py-20 px-6 bg-slate-50 border-b border-slate-200">
      <div class="max-w-6xl mx-auto">
        <div class="reveal-on-scroll text-center max-w-2xl mx-auto mb-12">
          <span class="text-xs font-bold text-sky-600 uppercase tracking-widest block mb-2">ROLE-BASED ACCESS CONTROL (RBAC)</span>
          <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
            Struktur 7 Peran Pengguna & Tanggung Jawab
          </h2>
          <p class="text-slate-600 text-xs sm:text-sm mt-2">
            Klik salah satu peran di bawah untuk melihat rincian tugas spesifik dan cakupan akses datanya.
          </p>
        </div>

        <!-- Role Selector Pills -->
        <div class="reveal-on-scroll delay-100 flex flex-wrap justify-center gap-2 mb-10 bg-white p-1.5 rounded-2xl max-w-3xl mx-auto border border-slate-200 shadow-sm">
          <button 
            v-for="r in rolesDetail" 
            :key="r.key" 
            @click="selectedRoleTab = r.key" 
            :class="['px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 hover:scale-105', selectedRoleTab === r.key ? 'bg-sky-600 text-white shadow-md' : 'text-slate-600 hover:text-slate-900']"
          >
            <Users class="w-3.5 h-3.5" />
            {{ r.key }}
          </button>
        </div>

        <!-- Selected Role Display Card -->
        <div v-for="r in rolesDetail" :key="r.key" v-show="selectedRoleTab === r.key">
          <div class="reveal-on-scroll delay-200 bg-white border border-slate-200 rounded-3xl p-8 shadow-sm grid grid-cols-1 lg:grid-cols-12 gap-8 items-start hover:shadow-md transition">
            <!-- Left Info -->
            <div class="lg:col-span-5 space-y-4">
              <span :class="['px-3 py-1 rounded-full text-xs font-extrabold border inline-block', r.badgeColor]">{{ r.badge }}</span>
              <h3 class="text-2xl font-bold text-slate-900">{{ r.title }}</h3>
              <p class="text-slate-600 text-xs sm:text-sm leading-relaxed">{{ r.description }}</p>

              <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 mt-4">
                <span class="text-xs font-bold text-sky-700 block mb-1">Cakupan Akses Data:</span>
                <p class="text-xs text-slate-600">{{ r.scope }}</p>
              </div>

              <div class="pt-2">
                <Link href="/login" class="inline-flex items-center gap-2 text-xs font-bold text-sky-600 hover:text-sky-700 transition">
                  Coba Login sebagai {{ r.key }} <ChevronRight class="w-4 h-4" />
                </Link>
              </div>
            </div>

            <!-- Right Responsibilities Checklist -->
            <div class="lg:col-span-7 bg-slate-50 border border-slate-200 rounded-2xl p-6">
              <h4 class="text-sm font-bold text-slate-900 mb-4 flex items-center gap-2">
                <CheckCircle2 class="w-4 h-4 text-sky-600" />
                Tugas & Tanggung Jawab Utama
              </h4>
              <ul class="space-y-3">
                <li v-for="(task, i) in r.responsibilities" :key="i" class="flex items-start gap-3 text-xs sm:text-sm text-slate-700">
                  <div class="w-5 h-5 rounded-full bg-sky-100 text-sky-800 flex items-center justify-center shrink-0 font-bold text-[10px] mt-0.5">
                    {{ i + 1 }}
                  </div>
                  <span>{{ task }}</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- SECTION 4: CONNECTED WORKFLOW PIPELINE -->
    <section id="workflow" class="py-20 px-6 bg-white border-b border-slate-200">
      <div class="max-w-6xl mx-auto">
        <div class="reveal-on-scroll text-center max-w-2xl mx-auto mb-12">
          <span class="text-xs font-bold text-sky-600 uppercase tracking-widest block mb-2">END-TO-END PIPELINE</span>
          <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
            5 Tahapan Alur Pengajuan Anggaran
          </h2>
          <p class="text-slate-600 text-xs sm:text-sm mt-2">
            Dari penyusunan draft hingga realisasi belanja dan monitoring laporan akuntabilitas.
          </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
          <div 
            v-for="(w, i) in workflowSteps" 
            :key="w.step" 
            :class="[
              'reveal-on-scroll bg-slate-50 p-6 rounded-2xl border border-slate-200 relative group hover:border-sky-500 hover:bg-sky-50/50 hover:-translate-y-1 hover:shadow-lg transition-all duration-300',
              i === 0 ? 'delay-100' : (i === 1 ? 'delay-200' : (i === 2 ? 'delay-300' : (i === 3 ? 'delay-400' : 'delay-500')))
            ]"
          >
            <span class="text-2xl font-black text-slate-300 group-hover:text-sky-600 font-mono block mb-2 transition">
              {{ w.step }}
            </span>
            <h3 class="text-sm font-bold text-slate-900 mb-1">{{ w.title }}</h3>
            <span class="text-[11px] font-semibold text-sky-700 block mb-2">{{ w.role }}</span>
            <p class="text-slate-600 text-xs leading-relaxed">{{ w.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- SECTION 5: MODUL PENGENDALIAN & MONITORING -->
    <section id="features" class="py-20 px-6 bg-slate-50 border-b border-slate-200">
      <div class="max-w-5xl mx-auto">
        <div class="reveal-on-scroll text-center max-w-2xl mx-auto mb-12">
          <span class="text-xs font-bold text-sky-600 uppercase tracking-widest block mb-2">FITUR UTAMA SISTEM</span>
          <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
            Instrumen Pengendalian Anggaran Kampus
          </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="reveal-on-scroll delay-100 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm hover:border-sky-300 hover:shadow-md hover:-translate-y-1 transition-all duration-300">
            <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center mb-4">
              <AlertTriangle class="w-5 h-5" />
            </div>
            <h3 class="text-sm font-bold text-slate-900 mb-2">Rule-Based Early Warning (EWS)</h3>
            <p class="text-slate-600 text-xs leading-relaxed">
              Deteksi dini otomatis notifikasi saldo kritis (&lt; 15%) dan pemblokiran instan terhadap upaya pengajuan yang melebihi pagu (*overbudget*).
            </p>
          </div>

          <div class="reveal-on-scroll delay-200 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm hover:border-sky-300 hover:shadow-md hover:-translate-y-1 transition-all duration-300">
            <div class="w-10 h-10 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center mb-4">
              <Lock class="w-5 h-5" />
            </div>
            <h3 class="text-sm font-bold text-slate-900 mb-2">Budget Reservation Locking</h3>
            <p class="text-slate-600 text-xs leading-relaxed">
              Penguncian saldo komitmen secara atomik saat pengajuan disetujui, menjamin saldo tidak terpakai ganda oleh kegiatan lain.
            </p>
          </div>

          <div class="reveal-on-scroll delay-300 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm hover:border-indigo-300 hover:shadow-md hover:-translate-y-1 transition-all duration-300">
            <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center mb-4">
              <ShieldCheck class="w-5 h-5" />
            </div>
            <h3 class="text-sm font-bold text-slate-900 mb-2">100% Audit Trail Security</h3>
            <p class="text-slate-600 text-xs leading-relaxed">
              Pencatatan transparan seluruh mutasi data, alamat IP, timestamp, dan payload perubahan untuk penelusuran akuntabilitas finansial.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- FOOTER -->
    <footer class="reveal-on-scroll py-12 px-6 text-center bg-white">
      <div class="max-w-3xl mx-auto">
        <img src="/image/logo.webp" alt="Logo SIPEDA FT UNSOED" class="w-12 h-12 object-contain rounded-xl mx-auto mb-3 shadow-sm hover:scale-105 transition" />
        <h3 class="text-base font-bold text-slate-900 mb-1">SIPEDA FT UNSOED</h3>
        <p class="text-xs text-slate-500 mb-6 max-w-md mx-auto">
          Sistem Informasi Pagu & Pengendalian Anggaran Fakultas Teknik Universitas Jenderal Soedirman &copy; 2026.
        </p>
        <div class="flex justify-center gap-4">
          <Link href="/login" class="px-6 py-2.5 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-bold transition shadow-md shadow-sky-600/20 hover:scale-105">
            Masuk ke Aplikasi
          </Link>
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.reveal-on-scroll {
  opacity: 0;
  transform: translateY(28px);
  transition: opacity 0.7s cubic-bezier(0.16, 1, 0.3, 1), transform 0.7s cubic-bezier(0.16, 1, 0.3, 1);
  will-change: opacity, transform;
}

.reveal-on-scroll.is-revealed {
  opacity: 1;
  transform: translateY(0);
}

.delay-100 { transition-delay: 100ms; }
.delay-200 { transition-delay: 200ms; }
.delay-300 { transition-delay: 300ms; }
.delay-400 { transition-delay: 400ms; }
.delay-500 { transition-delay: 500ms; }
</style>
