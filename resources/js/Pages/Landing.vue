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
  ChevronRight,
  ChevronLeft,
  Search,
  Sliders,
  Check,
  Compass,
  Layers,
  FileCheck
} from 'lucide-vue-next';

defineProps({
  fiscalYear: [String, Number],
  departmentsCount: Number,
  user: Object,
});

const isScrolled = ref(false);
const searchQuery = ref('');

const handleScroll = () => {
  isScrolled.value = window.scrollY > 40;
};

onMounted(() => {
  window.addEventListener('scroll', handleScroll, { passive: true });
  handleScroll();

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
  <div class="bg-slate-100 min-h-screen text-slate-800 font-sans antialiased selection:bg-sky-600 selection:text-white">
    <!-- MAIN CONTAINER CARD -->
    <div class="max-w-[1440px] mx-auto bg-white min-h-screen shadow-sm pb-16">
      
      <!-- HEADER -->
      <header class="px-6 lg:px-12 py-5 flex items-center justify-between sticky top-0 bg-white/90 backdrop-blur-md z-50 border-b border-slate-100">
        <!-- Logo -->
        <div class="flex items-center gap-3">
          <img src="/image/SIKARALOGO.png" alt="Logo SIKARA" class="w-9 h-9 object-contain rounded-xl shadow-sm" />
          <div>
            <span class="text-xl font-black tracking-tight text-slate-900 uppercase">SIKARA</span>
            <span class="text-[10px] font-bold text-sky-700 block -mt-1">FT UNSOED</span>
          </div>
        </div>

        <!-- Navigation -->
        <nav class="hidden md:flex items-center space-x-6 text-xs font-semibold text-slate-600">
          <a class="hover:text-sky-600 transition-colors" href="#about">Tentang SIKARA</a>
          <a class="hover:text-sky-600 transition-colors" href="#simulation">Simulasi Live</a>
          <a class="hover:text-sky-600 transition-colors" href="#roles">Peran &amp; Role</a>
          <a class="hover:text-sky-600 transition-colors" href="#workflow">Siklus Workflow</a>
          <a class="hover:text-sky-600 transition-colors" href="#features">Fitur Unggulan</a>
        </nav>

        <!-- Right Actions -->
        <div class="flex items-center space-x-4">
          <!-- Search -->
          <div class="relative hidden lg:block">
            <input 
              v-model="searchQuery" 
              class="pl-4 pr-10 py-2 rounded-full bg-slate-100 border-none text-xs w-64 focus:ring-2 focus:ring-sky-600 outline-none placeholder-slate-400" 
              placeholder="Cari fitur atau usulan..." 
              type="text"
            />
            <button class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
              <Search class="w-3.5 h-3.5" />
            </button>
          </div>

          <!-- CTA -->
          <Link 
            v-if="user" 
            href="/dashboard" 
            class="bg-sky-600 text-white px-5 py-2.5 rounded-full text-xs font-bold hover:bg-sky-500 transition-all shadow-md shadow-sky-600/20 flex items-center gap-1.5"
          >
            <UserCheck class="w-3.5 h-3.5" /> Dashboard ({{ user.role }})
          </Link>
          <Link 
            v-else 
            href="/login" 
            class="bg-sky-600 text-white px-6 py-2.5 rounded-full text-xs font-bold hover:bg-sky-500 transition-all shadow-md shadow-sky-600/20 flex items-center gap-1.5"
          >
            <LogIn class="w-3.5 h-3.5" /> Login Aplikasi
          </Link>
        </div>
      </header>

      <!-- MAIN CONTENT -->
      <main class="px-6 lg:px-12 space-y-16 mt-4">
        
        <!-- HERO SECTION WITH VIDEO BACKGROUND -->
        <section id="about" class="relative h-[560px] sm:h-[600px] rounded-[32px] overflow-hidden shadow-xl border border-slate-200">
          <!-- Video Background -->
          <video 
            autoplay 
            loop 
            muted 
            playsinline 
            class="absolute inset-0 w-full h-full object-cover scale-105"
            src="/image/landingvideo.MP4"
          ></video>

          <!-- Gradient Overlay -->
          <div class="absolute inset-0 bg-gradient-to-r from-slate-950/90 via-slate-900/75 to-sky-950/60 backdrop-blur-[1px]"></div>

          <!-- Hero Content (Vertically Centered on Left) -->
          <div class="absolute inset-0 flex flex-col justify-center p-8 sm:p-12 lg:p-16 text-white max-w-3xl">
            <span class="px-3.5 py-1 bg-sky-500/20 border border-sky-400/30 text-sky-200 text-xs font-bold rounded-full uppercase tracking-wider inline-block mb-3.5 self-start">
              Fakultas Teknik Universitas Jenderal Soedirman
            </span>

            <h1 class="text-2xl sm:text-4xl lg:text-5xl font-black leading-tight mb-3.5 tracking-tight max-w-2xl">
              SIKARA — Sistem Informasi Kendali Anggaran dan Realisasi
            </h1>

            <p class="text-xs sm:text-sm lg:text-base mb-6 max-w-xl text-slate-200 leading-relaxed font-sans opacity-95">
              Transparansi alokasi dana, otomatisasi reservasi komitmen pagu, dan pemantauan real-time LRA Fakultas Teknik Universitas Jenderal Soedirman.
            </p>

            <div class="flex flex-wrap items-center gap-3">
              <Link href="/login" class="bg-sky-600 hover:bg-sky-500 text-white px-6 py-3 rounded-full font-bold text-xs flex items-center gap-2 shadow-lg shadow-sky-600/30 hover:scale-105">
                <KeyRound class="w-4 h-4" />
                Mulai Sesi Demo &amp; Login
              </Link>
              <a href="#workflow" class="bg-white/10 border border-white/30 text-white px-6 py-3 rounded-full font-bold text-xs backdrop-blur-md flex items-center gap-2 hover:scale-105">
                Pelajari Alur Sistem
                <ArrowRight class="w-4 h-4" />
              </a>
            </div>
          </div>
        </section>

        <!-- FEATURES & STATS SECTION -->
        <section class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start pt-4">
          <!-- Left Side: Trust & Stats -->
          <div class="lg:col-span-5 space-y-6">
            <span class="text-xs font-bold tracking-wider text-sky-600 uppercase block">MENGAPA MEMILIH SIKARA</span>
            <h2 class="text-2xl sm:text-4xl font-black text-slate-900 leading-tight tracking-tight">
              Instrumen Pengendalian Anggaran Fakultas Terpercaya
            </h2>
            <p class="text-slate-600 text-xs sm:text-sm leading-relaxed">
              SIKARA dirancang khusus untuk memastikan setiap rupiah alokasi pagu jurusan terpantau secara transparan, akuntabel, dan bebas dari *overbudget*.
            </p>

            <!-- Social Links / Quick Nav -->
            <div class="flex items-center space-x-3 pt-2">
              <a class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-700 hover:bg-sky-600 hover:text-white transition-colors" href="https://ft.unsoed.ac.id" target="_blank" title="Website FT UNSOED">
                <Building2 class="w-4 h-4" />
              </a>
              <a class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-700 hover:bg-sky-600 hover:text-white transition-colors" href="/login" title="Akses Login">
                <KeyRound class="w-4 h-4" />
              </a>
              <a class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-700 hover:bg-sky-600 hover:text-white transition-colors" href="#simulation" title="Simulasi Pagu">
                <BarChart3 class="w-4 h-4" />
              </a>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-3 gap-4 border-t border-slate-200 pt-6">
              <div>
                <div class="w-10 h-10 rounded-full bg-sky-100 flex items-center justify-center text-sky-700 mb-2">
                  <Building2 class="w-4 h-4" />
                </div>
                <p class="text-xl sm:text-2xl font-black text-slate-900 font-sans">5 Jurusan</p>
                <p class="text-[11px] text-slate-500 mt-0.5">JTIF, JTS, JTE, JTG, JTI</p>
              </div>
              <div>
                <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-700 mb-2">
                  <Users class="w-4 h-4" />
                </div>
                <p class="text-xl sm:text-2xl font-black text-slate-900 font-sans">7 Roles</p>
                <p class="text-[11px] text-slate-500 mt-0.5">RBAC 7 Tingkat Peran</p>
              </div>
              <div>
                <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-700 mb-2">
                  <ShieldCheck class="w-4 h-4" />
                </div>
                <p class="text-xl sm:text-2xl font-black text-slate-900 font-sans">100% Audit</p>
                <p class="text-[11px] text-slate-500 mt-0.5">Audit Trail &amp; EWS Guard</p>
              </div>
            </div>
          </div>

          <!-- Right Side: Feature Cards -->
          <div class="lg:col-span-7 space-y-4">
            <!-- Card 1: EWS -->
            <div class="bg-slate-50 p-6 rounded-3xl flex items-start space-x-5 hover:shadow-md transition-all border border-slate-200">
              <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center shadow-sm shrink-0">
                <AlertTriangle class="w-6 h-6" />
              </div>
              <div>
                <h3 class="font-bold text-slate-900 text-base mb-1">Rule-Based Early Warning (EWS)</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                  Deteksi dini otomatis notifikasi saldo kritis (&lt; 15%) dan pemblokiran instan terhadap upaya pengajuan yang melebihi pagu (*overbudget*).
                </p>
              </div>
            </div>

            <!-- Card 2: Locking -->
            <div class="bg-slate-50 p-6 rounded-3xl flex items-start space-x-5 hover:shadow-md transition-all border border-slate-200">
              <div class="w-12 h-12 rounded-2xl bg-sky-600 text-white flex items-center justify-center shadow-sm shrink-0">
                <Lock class="w-6 h-6" />
              </div>
              <div>
                <h3 class="font-bold text-slate-900 text-base mb-1">Budget Reservation Locking</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                  Penguncian saldo komitmen secara atomik saat pengajuan disetujui, menjamin saldo tidak terpakai ganda oleh kegiatan lain.
                </p>
              </div>
            </div>

            <!-- Card 3: Audit Trail -->
            <div class="bg-slate-50 p-6 rounded-3xl flex items-start space-x-5 hover:shadow-md transition-all border border-slate-200">
              <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center shadow-sm shrink-0">
                <ShieldCheck class="w-6 h-6" />
              </div>
              <div>
                <h3 class="font-bold text-slate-900 text-base mb-1">100% Audit Trail Security</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                  Pencatatan transparan seluruh mutasi data, alamat IP, timestamp, dan payload perubahan untuk penelusuran akuntabilitas finansial.
                </p>
              </div>
            </div>
          </div>
        </section>

        <!-- SIMULASI LIVE SECTION -->
        <section id="simulation" class="bg-slate-50 rounded-[32px] p-8 lg:p-12 relative border border-slate-200">
          <div class="flex justify-between items-end mb-8">
            <div>
              <span class="text-xs font-bold text-sky-600 uppercase tracking-wider block mb-1">SIMULASI LIVE &amp; MONITORING</span>
              <h2 class="text-2xl sm:text-3xl font-black text-slate-900">Kendali Anggaran Real-Time &amp; Transparan</h2>
            </div>
            <p class="text-xs text-slate-500 max-w-xs text-right hidden md:block leading-relaxed">
              Gambaran visual bagaimana SIKARA secara otomatis mengunci komitmen dan menghitung sisa saldo bebas.
            </p>
          </div>

          <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
            <!-- Header Status -->
            <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-slate-100">
              <div>
                <span class="text-[11px] font-sans font-bold text-sky-700 block">SIMULATION STATE ACTIVE &bull; TA {{ fiscalYear }}</span>
                <h3 class="text-base sm:text-lg font-bold text-slate-900">Mata Anggaran: 521111 - Belanja Bahan Operasional Lab</h3>
              </div>
              <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-xs font-bold text-slate-700">Live Simulation Status</span>
              </div>
            </div>

            <!-- Total Pagu Banner -->
            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 flex flex-wrap items-center justify-between gap-4">
              <div>
                <span class="text-xs text-slate-500 font-semibold block">Total Pagu Alokasi Aktif (Allocated)</span>
                <span class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight font-sans">Rp 12.500.000.000</span>
              </div>
              <span class="px-3 py-1.5 bg-sky-100 text-sky-800 text-xs font-extrabold rounded-xl border border-sky-200">
                Formula: Pagu Aktif = Realisasi + Komitmen + Saldo Bebas
              </span>
            </div>

            <!-- 3 Vertical Bars Visualization Chart -->
            <div class="p-6 bg-slate-50/50 rounded-2xl border border-slate-200 space-y-4">
              <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider text-center">
                Distribusi Alokasi Anggaran (Visualisasi Komparasi)
              </h4>

              <div class="h-44 flex items-end justify-around gap-6 sm:gap-12 border-b border-slate-200 pb-2 px-4">
                <!-- Bar 1: Realisasi -->
                <div class="flex-1 flex flex-col items-center h-full justify-end group">
                  <div class="text-[10px] font-bold text-slate-700 mb-1 opacity-0 group-hover:opacity-100 transition">41.6%</div>
                  <div class="w-full max-w-[56px] bg-slate-900 rounded-t-xl transition-all duration-500 relative flex items-start justify-center pt-1.5 shadow-sm" style="height: 65%;">
                    <span class="text-[9px] font-bold text-white tracking-wider">41.6%</span>
                  </div>
                </div>

                <!-- Bar 2: Komitmen (Reserved) -->
                <div class="flex-1 flex flex-col items-center h-full justify-end group">
                  <div class="text-[10px] font-bold text-slate-700 mb-1 opacity-0 group-hover:opacity-100 transition">16.8%</div>
                  <div class="w-full max-w-[56px] bg-sky-600 rounded-t-xl transition-all duration-500 relative flex items-start justify-center pt-1.5 shadow-sm" style="height: 35%;">
                    <span class="text-[9px] font-bold text-white tracking-wider">16.8%</span>
                  </div>
                </div>

                <!-- Bar 3: Saldo Bebas (Available) -->
                <div class="flex-1 flex flex-col items-center h-full justify-end group">
                  <div class="text-[10px] font-bold text-slate-700 mb-1 opacity-0 group-hover:opacity-100 transition">41.6%</div>
                  <div class="w-full max-w-[56px] bg-sky-200 border border-sky-300 rounded-t-xl transition-all duration-500 relative flex items-start justify-center pt-1.5 shadow-sm" style="height: 65%;">
                    <span class="text-[9px] font-bold text-sky-900 tracking-wider">41.6%</span>
                  </div>
                </div>
              </div>

              <!-- Labels -->
              <div class="flex justify-around text-center text-[11px] font-semibold text-slate-700">
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
            </div>

            <!-- Activity Items -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 flex items-center justify-between text-xs">
                <div class="flex items-center gap-2.5">
                  <div class="w-8 h-8 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center font-bold">
                    <FileText class="w-4 h-4" />
                  </div>
                  <div>
                    <span class="font-bold text-slate-900 block text-[11px]">Pengadaan Perangkat Lab Informatika</span>
                    <span class="text-[10px] text-slate-500">PTK Informatika &bull; Rp 45.000.000</span>
                  </div>
                </div>
                <span class="px-2 py-0.5 bg-sky-100 text-sky-800 text-[10px] font-extrabold rounded-md">APPROVED</span>
              </div>

              <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 flex items-center justify-between text-xs">
                <div class="flex items-center gap-2.5">
                  <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">
                    <CheckCircle2 class="w-4 h-4" />
                  </div>
                  <div>
                    <span class="font-bold text-slate-900 block text-[11px]">Seminar Nasional Teknik Elektro</span>
                    <span class="text-[10px] text-slate-500">PTK Elektro &bull; Rp 28.500.000</span>
                  </div>
                </div>
                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-extrabold rounded-md">COMPLETED</span>
              </div>
            </div>

            <!-- EWS Alert -->
            <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl flex items-center gap-2 text-xs text-rose-800 font-medium">
              <AlertTriangle class="w-4 h-4 text-rose-600 shrink-0" />
              <span><strong>EWS-001:</strong> Pagu Akun 521111 Jurusan Mesin tersisa 14.2% (&lt; 15%).</span>
            </div>
          </div>
        </section>

        <!-- RBAC 7 ROLES SECTION -->
        <section id="roles">
          <div class="text-center max-w-2xl mx-auto mb-8">
            <span class="text-xs font-bold text-sky-600 uppercase tracking-wider block mb-1">ROLE-BASED ACCESS CONTROL (RBAC)</span>
            <h2 class="text-2xl sm:text-3xl font-black text-slate-900">Struktur 7 Peran Pengguna &amp; Tanggung Jawab</h2>
            <p class="text-xs text-slate-500 mt-1">Klik salah satu peran di bawah untuk melihat rincian tugas spesifik dan cakupan akses datanya.</p>
          </div>

          <!-- Role Selector Pills -->
          <div class="flex flex-wrap justify-center gap-2 mb-8 bg-slate-50 p-2 rounded-2xl max-w-3xl mx-auto border border-slate-200">
            <button 
              v-for="r in rolesDetail" 
              :key="r.key" 
              @click="selectedRoleTab = r.key" 
              :class="['px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2', selectedRoleTab === r.key ? 'bg-sky-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900']"
            >
              <Users class="w-3.5 h-3.5" />
              {{ r.key }}
            </button>
          </div>

          <!-- Selected Role Display Card -->
          <div v-for="r in rolesDetail" :key="r.key" v-show="selectedRoleTab === r.key">
            <div class="bg-slate-50 border border-slate-200 rounded-3xl p-8 shadow-sm grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
              <!-- Left Info -->
              <div class="lg:col-span-5 space-y-4">
                <span :class="['px-3 py-1 rounded-full text-xs font-extrabold border inline-block', r.badgeColor]">{{ r.badge }}</span>
                <h3 class="text-2xl font-bold text-slate-900">{{ r.title }}</h3>
                <p class="text-slate-600 text-xs leading-relaxed">{{ r.description }}</p>

                <div class="p-4 bg-white rounded-2xl border border-slate-200">
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
              <div class="lg:col-span-7 bg-white border border-slate-200 rounded-2xl p-6">
                <h4 class="text-sm font-bold text-slate-900 mb-4 flex items-center gap-2">
                  <CheckCircle2 class="w-4 h-4 text-sky-600" />
                  Tugas &amp; Tanggung Jawab Utama
                </h4>
                <ul class="space-y-3">
                  <li v-for="(task, i) in r.responsibilities" :key="i" class="flex items-start gap-3 text-xs text-slate-700">
                    <div class="w-5 h-5 rounded-full bg-sky-100 text-sky-800 flex items-center justify-center shrink-0 font-bold text-[10px] mt-0.5">
                      {{ i + 1 }}
                    </div>
                    <span>{{ task }}</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </section>

        <!-- STEPS SECTION -->
        <section id="workflow" class="border-t border-slate-200 pt-12 pb-4">
          <div class="text-center max-w-2xl mx-auto mb-10">
            <span class="text-xs font-bold text-sky-600 uppercase tracking-wider block mb-1">END-TO-END PIPELINE</span>
            <h2 class="text-2xl font-black text-slate-900">5 Tahapan Alur Pengajuan Anggaran</h2>
            <p class="text-xs text-slate-500 mt-1">Dari penyusunan draft hingga realisasi belanja dan monitoring laporan akuntabilitas.</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
            <div 
              v-for="w in workflowSteps" 
              :key="w.step" 
              class="bg-slate-50 p-6 rounded-3xl border border-slate-200 space-y-2 hover:border-sky-300 transition"
            >
              <span class="text-xs font-bold text-sky-700 font-sans block">{{ w.step }}</span>
              <h3 class="font-bold text-slate-900 text-sm mb-1">{{ w.title }}</h3>
              <span class="text-[10px] font-bold text-slate-500 uppercase block">{{ w.role }}</span>
              <p class="text-xs text-slate-600 leading-relaxed">{{ w.desc }}</p>
            </div>
          </div>
        </section>

        <!-- FOOTER SECTION -->
        <footer class="mt-12 pt-12 pb-6 border-t border-slate-200 text-center space-y-4">
          <div class="flex items-center justify-center gap-3">
            <img src="/image/SIKARALOGO.png" alt="Logo SIKARA FT UNSOED" class="w-10 h-10 object-contain rounded-xl shadow-sm" />
            <div class="text-left">
              <span class="text-base font-black text-slate-900 tracking-tight uppercase block leading-tight">SIKARA FT UNSOED</span>
              <span class="text-[10px] text-sky-700 font-bold block">Fakultas Teknik Universitas Jenderal Soedirman</span>
            </div>
          </div>
          <p class="text-xs text-slate-500 max-w-md mx-auto leading-relaxed">
            SIKARA &copy; 2026 &bull; Sistem Informasi Kendali Anggaran dan Realisasi. Seluruh hak cipta dilindungi undang-undang.
          </p>
          <div class="pt-2">
            <Link href="/login" class="inline-flex items-center gap-2 px-6 py-2.5 bg-sky-600 hover:bg-sky-500 text-white rounded-full text-xs font-bold transition shadow-md shadow-sky-600/20">
              <LogIn class="w-3.5 h-3.5" /> Masuk ke Aplikasi SIKARA
            </Link>
          </div>
        </footer>
      </main>
    </div>
  </div>
</template>

<style scoped>
.reveal-on-scroll {
  opacity: 1;
  transform: translateY(0);
}
</style>
