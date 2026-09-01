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
  UserCheck,
  ChevronRight,
  Search,
  Sliders,
  Layers,
  FileCheck,
  Info,
  Clock,
  Sparkles
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
});

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
});

const selectedRoleTab = ref('PTK');

const rolesDetail = [
  {
    key: 'PTK',
    title: 'Petugas Pengelola Keuangan (Operator Jurusan)',
    badge: 'Level Operasional',
    badgeColor: 'bg-sky-100 text-sky-800 border-sky-300',
    description: 'Pencatat transaksi belanja unit jurusan dengan master-assisted quick entry tanpa perlu input kode berulang.',
    responsibilities: [
      'Memilih pos anggaran aktif dari Master Pagu tanpa input kode manual.',
      'Mencatat bukti transaksi, uraian belanja, dan nominal pengeluaran.',
      'Menyimpan draft atau mengajukan transaksi ke tahap Dalam Proses.',
      'Melakukan perbaikan data jika terdapat pengembalian berkas SPJ.'
    ],
    scope: 'Terisolasi khusus untuk unit jurusan yang ditugaskan (department_id).'
  },
  {
    key: 'PTU / Bendahara',
    title: 'PTU & Bendahara Pengeluaran Pembantu',
    badge: 'Level Verifikasi & Kas',
    badgeColor: 'bg-blue-100 text-blue-800 border-blue-300',
    description: 'Pemeriksa kelengkapan berkas SPJ transaksi dan pengelola kas belanja operasional fakultas.',
    responsibilities: [
      'Memeriksa kelengkapan bukti administrasi dan dokumen SPJ transaksi.',
      'Memantau antrean transaksi berjalan (Workbench Verifikasi).',
      'Memfinalisasi transaksi menjadi realisasi belanja lunas.',
      'Mengembalikan berkas transaksi yang belum memenuhi syarat administrasi.'
    ],
    scope: 'Akses pemeriksaan dan finalisasi transaksi seluruh unit Fakultas Teknik.'
  },
  {
    key: 'Kajur / Kaprodi',
    title: 'Ketua Jurusan & Ketua Program Studi',
    badge: 'Monitoring Read-Only',
    badgeColor: 'bg-indigo-100 text-indigo-800 border-indigo-300',
    description: 'Pimpinan unit yang memantau sisa pagu, serapan realisasi belanja, dan aktivitas anggaran (Read-Only).',
    responsibilities: [
      'Memantau realisasi anggaran dan ketersediaan saldo pos anggaran jurusan.',
      'Melihat rincian transaksi belanja yang berkaitan dengan program studi.',
      'Memantau indikator peringatan dini (EWS) saldo kritis.',
      'Mengevaluasi laporan penyerapan anggaran unit kerja.'
    ],
    scope: 'Monitoring data pagu dan transaksi sesuai jurusan & program studi masing-masing.'
  },
  {
    key: 'Kabag',
    title: 'Kepala Bagian Tata Usaha / Keuangan',
    badge: 'Kontrol Anggaran',
    badgeColor: 'bg-amber-100 text-amber-800 border-amber-300',
    description: 'Pengendali operasional anggaran fakultas yang mengawasi kepatuhan pagu dan pergeseran revisi anggaran.',
    responsibilities: [
      'Mengawasi ketersediaan saldo dan serapan anggaran 5 jurusan.',
      'Menerapkan pergeseran dan usulan revisi pagu anggaran (Budget Revision).',
      'Memantau kepatuhan aturan Rule-Based Budget Control (RBC).',
      'Mengevaluasi rekapitulasi realisasi belanja operasional fakultas.'
    ],
    scope: 'Akses eksekutif kontrol anggaran seluruh unit di Fakultas Teknik.'
  },
  {
    key: 'Wakil Dekan',
    title: 'Wakil Dekan II (Bidang Umum & Keuangan)',
    badge: 'Level Strategis',
    badgeColor: 'bg-purple-100 text-purple-800 border-purple-300',
    description: 'Pimpinan fakultas yang memantau kesehatan finansial, tren penyerapan 5 jurusan, dan arahan kebijakan.',
    responsibilities: [
      'Memantau tren penyerapan anggaran bulanan seluruh unit jurusan.',
      'Menerima notifikasi Early Warning System untuk pos saldo kritis (< 15%).',
      'Mengevaluasi Laporan Realisasi Anggaran (LRA) berkala per semester.',
      'Merumuskan kebijakan efisiensi dan pergeseran anggaran fakultas.'
    ],
    scope: 'Akses monitoring strategis dan decision support seluruh Fakultas Teknik.'
  },
  {
    key: 'Dekan',
    title: 'Dekan Fakultas Teknik (Pimpinan Utama)',
    badge: 'Pimpinan Tertinggi',
    badgeColor: 'bg-emerald-100 text-emerald-800 border-emerald-300',
    description: 'Kuasa Pengguna Anggaran (KPA) yang memantau performa makro anggaran dan akuntabilitas institusi.',
    responsibilities: [
      'Memantau pencapaian serapan anggaran global Fakultas Teknik.',
      'Mengevaluasi ringkasan performa finansial dan indikator risiko EWS.',
      'Meninjau laporan akuntabilitas keuangan dan audit trail fakultas.',
      'Memberikan arahan prioritas strategis belanja fakultas.'
    ],
    scope: 'Akses pimpinan tertinggi seluruh unit kerja di Fakultas Teknik.'
  },
  {
    key: 'Admin',
    title: 'Administrator Sistem',
    badge: 'Pengelola Sistem',
    badgeColor: 'bg-rose-100 text-rose-800 border-rose-300',
    description: 'Pengelola teknis yang mengatur konfigurasi master pagu, multi-role pengguna, dan integritas data.',
    responsibilities: [
      'Mengelola akun pengguna, penetapan peran (Multi-Role), dan unit kerja.',
      'Mengelola master tahun anggaran, versi pagu DIPA, dan sumber dana.',
      'Memantau validitas pemetaan kode anggaran dan kesehatan database.',
      'Memeriksa Comprehensive Audit Trail log aktivitas sistem.'
    ],
    scope: 'Akses penuh ke modul konfigurasi, master data, user management, dan audit trail.'
  }
];

