@php
    $user = auth()->user();
    $isAdmin = $user->role === 'admin';

    // Ikon SVG (24x24, stroke)
    $icons = [
        'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
        'menu'      => '<path d="M4 10h13v4a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5v-4Z"/><path d="M17 11h1.5a2.5 2.5 0 0 1 0 5H17"/><path d="M8 3v3M12 3v3"/>',
        'users'     => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.5 3-6 6.5-6s6.5 2.5 6.5 6"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14.5c2 .7 3.5 2.6 3.5 5.5"/>',
        'stock'     => '<rect x="3" y="4" width="18" height="5" rx="1"/><path d="M5 9v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V9"/><path d="M10 13h4"/>',
        'report'    => '<path d="M4 4v16h16"/><path d="M8 16v-5M12 16V8M16 16v-3"/>',
        'orders'    => '<path d="M9 6h11M9 12h11M9 18h11"/><path d="M4 6h.01M4 12h.01M4 18h.01"/>',
        'search'    => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'card'      => '<rect x="2.5" y="5" width="19" height="14" rx="2"/><path d="M2.5 10h19"/>',
        'printer'   => '<path d="M7 9V3h10v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M7 14h10v7H7z"/>',
        'logout'    => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/>',
        'shield'    => '<path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6l-8-3Z"/>',
        'lock'      => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
    ];
    $svg = fn ($key, $size = 18) => '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$icons[$key].'</svg>';

    // [label, route, ikon]
    $menu = $isAdmin
        ? [['Dashboard','admin.dashboard','dashboard'],['Menu & Stok','admin.menus','menu'],['Kelola Pengguna','admin.users','users'],['Laporan','admin.reports','report']]
        : [['Dashboard','kasir.dashboard','dashboard'],['Daftar Pesanan','kasir.orders','orders'],['Cari Order','kasir.search','search'],['Verifikasi Bayar','kasir.verify','card'],['Cetak Struk','kasir.receipt','printer']];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - Solstice Coffe</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@700;800&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen font-sans text-sm text-ink {{ $isAdmin ? 'bg-[#FAF8F5]' : 'bg-[#FDFDFD]' }}">

<aside class="sticky top-0 flex h-screen w-[210px] shrink-0 flex-col bg-brand p-4 text-cream max-md:w-[70px] max-md:px-2">
    <div class="mb-3 flex items-center gap-2 border-b border-cream/20 pb-3 max-md:justify-center">
        <div class="grid size-8 shrink-0 place-items-center overflow-hidden rounded-md bg-sand text-lg">
            {{-- <img src="{{ asset('images/logo-solstice.png') }}" alt="Logo" class="size-full object-cover"> --}}
            ☕
        </div>
        <div class="max-md:hidden">
            <strong class="block font-display text-sm">Solstice Coffe</strong>
            <small class="text-[9px] opacity-75">{{ $isAdmin ? 'Admin' : 'Kasir' }} Panel v1.0</small>
        </div>
    </div>

    <nav class="flex flex-1 flex-col gap-1">
        @foreach ($menu as [$label, $route, $icon])
            @php
                $isActive = $route === 'admin.menus'
                    ? request()->routeIs('admin.menus', 'admin.stock', 'admin.stock.critical')
                    : request()->routeIs($route);
            @endphp
            <a href="{{ Route::has($route) ? route($route) : '#' }}"
               title="{{ $label }}" class="flex items-center gap-2 rounded-md px-2.5 py-2 transition {{ $isActive ? 'bg-cream font-semibold text-brand' : 'hover:bg-cream/10' }}">
                {!! $svg($icon, 15) !!}<span class="max-md:hidden">{{ $label }}</span>
            </a>
        @endforeach
    </nav>

    <div class="flex items-center gap-2">
        <div class="grid size-7 shrink-0 place-items-center rounded-full bg-cream text-[10px] font-bold text-brand">
            {{ $isAdmin ? 'AD' : 'K1' }}
        </div>
        <div class="min-w-0 max-md:hidden">
            <strong class="block truncate">{{ $user->name }}</strong>
            <small class="text-[11px] opacity-75">{{ $isAdmin ? 'Super Admin' : 'Kasir Shift Pagi' }}</small>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="ml-auto max-md:hidden">
            @csrf
            <button type="submit" title="Keluar" aria-label="Keluar"
                    class="grid size-9 place-items-center rounded-lg text-red-400 hover:bg-cream/10 hover:text-red-300">
                {!! $svg('logout', 20) !!}
            </button>
        </form>
    </div>
</aside>

<div class="min-w-0 flex-1">
    <header class="flex h-[54px] items-center justify-between border-b border-line bg-white px-6 max-sm:px-3">
        <h1 class="font-display text-xl font-bold text-brand max-sm:text-base">@yield('title')</h1>
        <div class="flex items-center gap-2 text-[10px] text-muted max-sm:gap-1">
            <span class="flex items-center gap-2">
                {!! $svg($isAdmin ? 'shield' : 'lock', 16) !!}
                {{ $isAdmin ? 'Sesi Admin Aman' : 'Sesi Kasir Aktif' }}
            </span>
            <span class="rounded-md border border-brand bg-cream px-3 py-1.5 font-bold text-brand">
                {{ $isAdmin ? 'Sistem Aktif' : 'Shift #2' }}
            </span>
        </div>
    </header>
    <section class="p-6 max-sm:p-3">
        @if (session('success'))<div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-xs text-green-800">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-800">{{ session('error') }}</div>@endif
        @if ($errors->any())<div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-800"><ul class="list-disc pl-4">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @yield('content')
    </section>
</div>

</body>
</html>
