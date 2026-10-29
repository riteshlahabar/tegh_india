<header class="fixed top-0 right-0 left-0 lg:left-[260px] h-16 sm:h-20 bg-surface/80 backdrop-blur-md border-b border-surface-container-highest flex justify-between items-center gap-2 px-3 sm:px-4 lg:px-xl z-40">
    <div class="flex items-center gap-2 sm:gap-4 min-w-0 flex-1">
        <button type="button" onclick="toggleTelecallerSidebar()" class="lg:hidden shrink-0 w-10 h-10 flex items-center justify-center rounded-full hover:bg-surface-container-high text-primary" aria-label="Open menu">
            <span class="material-symbols-outlined">menu</span>
        </button>
        <div class="min-w-0">
            <h1 class="font-title-sm text-base sm:text-title-sm text-primary truncate">{{ $pageTitle ?? 'Inside Sales' }}</h1>
            <p class="hidden sm:block text-[10px] font-label-caps text-on-surface-variant/70 uppercase truncate">{{ $pageSubtitle ?? 'Telecaller portal' }}</p>
        </div>
    </div>
    <div class="flex items-center gap-2 shrink-0">
        <span class="hidden sm:inline text-xs font-bold tracking-widest uppercase text-secondary">{{ auth()->user()->branch ?? '' }}</span>
        <form method="POST" action="{{ route('telecaller.logout') }}">
            @csrf
            <button type="submit" class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-surface-container-high text-on-surface-variant" title="Logout">
                <span class="material-symbols-outlined">logout</span>
            </button>
        </form>
    </div>
</header>