const systemWorkflowSteps = [
  {
    num: '1',
    title: 'Master Pagu',
    desc: 'Data anggaran tahun berjalan dan versi revisi dimasukkan terlebih dahulu sebagai acuan pengendali.'
  },
  {
    num: '2',
    title: 'PTK Memilih Pos',
    desc: 'PTK memilih pos anggaran aktif dari daftar tanpa perlu mengetik ulang struktur kode bertingkat.'
  },
  {
    num: '3',
    title: 'Catat Transaksi',
    desc: 'PTK cukup mengisi nomor bukti, tanggal, uraian belanja, nominal, dan data transaksi yang diperlukan.'
  },
  {
    num: '4',
    title: 'Sistem Mengendalikan',
    desc: 'SIKARA menghitung saldo dan mencegah penggunaan melampaui sisa anggaran secara otomatis (RBC-001).'
  },
  {
    num: '5',
    title: 'Monitoring & Laporan',
    desc: 'Informasi realisasi dapat dipantau langsung per jurusan sesuai kewenangan dan diekspor ke format LRA.'
  }
];

const highlightedFeatures = [
  {
    title: 'Master-Assisted Transaction Entry',
    desc: 'Pemilihan pos anggaran otomatis memuat struktur mata anggaran, kode subkomponen, dan sisa saldo tanpa input berulang.',
    icon: Wallet,
    color: 'bg-sky-100 text-sky-700'
  },
  {
    title: 'Rule-Based Budget Control',
    desc: 'Pengendalian server-side aktif (RBC-001) yang memblokir transaksi overbudget dan mengunci komitmen secara atomik.',
    icon: Lock,
    color: 'bg-indigo-100 text-indigo-700'
  },
  {
    title: 'Monitoring per Jurusan',
    desc: 'Pemisahan dan pemantauan realisasi anggaran per jurusan (JTIF, JTS, JTE, JTG, JTI) dan program studi secara terstruktur.',
    icon: Building2,
    color: 'bg-emerald-100 text-emerald-700'
  },
  {
    title: 'Early Warning System (EWS)',
    desc: 'Deteksi dini otomatis notifikasi saldo kritis (< 15%), high utilization, zero spending, dan aging verifikasi.',
    icon: AlertTriangle,
    color: 'bg-amber-100 text-amber-700'
  },
  {
    title: 'Comprehensive Audit Trail',
    desc: 'Pencatatan transparan seluruh mutasi data, alamat IP, timestamp, dan payload perubahan untuk akuntabilitas institusi.',
    icon: ShieldCheck,
    color: 'bg-blue-100 text-blue-700'
  },
  {
    title: 'Reporting & Export',
    desc: 'Penyajian laporan LRA komprehensif secara berkala dengan dukungan ekspor siap cetak PDF Landscape dan CSV.',
    icon: BarChart3,
    color: 'bg-purple-100 text-purple-700'
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
          <a class="hover:text-sky-600 transition-colors" href="#alur">Alur Sistem</a>
          <a class="hover:text-sky-600 transition-colors" href="#features">Fitur Unggulan</a>
          <a class="hover:text-sky-600 transition-colors" href="#simulation">Simulasi Live</a>
          <a class="hover:text-sky-600 transition-colors" href="#roles">Struktur Peran</a>
        </nav>

        <!-- Right Actions -->
        <div class="flex items-center space-x-4">
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
            <LogIn class="w-3.5 h-3.5" /> Masuk ke SIKARA
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
              Monitoring dan Pengendalian Realisasi Anggaran Fakultas Teknik
            </span>

            <h1 class="text-2xl sm:text-4xl lg:text-5xl font-black leading-tight mb-2 tracking-tight max-w-2xl">
              SIKARA
            </h1>
            <h2 class="text-lg sm:text-2xl font-bold text-sky-200 mb-3.5 tracking-tight">
              Sistem Informasi Kendali Anggaran dan Realisasi
            </h2>

            <p class="text-xs sm:text-sm lg:text-base mb-6 max-w-xl text-slate-200 leading-relaxed font-sans opacity-95">
              Sistem internal Fakultas Teknik untuk membantu pencatatan realisasi, pengendalian ketersediaan anggaran, monitoring per jurusan, dan penyajian informasi keuangan secara terstruktur.
            </p>

            <div class="flex flex-wrap items-center gap-3">
              <Link href="/login" class="bg-sky-600 hover:bg-sky-500 text-white px-6 py-3 rounded-full font-bold text-xs flex items-center gap-2 shadow-lg shadow-sky-600/30 hover:scale-105 transition">
                <KeyRound class="w-4 h-4" />
                Masuk ke SIKARA
              </Link>
              <a href="#alur" class="bg-white/10 border border-white/30 text-white px-6 py-3 rounded-full font-bold text-xs backdrop-blur-md flex items-center gap-2 hover:scale-105 transition">
                Lihat Alur Sistem
                <ArrowRight class="w-4 h-4" />
              </a>
            </div>

            <div class="mt-6 flex items-center gap-2 text-[11px] text-slate-300">
              <Info class="w-3.5 h-3.5 text-sky-400 shrink-0" />
              <span>Monitoring berdasarkan data internal terkini Fakultas Teknik UNSOED.</span>
            </div>
          </div>
        </section>

        <!-- SECTION: BAGAIMANA SIKARA BEKERJA (5 STEPS PIPELINE) -->
        <section id="alur" class="pt-4 space-y-8">
          <div class="text-center max-w-2xl mx-auto">
            <span class="text-xs font-bold text-sky-600 uppercase tracking-wider block mb-1">ALUR KERJA RINGKAS</span>
            <h2 class="text-2xl sm:text-3xl font-black text-slate-900">Bagaimana SIKARA Bekerja</h2>
            <p class="text-xs text-slate-500 mt-1">
              Prinsip minimal input dengan kendali anggaran otomatis dari master pagu hingga monitoring realisasi.
            </p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 sm:gap-6">
            <div 
              v-for="w in systemWorkflowSteps" 
              :key="w.num" 
              class="bg-slate-50 p-6 rounded-3xl border border-slate-200/80 hover:border-sky-300 hover:shadow-md transition space-y-3 relative group"
            >
              <div class="w-9 h-9 rounded-2xl bg-sky-600 text-white font-black text-sm flex items-center justify-center shadow-md shadow-sky-600/20 group-hover:scale-105 transition">
                {{ w.num }}
              </div>
              <h3 class="font-bold text-slate-900 text-sm">{{ w.title }}</h3>
              <p class="text-xs text-slate-600 leading-relaxed">{{ w.desc }}</p>
            </div>
          </div>
        </section>

        <!-- SECTION: FITUR UNGGULAN -->
        <section id="features" class="border-t border-slate-200 pt-12 space-y-8">
          <div class="text-center max-w-2xl mx-auto">
            <span class="text-xs font-bold text-sky-600 uppercase tracking-wider block mb-1">KAPABILITAS UTAMA</span>
            <h2 class="text-2xl sm:text-3xl font-black text-slate-900">Fitur Unggulan SIKARA</h2>
            <p class="text-xs text-slate-500 mt-1">
              Dirancang untuk efisiensi operator jurusan dan akurasi monitoring pimpinan fakultas.
            </p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div 
              v-for="(f, idx) in highlightedFeatures" 
              :key="idx" 
              class="bg-slate-50 p-6 rounded-3xl border border-slate-200/80 hover:border-sky-300 hover:shadow-md transition space-y-3"
            >
              <div :class="['w-12 h-12 rounded-2xl flex items-center justify-center shadow-sm', f.color]">
                <component :is="f.icon" class="w-6 h-6" />
              </div>
              <h3 class="font-bold text-slate-900 text-sm">{{ f.title }}</h3>
              <p class="text-xs text-slate-600 leading-relaxed">{{ f.desc }}</p>
            </div>
          </div>
        </section>

        <!-- SIMULASI LIVE SECTION -->
        <section id="simulation" class="bg-slate-50 rounded-[32px] p-8 lg:p-12 relative border border-slate-200">
          <div class="flex justify-between items-end mb-8">
            <div>
              <span class="text-xs font-bold text-sky-600 uppercase tracking-wider block mb-1">SIMULASI LIVE &amp; MONITORING</span>
              <h2 class="text-2xl sm:text-3xl font-black text-slate-900">Kendali Anggaran Berbasis Aturan (RBC-001)</h2>
            </div>
            <p class="text-xs text-slate-500 max-w-xs text-right hidden md:block leading-relaxed">
              Monitoring ketersediaan dana secara otomatis dan pencegahan pengeluaran melampaui sisa pagu.
            </p>
          </div>

          <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-6">
            <!-- Header Status -->
            <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-slate-100">
              <div>
                <span class="text-[11px] font-sans font-bold text-sky-700 block">SIMULATION STATE ACTIVE &bull; TA {{ fiscalYear }} (RM)</span>
                <h3 class="text-base sm:text-lg font-bold text-slate-900">Mata Anggaran: 521211 - Belanja Bahan Praktikum Lab</h3>
              </div>
              <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-xs font-bold text-slate-700">Internal Data Live</span>
              </div>
            </div>

            <!-- Total Pagu Banner -->
            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 flex flex-wrap items-center justify-between gap-4">
              <div>
                <span class="text-xs text-slate-500 font-semibold block">Total Pagu Alokasi Aktif (Rev 02)</span>
                <span class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight font-sans">Rp 12.500.000.000</span>
              </div>
              <span class="px-3 py-1.5 bg-sky-100 text-sky-800 text-xs font-extrabold rounded-xl border border-sky-200">
                Pagu Aktif = Realisasi + Dalam Proses + Saldo Tersedia
              </span>
            </div>

            <!-- 3 Vertical Bars Visualization Chart -->
            <div class="p-6 bg-slate-50/50 rounded-2xl border border-slate-200 space-y-4">
              <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider text-center">
                Distribusi Realisasi &amp; Ketersediaan Saldo
              </h4>

              <div class="h-44 flex items-end justify-around gap-6 sm:gap-12 border-b border-slate-200 pb-2 px-4">
                <!-- Bar 1: Realisasi -->
                <div class="flex-1 flex flex-col items-center h-full justify-end group">
                  <div class="text-[10px] font-bold text-slate-700 mb-1 opacity-0 group-hover:opacity-100 transition">41.6%</div>
                  <div class="w-full max-w-[56px] bg-slate-900 rounded-t-xl transition-all duration-500 relative flex items-start justify-center pt-1.5 shadow-sm" style="height: 65%;">
                    <span class="text-[9px] font-bold text-white tracking-wider">41.6%</span>
                  </div>
                </div>

                <!-- Bar 2: Dalam Proses -->
                <div class="flex-1 flex flex-col items-center h-full justify-end group">
                  <div class="text-[10px] font-bold text-slate-700 mb-1 opacity-0 group-hover:opacity-100 transition">16.8%</div>
                  <div class="w-full max-w-[56px] bg-sky-600 rounded-t-xl transition-all duration-500 relative flex items-start justify-center pt-1.5 shadow-sm" style="height: 35%;">
                    <span class="text-[9px] font-bold text-white tracking-wider">16.8%</span>
                  </div>
                </div>

                <!-- Bar 3: Saldo Tersedia -->
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
                  <span class="block font-bold text-sky-700">Dalam Proses</span>
                  <span class="text-[10px] font-medium text-slate-500">Rp 2,1 M</span>
                </div>
                <div class="flex-1">
                  <span class="block font-bold text-slate-900">Saldo Tersedia</span>
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
                    <span class="font-bold text-slate-900 block text-[11px]">Bahan Praktikum Pemrograman Dasar</span>
                    <span class="text-[10px] text-slate-500">PTK Informatika &bull; Rp 5.000.000</span>
                  </div>
                </div>
                <span class="px-2.5 py-0.5 bg-indigo-100 text-indigo-800 text-[10px] font-extrabold rounded-md">PROCESSING</span>
              </div>

              <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 flex items-center justify-between text-xs">
                <div class="flex items-center gap-2.5">
                  <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">
                    <CheckCircle2 class="w-4 h-4" />
                  </div>
                  <div>
                    <span class="font-bold text-slate-900 block text-[11px]">Uji Kuat Tekan Beton Laboratorium</span>
                    <span class="text-[10px] text-slate-500">PTK Sipil &bull; Rp 35.000.000</span>
                  </div>
                </div>
                <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-extrabold rounded-md">FINAL</span>
              </div>
            </div>

            <!-- EWS Alert -->
            <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl flex items-center gap-2 text-xs text-amber-900 font-medium">
              <AlertTriangle class="w-4 h-4 text-amber-600 shrink-0" />
              <span><strong>EWS-001:</strong> Sisa saldo bebas pos 521211 Jurusan Elektro tersisa 14.2% (&lt; 15%). Perlu pengendalian belanja.</span>
            </div>
          </div>
        </section>

        <!-- ROLE OVERVIEW SECTION -->
        <section id="roles" class="border-t border-slate-200 pt-12">
          <div class="text-center max-w-2xl mx-auto mb-8">
            <span class="text-xs font-bold text-sky-600 uppercase tracking-wider block mb-1">STRUKTUR PENGGUNA</span>
            <h2 class="text-2xl sm:text-3xl font-black text-slate-900">Peran dan Tanggung Jawab Pengguna</h2>
            <p class="text-xs text-slate-500 mt-1">Pilih peran pengguna di bawah untuk melihat tanggung jawab dan cakupan akses data.</p>
          </div>

          <!-- Role Selector Pills -->
          <div class="flex flex-wrap justify-center gap-2 mb-8 bg-slate-50 p-2 rounded-2xl max-w-4xl mx-auto border border-slate-200">
            <button 
              v-for="r in rolesDetail" 
              :key="r.key" 
              @click="selectedRoleTab = r.key" 
              :class="['px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5', selectedRoleTab === r.key ? 'bg-sky-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900']"
            >
              <Users class="w-3.5 h-3.5" />
              {{ r.key }}
            </button>
          </div>

          <!-- Selected Role Display Card -->
          <div v-for="r in rolesDetail" :key="r.key" v-show="selectedRoleTab === r.key">
            <div class="bg-slate-50 border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
              <!-- Left Info -->
              <div class="lg:col-span-5 space-y-4">
                <span :class="['px-3 py-1 rounded-full text-xs font-extrabold border inline-block', r.badgeColor]">{{ r.badge }}</span>
                <h3 class="text-xl sm:text-2xl font-bold text-slate-900">{{ r.title }}</h3>
                <p class="text-slate-600 text-xs leading-relaxed">{{ r.description }}</p>

                <div class="p-4 bg-white rounded-2xl border border-slate-200">
                  <span class="text-xs font-bold text-sky-700 block mb-1">Cakupan Akses Data:</span>
                  <p class="text-xs text-slate-600">{{ r.scope }}</p>
                </div>

                <div class="pt-2">
                  <Link href="/login" class="inline-flex items-center gap-2 text-xs font-bold text-sky-600 hover:text-sky-700 transition">
                    Coba Akses sebagai {{ r.key }} <ChevronRight class="w-4 h-4" />
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
              <LogIn class="w-3.5 h-3.5" /> Masuk ke SIKARA
            </Link>
          </div>
        </footer>
      </main>
    </div>
  </div>
</template>

<style scoped>
</style>
