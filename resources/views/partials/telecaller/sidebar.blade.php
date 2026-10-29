@php
    $nav = $activeNav ?? 'queue';
    $navLink = function (string $key, string $route, string $icon, string $label) use ($nav) {
        $active = $nav === $key;
        $base = $active
            ? 'nav-btn flex items-center gap-md px-md py-3 border-l-4 border-secondary-container bg-white/10 text-on-primary font-normal transition-all'
            : 'nav-btn flex items-center gap-md px-md py-3 text-on-primary/70 hover:text-on-primary hover:bg-white/5 transition-colors';
        $iconClass = $active ? 'material-symbols-outlined text-on-primary' : 'material-symbols-outlined';
        return compact('base', 'iconClass', 'route', 'icon', 'label', 'active');
    };
    $items = [
        $navLink('queue', route('telecaller.dashboard'), 'phone_in_talk', 'Dial Queue'),
        $navLink('leads', route('telecaller.leads'), 'group', 'Leads'),
        $navLink('customers', route('telecaller.customers'), 'apartment', 'Customers'),
        $navLink('quotations', route('telecaller.quotations'), 'request_quote', 'Quotations'),
        $navLink('calls', route('telecaller.calls'), 'history', 'Call Log'),
    ];
@endphp
<div id="telecaller-backdrop" class="fixed inset-0 bg-slate-900/50 z-40 hidden lg:hidden" onclick="closeTelecallerSidebar()" aria-hidden="true"></div>
<aside id="telecaller-sidebar" class="fixed h-[100dvh] w-[min(260px,85vw)] left-0 top-0 bg-primary border-r border-outline-variant flex flex-col py-base z-50 max-lg:-translate-x-full lg:translate-x-0 transition-transform duration-200 ease-out">
    <div class="px-md mb-lg flex items-start justify-between gap-2">
        <div class="min-w-0">
            <img alt="Tegh Cables (India) Pvt. Ltd." class="h-10 sm:h-11 w-full max-w-[200px] object-contain object-left" src="{{ asset('images/logo.png') }}"/>
            <p class="sidebar-text text-[10px] text-secondary-container mt-2 px-0.5 tracking-widest uppercase">Inside Sales</p>
        </div>
        <button type="button" onclick="closeTelecallerSidebar()" class="lg:hidden shrink-0 w-9 h-9 flex items-center justify-center rounded-full text-on-primary/80 hover:bg-white/10" aria-label="Close menu">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>
    <nav class="flex-1 space-y-1 overflow-y-auto overscroll-contain px-xs pb-4">
        @foreach($items as $item)
        <a class="{{ $item['base'] }}" href="{{ $item['route'] }}" onclick="closeTelecallerSidebar()">
            <span class="{{ $item['iconClass'] }}">{{ $item['icon'] }}</span>
            <span class="sidebar-text text-sm">{{ $item['label'] }}</span>
        </a>
        @endforeach
    </nav>
    <div class="px-xs pt-base border-t border-white/10 shrink-0">
        <div class="flex items-center justify-between gap-md px-md py-3 text-on-primary/70">
            <div class="flex items-center gap-md min-w-0">
                <span class="material-symbols-outlined">headset_mic</span>
                <span class="sidebar-text text-xs truncate">{{ auth()->user()->name ?? 'Telecaller' }}</span>
            </div>
            <form method="POST" action="{{ route('telecaller.logout') }}">
                @csrf
                <button type="submit" title="Logout" class="hover:text-on-primary transition-colors">
                    <span class="material-symbols-outlined text-[20px]">logout</span>
                </button>
            </form>
        </div>
    </div>
</aside>
