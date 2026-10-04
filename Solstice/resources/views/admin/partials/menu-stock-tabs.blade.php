<nav class="mb-4 flex flex-wrap gap-2" aria-label="Menu dan stok">
    <a href="{{ route('admin.menus') }}"
       @if(request()->routeIs('admin.menus')) aria-current="page" @endif
       class="rounded-full px-4 py-2 text-xs font-semibold {{ request()->routeIs('admin.menus') ? 'bg-brand text-white' : 'bg-cream text-brand hover:bg-sand' }}">
        Menu &amp; Kategori
    </a>
    <a href="{{ route('admin.stock') }}"
       @if(request()->routeIs('admin.stock', 'admin.stock.critical')) aria-current="page" @endif
       class="rounded-full px-4 py-2 text-xs font-semibold {{ request()->routeIs('admin.stock', 'admin.stock.critical') ? 'bg-brand text-white' : 'bg-cream text-brand hover:bg-sand' }}">
        Stok Menu
    </a>
</nav>
