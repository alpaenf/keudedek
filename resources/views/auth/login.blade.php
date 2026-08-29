<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPEDA - Switch Role / Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { font-family: 'Poppins', sans-serif; }</style>
</head>
<body class="bg-slate-900 min-h-screen flex items-center justify-center p-6 text-slate-100">
    <div class="max-w-md w-full bg-slate-800 p-8 rounded-3xl border border-slate-700 shadow-2xl">
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-sky-600 rounded-2xl flex items-center justify-center mx-auto mb-4 text-white shadow-lg shadow-sky-600/30">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
            <h1 class="text-2xl font-bold text-white">SIPEDA FT UNSOED</h1>
            <p class="text-xs text-sky-400 font-medium mt-1">Pilih Role Pengguna untuk Memulai Sesi Demo</p>
        </div>

        <div class="space-y-3 mb-8">
            @foreach($users as $usr)
                <form action="{{ route('login.as', $usr) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full p-4 bg-slate-700/60 hover:bg-sky-600 border border-slate-600 hover:border-sky-500 rounded-2xl flex items-center justify-between transition group text-left">
                        <div>
                            <span class="font-bold text-sm text-white group-hover:text-white block">{{ $usr->name }}</span>
                            <span class="text-xs text-slate-400 group-hover:text-sky-100 block">{{ $usr->role }} &bull; {{ $usr->department?->name ?? 'Fakultas Teknik' }}</span>
                        </div>
                        <svg class="w-5 h-5 text-slate-400 group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </form>
            @endforeach
        </div>

        <div class="text-center text-[11px] text-slate-500">
            Internal Financial Monitoring & Control Layer System &copy; 2026
        </div>
    </div>
</body>
</html>
